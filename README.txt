SISTEM INFORMASI ABSENSI KARYAWAN - PHP + MYSQL

FITUR
- Login admin dan karyawan
- Admin menambah data karyawan sekaligus akun login
- Karyawan mencatat absensi masuk dan pulang
- Satu catatan absensi per karyawan per tanggal
- Admin melihat laporan berdasarkan rentang tanggal dan mencetaknya
- Dashboard admin dan karyawan

PERSYARATAN
- XAMPP (Apache dan MySQL)
- PHP 8+ dan MySQL/MariaDB
- Browser

CARA MENJALANKAN DI WINDOWS
1. Ekstrak folder absensi_karyawan ke C:\xampp\htdocs\
2. Buka XAMPP Control Panel, jalankan Apache dan MySQL.
3. Buka http://localhost/phpmyadmin
4. Pilih tab Import, pilih file database.sql dari folder proyek, lalu klik Import.
   (Alternatif: buka database.sql, salin seluruh SQL, jalankan di tab SQL phpMyAdmin.)
5. Pastikan config/database.php memakai host localhost, database db_absensi,
   username root, dan password kosong (pengaturan XAMPP standar).
6. Buka http://localhost/absensi_karyawan/setup_admin.php satu kali.
7. Login melalui http://localhost/absensi_karyawan/
   Username: admin
   Password: admin123
8. Setelah admin berhasil dibuat, HAPUS setup_admin.php dari folder proyek.
9. Dari menu Data Karyawan, tambahkan karyawan dan buat username/password.
   Karyawan lalu dapat login memakai akun tersebut.

CATATAN PENTING
- Ini merupakan aplikasi awal untuk pembelajaran/prototipe tugas RPL, belum siap
  dipakai untuk operasional perusahaan sungguhan.
- Ganti password admin bawaan dan gunakan password kuat.
- Atur zona waktu PHP/server jika waktu absensi tidak sesuai.
- Penghapusan data karyawan juga menghapus catatan absensinya.
- Fitur edit karyawan, reset password, ekspor Excel, dan validasi lokasi belum disertakan.
- Tidak ada fitur edit profil atau pengubahan password pada versi awal ini.
