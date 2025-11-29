#!/bin/bash

# 색상 정의
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 철산랜드 AI 서버 가동 준비 중...${NC}"

# 스크립트가 있는 폴더로 이동
cd "$(dirname "$0")"

# 1. 데이터 확인
echo -e "\n${YELLOW}[1/3] 데이터 확인 중...${NC}"
count=$(ls ../data/*.json 2>/dev/null | wc -l)
if [ "$count" != "0" ]; then
    echo "✅ ${count}개의 여행 데이터 파일을 발견했습니다."
else
    echo "⚠️  ../data 폴더에 JSON 파일이 없습니다!"
    echo "   테스트를 위해 샘플 데이터를 사용하거나, 파일을 넣어주세요."
fi

# 2. 가상환경 설정 및 설치 (완전 초기화)
echo -e "\n${YELLOW}[2/3] 가상환경 설정 및 설치 (초기화 중)...${NC}"

# 기존 가상환경 삭제 (오류 방지)
if [ -d "venv" ]; then
    rm -rf venv
fi

# 가상환경 생성
echo "📦 가상환경을 새로 생성합니다..."
python3 -m venv venv

# 가상환경 활성화
source venv/bin/activate

# pip 업그레이드 및 라이브러리 설치
pip install --upgrade pip > /dev/null 2>&1
pip install -r requirements.txt
if [ $? -eq 0 ]; then
    echo "✅ 설치 완료"
else
    echo "❌ 설치 중 오류 발생"
    echo "   (상세 에러를 확인해주세요)"
    exit 1
fi

python ingest.py

# 3. 서버 실행
echo -e "\n${YELLOW}[3/3] AI 서버를 시작합니다!${NC}"
echo -e "${GREEN}👉 서버가 켜지면 웹사이트에서 'AI 추천' 버튼을 눌러보세요.${NC}"
echo "   (서버를 끄려면 키보드에서 Ctrl + C 를 누르세요)"
echo "---------------------------------------------------"

uvicorn main:app --reload
