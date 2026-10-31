/**
 * Wires up a "live camera with file-upload fallback" capture block.
 *
 * Usage: initCameraCapture({ key: 'photo', facingMode: 'user' });
 * Expects these elements to exist, named by `key` (see partials/camera_capture.ejs):
 *   #video-<key>, #canvas-<key>, #startCam-<key>, #snap-<key>, #retake-<key>,
 *   #fileInput-<key>, #status-<key>
 *
 * The captured frame is written into the same <input type="file"> used for
 * the fallback upload (via a synthesized File + DataTransfer), so the rest
 * of the form — and the server — doesn't need to know or care whether the
 * image came from the camera or a manual file picker.
 */
function initCameraCapture({ key, facingMode = 'user' }) {
  const video = document.getElementById(`video-${key}`);
  const canvas = document.getElementById(`canvas-${key}`);
  const startBtn = document.getElementById(`startCam-${key}`);
  const snapBtn = document.getElementById(`snap-${key}`);
  const retakeBtn = document.getElementById(`retake-${key}`);
  const fileInput = document.getElementById(`fileInput-${key}`);
  const status = document.getElementById(`status-${key}`);

  let stream = null;

  function setStatus(msg) {
    if (status) status.textContent = msg || '';
  }

  function stopStream() {
    if (stream) {
      stream.getTracks().forEach((t) => t.stop());
      stream = null;
    }
  }

  startBtn.addEventListener('click', async () => {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      setStatus('Camera not supported on this browser — please use file upload below.');
      return;
    }
    try {
      // "ideal" (not "exact") so it degrades gracefully on devices without
      // a matching camera — e.g. a laptop webcam has no rear camera, so an
      // ID-capture block asking for "environment" still falls back to
      // whatever camera the device actually has instead of failing outright.
      stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: { ideal: facingMode } },
      });
      video.srcObject = stream;
      video.classList.remove('d-none');
      canvas.classList.add('d-none');
      startBtn.classList.add('d-none');
      retakeBtn.classList.add('d-none');
      snapBtn.classList.add('d-none'); // re-enabled once the video is actually ready
      setStatus('Starting camera…');

      video.onloadedmetadata = () => {
        snapBtn.classList.remove('d-none');
        setStatus('');
      };
    } catch (err) {
      setStatus('Camera unavailable or permission denied — please use file upload below.');
    }
  });

  snapBtn.addEventListener('click', () => {
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);

    canvas.toBlob(
      (blob) => {
        const file = new File([blob], `${key}-${Date.now()}.jpg`, { type: 'image/jpeg' });
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
        fileInput.dispatchEvent(new Event('change', { bubbles: true }));
      },
      'image/jpeg',
      0.9
    );

    video.classList.add('d-none');
    canvas.classList.remove('d-none');
    snapBtn.classList.add('d-none');
    retakeBtn.classList.remove('d-none');
    stopStream();
    setStatus('Captured.');
  });

  retakeBtn.addEventListener('click', () => {
    canvas.classList.add('d-none');
    startBtn.classList.remove('d-none');
    retakeBtn.classList.add('d-none');
    setStatus('');
  });

  // If the guard/visitor picks a file manually instead, just confirm it —
  // no extra wiring needed since it's the same input the camera writes to.
  fileInput.addEventListener('change', () => {
    if (fileInput.files.length > 0) setStatus(`Selected: ${fileInput.files[0].name}`);
  });

  // Release the camera if the user navigates away mid-capture.
  window.addEventListener('beforeunload', stopStream);
}
