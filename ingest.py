import os
import json
from typing import List, Dict
from langchain_google_genai import GoogleGenerativeAIEmbeddings
from langchain_community.vectorstores import Chroma
from langchain.schema import Document
from langchain.text_splitter import RecursiveCharacterTextSplitter

# ========================================
# 환경 변수 설정
# ========================================
os.environ["GOOGLE_API_KEY"] = "YOUR_GOOGLE_API_KEY"

# ========================================
# 1. JSON 문서 로드
# ========================================
def load_json_documents(data_dir: str = "./data") -> List[Document]:
    """
    JSON 파일에서 문서 로드
    각 타임스탬프별 raw_content를 Document로 변환
    """
    documents = []
    
    for filename in os.listdir(data_dir):
        if not filename.endswith('.json'):
            continue
            
        filepath = os.path.join(data_dir, filename)
        print(f"📂 Loading: {filename}")
        
        with open(filepath, 'r', encoding='utf-8') as f:
            data = json.load(f)
        
        for item in data:
            # 메타데이터 구성
            metadata = {
                "id": item.get("id"),
                "source_type": item.get("source_type"),
                "title": item.get("title"),
                "original_url": item.get("original_url"),
                "url_full": item.get("url_full"),
                "timestamp_str": item.get("timestamp_str"),
                "timestamp_seconds": item.get("timestamp_seconds", 0),
                "source_file": filename,
            }
            
            # Document 생성
            doc = Document(
                page_content=item.get("raw_content", ""),
                metadata=metadata
            )
            documents.append(doc)
    
    print(f"✅ Loaded {len(documents)} documents")
    return documents


