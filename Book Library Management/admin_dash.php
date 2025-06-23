<?php
require_once 'db_connect.php';
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
$conn = new mysqli('localhost', 'root', '', 'vf_library');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add Member Logic (already present)
if (isset($_POST['add_member'])) {
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $status = 'Active';
    $date_joined = date('Y-m-d');
    $membership_date = date('Y-m-d');
    $member_id = uniqid('MBR-');

    $sql = "INSERT INTO Members (member_id, first_name, last_name, username, email, password, status, date_joined, membership_date)
            VALUES ('$member_id', '$first_name', '$last_name', '$username', '$email', '$password', '$status', '$date_joined', '$membership_date')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('New member added successfully!');</script>";
    } else {
        echo "<script>alert('Error adding member: " . $conn->error . "');</script>";
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
    $category = $_POST['category'];
    $quantity = intval($_POST['quantity']);

    // Book metadata
    $metaCheck = $conn->prepare("SELECT isbn FROM book_metadata WHERE isbn=?");
    $metaCheck->bind_param("s", $isbn);
    $metaCheck->execute();
    $metaCheckResult = $metaCheck->get_result();
    if ($metaCheckResult->num_rows == 0) {
        $metaInsert = $conn->prepare("INSERT INTO book_metadata (isbn, title, author, publisher, publication_year, genre) VALUES (?, ?, ?, ?, ?, ?)");
        $metaInsert->bind_param("ssssss", $isbn, $title, $author, $publisher, $publication_year, $genre);
        $metaInsert->execute();
        $metaInsert->close();
    }
    $metaCheck->close();

    // Books table
    $bookCheck = $conn->prepare("SELECT book_id, availability FROM books WHERE isbn=? AND category=?");
    $bookCheck->bind_param("ss", $isbn, $category);
    $bookCheck->execute();
    $bookCheckResult = $bookCheck->get_result();
    if ($bookCheckResult->num_rows > 0) {
        $row = $bookCheckResult->fetch_assoc();
        $newAvailability = $row['availability'] + $quantity;
        $update = $conn->prepare("UPDATE books SET availability=? WHERE book_id=?");
        $update->bind_param("is", $newAvailability, $row['book_id']);
        $update->execute();
        $update->close();
    } else {
        $insert = $conn->prepare("INSERT INTO books (isbn, category, availability) VALUES (?, ?, ?)");
        $insert->bind_param("ssi", $isbn, $category, $quantity);
        $insert->execute();
        $insert->close();
    }
    $bookCheck->close();

    echo "<script>alert('Book added/updated successfully!');</script>";
}

