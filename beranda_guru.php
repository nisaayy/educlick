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
// **TIDAK MENGUBAH LOGIKA QUERY** — sama seperti sebelumnya (LIMIT 3)
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
  --accent1: #f68c2e;
  --accent2: #4d6651;
  --bg1: linear-gradient(135deg,#d9ebd0 0%,#c1dec8 100%);
  --card-shadow: 0 4px 16px rgba(0,0,0,0.08);
  --rounded: 14px;
  --gap: 18px;
  --max-width: 1200px;
}

/* Reset + base */
*{box-sizing:border-box}
html,body{height:100%}
body{
  font-family:'Poppins',sans-serif;
  background:var(--bg1);
  margin:0;
  padding:28px;
  color:#21321f;
  -webkit-font-smoothing:antialiased;
  -moz-osx-font-smoothing:grayscale;
}
.container{ max-width:var(--max-width); margin:0 auto; }

/* Header */
.header{
  display:flex;
  gap:16px;
  align-items:center;
  padding:22px;
  border-radius:20px;
  background: linear-gradient(135deg,var(--accent1) 0%, #ff9d4d 100%);
  color:#fff;
  box-shadow: 0 10px 40px rgba(246,140,46,0.2);
  margin-bottom:var(--gap);
}
.header-avatar{ width:72px;height:72px;border-radius:50%;overflow:hidden;flex-shrink:0;background:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--accent1) }
.header-info h3{ margin:0;font-size:20px }
.header-info p{ margin:2px 0 0;font-size:13px;opacity:0.95 }

/* Grid utama untuk desktop */
.main-grid{
  display:grid;
  grid-template-columns: 1fr 380px; /* konten kiri + kolom kanan */
  gap:var(--gap);
  align-items:start;
  margin-bottom:20px;
}

/* Kiri: konten utama (statistik + info + quick access) */
.left-column{
  display:flex;
  flex-direction:column;
  gap:var(--gap);
}

