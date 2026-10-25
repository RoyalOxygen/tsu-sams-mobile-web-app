"""
TSU-SAMS Face Analysis Microservice
Powered by InsightFace ArcFace (buffalo_l) + FastAPI
Generates 512-dimensional face embeddings.
"""

import os
from fastapi import FastAPI, File, UploadFile, Form, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import List, Optional, Dict
import numpy as np
import cv2
import logging
import uvicorn
import json
from pathlib import Path

from insightface.app import FaceAnalysis

logging.basicConfig(level=logging.INFO, format="%(asctime)s | %(levelname)s | %(message)s")
logger = logging.getLogger("tsu-sams-face")

# ---------------------------------------------------------------------------
# Paths are anchored to this file so the service runs identically natively,
# inside a venv, or in the Docker image (where BASE_DIR == /app).
# Override with the SAMS_MODEL_DIR / SAMS_DATA_DIR environment variables.
# ---------------------------------------------------------------------------
BASE_DIR = Path(__file__).resolve().parent
MODEL_DIR = Path(os.getenv("SAMS_MODEL_DIR", str(BASE_DIR / "models")))
DATA_DIR = Path(os.getenv("SAMS_DATA_DIR", str(BASE_DIR / "data")))
HOST = os.getenv("SAMS_HOST", "0.0.0.0")
PORT = int(os.getenv("SAMS_PORT", "8000"))

