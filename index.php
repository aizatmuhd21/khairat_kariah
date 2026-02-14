<?php
// index.php
require_once 'config/database.php';
require_once 'config/session.php';

Session::start();

// Check if user is logged in
if (Session::isLoggedIn()) {
    $userType = Session::getUserType();
    if ($userType === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khairat Kariah - Sistem Pengurusan</title>
    <style>
        /* Copy all the CSS from previous example here */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            max-width: 800px;
            width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .role-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .role-card {
            background: linear-gradient(135deg, #f8faff 0%, #f0f3ff 100%);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .role-card:hover {
            transform: translateY(-10px);
            border-color: #667eea;
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.2);
        }

        .role-card .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
        }

        .link:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .card {
                padding: 1.5rem;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .role-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>🕌 KHAIRAT KARIAH</h1>
            <p>Sistem Pengurusan Khairat Kematian Bersepadu</p>
        </div>
        
        <div style="text-align: center; margin-bottom: 2rem;">
            <h2 style="color: #333;">Sila pilih peranan anda</h2>
        </div>
        
        <div class="role-grid">
            <a href="login.php?role=user" class="role-card">
                <div class="icon">👤</div>
                <h3>Pengguna Ahli</h3>
                <p>Untuk ahli khairat yang berdaftar</p>
                <p style="margin-top: 1rem; font-size: 0.85rem;">Semak status bayaran, lihat nombor kecemasan</p>
            </a>
            
            <a href="login.php?role=admin" class="role-card">
                <div class="icon">👑</div>
                <h3>Pentadbir</h3>
                <p>Untuk pengurus masjid/surau</p>
                <p style="margin-top: 1rem; font-size: 0.85rem;">Urus ahli, kariah, pantau bayaran</p>
            </a>
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <p>Belum daftar? <a href="register.php" class="link">Daftar di sini</a></p>
        </div>
    </div>
</body>
</html>