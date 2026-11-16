
<?php
session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

// Halaman ini hanya untuk admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

include 'koneksi.php';

$pesan = "";
$jenis_pesan = "success";

// ============================
// TAMBAH DATA KARYAWAN
// ============================
if (isset($_POST['tambah'])) {

    $nip = trim($_POST['nip']);
    $nama = trim($_POST['nama']);
    $jabatan = trim($_POST['jabatan']);
    $bagian = trim($_POST['bagian']);
    $tanggal_masuk = !empty($_POST['tanggal_masuk'])
        ? $_POST['tanggal_masuk']
        : null;
    $status = $_POST['status'];

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO karyawan
        (nip, nama, jabatan, bagian, tanggal_masuk, status)
        VALUES (?, ?, ?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ssssss",
        $nip,
        $nama,
        $jabatan,
        $bagian,
        $tanggal_masuk,
        $status
    );

    if (mysqli_stmt_execute($stmt)) {
        header("Location: karyawan.php?pesan=tambah");
        exit;
    } else {
        $pesan = "Gagal menambahkan data. Pastikan NIP belum digunakan.";
        $jenis_pesan = "error";
    }

    mysqli_stmt_close($stmt);
}

// ============================
// HAPUS DATA KARYAWAN
// ============================
if (isset($_GET['hapus'])) {

    $id = (int) $_GET['hapus'];

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM karyawan WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: karyawan.php?pesan=hapus");
        exit;
    } else {
        $pesan = "Data gagal dihapus. Periksa apakah karyawan masih memiliki data absensi.";
        $jenis_pesan = "error";
    }

    mysqli_stmt_close($stmt);
}

// ============================
// NOTIFIKASI
// ============================
if (isset($_GET['pesan'])) {

    if ($_GET['pesan'] == 'tambah') {
        $pesan = "Data karyawan berhasil ditambahkan!";
    } elseif ($_GET['pesan'] == 'hapus') {
        $pesan = "Data karyawan berhasil dihapus!";
    } elseif ($_GET['pesan'] == 'berhasil_edit') {
        $pesan = "Data karyawan berhasil diperbarui!";
    }
}

// ============================
// MENGAMBIL DATA KARYAWAN
// ============================
$result = mysqli_query(
    $conn,
    "SELECT * FROM karyawan ORDER BY id DESC"
);

if (!$result) {
    die("Gagal mengambil data: " . mysqli_error($conn));
}

