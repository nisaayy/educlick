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
                --shadow: 0 4px 12px rgba(0,0,0,0.1);
                --shadow-lg: 0 8px 24px rgba(0,0,0,0.15);
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                background: linear-gradient(135deg, var(--green-light) 0%, #95d5b2 50%, var(--green) 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: 'Poppins', sans-serif;
                padding: 20px;
                position: relative;
                overflow: hidden;
            }

            /* Animated background pattern */
            body::before {
                content: '';
                position: absolute;
                top: -50%;
                left: -50%;
                width: 200%;
                height: 200%;
                background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
                background-size: 50px 50px;
                animation: float 20s linear infinite;
                z-index: 0;
            }

            @keyframes float {
                0% { transform: translate(0, 0); }
                100% { transform: translate(50px, 50px); }
            }

            /* Floating decorative elements */
            .decoration {
                position: absolute;
                border-radius: 50%;
                opacity: 0.15;
                z-index: 0;
            }

            .decoration:nth-child(1) {
                width: 300px;
                height: 300px;
                background: var(--orange);
                top: -100px;
                right: -100px;
                animation: float-1 15s ease-in-out infinite;
            }

            .decoration:nth-child(2) {
                width: 200px;
                height: 200px;
                background: var(--green-dark);
                bottom: -50px;
                left: -50px;
                animation: float-2 12s ease-in-out infinite;
            }

            .decoration:nth-child(3) {
                width: 150px;
                height: 150px;
                background: var(--orange-dark);
                top: 50%;
                right: 10%;
                animation: float-3 18s ease-in-out infinite;
            }

            @keyframes float-1 {
                0%, 100% { transform: translate(0, 0) rotate(0deg); }
                50% { transform: translate(30px, 30px) rotate(180deg); }
            }

            @keyframes float-2 {
                0%, 100% { transform: translate(0, 0) rotate(0deg); }
                50% { transform: translate(-20px, -20px) rotate(-180deg); }
            }

            @keyframes float-3 {
                0%, 100% { transform: translate(0, 0) scale(1); }
                50% { transform: translate(20px, -20px) scale(1.1); }
            }

            .container {
                position: relative;
                z-index: 1;
            }
            
            .logout-card {
                border: none;
                border-radius: 24px;
                box-shadow: var(--shadow-lg);
                background: linear-gradient(135deg, var(--orange) 0%, var(--orange-dark) 100%);
                overflow: hidden;
                position: relative;
                animation: slideIn 0.5s ease-out;
            }

            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(-30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            /* Decorative pattern on card */
            .logout-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 200px;
                background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
                z-index: 0;
                opacity: 0.5;
            }
            
            .logout-header {
                background: transparent;
                color: white;
                padding: 2rem;
                text-align: center;
                position: relative;
                z-index: 1;
            }
            
            .logout-icon {
                font-size: 4rem;
                margin-bottom: 1rem;
                opacity: 0.95;
                display: inline-block;
                animation: pulse 2s ease-in-out infinite;
            }

            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
            
            .logout-body {
                padding: 2rem;
                text-align: center;
                background: var(--white);
                border-radius: 24px 24px 0 0;
                margin-top: -10px;
                position: relative;
                z-index: 1;
            }

            .logout-body h4 {
                color: var(--text-dark);
                font-weight: 700;
                margin-bottom: 0.5rem;
                font-size: 1.3rem;
            }

            .logout-body p {
                color: var(--text-light);
                font-size: 0.95rem;
                margin-bottom: 1.5rem;
            }
            
            .btn-logout {
                background: linear-gradient(135deg, var(--orange) 0%, var(--orange-dark) 100%);
                border: none;
                color: white;
                padding: 12px 30px;
                border-radius: 12px;
                font-weight: 600;
                transition: all 0.3s ease;
                box-shadow: 0 4px 12px rgba(255, 157, 77, 0.3);
                font-size: 0.95rem;
            }
            
            .btn-logout:hover {
                transform: translateY(-3px);
                box-shadow: 0 6px 20px rgba(255, 157, 77, 0.4);
                color: white;
            }

            .btn-logout:active {
                transform: translateY(-1px);
            }
            
            .btn-cancel {
                background: var(--white);
                border: 2px solid var(--green);
                color: var(--green-dark);
                padding: 12px 30px;
                border-radius: 12px;
                font-weight: 600;
                transition: all 0.3s ease;
                font-size: 0.95rem;
            }
            
            .btn-cancel:hover {
                background: var(--green);
                color: var(--white);
                border-color: var(--green);
                transform: translateY(-3px);
                box-shadow: 0 6px 20px rgba(127, 176, 105, 0.3);
            }

            .btn-cancel:active {
                transform: translateY(-1px);
            }
            
            .user-info {
                background: linear-gradient(135deg, var(--green-light) 0%, rgba(168, 213, 186, 0.6) 100%);
                border-radius: 14px;
                padding: 1.2rem;
                margin-top: 2rem;
                border-left: 5px solid var(--green);
                box-shadow: 0 2px 8px rgba(127, 176, 105, 0.2);
            }
            
            .user-info i {
                color: var(--green-dark);
                margin-right: 8px;
            }

            .user-info strong {
                color: var(--green-dark);
            }

            .user-info small {
                color: var(--text-dark);
                font-size: 0.9rem;
            }
            
            .btn-group {
                display: flex;
                gap: 1rem;
                justify-content: center;
                flex-wrap: wrap;
            }

            .warning-text {
                background: rgba(255, 157, 77, 0.1);
                border-left: 4px solid var(--orange);
                padding: 1rem;
                border-radius: 8px;
                margin-bottom: 1.5rem;
                text-align: left;
            }

            .warning-text i {
                color: var(--orange);
                margin-right: 8px;
            }
            
            @media (max-width: 576px) {
                .logout-header {
                    padding: 2.5rem 1.5rem 1.5rem;
                }

                .logout-icon {
                    font-size: 4rem;
                }

                .logout-body {
                    padding: 2rem 1.5rem;
                }

                .logout-body h4 {
                    font-size: 1.3rem;
                }
                
                .btn-group {
                    flex-direction: column;
                }
                
                .btn-logout, .btn-cancel {
                    width: 100%;
                }

                .decoration {
                    display: none;
                }
            }
        </style>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    </head>
    <body>
        <div class="decoration"></div>
        <div class="decoration"></div>
        <div class="decoration"></div>

        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card logout-card">
                        <div class="logout-header">
                            <div class="logout-icon">
                                <i class="fas fa-sign-out-alt"></i>
                            </div>
                            <h3 class="mb-0 fw-bold">Konfirmasi Logout</h3>
                        </div>
                        
                        <div class="logout-body">
                            <h4>Keluar dari Sistem?</h4>
                            <p class="text-muted">Anda akan keluar dari sesi saat ini dan perlu login kembali untuk mengakses sistem.</p>
                            
                            <div class="warning-text">
                                <i class="fas fa-info-circle"></i>
                                <small><strong>Perhatian:</strong> Pastikan Anda telah menyimpan semua pekerjaan sebelum keluar.</small>
                            </div>

                            <div class="btn-group">
                                <a href="?confirm=true" class="btn btn-logout">
                                    <i class="fas fa-check-circle me-2"></i>Ya, Logout Sekarang
                                </a>
                                <a href="javascript:history.back()" class="btn btn-cancel">
                                    <i class="fas fa-arrow-left me-2"></i>Kembali
                                </a>
                            </div>
                            
                            <div class="user-info">
                                <small>
                                    <i class="fas fa-user-circle"></i>
                                    <strong><?= htmlspecialchars($_SESSION['nama'] ?? 'Pengguna') ?></strong> 
                                    <span class="mx-2">•</span>
                                    <i class="fas fa-shield-alt"></i>
                                    <strong><?= ucfirst($_SESSION['role'] ?? 'User') ?></strong>
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
                        this.style.transform = 'translateY(-3px)';
                    });
                    button.addEventListener('mouseleave', function() {
                        this.style.transform = 'translateY(0)';
                    });
                });

                // Add ripple effect on click
                buttons.forEach(button => {
                    button.addEventListener('click', function(e) {
                        let ripple = document.createElement('span');
                        ripple.classList.add('ripple');
                        this.appendChild(ripple);
                        
                        let x = e.clientX - e.target.offsetLeft;
                        let y = e.clientY - e.target.offsetTop;
                        
                        ripple.style.left = x + 'px';
                        ripple.style.top = y + 'px';
                        
                        setTimeout(() => {
                            ripple.remove();
                        }, 600);
                    });
                });
            });
        </script>
    </body>
    </html>
    <?php
}
?>
