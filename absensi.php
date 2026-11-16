
<?php
session_start();
include 'koneksi.php';

date_default_timezone_set('Asia/Jakarta');

// =====================================
// 1. PEMERIKSAAN LOGIN DAN HAK AKSES
// =====================================

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'] ?? '';
$id_user = (int) $_SESSION['id_user'];

if (!in_array($role, ['admin', 'karyawan'], true)) {
    die("Role pengguna tidak valid.");
}

// =====================================
// 2. TOKEN KEAMANAN CSRF
// =====================================

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];

$pesan = "";
$jenis_pesan = "info";

// =====================================
// 3. IDENTITAS KARYAWAN YANG LOGIN
// =====================================

$id_karyawan_login = 0;

if ($role === 'karyawan') {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id
         FROM karyawan
         WHERE user_id = ? AND status = 'Aktif'"
    );

    if (!$stmt) {
        die("Kesalahan database.");
    }

    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);

    $data_login = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    mysqli_stmt_close($stmt);

    if (!$data_login) {
        die("Akun ini belum terhubung dengan data karyawan aktif.");
    }

    $id_karyawan_login = (int) $data_login['id'];
}

// =====================================
// 4. PROSES ABSENSI MASUK DAN PULANG
// =====================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['absen'])) {

    // Validasi token CSRF
    $token_post = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token_post) ||
        !hash_equals($csrf_token, $token_post)
    ) {

        $pesan = "Permintaan tidak valid. Silakan muat ulang halaman.";
        $jenis_pesan = "error";

    } else {

        $aksi = $_POST['aksi'] ?? '';

        // Admin memilih karyawan.
        // Karyawan selalu menggunakan identitas akunnya sendiri.
        if ($role === 'admin') {
            $karyawan_id = (int) ($_POST['karyawan_id'] ?? 0);
        } else {
            $karyawan_id = $id_karyawan_login;
        }

        $tanggal = date('Y-m-d');
        $waktu = date('H:i:s');

        if ($karyawan_id <= 0) {

            $pesan = "Pilih karyawan terlebih dahulu.";
            $jenis_pesan = "error";

        } elseif (!in_array($aksi, ['masuk', 'pulang'], true)) {

            $pesan = "Jenis absensi tidak valid.";
            $jenis_pesan = "error";

        } else {

            // Pastikan karyawan aktif
            $stmt = mysqli_prepare(
                $conn,
                "SELECT id
                 FROM karyawan
                 WHERE id = ? AND status = 'Aktif'"
            );

            if (!$stmt) {
                die("Kesalahan database.");
            }

            mysqli_stmt_bind_param($stmt, "i", $karyawan_id);
            mysqli_stmt_execute($stmt);

            $karyawan = mysqli_fetch_assoc(
                mysqli_stmt_get_result($stmt)
            );

            mysqli_stmt_close($stmt);

            if (!$karyawan) {

                $pesan = "Karyawan tidak ditemukan atau tidak aktif.";
                $jenis_pesan = "error";

            } else {

                // Cari absensi hari ini
                $stmt = mysqli_prepare(
                    $conn,
                    "SELECT id, jam_masuk, jam_pulang
                     FROM absensi
                     WHERE karyawan_id = ? AND tanggal = ?"
                );

                if (!$stmt) {
                    die("Kesalahan database.");
                }

                mysqli_stmt_bind_param(
                    $stmt,
                    "is",
                    $karyawan_id,
                    $tanggal
                );

                mysqli_stmt_execute($stmt);

                $absensi = mysqli_fetch_assoc(
                    mysqli_stmt_get_result($stmt)
                );

                mysqli_stmt_close($stmt);

                // -----------------------------
                // ABSENSI MASUK
                // -----------------------------

                if ($aksi === 'masuk') {

                    if (!$absensi) {

                        $stmt = mysqli_prepare(
                            $conn,
                            "INSERT INTO absensi
                             (karyawan_id, tanggal, jam_masuk, keterangan)
                             VALUES (?, ?, ?, 'Hadir')"
                        );

                        if (!$stmt) {
                            die("Kesalahan database.");
                        }

                        mysqli_stmt_bind_param(
                            $stmt,
                            "iss",
                            $karyawan_id,
                            $tanggal,
                            $waktu
                        );

                        if (mysqli_stmt_execute($stmt)) {

                            $pesan = "Absensi masuk berhasil dicatat.";
                            $jenis_pesan = "success";

                        } else {

                            $pesan = "Gagal mencatat absensi masuk. Mungkin absensi sudah tercatat.";
                            $jenis_pesan = "error";
                        }

                        mysqli_stmt_close($stmt);

                    } else {

                        $pesan = "Absensi masuk hari ini sudah dilakukan.";
                        $jenis_pesan = "error";
                    }

                }

                // -----------------------------
                // ABSENSI PULANG
                // -----------------------------

                elseif ($aksi === 'pulang') {

                    if (
                        !$absensi ||
                        $absensi['jam_masuk'] === null
                    ) {

                        $pesan = "Silakan lakukan absensi masuk terlebih dahulu.";
                        $jenis_pesan = "error";

                    } elseif ($absensi['jam_pulang'] !== null) {

                        $pesan = "Absensi pulang hari ini sudah dilakukan.";
                        $jenis_pesan = "error";

                    } else {

                        $stmt = mysqli_prepare(
                            $conn,
                            "UPDATE absensi
                             SET jam_pulang = ?
                             WHERE id = ? AND jam_pulang IS NULL"
                        );

                        if (!$stmt) {
                            die("Kesalahan database.");
                        }

                        mysqli_stmt_bind_param(
                            $stmt,
                            "si",
                            $waktu,
                            $absensi['id']
                        );

                        mysqli_stmt_execute($stmt);

                        if (mysqli_stmt_affected_rows($stmt) === 1) {

                            $pesan = "Absensi pulang berhasil dicatat.";
                            $jenis_pesan = "success";

                        } else {

                            $pesan = "Absensi pulang sudah tercatat.";
                            $jenis_pesan = "error";
                        }

                        mysqli_stmt_close($stmt);
                    }
                }
            }
        }
    }
}

