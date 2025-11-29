#!/bin/bash

# Render에서는 venv를 만들 필요 없이 바로 실행하면 됩니다.
# 데이터가 있다면 학습(Ingest)을 먼저 수행합니다.
echo "Starting Data Ingestion..."
python ingest.py

echo "Starting Web Server..."
# Render는 $PORT 환경변수를 제공합니다.
uvicorn main:app --host 0.0.0.0 --port $PORT
