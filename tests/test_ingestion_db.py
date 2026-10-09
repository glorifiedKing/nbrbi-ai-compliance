import sys
import os
import uuid
import psycopg
from pgvector.psycopg import register_vector

sys.path.insert(0, r"E:\nbrbi-ai-fastapi")

from app.services.document_ingestion import RegulatoryIngestionEngine
from app.config import settings

def test_ingest_sample():
    sample_pdf = r"E:\nbrbi-ai-compliance\public\sample_documents\THE-BUILDING-CONTROL-REGULATIONS-2020.pdf"
    doc_id = str(uuid.uuid4())

    print(f"Creating test regulatory document in database with ID: {doc_id}")
    
    with psycopg.connect(settings.DATABASE_URL) as conn:
        with conn.cursor() as cur:
            # Find a user or create temporary test admin
            cur.execute("SELECT id FROM users LIMIT 1;")
            user_row = cur.fetchone()
            if not user_row:
                user_id = str(uuid.uuid4())
                cur.execute(
                    "INSERT INTO users (id, name, email, password, created_at, updated_at) VALUES (%s, %s, %s, %s, NOW(), NOW());",
                    (user_id, "Test Admin", "admin@nbrb.ug", "secret")
                )
            else:
                user_id = user_row[0]

            cur.execute("""
            INSERT INTO regulatory_documents (
                id, title, category, edition_year, file_path, status, is_active, uploaded_by, created_at, updated_at
            ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW());
            """, (doc_id, "The Building Control Regulations 2020", "general_building_control", "2020", "sample.pdf", "pending", True, user_id))
            conn.commit()

    print("Ingesting and vectorizing PDF...")
    total = RegulatoryIngestionEngine.ingest_document(doc_id, sample_pdf)
    print(f"[OK] Ingestion complete. Vectorized {total} chunks in PostgreSQL pgvector.")

    # Verify chunks in DB
    with psycopg.connect(settings.DATABASE_URL) as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT COUNT(*) FROM regulatory_chunks WHERE regulatory_document_id = %s;", (doc_id,))
            count = cur.fetchone()[0]
            print(f"[VERIFIED] Found {count} chunks in 'regulatory_chunks' table.")

            cur.execute("SELECT status, total_chunks FROM regulatory_documents WHERE id = %s;", (doc_id,))
            row = cur.fetchone()
            print(f"[VERIFIED] regulatory_documents status = '{row[0]}', total_chunks = {row[1]}")

if __name__ == "__main__":
    test_ingest_sample()
