# API Specification

> Dokumentasi endpoint yang dikembangkan (Internal) maupun yang dikonsumsi dari layanan eksternal (Third-Party).

---

## Fitur 1 — Autentikasi Pasien & Verifikasi OTP WhatsApp

---

### Register Pasien

**Method:** `POST`

**URL:** `/api/v1/auth/register`

**Deskripsi:** Mendaftarkan akun pasien baru menggunakan nama dan nomor WhatsApp. Backend akan memicu pengiriman OTP ke nomor WhatsApp yang didaftarkan.

**Autentikasi Diperlukan:** `Tidak`

**Sumber:** `Internal System`

**Request Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "string",
  "phone": "string"
}
```

**Response Sukses (`201 Created`):**
```json
{
  "status": "success",
  "message": "OTP telah dikirim ke WhatsApp Anda"
}
```

**Response Gagal:**
```json
{
  "status": "error",
  "message": "Nomor WhatsApp sudah terdaftar"
}
```

---

### Verifikasi OTP

**Method:** `POST`

**URL:** `/api/v1/auth/verify-otp`

**Deskripsi:** Memverifikasi kode OTP 6-digit yang dikirim ke WhatsApp pasien. Jika valid, mengembalikan Bearer Token untuk sesi login.

**Autentikasi Diperlukan:** `Tidak`

**Sumber:** `Internal System`

**Request Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
  "phone": "string",
  "otp": "string"
}
```

**Response Sukses (`200 OK`):**
```json
{
  "status": "success",
  "data": {
    "token": "string",
    "patient": {
      "id": "integer",
      "name": "string",
      "phone": "string"
    }
  }
}
```

**Response Gagal:**
```json
{
  "status": "error",
  "message": "Kode OTP tidak valid atau sudah kedaluwarsa"
}
```

---

### Kirim OTP ke WhatsApp (Third-Party)

**Method:** `POST`

**URL:** `https://api.fonnte.com/send`

**Deskripsi:** Endpoint Fonnte yang dipanggil oleh Backend Laravel untuk mengirimkan pesan OTP ke nomor WhatsApp pasien.

**Autentikasi Diperlukan:** `Ya`

**Sumber:** `Third-Party API — Fonnte`

**Request Headers:**
```
Authorization: <fonnte_api_key>
Content-Type: application/json
```

**Request Body:**
```json
{
  "target": "string",
  "message": "string"
}
```

**Response Sukses (`200 OK`):**
```json
{
  "status": true,
  "id": "string"
}
```

**Response Gagal:**
```json
{
  "status": false,
  "reason": "string"
}
```

---

### Logout Pasien

**Method:** `POST`

**URL:** `/api/v1/auth/logout`

**Deskripsi:** Menginvalidasi Bearer Token pasien yang sedang aktif (revoke token Sanctum).

**Autentikasi Diperlukan:** `Ya`

**Sumber:** `Internal System`

**Request Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:** `-`

**Response Sukses (`200 OK`):**
```json
{
  "status": "success",
  "message": "Berhasil logout"
}
```

**Response Gagal:**
```json
{
  "status": "error",
  "message": "Token tidak valid"
}
```

---

## Fitur 2 — Sistem Reservasi Jadwal Dokter

---

### Daftar Dokter & Jadwal

**Method:** `GET`

**URL:** `/api/v1/doctors`

**Deskripsi:** Mengambil daftar dokter beserta jadwal praktik yang tersedia. Mendukung filter berdasarkan nama atau spesialisasi via query parameter.

**Autentikasi Diperlukan:** `Ya`

**Sumber:** `Internal System`

**Request Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Query Parameters:**
```
?search=string        (opsional, cari berdasarkan nama dokter)
?specialization=string  (opsional, misal: Umum, Anak, Gigi)
```

**Request Body:** `-`

**Response Sukses (`200 OK`):**
```json
{
  "status": "success",
  "data": [
    {
      "id": "integer",
      "name": "string",
      "specialization": "string",
      "schedules": [
        {
          "day": "string",
          "start_time": "string",
          "end_time": "string",
          "available_slots": "integer"
        }
      ]
    }
  ]
}
```

**Response Gagal:**
```json
{
  "status": "error",
  "message": "Unauthorized"
}
```

---

### Buat Reservasi

**Method:** `POST`

**URL:** `/api/v1/reservations`

**Deskripsi:** Membuat reservasi baru untuk pasien pada slot jadwal dokter yang dipilih. Backend akan mengunci kuota slot agar tidak terjadi overbooking.

**Autentikasi Diperlukan:** `Ya`

**Sumber:** `Internal System`