// Count pending requests for this admin
$admin_email = $_SESSION['email'];
$result = $conn->query("SELECT COUNT(*) as cnt FROM requests WHERE recipient_email='$admin_email' AND status='Pending'");
$row = $result->fetch_assoc();
$pendingCount = $row['cnt'];
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
                <li><a href="?tab=books" class="nav-item<?php if($tab=='books') echo ' active'; ?>" data-tab="books"><i class="fas fa-book"></i> TOTAL BOOKS</a></li>
                <li><a href="?tab=add-book" class="nav-item<?php if($tab=='add-book') echo ' active'; ?>" data-tab="add-book"><i class="fas fa-plus"></i> ADD BOOKS</a></li>
                <li><a href="?tab=members" class="nav-item<?php if($tab=='members') echo ' active'; ?>" data-tab="members"><i class="fas fa-users"></i> MEMBERS</a></li>
                <li><a href="?tab=reports" class="nav-item<?php if($tab=='reports') echo ' active'; ?>" data-tab="reports"><i class="fas fa-chart-bar"></i> REPORTS</a></li>
                <li><a href="?tab=settings" class="nav-item<?php if($tab=='settings') echo ' active'; ?>" data-tab="settings"><i class="fas fa-cog"></i> SETTINGS</a></li>
            </ul>
        </nav>
    </div>
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-actions">
                <button class="header-btn"><i class="fas fa-envelope"></i></button>
                <button class="header-btn notification-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge"><?php echo $pendingCount; ?></span>
                </button>
                <div class="user-menu">
                    <?php
                    $adminName = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'A';
                    $avatarLetter = strtoupper(substr($adminName, 0, 1));
                    ?>
                    <div class="user-avatar"><?php echo $avatarLetter; ?></div>
                    <span class="user-name"><?php echo htmlspecialchars($adminName); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <a href="admin_logout.php" class="btn-cancel" style="margin-left:1rem;">Logout</a>
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
                            $books = $conn->query("SELECT COUNT(*) as total FROM Books");
                            echo $books ? $books->fetch_assoc()['total'] : '0';
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
                            $members = $conn->query("SELECT COUNT(*) as total FROM Members");
                            echo $members ? $members->fetch_assoc()['total'] : '0';
                            ?>
                        </p>
                    </div>
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                </div>
            </div>
            <div class="reports-section">
                <div class="reports-header">
                    <h2>Reports</h2>
                    <div class="report-filters">
                        <button class="filter-btn active">Today</button>
                        <button class="filter-btn">Last Week</button>
                        <button class="filter-btn">Last Month</button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="reportsChart" width="800" height="200"></canvas>
                </div>
            </div>
            <div class="bottom-section">
                <div class="info-card">
                    <h3>New Members</h3>
                    <div class="info-list">
                        <?php
                        $newMembers = $conn->query("SELECT first_name, last_name, date_joined FROM Members ORDER BY date_joined DESC LIMIT 2");
                        while($m = $newMembers->fetch_assoc()): ?>
                        <div class="info-item">
                            <div class="info-avatar blue"><i class="fas fa-user"></i></div>
                            <div class="info-details">
                                <p class="info-name"><?php echo htmlspecialchars($m['first_name'].' '.$m['last_name']); ?></p>
                                <p class="info-date">Joined <?php echo htmlspecialchars($m['date_joined']); ?></p>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <div class="info-card">
                    <h3>New Books</h3>
                    <div class="info-list">
                        <?php
                        $newBooks = $conn->query("SELECT title, publication_year FROM Book_Metadata ORDER BY publication_year DESC LIMIT 2");
                        while($b = $newBooks->fetch_assoc()): ?>
                        <div class="info-item">
                            <div class="info-avatar red"><i class="fas fa-book"></i></div>
                            <div class="info-details">
                                <p class="info-name"><?php echo htmlspecialchars($b['title']); ?></p>
                                <p class="info-date">Added <?php echo htmlspecialchars($b['publication_year']); ?></p>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
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
                            <th>Book ID</th>
                            <th>ISBN</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Publisher</th>
                            <th>Year</th>
                            <th>Genre</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody id="booksTableBody">
                        <?php
                        $result = $conn->query("SELECT b.book_id, bm.isbn, bm.title, bm.author, bm.publisher, bm.publication_year, bm.genre, b.category, b.availability
                            FROM Books b
                            LEFT JOIN Book_Metadata bm ON b.isbn = bm.isbn");
                        while ($row = $result && $result->num_rows > 0 ? $result->fetch_assoc() : false): ?>
                            <tr>
                                <td><?=htmlspecialchars($row['book_id'])?></td>
                                <td><?=htmlspecialchars($row['isbn'])?></td>
                                <td><?=htmlspecialchars($row['title'])?></td>
                                <td><?=htmlspecialchars($row['author'])?></td>
                                <td><?=htmlspecialchars($row['publisher'])?></td>
                                <td><?=htmlspecialchars($row['publication_year'])?></td>
                                <td><?=htmlspecialchars($row['genre'])?></td>
                                <td><?=htmlspecialchars($row['category'])?></td>
                                <td><span class="status available"><?=htmlspecialchars($row['availability'])?></span></td>
                                <td>
                                    <button class="action-btn edit"><i class="fas fa-edit"></i></button>
                                    <button class="action-btn delete"><i class="fas fa-trash"></i></button>
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
                            <input type="text" placeholder="Search members..." id="memberSearch">
                        </div>
                        <button class="add-btn cyan" onclick="openMemberModal()">
                            <i class="fas fa-plus"></i>
                            Add Member
                        </button>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                        <tr>
                            <th>Member ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Date Joined</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody id="membersTableBody">
                        <?php
                        $result = $conn->query("SELECT member_id, first_name, last_name, username, email, status, date_joined FROM Members");
                        while ($row = $result && $result->num_rows > 0 ? $result->fetch_assoc() : false): ?>
                            <tr>
                                <td><?=htmlspecialchars($row['member_id'])?></td>
                                <td><?=htmlspecialchars($row['first_name'])?></td>
                                <td><?=htmlspecialchars($row['last_name'])?></td>
                                <td><?=htmlspecialchars($row['username'])?></td>
                                <td><?=htmlspecialchars($row['email'])?></td>
                                <td><span class="status active"><?=htmlspecialchars($row['status'])?></span></td>
                                <td><?=htmlspecialchars($row['date_joined'])?></td>
                                <td>
                                    <button class="action-btn edit"><i class="fas fa-edit"></i></button>
                                    <button class="action-btn delete"><i class="fas fa-trash"></i></button>
                                    <button class="action-btn deactivate"><i class="fas fa-user-times"></i></button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Reports -->
        <div id="reports" class="tab-content<?php if($tab=='reports') echo ' active'; ?>">
            <div class="page-header">
                <h1>Reports</h1>
                <p>Detailed reports and analytics coming soon...</p>
            </div>
        </div>
        <!-- Settings -->
        <div id="settings" class="tab-content<?php if($tab=='settings') echo ' active'; ?>">
            <div class="page-header">
                <h1>Settings</h1>
                <p>System settings and configuration coming soon...</p>
            </div>
        </div>
        <!-- Add this section where you want the admin to view requests/mails -->
        <div id="requests" class="tab-content<?php if($tab=='requests') echo ' active'; ?>">
            <div class="page-header">
                <h1>Member Requests</h1>
                <p>View and manage requests sent to you</p>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>From</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $admin_email = $_SESSION['email'];
                    $requests = $conn->query("SELECT * FROM requests WHERE recipient_email='$admin_email' ORDER BY request_date DESC");
                    while ($req = $requests && $requests->num_rows > 0 ? $requests->fetch_assoc() : false): ?>
                        <tr>
                            <td><?=htmlspecialchars($req['member_email'])?></td>
                            <td><?=htmlspecialchars($req['subject'])?></td>
                            <td><?=nl2br(htmlspecialchars($req['request_mail']))?></td>
                            <td><?=htmlspecialchars($req['request_date'])?></td>
                            <td><span class="status"><?=htmlspecialchars($req['status'])?></span></td>
                            <td>
                                <?php if($req['status'] == 'Pending'): ?>
                                    <button onclick="updateRequestStatus('<?= $req['request_id'] ?>', 'Accepted')">Accept</button>
                                    <button onclick="updateRequestStatus('<?= $req['request_id'] ?>', 'Denied')">Deny</button>
                                    <button onclick="updateRequestStatus('<?= $req['request_id'] ?>', 'Approved')">Approve</button>
                                    <button onclick="updateRequestStatus('<?= $req['request_id'] ?>', 'Fulfilled')">Fulfill</button>
                                <?php else: ?>
                                    <span>No actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- Book Modal -->
<div id="bookModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Book</h2>
            <button class="close-btn" onclick="closeBookModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="bookForm" class="modal-form" method="post" action="?tab=books">
            <input type="text" name="isbn" placeholder="ISBN" required>
            <input type="text" name="title" placeholder="Book Title" required>
            <input type="text" name="author" placeholder="Author" required>
            <input type="text" name="publisher" placeholder="Publisher" required>
            <input type="number" name="publication_year" placeholder="Publication Year" required>
            <input type="text" name="genre" placeholder="Genre" required>
            <input type="text" name="category" placeholder="Category" required>
            <input type="number" name="quantity" placeholder="Number of Copies" required>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeBookModal()">Cancel</button>
                <button type="submit" name="add_book" class="btn-submit blue">Add Book</button>
            </div>
        </form>
    </div>
</div>
<!-- Member Modal -->
<div id="memberModal" class="modal">
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
            <input type="password" name="password" placeholder="Password" required>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeMemberModal()">Cancel</button>
                <button type="submit" name="add_member" class="btn-submit cyan">Add Member</button>
            </div>
        </form>
    </div>
</div>
<script src="script.js"></script>
<script>
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

    async function updateRequestStatus(requestId, newStatus) {
        const response = await fetch('api/update_request_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: requestId, status: newStatus })
        });
        const result = await response.json();
        if(result.success) {
            // Optionally reload requests or show a success message
        }
    }
</script>
</body>
</html>