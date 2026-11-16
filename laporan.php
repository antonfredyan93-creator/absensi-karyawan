
<?php
session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

// Laporan seluruh karyawan hanya untuk admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

include 'koneksi.php';

// FILTER
$tanggal_mulai = $_GET['tanggal_mulai'] ?? '';
$tanggal_akhir = $_GET['tanggal_akhir'] ?? '';
$karyawan_id = isset($_GET['karyawan_id'])
    ? (int) $_GET['karyawan_id']
    : 0;

// Validasi format tanggal
if (
    ($tanggal_mulai !== '' &&
     !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_mulai)) ||
    ($tanggal_akhir !== '' &&
     !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_akhir))
) {
    die("Format tanggal tidak valid.");
}

// DAFTAR KARYAWAN
$query_karyawan = mysqli_query(
    $conn,
    "SELECT id, nip, nama FROM karyawan ORDER BY nama ASC"
);

// QUERY LAPORAN
$sql = "SELECT
            a.id,
            k.nip,
            k.nama,
            k.bagian,
            a.tanggal,
            a.jam_masuk,
            a.jam_pulang,
            a.keterangan
        FROM absensi a
        INNER JOIN karyawan k ON a.karyawan_id = k.id
        WHERE (? = '' OR a.tanggal >= ?)
        AND (? = '' OR a.tanggal <= ?)
        AND (? = 0 OR a.karyawan_id = ?)
        ORDER BY a.tanggal DESC, k.nama ASC";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ssssii",
    $tanggal_mulai,
    $tanggal_mulai,
    $tanggal_akhir,
    $tanggal_akhir,
    $karyawan_id,
    $karyawan_id
);

mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);

$total_absensi = mysqli_num_rows($hasil);

// Judul periode laporan
$periode = "Semua Periode";

if ($tanggal_mulai && $tanggal_akhir) {
    $periode = $tanggal_mulai . " sampai " . $tanggal_akhir;
} elseif ($tanggal_mulai) {
    $periode = "Mulai " . $tanggal_mulai;
} elseif ($tanggal_akhir) {
    $periode = "Sampai " . $tanggal_akhir;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Laporan Kehadiran | Sistem Absensi</title>

<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #f3f6fa;
    color: #263445;
}

.sidebar {
    width: 245px;
    min-height: 100vh;
    background: #263e59;
    color: white;
    position: fixed;
    top: 0;
    left: 0;
    padding: 30px 20px;
}

.brand {
    font-size: 19px;
    font-weight: bold;
    line-height: 1.5;
    margin-bottom: 40px;
}

.brand span {
    color: #b9d4ef;
}

.menu-label {
    color: #aebed0;
    font-size: 11px;
    letter-spacing: 1px;
    margin: 25px 10px 12px;
}

.sidebar a {
    display: block;
    color: #e8eef5;
    text-decoration: none;
    padding: 13px 14px;
    border-radius: 8px;
    margin-bottom: 7px;
    font-size: 14px;
}

.sidebar a:hover,
.sidebar a.active {
    background: #456687;
}

.sidebar .logout {
    margin-top: 35px;
    background: #a94444;
}

.main {
    margin-left: 245px;
    padding: 30px;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 30px;
}

.topbar h1 {
    color: #263e59;
    font-size: 25px;
    margin-bottom: 7px;
}

.subtitle {
    color: #7b8794;
    font-size: 14px;
}

.admin-label {
    background: #e2edf7;
    color: #355070;
    padding: 9px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: bold;
}

.summary {
    background: white;
    padding: 23px;
    border-radius: 12px;
    box-shadow: 0 3px 15px rgba(30,55,80,0.05);
    margin-bottom: 25px;
    max-width: 330px;
    border-left: 5px solid #355070;
}

.summary p {
    color: #7b8794;
    font-size: 14px;
    margin-bottom: 10px;
}

.summary h2 {
    color: #355070;
    font-size: 32px;
}

.panel {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 3px 15px rgba(30,55,80,0.05);
    margin-bottom: 25px;
}

.panel-title {
    color: #263e59;
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 20px;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 17px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #536273;
    margin-bottom: 8px;
}

input,
select {
    width: 100%;
    padding: 12px;
    border: 1px solid #dce3eb;
    border-radius: 7px;
    outline: none;
    background: white;
    font-size: 14px;
}

input:focus,
select:focus {
    border-color: #52799e;
}

.btn {
    display: inline-block;
    padding: 11px 17px;
    border: none;
    border-radius: 7px;
    background: #355070;
    color: white;
    text-decoration: none;
    font-size: 13px;
    cursor: pointer;
    margin-top: 18px;
}

.btn:hover {
    opacity: 0.85;
}

.btn-reset {
    background: #e8edf2;
    color: #45576a;
}

.btn-print {
    background: #238354;
    margin-top: 0;
}

