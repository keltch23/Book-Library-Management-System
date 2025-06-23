 
-- Library Management System: Complete Schema with Auto-ID Formatting and Aggregation View
CREATE DATABASE IF NOT EXISTS shearwater_db;
-- 1. Librarians Table
CREATE TABLE librarians (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL
);

-- 2. Members Table (with formatted Member ID)
CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_code VARCHAR(10) UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    membership_date DATE DEFAULT CURRENT_DATE,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
);

-- Trigger to auto-fill member_code as 'MBR###'
DELIMITER //
CREATE TRIGGER trg_members_autocode
BEFORE INSERT ON members
FOR EACH ROW
BEGIN
    DECLARE new_code VARCHAR(10);
    SET new_code = CONCAT('MBR', LPAD((SELECT IFNULL(MAX(id)+1,1) FROM members), 3, '0'));
    SET NEW.member_code = new_code;
END;
//
DELIMITER ;

-- 3. Book Metadata Table
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    isbn VARCHAR(10) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(100),
    publisher VARCHAR(100),
    publication_year YEAR,
    genre VARCHAR(50)
);

-- 4. Book Copies Table
CREATE TABLE book_copies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    copy_id VARCHAR(30) UNIQUE,
    status ENUM('Available', 'Borrowed', 'Lost') DEFAULT 'Available',
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

-- Trigger to auto-fill copy_id as 'CP#####'
DELIMITER //
CREATE TRIGGER trg_copy_id_autofill
BEFORE INSERT ON book_copies
FOR EACH ROW
BEGIN
    DECLARE new_copy_code VARCHAR(30);
    SET new_copy_code = CONCAT('CP', LPAD((SELECT IFNULL(MAX(id)+1,1) FROM book_copies), 5, '0'));
    SET NEW.copy_id = new_copy_code;
END;
//
DELIMITER ;

-- 5. Lending Transactions Table
CREATE TABLE lending_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    copy_id INT NOT NULL,
    member_id INT NOT NULL,
    checkout_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE DEFAULT NULL,
    FOREIGN KEY (copy_id) REFERENCES book_copies(id),
    FOREIGN KEY (member_id) REFERENCES members(id)
);

-- 6. Fines Table
CREATE TABLE fines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    member_id INT NOT NULL,
    amount DECIMAL(6,2) NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('Paid', 'Unpaid') DEFAULT 'Unpaid',
    FOREIGN KEY (transaction_id) REFERENCES lending_transactions(id),
    FOREIGN KEY (member_id) REFERENCES members(id)
);

-- 7. Book Status Aggregation View
CREATE VIEW book_status_summary AS
SELECT 
    b.id AS book_id,
    b.title,
    b.isbn,
    COUNT(*) AS total_copies,
    SUM(CASE WHEN bc.status = 'Available' THEN 1 ELSE 0 END) AS available,
    SUM(CASE WHEN bc.status = 'Borrowed' THEN 1 ELSE 0 END) AS borrowed,
    SUM(CASE WHEN bc.status = 'Lost' THEN 1 ELSE 0 END) AS lost
FROM books b
JOIN book_copies bc ON b.id = bc.book_id
GROUP BY b.id;

-- 8. Active Loans Report
CREATE VIEW active_loans_report AS
SELECT 
    b.title AS book_title,
    m.full_name AS member_name,
    lt.due_date,
    CASE 
        WHEN lt.return_date IS NULL AND lt.due_date < CURDATE() THEN 'Overdue'
        ELSE 'On Time'
    END AS overdue_status
FROM lending_transactions lt
JOIN book_copies bc ON lt.copy_id = bc.id
JOIN books b ON bc.book_id = b.id
JOIN members m ON lt.member_id = m.id
WHERE lt.return_date IS NULL;

-- 9. Member History Report
CREATE VIEW member_history_report AS
SELECT 
    m.id AS member_id,
    m.full_name AS member_name,
    b.title AS book_title,
    bc.copy_id,
    lt.checkout_date,
    lt.return_date,
    IFNULL(f.amount, 0) AS fine_incurred,
    IFNULL(f.status, 'Paid') AS fine_status
FROM members m
JOIN lending_transactions lt ON m.id = lt.member_id
JOIN book_copies bc ON lt.copy_id = bc.id
JOIN books b ON bc.book_id = b.id
LEFT JOIN fines f ON lt.id = f.transaction_id;

