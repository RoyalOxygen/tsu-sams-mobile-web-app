# TSU-SAMS Face Service (InsightFace ArcFace + FastAPI)

512-dimensional ArcFace face-embedding microservice used for enrollment and
face-login verification in TSU-SAMS.

## Run without Docker

```bash
cd python
./start.sh
```

`start.sh` creates a `venv`, installs `requirements.txt` on first run, and
launches the API. The first start downloads the `buffalo_l` model (~300 MB)
into `models/` — this can take a few minutes depending on your connection.

## Manual run

```bash
cd python
python3 -m venv venv
source venv/bin/activate or .\venv\Scripts\Activate.ps1
pip install -r requirements.txt
uvicorn main:app --host 0.0.0.0 --port 8000
```

## Configuration (environment variables)

| Variable        | Default      | Purpose                          |
|-----------------|--------------|----------------------------------|
| `SAMS_HOST`     | `0.0.0.0`    | Bind address                     |
| `SAMS_PORT`     | `8000`       | Bind port                        |
| `SAMS_MODEL_DIR`| `./models`   | InsightFace model download root  |
| `SAMS_DATA_DIR` | `./data`     | Enrollment store (`enrollments.json`) |

Paths are resolved relative to this file, so the service also runs correctly
inside the Docker image where the base directory is `/app`.

## Endpoints

| Method | Path                 | Description                                        |
|--------|----------------------|----------------------------------------------------|
| GET    | `/health`            | Health check + embedding dimension                 |
| POST   | `/extract`           | Upload image -> 512-dim ArcFace embedding          |
| POST   | `/enroll`            | Upload images -> averaged embedding + enroll       |
| POST   | `/verify`            | Upload image -> 1:N match against enrolled faces   |
| POST   | `/compare`           | Compare two 512-dim descriptors                    |
| DELETE | `/delete/{student_id}` | Remove an enrollment                             |

## Docker

Build and run inside the project's `docker-compose.yml` (the `face-api`
service). The Dockerfile mounts `/app/data` for the enrollment store.
