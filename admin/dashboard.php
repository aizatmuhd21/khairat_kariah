<?php
// admin/dashboard.php
require_once '../config/database.php';
require_once '../config/session.php';

Session::start();

// Check if admin is logged in
if (!Session::isLoggedIn() || Session::getUserType() !== 'admin') {
    header("Location: ../login.php?role=admin");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [];

// Total members
$member_query = "SELECT COUNT(*) as total FROM users WHERE status = 'aktif'";
$member_stmt = $db->prepare($member_query);
$member_stmt->execute();
$stats['total_members'] = $member_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total kariah
$kariah_query = "SELECT COUNT(*) as total FROM kariah";
$kariah_stmt = $db->prepare($kariah_query);
$kariah_stmt->execute();
$stats['total_kariah'] = $kariah_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Current year collections
$year = date('Y');
$payment_query = "SELECT 
                    COUNT(CASE WHEN status = 'lunas' THEN 1 END) as paid_count,
                    COUNT(CASE WHEN status = 'belum_bayar' THEN 1 END) as unpaid_count,
                    SUM(CASE WHEN status = 'lunas' THEN jumlah ELSE 0 END) as total_collected
                  FROM payments 
                  WHERE tahun = :year";
$payment_stmt = $db->prepare($payment_query);
$payment_stmt->bindParam(':year', $year);
$payment_stmt->execute();
$stats['payments'] = $payment_stmt->fetch(PDO::FETCH_ASSOC);

// Recent members
$recent_query = "SELECT u.*, k.nama_kariah 
                 FROM users u 
                 LEFT JOIN kariah k ON u.kariah_id = k.id 
                 ORDER BY u.created_at DESC 
                 LIMIT 5";
$recent_stmt = $db->prepare($recent_query);
$recent_stmt->execute();
$recent_members = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

// Unpaid members
$unpaid_query = "SELECT u.no_ahli, u.nama, k.nama_kariah, p.tahun, p.jumlah
                 FROM users u
                 JOIN payments p ON u.id = p.user_id
                 LEFT JOIN kariah k ON u.kariah_id = k.id
                 WHERE p.status = 'belum_bayar' AND p.tahun = :year
                 ORDER BY u.nama
                 LIMIT 10";
$unpaid_stmt = $db->prepare($unpaid_query);
$unpaid_stmt->bindParam(':year', $year);
$unpaid_stmt->execute();
$unpaid_members = $unpaid_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get admin info
$admin_name = Session::get('admin_name');
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Khairat Kariah</title>
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
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Navigation */
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
            gap: 0.5rem;
            align-items: center;
        }

        .nav-item {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #333;
            background: transparent;
            border: none;
            font-size: 0.95rem;
        }

        .nav-item:hover, .nav-item.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .admin-badge {
            background: #f0f4ff;
            padding: 0.3rem 1rem;
            border-radius: 20px;
            color: #667eea;
            font-weight: 600;
            margin-left: 1rem;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 45px rgba(102, 126, 234, 0.2);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .card-header h2 {
            color: #333;
            font-size: 1.3rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #f8faff 0%, #f0f3ff 100%);
            border-radius: 15px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.2);
            border-color: #667eea;
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-trend {
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        .trend-up { color: #10b981; }
        .trend-down { color: #ef4444; }

        /* Tables */
        .table-container {
            overflow-x: auto;
            border-radius: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 500;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #eef2f6;
        }

        tr:hover {
            background: #f8faff;
        }

        /* Badges */
        .badge {
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-success {
            background: linear-gradient(135deg, #a8e6cf 0%, #d4edda 100%);
            color: #155724;
        }

        .badge-warning {
            background: linear-gradient(135deg, #ffd3b5 0%, #ffb3b3 100%);
            color: #721c24;
        }

        .badge-info {
            background: linear-gradient(135deg, #b8e0ff 0%, #d4eaff 100%);
            color: #0369a1;
        }

        /* Buttons */
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-sm {
            padding: 0.3rem 0.8rem;
            font-size: 0.85rem;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
        }

        .btn-outline:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .action-btn {
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 2px;
            background: transparent;
        }

        .action-btn:hover {
            transform: scale(1.1);
        }

        .action-btn.edit:hover { background: #667eea; color: white; }
        .action-btn.paid:hover { background: #10b981; color: white; }
        .action-btn.delete:hover { background: #ef4444; color: white; }

        /* Forms */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.3rem;
            color: #333;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.6rem 1rem;
            border: 2px solid #e0e7ff;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h3 {
            color: #333;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6b7280;
        }

        /* Search and Filter */
        .search-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            padding: 0.6rem 1rem;
            border: 2px solid #e0e7ff;
            border-radius: 10px;
            min-width: 200px;
        }

        .filter-select {
            padding: 0.6rem 1rem;
            border: 2px solid #e0e7ff;
            border-radius: 10px;
            background: white;
        }

        /* Content sections */
        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-bar {
                flex-direction: column;
                gap: 1rem;
                border-radius: 20px;
            }
            
            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .search-bar {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navigation -->
        <div class="nav-bar">
            <div class="nav-logo">🕌 Khairat Kariah (Admin)</div>
            <div class="nav-menu">
                <button class="nav-item active" onclick="showSection('overview')">📊 Overview</button>
                <button class="nav-item" onclick="showSection('members')">👥 Ahli</button>
                <button class="nav-item" onclick="showSection('kariah')">📍 Kariah</button>
                <button class="nav-item" onclick="showSection('payments')">💰 Bayaran</button>
                <button class="nav-item" onclick="showSection('reports')">📈 Laporan</button>
                <a href="../logout.php" class="nav-item">🚪 Log Keluar</a>
                <span class="admin-badge">👑 <?php echo $admin_name ?: 'Admin'; ?></span>
            </div>
        </div>

        <!-- Overview Section (Default) -->
        <div id="overview-section" class="content-section active">
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-label">Jumlah Ahli Aktif</div>
                    <div class="stat-number"><?php echo number_format($stats['total_members']); ?></div>
                    <div class="stat-trend trend-up">↑ 12% dari bulan lepas</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📍</div>
                    <div class="stat-label">Jumlah Kariah</div>
                    <div class="stat-number"><?php echo $stats['total_kariah']; ?></div>
                    <div class="stat-trend">Semua aktif</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-label">Kutipan <?php echo $year; ?></div>
                    <div class="stat-number">RM <?php echo number_format($stats['payments']['total_collected'] ?: 0, 2); ?></div>
                    <div class="stat-trend trend-up">↑ 8% dari tahun lepas</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-label">Belum Bayar <?php echo $year; ?></div>
                    <div class="stat-number"><?php echo number_format($stats['payments']['unpaid_count'] ?: 0); ?></div>
                    <div class="stat-trend trend-down">Perlu tindakan</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h2>⚡ Tindakan Pantas</h2>
                </div>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button class="btn" onclick="showAddMemberModal()">+ Tambah Ahli Baru</button>
                    <button class="btn" onclick="showAddKariahModal()">+ Tambah Kariah</button>
                    <button class="btn" onclick="showAddPaymentModal()">💰 Rekod Bayaran</button>
                    <button class="btn btn-outline" onclick="exportReport()">📊 Export Laporan</button>
                </div>
            </div>

            <!-- Recent Members & Unpaid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Recent Members -->
                <div class="card">
                    <div class="card-header">
                        <h2>👥 Ahli Baru Daftar</h2>
                        <button class="btn btn-sm" onclick="showSection('members')">Lihat Semua</button>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>No. Ahli</th>
                                    <th>Nama</th>
                                    <th>Kariah</th>
                                    <th>Tarikh</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_members as $member): ?>
                                <tr>
                                    <td><?php echo $member['no_ahli']; ?></td>
                                    <td><?php echo $member['nama']; ?></td>
                                    <td><?php echo $member['nama_kariah']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($member['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Unpaid Members -->
                <div class="card">
                    <div class="card-header">
                        <h2>⚠️ Belum Bayar <?php echo $year; ?></h2>
                        <button class="btn btn-sm" onclick="showSection('payments')">Urus Bayaran</button>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>No. Ahli</th>
                                    <th>Nama</th>
                                    <th>Kariah</th>
                                    <th>Amaun</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($unpaid_members as $member): ?>
                                <tr>
                                    <td><?php echo $member['no_ahli']; ?></td>
                                    <td><?php echo $member['nama']; ?></td>
                                    <td><?php echo $member['nama_kariah']; ?></td>
                                    <td>RM <?php echo number_format($member['jumlah'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Members Management Section -->
        <div id="members-section" class="content-section">
            <div class="card">
                <div class="card-header">
                    <h2>👥 Pengurusan Ahli</h2>
                    <button class="btn" onclick="showAddMemberModal()">+ Tambah Ahli</button>
                </div>

                <!-- Search and Filter -->
                <div class="search-bar">
                    <input type="text" class="search-input" id="memberSearch" placeholder="Cari nama, no. ahli, no. KP..." onkeyup="searchMembers()">
                    <select class="filter-select" id="kariahFilter" onchange="filterMembers()">
                        <option value="">Semua Kariah</option>
                        <?php
                        $kariah_list = $db->query("SELECT * FROM kariah ORDER BY nama_kariah")->fetchAll();
                        foreach ($kariah_list as $k): ?>
                        <option value="<?php echo $k['id']; ?>"><?php echo $k['nama_kariah']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="filter-select" id="statusFilter" onchange="filterMembers()">
                        <option value="">Semua Status</option>
                        <option value="aktif">Aktif</option>
                        <option value="tidak_aktif">Tidak Aktif</option>
                    </select>
                </div>

                <!-- Members Table -->
                <div class="table-container">
                    <table id="membersTable">
                        <thead>
                            <tr>
                                <th>No. Ahli</th>
                                <th>Nama</th>
                                <th>No. KP</th>
                                <th>Kariah</th>
                                <th>Ahli Keluarga</th>
                                <th>Status</th>
                                <th>Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $members_query = "SELECT u.*, k.nama_kariah 
                                             FROM users u 
                                             LEFT JOIN kariah k ON u.kariah_id = k.id 
                                             ORDER BY u.created_at DESC";
                            $members_stmt = $db->prepare($members_query);
                            $members_stmt->execute();
                            $members = $members_stmt->fetchAll();
                            
                            foreach ($members as $member):
                            ?>
                            <tr>
                                <td><?php echo $member['no_ahli']; ?></td>
                                <td><?php echo $member['nama']; ?></td>
                                <td><?php echo $member['no_kp']; ?></td>
                                <td><?php echo $member['nama_kariah']; ?></td>
                                <td><?php echo $member['jumlah_ahli_keluarga']; ?></td>
                                <td>
                                    <span class="badge <?php echo $member['status'] == 'aktif' ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo ucfirst($member['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="action-btn edit" onclick="editMember(<?php echo $member['id']; ?>)" title="Edit">✏️</button>
                                    <button class="action-btn paid" onclick="recordPayment(<?php echo $member['id']; ?>)" title="Rekod Bayaran">💰</button>
                                    <button class="action-btn delete" onclick="deleteMember(<?php echo $member['id']; ?>)" title="Padam">🗑️</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Kariah Management Section -->
        <div id="kariah-section" class="content-section">
            <div class="card">
                <div class="card-header">
                    <h2>📍 Pengurusan Kariah</h2>
                    <button class="btn" onclick="showAddKariahModal()">+ Tambah Kariah</button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Kariah</th>
                                <th>Lokasi</th>
                                <th>Nombor Kecemasan</th>
                                <th>Jumlah Ahli</th>
                                <th>Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $kariah_query = "SELECT k.*, 
                                            (SELECT COUNT(*) FROM users WHERE kariah_id = k.id) as total_ahli 
                                            FROM kariah k 
                                            ORDER BY k.nama_kariah";
                            $kariah_stmt = $db->prepare($kariah_query);
                            $kariah_stmt->execute();
                            $kariah_list = $kariah_stmt->fetchAll();
                            
                            foreach ($kariah_list as $kariah):
                            ?>
                            <tr>
                                <td>K<?php echo str_pad($kariah['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo $kariah['nama_kariah']; ?></td>
                                <td><?php echo $kariah['lokasi']; ?></td>
                                <td><?php echo $kariah['nombor_kecemasan']; ?></td>
                                <td><?php echo $kariah['total_ahli']; ?> orang</td>
                                <td>
                                    <button class="action-btn edit" onclick="editKariah(<?php echo $kariah['id']; ?>)" title="Edit">✏️</button>
                                    <button class="action-btn delete" onclick="deleteKariah(<?php echo $kariah['id']; ?>)" title="Padam">🗑️</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payments Management Section -->
        <div id="payments-section" class="content-section">
            <div class="card">
                <div class="card-header">
                    <h2>💰 Pengurusan Bayaran</h2>
                    <button class="btn" onclick="showAddPaymentModal()">+ Rekod Bayaran</button>
                </div>

                <!-- Payment Filters -->
                <div class="search-bar">
                    <select class="filter-select" id="paymentYear" onchange="filterPayments()">
                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == date('Y') ? 'selected' : ''; ?>>
                            Tahun <?php echo $y; ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                    <select class="filter-select" id="paymentStatus" onchange="filterPayments()">
                        <option value="">Semua Status</option>
                        <option value="lunas">Lunas</option>
                        <option value="belum_bayar">Belum Bayar</option>
                    </select>
                    <input type="text" class="search-input" id="paymentSearch" placeholder="Carian..." onkeyup="filterPayments()">
                </div>

                <div class="table-container">
                    <table id="paymentsTable">
                        <thead>
                            <tr>
                                <th>Tarikh</th>
                                <th>No. Ahli</th>
                                <th>Nama</th>
                                <th>Kariah</th>
                                <th>Tahun</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                                <th>Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $payments_query = "SELECT p.*, u.no_ahli, u.nama, k.nama_kariah 
                                              FROM payments p
                                              JOIN users u ON p.user_id = u.id
                                              LEFT JOIN kariah k ON u.kariah_id = k.id
                                              ORDER BY p.tahun DESC, p.created_at DESC
                                              LIMIT 50";
                            $payments_stmt = $db->prepare($payments_query);
                            $payments_stmt->execute();
                            $payments = $payments_stmt->fetchAll();
                            
                            foreach ($payments as $payment):
                            ?>
                            <tr>
                                <td><?php echo $payment['tarikh_bayar'] ? date('d/m/Y', strtotime($payment['tarikh_bayar'])) : '-'; ?></td>
                                <td><?php echo $payment['no_ahli']; ?></td>
                                <td><?php echo $payment['nama']; ?></td>
                                <td><?php echo $payment['nama_kariah']; ?></td>
                                <td><?php echo $payment['tahun']; ?></td>
                                <td>RM <?php echo number_format($payment['jumlah'], 2); ?></td>
                                <td>
                                    <span class="badge <?php echo $payment['status'] == 'lunas' ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo $payment['status'] == 'lunas' ? 'Lunas' : 'Belum Bayar'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="action-btn edit" onclick="editPayment(<?php echo $payment['id']; ?>)" title="Edit">✏️</button>
                                    <?php if ($payment['status'] == 'belum_bayar'): ?>
                                    <button class="action-btn paid" onclick="markAsPaid(<?php echo $payment['id']; ?>)" title="Tanda Lunas">✅</button>
                                    <?php endif; ?>
                                    <button class="action-btn delete" onclick="deletePayment(<?php echo $payment['id']; ?>)" title="Padam">🗑️</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Reports Section -->
        <div id="reports-section" class="content-section">
            <div class="card">
                <div class="card-header">
                    <h2>📈 Laporan & Analisis</h2>
                </div>

                <!-- Report Filters -->
                <div class="search-bar">
                    <select class="filter-select" id="reportYear" onchange="generateReport()">
                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                        <option value="<?php echo $y; ?>">Tahun <?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                    <select class="filter-select" id="reportType" onchange="generateReport()">
                        <option value="summary">Ringkasan</option>
                        <option value="kariah">Mengikut Kariah</option>
                        <option value="monthly">Bulanan</option>
                    </select>
                    <button class="btn" onclick="exportReport()">📊 Export PDF</button>
                </div>

                <!-- Report Content -->
                <div id="reportContent">
                    <!-- Will be populated via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- Add Member Modal -->
    <div id="addMemberModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tambah Ahli Baru</h3>
                <button class="close-btn" onclick="closeModal('addMemberModal')">&times;</button>
            </div>
            <form id="addMemberForm" onsubmit="addMember(event)">
                <div class="form-group">
                    <label>Nama Penuh</label>
                    <input type="text" name="nama" required>
                </div>
                <div class="form-group">
                    <label>No. Kad Pengenalan</label>
                    <input type="text" name="no_kp" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Telefon</label>
                    <input type="tel" name="telefon" required>
                </div>
                <div class="form-group">
                    <label>Kariah</label>
                    <select name="kariah_id" required>
                        <option value="">Pilih Kariah</option>
                        <?php foreach ($kariah_list as $k): ?>
                        <option value="<?php echo $k['id']; ?>"><?php echo $k['nama_kariah']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jumlah Ahli Keluarga</label>
                    <input type="number" name="jumlah_keluarga" min="1" value="1" required>
                </div>
                <div class="form-group">
                    <label>Kata Laluan</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn">Daftar Ahli</button>
            </form>
        </div>
    </div>

    <!-- Add Kariah Modal -->
    <div id="addKariahModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tambah Kariah Baru</h3>
                <button class="close-btn" onclick="closeModal('addKariahModal')">&times;</button>
            </div>
            <form id="addKariahForm" onsubmit="addKariah(event)">
                <div class="form-group">
                    <label>Nama Kariah</label>
                    <input type="text" name="nama_kariah" required>
                </div>
                <div class="form-group">
                    <label>Lokasi</label>
                    <textarea name="lokasi" rows="2" required></textarea>
                </div>
                <div class="form-group">
                    <label>Nombor Kecemasan</label>
                    <input type="text" name="nombor_kecemasan" required placeholder="012-345 6789">
                </div>
                <button type="submit" class="btn">Tambah Kariah</button>
            </form>
        </div>
    </div>

    <!-- Add Payment Modal -->
    <div id="addPaymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Rekod Bayaran</h3>
                <button class="close-btn" onclick="closeModal('addPaymentModal')">&times;</button>
            </div>
            <form id="addPaymentForm" onsubmit="addPayment(event)">
                <div class="form-group">
                    <label>Cari Ahli</label>
                    <input type="text" id="memberSearchInput" onkeyup="searchMemberForPayment()" placeholder="Nama atau No. Ahli">
                    <select name="user_id" id="memberSelect" size="5" style="width: 100%; margin-top: 0.5rem;" required>
                        <?php
                        $user_list = $db->query("SELECT id, no_ahli, nama FROM users WHERE status = 'aktif' ORDER BY nama LIMIT 10")->fetchAll();
                        foreach ($user_list as $user): ?>
                        <option value="<?php echo $user['id']; ?>">
                            <?php echo $user['no_ahli']; ?> - <?php echo $user['nama']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tahun</label>
                    <select name="tahun" required>
                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                        <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>