$total_karyawan = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Data Karyawan | Sistem Absensi</title>

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
            margin-bottom: 40px;
            line-height: 1.5;
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
            transition: 0.2s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #456687;
            color: white;
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
            box-shadow: 0 3px 15px rgba(30, 55, 80, 0.05);
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
            box-shadow: 0 3px 15px rgba(30, 55, 80, 0.05);
            margin-bottom: 25px;
        }

        .panel-title {
            color: #263e59;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
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
            padding: 12px 14px;
            border: 1px solid #dce3eb;
            border-radius: 7px;
            font-size: 14px;
            outline: none;
            background: white;
        }

        input:focus,
        select:focus {
            border-color: #52799e;
            box-shadow: 0 0 0 3px rgba(53, 80, 112, 0.1);
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
            transition: 0.2s;
        }

        .btn:hover {
            background: #263e59;
        }

        .btn-submit {
            margin-top: 20px;
        }

        .btn-edit {
            background: #e6eff8;
            color: #355070;
            padding: 8px 12px;
        }

        .btn-edit:hover {
            background: #cfdfef;
        }

        .btn-delete {
            background: #fbe9e9;
            color: #b23b3b;
            padding: 8px 12px;
        }

        .btn-delete:hover {
            background: #f4d5d5;
        }

        .pesan {
            padding: 14px 17px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .success {
            background: #e4f5e9;
            color: #267341;
        }

        .error {
            background: #fdeaea;
            color: #a12c2c;
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

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .aktif {
            background: #e1f5e8;
            color: #287744;
        }

        .tidak-aktif {
            background: #fce9e9;
            color: #a83d3d;
        }

        .kosong {
            text-align: center;
            color: #8995a3;
            padding: 30px;
        }

        .aksi {
            display: flex;
            gap: 7px;
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

            .menu-label {
                margin-top: 15px;
            }

            .main {
                margin-left: 0;
                padding: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .summary {
                max-width: 100%;
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
    <a href="karyawan.php" class="active">Data Karyawan</a>
    <a href="absensi.php">Data Absensi</a>
    <a href="laporan.php">Laporan Kehadiran</a>
    <a href="akun_karyawan.php">Kelola Akun</a>

    <a href="logout.php" class="logout"
       onclick="return confirm('Yakin ingin keluar dari sistem?')">
        Keluar Sistem
    </a>

</aside>

<!-- KONTEN UTAMA -->
<main class="main">

    <div class="topbar">
        <div>
            <h1>Data Karyawan</h1>
            <p class="subtitle">
                Kelola informasi dan data seluruh karyawan.
            </p>
        </div>

        <div class="admin-label">
            ADMINISTRATOR
        </div>
    </div>

    <?php if ($pesan != "") { ?>
        <div class="pesan <?php echo $jenis_pesan; ?>">
            <?php echo htmlspecialchars($pesan); ?>
        </div>
    <?php } ?>

    <!-- RINGKASAN -->
    <div class="summary">
        <p>Total Karyawan Terdaftar</p>
        <h2><?php echo $total_karyawan; ?></h2>
    </div>

    <!-- FORM TAMBAH -->
    <section class="panel">

        <div class="panel-title">
            Tambah Karyawan Baru
        </div>

        <form method="POST">

            <div class="form-grid">

                <div class="form-group">
                    <label>NIP Karyawan</label>
                    <input type="text" name="nip"
                           placeholder="Contoh: 10003" required>
                </div>

                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama"
                           placeholder="Masukkan nama karyawan" required>
                </div>

                <div class="form-group">
                    <label>Jabatan</label>
                    <input type="text" name="jabatan"
                           placeholder="Contoh: Staff Administrasi" required>
                </div>

                <div class="form-group">
                    <label>Bagian / Divisi</label>
                    <input type="text" name="bagian"
                           placeholder="Contoh: Administrasi" required>
                </div>

                <div class="form-group">
                    <label>Tanggal Masuk</label>
                    <input type="date" name="tanggal_masuk">
                </div>

                <div class="form-group">
                    <label>Status Karyawan</label>
                    <select name="status" required>
                        <option value="Aktif">Aktif</option>
                        <option value="Tidak Aktif">Tidak Aktif</option>
                    </select>
                </div>

            </div>

            <button type="submit" name="tambah" class="btn btn-submit">
                + Simpan Data Karyawan
            </button>

        </form>

    </section>

    <!-- TABEL KARYAWAN -->
    <section class="panel">

        <div class="panel-title">
            Daftar Karyawan
        </div>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>No</th>
                        <th>NIP</th>
                        <th>Nama Karyawan</th>
                        <th>Jabatan</th>
                        <th>Bagian</th>
                        <th>Tanggal Masuk</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php
                $no = 1;

                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                ?>

                    <tr>
                        <td><?php echo $no++; ?></td>

                        <td>
                            <?php echo htmlspecialchars($row['nip']); ?>
                        </td>

                        <td>
                            <strong>
                                <?php echo htmlspecialchars($row['nama']); ?>
                            </strong>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row['jabatan']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row['bagian']); ?>
                        </td>

                        <td>
                            <?php
                            echo !empty($row['tanggal_masuk'])
                                ? htmlspecialchars($row['tanggal_masuk'])
                                : '-';
                            ?>
                        </td>

                        <td>
                            <?php if ($row['status'] == 'Aktif') { ?>
                                <span class="status aktif">Aktif</span>
                            <?php } else { ?>
                                <span class="status tidak-aktif">Tidak Aktif</span>
                            <?php } ?>
                        </td>

                        <td>
                            <div class="aksi">

                                <a class="btn btn-edit"
                                   href="edit_karyawan.php?id=<?php echo (int)$row['id']; ?>">
                                    Edit
                                </a>

                                <a class="btn btn-delete"
                                   href="karyawan.php?hapus=<?php echo (int)$row['id']; ?>"
                                   onclick="return confirm('Yakin ingin menghapus karyawan ini?')">
                                    Hapus
                                </a>

                            </div>
                        </td>
                    </tr>

                <?php
                    }
                } else {
                ?>

                    <tr>
                        <td colspan="8" class="kosong">
                            Belum ada data karyawan.
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
