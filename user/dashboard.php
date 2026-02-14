<?php
// user/dashboard.php
require_once '../config/database.php';
require_once '../config/session.php';

Session::start();

// Check if user is logged in
if (!Session::isLoggedIn() || Session::getUserType() !== 'user') {
    header("Location: ../login.php?role=user");
    exit();
}

$user_id = Session::get('user_id');
$user_name = Session::get('user_name');
$user_no_ahli = Session::get('user_no_ahli');

$database = new Database();
$db = $database->getConnection();

// Get user details with kariah info
$query = "SELECT u.*, k.nama_kariah, k.lokasi, k.nombor_kecemasan 
          FROM users u 
          LEFT JOIN kariah k ON u.kariah_id = k.id 
          WHERE u.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get payment history
$payment_query = "SELECT * FROM payments WHERE user_id = :user_id ORDER BY tahun DESC";
$payment_stmt = $db->prepare($payment_query);
$payment_stmt->bindParam(':user_id', $user_id);
$payment_stmt->execute();
$payments = $payment_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate stats
$total_paid = 0;
$total_unpaid = 0;
$last_payment = null;

foreach ($payments as $p) {
    if ($p['status'] === 'lunas') {
        $total_paid++;
        if (!$last_payment || $p['tahun'] > $last_payment['tahun']) {
            $last_payment = $p;
        }
    } else {
        $total_unpaid++;
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Ahli - Khairat Kariah</title>
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
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .nav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .nav-logo {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-menu {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-item {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #333;
        }

        .nav-item:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 45px rgba(102, 126, 234, 0.2);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #f8faff 0%, #f0f3ff 100%);
            border-radius: 15px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.2);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .payment-status {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid #eef2f6;
            transition: all 0.3s ease;
            border-radius: 10px;
        }

        .payment-status:hover {
            background: linear-gradient(135deg, #f0f4ff 0%, #f5f0ff 100%);
            padding-left: 1.5rem;
        }

        .status-badge {
            padding: 0.4rem 1.2rem;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-lunas {
            background: linear-gradient(135deg, #a8e6cf 0%, #d4edda 100%);
            color: #155724;
        }

        .status-belum {
            background: linear-gradient(135deg, #ffd3b5 0%, #ffb3b3 100%);
            color: #721c24;
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
            display: inline-block;
            text-decoration: none;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
        }

        .emergency-contact {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8faff;
            border-radius: 10px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .emergency-contact:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateX(5px);
        }

        .emergency-contact:hover .contact-label {
            color: white;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .info-item {
            padding: 1rem;
            background: #f8faff;
            border-radius: 10px;
        }

        @media (max-width: 768px) {
            .nav-bar {
                flex-direction: column;
                gap: 1rem;
                border-radius: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navigation -->
        <div class="nav-bar">
            <div class="nav-logo">🕌 Khairat Kariah</div>
            <div class="nav-menu">
                <span class="nav-item">📊 Dashboard</span>
                <span class="nav-item">👤 Profil</span>
                <span class="nav-item">💰 Bayaran</span>
                <a href="../logout.php" class="nav-item">🚪 Log Keluar</a>
            </div>
        </div>

        <!-- Welcome Card -->
        <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Selamat Datang, <?php echo $user['nama']; ?>!</h1>
            <p>No. Ahli: <?php echo $user['no_ahli']; ?> | Ahli sejak <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div style="color: #6b7280;">Status Keahlian</div>
                <div class="stat-number"><?php echo ucfirst($user['status']); ?></div>
                <div style="color: #10b981;">Aktif</div>
            </div>
            
            <div class="stat-card">
                <div style="color: #6b7280;">Ahli Keluarga</div>
                <div class="stat-number"><?php echo $user['jumlah_ahli_keluarga']; ?></div>
                <div style="color: #6b7280;">Orang</div>
            </div>
            
            <div class="stat-card">
                <div style="color: #6b7280;">Bayaran Lunas</div>
                <div class="stat-number"><?php echo $total_paid; ?></div>
                <div style="color: #10b981;">Tahun</div>
            </div>
            
            <div class="stat-card">
                <div style="color: #6b7280;">Belum Bayar</div>
                <div class="stat-number"><?php echo $total_unpaid; ?></div>
                <div style="color: #ef4444;">Tahun</div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <!-- Left Column - Payments -->
            <div>
                <div class="card">
                    <h2 style="margin-bottom: 1.5rem; color: #333;">💰 Status Pembayaran</h2>
                    
                    <?php foreach ($payments as $payment): ?>
                    <div class="payment-status">
                        <div>
                            <strong>Tahun <?php echo $payment['tahun']; ?></strong>
                            <?php if ($payment['tarikh_bayar']): ?>
                            <br><small>Dibayar: <?php echo date('d/m/Y', strtotime($payment['tarikh_bayar'])); ?></small>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="status-badge <?php echo $payment['status'] === 'lunas' ? 'status-lunas' : 'status-belum'; ?>">
                                <?php echo $payment['status'] === 'lunas' ? '✅ LUNAS' : '⚠️ BELUM BAYAR'; ?>
                                (RM <?php echo number_format($payment['jumlah'], 2); ?>)
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if ($total_unpaid > 0): ?>
                    <button class="btn" style="margin-top: 1.5rem; width: 100%;" onclick="alert('Fungsi pembayaran akan ditambah')">
                        💳 Bayar Yuran Tertunggak
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column - Emergency Contacts -->
            <div>
                <div class="card">
                    <h2 style="margin-bottom: 1.5rem; color: #333;">📞 Hubungan Kecemasan</h2>
                    
                    <div class="emergency-contact">
                        <span style="font-size: 1.5rem;">🏥</span>
                        <div>
                            <div class="contact-label" style="font-weight: 500;">Ambulans</div>
                            <strong>999</strong>
                        </div>
                    </div>
                    
                    <div class="emergency-contact">
                        <span style="font-size: 1.5rem;">🕌</span>
                        <div>
                            <div class="contact-label" style="font-weight: 500;">Kariah <?php echo $user['nama_kariah']; ?></div>
                            <strong><?php echo $user['nombor_kecemasan']; ?></strong>
                        </div>
                    </div>
                    
                    <div class="emergency-contact">
                        <span style="font-size: 1.5rem;">📍</span>
                        <div>
                            <div class="contact-label" style="font-weight: 500;">Lokasi Kariah</div>
                            <strong><?php echo $user['lokasi']; ?></strong>
                        </div>
                    </div>
                    
                    <div style="margin-top: 1.5rem; padding: 1rem; background: #f8faff; border-radius: 10px;">
                        <h3 style="color: #333; margin-bottom: 0.5rem;">📋 Maklumat Peribadi</h3>
                        <p><strong>No. KP:</strong> <?php echo $user['no_kp']; ?></p>
                        <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
                        <p><strong>Telefon:</strong> <?php echo $user['telefon']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <h2 style="margin-bottom: 1.5rem; color: #333;">📋 Ringkasan Keahlian</h2>
            
            <div class="info-grid">
                <div class="info-item">
                    <div style="color: #6b7280; font-size: 0.9rem;">Jumlah Bayaran Setakat Ini</div>
                    <div class="stat-number" style="font-size: 1.5rem;">RM <?php 
                        $total_amount = 0;
                        foreach ($payments as $p) {
                            if ($p['status'] === 'lunas') $total_amount += $p['jumlah'];
                        }
                        echo number_format($total_amount, 2);
                    ?></div>
                </div>
                
                <div class="info-item">
                    <div style="color: #6b7280; font-size: 0.9rem;">Bayaran Terakhir</div>
                    <div class="stat-number" style="font-size: 1.5rem;">
                        <?php echo $last_payment ? date('d/m/Y', strtotime($last_payment['tarikh_bayar'])) : 'Tiada rekod'; ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <div style="color: #6b7280; font-size: 0.9rem;">Status Tahun Semasa (2026)</div>
                    <div class="stat-number" style="font-size: 1.5rem; color: <?php 
                        $current_year_paid = false;
                        foreach ($payments as $p) {
                            if ($p['tahun'] == 2026 && $p['status'] == 'lunas') {
                                $current_year_paid = true;
                                break;
                            }
                        }
                        echo $current_year_paid ? '#10b981' : '#ef4444';
                    ?>;">
                        <?php echo $current_year_paid ? 'LUNAS' : 'BELUM BAYAR'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>