<?php
// login.php
require_once 'config/database.php';
require_once 'config/session.php';

Session::start();

// If already logged in, redirect
if (Session::isLoggedIn()) {
    $userType = Session::getUserType();
    if ($userType === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit();
}

$error = '';
$role = $_GET['role'] ?? 'user';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $identifier = $_POST['identifier'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    
    if ($role === 'admin') {
        // Admin login
        $query = "SELECT * FROM admins WHERE username = :username OR email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $identifier);
        $stmt->bindParam(':email', $identifier);
        $stmt->execute();
        
        if ($admin = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Verify password (you should use password_hash() in production)
            if ($password === 'admin123') { // Temporary - use password_verify() in production
                Session::set('admin_id', $admin['id']);
                Session::set('admin_name', $admin['nama']);
                Session::set('admin_username', $admin['username']);
                header("Location: admin/dashboard.php");
                exit();
            } else {
                $error = "Kata laluan salah";
            }
        } else {
            $error = "Admin tidak dijumpai";
        }
    } else {
        // User login
        $query = "SELECT u.*, k.nama_kariah, k.nombor_kecemasan 
                  FROM users u 
                  LEFT JOIN kariah k ON u.kariah_id = k.id 
                  WHERE u.no_ahli = :identifier OR u.email = :identifier OR u.no_kp = :identifier";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':identifier', $identifier);
        $stmt->execute();
        
        if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Verify password (you should use password_hash() in production)
            if ($password === 'user123') { // Temporary - use password_verify() in production
                Session::set('user_id', $user['id']);
                Session::set('user_no_ahli', $user['no_ahli']);
                Session::set('user_name', $user['nama']);
                Session::set('user_kariah', $user['nama_kariah']);
                Session::set('user_kecemasan', $user['nombor_kecemasan']);
                header("Location: user/dashboard.php");
                exit();
            } else {
                $error = "Kata laluan salah";
            }
        } else {
            $error = "Ahli tidak dijumpai";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Masuk - Khairat Kariah</title>
    <style>
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
            max-width: 500px;
            width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #e0e7ff;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
            margin-top: 1rem;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }

        .error-message {
            background: #fee;
            color: #c00;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border: 1px solid #fcc;
        }

        .link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .link:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .info-box {
            background: #e8f4fd;
            border: 1px solid #b8e0ff;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: #0369a1;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1><?php echo $role === 'admin' ? '🔐 Log Masuk Admin' : '🔐 Log Masuk Ahli'; ?></h1>
            <p><?php echo $role === 'admin' ? 'Akses khas untuk pentadbir sistem' : 'Sila masukkan email atau no. ahli anda'; ?></p>
        </div>
        
        <!-- Demo credentials info -->
        <div class="info-box">
            <strong>📝 Demo Credentials:</strong><br>
            <?php if ($role === 'admin'): ?>
                Admin: username "admin" / password "admin123"
            <?php else: ?>
                Ahli: no. ahli "A001284" / password "user123"
            <?php endif; ?>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="role" value="<?php echo $role; ?>">
            
            <div class="form-group">
                <label><?php echo $role === 'admin' ? 'Username / Email' : 'No. Ahli / Email / No. KP'; ?></label>
                <input type="text" name="identifier" required placeholder="<?php echo $role === 'admin' ? 'masukkan username' : 'cth: A001284'; ?>">
            </div>
            
            <div class="form-group">
                <label>Kata Laluan</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            
            <div style="text-align: right; margin-bottom: 1rem;">
                <a href="#" class="link">Lupa kata laluan?</a>
            </div>
            
            <button type="submit" class="btn">Log Masuk</button>
            
            <a href="index.php" class="btn btn-secondary" style="display: block; text-align: center; text-decoration: none;">Kembali</a>
        </form>
        
        <?php if ($role !== 'admin'): ?>
        <div style="text-align: center; margin-top: 1.5rem;">
            <p>Tiada akaun? <a href="register.php" class="link">Daftar sekarang</a></p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>