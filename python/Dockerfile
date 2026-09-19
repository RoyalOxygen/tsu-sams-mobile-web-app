FROM python:3.10-slim

WORKDIR /app

ENV PYTHONUNBUFFERED=1
ENV PIP_NO_CACHE_DIR=1

# System libraries required by OpenCV / InsightFace
RUN apt-get update && apt-get install -y \
    libgl1 \
    libglib2.0-0 \
    libsm6 \
    libxext6 \
    libxrender1 \
    libgomp1 \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt .

RUN pip install --upgrade pip && \
    pip install -r requirements.txt

COPY main.py .

# InsightFace model/data locations
ENV SAMS_MODEL_DIR=/app/models
ENV SAMS_DATA_DIR=/app/data

RUN mkdir -p /app/models /app/data

EXPOSE 10000 
# 8000 on local server

CMD ["sh", "-c", "uvicorn main:app --host 0.0.0.0 --port ${PORT:-8000}"]
