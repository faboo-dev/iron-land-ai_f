#!/bin/bash

echo "🏰 Iron Land AI - Starting..."

# 기존 DB 삭제 및 재인덱싱
echo "🔄 Re-indexing database..."
rm -rf ./chroma_db
python ingest.py

# 서버 실행
echo "🚀 Starting server..."
uvicorn main:app --host 0.0.0.0 --port $PORT
