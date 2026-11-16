
<?php
session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

include 'koneksi.php';

$pesan = "";
$jenis_pesan = "info";

// ============================
// MEMBUAT AKUN KARYAWAN
// ============================
if (isset($_POST['buat_akun'])) {

    $karyawan_id = (int) $_POST['karyawan_id'];
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($karyawan_id <= 0 || $username === '' || $password === '') {

        $pesan = "Semua kolom wajib diisi.";
        $jenis_pesan = "error";

    } elseif (strlen($password) < 6) {

        $pesan = "Password minimal 6 karakter.";
        $jenis_pesan = "error";

    } else {

        // Periksa karyawan
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, user_id
             FROM karyawan
             WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $karyawan_id);
        mysqli_stmt_execute($stmt);

        $data_karyawan = mysqli_fetch_assoc(
            mysqli_stmt_get_result($stmt)
        );

        mysqli_stmt_close($stmt);

        if (!$data_karyawan) {

            $pesan = "Data karyawan tidak ditemukan.";
            $jenis_pesan = "error";

        } elseif ($data_karyawan['user_id'] !== null) {

            $pesan = "Karyawan tersebut sudah memiliki akun.";
            $jenis_pesan = "error";

        } else {

            // Periksa username
            $stmt = mysqli_prepare(
                $conn,
                "SELECT id FROM users WHERE username = ?"
            );

            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);

            $username_ada = mysqli_fetch_assoc(
                mysqli_stmt_get_result($stmt)
            );

            mysqli_stmt_close($stmt);

            if ($username_ada) {

                $pesan = "Username sudah digunakan.";
                $jenis_pesan = "error";

            } else {

                $password_hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                mysqli_begin_transaction($conn);

                try {

                    // Membuat akun
                    $stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO users (username, password, role)
                         VALUES (?, ?, 'karyawan')"
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "ss",
                        $username,
                        $password_hash
                    );

                    mysqli_stmt_execute($stmt);

                    $user_id = mysqli_insert_id($conn);
                    mysqli_stmt_close($stmt);

                    // Menghubungkan akun ke karyawan
                    $stmt = mysqli_prepare(
                        $conn,
                        "UPDATE karyawan
                         SET user_id = ?
                         WHERE id = ? AND user_id IS NULL"
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "ii",
                        $user_id,
                        $karyawan_id
                    );

                    mysqli_stmt_execute($stmt);

                    if (mysqli_stmt_affected_rows($stmt) !== 1) {
                        throw new Exception("Gagal menghubungkan akun.");
                    }

                    mysqli_stmt_close($stmt);

                    mysqli_commit($conn);

                    header("Location: akun_karyawan.php?pesan=berhasil");
                    exit;

                } catch (Throwable $e) {

                    mysqli_rollback($conn);

                    $pesan = "Gagal membuat akun. Periksa kembali data database.";
                    $jenis_pesan = "error";
                }
            }
        }
    }
}

// ============================
// NOTIFIKASI
// ============================
if (isset($_GET['pesan']) && $_GET['pesan'] == 'berhasil') {
    $pesan = "Akun karyawan berhasil dibuat!";
    $jenis_pesan = "success";
}

// ============================
// DAFTAR KARYAWAN BELUM PUNYA AKUN
// ============================
$query_karyawan = mysqli_query(
    $conn,
    "SELECT id, nip, nama
     FROM karyawan
     WHERE user_id IS NULL
     ORDER BY nama ASC"
);

// ============================
// DAFTAR AKUN YANG SUDAH DIBUAT
// ============================
$query_akun = mysqli_query(
    $conn,
    "SELECT k.nip, k.nama, u.username
     FROM karyawan k
     INNER JOIN users u ON k.user_id = u.id
     ORDER BY k.nama ASC"
);

$total_akun = mysqli_num_rows($query_akun);
$total_belum_akun = mysqli_num_rows($query_karyawan);
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Kelola Akun | Sistem Absensi</title>

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

/* SIDEBAR */

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

/* KONTEN */

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

/* RINGKASAN */

