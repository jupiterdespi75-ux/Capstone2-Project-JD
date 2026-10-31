# QRode AI service (InsightFace buffalo_l / ArcFace)

Small FastAPI app that turns a face photo into a 512-dim embedding.
Laravel calls it at `http://127.0.0.1:5000/extract-vector`.

    python -m venv venv
    venv\Scripts\activate            (PowerShell; on Linux/macOS: source venv/bin/activate)
    pip install -r requirements.txt
    uvicorn ai_service:app --host 127.0.0.1 --port 5000

First start downloads the model (~280 MB) to ~/.insightface/models/buffalo_l.
Check http://127.0.0.1:5000/health -> {"status":"ok"} before registering visitors.
Keep it bound to 127.0.0.1: it has no authentication.