// =====================================
// 5. DAFTAR KARYAWAN UNTUK ADMIN
// =====================================

$query_karyawan = false;

if ($role === 'admin') {

    $query_karyawan = mysqli_query(
        $conn,
        "SELECT id, nip, nama
         FROM karyawan
         WHERE status = 'Aktif'
         ORDER BY nama ASC"
    );

    if (!$query_karyawan) {
        die("Gagal mengambil data karyawan.");
    }
}

// =====================================
// 6. RIWAYAT ABSENSI HARI INI
// =====================================

$tanggal_hari_ini = date('Y-m-d');

if ($role === 'admin') {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT k.nama, k.nip, a.tanggal,
                a.jam_masuk, a.jam_pulang, a.keterangan
         FROM absensi a
         INNER JOIN karyawan k ON a.karyawan_id = k.id
         WHERE a.tanggal = ?
         ORDER BY k.nama ASC"
    );

    if (!$stmt) {
        die("Kesalahan database.");
    }

    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $tanggal_hari_ini
    );

} else {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT k.nama, k.nip, a.tanggal,
                a.jam_masuk, a.jam_pulang, a.keterangan
         FROM absensi a
         INNER JOIN karyawan k ON a.karyawan_id = k.id
         WHERE a.tanggal = ?
         AND a.karyawan_id = ?
         ORDER BY a.tanggal DESC"
    );

    if (!$stmt) {
        die("Kesalahan database.");
    }

    mysqli_stmt_bind_param(
        $stmt,
        "si",
        $tanggal_hari_ini,
        $id_karyawan_login
    );
}

mysqli_stmt_execute($stmt);

$riwayat = mysqli_stmt_get_result($stmt);

mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Absensi Karyawan</title>

<style>

* {
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #f2f4f7;
    margin: 0;
    padding: 20px;
    color: #333;
}

.container {
    max-width: 1000px;
    margin: auto;
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.05);
}

h2, h3 {
    color: #355070;
}

.menu {
    margin: 20px 0;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.menu a {
    display: inline-block;
    background: #355070;
    color: white;
    padding: 11px 15px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 13px;
}

.menu a:hover {
    background: #263e59;
}

.info-akun {
    background: #e8f1fa;
    color: #355070;
    padding: 14px;
    border-radius: 7px;
    margin: 15px 0;
    line-height: 1.6;
}

.form-group {
    margin: 18px 0;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #536273;
}

select {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccd5df;
    border-radius: 7px;
    background: white;
}

button {
    padding: 12px 18px;
    margin: 5px 3px 5px 0;
    border: none;
    border-radius: 6px;
    color: white;
    cursor: pointer;
    font-size: 14px;
}

button:hover {
    opacity: 0.88;
}

.masuk {
    background: #198754;
}

.pulang {
    background: #355070;
}

.pesan {
    padding: 13px;
    margin: 15px 0;
    border-radius: 7px;
    line-height: 1.5;
}

.success {
    background: #d4edda;
    color: #155724;
}

.error {
    background: #f8d7da;
    color: #721c24;
}

.info {
    background: #e8f1fa;
    color: #355070;
}

.table-wrapper {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    min-width: 600px;
}

th, td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: center;
    font-size: 13px;
}

