<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Helper nama hari
function hariID($timestamp = null) {
    $map = [1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu'];
    return $map[intval(date('N', $timestamp ?? time()))] ?? '';
}

$hari_ini = hariID();
$tanggal_sql = date('Y-m-d');

// ==================== INFORMASI DARI ADMIN ====================
$sql_informasi = "
    SELECT * FROM informasi 
    WHERE (ditujukan = 'guru' OR ditujukan = 'umum')
    AND tanggal >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY tanggal DESC LIMIT 3";
$informasi_result = $conn->query($sql_informasi);
$informasi_list = $informasi_result ? $informasi_result->fetch_all(MYSQLI_ASSOC) : [];

// ==================== STATISTIK ====================
// 1) Total kelas yang diajar (berdasarkan jadwal)
$stmt = $conn->prepare("SELECT COUNT(*) AS total_kelas FROM jadwal WHERE id_guru = ?");
$stmt->bind_param("i", $guru_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$total_kelas = $res['total_kelas'] ?? 0;
$stmt->close();

// 2) Total jadwal hari ini
$stmt = $conn->prepare("SELECT COUNT(*) AS total_jadwal_hari FROM jadwal WHERE id_guru = ? AND hari = ?");
$stmt->bind_param("is", $guru_id, $hari_ini);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$total_jadwal_hari = $res['total_jadwal_hari'] ?? 0;
$stmt->close();

// 3) Total absensi hari ini
$stmt = $conn->prepare("SELECT COUNT(*) AS total_absensi FROM absensi WHERE id_guru = ? AND tanggal = ?");
$stmt->bind_param("is", $guru_id, $tanggal_sql);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$total_absensi = $res['total_absensi'] ?? 0;
$stmt->close();

// 4) Status absensi
$stmt = $conn->prepare("SELECT status_edit FROM absensi WHERE tanggal = ? LIMIT 1");
$stmt->bind_param("s", $tanggal_sql);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$status_absensi = $res['status_edit'] ?? 'open';
$stmt->close();

$hadir_hari_ini = "$total_absensi/$total_jadwal_hari Jadwal";

// 6) Jadwal berikutnya
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
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard Wali / Guru</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{
  --orange: #FF9D4D;
  --orange-dark: #f68c2e;
  --green: #7FB069;
  --green-dark: #4d6651;
  --green-light: #A8D08D;
  --red: #FF6B6B;
  --bg: #E8F4F0;
  --white: #FFFFFF;
  --text-dark: #2D3748;
  --text-light: #718096;
  --shadow: 0 2px 8px rgba(0,0,0,0.08);
  --shadow-lg: 0 4px 16px rgba(0,0,0,0.12);
}

*{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:'Poppins',sans-serif;
  background:var(--bg);
  color:var(--text-dark);
  -webkit-font-smoothing:antialiased;
  padding-bottom:80px;
}

/* Header dengan foto profil */
.header{
  background:linear-gradient(135deg, var(--orange-dark) 0%, var(--orange) 100%);
  padding:20px;
  color:var(--white);
  border-radius:0 0 24px 24px;
}
.header-content{
  max-width:1200px;
  margin:0 auto;
  display:flex;
  align-items:center;
  gap:12px;
}
.header-avatar{
  width:48px;
  height:48px;
  border-radius:50%;
  overflow:hidden;
  background:var(--white);
  display:flex;
  align-items:center;
  justify-content:center;
  font-weight:700;
  color:var(--orange);
  font-size:20px;
  flex-shrink:0;
  box-shadow:0 2px 8px rgba(0,0,0,0.15);
}
.header-avatar img{
  width:100%;
  height:100%;
  object-fit:cover;
}
.header-info h3{
  font-size:15px;
  font-weight:600;
  margin-bottom:2px;
}
.header-info p{
  font-size:12px;
  opacity:0.9;
}

/* Container utama */
.container{
  max-width:1200px;
  margin:0 auto;
  padding:20px;
}

/* Stats Cards - Gaya Baru dengan Border Radius */
.stats-section{
  display:flex;
  flex-direction:column;
  gap:10px;
  margin-bottom:20px;
}
.stat-card{
  background:var(--white);
  border-radius:16px;
  padding:14px 16px;
  box-shadow:var(--shadow);
  display:flex;
  align-items:center;
  gap:12px;
}
.stat-icon{
  width:36px;
  height:36px;
  border-radius:8px;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:18px;
  flex-shrink:0;
  background:var(--green-dark);
  color:var(--white);
}
.stat-content{
  flex:1;
}
.stat-content h4{
  font-size:11px;
  color:var(--text-light);
  font-weight:500;
  margin-bottom:2px;
}
.stat-content p{
  font-size:14px;
  font-weight:600;
  color:var(--text-dark);
}

/* Urgent Info */
.urgent-info{
  background:linear-gradient(135deg, var(--orange-dark) 0%, var(--orange) 100%);
  border-radius:16px;
  padding:16px;
  margin-bottom:20px;
  color:var(--white);
  box-shadow:var(--shadow);
}
.urgent-info h3{
  font-size:12px;
  font-weight:700;
  text-transform:uppercase;
  letter-spacing:0.5px;
  margin-bottom:6px;
}
.urgent-info p{
  font-size:12px;
  line-height:1.4;
  opacity:0.95;
}

/* Quick Access */
.quick-access{
  margin-bottom:20px;
}
.quick-access h3{
  font-size:15px;
  font-weight:600;
  margin-bottom:12px;
  color:var(--text-dark);
}
.quick-buttons{
  display:grid;
  grid-template-columns:repeat(3, 1fr);
  gap:10px;
}
.quick-btn{
  background:var(--white);
  border-radius:16px;
  padding:20px 12px;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  gap:8px;
  text-decoration:none;
  box-shadow:var(--shadow);
  transition:all 0.3s ease;
  min-height:100px;
}
.quick-btn:hover{
  transform:translateY(-2px);
  box-shadow:var(--shadow-lg);
}
.quick-btn-icon{
  width:48px;
  height:48px;
  border-radius:12px;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:24px;
  color:var(--white);
}
.quick-btn.green .quick-btn-icon{background:var(--green-light)}
.quick-btn.orange .quick-btn-icon{background:var(--orange)}
.quick-btn.blue .quick-btn-icon{background:#6B9BC3}
.quick-btn span{
  font-size:12px;
  font-weight:600;
  color:var(--text-dark);
  text-align:center;
}

/* Informasi Cards */
.info-section h3{
  font-size:15px;
  font-weight:600;
  margin-bottom:12px;
  color:var(--text-dark);
}
.info-list{
  display:flex;
  flex-direction:column;
  gap:10px;
}
.info-card{
  background:var(--white);
  border-radius:16px;
  padding:14px;
  box-shadow:var(--shadow);
  text-decoration:none;
  color:inherit;
  border-left:4px solid var(--orange);
  transition:all 0.3s ease;
}
.info-card:hover{
  transform:translateX(4px);
  box-shadow:var(--shadow-lg);
}
.info-header{
  display:flex;
  justify-content:space-between;
  align-items:start;
  margin-bottom:8px;
}
.info-title{
  font-size:13px;
  font-weight:600;
  color:var(--text-dark);
  flex:1;
}
.info-badge{
  background:var(--green-dark);
  color:var(--white);
  padding:3px 8px;
  border-radius:6px;
  font-size:10px;
  font-weight:600;
  text-transform:uppercase;
}
.info-text{
  font-size:12px;
  color:var(--text-light);
  line-height:1.4;
  margin-bottom:8px;
  display:-webkit-box;
  -webkit-line-clamp:2;
  -webkit-box-orient:vertical;
  overflow:hidden;
}
.info-meta{
  font-size:10px;
  color:var(--text-light);
  display:flex;
  align-items:center;
  gap:4px;
}
.info-footer{
  text-align:right;
  margin-top:12px;
}
.info-footer a{
  color:var(--orange-dark);
  text-decoration:none;
  font-weight:600;
  font-size:12px;
}

/* Empty state */
.empty-state{
  background:var(--white);
  border-radius:16px;
  padding:32px;
  text-align:center;
  box-shadow:var(--shadow);
}
.empty-state i{
  font-size:48px;
  color:var(--text-light);
  margin-bottom:12px;
}
.empty-state p{
  color:var(--text-light);
  font-size:13px;
}

/* Floating Logout */
.floating-logout{
  position:fixed;
  right:20px;
  bottom:20px;
  z-index:999;
}
.floating-btn{
  width:56px;
  height:56px;
  border-radius:50%;
  background:linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  color:var(--white);
  display:flex;
  align-items:center;
  justify-content:center;
  text-decoration:none;
  box-shadow:0 4px 16px rgba(220,53,69,0.3);
  transition:all 0.3s ease;
  font-size:20px;
}
.floating-btn:hover{
  transform:scale(1.1);
  box-shadow:0 6px 24px rgba(220,53,69,0.4);
}

/* Responsive */
@media (max-width: 768px){
  .quick-buttons{
    grid-template-columns:repeat(3, 1fr);
  }
}

@media (max-width: 480px){
  .container{
    padding:16px;
  }
  .header{
    padding:16px;
    border-radius:0 0 20px 20px;
  }
  .header-avatar{
    width:44px;
    height:44px;
    font-size:18px;
  }
  .header-info h3{
    font-size:14px;
  }
  .header-info p{
    font-size:11px;
  }
  .quick-buttons{
    gap:8px;
  }
  .quick-btn{
    padding:16px 10px;
    min-height:90px;
  }
  .quick-btn-icon{
    width:40px;
    height:40px;
    font-size:20px;
  }
  .quick-btn span{
    font-size:11px;
  }
}
</style>
</head>
<body>

<!-- Header -->
<div class="header">
  <div class="header-content">
    <a href="profil.php" style="text-decoration:none;">
      <div class="header-avatar">
        <i class="fas fa-user"></i>
      </div>
    </a>
    <div class="header-info">
      <h3>Fazlur Riofauzan</h3>
      <p>Guru Mapel • Senin, 24 Nov 2025</p>
    </div>
  </div>
</div>

<!-- Container -->
<div class="container">

  <!-- Stats Cards -->
  <div class="stats-section">
    <div class="stat-card">
      <div class="stat-icon">
        <i class="fas fa-user-check"></i>
      </div>
      <div class="stat-content">
        <h4>Hadir Hari Ini</h4>
        <p>27/30 Siswa</p>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">
        <i class="fas fa-calendar-alt"></i>
      </div>
      <div class="stat-content">
        <h4>Jadwal Berikutnya</h4>
        <p>Sejarah</p>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">
        <i class="fas fa-door-open"></i>
      </div>
      <div class="stat-content">
        <h4>Total Kelas Diampu</h4>
        <p>5 Kelas</p>
      </div>
    </div>
  </div>

  <!-- Urgent Info -->
  <div class="urgent-info">
    <h3>URGENT INFO</h3>
    <p>Rapat guru pukul 13.30 harap bersiap</p>
  </div>

  <!-- Quick Access -->
  <div class="quick-access">
    <h3>Quick Access</h3>
    <div class="quick-buttons">
      <a href="jadwal.php" class="quick-btn green">
        <div class="quick-btn-icon">
          <i class="fas fa-gift"></i>
        </div>
        <span>Jadwal</span>
      </a>
      <a href="notifikasi.php" class="quick-btn orange">
        <div class="quick-btn-icon">
          <i class="fas fa-id-card"></i>
        </div>
        <span>Notifikasi</span>
      </a>
      <a href="absensi.php" class="quick-btn blue">
        <div class="quick-btn-icon">
          <i class="fas fa-gift"></i>
        </div>
        <span>Absensi</span>
      </a>
    </div>
  </div>

  <!-- Informasi Section -->
  <div class="info-section">
    <h3>Presentase Kehadiran Bulan Oktober</h3>
    <div class="empty-state">
      <i class="fas fa-chart-bar"></i>
      <p>Grafik kehadiran akan ditampilkan di sini</p>
    </div>
  </div>

</div>

<!-- Floating Logout -->
<div class="floating-logout">
  <a class="floating-btn" href="logout.php" title="Logout">
    <i class="fas fa-sign-out-alt"></i>
  </a>
</div>

</body>
</html>
