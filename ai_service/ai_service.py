"""
QRode AI service — face embedding extraction (InsightFace buffalo_l / ArcFace).

Run:   uvicorn ai_service:app --host 127.0.0.1 --port 5000

POST /extract-vector   multipart field "file" (an image)
  200 -> {"embedding": [512 floats, L2-normalized], "dimension": 512, "det_score": 0.99}
  400 -> {"error": "invalid_image" | "empty_file", "message": "..."}
  413 -> {"error": "file_too_large", ...}
  422 -> {"error": "no_face_detected" | "multiple_faces_detected", ...}
  503 -> {"error": "model_not_ready", ...}

GET /health -> {"status": "ok"}

Bind to 127.0.0.1 only: this service has no authentication and handles
face data, so it must not be reachable from the network.
"""
import logging
from contextlib import asynccontextmanager

import cv2
import numpy as np
from fastapi import FastAPI, File, UploadFile
from fastapi.responses import JSONResponse
from insightface.app import FaceAnalysis

logger = logging.getLogger("qrode.ai")
logging.basicConfig(level=logging.INFO)

MAX_UPLOAD_BYTES = 10 * 1024 * 1024  # 10 MB
MAX_SIDE_PX = 1600                   # downscale huge photos before detection
EMBEDDING_DIM = 512

face_app = None  # loaded once at startup


@asynccontextmanager
async def lifespan(_: FastAPI):
    global face_app
    logger.info("Loading InsightFace buffalo_l (first run downloads the model, ~280 MB)...")
    face_app = FaceAnalysis(name="buffalo_l", providers=["CPUExecutionProvider"])
    face_app.prepare(ctx_id=-1, det_size=(640, 640))  # ctx_id=-1 -> CPU
    logger.info("Model ready.")
    yield


app = FastAPI(title="QRode AI Service", lifespan=lifespan)


def error(status: int, code: str, message: str, **extra) -> JSONResponse:
    return JSONResponse(status_code=status, content={"error": code, "message": message, **extra})


@app.get("/health")
def health():
    return {"status": "ok" if face_app is not None else "loading"}


# Plain `def` (not async): FastAPI runs it in a worker thread, so the heavy
# CPU inference below doesn't block the event loop.
@app.post("/extract-vector")
def extract_vector(file: UploadFile = File(...)):
    if face_app is None:
        return error(503, "model_not_ready", "The face model is still loading. Try again shortly.")

    data = file.file.read(MAX_UPLOAD_BYTES + 1)
    if not data:
        return error(400, "empty_file", "The uploaded file is empty.")
    if len(data) > MAX_UPLOAD_BYTES:
        return error(413, "file_too_large", "Image must be 10 MB or smaller.")

    image = cv2.imdecode(np.frombuffer(data, dtype=np.uint8), cv2.IMREAD_COLOR)
    if image is None:
        return error(400, "invalid_image", "The file is not a valid image.")

    h, w = image.shape[:2]
    if max(h, w) > MAX_SIDE_PX:
        scale = MAX_SIDE_PX / max(h, w)
        image = cv2.resize(image, (int(w * scale), int(h * scale)), interpolation=cv2.INTER_AREA)

    faces = face_app.get(image)

    if len(faces) == 0:
        return error(422, "no_face_detected", "No face was detected. Use a clear, front-facing photo.")
    if len(faces) > 1:
        return error(
            422,
            "multiple_faces_detected",
            f"{len(faces)} faces were detected. The photo must contain exactly one face.",
            face_count=len(faces),
        )

    face = faces[0]
    embedding = face.normed_embedding  # L2-normalized 512-d ArcFace vector

    if embedding is None or len(embedding) != EMBEDDING_DIM:
        return error(500, "embedding_failed", "Could not compute a face embedding.")

    return {
        "embedding": [float(x) for x in embedding],
        "dimension": EMBEDDING_DIM,
        "det_score": float(face.det_score),
    }
