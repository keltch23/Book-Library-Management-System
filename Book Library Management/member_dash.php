<?php
session_start();
require_once 'db_connect.php';
$first_login = isset($_SESSION['first_login']) ? $_SESSION['first_login'] : 0;
if (!isset($_SESSION['member_logged_in']) || !$_SESSION['member_logged_in']) {
    header("Location: login_signup.php");
    exit();
}
$force_reset = ($first_login == 1);
$member_id = $_SESSION['member_id'];
$member_name = isset($_SESSION['member_name']) ? $_SESSION['member_name'] : 'Member';

// Helper: Check for active loan or unpaid fine
function memberHasActiveLoanOrFine($pdo, $member_id) {
    // Check for active loan
    $loanStmt = $pdo->prepare("SELECT COUNT(*) FROM lending_transactions WHERE member_id = ? AND return_date IS NULL");
    $loanStmt->execute([$member_id]);
    $activeLoans = $loanStmt->fetchColumn();

    // Check for unpaid fines
    $fineStmt = $pdo->prepare("SELECT COUNT(*) FROM fines WHERE member_id = ? AND status = 'Unpaid'");
    $fineStmt->execute([$member_id]);
    $unpaidFines = $fineStmt->fetchColumn();

    return ($activeLoans > 0 || $unpaidFines > 0);
}

// Handle borrow request: Insert a request for admin approval
if (isset($_POST['borrow_book_isbn'])) {
    // Prevent if member has active loan or unpaid fine
    if (memberHasActiveLoanOrFine($pdo, $member_id)) {
        echo "<script>alert('You cannot borrow a new book while you have an active loan or unpaid fine.');location.href='member_dash.php';</script>";
        exit;
    }
    $isbn = $_POST['borrow_book_isbn'];
    $stmt = $pdo->prepare("SELECT id FROM books WHERE isbn = ?");
    $stmt->execute([$isbn]);
    $book = $stmt->fetch();
    if ($book) {
        // Prevent duplicate pending requests
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrow_requests WHERE member_id=? AND book_id=? AND status='Pending' AND type='borrow'");
        $stmt->execute([$member_id, $book['id']]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO borrow_requests (member_id, book_id, type, status) VALUES (?, ?, 'borrow', 'Pending')");
            $stmt->execute([$member_id, $book['id']]);
        }
    }
    echo "<script>alert('Please collect book at the library.');location.href='member_dash.php';</script>";
    exit;
}

// Handle return request: Insert a request for admin approval
if (isset($_POST['return_copy_id'])) {
    $copy_id_str = $_POST['return_copy_id'];
    // Get the numeric book_id for this copy
    $stmt = $pdo->prepare("SELECT book_id FROM book_copies WHERE copy_id = ?");
    $stmt->execute([$copy_id_str]);
    $book_id = $stmt->fetchColumn();
    if ($book_id) {
        // Prevent duplicate pending requests
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrow_requests WHERE member_id=? AND book_id=? AND status='Pending' AND type='return'");
        $stmt->execute([$member_id, $book_id]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO borrow_requests (member_id, book_id, type, status) VALUES (?, ?, 'return', 'Pending')");
            $stmt->execute([$member_id, $book_id]);
        }
    }
    echo "<script>alert('Please submit book to the library.');location.href='member_dash.php';</script>";
    exit;
}

