import os
import json
from typing import List
from langchain_google_genai import GoogleGenerativeAIEmbeddings
from langchain_community.vectorstores import Chroma
from langchain_core.documents import Document
from dotenv import load_dotenv

# 환경변수 로드
load_dotenv()

def load_json_documents(data_dir: str = "./data") -> List[Document]:
    """JSON 파일에서 문서 로드"""
    documents = []
    
    for filename in os.listdir(data_dir):
        if not filename.endswith('.json'):
            continue
            
        filepath = os.path.join(data_dir, filename)
        print(f"📂 Loading: {filename}")
        
        with open(filepath, 'r', encoding='utf-8') as f:
            data = json.load(f)
        
        for item in data:
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
            
            doc = Document(
                page_content=item.get("raw_content", ""),
                metadata=metadata
            )
            documents.append(doc)
    
    print(f"✅ Loaded {len(documents)} documents")
    return documents

def smart_chunking(documents: List[Document], window_size: int = 3, max_chunk_size: int = 1200) -> List[Document]:
    """연관된 타임스탬프의 청크를 병합"""
    merged_docs = []
    
    video_groups = {}
    for doc in documents:
        video_id = doc.metadata.get("original_url")
        if video_id not in video_groups:
            video_groups[video_id] = []
        video_groups[video_id].append(doc)
    
    for video_id, docs in video_groups.items():
        docs.sort(key=lambda x: x.metadata.get("timestamp_seconds", 0))
        
        i = 0
        while i < len(docs):
            merge_docs = docs[i:i + window_size]
            
            merged_content = ""
            all_timestamps = []
            first_metadata = merge_docs[0].metadata.copy()
            
            for doc in merge_docs:
                timestamp = doc.metadata.get("timestamp_str", "00:00")
                all_timestamps.append(timestamp)
                merged_content += f"[{timestamp}] {doc.page_content}\n\n"
                
                if len(merged_content) >= max_chunk_size:
                    break
            
            first_metadata["id"] = f"{first_metadata['id']}_merged_{i}"
            first_metadata["merged_timestamps"] = all_timestamps
            first_metadata["chunk_type"] = "merged"
            
            keywords = extract_keywords(merged_content)
            first_metadata["keywords"] = keywords
            
            merged_doc = Document(
                page_content=merged_content.strip(),
                metadata=first_metadata
            )
            merged_docs.append(merged_doc)
            
            i += max(1, window_size // 2)
    
    print(f"✅ Created {len(merged_docs)} merged chunks")
    return merged_docs

def extract_keywords(text: str) -> List[str]:
    """키워드 추출"""
    hopping_keywords = [
        "썬마호핑", "선마호핑", "썬마", "선마",
        "해적호핑", "해적", 
        "클럽세부", "클럽", 
        "한바다", 
        "바이킹호핑", "바이킹",
        "놀자호핑", "놀자",
        "락빌리지",
    ]
    
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

def create_vectorstore(documents: List[Document], persist_directory: str = "./chroma_db") -> Chroma:
    """ChromaDB 생성"""
    if os.path.exists(persist_directory):
        import shutil
        shutil.rmtree(persist_directory)
        print(f"🗑️  Deleted old database")
    
    embeddings = GoogleGenerativeAIEmbeddings(model="models/text-embedding-004")
    
    print("🔄 Creating vector store...")
    vectorstore = Chroma.from_documents(
        documents=documents,
        embedding=embeddings,
        persist_directory=persist_directory,
        collection_name="travel_knowledge_base"
    )
    
    print(f"✅ Vector store created with {len(documents)} documents")
    return vectorstore

if __name__ == "__main__":
    print("🚀 Starting data ingestion...\n")
    
    documents = load_json_documents("./data")
    merged_documents = smart_chunking(documents, window_size=3, max_chunk_size=1200)
    vectorstore = create_vectorstore(merged_documents)
    
    print("\n✅ Ingestion completed!")
