<?php
// register.php
require_once 'config/database.php';
require_once 'config/session.php';

Session::start();

// If already logged in, redirect
if (Session::isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get all kariah for dropdown
$kariah_query = "SELECT * FROM kariah ORDER BY nama_kariah";
$kariah_stmt = $db->prepare($kariah_query);
$kariah_stmt->execute();
$kariah_list = $kariah_stmt->fetchAll(PDO::FETCH_ASSOC);

$success = '';
$error = '';

// Process registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'] ?? '';
    $no_kp = $_POST['no_kp'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefon = $_POST['telefon'] ?? '';
    $kariah_id = $_POST['kariah_id'] ?? '';
    $jumlah_keluarga = $_POST['jumlah_keluarga'] ?? 1;
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if ($password !== $confirm_password) {
        $error = "Kata laluan tidak sepadan";
    } elseif (strlen($password) < 6) {
        $error = "Kata laluan minimum 6 aksara";
    } else {
        // Check if email or no_kp already exists
        $check_query = "SELECT id FROM users WHERE email = :email OR no_kp = :no_kp";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':email', $email);
        $check_stmt->bindParam(':no_kp', $no_kp);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $error = "Email atau No. KP sudah didaftarkan";
        } else {
            // Generate no_ahli
            $year = date('y');
            $prefix = "A" . $year;
            
            // Get last number
            $last_query = "SELECT no_ahli FROM users WHERE no_ahli LIKE '$prefix%' ORDER BY id DESC LIMIT 1";
            $last_stmt = $db->prepare($last_query);
            $last_stmt->execute();
            
            if ($last_stmt->rowCount() > 0) {
                $last = $last_stmt->fetch(PDO::FETCH_ASSOC);
                $last_num = intval(substr($last['no_ahli'], 3));
                $new_num = str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $new_num = '0001';
            }
            
            $no_ahli = $prefix . $new_num;
            
            // Hash password (in production)
            // $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user (using plain password for demo - USE HASHING IN PRODUCTION)
            $insert_query = "INSERT INTO users (no_ahli, nama, no_kp, email, telefon, kariah_id, jumlah_ahli_keluarga, password) 
                            VALUES (:no_ahli, :nama, :no_kp, :email, :telefon, :kariah_id, :jumlah_keluarga, :password)";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(':no_ahli', $no_ahli);
            $insert_stmt->bindParam(':nama', $nama);
            $insert_stmt->bindParam(':no_kp', $no_kp);
            $insert_stmt->bindParam(':email', $email);
            $insert_stmt->bindParam(':telefon', $telefon);
            $insert_stmt->bindParam(':kariah_id', $kariah_id);
            $insert_stmt->bindParam(':jumlah_keluarga', $jumlah_keluarga);
            $insert_stmt->bindParam(':password', $password); // Use $hashed_password in production
            
            if ($insert_stmt->execute()) {
                $success = "Pendaftaran berjaya! No. Ahli anda: $no_ahli";
            } else {
                $error = "Pendaftaran gagal. Sila cuba lagi.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Ahli - Khairat Kariah</title>
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
            max-width: 600px;
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

        .form-group input, .form-group select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #e0e7ff;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus, .form-group select:focus {
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

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border: 1px solid #f5c6cb;
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .card {
                padding: 1.5rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>📝 Pendaftaran Ahli Baru</h1>
            <p>Sila isi maklumat untuk mendaftar sebagai ahli khairat</p>
        </div>
        
        <?php if ($success): ?>
            <div class="success-message">
                <?php echo $success; ?><br>
                <a href="login.php?role=user" class="link">Log masuk sekarang</a>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>Nama Penuh</label>
                <input type="text" name="nama" required placeholder="cth: Ahmad bin Ali">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>No. Kad Pengenalan</label>
                    <input type="text" name="no_kp" required placeholder="000101-10-1234">
                </div>
                
                <div class="form-group">
                    <label>No. Telefon</label>
                    <input type="tel" name="telefon" required placeholder="012-345 6789">
                </div>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="ahmad@email.com">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Kariah</label>
                    <select name="kariah_id" required>
                        <option value="">Pilih Kariah</option>
                        <?php foreach ($kariah_list as $kariah): ?>
                        <option value="<?php echo $kariah['id']; ?>">
                            <?php echo $kariah['nama_kariah']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Jumlah Ahli Keluarga</label>
                    <input type="number" name="jumlah_keluarga" min="1" value="1" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Kata Laluan</label>
                    <input type="password" name="password" required placeholder="minimum 6 aksara" minlength="6">
                </div>
                
                <div class="form-group">
                    <label>Pengesahan Kata Laluan</label>
                    <input type="password" name="confirm_password" required placeholder="taip semula">
                </div>
            </div>
            
            <button type="submit" class="btn">Daftar</button>
            
            <a href="index.php" class="btn btn-secondary" style="display: block; text-align: center; text-decoration: none;">Kembali</a>
        </form>
        
        <div style="text-align: center; margin-top: 1.5rem;">
            <p>Sudah ada akaun? <a href="login.php?role=user" class="link">Log masuk</a></p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>