// Handle return all request (optional, for bulk returns)
if (isset($_POST['return_all_loans'])) {
    $stmt = $pdo->prepare("SELECT bc.id AS copy_id, bc.copy_id AS copy_code, bc.book_id 
        FROM lending_transactions lt
        JOIN book_copies bc ON lt.copy_id = bc.id
        WHERE lt.member_id = ? AND lt.return_date IS NULL");
    $stmt->execute([$member_id]);
    $copyRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($copyRows as $row) {
        $copy_code = $row['copy_code'];
        $book_id = $row['book_id'];
        // Prevent duplicate pending requests for each book
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrow_requests WHERE member_id=? AND book_id=? AND status='Pending' AND type='return'");
        $stmt->execute([$member_id, $book_id]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO borrow_requests (member_id, book_id, type, status) VALUES (?, ?, 'return', 'Pending')");
            $stmt->execute([$member_id, $book_id]);
        }
    }
    echo "<script>alert('Please submit all books to the library.');location.href='member_dash.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Library Member Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dash.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.344.0/lucide.min.css" rel="stylesheet">
    <style>
        .data-table th, .data-table td {
            padding: 8px 12px;
            text-align: left;
            vertical-align: middle;
        }
        .data-table th {
            background: #2563eb;
            color: #fff;
            font-weight: 600;
            border: none;
            font-size: 1rem;
            text-align: left;
            padding-left: 20px;
        }
        .data-table td {
            border-bottom: 1px solid #f1f1f1;
            font-size: 1rem;
            text-align: left;
        }
        .msg-success {
            color: #2563eb;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        #forceResetModal .modal-content p {
            color: #2563eb !important;
            font-weight: 500;
        }
        #forceResetModal .modal-content button[type="submit"] {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 8px 20px;
            font-weight: 500;
            cursor: pointer;
            margin-top: 10px;
            transition: background 0.2s;
        }
        #forceResetModal .modal-content button[type="submit"]:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>
<div class="app">
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <button class="menu-btn" onclick="toggleSidebar()">
                <i data-lucide="menu"></i>
            </button>
            <div class="logo">
                <i data-lucide="star" class="logo-icon"></i>
                <span class="logo-text">VF-Library</span>
            </div>
        </div>
        <div class="header-right">
            <!-- Removed user-avatar from header -->
        </div>
    </header>
    <div class="main-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="compose-section">
                <button class="compose-btn" onclick="showCompose()">
                    <i data-lucide="plus"></i>
                    <span class="compose-text">Request</span>
                </button>
            </div>
            <nav class="sidebar-nav">
                <button class="nav-item" data-section="available" onclick="setActiveSection('available')">
                    <i data-lucide="check-circle"></i>
                    <span class="nav-text">Available Books</span>
                </button>
                <button class="nav-item active" data-section="my-books" onclick="setActiveSection('my-books')">
                    <i data-lucide="book-open"></i>
                    <span class="nav-text">My Books</span>
                </button>
                <button class="nav-item" data-section="fine" onclick="setActiveSection('fine')">
                    <i data-lucide="dollar-sign"></i>
                    <span class="nav-text">My Fines</span>
                </button>
                <button class="nav-item" data-section="history" onclick="setActiveSection('history')">
                    <i data-lucide="history"></i>
                    <span class="nav-text">My History</span>
                </button>
                <button class="nav-item" data-section="mail" onclick="setActiveSection('mail')">
                    <i data-lucide="mail"></i>
                    <span class="nav-text">Mail</span>
                </button>
                <button class="nav-item" data-section="updates" onclick="setActiveSection('updates')">
                    <i data-lucide="megaphone"></i>
                    <span class="nav-text">Updates</span>
                </button>
            </nav>
            <!-- Footer with avatar, name, arrow, and logout -->
            <div class="sidebar-footer" style="display:flex;align-items:center;gap:10px;margin-top:2rem;position:relative;">
                <div class="user-avatar" style="background:#2563eb;color:#fff;width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;font-weight:600;">
                    <?=htmlspecialchars(strtoupper($member_name[0]))?>
                </div>
                <div class="storage-info" style="font-weight:500;">
                    <?=htmlspecialchars($member_name)?>
                </div>
                <button id="memberProfileBtn" style="background:none;border:none;cursor:pointer;padding:0;margin-left:4px;">
                    <svg style="width:18px;height:18px;transform:rotate(180deg);" viewBox="0 0 24 24"><path fill="#222" d="M7 14l5-5 5 5z"/></svg>
                </button>
                <div id="memberDropdown" style="display:none;position:absolute;bottom:110%;left:0;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.12);padding:8px 0;min-width:120px;z-index:100;">
                    <a href="member_logout.php" style="display:block;padding:10px 18px;color:#222;text-decoration:none;font-weight:500;">Logout</a>
                </div>
            </div>
        </div>
        <!-- Main Content -->
        <div class="content-area">
            <!-- My Books View -->
            <div class="view books-view active" id="my-books-view">
                <div class="books-container">
                    <div class="books-list" style="max-height: 70vh; overflow-y: auto; padding: 16px;">
                        <div class="toolbar">
                            <div class="toolbar-left">
                                <span style="font-weight:500;">My Borrowed Books</span>
                            </div>
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 32px;">
                            <?php
                            $stmt = $pdo->prepare("SELECT bc.copy_id, b.title, b.author, b.publisher, b.publication_year, b.genre, b.cover_image, lt.checkout_date, lt.due_date, bc.status
                                FROM lending_transactions lt
                                JOIN book_copies bc ON lt.copy_id = bc.id
                                JOIN books b ON bc.book_id = b.id
                                WHERE lt.member_id = ? AND lt.return_date IS NULL");
                            $stmt->execute([$member_id]);
                            $hasBooks = false;
                            while ($row = $stmt->fetch()):
                                $hasBooks = true;
                                $cover = $row['cover_image'] ? htmlspecialchars($row['cover_image']) : 'default_cover.png';
                            ?>
                            <div style="width:220px; background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); display:flex; flex-direction:column; align-items:center; padding:18px 12px 20px 12px; border:1px solid #eee;">
                                <img src="uploads/<?= $cover ?>" alt="Cover" style="width:180px; height:260px; object-fit:cover; border-radius:8px; box-shadow:0 1px 4px rgba(0,0,0,0.07); margin-bottom:16px;">
                                <div style="width:100%; display:flex; flex-direction:column; align-items:flex-start; gap:6px;">
                                    <div style="font-size:1.08em;font-weight:600;"><?=htmlspecialchars($row['title'])?></div>
                                    <div style="color:#555;"><b>Author:</b> <?=htmlspecialchars($row['author'])?></div>
                                    <div style="color:#777;"><b>Publisher:</b> <?=htmlspecialchars($row['publisher'])?></div>
                                    <div style="color:#777;"><b>Year:</b> <?=htmlspecialchars($row['publication_year'])?></div>
                                    <div style="color:#777;"><b>Genre:</b> <?=htmlspecialchars($row['genre'])?></div>
                                    <div style="color:#777;"><b>Copy ID:</b> <?=htmlspecialchars($row['copy_id'])?></div>
                                    <div style="color:#777;"><b>Checkout:</b> <?=htmlspecialchars($row['checkout_date'])?></div>
                                    <div style="color:#777;"><b>Due:</b> <?=htmlspecialchars($row['due_date'])?></div>
                                    <div style="margin:8px 0;">
                                        <span style="background:#fee2e2;color:#991b1b;padding:2px 10px;border-radius:12px;font-size:0.95em;">
                                            <?=htmlspecialchars($row['status'])?>
                                        </span>
                                    </div>
                                    <form method="post" style="width:100%;margin-top:8px;" onsubmit="return showReturnConfirmModal('<?=htmlspecialchars($row['copy_id'])?>');">
                                        <input type="hidden" name="return_copy_id" value="<?=htmlspecialchars($row['copy_id'])?>">
                                        <button type="submit" style="background:#ef4444;color:#fff;border:none;border-radius:6px;padding:8px 20px;font-weight:500;cursor:pointer;width:100%;">Return</button>
                                    </form>
                                </div>
                            </div>
                            <?php endwhile; ?>
                            <?php if (!$hasBooks): ?>
                                <div style="color:#888; width:100%; text-align:center; margin-top:32px;">No borrowed books.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Available Books View -->
            <div class="view books-view" id="available-view">
                <div class="books-container">
                    <div class="books-list" style="max-height: 70vh; overflow-y: auto; padding: 16px;">
                        <div class="toolbar">
                            <div class="toolbar-left">
                                <span style="font-weight:500;">Available Books</span>
                            </div>
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 32px;">
                            <?php
                            // Available books query
                            $stmt = $pdo->query("SELECT 
                                    b.isbn, 
                                    b.title, 
                                    b.author, 
                                    b.publisher, 
                                    b.publication_year, 
                                    b.genre, 
                                    b.cover_image, 
                                    COUNT(CASE WHEN bc.status = 'Available' THEN 1 END) as available_copies
                                FROM books b
                                JOIN book_copies bc ON bc.book_id = b.id
                                GROUP BY b.id");
                            while ($row = $stmt->fetch()):
                                $cover = $row['cover_image'] ? htmlspecialchars($row['cover_image']) : 'default_cover.png';
                                $available = (int)$row['available_copies'];
                            ?>
                            <div style="width:220px; background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); display:flex; flex-direction:column; align-items:center; padding:18px 12px 20px 12px; border:1px solid #eee;">
                                <img src="uploads/<?= $cover ?>" alt="Cover" style="width:180px; height:260px; object-fit:cover; border-radius:8px; box-shadow:0 1px 4px rgba(0,0,0,0.07); margin-bottom:16px;">
                                <div style="width:100%; display:flex; flex-direction:column; align-items:flex-start; gap:6px;">
                                    <div style="font-size:1.08em;font-weight:600;"><?=htmlspecialchars($row['title'])?></div>
                                    <div style="color:#555;"><b>Author:</b> <?=htmlspecialchars($row['author'])?></div>
                                    <div style="color:#777;"><b>Publisher:</b> <?=htmlspecialchars($row['publisher'])?></div>
                                    <div style="color:#777;"><b>Year:</b> <?=htmlspecialchars($row['publication_year'])?></div>
                                    <div style="color:#777;"><b>Genre:</b> <?=htmlspecialchars($row['genre'])?></div>
                                    <div style="margin:8px 0;">
                                        <?php if ($available > 0): ?>
                                            <span style="background:#d1fae5;color:#059669;padding:2px 10px;border-radius:12px;font-size:0.95em;">
                                                <?= $available ?> available
                                            </span>
                                        <?php else: ?>
                                            <span style="background:#fee2e2;color:#991b1b;padding:2px 10px;border-radius:12px;font-size:0.95em;">
                                                Unavailable
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <form id="borrowForm_<?=htmlspecialchars($row['isbn'])?>" method="post" onsubmit="return showBorrowConfirmModal('<?=htmlspecialchars($row['isbn'])?>');">
                                        <input type="hidden" name="borrow_book_isbn" value="<?=htmlspecialchars($row['isbn'])?>">
                                        <button 
                                            type="submit"
                                            class="borrow-btn"
                                            style="background:#2563eb;color:#fff;border:none;border-radius:6px;padding:8px 20px;font-weight:500;cursor:pointer;width:100%;"
                                            <?= $available == 0 ? 'disabled style="background:#ccc;cursor:not-allowed;width:100%;"' : '' ?>>
                                            Borrow
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Borrow Confirm Modal -->
<div id="borrowConfirmModal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.25);z-index:1002;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px 24px;border-radius:12px;max-width:400px;width:100%;box-shadow:0 4px 24px rgba(0,0,0,0.12);position:relative;">
        <div style="font-weight:600;font-size:1.1em;margin-bottom:12px;">Please collect book at the library.</div>
        <form id="borrowConfirmForm" method="post">
            <input type="hidden" name="borrow_book_isbn" id="borrowConfirmIsbn">
            <button type="submit" style="background:#2563eb;color:#fff;border:none;border-radius:6px;padding:8px 20px;font-weight:500;cursor:pointer;">OK</button>
        </form>
    </div>
</div>
<!-- Return Confirm Modal -->
<div id="returnConfirmModal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.25);z-index:1002;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px 24px;border-radius:12px;max-width:400px;width:100%;box-shadow:0 4px 24px rgba(0,0,0,0.12);position:relative;">
        <div style="font-weight:600;font-size:1.1em;margin-bottom:12px;">Please submit book to the library.</div>
        <form id="returnConfirmForm" method="post">
            <input type="hidden" name="return_copy_id" id="returnConfirmCopyId">
            <button type="submit" style="background:#2563eb;color:#fff;border:none;border-radius:6px;padding:8px 20px;font-weight:500;cursor:pointer;">OK</button>
        </form>
    </div>
</div>
<script>
function setActiveSection(section) {
    document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
    document.querySelectorAll('.view.books-view').forEach(view => view.classList.remove('active'));
    document.querySelector('.nav-item[data-section="' + section + '"]').classList.add('active');
    if (section === 'my-books') {
        document.getElementById('my-books-view').classList.add('active');
    } else if (section === 'available') {
        document.getElementById('available-view').classList.add('active');
    }
}

function showBorrowConfirmModal(isbn) {
    document.getElementById('borrowConfirmIsbn').value = isbn;
    document.getElementById('borrowConfirmModal').style.display = 'flex';
    return false;
}
function showReturnConfirmModal(copyId) {
    document.getElementById('returnConfirmCopyId').value = copyId;
    document.getElementById('returnConfirmModal').style.display = 'flex';
    return false;
}
document.addEventListener('DOMContentLoaded', function() {
    setActiveSection('available');

    const profileBtn = document.getElementById('memberProfileBtn');
    const dropdown = document.getElementById('memberDropdown');
    if (profileBtn && dropdown) {
        profileBtn.onclick = function(e) {
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            e.stopPropagation();
        };
        document.addEventListener('click', function() {
            dropdown.style.display = 'none';
        });
    }
});
if (window.lucide) window.lucide.createIcons();

// Inactivity auto-logout (30s inactivity, 5s countdown with "Stay Logged In" prompt)
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
        window.location.href = 'member_logout.php';
    } else {
        countdown--;
        countdownTimer = setTimeout(updateCountdown, 1000);
    }
}
document.addEventListener('mousemove', resetInactivityTimer);
document.addEventListener('keydown', resetInactivityTimer);
document.addEventListener('click', resetInactivityTimer);
resetInactivityTimer();
</script>
</body>
</html>