**Request Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "doctor_id": "integer",
  "schedule_date": "string (YYYY-MM-DD)",
  "start_time": "string (HH:MM)",
  "complaint": "string"
}
```

**Response Sukses (`201 Created`):**
```json
{
  "status": "success",
  "data": {
    "reservation_id": "integer",
    "doctor_name": "string",
    "schedule_date": "string",
    "start_time": "string",
    "status": "pending_payment"
  }
}
```

**Response Gagal:**
```json
{
  "status": "error",
  "message": "Slot waktu tidak tersedia atau sudah penuh"
}
```

---

### Riwayat Reservasi Pasien

**Method:** `GET`

**URL:** `/api/v1/reservations`

**Deskripsi:** Mengambil seluruh riwayat reservasi milik pasien yang sedang login.

**Autentikasi Diperlukan:** `Ya`

**Sumber:** `Internal System`

**Request Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:** `-`

**Response Sukses (`200 OK`):**
```json
{
  "status": "success",
  "data": [
    {
      "reservation_id": "integer",
      "doctor_name": "string",
      "specialization": "string",
      "schedule_date": "string",
      "start_time": "string",
      "status": "string"
    }
  ]
}
```

**Response Gagal:**
```json
{
  "status": "error",
  "message": "Unauthorized"
}
```

---

## Fitur 3 — Pembayaran Instan (Payment Gateway)

---

### Buat Transaksi Pembayaran

**Method:** `POST`

**URL:** `/api/v1/payments`

**Deskripsi:** Membuat transaksi pembayaran untuk reservasi yang berstatus `pending_payment`. Backend akan membuat transaksi di Midtrans dan mengembalikan Snap Token untuk diproses di Frontend.

**Autentikasi Diperlukan:** `Ya`

**Sumber:** `Internal System`

**Request Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "reservation_id": "integer"
}
```

**Response Sukses (`201 Created`):**
```json
{
  "status": "success",
  "data": {
    "snap_token": "string",
    "redirect_url": "string"
  }
}
```

**Response Gagal:**
```json
{
  "status": "error",
  "message": "Reservasi tidak ditemukan atau sudah dibayar"
}
```

---

### Webhook Konfirmasi Pembayaran (Midtrans)

**Method:** `POST`

**URL:** `/api/v1/payments/webhook`

**Deskripsi:** Endpoint yang dipanggil oleh Midtrans secara otomatis untuk memberitahu Backend status akhir pembayaran. Backend akan memperbarui status reservasi menjadi `confirmed` jika pembayaran berhasil.

**Autentikasi Diperlukan:** `Tidak` _(verifikasi menggunakan signature key dari Midtrans)_

**Sumber:** `Third-Party API — Midtrans`

**Request Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
  "order_id": "string",
  "transaction_status": "string",
  "fraud_status": "string",
  "signature_key": "string"
}
```

**Response Sukses (`200 OK`):**
```json
{
  "status": "success",
  "message": "Pembayaran dikonfirmasi"
}
```

**Response Gagal:**
```json
{
  "status": "error",
  "message": "Signature tidak valid"
}
```

---

## Fitur 4 — Riwayat Rekam Medis & E-Resep Digital

---

### Ambil Rekam Medis Pasien

**Method:** `GET`

**URL:** `/api/v1/medical-records`

**Deskripsi:** Mengambil seluruh riwayat rekam medis milik pasien yang sedang login, termasuk diagnosis dan e-resep dari setiap kunjungan.

**Autentikasi Diperlukan:** `Ya`

**Sumber:** `Internal System`

**Request Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:** `-`

**Response Sukses (`200 OK`):**
```json
{
  "status": "success",
  "data": [
    {
      "record_id": "integer",
      "visit_date": "string",
      "doctor_name": "string",
      "diagnosis": "string",
      "prescriptions": [
        {
          "medicine_name": "string",
          "dosage": "string",
          "instructions": "string"
        }
      ]
    }
  ]
}
```

**Response Gagal:**
```json
{
  "status": "error",
  "message": "Unauthorized"
}
```

---

### Input Rekam Medis oleh Dokter

**Method:** `POST`

**URL:** `/api/v1/medical-records`

**Deskripsi:** Dokter menginput catatan diagnosis dan daftar resep digital setelah sesi konsultasi selesai.

**Autentikasi Diperlukan:** `Ya` _(role: dokter)_

**Sumber:** `Internal System`

**Request Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "reservation_id": "integer",
  "diagnosis": "string",
  "prescriptions": [
    {
      "medicine_name": "string",
      "dosage": "string",
      "instructions": "string"
    }
  ]
}
```

**Response Sukses (`201 Created`):**
```json
{
  "status": "success",
  "data": {
    "record_id": "integer",
    "message": "Rekam medis berhasil disimpan"
  }
}
```

**Response Gagal:**
```json
{
  "status": "error",
  "message": "Reservasi tidak ditemukan atau tidak berstatus selesai"
}
```

---

## Fitur 5 — Pengingat Otomatis Jadwal Kunjungan

---

### Kirim Pengingat via WhatsApp (Third-Party, dipanggil oleh Laravel Scheduler)

**Method:** `POST`

**URL:** `https://api.fonnte.com/send`

**Deskripsi:** Endpoint Fonnte yang dipanggil oleh Laravel Scheduler (H-1 sebelum kunjungan) untuk mengirimkan pesan pengingat jadwal kepada pasien via WhatsApp.

**Autentikasi Diperlukan:** `Ya`

**Sumber:** `Third-Party API — Fonnte`

**Request Headers:**
```
Authorization: <fonnte_api_key>
Content-Type: application/json
```

**Request Body:**
```json
{
  "target": "string",
  "message": "string"
}
```

**Response Sukses (`200 OK`):**
```json
{
  "status": true,
  "id": "string"
}
```

**Response Gagal:**
```json
{
  "status": false,
  "reason": "string"
}
```

---
