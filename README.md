# BIMS Uganda AI Drawing Compliance Engine

Building Industry Management System (BIMS) under the National Building Review Board (NBRB) Uganda mandate.

---

## 1. Starting the Python AI Microservice (`E:\nbrbi-ai-fastapi`)

Open a terminal and run:

```powershell
cd E:\nbrbi-ai-fastapi
.\.venv\Scripts\uvicorn app.main:app --host 127.0.0.1 --port 8001 --reload
```

*(Optional - If running Celery async workers with Redis)*:
```powershell
cd E:\nbrbi-ai-fastapi
.\.venv\Scripts\celery -A app.workers.celery_app.celery worker --loglevel=info -P solo
```

FastAPI Documentation: `http://127.0.0.1:8001/api/docs`

---

## 2. Starting the Laravel Backend (`E:\nbrbi-ai-compliance`)

```powershell
cd E:\nbrbi-ai-compliance
php artisan serve
php artisan queue:work
```

- **Admin Web Dashboard**: `http://127.0.0.1:8000/admin/dashboard`
- **Default Admin Account**:
  - Email: `admin@nbrb.go.ug`
  - Password: `Admin@NBRB2026`
- **Default Engineer Account**:
  - Email: `engineer@bims.ug`
  - Password: `Engineer@2026`