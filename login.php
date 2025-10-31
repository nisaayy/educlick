<?php
session_start();
include 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $query = "SELECT * FROM users WHERE username='$username'";
    $result = mysqli_query($koneksi, $query);
    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        if (password_verify($password, $row['password'])) {
            $_SESSION['user'] = $username;
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $username;
            header('Location: index.php');
            exit;
        } else {
            $error = "Username atau password salah!";
        }
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary-green: #2d5016;
            --secondary-green: #4a7c59;
            --light-green: #8fbc8f;
            --pale-green: #f0f8f0;
            --glass-white: rgba(255, 255, 255, 0.95);
            --glass-green: rgba(45, 80, 22, 0.03);
            --shadow-soft: 0 10px 25px rgba(45, 80, 22, 0.08);
            --shadow-medium: 0 15px 35px rgba(45, 80, 22, 0.12);
            --shadow-strong: 0 20px 45px rgba(45, 80, 22, 0.15);
            --gradient-primary: linear-gradient(135deg, #2d5016 0%, #4a7c59 100%);
            --gradient-bg: linear-gradient(135deg, #f0f8f0 0%, #e8f5e8 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--gradient-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(45, 80, 22, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(74, 124, 89, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 40% 60%, rgba(143, 188, 143, 0.06) 0%, transparent 50%);
            pointer-events: none;
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 450px;
            margin: 0 auto;
            padding: 2rem;
        }

        .login-card {
            background: var(--glass-white);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            padding: 3rem 2.5rem;
            box-shadow: var(--shadow-medium);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
            border-radius: 24px 24px 0 0;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-strong);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: var(--shadow-soft);
            transition: all 0.3s ease;
        }

        .login-icon:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-medium);
        }

        .login-icon i {
            font-size: 2rem;
            color: white;
        }

        .login-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }

        .login-subtitle {
            color: var(--secondary-green);
            font-size: 0.95rem;
            font-weight: 400;
            opacity: 0.8;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            font-weight: 500;
            color: var(--primary-green);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            letter-spacing: 0.3px;
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-green);
            opacity: 0.6;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .form-control {
            border: 2px solid rgba(45, 80, 22, 0.1);
            border-radius: 12px;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }

        .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 3px rgba(45, 80, 22, 0.1);
            background: white;
            outline: none;
        }

        .form-control:focus + .input-icon {
            color: var(--primary-green);
            opacity: 1;
        }

        .btn-login {
            background: var(--gradient-primary);
            border: none;
            border-radius: 12px;
            padding: 0.875rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(45, 80, 22, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .divider {
            text-align: center;
            margin: 2rem 0 1.5rem;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(45, 80, 22, 0.2), transparent);
        }

        .register-link {
            text-align: center;
            color: var(--secondary-green);
            font-size: 0.9rem;
        }

        .register-link a {
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
        }

        .register-link a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 0;
            background: var(--primary-green);
            transition: width 0.3s ease;
        }

        .register-link a:hover::after {
            width: 100%;
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            box-shadow: var(--shadow-soft);
        }

        .alert-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
            border-left: 4px solid #d63447;
        }

        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
        }

        .floating-element {
            position: absolute;
            background: rgba(45, 80, 22, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .floating-element:nth-child(1) {
            width: 20px;
            height: 20px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            width: 15px;
            height: 15px;
            top: 60%;
            right: 20%;
            animation-delay: 2s;
        }

        .floating-element:nth-child(3) {
            width: 25px;
            height: 25px;
            top: 80%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        @media (max-width: 768px) {
            .login-container {
                padding: 1rem;
            }
            
            .login-card {
                padding: 2rem 1.5rem;
            }
            
            .login-title {
                font-size: 1.75rem;
            }
        }

        /* Loading animation */
        .btn-login.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .btn-login.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="floating-elements">
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
    </div>
    
    <div class="container">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <div class="login-icon">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h1 class="login-title">Selamat Datang</h1>
                    <p class="login-subtitle">Masuk ke akun Anda untuk melanjutkan</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="loginForm">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <div class="input-group">
                            <input type="text" name="username" class="form-control" required>
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" class="form-control" required>
                            <i class="fas fa-lock input-icon"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Masuk
                    </button>
                </form>

                <div class="divider"></div>

                <p class="register-link">
                    Belum punya akun? <a href="register.php">Daftar sekarang</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Add loading animation on form submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.querySelector('.btn-login');
            btn.classList.add('loading');
            btn.innerHTML = '<span style="opacity: 0;">Masuk...</span>';
        });

        // Add smooth focus animations
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>