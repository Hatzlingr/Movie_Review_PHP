# 🎬 MovieReview

Aplikasi web review film berbasis **PHP 8 native + MySQL 8**, tanpa framework.  
Fitur: daftar film, rating bintang, review, watchlist, like review, dan panel admin lengkap.

---

## ✨ Fitur

| Fitur          | Keterangan                                                              |
| -------------- | ----------------------------------------------------------------------- |
| 🎞️ Daftar Film | Tampilan card grid, search by judul, pagination                         |
| ⭐ Rating      | Skala 1–5, satu rating per user per film                                |
| 💬 Review      | Satu review per user per film, dapat diedit & dihapus                   |
| ❤️ Like Review | Toggle like/unlike pada review milik user lain                          |
| 🔖 Watchlist   | Status: Plan to Watch / Watching / Completed                            |
| 🔐 Auth        | Register, Login (username atau email), Logout                           |
| 🛡️ Admin       | Dashboard, CRUD film + upload poster, kelola genre/aktor/sutradara/user |

---

## 🗂️ Struktur Folder

```
movie_review/
├── action/                  # Handler POST/GET (rating, review, watchlist, like)
├── admin/                   # Halaman admin (dashboard, movies, genres, actors, dll)
├── app/
│   ├── config/
│   │   ├── db.example.php   # Contoh konfigurasi database
│   │   └── db.php           # Konfigurasi lokal (tidak di-commit)
│   ├── helpers/
│   │   ├── auth.php         # Fungsi autentikasi & session
│   │   ├── flash.php        # Flash message
│   │   └── functions.php    # Helper umum (e, redirect, ensure_session)
│   └── views/partials/      # Header, footer, sidebar admin
├── auth/                    # Login, register, logout
├── database/
│   ├── schema.sql           # Struktur tabel
│   └── seed.sql             # Data contoh (opsional)
├── me/                      # Halaman user (watchlist, reviews)
└── public/                  # Document root
    ├── index.php            # Beranda
    ├── movie.php            # Detail film
    ├── assets/
    │   └── css/app.css      # Custom CSS (glass header, blur, dll)
    └── uploads/posters/     # Poster film yang di-upload
```

---

## ⚙️ Persyaratan

- **PHP** 8.0+
- **MySQL** 8.0+
- **Apache** dengan `mod_rewrite` aktif
- **Laragon** (rekomendasi) atau XAMPP / WAMP

---

## 🚀 Instalasi

### 1. Clone / Download Proyek

```bash
git clone https://github.com/Hatzlingr/Movie_Review_PHP.git
```

Letakkan di `C:\laragon\www\movie_review\`

---

### 2. Buat Database

Buka **phpMyAdmin** (atau HeidiSQL), lalu jalankan:

```sql
-- Jalankan file ini
database/schema.sql
```

> File tersebut sudah termasuk perintah `CREATE DATABASE movie_review_db`.

Opsional — isi data contoh:

```sql
database/seed.sql
```

---

### 3. Konfigurasi Database

Salin file contoh:

```bash
copy app\config\db.example.php app\config\db.php
```

Edit `app/config/db.php`:

```php
$DB_HOST = '127.0.0.1';
$DB_NAME = 'movie_review_db';
$DB_USER = 'root';
$DB_PASS = '';          // sesuaikan password MySQL kamu
```

---

### 4. Buat Folder Upload

Pastikan folder berikut ada dan bisa ditulis:

```
public/uploads/posters/
```

```powershell
New-Item -ItemType Directory -Force "C:\laragon\www\movie_review\public\uploads\posters"
```

---

### 5. Buat Akun Admin

1. Register akun baru di `http://movie_review.test/auth/register.php`
2. Buka phpMyAdmin dan jalankan:

```sql
UPDATE users SET role = 'admin' WHERE username = 'username_kamu';
```

---

## 📌 URL Penting

| Halaman         | URL                                           |
| --------------- | --------------------------------------------- |
| Beranda         | `http://movie_review.test/`                    |
| Login           | `http://movie_review.test/auth/login.php`      |
| Register        | `http://movie_review.test/auth/register.php`   |
| Watchlist Saya  | `http://movie_review.test/me/watchlist.php`    |
| Review Saya     | `http://movie_review.test/me/reviews.php`      |
| Admin Dashboard | `http://movie_review.test/admin/dashboard.php` |

---

## 🗄️ Skema Database

```
users           — akun pengguna (role: user / admin)
movies          — data film
genres          — genre
movie_genres    — relasi film ↔ genre
actors          — aktor
movie_actors    — relasi film ↔ aktor (+ role_name)
directors       — sutradara
movie_directors — relasi film ↔ sutradara
ratings         — rating 1–5 (unik per user+film)
reviews         — teks review (unik per user+film)
watchlists      — watchlist user (plan_to_watch/watching/completed)
review_likes    — like pada review
```

---

## 🔒 Keamanan

- Password di-hash dengan `password_hash()` (bcrypt)
- Semua input di-escape dengan `htmlspecialchars()` sebelum ditampilkan
- Query menggunakan **PDO Prepared Statements** — aman dari SQL Injection
- `session_regenerate_id(true)` dipanggil saat login
- Upload poster divalidasi via MIME type (`finfo`) + batas ukuran 2 MB
- Halaman admin dilindungi `require_admin()`, halaman user dilindungi `require_login()`

---

## 🛠️ Tech Stack

- **Backend:** PHP 8+, PDO + Prepared Statements
- **Database:** MySQL 8+
- **Frontend:** Bootstrap 5.3, Bootstrap Icons
- **CSS Custom:** Glass/blur header dengan `backdrop-filter`
- **Auth:** PHP Sessions native

---

## 📄 Lisensi

MIT License — bebas digunakan dan dimodifikasi.
