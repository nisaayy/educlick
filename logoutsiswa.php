<?php
// logout.php
session_start();

// Cek jika logout dengan konfirmasi
if (isset($_GET['confirm']) && $_GET['confirm'] == 'true') {
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

    // Redirect ke halaman login
    header("Location: ../index.php");
    exit;
} else {
    // Tampilkan konfirmasi logout
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Konfirmasi Logout - SDIT</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .logout-card {
                border: none;
                border-radius: 20px;
                box-shadow: 0 15px 35px rgba(0,0,0,0.1);
                backdrop-filter: blur(10px);
                background: rgba(255,255,255,0.95);
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card logout-card">
                        <div class="card-body text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-sign-out-alt fa-4x text-warning mb-3"></i>
                                <h3 class="card-title">Konfirmasi Logout</h3>
                                <p class="text-muted">Apakah Anda yakin ingin keluar dari sistem?</p>
                            </div>
                            
                            <div class="d-flex justify-content-center gap-3">
                                <a href="?confirm=true" class="btn btn-danger btn-lg px-4">
                                    <i class="fas fa-check me-2"></i>Ya, Logout
                                </a>
                                <a href="dashboard_admin.php" class="btn btn-secondary btn-lg px-4">
                                    <i class="fas fa-times me-2"></i>Batal
                                </a>
                            </div>
                            
                            <div class="mt-4">
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i>
                                    <?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?> 
                                    | 
                                    <i class="fas fa-shield-alt me-1"></i>
                                    <?= ucfirst($_SESSION['role'] ?? 'Admin') ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}
?>
