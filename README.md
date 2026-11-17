# Simple Imagix (v1.2.1)

> Convert JPG/PNG to WebP (Full & Subsizes), AJAX Bulk Conversion, and Unused Media Cleaner for WordPress.

## ✨ Fitur Utama

1. **Automatic WebP Conversion (Full & Subsizes/Thumbnails)**
   * Mengonversi gambar utama dan seluruh variasi ukuran (`thumbnail`, `medium`, `large`, dll.) ke WebP saat diupload.
   * Opsi untuk mengaktifkan atau menonaktifkan konversi otomatis langsung dari menu pengaturan.
   * Mempertahankan transparansi pada gambar PNG melalui penanganan alpha channel GD.
   * Memperbarui kolom `post_mime_type` di tabel `wp_posts` menjadi `image/webp`.

2. **AJAX Batch Bulk Converter (Anti-Timeout)**
   * Memproses konversi gambar lama secara bertahap via antrean AJAX (5 gambar per batch).
   * Dilengkapi *progress bar* visual (0%–100%) dan log status langsung di halaman admin.
   * Sangat stabil dan bebas dari kendala timeout (504 Gateway Timeout) di Apache, Nginx, LiteSpeed, aaPanel, dan HestiaCP.

3. **Unused Media Cleaner (Pembersih Gambar Tidak Terpakai)**
   * Memindai seluruh berkas di Media Library yang tidak lagi digunakan sebagai:
     * Featured Image (`_thumbnail_id`).
     * Konten Post, Page, dan Custom Post Type (CPT) baik Classic Editor maupun Gutenberg Block.
     * Ikon dan Logo Situs (`site_icon`, `custom_logo`).
     * Galeri Produk WooCommerce (`_product_image_gallery`).
     * Elementor Page Builder metadata (`_elementor_data`).
   * Memberikan daftar pratinjau (*preview*) berkas tidak terpakai.
   * Aksi penghapusan aman: **Move to Trash** (bisa dipulihkan kembali) dan **Delete Permanently** dengan dialog konfirmasi.

4. **Quality & Storage Control**
   * Pengaturan kualitas kompresi WebP (10 - 100).
   * Opsi *Delete Original File* (JPG/PNG) setelah berhasil dikonversi ke WebP untuk menghemat penyimpanan server.

5. **Antarmuka Admin Responsif & Simetris**
   * Navigasi tab instan via JavaScript tanpa reload halaman.
   * Tata letak rapi, simetris, dan menyatu dengan standar desain WordPress.

---

## 📁 Struktur Berkas

```text
simple-imagix/
├── assets/
│   ├── js/
│   │   ├── admin-tabs.js        # Navigasi tab instan tanpa reload halaman
│   │   ├── bulk-convert.js      # AJAX batching & progress bar bulk convert
│   │   └── media-cleaner.js     # AJAX scanner & safe trash/delete handler
│   └── css/
│       └── admin.css            # Styling tab, layout simetris, & cleaner table
├── includes/
│   ├── class-converter.php      # Engine konversi WebP (Full & Subsizes)
│   ├── class-cleaner.php        # Engine deteksi berkas media tidak terpakai
│   └── class-ajax.php           # Endpoint registrasi AJAX WordPress
├── admin-page.php               # Antarmuka Admin Tabbed Settings
├── converter.php                # Legacy wrapper untuk kompatibilitas mundur
├── simple-imagix.php            # Entry point plugin
├── plan-simple-imagix-features.md
└── readme.txt
```

---

## 🚀 Persyaratan Sistem

* **PHP:** Minimal 7.4 (Kompatibel penuh dengan PHP 8.0, 8.1, 8.2, 8.3).
* **PHP GD Extension:** Terinstal dengan WebP support (`imagewebp()`).
* **WordPress:** Minimal 5.6 (Diuji hingga 6.9).
* **Web Server & Panel:** Mendukung Apache, Nginx, LiteSpeed, OpenLiteSpeed, aaPanel, dan HestiaCP.

---

## 📝 Changelog

### 1.2.1 - 17 November 2026
* Menambahkan opsi toggle pengaturan untuk mengaktifkan/menonaktifkan konversi WebP otomatis saat upload.
* Memperbaiki tata letak agar rata dan simetris di sisi kiri pada tab navigasi dan tabel pengaturan.
* Menerapkan sistem tab instan via JavaScript tanpa memuat ulang (*reload*) halaman.

### 1.2.0 - 17 November 2026
* Menambahkan dukungan konversi otomatis seluruh thumbnail/subsizes WordPress ke format WebP.
* Sinkronisasi tipe berkas database `post_mime_type` menjadi `image/webp`.
* Memperbarui fitur bulk conversion dengan antrean batch AJAX (5 gambar/batch) dan visual progress bar.
* Menambahkan fitur Unused Media Cleaner dengan opsi aman *Move to Trash* dan *Permanent Delete*.
* Merombak antarmuka admin menjadi sistem tab terstruktur di bawah *Settings > Simple Imagix*.

### 1.1 - 16 November 2026
* Rilis awal dengan konversi WebP otomatis saat upload dan bulk convert standar.
