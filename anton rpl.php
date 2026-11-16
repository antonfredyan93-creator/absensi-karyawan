
<?php
session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

$username = htmlspecialchars($_SESSION['username']);
$role = htmlspecialchars($_SESSION['role']);

$query_karyawan = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM karyawan"
);

$total_karyawan = $query_karyawan
    ? mysqli_fetch_assoc($query_karyawan)['total']
    : 0;

$query_absensi = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM absensi"
);

$total_absensi = $query_absensi
    ? mysqli_fetch_assoc($query_absensi)['total']
    : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Absensi Karyawan</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #F2F2F2;
        }

        header {
            background: #355070;
            color: white;
            padding: 25px 35px;
        }

        .container {
            padding: 30px;
        }

        .menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px #ddd;
        }

        .card h3 {
            color: #355070;
        }

        .number {
            font-size: 32px;
            font-weight: bold;
            color: #355070;
        }

        a {
            display: inline-block;
            background: #355070;
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 10px;
        }

        .logout {
            background: #b23b3b;
        }
    </style>
</head>

<body>

<header>
    <h2>SISTEM INFORMASI ABSENSI KARYAWAN</h2>
    <p>Selamat datang, <?php echo $username; ?>!</p>
    <p>Role: <?php echo $role; ?></p>
</header>

<div class="container">

    <h2>Dashboard Admin</h2>
    <p>Ringkasan sistem informasi absensi karyawan.</p>

    <div class="menu">

        <div class="card">
            <h3>Total Karyawan</h3>
            <div class="number"><?php echo $total_karyawan; ?></div>
            <a href="karyawan.php">Data Karyawan</a>
        </div>

        <div class="card">
            <h3>Total Absensi</h3>
            <div class="number"><?php echo $total_absensi; ?></div>
            <a href="absensi.php">Data Absensi</a>
        </div>

        <div class="card">
            <h3>Laporan Kehadiran</h3>
            <p>Melihat rekap kehadiran karyawan.</p>
            <a href="laporan.php">Lihat Laporan</a>
        </div>

        <div class="card">
            <h3>Keluar Sistem</h3>
            <p>Akhiri sesi penggunaan aplikasi.</p>
            <a href="logout.php" class="logout">Logout</a>
        </div>

    </div>

</div>

</body>
</html>
