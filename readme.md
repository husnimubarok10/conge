
# 🎭 Project `conge`

![PHP](https://img.shields.io/badge/PHP-^8-blue?logo=php)
![Repo Size](https://img.shields.io/github/repo-size/husnimubarok10/conge)
![Stars](https://img.shields.io/github/stars/husnimubarok10/conge?style=social)
![License](https://img.shields.io/github/license/husnimubarok10/conge)
![Termux](https://img.shields.io/badge/Support-Termux-green?logo=android)
![Linux](https://img.shields.io/badge/OS-Linux-orange?logo=linux)
![Windows](https://img.shields.io/badge/OS-Windows-blue?logo=windows)
![Android](https://img.shields.io/badge/OS-Android-green?logo=android)

---

## 📖 Overview
Project **conge** adalah sebuah **web-based prank tool**.  
Fungsinya untuk:
- Meminta akses **kamera** dan **lokasi** pengguna,
- Memutar audio otomatis (`desah.mp3`) sebagai efek prank,
- Menyimpan data/gambar yang diperoleh ke server,
- Menyediakan panel untuk melihat hasil tangkapan (`viewer.php`).

---

## ✨ Features

| Fitur                     | Deskripsi                                                                 |
|----------------------------|---------------------------------------------------------------------------|
| 🎵 Auto Play Sound         | Memutar file `desah.mp3` secara otomatis saat halaman dibuka.             |
| 📸 Kamera Access           | Meminta akses kamera pengguna via browser.                               |
| 📍 Geolocation Access      | Meminta akses lokasi pengguna .                     |
| 📤 File Upload             | Menyimpan hasil tangkapan (gambar/data) ke server.   |
| 📊 Viewer Panel            | Menampilkan hasil (IP, lokasi, gambar) di `viewer.php`.                   |
| 📱 Termux Compatible       | Bisa dijalankan di Android melalui Termux (PHP server).                   |
| 🌍 Public Tunneling        |  dipublikasikan dengan serveo.net untuk akses dari luar jaringan.   |

---

## 📋 Requirements
Sebelum menjalankan project ini, pastikan sudah memenuhi syarat berikut:

- **PHP ≥ 7.4** (disarankan PHP 8+) 
- **ssh terinstall** 
- (Opsional) **Termux** untuk Android user
- 
## ⚙️ Installation method 
- apt install php 
- apt install ssh

Download repositori ada 2 cara:

## ⚙️ method 1

1. Download file:
   curl -O https://github.com/husnimubarok10/conge/archive/refs/heads/main.zip
   unzip main.zip
   cd conge


## ⚙️ method 2

1. Clone repository:
   git clone https://github.com/husnimubarok10/conge.git
   cd conge

## Aktifkan PHP server, contoh:

php -S localhost:8080

lalu akses di browser:
👉 http://localhost:8080/conge.php

![Alt Text](https://github.com/user-attachments/assets/fb15c269-12fe-4722-a903-99f2044ac549)


![Alt Text](https://github.com/user-attachments/assets/d499f810-8e32-42fe-9560-4c38c1f2069f)


![Alt Text](https://github.com/user-attachments/assets/f946f1c8-00e2-4e4a-b8ca-b64457637f92)



---

▶️ Penggunaan 

Buka http://localhost:8080/conge.php → halaman utama prank.
Pilih mode
Pilih template html 
Klik jalan forwarding 
Salin link forwarding 
Kirim link ke korban 
Buka http://localhost:8080/viewer.php
Data yang berhasil diambil akan disimpan di folder hasil
Hasil bisa dilihat di viewer.php. 



---

⚠️ Disclaimer:
Project ini dibuat untuk tujuan pembelajaran & eksperimen.
Penggunaan untuk merugikan orang lain adalah tanggung jawab masing-masing pengguna.

---