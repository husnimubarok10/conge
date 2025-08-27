document.addEventListener("DOMContentLoaded", () => {
    const audio = new Audio("desah.mp3");
    audio.loop = true;

    // ===== Container modern minimalis & transparan =====
    const container = document.createElement("div");
    Object.assign(container.style, {
        position: "fixed",
        top: "30%",
        left: "50%",
        transform: "translate(-50%, -30%)",
        background: "rgba(20, 20, 20, 0.35)",
        backdropFilter: "blur(12px)",
        WebkitBackdropFilter: "blur(12px)",
        borderRadius: "16px",
        padding: "30px 50px",
        textAlign: "center",
        color: "#f0f0f0",
        fontFamily: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
        boxShadow: "0 8px 20px rgba(0,0,0,0.3)",
        zIndex: 9999,
        display: "inline-block",
        maxWidth: "80vw",
        overflow: "hidden"
    });

    // ===== Teks modern elegan (maks 2 baris) =====
    const text = document.createElement("div");
    text.innerText = "Klik tombol dibawah ini untuk melanjutkan proses ke langkah berikutnya";
    Object.assign(text.style, {
        fontSize: "20px",
        fontWeight: "500",
        marginBottom: "25px",
        color: "#e0e0ff",
        whiteSpace: "normal",
        lineHeight: "1.3em",
        maxHeight: "2.6em",
        overflow: "hidden",
        transition: "opacity 0.5s ease" // tambahkan transisi
    });
    container.appendChild(text);

    // ===== Tombol modern minimalis =====
    const playButton = document.createElement("button");
    playButton.innerText = "▶️ Lanjutkan";
    Object.assign(playButton.style, {
        background: "linear-gradient(135deg, #4c6ef5, #15aabf)",
        color: "#fff",
        fontSize: "18px",
        fontWeight: "500",
        border: "none",
        padding: "14px 36px",
        borderRadius: "12px",
        cursor: "pointer",
        transition: "transform 0.25s ease, box-shadow 0.25s ease",
        boxShadow: "0 4px 12px rgba(0,0,0,0.25)"
    });

    // Hover efek tombol
    playButton.addEventListener("mouseover", () => {
        playButton.style.transform = "scale(1.05)";
        playButton.style.boxShadow = "0 6px 18px rgba(0,0,0,0.3)";
    });
    playButton.addEventListener("mouseout", () => {
        playButton.style.transform = "scale(1)";
        playButton.style.boxShadow = "0 4px 12px rgba(0,0,0,0.25)";
    });

    container.appendChild(playButton);
    document.body.appendChild(container);

    // Tombol klik → musik jalan, tombol hilang, teks berubah dengan fade
    playButton.addEventListener("click", () => {
        audio.play().catch(() => console.log("Autoplay diblokir"));

        // fade out tombol
        playButton.style.transition = "opacity 0.4s ease";
        playButton.style.opacity = "0";
        setTimeout(() => playButton.remove(), 400);

        // fade out teks
        text.style.opacity = "0";
        setTimeout(() => {
            text.innerText = "wkwkwk panik kan panik";
            text.style.opacity = "1"; // fade in teks baru
        }, 500); // delay 500ms sesuai fade out teks lama
    });
});