.table-wrapper {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

th {
    background: #f0f4f8;
    color: #42566d;
    padding: 14px 12px;
    font-size: 12px;
    text-align: left;
    white-space: nowrap;
}

td {
    padding: 14px 12px;
    border-bottom: 1px solid #edf0f4;
    font-size: 13px;
}

tr:hover td {
    background: #fafbfd;
}

.total-info {
    color: #778595;
    font-size: 13px;
    margin-bottom: 18px;
}

.keterangan {
    display: inline-block;
    background: #e1f5e8;
    color: #287744;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
}

.kosong {
    text-align: center;
    color: #8995a3;
    padding: 30px;
}

.print-header {
    display: none;
}

@media (max-width: 800px) {
    .sidebar {
        position: relative;
        width: 100%;
        min-height: auto;
        padding: 20px;
    }

    .brand {
        margin-bottom: 15px;
    }

    .main {
        margin-left: 0;
        padding: 20px;
    }

    .filter-grid {
        grid-template-columns: 1fr;
    }

    .summary {
        max-width: 100%;
    }
}

@media print {
    body {
        background: white;
    }

    .sidebar,
    .topbar,
    .filter-panel,
    .no-print {
        display: none !important;
    }

    .main {
        margin: 0;
        padding: 0;
    }

    .panel {
        box-shadow: none;
        padding: 0;
    }

    .summary {
        box-shadow: none;
        border: 1px solid #ddd;
        margin-bottom: 15px;
    }

    .print-header {
        display: block;
        text-align: center;
        margin-bottom: 25px;
    }

    table {
        min-width: 0;
        font-size: 10px;
    }

    th, td {
        border: 1px solid #ccc;
        padding: 8px;
    }

    th {
        background: #eee !important;
        color: black !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<aside class="sidebar">

    <div class="brand">
        SISTEM INFORMASI<br>
        <span>ABSENSI KARYAWAN</span>
    </div>

    <div class="menu-label">MENU UTAMA</div>

    <a href="dashboard.php">Dashboard</a>
    <a href="karyawan.php">Data Karyawan</a>
    <a href="absensi.php">Data Absensi</a>
    <a href="laporan.php" class="active">Laporan Kehadiran</a>
    <a href="akun_karyawan.php">Kelola Akun</a>

    <a href="logout.php" class="logout"
       onclick="return confirm('Yakin ingin keluar?')">
        Keluar Sistem
    </a>

</aside>

<!-- KONTEN -->
<main class="main">

    <div class="topbar">
        <div>
            <h1>Laporan Kehadiran</h1>
            <p class="subtitle">
                Rekap dan pantau kehadiran seluruh karyawan.
            </p>
        </div>

        <div class="admin-label">
            ADMINISTRATOR
        </div>
    </div>

    <div class="print-header">
        <h2>SISTEM INFORMASI ABSENSI KARYAWAN</h2>
        <p>LAPORAN REKAP KEHADIRAN KARYAWAN</p>
        <p>Periode: <?php echo htmlspecialchars($periode); ?></p>
    </div>

    <!-- RINGKASAN -->
    <div class="summary">
        <p>Total Data Absensi</p>
        <h2><?php echo $total_absensi; ?></h2>
    </div>

    <!-- FILTER -->
    <section class="panel filter-panel">

        <div class="panel-title">
            Filter Laporan
        </div>

        <form method="GET">

            <div class="filter-grid">

                <div class="form-group">
                    <label>Tanggal Mulai</label>
                    <input type="date"
                           name="tanggal_mulai"
                           value="<?php echo htmlspecialchars($tanggal_mulai); ?>">
                </div>

                <div class="form-group">
                    <label>Tanggal Akhir</label>
                    <input type="date"
                           name="tanggal_akhir"
                           value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                </div>

                <div class="form-group">
                    <label>Pilih Karyawan</label>

                    <select name="karyawan_id">
                        <option value="0">Semua Karyawan</option>

                        <?php while ($k = mysqli_fetch_assoc($query_karyawan)) { ?>
                            <option
                                value="<?php echo (int)$k['id']; ?>"
                                <?php echo ($karyawan_id == $k['id']) ? 'selected' : ''; ?>
                            >
                                <?php echo htmlspecialchars($k['nama']); ?>
                            </option>
                        <?php } ?>

                    </select>
                </div>

            </div>

            <button type="submit" class="btn">
                Tampilkan Laporan
            </button>

            <a href="laporan.php" class="btn btn-reset">
                Reset Filter
            </a>

        </form>

    </section>

    <!-- TABEL LAPORAN -->
    <section class="panel">

        <div style="display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap;margin-bottom:20px;">

            <div>
                <div class="panel-title" style="margin-bottom:5px;">
                    Daftar Kehadiran
                </div>

                <p class="total-info">
                    Periode laporan: <?php echo htmlspecialchars($periode); ?>
                </p>
            </div>

            <button onclick="window.print()" class="btn btn-print no-print">
                Cetak Laporan
            </button>

        </div>

        <div class="total-info">
            Menampilkan <?php echo $total_absensi; ?> data absensi.
        </div>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>No</th>
                        <th>NIP</th>
                        <th>Nama Karyawan</th>
                        <th>Bagian</th>
                        <th>Tanggal</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>

                <tbody>

                <?php
                $no = 1;

                if ($total_absensi > 0) {
                    while ($data = mysqli_fetch_assoc($hasil)) {
                ?>

                    <tr>
                        <td><?php echo $no++; ?></td>

                        <td><?php echo htmlspecialchars($data['nip']); ?></td>

                        <td>
                            <strong>
                                <?php echo htmlspecialchars($data['nama']); ?>
                            </strong>
                        </td>

                        <td><?php echo htmlspecialchars($data['bagian']); ?></td>

                        <td><?php echo htmlspecialchars($data['tanggal']); ?></td>

                        <td>
                            <?php echo htmlspecialchars($data['jam_masuk'] ?: '-'); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($data['jam_pulang'] ?: '-'); ?>
                        </td>

                        <td>
                            <span class="keterangan">
                                <?php echo htmlspecialchars($data['keterangan'] ?? '-'); ?>
                            </span>
                        </td>
                    </tr>

                <?php
                    }
                } else {
                ?>

                    <tr>
                        <td colspan="8" class="kosong">
                            Belum ada data absensi untuk filter tersebut.
                        </td>
                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

</body>
</html>
