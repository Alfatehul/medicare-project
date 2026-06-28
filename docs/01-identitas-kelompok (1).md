# Identitas Kelompok

---

**Nama Kelompok:** `7`

- **Nama Proyek / Aplikasi:** `MediCare Patient Portal`
- **link website:** `https://medicare-project-production.up.railway.app`
- **Jumlah Anggota:** `3` orang
- **Repositori:** `https://github.com/Alfatehul/medicare-project.git`

---

## Anggota & Role

**Anggota 1**

- Nama Lengkap: `Alpatehul Rahman`
- NIM: `230705102`
- Role: `Backend Developer`
- Teknologi: `Laravel`

**Anggota 2**

- Nama Lengkap: `Muhammad Rizki Akbar`
- NIM: `230705068`
- Role: `Frontend Developer`
- Teknologi: `Next.js`

**Anggota 3**

- Nama Lengkap: `Rian Pranama Putra`
- NIM: `230705090`
- Role: `DevOps`
- Teknologi: `Docker`

---

## Stack Teknologi

**Frontend:** `Next.js`

**Backend:** `Laravel` _(wajib)_

**Database:** `Mysql`

**DevOps / Infrastruktur:** `GitHub Actions`

---

## Arsitektur Aplikasi

**Aplikasi 1 — Frontend**

- Nama Aplikasi: `MediCare Patient Portal`
- Deskripsi Singkat: `Aplikasi web berbasis Next.js untuk pasien melakukan reservasi jadwal dokter secara online`
- Berkomunikasi dengan: `Aplikasi 3 (Core Clinic API) via REST API dengan autentikasi Bearer Token (Laravel Sanctum) setelah pasien login`

Nama Aplikasi / Service: medicare-project

Deskripsi Singkat: Service backend utama berbasis Laravel 11 / PHP 8.4 yang mengelola seluruh data medis, manajemen autentikasi OTP via WhatsApp, serta menyediakan RESTful API untuk kebutuhan data portal pasien.

Menyediakan layanan untuk: Aplikasi 1 (serene-comfort / MediCare Patient Portal). Komunikasi antar-service menggunakan CORS policy yang telah dikonfigurasi terbuka untuk domain frontend, serta routing API yang diakses melalui prefix base URL https://medicare-project.up.railway.app/api/v1/.

**Aplikasi 3 — DevOps**

-Nama Aplikasi / Service: MediCare DevOps Pipeline
-Deskripsi Singkat: Layanan otomatisasi pengembangan dan deployment aplikasi menggunakan GitHub Actions untuk memastikan proses build, testing, dan deployment berjalan secara konsisten.
-Melayani: Frontend (Next.js) dan Backend (Laravel)
Teknologi: GitHub Actions