th {
    background: #355070;
    color: white;
}

tr:nth-child(even) {
    background: #f7f9fb;
}

.status {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 15px;
    background: #e1f5e8;
    color: #267341;
    font-size: 11px;
}

@media (max-width: 650px) {

    body {
        padding: 10px;
    }

    .container {
        padding: 15px;
    }

    table {
        font-size: 12px;
    }

    th, td {
        padding: 8px;
    }

}

</style>

</head>

<body>

<div class="container">

    <h2>Absensi Karyawan</h2>

    <p>
        Sistem Informasi Absensi dan Rekap Kehadiran Karyawan
    </p>

    <div class="menu">

        <a href="dashboard.php">Dashboard</a>

        <?php if ($role === 'admin') { ?>

            <a href="karyawan.php">Data Karyawan</a>
            <a href="laporan.php">Laporan</a>
            <a href="akun_karyawan.php">Kelola Akun</a>

        <?php } ?>

        <a href="logout.php">Logout</a>

    </div>

    <hr>

    <h3>Form Absensi</h3>

    <?php if ($pesan !== '') { ?>

        <div class="pesan <?php echo $jenis_pesan; ?>">

            <?php echo htmlspecialchars($pesan); ?>

        </div>

    <?php } ?>

    <div class="info-akun">

        <?php if ($role === 'admin') { ?>

            <strong>Mode Administrator</strong><br>
            Admin dapat mencatat absensi untuk seluruh karyawan aktif.

        <?php } else { ?>

            <strong>Mode Karyawan</strong><br>
            Anda hanya dapat melakukan absensi menggunakan akun sendiri.

        <?php } ?>

    </div>

    <form method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($csrf_token); ?>"
        >

        <?php if ($role === 'admin') { ?>

            <div class="form-group">

                <label>Pilih Karyawan</label>

                <select name="karyawan_id" required>

                    <option value="">-- Pilih Karyawan --</option>

                    <?php while ($k = mysqli_fetch_assoc($query_karyawan)) { ?>

                        <option value="<?php echo (int)$k['id']; ?>">

                            <?php echo htmlspecialchars($k['nama']); ?>

                            - NIP <?php echo htmlspecialchars($k['nip']); ?>

                        </option>

                    <?php } ?>

                </select>

            </div>

        <?php } ?>

        <input
            type="hidden"
            name="aksi"
            id="aksi"
            value=""
        >

        <button
            type="submit"
            name="absen"
            value="1"
            class="masuk"
            onclick="document.getElementById('aksi').value='masuk';"
        >
            Absen Masuk
        </button>

        <button
            type="submit"
            name="absen"
            value="1"
            class="pulang"
            onclick="document.getElementById('aksi').value='pulang';"
        >
            Absen Pulang
        </button>

    </form>

    <hr>

    <h3>Riwayat Absensi Hari Ini</h3>

    <p>
        Tanggal:
        <strong><?php echo date('d-m-Y'); ?></strong>
    </p>

    <div class="table-wrapper">

        <table>

            <thead>

                <tr>

                    <th>No</th>

                    <?php if ($role === 'admin') { ?>

                        <th>Nama Karyawan</th>
                        <th>NIP</th>

                    <?php } ?>

                    <th>Tanggal</th>
                    <th>Jam Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Keterangan</th>

                </tr>

            </thead>

            <tbody>

                <?php

                $no = 1;

                if (mysqli_num_rows($riwayat) > 0) {

                    while ($r = mysqli_fetch_assoc($riwayat)) {

                ?>

                    <tr>

                        <td><?php echo $no++; ?></td>

                        <?php if ($role === 'admin') { ?>

                            <td>
                                <?php echo htmlspecialchars($r['nama']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($r['nip']); ?>
                            </td>

                        <?php } ?>

                        <td>
                            <?php echo htmlspecialchars($r['tanggal']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($r['jam_masuk'] ?? '-'); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($r['jam_pulang'] ?? '-'); ?>
                        </td>

                        <td>
                            <span class="status">
                                <?php echo htmlspecialchars($r['keterangan'] ?? '-'); ?>
                            </span>
                        </td>

                    </tr>

                <?php

                    }

                } else {

                ?>

                    <tr>

                        <td colspan="<?php echo $role === 'admin' ? 7 : 5; ?>">
                            Belum ada absensi hari ini.
                        </td>

                    </tr>

                <?php } ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>