.summary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(180px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.summary {
    background: white;
    padding: 23px;
    border-radius: 12px;
    box-shadow: 0 3px 15px rgba(30,55,80,0.05);
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

/* PANEL */

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

/* FORM */

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
    outline: none;
    background: white;
    font-size: 14px;
}

input:focus,
select:focus {
    border-color: #52799e;
    box-shadow: 0 0 0 3px rgba(53,80,112,0.1);
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
    margin-top: 20px;
}

.btn:hover {
    background: #263e59;
}

/* NOTIFIKASI */

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

.info {
    background: #e8f1fa;
    color: #355070;
}

/* TABEL */

.table-wrapper {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 600px;
}

th {
    background: #f0f4f8;
    color: #42566d;
    padding: 14px 12px;
    font-size: 12px;
    text-align: left;
}

td {
    padding: 14px 12px;
    border-bottom: 1px solid #edf0f4;
    font-size: 13px;
}

tr:hover td {
    background: #fafbfd;
}

.kosong {
    text-align: center;
    color: #8995a3;
    padding: 30px;
}

.status {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    background: #e1f5e8;
    color: #287744;
    font-size: 11px;
    font-weight: bold;
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

    .form-grid {
        grid-template-columns: 1fr;
    }

    .summary-grid {
        grid-template-columns: 1fr;
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
    <a href="laporan.php">Laporan Kehadiran</a>
    <a href="akun_karyawan.php" class="active">Kelola Akun</a>

    <a href="logout.php" class="logout"
       onclick="return confirm('Yakin ingin keluar dari sistem?')">
        Keluar Sistem
    </a>

</aside>

<!-- KONTEN UTAMA -->

<main class="main">

    <div class="topbar">

        <div>
            <h1>Kelola Akun Karyawan</h1>

            <p class="subtitle">
                Buat dan kelola akun akses sistem untuk karyawan.
            </p>
        </div>

        <div class="admin-label">
            ADMINISTRATOR
        </div>

    </div>

    <!-- NOTIFIKASI -->

    <?php if ($pesan !== '') { ?>

        <div class="pesan <?php echo $jenis_pesan; ?>">
            <?php echo htmlspecialchars($pesan); ?>
        </div>

    <?php } ?>

    <!-- RINGKASAN -->

    <div class="summary-grid">

        <div class="summary">
            <p>Total Akun Karyawan</p>
            <h2><?php echo $total_akun; ?></h2>
        </div>

        <div class="summary">
            <p>Karyawan Belum Memiliki Akun</p>
            <h2><?php echo $total_belum_akun; ?></h2>
        </div>

    </div>

    <!-- FORM BUAT AKUN -->

    <section class="panel">

        <div class="panel-title">
            Buat Akun Login Baru
        </div>

        <form method="POST">

            <div class="form-grid">

                <div class="form-group">

                    <label>Pilih Karyawan</label>

                    <select name="karyawan_id" required>

                        <option value="">
                            -- Pilih Karyawan --
                        </option>

                        <?php while ($k = mysqli_fetch_assoc($query_karyawan)) { ?>

                            <option value="<?php echo (int)$k['id']; ?>">

                                <?php echo htmlspecialchars($k['nama']); ?>
                                - NIP <?php echo htmlspecialchars($k['nip']); ?>

                            </option>

                        <?php } ?>

                    </select>

                </div>

                <div class="form-group">

                    <label>Username</label>

                    <input
                        type="text"
                        name="username"
                        placeholder="Masukkan username"
                        maxlength="50"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>Password</label>

                    <input
                        type="password"
                        name="password"
                        placeholder="Minimal 6 karakter"
                        minlength="6"
                        required
                    >

                </div>

            </div>

            <button type="submit" name="buat_akun" class="btn">
                + Buat Akun Karyawan
            </button>

        </form>

    </section>

    <!-- DAFTAR AKUN -->

    <section class="panel">

        <div class="panel-title">
            Daftar Akun Karyawan
        </div>

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>
                        <th>No</th>
                        <th>NIP</th>
                        <th>Nama Karyawan</th>
                        <th>Username</th>
                        <th>Status Akun</th>
                    </tr>

                </thead>

                <tbody>

                    <?php
                    $no = 1;

                    if ($total_akun > 0) {

                        while ($a = mysqli_fetch_assoc($query_akun)) {
                    ?>

                        <tr>

                            <td><?php echo $no++; ?></td>

                            <td>
                                <?php echo htmlspecialchars($a['nip']); ?>
                            </td>

                            <td>
                                <strong>
                                    <?php echo htmlspecialchars($a['nama']); ?>
                                </strong>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($a['username']); ?>
                            </td>

                            <td>
                                <span class="status">Aktif</span>
                            </td>

                        </tr>

                    <?php
                        }

                    } else {
                    ?>

                        <tr>
                            <td colspan="5" class="kosong">
                                Belum ada akun karyawan yang dibuat.
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
