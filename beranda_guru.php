<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

try
// Proteksi: hanya guru_mapel dan wali_kelas yang boleh akses
$allowed_roles = ['guru_mapel', 'wali_kelas'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../index.php");
    exit;
}

if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/db.php';

$guru_id = $_SESSION['id'];
$guru_nama = $_SESSION['nama'] ?? 'Guru';

function hariID($timestamp = null) {
    $map = [1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu'];
    return $map[intval(date('N', $timestamp ?? time()))] ?? '';
}

$hari_ini = hariID();
$tanggal_sql = date('Y-m-d');

// ==================== INFORMASI ====================
$sql_informasi = "
    SELECT * FROM informasi 
    WHERE (ditujukan = 'guru' OR ditujukan = 'umum')
    AND tanggal >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY tanggal DESC LIMIT 3";
$informasi_result = $conn->query($sql_informasi);
$informasi_list = $informasi_result ? $informasi_result->fetch_all(MYSQLI_ASSOC) : [];

// ==================== STATISTIK ====================
$stmt = $conn->prepare("SELECT COUNT(*) AS total_kelas FROM jadwal WHERE id_guru = ?");
$stmt->bind_param("i", $guru_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$total_kelas = $res['total_kelas'] ?? 0;
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS total_jadwal_hari FROM jadwal WHERE id_guru = ? AND hari = ?");
$stmt->bind_param("is", $guru_id, $hari_ini);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$total_jadwal_hari = $res['total_jadwal_hari'] ?? 0;
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS total_absensi FROM absensi WHERE id_guru = ? AND tanggal = ?");
$stmt->bind_param("is", $guru_id, $tanggal_sql);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$total_absensi = $res['total_absensi'] ?? 0;
$stmt->close();

$stmt = $conn->prepare("SELECT status_edit FROM absensi WHERE tanggal = ? LIMIT 1");
$stmt->bind_param("s", $tanggal_sql);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$status_absensi = $res['status_edit'] ?? 'open';
$stmt->close();

$hadir_hari_ini = "$total_absensi/$total_jadwal_hari Jadwal";

$sql_jadwal_berikutnya = "
    SELECT j.*, m.nama_mapel 
    FROM jadwal j
    LEFT JOIN mapel m ON j.id_mapel = m.id
    WHERE j.id_guru = ? AND j.hari = ?
    ORDER BY j.jam_mulai ASC
    LIMIT 1
";
$stmt = $conn->prepare($sql_jadwal_berikutnya);
$stmt->bind_param("is", $guru_id, $hari_ini);
$stmt->execute();
$jadwal_berikutnya = $stmt->get_result()->fetch_assoc();
$jadwal_berikutnya_text = $jadwal_berikutnya ? $jadwal_berikutnya['nama_mapel'] : "Tidak ada jadwal";
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Guru</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* Gaya tetap sama — potongan penting saja */
body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #d9ebd0 0%, #c1dec8 100%);
    padding: 20px;
}
.container { max-width: 1200px; margin: auto; }

/* Informasi Ringkas */
.informasi-section {
    background: white;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}
.informasi-section h4 {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #2c3e2f;
    margin-bottom: 12px;
}
.informasi-item {
    padding: 10px 12px;
    border-radius: 8px;
    background: #f8f9fa;
    border-left: 3px solid #f68c2e;
    margin-bottom: 10px;
    cursor: pointer;
    transition: 0.3s;
}
.informasi-item:hover {
    background: #f0f0f0;
}
.informasi-judul {
    font-size: 13px;
    font-weight: 600;
    color: #2c3e2f;
    margin-bottom: 4px;
}
.informasi-isi {
    font-size: 12px;
    color: #6b8e70;
    line-height: 1.4;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2; 
    -webkit-box-orient: vertical;
}
.informasi-meta {
    font-size: 10px;
    color: #a0b5a0;
    margin-top: 4px;
}
.informasi-footer {
    text-align: right;
    margin-top: 10px;
}
.informasi-footer a {
    color: #f68c2e;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
}
.informasi-footer a:hover { text-decoration: underline; }

/* Quick Access di bawah informasi */
.quick-access {
    background: white;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}
.quick-buttons {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}
.quick-btn {
    height: 70px;
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    color: white;
    font-size: 20px;
    text-decoration: none;
    transition: all 0.3s ease;
}
.btn-orange { background: linear-gradient(135deg, #f68c2e 0%, #ff9d4d 100%); }
.btn-gray { background: linear-gradient(135deg, #a0b5a0 0%, #b5c9b5 100%); }
.quick-btn span { font-size: 11px; font-weight: 600; }
.quick-btn:hover { transform: translateY(-3px); }
</style>
</head>
<body>
<div class="container">

<!-- HEADER -->
<?php
$stmt = $conn->prepare("SELECT foto_profil FROM guru_foto_profil WHERE guru_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $guru_id);
$stmt->execute();
$foto_result = $stmt->get_result();
$foto_data = $foto_result->fetch_assoc();
$foto_profil = $foto_data['foto_profil'] ?? null;
$stmt->close();
?>
<div class="header" style="display:flex;align-items:center;gap:20px;margin-bottom:20px;">
    <a href="profil.php" class="avatar-container" style="text-decoration:none;">
        <div class="header-avatar" style="width:70px;height:70px;border-radius:50%;overflow:hidden;">
            <?php if ($foto_profil): ?>
                <img src="../uploads/profil/<?= $foto_profil ?>?t=<?= time() ?>" 
                     alt="Foto Profil" style="width:100%;height:100%;object-fit:cover;">
            <?php else: ?>
                <div style="background:#fff;text-align:center;line-height:70px;color:#f68c2e;font-weight:bold;">
                    <?= strtoupper(substr($guru_nama,0,1)) ?>
                </div>
            <?php endif; ?>
        </div>
    </a>
    <div>
        <h3 style="margin:0;"><?= htmlspecialchars($guru_nama) ?></h3>
        <p style="color:#666;"><?= ucfirst($_SESSION['role']) ?> • <?= $hari_ini ?>, <?= date('d M Y') ?></p>
    </div>
</div>

<!-- Statistik -->
<div class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-bottom:20px;">
    <div class="stat-card"><b>Hadir Hari Ini:</b> <?= $hadir_hari_ini ?></div>
    <div class="stat-card"><b>Jadwal Berikutnya:</b> <?= htmlspecialchars($jadwal_berikutnya_text) ?></div>
    <div class="stat-card"><b>Total Kelas:</b> <?= $total_kelas ?> Kelas</div>
</div>

<!-- Status -->
<div class="status-absensi" style="background:<?= $status_absensi=='open'?'#28a745':'#6c757d' ?>;color:white;padding:16px;border-radius:12px;margin-bottom:20px;">
    STATUS ABSENSI: <?= strtoupper($status_absensi) ?> — 
    <?= $status_absensi=='open'?'Guru dapat menginput absensi':'Periode absensi ditutup' ?>
</div>

<!-- INFORMASI -->
<div class="informasi-section">
    <h4><i class="fas fa-info-circle"></i> Informasi Terbaru</h4>
    <?php if (count($informasi_list) > 0): ?>
        <?php foreach ($informasi_list as $info): ?>
            <div class="informasi-item" onclick="window.location='informasi.php'">
                <div class="informasi-judul"><?= htmlspecialchars($info['judul']) ?></div>
                <div class="informasi-isi"><?= nl2br(htmlspecialchars($info['isi'])) ?></div>
                <div class="informasi-meta"><?= date('d/m/Y H:i', strtotime($info['tanggal'])) ?></div>
            </div>
        <?php endforeach; ?>
        <div class="informasi-footer">
            <a href="informasi.php">Lihat Semua &raquo;</a>
        </div>
    <?php else: ?>
        <p style="text-align:center;color:#a0b5a0;">Tidak ada informasi baru</p>
    <?php endif; ?>
</div>

<!-- QUICK ACCESS -->
<div class="quick-access">
    <h4><i class="fas fa-bolt"></i> Akses Cepat</h4>
    <div class="quick-buttons">
        <a href="jadwal.php" class="quick-btn btn-orange">
            <i class="fas fa-calendar-alt"></i><span>Jadwal</span>
        </a>
        <a href="notifikasi.php" class="quick-btn btn-gray">
            <i class="fas fa-bell"></i><span>Notifikasi</span>
        </a>
    </div>
</div>

<!-- LOGOUT FLOATING -->
<div class="floating-logout" style="position:fixed;bottom:30px;right:30px;">
    <a href="logout.php" class="floating-btn" style="display:flex;align-items:center;justify-content:center;width:60px;height:60px;border-radius:50%;background:#dc3545;color:white;text-decoration:none;">
        <i class="fas fa-sign-out-alt"></i>
    </a>
</div>

</div>
</body>
</html>