/* Stats grid */
.stats-grid{
  display:grid;
  grid-template-columns: repeat(3,1fr);
  gap:12px;
}
.stat-card{
  background:#fff;border-radius:16px;padding:18px;box-shadow:var(--card-shadow);display:flex;gap:12px;align-items:center;border-left:4px solid var(--accent2);
}
.stat-icon{ width:48px;height:48px;border-radius:10px;background:#f1f7ef;display:flex;align-items:center;justify-content:center;flex-shrink:0 }
.stat-content h4{ margin:0;font-size:13px;color:#6b8e70;font-weight:600 }
.stat-content p{ margin:6px 0 0;font-size:18px;font-weight:700;color:#21321f }

/* Informasi (ringkas) */
.informasi-card{
  background:#fff;border-radius:16px;padding:16px;box-shadow:var(--card-shadow);
}
.informasi-card h4{ margin:0 0 10px;font-size:16px;color:#21321f;display:flex;align-items:center;gap:8px;font-weight:700 }
.informasi-list{ display:flex;flex-direction:column;gap:10px }
.informasi-item{
  background:#f8f9fa;border-radius:10px;padding:12px;border-left:3px solid var(--accent1);text-decoration:none;color:inherit;display:block;
}
.informasi-item:hover{ background:#f2f4f3; transform:translateY(-2px); transition:all .18s ease }
.informasi-judul{ font-size:13px;font-weight:700;color:#21321f;display:flex;justify-content:space-between;align-items:center;gap:8px }
.informasi-isi{
  font-size:13px;color:#5f8766;line-height:1.3;margin-top:6px;
  overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;
}
.informasi-meta{ font-size:11px;color:#9aa99a;margin-top:6px }

/* footer link lihat semua */
.informasi-footer{ text-align:right;margin-top:8px }
.informasi-footer a{ text-decoration:none;color:var(--accent1);font-weight:700;font-size:13px }

/* Quick Access (di bawah informasi) */
.quick-access{
  background:#fff;border-radius:16px;padding:14px;box-shadow:var(--card-shadow);
}
.quick-access h4{ margin:0 0 12px;font-size:15px;color:#21321f;font-weight:700;display:flex;gap:8px;align-items:center }
.quick-buttons{ display:grid;grid-template-columns:repeat(3,1fr);gap:10px }
.quick-btn{ display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;height:72px;border-radius:10px;color:#fff;text-decoration:none;font-weight:700;box-shadow:0 2px 8px rgba(0,0,0,0.08) }
.btn-orange{ background: linear-gradient(135deg,#f68c2e 0%,#ff9d4d 100%); }
.btn-gray{ background: linear-gradient(135deg,#a0b5a0 0%,#b5c9b5 100%); }
.btn-green{ background: linear-gradient(135deg,#4d6651 0%,#5a7a5e 100%); }
.quick-btn span{ font-size:12px }

/* Kanan: status absensi + jadwal singkat (opsional) */
.right-column{ display:flex;flex-direction:column;gap:12px }
.status-absensi{
  border-radius:16px;padding:18px;color:#fff;box-shadow:var(--card-shadow);
}
.status-open{ background: linear-gradient(135deg,#28a745 0%,#34ce57 100%); box-shadow:0 6px 20px rgba(40,167,69,0.12) }
.status-closed{ background: linear-gradient(135deg,#6c757d 0%,#868e96 100%); box-shadow:0 6px 20px rgba(108,117,125,0.08) }
.status-absensi h4{ margin:0 0 6px;font-size:14px;font-weight:800 }
.status-absensi p{ margin:0;font-size:13px;opacity:0.95 }

/* Floating logout */
.floating-logout{ position:fixed;right:26px;bottom:26px;z-index:999 }
.floating-btn{ width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#dc3545 0%,#c82333 100%);color:#fff;text-decoration:none;box-shadow:0 8px 30px rgba(220,53,69,0.18) }

/* Responsive: untuk layar sempit, buat stack */
@media (max-width: 980px){
  .main-grid{ grid-template-columns: 1fr; }
  .stats-grid{ grid-template-columns: repeat(2,1fr); }
}
@media (max-width: 600px){
  body{ padding:14px; }
  .stats-grid{ grid-template-columns: 1fr; }
  .quick-buttons{ grid-template-columns: repeat(2,1fr) }
  .header{ padding:16px }
  .header-avatar{ width:56px;height:56px }
  .header-info h3{ font-size:16px }
}
</style>
</head>
<body>
<div class="container">

  <!-- header -->
  <?php
  $stmt = $conn->prepare("SELECT foto_profil FROM guru_foto_profil WHERE guru_id = ? ORDER BY created_at DESC LIMIT 1");
  $stmt->bind_param("i", $guru_id);
  $stmt->execute();
  $foto_result = $stmt->get_result();
  $foto_data = $foto_result->fetch_assoc();
  $foto_profil = $foto_data['foto_profil'] ?? null;
  $stmt->close();
  ?>
  <div class="header">
    <a href="profil.php" style="text-decoration:none;">
      <div class="header-avatar">
        <?php if ($foto_profil): ?>
          <img src="../uploads/profil/<?= htmlspecialchars($foto_profil) ?>?t=<?= time() ?>" alt="Foto profil" style="width:100%;height:100%;object-fit:cover">
        <?php else: ?>
          <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:22px;">
            <?= strtoupper(htmlspecialchars(substr($guru_nama,0,1))) ?>
          </div>
        <?php endif; ?>
      </div>
    </a>
    <div class="header-info">
      <h3><?= htmlspecialchars($guru_nama) ?></h3>
      <p><?= ucfirst($_SESSION['role']) ?> • <?= $hari_ini ?>, <?= date('d M Y') ?></p>
    </div>
  </div>

  <div class="main-grid">
    <!-- KIRI -->
    <div class="left-column">

      <!-- stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-user-check"></i></div>
          <div class="stat-content">
            <h4>Hadir Hari Ini</h4>
            <p><?= htmlspecialchars($hadir_hari_ini) ?></p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
          <div class="stat-content">
            <h4>Jadwal Berikutnya</h4>
            <p><?= htmlspecialchars($jadwal_berikutnya_text) ?></p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-door-open"></i></div>
          <div class="stat-content">
            <h4>Total Kelas Diampu</h4>
            <p><?= htmlspecialchars($total_kelas) ?> Kelas</p>
          </div>
        </div>
      </div>

      <!-- Informasi Terbaru (ringkas, klik ke informasi.php) -->
      <div class="informasi-card">
        <h4><i class="fas fa-info-circle" style="color:var(--accent1)"></i> Informasi Terbaru</h4>
        <div class="informasi-list">
          <?php if (count($informasi_list) > 0): ?>
            <?php foreach ($informasi_list as $info): ?>
              <?php
                // safe escape for link, id optional (jika ada kolom id)
                $info_id = isset($info['id']) ? urlencode($info['id']) : '';
                $href = $info_id !== '' ? "informasi.php?id={$info_id}" : "informasi.php";
              ?>
              <a class="informasi-item" href="<?= $href ?>">
                <div class="informasi-judul">
                  <span><?= htmlspecialchars($info['judul']) ?></span>
                  <span class="informasi-badge" style="background:var(--accent2);color:#fff;padding:4px 6px;border-radius:6px;font-size:11px"><?= strtoupper(htmlspecialchars($info['ditujukan'])) ?></span>
                </div>
                <div class="informasi-isi"><?= nl2br(htmlspecialchars($info['isi'])) ?></div>
                <div class="informasi-meta"><i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($info['tanggal'])) ?></div>
              </a>
            <?php endforeach; ?>
            <div class="informasi-footer"><a href="informasi.php">Lihat Semua &raquo;</a></div>
          <?php else: ?>
            <div style="text-align:center;color:#9aa99a;padding:18px;font-size:14px">
              <i class="fas fa-bell-slash" style="font-size:20px;margin-bottom:8px;display:block"></i>
              Tidak ada informasi baru
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Quick access (di bawah informasi) -->
      <div class="quick-access">
        <h4><i class="fas fa-bolt" style="color:var(--accent1)"></i> Akses Cepat</h4>
        <div class="quick-buttons">
          <a href="jadwal.php" class="quick-btn btn-orange"><i class="fas fa-calendar-alt"></i><span>Jadwal</span></a>
          <a href="notifikasi.php" class="quick-btn btn-gray"><i class="fas fa-bell"></i><span>Notifikasi</span></a>
          <a href="absensi.php" class="quick-btn btn-green"><i class="fas fa-user-check"></i><span>Absensi</span></a>
        </div>
      </div>

    </div>

    <!-- KANAN (status singkat) -->
    <div class="right-column">
      <div class="status-absensi <?= $status_absensi == 'open' ? 'status-open' : 'status-closed' ?>">
        <h4>STATUS ABSENSI: <?= strtoupper(htmlspecialchars($status_absensi)) ?></h4>
        <p><?= $status_absensi == 'open' ? 'Guru dapat menginput absensi hari ini' : 'Periode absensi telah ditutup' ?></p>
      </div>

      <!-- contoh: card kecil info tambahan (boleh diubah) -->
      <div style="background:#fff;border-radius:16px;padding:14px;box-shadow:var(--card-shadow)">
        <h4 style="margin:0 0 8px;font-size:14px">Ringkasan</h4>
        <p style="margin:0;font-size:13px;color:#5f8766">Total kelas: <strong><?= htmlspecialchars($total_kelas) ?></strong></p>
        <p style="margin:8px 0 0;font-size:13px;color:#5f8766">Jadwal hari ini: <strong><?= htmlspecialchars($total_jadwal_hari) ?></strong></p>
      </div>
    </div>
  </div> <!-- end main-grid -->

  <!-- floating logout -->
  <div class="floating-logout">
    <a class="floating-btn" href="logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
  </div>

</div>

</body>
</html>
