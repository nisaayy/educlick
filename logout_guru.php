<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Proteksi: hanya yang sudah login yang bisa logout
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['guru_mapel', 'wali_kelas'])) {
    header("Location: ../index.php");
    exit;
}

$guru_nama = $_SESSION['nama'] ?? 'Guru';

// Jika konfirmasi logout
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    // Hapus semua data session
    $_SESSION = array();

    // Hapus session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Hancurkan session
    session_destroy();

    // Redirect ke halaman login dengan pesan sukses
    header("Location: ../index.php?logout=success");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - SDIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #d9ebd0 0%, #c1dec8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .logout-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: none;
            max-width: 450px;
            width: 100%;
        }
        .logout-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #f68c2e 0%, #ff9d4d 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 32px;
        }
        .btn-logout {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }
        .btn-cancel {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logout-card p-5 text-center">
            <div class="logout-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            
            <h3 class="mb-3 text-dark">Konfirmasi Logout</h3>
            <p class="text-muted mb-4">
                Apakah Anda yakin ingin keluar dari sistem, 
                <strong><?= htmlspecialchars($guru_nama) ?></strong>?
            </p>
            
            <div class="d-flex gap-3 justify-content-center">
                <a href="dashboard_guru.php" class="btn btn-cancel">
                    <i class="fas fa-arrow-left me-2"></i>Batal
                </a>
                <a href="logout.php?confirm=yes" class="btn btn-logout">
                    <i class="fas fa-sign-out-alt me-2"></i>Ya, Logout
                </a>
            </div>
            
            <div class="mt-4">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Anda akan diarahkan ke halaman login setelah logout.
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
