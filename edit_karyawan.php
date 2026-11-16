
<?php
session_start();
include 'koneksi.php';

// Periksa login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

// Hanya admin yang boleh mengedit data karyawan
if ($_SESSION['role'] !== 'admin') {
    die("Akses ditolak. Halaman ini hanya untuk admin.");
}

// Ambil ID karyawan
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: karyawan.php");
    exit;
}

$id = (int) $_GET['id'];

// Ambil data karyawan
$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM karyawan WHERE id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("Data karyawan tidak ditemukan.");
}

$pesan = "";

// Proses update data
if (isset($_POST['update'])) {

    $nip = trim($_POST['nip']);
    $nama = trim($_POST['nama']);
    $jabatan = trim($_POST['jabatan']);
    $bagian = trim($_POST['bagian']);
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $status = $_POST['status'];

    if (
        $nip === '' ||
        $nama === '' ||
        $jabatan === '' ||
        $bagian === '' ||
        $tanggal_masuk === ''
    ) {
        $pesan = "Semua kolom wajib diisi.";
    } elseif (!in_array($status, ['Aktif', 'Tidak Aktif'], true)) {
        $pesan = "Status karyawan tidak valid.";
    } else {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE karyawan
             SET nip = ?,
                 nama = ?,
                 jabatan = ?,
                 bagian = ?,
                 tanggal_masuk = ?,
                 status = ?
             WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssssssi",
            $nip,
            $nama,
            $jabatan,
            $bagian,
            $tanggal_masuk,
            $status,
            $id
        );

        if (mysqli_stmt_execute($stmt)) {
            header("Location: karyawan.php?pesan=berhasil_edit");
            exit;
        } else {
            if (mysqli_errno($conn) == 1062) {
                $pesan = "NIP sudah digunakan oleh karyawan lain.";
            } else {
                $pesan = "Gagal memperbarui data karyawan.";
            }
        }
    }

    // Pertahankan input jika update gagal
    $data['nip'] = $nip;
    $data['nama'] = $nama;
    $data['jabatan'] = $jabatan;
    $data['bagian'] = $bagian;
    $data['tanggal_masuk'] = $tanggal_masuk;
    $data['status'] = $status;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Karyawan</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f4f7;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        h2 {
            color: #355070;
            text-align: center;
        }

        label {
            display: block;
            margin-top: 12px;
        }

        input, select {
            width: 100%;
            box-sizing: border-box;
            padding: 11px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            background: #355070;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .kembali {
            display: block;
            margin-top: 15px;
            text-align: center;
            color: #355070;
            text-decoration: none;
        }

        .pesan {
            background: #e8f1fa;
            padding: 12px;
            border-radius: 5px;
            margin-top: 15px;
        }
    </style>
</head>

<body>

<div class="container">

    <h2>Edit Data Karyawan</h2>

    <?php if ($pesan !== '') { ?>
        <div class="pesan">
            <?php echo htmlspecialchars($pesan); ?>
        </div>
    <?php } ?>

    <form method="POST">

        <label>NIP</label>
        <input
            type="text"
            name="nip"
            value="<?php echo htmlspecialchars($data['nip']); ?>"
            required
        >

        <label>Nama Karyawan</label>
        <input
            type="text"
            name="nama"
            value="<?php echo htmlspecialchars($data['nama']); ?>"
            required
        >

        <label>Jabatan</label>
        <input
            type="text"
            name="jabatan"
            value="<?php echo htmlspecialchars($data['jabatan']); ?>"
            required
        >

        <label>Bagian</label>
        <input
            type="text"
            name="bagian"
            value="<?php echo htmlspecialchars($data['bagian']); ?>"
            required
        >

        <label>Tanggal Masuk</label>
        <input
            type="date"
            name="tanggal_masuk"
            value="<?php echo htmlspecialchars($data['tanggal_masuk'] ?? ''); ?>"
            required
        >

        <label>Status Karyawan</label>
        <select name="status" required>
            <option value="Aktif"
                <?php echo ($data['status'] == 'Aktif') ? 'selected' : ''; ?>>
                Aktif
            </option>

            <option value="Tidak Aktif"
                <?php echo ($data['status'] == 'Tidak Aktif') ? 'selected' : ''; ?>>
                Tidak Aktif
            </option>
        </select>

        <button type="submit" name="update">
            Simpan Perubahan
        </button>

    </form>

    <a href="karyawan.php" class="kembali">
        Kembali ke Data Karyawan
    </a>

</div>

</body>
</html>