# ========================================
# 2. 스마트 청킹 (연관 청크 병합)
# ========================================
def smart_chunking(documents: List[Document], 
                   window_size: int = 3,
                   max_chunk_size: int = 1200) -> List[Document]:
    """
    연관된 타임스탬프의 청크를 병합
    
    Args:
        documents: 원본 문서 리스트
        window_size: 병합할 연속 청크 개수 (기본 3개)
        max_chunk_size: 최대 청크 크기 (기본 1200자)
    
    Returns:
        병합된 문서 리스트
    """
    merged_docs = []
    
    # 같은 영상끼리 그룹화
    video_groups = {}
    for doc in documents:
        video_id = doc.metadata.get("original_url")
        if video_id not in video_groups:
            video_groups[video_id] = []
        video_groups[video_id].append(doc)
    
    # 각 영상별로 처리
    for video_id, docs in video_groups.items():
        # 타임스탬프 순서로 정렬
        docs.sort(key=lambda x: x.metadata.get("timestamp_seconds", 0))
        
        i = 0
        while i < len(docs):
            # window_size만큼 병합
            merge_docs = docs[i:i + window_size]
            
            # 병합된 내용 생성
            merged_content = ""
            all_timestamps = []
            first_metadata = merge_docs[0].metadata.copy()
            
            for doc in merge_docs:
                timestamp = doc.metadata.get("timestamp_str", "00:00")
                all_timestamps.append(timestamp)
                merged_content += f"[{timestamp}] {doc.page_content}\n\n"
                
                # 최대 크기 체크
                if len(merged_content) >= max_chunk_size:
                    break
            
            # 메타데이터 업데이트
            first_metadata["id"] = f"{first_metadata['id']}_merged_{i}"
            first_metadata["merged_timestamps"] = all_timestamps
            first_metadata["chunk_type"] = "merged"
            
            # 키워드 추출 (간단한 버전)
            keywords = extract_keywords(merged_content)
            first_metadata["keywords"] = keywords
            
            # 새 문서 생성
            merged_doc = Document(
                page_content=merged_content.strip(),
                metadata=first_metadata
            )
            merged_docs.append(merged_doc)
            
            # 다음 윈도우로 이동 (50% 오버랩)
            i += max(1, window_size // 2)
    
    print(f"✅ Created {len(merged_docs)} merged chunks from {len(documents)} original docs")
    return merged_docs


def extract_keywords(text: str) -> List[str]:
    """
    텍스트에서 키워드 추출 (간단한 버전)
    실제로는 KoNLPy 등 사용 권장
    """
    # 호핑 업체 키워드
    hopping_keywords = [
        "썬마호핑", "선마호핑", "썬마", "선마",
        "해적호핑", "해적", 
        "클럽세부", "클럽", 
        "한바다", 
        "바이킹호핑", "바이킹",
        "놀자호핑", "놀자",
        "락빌리지",
    ]
    
    # 주제 키워드
    topic_keywords = [
        "가족", "아이", "어린이", "유치원",
        "가격", "비용", "할인", "예약",
        "오전", "오후", "출발",
        "스노클링", "다이빙",
        "힐루뚱안", "날루수안", "올랑고", "판다논",
    ]
    
    found_keywords = []
    for keyword in hopping_keywords + topic_keywords:
        if keyword in text:
            found_keywords.append(keyword)
    
    return list(set(found_keywords))


# ========================================
# 3. ChromaDB에 저장
# ========================================
def create_vectorstore(documents: List[Document], 
                       persist_directory: str = "./chroma_db",
                       collection_name: str = "travel_knowledge_base") -> Chroma:
    """
    문서를 ChromaDB에 저장
    """
    # 기존 DB 삭제 (재구축)
    if os.path.exists(persist_directory):
        import shutil
        shutil.rmtree(persist_directory)
        print(f"🗑️  Deleted old database: {persist_directory}")
    
    # Embeddings 생성
    embeddings = GoogleGenerativeAIEmbeddings(
        model="models/text-embedding-004"
    )
    
    # ChromaDB 생성
    print("🔄 Creating vector store...")
    vectorstore = Chroma.from_documents(
        documents=documents,
        embedding=embeddings,
        persist_directory=persist_directory,
        collection_name=collection_name
    )
    
    print(f"✅ Successfully created vector store with {len(documents)} documents")
    return vectorstore


# ========================================
# 4. 테스트 검색
# ========================================
def test_search(vectorstore: Chroma, query: str = "썬마호핑"):
    """
    검색 테스트
    """
    print(f"\n🔍 Testing search for: '{query}'")
    print("=" * 80)
    
    # 검색 실행
    results = vectorstore.similarity_search(query, k=5)
    
    print(f"Found {len(results)} results:\n")
    
    for i, doc in enumerate(results, 1):
        print(f"Result {i}:")
        print(f"  ID: {doc.metadata.get('id')}")
        print(f"  Title: {doc.metadata.get('title')}")
        print(f"  Timestamp: {doc.metadata.get('timestamp_str')}")
        print(f"  URL: {doc.metadata.get('url_full')}")
        print(f"  Keywords: {doc.metadata.get('keywords', [])}")
        print(f"  Content Preview: {doc.page_content[:150]}...")
        print()


# ========================================
# MAIN
# ========================================
if __name__ == "__main__":
    print("🚀 Starting data ingestion...\n")
    
    # 1. JSON 로드
    documents = load_json_documents("./data")
    
    print(f"\n📊 Original documents stats:")
    print(f"  Total: {len(documents)}")
    print(f"  Avg length: {sum(len(d.page_content) for d in documents) / len(documents):.0f} chars")
    
    # 2. 스마트 청킹
    merged_documents = smart_chunking(
        documents, 
        window_size=3,  # 3개 연속 청크 병합
        max_chunk_size=1200
    )
    
    print(f"\n📊 Merged documents stats:")
    print(f"  Total: {len(merged_documents)}")
    print(f"  Avg length: {sum(len(d.page_content) for d in merged_documents) / len(merged_documents):.0f} chars")
    
    # 3. Vector Store 생성
    vectorstore = create_vectorstore(merged_documents)
    
    # 4. 테스트 검색
    test_search(vectorstore, "썬마호핑")
    test_search(vectorstore, "가족 여행 호핑")
    
    print("\n✅ Ingestion completed!")
