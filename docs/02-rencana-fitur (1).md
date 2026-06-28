# Rencana Fitur

> Dokumentasikan minimal **5 fitur utama** proyek Anda.

---

## Fitur 1 — Autentikasi Pasien & Verifikasi OTP WhatsApp

**Role Penanggung Jawab:** `Frontend | Backend`

**Sumber Data:** `Internal System | Third-Party API — Fonnte (WhatsApp Gateway)`

**Deskripsi & Ekspektasi:**
Pasien melakukan registrasi dan login ke platform MediCare menggunakan nomor WhatsApp aktif. Untuk memastikan validitas data, Backend Laravel akan memanggil API Fonnte menggunakan API Key khusus untuk mengirimkan kode OTP 6-digit ke WhatsApp pasien. Pasien kemudian memasukkan kode tersebut di sisi Frontend. Fitur ini mencegah pendaftaran akun fiktif (bot) dan menjaga keamanan akun pasien tanpa perlu mengingat kata sandi yang rumit.

---
 ## Fitur 2 — Sistem Reservasi Jadwal Dokter dan Pembayaran Online

**Role Penanggung Jawab:** `Frontend | Backend`

**Sumber Data:** `Internal System | Third-Party API — Midtrans`

**Deskripsi & Ekspektasi:**
Pasien dapat memilih dokter berdasarkan spesialisasi yang tersedia dan membuat janji temu secara online melalui aplikasi MediCare. Setelah reservasi berhasil dibuat, sistem akan secara otomatis menghasilkan tagihan biaya konsultasi yang dapat dibayar menggunakan berbagai metode pembayaran seperti QRIS, transfer bank, e-wallet, dan kartu kredit melalui integrasi Midtrans Payment Gateway. Setelah pembayaran berhasil dikonfirmasi, status reservasi akan berubah menjadi "Terkonfirmasi" dan data kunjungan pasien akan tersimpan di sistem. Ekspektasinya, pasien dapat melakukan reservasi dan pembayaran dalam satu alur yang cepat, mudah, dan aman tanpa perlu datang langsung ke klinik.

---

## Fitur 3 — Pembayaran Instan Biaya Konsultasi (Payment Gateway)

**Role Penanggung Jawab:** `Frontend | Backend`

**Sumber Data:** `Internal System | Third-Party API — Midtrans`

**Deskripsi & Ekspektasi:**
Setelah pasien berhasil membuat reservasi, sistem akan menampilkan tagihan biaya konsultasi yang harus dibayarkan. Backend Laravel terintegrasi dengan Midtrans Payment Gateway untuk menyediakan berbagai metode pembayaran (transfer bank, QRIS, e-wallet seperti GoPay dan OVO). Setelah pembayaran dikonfirmasi oleh Midtrans via webhook, status reservasi pasien secara otomatis diperbarui menjadi "Terkonfirmasi". Ekspektasinya, pasien dapat menyelesaikan pembayaran dengan mudah dan aman tanpa perlu datang langsung ke klinik.

---

## Fitur 4 — Kalkulator Kandungan Gizi Makanan

**Role Penanggung Jawab:** Frontend | Backend

**Sumber Data:** Third-Party API — Edamam Food Database API

**Deskripsi & Ekspektasi:**
Pasien dapat mencari berbagai jenis makanan atau bahan pangan untuk mengetahui kandungan gizinya secara detail. Sistem akan mengambil data nutrisi seperti kalori, protein, karbohidrat, dan lemak dari API Edamam, kemudian menampilkan hasil analisis dalam bentuk yang mudah dipahami. Ekspektasinya, pasien dapat memperoleh informasi nutrisi yang akurat untuk membantu menjaga pola makan sehat dan mendukung gaya hidup yang lebih baik.
---

## Fitur 5 — Informasi Klinik dan Cuaca

**Role Penanggung Jawab:** Frontend

**Sumber Data:** Internal System | Third-Party API — OpenWeatherMap

**Deskripsi & Ekspektasi:**
Sistem menampilkan informasi klinik serta kondisi cuaca terkini pada lokasi klinik, seperti suhu, kelembapan udara, dan kondisi cuaca (cerah, hujan, atau berawan). Informasi cuaca diperoleh secara real-time melalui API OpenWeatherMap dan ditampilkan pada dashboard pasien. Ekspektasinya, pasien dapat memperoleh informasi yang relevan sebelum melakukan kunjungan ke klinik sehingga dapat mempersiapkan perjalanan dengan lebih baik.
---