app = FastAPI(
    title="TSU-SAMS Face Service",
    description="512-dim ArcFace descriptor extraction and 1:N comparison for TSU-SAMS",
    version="2.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

logger.info("Loading InsightFace ArcFace model (buffalo_l) on CPU...")
face_app = FaceAnalysis(name="buffalo_l", root=str(MODEL_DIR))
face_app.prepare(ctx_id=-1, det_size=(640, 640))
logger.info("Model loaded successfully.")

test_img = np.zeros((112, 112, 3), dtype=np.uint8)
test_faces = face_app.get(test_img)
EMBEDDING_DIM = len(test_faces[0].embedding) if len(test_faces) > 0 else 512
logger.info(f"ArcFace embedding dimension confirmed: {EMBEDDING_DIM}")

FACE_DESCRIPTOR_LENGTH = EMBEDDING_DIM
FACE_MATCH_THRESHOLD = 0.50

ENROLLMENTS_FILE = DATA_DIR / "enrollments.json"
ENROLLMENTS_FILE.parent.mkdir(parents=True, exist_ok=True)


def load_enrollments() -> Dict[str, List[float]]:
    if ENROLLMENTS_FILE.exists():
        try:
            with open(ENROLLMENTS_FILE, "r") as f:
                return json.load(f)
        except Exception as e:
            logger.error(f"Failed to load enrollments: {e}")
            return {}
    return {}


def save_enrollments(data: Dict[str, List[float]]) -> None:
    try:
        with open(ENROLLMENTS_FILE, "w") as f:
            json.dump(data, f)
    except Exception as e:
        logger.error(f"Failed to save enrollments: {e}")
        raise HTTPException(status_code=500, detail="Enrollment storage error")


class DescriptorComparison(BaseModel):
    descriptor1: List[float]
    descriptor2: List[float]


class FaceExtractResponse(BaseModel):
    success: bool
    message: str
    descriptor: Optional[List[float]] = None
    confidence: Optional[float] = None
    bbox: Optional[List[float]] = None


class CompareResponse(BaseModel):
    success: bool
    matched: bool
    cosine_similarity: float
    confidence: float
    score: float


def normalize_embedding(embedding: np.ndarray) -> np.ndarray:
    norm = np.linalg.norm(embedding)
    if norm == 0:
        return embedding
    return embedding / norm


def cosine_similarity(a: np.ndarray, b: np.ndarray) -> float:
    return float(np.dot(a, b))


def extract_single_face(image_bytes: bytes):
    if not image_bytes:
        raise HTTPException(status_code=400, detail="Empty image file")
    img_array = np.frombuffer(image_bytes, np.uint8)
    img = cv2.imdecode(img_array, cv2.IMREAD_COLOR)
    if img is None:
        raise HTTPException(status_code=400, detail="Invalid image format")
    faces = face_app.get(img)
    if len(faces) == 0:
        return None, "No face detected"
    if len(faces) > 1:
        return None, "Multiple faces detected. Please ensure only one face is visible."
    face = faces[0]
    embedding = normalize_embedding(face.embedding)
    return {
        "embedding": embedding.tolist(),
        "confidence": float(face.det_score),
        "bbox": face.bbox.tolist()
    }, None


@app.get("/")
def root():
    return {
        "service": "TSU-SAMS Face Analysis",
        "model": "buffalo_l (ArcFace)",
        "embedding_dim": FACE_DESCRIPTOR_LENGTH,
        "mode": "cpu",
        "status": "running"
    }


@app.get("/health")
def health():
    return {
        "status": "healthy",
        "model_loaded": True,
        "embedding_dim": FACE_DESCRIPTOR_LENGTH
    }


@app.post("/extract", response_model=FaceExtractResponse)
async def extract_face(image: UploadFile = File(...)):
    try:
        contents = await image.read()
        result, error = extract_single_face(contents)
        if error:
            return FaceExtractResponse(success=False, message=error)
        if len(result["embedding"]) != FACE_DESCRIPTOR_LENGTH:
            logger.error(f"Unexpected descriptor length: {len(result['embedding'])}")
            raise HTTPException(status_code=500, detail="Model returned invalid descriptor length")
        return FaceExtractResponse(
            success=True,
            message="Face extracted successfully",
            descriptor=result["embedding"],
            confidence=result["confidence"],
            bbox=result["bbox"]
        )
    except HTTPException:
        raise
    except Exception as e:
        logger.exception("Extract error")
        raise HTTPException(status_code=500, detail=f"Face extraction failed: {str(e)}")


@app.post("/compare", response_model=CompareResponse)
async def compare_descriptors(data: DescriptorComparison):
    try:
        if len(data.descriptor1) != FACE_DESCRIPTOR_LENGTH or len(data.descriptor2) != FACE_DESCRIPTOR_LENGTH:
            raise HTTPException(
                status_code=400,
                detail=f"Descriptors must be exactly {FACE_DESCRIPTOR_LENGTH} dimensions"
            )
        d1 = normalize_embedding(np.array(data.descriptor1, dtype=np.float32))
        d2 = normalize_embedding(np.array(data.descriptor2, dtype=np.float32))
        sim = cosine_similarity(d1, d2)
        matched = sim >= FACE_MATCH_THRESHOLD
        confidence = max(0.0, min(1.0, sim))
        return CompareResponse(
            success=True,
            matched=matched,
            cosine_similarity=round(sim, 6),
            confidence=round(confidence, 4),
            score=round(confidence * 100, 2)
        )
    except HTTPException:
        raise
    except Exception as e:
        logger.exception("Compare error")
        raise HTTPException(status_code=500, detail=f"Comparison failed: {str(e)}")


@app.post("/enroll")
async def enroll_face(student_id: int = Form(...), face_images: List[UploadFile] = File(...)):
    try:
        if len(face_images) < 1:
            raise HTTPException(status_code=400, detail="At least one face image is required")
        embeddings = []
        confidences = []
        for img in face_images:
            contents = await img.read()
            result, error = extract_single_face(contents)
            if error:
                logger.warning(f"Skipping image for student {student_id}: {error}")
                continue
            embeddings.append(np.array(result["embedding"], dtype=np.float32))
            confidences.append(result["confidence"])
        if not embeddings:
            return {"success": False, "message": "No valid face found in any of the provided images"}
        avg_embedding = np.mean(embeddings, axis=0)
        avg_embedding = normalize_embedding(avg_embedding)
        enrollments = load_enrollments()
        enrollments[str(student_id)] = avg_embedding.tolist()
        save_enrollments(enrollments)
        logger.info(f"Enrolled student {student_id} with {len(embeddings)} images")
        return {
            "success": True,
            "message": "Face enrolled successfully",
            "embedding": avg_embedding.tolist(),
            "embedding_length": len(avg_embedding),
            "confidence": round(float(np.mean(confidences)), 4)
        }
    except HTTPException:
        raise
    except Exception as e:
        logger.exception("Enroll error")
        raise HTTPException(status_code=500, detail=f"Enrollment failed: {str(e)}")


@app.post("/verify")
async def verify_face(image: UploadFile = File(...)):
    try:
        contents = await image.read()
        result, error = extract_single_face(contents)
        if error:
            return {"success": False, "message": error, "confidence": 0, "distance": 1.0}
        embedding = np.array(result["embedding"], dtype=np.float32)
        enrollments = load_enrollments()
        if not enrollments:
            return {"success": False, "message": "No enrolled faces found in the system", "confidence": 0, "distance": 1.0}
        best_match_id = None
        best_score = -1.0
        for sid, stored_emb in enrollments.items():
            stored = np.array(stored_emb, dtype=np.float32)
            sim = cosine_similarity(embedding, stored)
            if sim > best_score:
                best_score = sim
                best_match_id = sid
        if best_match_id and best_score >= FACE_MATCH_THRESHOLD:
            return {
                "success": True,
                "message": "Face verified successfully",
                "student_id": int(best_match_id),
                "confidence": round(best_score, 4),
                "distance": round(1 - best_score, 4)
            }
        else:
            return {
                "success": False,
                "message": "Face not recognized. No matching enrollment found.",
                "confidence": round(best_score, 4) if best_match_id else 0,
                "distance": round(1 - best_score, 4) if best_match_id else 1.0
            }
    except HTTPException:
        raise
    except Exception as e:
        logger.exception("Verify error")
        raise HTTPException(status_code=500, detail=f"Verification failed: {str(e)}")


@app.delete("/delete/{student_id}")
async def delete_student(student_id: int):
    try:
        enrollments = load_enrollments()
        key = str(student_id)
        if key in enrollments:
            del enrollments[key]
            save_enrollments(enrollments)
            logger.info(f"Deleted enrollment for student {student_id}")
            return {"success": True, "message": "Student face data deleted"}
        return {"success": False, "message": "Student not found in enrollment database"}
    except Exception as e:
        logger.exception("Delete error")
        raise HTTPException(status_code=500, detail=f"Delete failed: {str(e)}")


if __name__ == "__main__":
    uvicorn.run(app, host=HOST, port=PORT)
