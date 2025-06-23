<?php
session_start();
require_once 'db_connect.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Add Member Logic
if (isset($_POST['add_member'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $full_name = $first_name . ' ' . $last_name;
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $membership_date = date('Y-m-d');
    $status = 'Active';
    $first_login = 1;

    // Generate next member_code
    $stmt = $pdo->query("SELECT MAX(id) as max_id FROM members");
    $max_id = $stmt->fetch()['max_id'] ?? 0;
    $next_id = $max_id + 1;
    $member_code = 'MBR' . str_pad($next_id, 5, '0', STR_PAD_LEFT);

    // Check for duplicate email or username
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->fetchColumn() > 0) {
        echo "<script>alert('Email or Username already exists!');</script>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO members (member_code, full_name, username, email, password, phone, membership_date, status, first_login) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$member_code, $full_name, $username, $email, $password, $phone, $membership_date, $status, $first_login])) {
            echo "<script>alert('New member added successfully!');</script>";
        } else {
            echo "<script>alert('Error adding member.');</script>";
        }
    }
}

// Add Book Logic
if (isset($_POST['add_book'])) {
    $isbn = $_POST['isbn'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $publisher = $_POST['publisher'];
    $publication_year = intval($_POST['publication_year']);
    $genre = $_POST['genre'];
    $cover_image = null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('cover_', true) . '.' . $ext;
        move_uploaded_file($_FILES['cover_image']['tmp_name'], 'uploads/' . $filename);
        $cover_image = $filename;
    }
    // Insert into DB
    $stmt = $pdo->prepare("INSERT INTO books (isbn, title, author, publisher, publication_year, genre, cover_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$isbn, $title, $author, $publisher, $publication_year, $genre, $cover_image]);
    // Insert a new book copy (status will default to 'Available')
    $book_id = $pdo->lastInsertId();
    if (!$book_id) {
        // If book already exists, get its ID
        $stmt = $pdo->prepare("SELECT id FROM books WHERE isbn = ?");
        $stmt->execute([$isbn]);
        $book_id = $stmt->fetchColumn();
    }
    $quantity = intval($_POST['quantity']);
    for ($i = 0; $i < $quantity; $i++) {
        $stmt = $pdo->prepare("INSERT INTO book_copies (book_id) VALUES (?)");
        $stmt->execute([$book_id]);
    }
    echo "<script>alert('Book(s) added successfully!');</script>";
}

// Count pending requests for this admin (example, adjust as needed)
$pendingCount = 0;
// If you have a requests table, adjust this query accordingly
// $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE recipient_email=? AND status='Pending'");
// $stmt->execute([$_SESSION['admin_email']]);
// $pendingCount = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Victoria Falls Library Management System - Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Main Styles -->
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Extra overrides for admin_dash.php if needed */
        .status.active { background: #dcfce7; color: #166534; }
        .status.inactive { background: #fee2e2; color: #991b1b; }
        .status.available { background: #dcfce7; color: #166534; }
        .status.borrowed { background: #fef3c7; color: #92400e; }
        .status.lost { background: #fee2e2; color: #991b1b; }
        .data-table th, .data-table td { vertical-align: middle; }
        .sub-tabs { border-bottom: 1px solid #eee; }
        .sub-tab-btn {
            background: none;
            border: none;
            font-size: 1.1em;
            padding: 0.7em 1.5em;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: border 0.2s, color 0.2s;
            color: #333;
            outline: none;
        }
        .sub-tab-btn.active, .sub-tab-btn:hover {
            border-bottom: 3px solid #0072ff;
            color: #0072ff;
            font-weight: bold;
        }
        .report-section { margin-top: 1.5rem; }
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0; top: 0; right: 0; bottom: 0;
            width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.25);
            justify-content: center;
            align-items: center;
        }
        .modal.show {
            display: flex !important;
        }
        .modal-content {
            background: #fff;
            border-radius: 10px;
            max-width: 400px;
            width: 95%;
            margin: auto;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            padding: 2rem 1.5rem 1.5rem 1.5rem;
            position: relative;
            text-align: center;
            animation: fadeInModal 0.2s;
        }
        @keyframes fadeInModal {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .modal-header h2 {
            font-size: 1.3rem;
            margin: 0;
        }
        .close-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: #888;
        }
        .modal-body p {
            font-size: 1.05rem;
            margin: 1.2em 0;
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1em;
            margin-top: 1.5em;
        }
        .btn-cancel, .btn-submit {
            padding: 0.5em 1.3em;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-cancel {
            background: #f3f4f6;
            color: #333;
        }
        .btn-submit.cyan {
            background: #06b6d4;
            color: #fff;
        }
    </style>
</head>
<body>
<div class="dashboard">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo-section">
            <div class="logo-icon">
                <i class="fas fa-book"></i>
            </div>
            <div class="logo-text">
                <h1>Victoria Falls</h1>
                <p>LIBRARY MANAGEMENT</p>
                <p>SYSTEM</p>
            </div>
        </div>
        <nav class="nav-menu">
            <ul>
                <li><a href="?tab=dashboard" class="nav-item<?php if($tab=='dashboard') echo ' active'; ?>" data-tab="dashboard"><i class="fas fa-home"></i> DASHBOARD</a></li>
                <li><a href="?tab=books" class="nav-item<?php if($tab=='books') echo ' active'; ?>" data-tab="books"><i class="fas fa-book"></i> BOOKS</a></li>
                <li><a href="?tab=members" class="nav-item<?php if($tab=='members') echo ' active'; ?>" data-tab="members"><i class="fas fa-users"></i> MEMBERS</a></li>
                <li><a href="?tab=checkout" class="nav-item<?php if($tab=='checkout') echo ' active'; ?>" data-tab="checkout"><i class="fas fa-clipboard-check"></i> CHECKOUT</a></li>
                <li><a href="?tab=reports" class="nav-item<?php if($tab=='reports') echo ' active'; ?>" data-tab="reports"><i class="fas fa-chart-bar"></i> REPORTS</a></li>
            </ul>
        </nav>
    </div>
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <h1 class="page-title">
                    <?php
                    // Dynamic page titles
                    if ($tab == 'dashboard') {
                        echo "Dashboard";
                    } elseif ($tab == 'books') {
                        echo "Books Management";
                    } elseif ($tab == 'members') {
                        echo "Members Management";
                    } elseif ($tab == 'reports') {
                        echo "Reports";
                    } elseif ($tab == 'settings') {
                        echo "Settings";
                    }
                    ?>
                </h1>
                <div class="header-actions">
                    <!-- Removed envelope and notification bell icons -->
                    <div class="user-menu" style="position:relative;">
                        <?php
                        $adminName = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'A';
                        $avatarLetter = strtoupper(substr($adminName, 0, 1));
                        ?>
                        <div id="adminProfileBtn" style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <div class="user-avatar" style="width:38px;height:38px;border-radius:50%;background:#2563eb;color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.2em;font-weight:600;">
                                <?php echo $avatarLetter; ?>
                            </div>
                            <span class="user-name" style="font-weight:500;"><?php echo htmlspecialchars($adminName); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div id="adminDropdown" style="display:none;position:absolute;top:110%;right:0;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.12);padding:8px 0;min-width:120px;z-index:100;">
                            <a href="admin_logout.php" style="display:block;padding:10px 18px;color:#222;text-decoration:none;font-weight:500;">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <!-- Dashboard -->
        <div id="dashboard" class="tab-content<?php if($tab=='dashboard') echo ' active'; ?>">
            <div class="page-header">
                <h1>Welcome to Dashboard</h1>
                <p>Admin / Dashboard</p>
            </div>
            <div class="stats-grid">
                <div class="stat-card yellow">
                    <div class="stat-info">
                        <p class="stat-title">Total Books</p>
                        <p class="stat-value">
                            <?php
                            // Count total available copies (not just titles)
                            $stmt = $pdo->query("SELECT COUNT(*) as total FROM book_copies WHERE status = 'Available'");
                            echo $stmt ? $stmt->fetch()['total'] : '0';
                            ?>
                        </p>
                    </div>
                    <div class="stat-icon"><i class="fas fa-book"></i></div>
                </div>
                <div class="stat-card green">
                    <div class="stat-info">
                        <p class="stat-title">Total Members</p>
                        <p class="stat-value">
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) as total FROM members");
                            echo $stmt ? $stmt->fetch()['total'] : '0';
                            ?>
                        </p>
                    </div>
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                </div>
            </div>
            <!-- Add more dashboard widgets as needed -->
        </div>
        <!-- Books Management -->
        <div id="books" class="tab-content<?php if($tab=='books') echo ' active'; ?>">
            <div class="page-header">
                <h1>Books Management</h1>
                <p>Manage your library books</p>
            </div>
            <div class="table-container">
                <div class="table-header">
                    <h2>Books List</h2>
                    <div class="table-actions">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="Search books..." id="bookSearch">
                        </div>
                        <button class="add-btn blue" onclick="openBookModal()">
                            <i class="fas fa-plus"></i>
                            Add Book
                        </button>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>ISBN</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Publisher</th>
                            <th>Year</th>
                            <th>Genre</th>
                            <th>Status</th>
                            <th>Actions</th> <!-- New column for actions -->
                        </tr>
                        </thead>
                        <tbody id="booksTableBody">
                        <?php
                        $stmt = $pdo->query("SELECT b.id, b.isbn, b.title, b.author, b.publisher, b.publication_year, b.genre,
                            (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id AND bc.status = 'Available') as available
                            FROM books b");
                        while ($row = $stmt->fetch()): ?>
                            <tr>
                                <td><?=htmlspecialchars($row['id'])?></td>
                                <td><?=htmlspecialchars($row['isbn'])?></td>
                                <td><?=htmlspecialchars($row['title'])?></td>
                                <td><?=htmlspecialchars($row['author'])?></td>
                                <td><?=htmlspecialchars($row['publisher'])?></td>
                                <td><?=htmlspecialchars($row['publication_year'])?></td>
                                <td><?=htmlspecialchars($row['genre'])?></td>
                                <td>
                                    <?php if ($row['available'] > 0): ?>
                                        <span class="status available"><?=htmlspecialchars($row['available'])?> available</span>
                                    <?php else: ?>
                                        <span class="status lost">No copies</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn-edit" title="Edit" onclick="editBook(<?= $row['id'] ?>)" style="background:none;border:none;cursor:pointer;">
                                        <i class="fas fa-pencil-alt" style="color:#2563eb;font-size:1.1em;"></i>
                                    </button>
                                    <button class="btn-delete" title="Delete" onclick="deleteBook(<?= $row['id'] ?>)" style="background:none;border:none;cursor:pointer;margin-left:8px;">
                                        <i class="fas fa-trash" style="color:#ef4444;font-size:1.1em;"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Members Management -->
        <div id="members" class="tab-content<?php if($tab=='members') echo ' active'; ?>">
            <div class="page-header">
                <h1>Members Management</h1>
                <p>Manage library members</p>
            </div>
            <div class="table-container">
                <div class="table-header">
                    <h2>Members List</h2>
                    <div class="table-actions">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="Search members..." id="memberSearch" onkeyup="filterMembers()">
                        </div>
                        <button class="add-btn cyan" onclick="openMemberModal()">
                            <i class="fas fa-plus"></i>
                            Add Member
                        </button>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table class="data-table" id="membersTable">
                        <thead>
                        <tr>
                            <th>Member Code</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Membership Date</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody id="membersTableBody">
                        <?php
                        $stmt = $pdo->query("SELECT id, member_code, full_name, email, phone, status, membership_date FROM members");
                        while ($row = $stmt->fetch()): ?>
                            <tr>
                                <td><?=htmlspecialchars($row['member_code'])?></td>
                                <td><?=htmlspecialchars($row['full_name'])?></td>
                                <td><?=htmlspecialchars($row['email'])?></td>
                                <td><?=htmlspecialchars($row['phone'])?></td>
                                <td><span class="status <?=strtolower($row['status'])?>"><?=htmlspecialchars($row['status'])?></span></td>
                                <td><?=htmlspecialchars($row['membership_date'])?></td>
                                <td>
                                    <button class="btn-edit" title="Edit" onclick="editMember(<?= $row['id'] ?>)" style="background:none;border:none;cursor:pointer;">
                                        <i class="fas fa-pencil-alt" style="color:#2563eb;font-size:1.1em;"></i>
                                    </button>
                                    <button class="btn-delete" title="Delete" onclick="deleteMember(<?= $row['id'] ?>)" style="background:none;border:none;cursor:pointer;margin-left:8px;">
                                        <i class="fas fa-trash" style="color:#ef4444;font-size:1.1em;"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Checkout Requests -->
        <div id="checkout" class="tab-content<?php if($tab=='checkout') echo ' active'; ?>">
            <div class="page-header">
                <h1>Checkout Requests</h1>
                <p>Approve or deny member borrow/return requests, or manually lend/return books.</p>
            </div>
            <div class="sub-tabs" style="display:flex;gap:1rem;margin-bottom:1.5rem;">
                <button class="sub-tab-btn" id="borrowTabBtn" onclick="showCheckoutTab('borrow')" style="border-bottom:3px solid #0072ff;">Borrow Requests</button>
                <button class="sub-tab-btn" id="returnTabBtn" onclick="showCheckoutTab('return')">Return Requests</button>
                <button class="sub-tab-btn" id="manualLendTabBtn" onclick="showCheckoutTab('manualLend')">Manual Lend</button>
                <button class="sub-tab-btn" id="manualReturnTabBtn" onclick="showCheckoutTab('manualReturn')">Manual Return</button>
            </div>
            <!-- Pending Borrow Requests -->
            <div id="borrowRequestsTab" class="checkout-tab-section">
                <div class="table-header">
                    <h2>Pending Borrow Requests</h2>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Member</th>
                                <th>Book Title</th>
                                <th>Request Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT r.id, m.full_name, b.title, r.request_date 
                            FROM borrow_requests r
                            JOIN members m ON r.member_id = m.id
                            JOIN books b ON r.book_id = b.id
                            WHERE r.status = 'Pending' AND r.type = 'borrow'
                            ORDER BY r.request_date ASC");
                        while ($row = $stmt->fetch()):
                        ?>
                            <tr>
                                <td><?=htmlspecialchars($row['id'])?></td>
                                <td><?=htmlspecialchars($row['full_name'])?></td>
                                <td><?=htmlspecialchars($row['title'])?></td>
                                <td><?=htmlspecialchars($row['request_date'])?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                                        <button type="submit" name="approve_borrow" class="btn-submit cyan" style="margin-right:6px;">Approve</button>
                                        <button type="submit" name="deny_borrow" class="btn-cancel">Deny</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Pending Return Requests -->
            <div id="returnRequestsTab" class="checkout-tab-section" style="display:none;">
                <div class="table-header">
                    <h2>Pending Return Requests</h2>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Member</th>
                                <th>Book Title</th>
                                <th>Request Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT r.id, m.full_name, b.title, r.request_date 
                            FROM borrow_requests r
                            JOIN members m ON r.member_id = m.id
                            JOIN books b ON r.book_id = b.id
                            WHERE r.status = 'Pending' AND r.type = 'return'
                            ORDER BY r.request_date ASC");
                        while ($row = $stmt->fetch()):
                        ?>
                            <tr>
                                <td><?=htmlspecialchars($row['id'])?></td>
                                <td><?=htmlspecialchars($row['full_name'])?></td>
                                <td><?=htmlspecialchars($row['title'])?></td>
                                <td><?=htmlspecialchars($row['request_date'])?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                                        <button type="submit" name="approve_return" class="btn-submit cyan" style="margin-right:6px;">Approve</button>
                                        <button type="submit" name="deny_return" class="btn-cancel">Deny</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Manual Lend Section -->
            <div id="manualLendTab" class="checkout-tab-section" style="display:none;">
                <div class="table-header">
                    <h2>Manual Lend Book to Member</h2>
                </div>
                <form method="post" style="max-width:400px;margin:1.5em 0;">
                    <label for="lend_book_id">Select Book:</label>
                    <select name="lend_book_id" id="lend_book_id" required style="width:100%;margin-bottom:1em;">
                        <option value="">--Choose Book--</option>
                        <?php
                        $books = $pdo->query("SELECT id, title FROM books ORDER BY title ASC");
                        while($b = $books->fetch()):
                        ?>
                        <option value="<?=$b['id']?>"><?=htmlspecialchars($b['title'])?></option>
                        <?php endwhile; ?>
                    </select>
                    <label for="lend_member_id">Member Code (e.g. MBR00001):</label>
                    <input type="text" name="lend_member_code" id="lend_member_id" required style="width:100%;margin-bottom:1em;">
                    <button type="submit" name="manual_lend" class="btn-submit cyan" style="width:100%;">Checkout Book</button>
                </form>
            </div>
            <!-- Manual Return Section -->
            <div id="manualReturnTab" class="checkout-tab-section" style="display:none;">
                <div class="table-header">
                    <h2>Manual Return Book from Member</h2>
                </div>
                <form method="post" style="max-width:400px;margin:1.5em 0;">
                    <label for="return_member_id">Member Code (e.g. MBR00001):</label>
                    <input type="text" name="return_member_code" id="return_member_id" required style="width:100%;margin-bottom:1em;">
                    <button type="submit" name="fetch_member_loans" class="btn-submit cyan" style="width:100%;">Show Borrowed Books</button>
                </form>
                <?php
                // Show borrowed books for manual return
                if (isset($_POST['fetch_member_loans'])) {
                    $member_code = trim($_POST['return_member_code']);
                    $stmt = $pdo->prepare("SELECT id, full_name FROM members WHERE member_code=?");
                    $stmt->execute([$member_code]);
                    $member = $stmt->fetch();
                    if ($member) {
                        $stmt = $pdo->prepare("SELECT lt.id as tx_id, b.title, bc.copy_id, lt.checkout_date
                            FROM lending_transactions lt
                            JOIN book_copies bc ON lt.copy_id = bc.id
                            JOIN books b ON bc.book_id = b.id
                            WHERE lt.member_id=? AND lt.return_date IS NULL");
                        $stmt->execute([$member['id']]);
                        $loans = $stmt->fetchAll();
                        if ($loans):
                ?>
                <form method="post" style="margin-top:1em;">
                    <input type="hidden" name="return_member_id" value="<?=htmlspecialchars($member['id'])?>">
                    <label for="return_tx_id">Select Book to Return for <?=htmlspecialchars($member['full_name'])?>:</label>
                    <select name="return_tx_id" id="return_tx_id" required style="width:100%;margin-bottom:1em;">
                        <option value="">--Choose Book--</option>
                        <?php foreach($loans as $loan): ?>
                        <option value="<?=$loan['tx_id']?>"><?=htmlspecialchars($loan['title'])?> (Copy: <?=htmlspecialchars($loan['copy_id'])?>, Borrowed: <?=htmlspecialchars($loan['checkout_date'])?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="manual_return" class="btn-submit cyan" style="width:100%;">Return Book</button>
                </form>
                <?php
                        else:
                            echo "<div style='color:#991b1b;margin-top:1em;'>No active loans for this member.</div>";
                        endif;
                    } else {
                        echo "<div style='color:#991b1b;margin-top:1em;'>Member not found.</div>";
                    }
                }
                ?>
            </div>
        </div>
        <!-- Reports -->
        <div id="reports" class="tab-content<?php if($tab=='reports') echo ' active'; ?>">
            <div class="page-header">
                <h1>Reports</h1>
                <p>Active Loans and Member History</p>
            </div>
            <!-- Horizontal Sub-Tabs -->
            <div class="sub-tabs" style="display:flex;gap:1rem;margin-bottom:1.5rem;">
                <button class="sub-tab-btn<?php if(!isset($_GET['report']) || $_GET['report']=='loans') echo ' active'; ?>" onclick="showReportTab('loans')">Active Loans</button>
                <button class="sub-tab-btn<?php if(isset($_GET['report']) && $_GET['report']=='bookstatus') echo ' active'; ?>" onclick="showReportTab('bookstatus')">Book Status</button>
                <button class="sub-tab-btn<?php if(isset($_GET['report']) && $_GET['report']=='history') echo ' active'; ?>" onclick="showReportTab('history')">Member History</button>
                <button class="sub-tab-btn<?php if(isset($_GET['report']) && $_GET['report']=='fines') echo ' active'; ?>" onclick="showReportTab('fines')">Fines</button>
                <button class="sub-tab-btn<?php if(isset($_GET['report']) && $_GET['report']=='lending') echo ' active'; ?>" onclick="showReportTab('lending')">Lending Transactions</button>
            </div>

            <!-- Active Loans Table -->
            <div id="report-loans" class="report-section" style="<?php if(isset($_GET['report']) && $_GET['report']!='loans') echo 'display:none;'; ?>">
                <h2>Active Loans Table</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Member Name</th>
                            <th>Due Date</th>
                            <th>Overdue Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $stmt = $pdo->query("
                        SELECT b.title, m.full_name, lt.due_date,
                            CASE 
                                WHEN lt.due_date < CURDATE() THEN 'Overdue'
                                ELSE 'On Time'
                            END as overdue_status
                        FROM lending_transactions lt
                        JOIN members m ON lt.member_id = m.id
                        JOIN book_copies bc ON lt.copy_id = bc.id
                        JOIN books b ON bc.book_id = b.id
                        WHERE lt.return_date IS NULL
                    ");
                    $hasRows = false;
                    while ($row = $stmt->fetch()):
                        $hasRows = true;
                    ?>
                        <tr>
                            <td><?=htmlspecialchars($row['title'])?></td>
                            <td><?=htmlspecialchars($row['full_name'])?></td>
                            <td><?=htmlspecialchars($row['due_date'])?></td>
                            <td>
                                <span class="status <?=($row['overdue_status']=='Overdue'?'lost':'active')?>"><?=htmlspecialchars($row['overdue_status'])?></span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if (!$hasRows): ?>
                        <tr>
                            <td colspan="4" style="text-align:center;color:#888;">No active loans.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Book Status Table -->
            <div id="report-bookstatus" class="report-section" style="<?php if(!isset($_GET['report']) || $_GET['report']!='bookstatus') echo 'display:none;'; ?>">
                <h2>Book Status Summary</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Total Copies</th>
                            <th>Available</th>
                            <th>Borrowed</th>
                            <th>Lost</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $stmt = $pdo->query("
                        SELECT b.title,
                            COUNT(bc.id) as total,
                            SUM(bc.status='Available') as available,
                            SUM(bc.status='Borrowed') as borrowed,
                            SUM(bc.status='Lost') as lost
                        FROM books b
                        LEFT JOIN book_copies bc ON b.id = bc.book_id
                        GROUP BY b.id
                    ");
                    while ($row = $stmt->fetch()):
                    ?>
                        <tr>
                            <td><?=htmlspecialchars($row['title'])?></td>
                            <td><?=htmlspecialchars($row['total'])?></td>
                            <td><?=htmlspecialchars($row['available'])?></td>
                            <td><?=htmlspecialchars($row['borrowed'])?></td>
                            <td><?=htmlspecialchars($row['lost'])?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Member History Table -->
            <div id="report-history" class="report-section" style="<?php if(!isset($_GET['report']) || $_GET['report']!='history') echo 'display:none;'; ?>">
                <h2>Member History Table</h2>
                <form method="get" action="">
                    <input type="hidden" name="tab" value="reports">
                    <input type="hidden" name="report" value="history">
                    <label for="member_id">Select Member:</label>
                    <select name="member_id" id="member_id" onchange="this.form.submit()">
                        <option value="">--Choose--</option>
                        <?php
                        $members = $pdo->query("SELECT id, member_code, full_name FROM members");
                        $selected_member = isset($_GET['member_id']) ? $_GET['member_id'] : '';
                        while($m = $members->fetch()):
                        ?>
                        <option value="<?=$m['id']?>" <?=($selected_member==$m['id']?'selected':'')?>><?=$m['member_code']?> - <?=$m['full_name']?></option>
                        <?php endwhile; ?>
                    </select>
                </form>
                <?php if (!empty($selected_member)): ?>
                <table class="data-table" style="margin-top:1rem;">
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Copy ID</th>
                            <th>Checkout Date</th>
                            <th>Return Date</th>
                            <th>Fine Incurred</th>
                            <th>Fine Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT b.title, bc.copy_id, lt.checkout_date, lt.return_date, 
                            IFNULL(f.amount, 0) as fine_incurred,
                            IFNULL(f.status, 'None') as fine_status
                        FROM lending_transactions lt
                        JOIN book_copies bc ON lt.copy_id = bc.id
                        JOIN books b ON bc.book_id = b.id
                        LEFT JOIN fines f ON lt.id = f.transaction_id
                        WHERE lt.member_id = ?
                        ORDER BY lt.checkout_date DESC
                    ");
                    $stmt->execute([$selected_member]);
                    while ($row = $stmt->fetch()):
                    ?>
                        <tr>
                            <td><?=htmlspecialchars($row['title'])?></td>
                            <td><?=htmlspecialchars($row['copy_id'])?></td>
                            <td><?=htmlspecialchars($row['checkout_date'])?></td>
                            <td><?=htmlspecialchars($row['return_date'] ?: '-')?></td>
                            <td>$<?=htmlspecialchars($row['fine_incurred'])?></td>
                            <td><?=htmlspecialchars($row['fine_status'])?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <!-- Fines Table -->
            <div id="report-fines" class="report-section" style="<?php if(!isset($_GET['report']) || $_GET['report']!='fines') echo 'display:none;'; ?>">
                <h2>Fines Table</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Member Name</th>
                            <th>Book Title</th>
                            <th>Fine Amount</th>
                            <th>Paid Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $stmt = $pdo->query("
                        SELECT m.full_name, b.title, f.amount, f.status
                        FROM fines f
                        JOIN lending_transactions lt ON f.transaction_id = lt.id
                        JOIN book_copies bc ON lt.copy_id = bc.id
                        JOIN books b ON bc.book_id = b.id
                        JOIN members m ON lt.member_id = m.id
                        WHERE f.status IN ('Unpaid', 'Paid')
                        ORDER BY f.status ASC, f.amount DESC
                    ");
                    while ($row = $stmt->fetch()):
                    ?>
                        <tr>
                            <td><?=htmlspecialchars($row['full_name'])?></td>
                            <td><?=htmlspecialchars($row['title'])?></td>
                            <td>$<?=htmlspecialchars($row['amount'])?></td>
                            <td><span class="status <?=($row['status']=='Paid'?'active':'lost')?>"><?=htmlspecialchars($row['status'])?></span></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Lending Transactions Table -->
            <div id="report-lending" class="report-section" style="<?php if(!isset($_GET['report']) || $_GET['report']!='lending') echo 'display:none;'; ?>">
                <h2>Lending Transactions Table</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Copy ID</th>
                            <th>Member ID</th>
                            <th>Checkout Date</th>
                            <th>Due Date</th>
                            <th>Return Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $stmt = $pdo->query("
                        SELECT 
                            lt.id AS transaction_id,
                            bc.copy_id,
                            m.member_code,
                            lt.checkout_date,
                            lt.due_date,
                            lt.return_date,
                            CASE 
                                WHEN lt.return_date IS NOT NULL THEN 'Returned'
                                ELSE 'Borrowed'
                            END AS status
                        FROM lending_transactions lt
                        JOIN book_copies bc ON lt.copy_id = bc.id
                        JOIN members m ON lt.member_id = m.id
                        ORDER BY lt.id ASC
                    ");
                    $txCount = 1;
                    while ($row = $stmt->fetch()):
                        $txId = 'TX' . str_pad($txCount, 3, '0', STR_PAD_LEFT);
                    ?>
                        <tr>
                            <td><?=htmlspecialchars($txId)?></td>
                            <td><?=htmlspecialchars($row['copy_id'])?></td>
                            <td><?=htmlspecialchars($row['member_code'])?></td>
                            <td><?=htmlspecialchars($row['checkout_date'])?></td>
                            <td><?=htmlspecialchars($row['due_date'])?></td>
                            <td><?=htmlspecialchars($row['return_date'] ?: '-')?></td>
                            <td><?=htmlspecialchars($row['status'])?></td>
                        </tr>
                    <?php $txCount++; endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Settings -->
        <!--
        <div id="settings" class="tab-content<?php if($tab=='settings') echo ' active'; ?>">
            <div class="page-header">
                <h1>Settings</h1>
                <p>System settings and configuration coming soon...</p>
            </div>
            <!-- Add this inside your Settings tab content, e.g. after <h1>Settings</h1> -->
            <!--
            <div class="settings-panel" style="max-width:400px;margin:2rem auto 0;background:#fff;padding:2rem 2rem 1.5rem 2rem;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07);">
                <h2 style="font-size:1.2em;margin-bottom:1.2em;">Account Settings</h2>
                <form method="post" action="?tab=settings">
                    <label for="admin_new_password" style="display:block;margin-bottom:0.5em;font-weight:500;">New Password</label>
                    <input type="password" id="admin_new_password" name="admin_new_password" required style="width:100%;padding:0.7em;margin-bottom:1.2em;border-radius:6px;border:1px solid #ddd;">
                    <label for="admin_confirm_password" style="display:block;margin-bottom:0.5em;font-weight:500;">Confirm Password</label>
                    <input type="password" id="admin_confirm_password" name="admin_confirm_password" required style="width:100%;padding:0.7em;margin-bottom:1.5em;border-radius:6px;border:1px solid #ddd;">
                    <button type="submit" name="admin_reset_password" class="btn-submit cyan" style="width:100%;">Update Password</button>
                </form>
                <?php if (isset($adminPasswordMsg)): ?>
                    <div style="margin-top:1em;color:<?=strpos($adminPasswordMsg,'success')!==false?'#16a34a':'#dc2626'?>;font-weight:500;">
                        <?=htmlspecialchars($adminPasswordMsg)?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        -->
    </div>
</div>
<!-- Book Modal -->
<div id="bookModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Book</h2>
            <button class="close-btn" onclick="closeBookModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="bookForm" class="modal-form" method="post" action="?tab=books" enctype="multipart/form-data">
            <input type="text" name="isbn" placeholder="ISBN" required>
            <input type="text" name="title" placeholder="Book Title" required>
            <input type="text" name="author" placeholder="Author" required>
            <input type="text" name="publisher" placeholder="Publisher" required>
            <input type="number" name="publication_year" placeholder="Publication Year" required>
            <input type="text" name="genre" placeholder="Genre" required>
            <input type="number" name="quantity" placeholder="Number of Copies" required>
            <input type="file" name="cover_image" accept="image/*">
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeBookModal()">Cancel</button>
                <button type="submit" name="add_book" class="btn-submit blue">Add Book</button>
            </div>
        </form>
    </div>
</div>
<!-- Member Modal -->
<div id="memberModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Member</h2>
            <button class="close-btn" onclick="closeMemberModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="memberForm" class="modal-form" method="post" action="?tab=members">
            <input type="text" name="first_name" placeholder="First Name" required>
            <input type="text" name="last_name" placeholder="Last Name" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="phone" placeholder="Phone" required>
            <input type="password" name="password" placeholder="Temporary Password" required>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeMemberModal()">Cancel</button>
                <button type="submit" name="add_member" class="btn-submit cyan">Add Member</button>
            </div>
        </form>
    </div>
</div>
<!-- Edit Member Modal -->
<div id="editMemberModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Member</h2>
            <button class="close-btn" onclick="closeEditMemberModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="editMemberForm" class="modal-form" method="post" action="?tab=members">
            <input type="hidden" name="edit_member_id" id="edit_member_id">
            <input type="text" name="edit_full_name" id="edit_full_name" placeholder="Full Name" required>
            <input type="email" name="edit_email" id="edit_email" placeholder="Email" required>
            <input type="text" name="edit_phone" id="edit_phone" placeholder="Phone" required>
            <select name="edit_status" id="edit_status" required>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>
            <div style="margin: 1em 0;">
                <label>
                    <input type="checkbox" id="reset_password_toggle" name="reset_password_toggle" value="1" onchange="document.getElementById('edit_password').style.display=this.checked?'block':'none';">
                    Reset Password
                </label>
                <input type="password" name="edit_password" id="edit_password" placeholder="New Password" style="display:none; margin-top:0.5em;">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeEditMemberModal()">Cancel</button>
                <button type="submit" name="update_member" class="btn-submit cyan">Update Member</button>
            </div>
        </form>
    </div>
</div>
<!-- Edit Book Modal -->
<div id="editBookModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Book</h2>
            <button class="close-btn" onclick="closeEditBookModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="editBookForm" class="modal-form" method="post" action="?tab=books" enctype="multipart/form-data">
            <input type="hidden" name="edit_book_id" id="edit_book_id">
            <input type="text" name="edit_isbn" id="edit_isbn" placeholder="ISBN" required>
            <input type="text" name="edit_title" id="edit_title" placeholder="Book Title" required>
            <input type="text" name="edit_author" id="edit_author" placeholder="Author" required>
            <input type="text" name="edit_publisher" id="edit_publisher" placeholder="Publisher" required>
            <input type="number" name="edit_publication_year" id="edit_publication_year" placeholder="Publication Year" required>
            <input type="text" name="edit_genre" id="edit_genre" placeholder="Genre" required>
            <input type="number" name="edit_add_copies" id="edit_add_copies" placeholder="Add Copies (number)" min="0" style="margin-bottom:10px;">
            <label style="margin-top:10px;">Change Cover Image (optional):</label>
            <input type="file" name="edit_cover_image" id="edit_cover_image" accept="image/*">
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeEditBookModal()">Cancel</button>
                <button type="submit" name="update_book" class="btn-submit blue">Update Book</button>
            </div>
        </form>
    </div>
</div>
<!-- Confirm Delete Book Modal -->
<div id="confirmDeleteBookModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Confirm Delete</h2>
            <button class="close-btn" onclick="closeConfirmDeleteBookModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this book and all its copies? This action cannot be undone.</p>
        </div>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeConfirmDeleteBookModal()">Cancel</button>
            <button class="btn-submit cyan" onclick="confirmDeleteBook()">Delete</button>
        </div>
    </div>
</div>
<!-- Restriction Modal -->
<div id="deleteRestrictionModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Action Not Allowed</h2>
            <button class="close-btn" onclick="closeDeleteRestrictionModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p id="deleteRestrictionModalMsg">
                <!-- Message will be set by JS -->
            </p>
        </div>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeDeleteRestrictionModal()">Close</button>
        </div>
    </div>
</div>
<!-- Confirm Delete Modal -->
<div id="confirmDeleteModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Confirm Delete</h2>
            <button class="close-btn" onclick="closeConfirmDeleteModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this member? This action cannot be undone.</p>
        </div>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeConfirmDeleteModal()">Cancel</button>
            <button class="btn-submit cyan" onclick="confirmDeleteMember()">Delete</button>
        </div>
    </div>
</div>
<script>
    function openBookModal() {
        document.getElementById('bookModal').style.display = 'block';
    }
    function closeBookModal() {
        document.getElementById('bookModal').style.display = 'none';
    }
    function openMemberModal() {
        document.getElementById('memberModal').style.display = 'block';
    }
    function closeMemberModal() {
        document.getElementById('memberModal').style.display = 'none';
    }
    function closeEditMemberModal() {
        document.getElementById('editMemberModal').style.display = 'none';
    }
    function closeEditBookModal() {
        document.getElementById('editBookModal').style.display = 'none';
    }
    // Show modal with flex for center alignment
    function showDeleteRestrictionModal(msg) {
        document.getElementById('deleteRestrictionModalMsg').innerHTML = msg;
        document.getElementById('deleteRestrictionModal').style.display = 'block';
    }
    function closeDeleteRestrictionModal() {
        document.getElementById('deleteRestrictionModal').style.display = 'none';
        window.location = '?tab=members';
    }
    function showConfirmDeleteModal(id) {
        window.memberToDelete = id;
        document.getElementById('confirmDeleteModal').style.display = 'block';
    }
    function closeConfirmDeleteModal() {
        document.getElementById('confirmDeleteModal').style.display = 'none';
    }
    function confirmDeleteMember() {
        window.location.href = '?tab=members&delete_member=' + window.memberToDelete;
    }
    function deleteMember(id) {
        showConfirmDeleteModal(id);
    }
    function confirmDeleteBook() {
        window.location.href = '?tab=books&delete_book=' + window.bookToDelete;
    }
    function deleteBook(id) {
        window.bookToDelete = id;
        document.getElementById('confirmDeleteBookModal').style.display = 'block';
    }
    document.addEventListener('DOMContentLoaded', function() {
        var tab = "<?php echo $tab; ?>";
        document.querySelectorAll('.nav-item').forEach(function(el) {
            el.classList.remove('active');
            if(el.getAttribute('data-tab') === tab) el.classList.add('active');
        });
        if (document.getElementById(tab)) {
            document.getElementById(tab).classList.add('active');
        }
    });
    function showReportTab(tab) {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', 'reports');
        url.searchParams.set('report', tab);
        window.location.href = url.toString();
    }
    function editMember(id) {
        fetch('get_member.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                document.getElementById('edit_member_id').value = data.id;
                document.getElementById('edit_full_name').value = data.full_name;
                document.getElementById('edit_email').value = data.email;
                document.getElementById('edit_phone').value = data.phone;
                document.getElementById('edit_status').value = data.status;
                document.getElementById('editMemberModal').style.display = 'block';
            });
    }
    function editBook(id) {
        fetch('get_book.php?id=' + id)
            .then(response => response.json())
            .then (data => {
                document.getElementById('edit_book_id').value = data.id;
                document.getElementById('edit_isbn').value = data.isbn;
                document.getElementById('edit_title').value = data.title;
                document.getElementById('edit_author').value = data.author;
                document.getElementById('edit_publisher').value = data.publisher;
                document.getElementById('edit_publication_year').value = data.publication_year;
                document.getElementById('edit_genre').value = data.genre;
                document.getElementById('editBookModal').style.display = 'block';
            });
    }
    function deleteMember(id) {
        // Open confirmation modal
        document.getElementById('confirmDeleteModal').style.display = 'block';
        // Set up confirm delete action
        window.confirmDeleteMember = function() {
            // Proceed with delete
            if (confirm('Are you sure you want to delete this member?')) {
                window.location.href = '?tab=members&delete_member=' + id;
            }
        }
    }
    function filterMembers() {
        var input = document.getElementById("memberSearch");
        var filter = input.value.toLowerCase();
        var table = document.getElementById("membersTable");
        var trs = table.getElementsByTagName("tr");
        for (var i = 1; i < trs.length; i++) {
            var tds = trs[i].getElementsByTagName("td");
            var show = false;
            for (var j = 0; j < tds.length-1; j++) {
                if (tds[j].innerText.toLowerCase().indexOf(filter) > -1) show = true;
            }
            trs[i].style.display = show ? "" : "none";
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        const profileBtn = document.getElementById('adminProfileBtn');
        const dropdown = document.getElementById('adminDropdown');
        profileBtn.onclick = function(e) {
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            e.stopPropagation();
        };
        document.addEventListener('click', function() {
            dropdown.style.display = 'none';
        });
    });
    // Inactivity auto-logout (30s inactivity, 5s countdown, NO stay logged in prompt)
    let inactivityTimer, countdownTimer, countdown = 5;
    let countdownModal;
    function resetInactivityTimer() {
        clearTimeout(inactivityTimer);
        clearTimeout(countdownTimer);
        if (countdownModal) {
            countdownModal.remove();
            countdownModal = null;
            document.body.style.pointerEvents = '';
            document.body.style.userSelect = '';
        }
        countdown = 5;
        inactivityTimer = setTimeout(startCountdown, 30000); // 30 seconds
    }
    function startCountdown() {
        if (!countdownModal) {
            countdownModal = document.createElement('div');
            countdownModal.style.position = 'fixed';
            countdownModal.style.top = 0;
            countdownModal.style.left = 0;
            countdownModal.style.width = '100vw';
            countdownModal.style.height = '100vh';
            countdownModal.style.background = 'rgba(0,0,0,0.35)';
            countdownModal.style.display = 'flex';
            countdownModal.style.alignItems = 'center';
            countdownModal.style.justifyContent = 'center';
            countdownModal.style.zIndex = 99999;
            countdownModal.innerHTML = `
                <div style="background:#fff;padding:32px 36px;border-radius:18px;box-shadow:0 8px 32px rgba(0,0,0,0.15);text-align:center;max-width:90vw;">
                    <h2 style="color:#f5576c;margin-bottom:18px;">Session Expiring</h2>
                    <p style="font-size:1.1em;margin-bottom:18px;">
                        No activity detected.<br>
                        You will be logged out in <span id="countdownNum" style="font-weight:bold;color:#4facfe;">${countdown}</span> seconds.
                    </p>
                </div>
            `;
            document.body.appendChild(countdownModal);
            // Block all interaction except modal
            document.body.style.pointerEvents = 'none';
            document.body.style.userSelect = 'none';
            countdownModal.style.pointerEvents = 'auto';
        }
        updateCountdown();
    }
    function updateCountdown() {
        if (document.getElementById('countdownNum')) {
            document.getElementById('countdownNum').textContent = countdown;
        }
        if (countdown <= 0) {
            window.location.href = 'admin_logout.php';
        } else {
            countdown--;
            countdownTimer = setTimeout(updateCountdown, 1000);
        }
    }
    document.addEventListener('mousemove', resetInactivityTimer);
    document.addEventListener('keydown', resetInactivityTimer);
    document.addEventListener('click', resetInactivityTimer);
    resetInactivityTimer();
    function showCheckoutTab(tab) {
        document.getElementById('borrowRequestsTab').style.display = (tab === 'borrow') ? 'block' : 'none';
        document.getElementById('returnRequestsTab').style.display = (tab === 'return') ? 'block' : 'none';
        document.getElementById('manualLendTab').style.display = (tab === 'manualLend') ? 'block' : 'none';
        document.getElementById('manualReturnTab').style.display = (tab === 'manualReturn') ? 'block' : 'none';
        document.getElementById('borrowTabBtn').classList.toggle('active', tab === 'borrow');
        document.getElementById('returnTabBtn').classList.toggle('active', tab === 'return');
        document.getElementById('manualLendTabBtn').classList.toggle('active', tab === 'manualLend');
        document.getElementById('manualReturnTabBtn').classList.toggle('active', tab === 'manualReturn');
    }
    document.addEventListener('DOMContentLoaded', function() {
        showCheckoutTab('borrow');
    });
</script>

<?php
// --- PHP CRUD HANDLERS ---

// UPDATE MEMBER
if (isset($_POST['update_member'])) {
    $id = intval($_POST['edit_member_id']);
    $full_name = trim($_POST['edit_full_name']);
    $email = trim($_POST['edit_email']);
    $phone = trim($_POST['edit_phone']);
    $status = $_POST['edit_status'];

    // Prevent duplicate email or phone (excluding self)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE (email=? OR phone=?) AND id<>?");
    $stmt->execute([$email, $phone, $id]);
    if ($stmt->fetchColumn() > 0) {
        echo "<script>alert('Email or Phone already exists!');</script>";
    } else {
        // Prevent deactivation if member has active loans or unpaid fines
        if ($status == 'Inactive') {
            $hasLoans = $pdo->prepare("SELECT COUNT(*) FROM lending_transactions WHERE member_id=? AND return_date IS NULL");
            $hasLoans->execute([$id]);
            $hasFines = $pdo->prepare("SELECT COUNT(*) FROM fines WHERE member_id=? AND status='Unpaid'");
            $hasFines->execute([$id]);
            if ($hasLoans->fetchColumn() > 0 || $hasFines->fetchColumn() > 0) {
                echo "<script>
                    showDeleteRestrictionModal('Cannot deactivate member with active loans or unpaid fines!');
                </script>";
                exit;
            }
        }
        // Password reset logic
        if (isset($_POST['reset_password_toggle']) && !empty($_POST['edit_password'])) {
            $password = password_hash($_POST['edit_password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE members SET full_name=?, email=?, phone=?, status=?, password=? WHERE id=?");
            $stmt->execute([$full_name, $email, $phone, $status, $password, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE members SET full_name=?, email=?, phone=?, status=? WHERE id=?");
            $stmt->execute([$full_name, $email, $phone, $status, $id]);
        }
        echo "<script>alert('Member updated successfully!');window.location='?tab=members';</script>";
        exit;
    }
}

// UPDATE BOOK
if (isset($_POST['update_book'])) {
    $id = intval($_POST['edit_book_id']);
    $isbn = trim($_POST['edit_isbn']);
    $title = trim($_POST['edit_title']);
    $author = trim($_POST['edit_author']);
    $publisher = trim($_POST['edit_publisher']);
    $publication_year = intval($_POST['edit_publication_year']);
    $genre = trim($_POST['edit_genre']);
    $add_copies = isset($_POST['edit_add_copies']) ? intval($_POST['edit_add_copies']) : 0;

    // Handle cover image update
    $cover_sql = "";
    $params = [$isbn, $title, $author, $publisher, $publication_year, $genre];
    if (isset($_FILES['edit_cover_image']) && $_FILES['edit_cover_image']['error'] == UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['edit_cover_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('cover_', true) . '.' . $ext;
        move_uploaded_file($_FILES['edit_cover_image']['tmp_name'], 'uploads/' . $filename);
        $cover_sql = ", cover_image=?";
        $params[] = $filename;
    }
    $params[] = $id;

    $stmt = $pdo->prepare("UPDATE books SET isbn=?, title=?, author=?, publisher=?, publication_year=?, genre=?$cover_sql WHERE id=?");
    $stmt->execute($params);

    // Add new copies if requested
    for ($i = 0; $i < $add_copies; $i++) {
        $stmt = $pdo->prepare("INSERT INTO book_copies (book_id) VALUES (?)");
        $stmt->execute([$id]);
    }

    echo "<script>alert('Book updated successfully!');window.location='?tab=books';</script>";
    exit;
}

// DELETE MEMBER (Hard delete, restrict if active loans or unpaid fines)
if (isset($_GET['delete_member'])) {
    $id = intval($_GET['delete_member']);
    // Check for active loans
    $hasLoans = $pdo->prepare("SELECT COUNT(*) FROM lending_transactions WHERE member_id=? AND return_date IS NULL");
    $hasLoans->execute([$id]);
    // Check for unpaid fines
    $hasFines = $pdo->prepare("SELECT COUNT(*) FROM fines WHERE member_id=? AND status='Unpaid'");
    $hasFines->execute([$id]);
    if ($hasLoans->fetchColumn() > 0 || $hasFines->fetchColumn() > 0) {
        echo "<script>
            window.memberToEdit = $id;
            showDeleteRestrictionModal('Caution: Member has Active Loans or Fines and cannot be deleted.');
            document.getElementById('openEditFromDelete').onclick = function() {
                closeDeleteRestrictionModal();
                editMember(window.memberToEdit);
            };
        </script>";
    } else {
        // Hard delete from DB
        $stmt = $pdo->prepare("DELETE FROM members WHERE id=?");
        $stmt->execute([$id]);
        echo "<script>alert('Member deleted successfully!');window.location='?tab=members';</script>";
        exit;
    }
}
if (isset($_GET['delete_member_confirmed'])) {
    $id = intval($_GET['delete_member_confirmed']);
    // Check for active loans
    $hasLoans = $pdo->prepare("SELECT COUNT(*) FROM lending_transactions WHERE member_id=? AND return_date IS NULL");
    $hasLoans->execute([$id]);
    // Check for unpaid fines
    $hasFines = $pdo->prepare("SELECT COUNT(*) FROM fines WHERE member_id=? AND status='Unpaid'");
    $hasFines->execute([$id]);
    if ($hasLoans->fetchColumn() > 0 || $hasFines->fetchColumn() > 0) {
        echo "<script>
            showDeleteRestrictionModal('<strong>Caution:</strong> You cannot delete <u>nor inactivate</u> a member with active loans or unpaid fines.<br>Please ensure all loans are returned and fines are cleared before proceeding.');
        </script>";
    } else {
        $stmt = $pdo->prepare("DELETE FROM members WHERE id=?");
        $stmt->execute([$id]);
        echo "<script>
            showDeleteRestrictionModal('Member deleted successfully!');
            setTimeout(function(){ window.location='?tab=members'; }, 1200);
        </script>";
        exit;
    }
}

// DELETE BOOK (hard delete)
if (isset($_GET['delete_book'])) {
    $id = intval($_GET['delete_book']);
    // Delete all book copies first (to maintain referential integrity)
    $stmt = $pdo->prepare("DELETE FROM book_copies WHERE book_id=?");
    $stmt->execute([$id]);
    // Delete the book itself
    $stmt = $pdo->prepare("DELETE FROM books WHERE id=?");
    $stmt->execute([$id]);
    echo "<script>alert('Book and all its copies deleted successfully!');window.location='?tab=books';</script>";
    exit;
}

// Approve Borrow Request
if (isset($_POST['approve_borrow'])) {
    $request_id = intval($_POST['request_id']);
    // Get request info
    $stmt = $pdo->prepare("SELECT * FROM borrow_requests WHERE id=?");
    $stmt->execute([$request_id]);
    $req = $stmt->fetch();
    if ($req && $req['status'] == 'Pending' && $req['type'] == 'borrow') {
        // Mark request as approved
        $pdo->prepare("UPDATE borrow_requests SET status='Approved' WHERE id=?")->execute([$request_id]);
        // Add lending transaction
        $copyStmt = $pdo->prepare("SELECT id FROM book_copies WHERE book_id=? AND status='Available' LIMIT 1");
        $copyStmt->execute([$req['book_id']]);
        $copy = $copyStmt->fetch();
        if ($copy) {
            $due_date = date('Y-m-d', strtotime('+14 days'));
            $pdo->prepare("INSERT INTO lending_transactions (copy_id, member_id, checkout_date, due_date) VALUES (?, ?, NOW(), ?)")
                ->execute([$copy['id'], $req['member_id'], $due_date]);
            $pdo->prepare("UPDATE book_copies SET status='Borrowed' WHERE id=?")->execute([$copy['id']]);
        }
        echo "<script>alert('Borrow request approved!');window.location='?tab=checkout';</script>";
        exit;
    }
}
// Deny Borrow Request
if (isset($_POST['deny_borrow'])) {
    $request_id = intval($_POST['request_id']);
    $pdo->prepare("UPDATE borrow_requests SET status='Denied' WHERE id=?")->execute([$request_id]);
    echo "<script>alert('Borrow request denied.');window.location='?tab=checkout';</script>";
    exit;
}
// Approve Return Request
if (isset($_POST['approve_return'])) {
    $request_id = intval($_POST['request_id']);
    $stmt = $pdo->prepare("SELECT * FROM borrow_requests WHERE id=?");
    $stmt->execute([$request_id]);
    $req = $stmt->fetch();
    if ($req && $req['status'] == 'Pending' && $req['type'] == 'return') {
        // Mark request as approved
        $pdo->prepare("UPDATE borrow_requests SET status='Approved' WHERE id=?")->execute([$request_id]);
        // Find the active lending transaction
        $ltStmt = $pdo->prepare("SELECT lt.id, lt.copy_id FROM lending_transactions lt
            JOIN book_copies bc ON lt.copy_id = bc.id
            WHERE lt.member_id=? AND bc.book_id=? AND lt.return_date IS NULL
            LIMIT 1");
        $ltStmt->execute([$req['member_id'], $req['book_id']]);
        $lt = $ltStmt->fetch();
        if ($lt) {
            $pdo->prepare("UPDATE lending_transactions SET return_date=NOW() WHERE id=?")->execute([$lt['id']]);
            $pdo->prepare("UPDATE book_copies SET status='Available' WHERE id=?")->execute([$lt['copy_id']]);
        }
        echo "<script>alert('Return request approved!');window.location='?tab=checkout';</script>";
        exit;
    }
}
// Deny Return Request
if (isset($_POST['deny_return'])) {
    $request_id = intval($_POST['request_id']);
    $pdo->prepare("UPDATE borrow_requests SET status='Denied' WHERE id=?")->execute([$request_id]);
    echo "<script>alert('Return request denied.');window.location='?tab=checkout';</script>";
    exit;
}

// Manual Lend Handler
if (isset($_POST['manual_lend'])) {
    $book_id = intval($_POST['lend_book_id']);
    $member_code = trim($_POST['lend_member_code']);
    // Find member by code
    $stmt = $pdo->prepare("SELECT id FROM members WHERE member_code=?");
    $stmt->execute([$member_code]);
    $member = $stmt->fetch();
    if ($member) {
        // Find available copy
        $stmt = $pdo->prepare("SELECT id FROM book_copies WHERE book_id=? AND status='Available' LIMIT 1");
        $stmt->execute([$book_id]);
        $copy = $stmt->fetch();
        if ($copy) {
            $due_date = date('Y-m-d', strtotime('+14 days'));
            $pdo->prepare("INSERT INTO lending_transactions (copy_id, member_id, checkout_date, due_date) VALUES (?, ?, NOW(), ?)")
                ->execute([$copy['id'], $member['id'], $due_date]);
            $pdo->prepare("UPDATE book_copies SET status='Borrowed' WHERE id=?")->execute([$copy['id']]);
            echo "<script>alert('Book checked out to member!');window.location='?tab=checkout';</script>";
            exit;
        } else {
            echo "<script>alert('No available copies for this book.');window.location='?tab=checkout';</script>";
            exit;
        }
    } else {
        echo "<script>alert('Member not found.');window.location='?tab=checkout';</script>";
        exit;
    }
}

// Manual Return Handler
if (isset($_POST['manual_return'])) {
    $tx_id = intval($_POST['return_tx_id']);
    // Get copy_id
    $stmt = $pdo->prepare("SELECT copy_id FROM lending_transactions WHERE id=? AND return_date IS NULL");
    $stmt->execute([$tx_id]);
    $copy_id = $stmt->fetchColumn();
    if ($copy_id) {
        $pdo->prepare("UPDATE lending_transactions SET return_date=NOW() WHERE id=?")->execute([$tx_id]);
        $pdo->prepare("UPDATE book_copies SET status='Available' WHERE id=?")->execute([$copy_id]);
        echo "<script>alert('Book returned successfully!');window.location='?tab=checkout';</script>";
        exit;
    } else {
        echo "<script>alert('Transaction not found or already returned.');window.location='?tab=checkout';</script>";
        exit;
    }
}
?>
