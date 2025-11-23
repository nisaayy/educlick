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
            :root {
                --orange: #FF9D4D;
                --orange-dark: #f68c2e;
                --green: #7FB069;
                --green-dark: #4d6651;
                --green-light: #A8D5BA;
                --bg: #F5F5F5;
                --white: #FFFFFF;
                --text-dark: #2D3748;
                --text-light: #718096;
                --shadow: 0 2px 8px rgba(0,0,0,0.08);
                --shadow-lg: 0 4px 16px rgba(0,0,0,0.12);
            }

            body {
                background: linear-gradient(135deg, var(--orange) 0%, var(--orange-dark) 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: 'Poppins', sans-serif;
            }
            
            .logout-card {
                border: none;
                border-radius: 20px;
                box-shadow: var(--shadow-lg);
                background: var(--white);
                overflow: hidden;
            }
            
            .logout-header {
                background: linear-gradient(135deg, var(--orange) 0%, var(--orange-dark) 100%);
                color: white;
                padding: 2rem;
                text-align: center;
            }
            
            .logout-icon {
                font-size: 4rem;
                margin-bottom: 1rem;
                opacity: 0.9;
            }
            
            .logout-body {
                padding: 2.5rem;
                text-align: center;
            }
            
            .btn-logout {
                background: linear-gradient(135deg, var(--orange) 0%, var(--orange-dark) 100%);
                border: none;
                color: white;
                padding: 12px 30px;
                border-radius: 12px;
                font-weight: 600;
                transition: all 0.3s ease;
                box-shadow: var(--shadow);
            }
            
            .btn-logout:hover {
                transform: translateY(-2px);
                box-shadow: var(--shadow-lg);
                color: white;
            }
            
            .btn-cancel {
                background: var(--bg);
                border: 2px solid var(--text-light);
                color: var(--text-dark);
                padding: 12px 30px;
                border-radius: 12px;
                font-weight: 600;
                transition: all 0.3s ease;
            }
            
            .btn-cancel:hover {
                background: var(--text-light);
                color: var(--white);
                border-color: var(--text-light);
            }
            
            .user-info {
                background: var(--bg);
                border-radius: 12px;
                padding: 1rem;
                margin-top: 1.5rem;
                border-left: 4px solid var(--green);
            }
            
            .user-info i {
                color: var(--green-dark);
                margin-right: 8px;
            }
            
            .card-title {
                color: var(--text-dark);
                font-weight: 700;
                margin-bottom: 1rem;
            }
            
            .text-muted {
                color: var(--text-light) !important;
            }
            
            .btn-group {
                display: flex;
                gap: 1rem;
                justify-content: center;
                flex-wrap: wrap;
            }
            
            @media (max-width: 576px) {
                .logout-body {
                    padding: 2rem 1.5rem;
                }
                
                .btn-group {
                    flex-direction: column;
                }
                
                .btn-logout, .btn-cancel {
                    width: 100%;
                }
            }
        </style>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    </head>
    <body>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card logout-card">
                        <div class="logout-header">
                            <div class="logout-icon">
                                <i class="fas fa-sign-out-alt"></i>
                            </div>
                            <h3 class="mb-0">Konfirmasi Logout</h3>
                        </div>
                        
                        <div class="logout-body">
                            <p class="text-muted mb-4">Apakah Anda yakin ingin keluar dari sistem?</p>
                            
                            <div class="btn-group">
                                <a href="?confirm=true" class="btn btn-logout">
                                    <i class="fas fa-check me-2"></i>Ya, Logout
                                </a>
                                <a href="javascript:history.back()" class="btn btn-cancel">
                                    <i class="fas fa-times me-2"></i>Batal
                                </a>
                            </div>
                            
                            <div class="user-info">
                                <small>
                                    <i class="fas fa-user"></i>
                                    <strong><?= htmlspecialchars($_SESSION['nama'] ?? 'Pengguna') ?></strong> 
                                    | 
                                    <i class="fas fa-shield-alt"></i>
                                    <?= ucfirst($_SESSION['role'] ?? 'User') ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Tambahkan efek smooth untuk tombol
            document.addEventListener('DOMContentLoaded', function() {
                const buttons = document.querySelectorAll('.btn-logout, .btn-cancel');
                buttons.forEach(button => {
                    button.addEventListener('mouseenter', function() {
                        this.style.transform = 'translateY(-2px)';
                    });
                    button.addEventListener('mouseleave', function() {
                        this.style.transform = 'translateY(0)';
                    });
                });
            });
        </script>
    </body>
    </html>
    <?php
}
?>
