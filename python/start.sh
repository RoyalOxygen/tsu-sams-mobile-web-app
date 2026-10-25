#!/usr/bin/env bash
# =============================================================================
# TSU-SAMS Face Service launcher
#
# Creates a Python venv on first run, installs dependencies, then starts the
# FastAPI + InsightFace service. Works without Docker.
#
# Usage:
#   ./start.sh                 # setup (first time) + run
#   SAMS_PORT=8000 ./start.sh  # custom port
#   SAMS_DATA_DIR=./data ./start.sh
# =============================================================================
set -euo pipefail

cd "$(dirname "$0")"

if [ ! -d "venv" ]; then
    echo "[setup] Creating Python virtual environment (venv)..."
    python3 -m venv venv
    echo "[setup] Upgrading pip..."
    ./venv/bin/pip install --upgrade pip
    echo "[setup] Installing dependencies (may take a few minutes)..."
    ./venv/bin/pip install -r requirements.txt
fi

mkdir -p data

HOST="${SAMS_HOST:-0.0.0.0}"
PORT="${SAMS_PORT:-8000}"

echo "[start] Launching TSU-SAMS Face Service on ${HOST}:${PORT}"
exec ./venv/bin/uvicorn main:app --host "$HOST" --port "$PORT"
