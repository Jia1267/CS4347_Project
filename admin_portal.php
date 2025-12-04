<?php
require_once 'config.php';
// Load publishers once into an array
$publisherOptions = [];
$activeTab = $_GET['tab'] ?? 'dashboard'; // default tab
$pubResult = mysqli_query($conn, "SELECT p_name FROM Publisher ORDER BY p_name");
if ($pubResult) {
    while ($row = mysqli_fetch_assoc($pubResult)) {
        $publisherOptions[] = $row['p_name'];
    }
    mysqli_free_result($pubResult);
}

// Ensure only admin can access
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
/* =========================================================
 * BOOK ACTIONS (ADD / UPDATE / DELETE)
 * =======================================================*/

// ADD BOOK
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ( $_POST['action'] ?? '' ) === 'add_book') {

    $title            = trim($_POST['title'] ?? '');
    $authorRaw        = trim($_POST['author'] ?? '');
    $isbn             = trim($_POST['isbn'] ?? '');
    $genre            = trim($_POST['genre'] ?? '');
    $publisher        = trim($_POST['publisher'] ?? '');
    $publication_date = $_POST['publication_date'] ?? null;

    if ($title === '' || $isbn === '' || $publisher === '') {
        $_SESSION['error_message'] = 'Title, ISBN, and Publisher are required.';
        header('Location: admin_portal.php?tab=books#books');
        exit();
    }

    // Insert Book (status defaults to 'AVAILABLE')
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO Book (title, isbn, genre, publication_date, p_name)
         VALUES (?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
        $_SESSION['error_message'] = 'DB error (prepare add): ' . mysqli_error($conn);
        header('Location: admin_portal.php?tab=books#books');
        exit();
    }

    mysqli_stmt_bind_param(
        $stmt,
        'sssss',
        $title,
        $isbn,
        $genre,
        $publication_date,
        $publisher
    );

    if (!mysqli_stmt_execute($stmt)) {
        if (mysqli_errno($conn) == 1062) {
            $_SESSION['error_message'] = 'A book with this ISBN already exists.';
        } else {
            $_SESSION['error_message'] = 'Error inserting book: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
        header('Location: admin_portal.php?tab=books#books');
        exit();
    }

    $bookId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // Insert authors (comma-separated in "author" field)
    if ($authorRaw !== '') {
        $authors = array_filter(array_map('trim', explode(',', $authorRaw)));
        if (!empty($authors)) {
            $stmtA = mysqli_prepare(
                $conn,
                "INSERT INTO Author (book_id, a_name) VALUES (?, ?)"
            );
            if ($stmtA) {
                foreach ($authors as $a_name) {
                    mysqli_stmt_bind_param($stmtA, 'is', $bookId, $a_name);
                    mysqli_stmt_execute($stmtA);
                }
                mysqli_stmt_close($stmtA);
            }
        }
    }

    $_SESSION['success_message'] = 'Book added successfully.';
    header('Location: admin_portal.php?tab=books#books');
    exit();
}

// UPDATE BOOK
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ( $_POST['action'] ?? '' ) === 'update_book') {

    $bookId           = (int)($_POST['book_id'] ?? 0);
    $title            = trim($_POST['title'] ?? '');
    $authorRaw        = trim($_POST['author'] ?? '');
    $isbn             = trim($_POST['isbn'] ?? '');
    $genre            = trim($_POST['genre'] ?? '');
    $publisher        = trim($_POST['publisher'] ?? '');
    $publication_date = $_POST['publication_date'] ?? null;

    if ($bookId <= 0) {
        $_SESSION['error_message'] = 'Invalid book ID.';
        header('Location: admin_portal.php?tab=books#books');
        exit();
    }
    if ($title === '' || $isbn === '' || $publisher === '') {
        $_SESSION['error_message'] = 'Title, ISBN, and Publisher are required.';
        header('Location: admin_portal.php?tab=books#books');
        exit();
    }

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE Book
         SET title = ?, isbn = ?, genre = ?, publication_date = ?, p_name = ?
         WHERE book_id = ?"
    );
    if (!$stmt) {
        $_SESSION['error_message'] = 'DB error (prepare update): ' . mysqli_error($conn);
        header('Location: admin_portal.php?tab=books#books');
        exit();
    }

    mysqli_stmt_bind_param(
        $stmt,
        'sssssi',
        $title,
        $isbn,
        $genre,
        $publication_date,
        $publisher,
        $bookId
    );

    if (!mysqli_stmt_execute($stmt)) {
        if (mysqli_errno($conn) == 1062) {
            $_SESSION['error_message'] = 'A book with this ISBN already exists.';
        } else {
            $_SESSION['error_message'] = 'Error updating book: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
        header('Location: admin_portal.php?tab=books#books');
        exit();
    }
    mysqli_stmt_close($stmt);

    // Replace authors
    $stmtDel = mysqli_prepare($conn, "DELETE FROM Author WHERE book_id = ?");
    if ($stmtDel) {
        mysqli_stmt_bind_param($stmtDel, 'i', $bookId);
        mysqli_stmt_execute($stmtDel);
        mysqli_stmt_close($stmtDel);
    }

    if ($authorRaw !== '') {
        $authors = array_filter(array_map('trim', explode(',', $authorRaw)));
        if (!empty($authors)) {
            $stmtA = mysqli_prepare(
                $conn,
                "INSERT INTO Author (book_id, a_name) VALUES (?, ?)"
            );
            if ($stmtA) {
                foreach ($authors as $a_name) {
                    mysqli_stmt_bind_param($stmtA, 'is', $bookId, $a_name);
                    mysqli_stmt_execute($stmtA);
                }
                mysqli_stmt_close($stmtA);
            }
        }
    }

    $_SESSION['success_message'] = 'Book updated successfully.';
    header('Location: admin_portal.php?tab=books#books');
    exit();
}

// DELETE BOOK
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ( $_POST['action'] ?? '' ) === 'delete_book') {

    $bookId = (int)($_POST['book_id'] ?? 0);

    if ($bookId <= 0) {
        $_SESSION['error_message'] = 'Invalid book ID.';
        header('Location: admin_portal.php?tab=books#books');
        exit();
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM Book WHERE book_id = ?");
    if (!$stmt) {
        $_SESSION['error_message'] = 'DB error (prepare delete): ' . mysqli_error($conn);
        header('Location: admin_portal.php?tab=books#books');
        exit();
    }

    mysqli_stmt_bind_param($stmt, 'i', $bookId);

    if (!mysqli_stmt_execute($stmt)) {
        if (mysqli_errno($conn) == 1451) {
            $_SESSION['error_message'] =
                'Cannot delete book because it is used in loan history.';
        } else {
            $_SESSION['error_message'] = 'Error deleting book: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
        header('Location: admin_portal.php?tab=books#books');
        exit();
    }

    mysqli_stmt_close($stmt);
    $_SESSION['success_message'] = 'Book deleted successfully.';
    header('Location: admin_portal.php?tab=books#books');
    exit();
}

/* =========================================================
 * LOAN ACTIONS (CREATE / RETURN / RENEW / LOST)
 * =======================================================*/

// CREATE LOAN
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ( $_POST['action'] ?? '' ) === 'create_loan') {

    $email  = trim($_POST['member_email'] ?? '');
    $bookId = (int)($_POST['book_id'] ?? 0);

    if ($email === '' || $bookId <= 0) {
        $_SESSION['error_message'] = 'Member email and Book ID are required.';
        header('Location: admin_portal.php?tab=loans#loans');
        exit();
    }

    // 1) Member lookup
    $stmt = mysqli_prepare($conn, "SELECT member_id FROM Member WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $res    = mysqli_stmt_get_result($stmt);
    $member = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$member) {
        $_SESSION['error_message'] = 'Member not found.';
        header('Location: admin_portal.php?tab=loans#loans');
        exit();
    }
    $memberId = (int)$member['member_id'];

    // 2) Book lookup by book_id (must be AVAILABLE)
    $stmt = mysqli_prepare(
        $conn,
        "SELECT book_id, status FROM Book WHERE book_id = ?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $bookId);
    mysqli_stmt_execute($stmt);
    $res  = mysqli_stmt_get_result($stmt);
    $book = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$book) {
        $_SESSION['error_message'] = 'Book not found for that ID.';
        header('Location: admin_portal.php?tab=loans#loans');
        exit();
    }
    if ($book['status'] !== 'AVAILABLE') {
        $_SESSION['error_message'] = 'Book is not available.';
        header('Location: admin_portal.php?tab=loans#loans');
        exit();
    }

    // 3) Insert Loan: date_out = today, due_date = today + 14, fee = 5
    $sqlLoan = "
        INSERT INTO Loan (member_id, date_out, due_date, fee)
        VALUES (?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 5.00)
    ";
    $stmtLoan = mysqli_prepare($conn, $sqlLoan);
    mysqli_stmt_bind_param($stmtLoan, 'i', $memberId);

    if (!mysqli_stmt_execute($stmtLoan)) {
        $_SESSION['error_message'] = 'Error creating loan: ' . mysqli_error($conn);
        mysqli_stmt_close($stmtLoan);
        header('Location: admin_portal.php?tab=loans#loans');
        exit();
    }

    $loanId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmtLoan);

    // 4) LoanItem row
    $stmtItem = mysqli_prepare(
        $conn,
        "INSERT INTO LoanItem (loan_id, book_id, quantity) VALUES (?, ?, 1)"
    );
    if ($stmtItem) {
        mysqli_stmt_bind_param($stmtItem, 'ii', $loanId, $bookId);
        mysqli_stmt_execute($stmtItem);
        mysqli_stmt_close($stmtItem);
    }

    // 5) Mark book ON_LOAN
    $stmtBook = mysqli_prepare(
        $conn,
        "UPDATE Book SET status = 'ON_LOAN' WHERE book_id = ?"
    );
    if ($stmtBook) {
        mysqli_stmt_bind_param($stmtBook, 'i', $bookId);
        mysqli_stmt_execute($stmtBook);
        mysqli_stmt_close($stmtBook);
    }

    $_SESSION['success_message'] = 'Loan created successfully.';
    header('Location: admin_portal.php?tab=loans#loans');
    exit();
}



// RETURN LOAN
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ( $_POST['action'] ?? '' ) === 'return_loan') {

    $loanId = (int)($_POST['loan_id'] ?? 0);
    $bookId = (int)($_POST['book_id'] ?? 0);

    if ($loanId <= 0 || $bookId <= 0) {
        $_SESSION['error_message'] = 'Invalid loan or book ID.';
        header('Location: admin_portal.php?tab=loans#loans');
        exit();
    }

    $stmt = mysqli_prepare($conn, "UPDATE Loan SET return_date = CURDATE() WHERE loan_id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $loanId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $stmtB = mysqli_prepare($conn, "UPDATE Book SET status = 'AVAILABLE' WHERE book_id = ?");
    if ($stmtB) {
        mysqli_stmt_bind_param($stmtB, 'i', $bookId);
        mysqli_stmt_execute($stmtB);
        mysqli_stmt_close($stmtB);
    }

    $_SESSION['success_message'] = 'Loan marked as returned.';
    header('Location: admin_portal.php?tab=loans#loans');
    exit();
}

// RENEW LOAN (add 14 days)
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ( $_POST['action'] ?? '' ) === 'renew_loan') {

    $loanId = (int)($_POST['loan_id'] ?? 0);

    if ($loanId <= 0) {
        $_SESSION['error_message'] = 'Invalid loan ID.';
        header('Location: admin_portal.php?tab=loans#loans');
        exit();
    }

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE Loan
         SET due_date = DATE_ADD(due_date, INTERVAL 14 DAY)
         WHERE loan_id = ? AND return_date IS NULL"
    );
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $loanId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $_SESSION['success_message'] = 'Loan renewed for another 14 days.';
    header('Location: admin_portal.php?tab=loans#loans');
    exit();
}

// MARK LOST
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ( $_POST['action'] ?? '' ) === 'mark_lost') {

    $loanId = (int)($_POST['loan_id'] ?? 0);
    $bookId = (int)($_POST['book_id'] ?? 0);

    if ($loanId <= 0 || $bookId <= 0) {
        $_SESSION['error_message'] = 'Invalid loan or book ID.';
        header('Location: admin_portal.php?tab=loans#loans');
        exit();
    }

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE Loan
         SET return_date = CURDATE()
         WHERE loan_id = ? AND return_date IS NULL"
    );
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $loanId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $stmtB = mysqli_prepare(
        $conn,
        "UPDATE Book SET status = 'LOST' WHERE book_id = ?"
    );
    if ($stmtB) {
        mysqli_stmt_bind_param($stmtB, 'i', $bookId);
        mysqli_stmt_execute($stmtB);
        mysqli_stmt_close($stmtB);
    }

    $_SESSION['success_message'] = 'Book marked as lost.';
    header('Location: admin_portal.php?tab=loans#loans');
    exit();
}
/* ================== END BOOK ACTIONS ================== */

// CHANGE ROLE (admin <-> member)
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ( $_POST['action'] ?? '' ) === 'change_role') {

    $memberId = (int)($_POST['member_id'] ?? 0);
    $role     = trim($_POST['role'] ?? '');

    if ($memberId <= 0 || ($role !== 'admin' && $role !== 'member')) {
        $_SESSION['error_message'] = 'Invalid member ID or role.';
        header('Location: admin_portal.php?tab=users#users');
        exit();
    }

    // prevent an admin from demoting themselves
    $currentUserId   = (int)($_SESSION['member_id'] ?? 0);   // adjust name if needed
    $currentUserRole = $_SESSION['role'] ?? '';

    if ($memberId === $currentUserId &&
        $currentUserRole === 'admin' &&
        $role !== 'admin') {

        $_SESSION['error_message'] = 'You cannot change your own role.';
        header('Location: admin_portal.php?tab=users#users');
        exit();
    }

    $stmt = mysqli_prepare($conn, "UPDATE Member SET role = ? WHERE member_id = ?");
    if (!$stmt) {
        $_SESSION['error_message'] = 'DB error (prepare change_role): ' . mysqli_error($conn);
        header('Location: admin_portal.php?tab=users#users');
        exit();
    }

    mysqli_stmt_bind_param($stmt, 'si', $role, $memberId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $_SESSION['success_message'] = 'Role updated successfully.';
    header('Location: admin_portal.php?tab=users#users');
    exit();
}

// RESET PASSWORD (set to default)
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ( $_POST['action'] ?? '' ) === 'reset_password') {

    $memberId = (int)($_POST['member_id'] ?? 0);

    if ($memberId <= 0) {
        $_SESSION['error_message'] = 'Invalid member ID.';
        header('Location: admin_portal.php?tab=users#users');
        exit();
    }

    $newPassword  = 'password123';  // default reset password for demo
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE Member SET password_hash = ? WHERE member_id = ?"
    );
    if (!$stmt) {
        $_SESSION['error_message'] = 'DB error (prepare reset_password): ' . mysqli_error($conn);
        header('Location: admin_portal.php?tab=users#users');
        exit();
    }

    mysqli_stmt_bind_param($stmt, 'si', $passwordHash, $memberId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $_SESSION['success_message'] =
        'Password reset to default ("password123").';
        header('Location: admin_portal.php?tab=users#users');
    exit();
}


// ADD MEMBER
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ( $_POST['action'] ?? '' ) === 'add_member') {

    $fname = trim($_POST['fname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role  = trim($_POST['role']  ?? '');

    if ($fname === '' || $lname === '' || $email === '' || $phone === '' || $role === '') {
        $_SESSION['error_message'] = 'First name, last name, email, phone, and role are required.';
        header('Location: admin_portal.php?tab=users#users');
        exit();
    }

    // Default password for demo
    $defaultPassword = 'password123';
    $passwordHash    = password_hash($defaultPassword, PASSWORD_DEFAULT);

    $sql = "
        INSERT INTO Member (fname, lname, dob, address, phone, email, password_hash, role)
        VALUES (?, ?, NULL, NULL, ?, ?, ?, ?)
    ";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        $_SESSION['error_message'] = 'DB error (prepare add_member): ' . mysqli_error($conn);
        header('Location: admin_portal.php?tab=users#users');
        exit();
    }

    mysqli_stmt_bind_param(
        $stmt,
        'ssssss',
        $fname,
        $lname,
        $phone,
        $email,
        $passwordHash,
        $role
    );

    if (!mysqli_stmt_execute($stmt)) {
        if (mysqli_errno($conn) == 1062) {
            $_SESSION['error_message'] = 'Email or phone already exists.';
        } else {
            $_SESSION['error_message'] = 'Error adding member: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
        header('Location: admin_portal.php?tab=users#users');
        exit();
    }

    mysqli_stmt_close($stmt);

    $_SESSION['success_message'] =
        'Member added successfully. Default password is "password123".';
        header('Location: admin_portal.php?tab=users#users');
    exit();
}

// DELETE MEMBER
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ( $_POST['action'] ?? '' ) === 'delete_member') {

    $memberId = (int)($_POST['member_id'] ?? 0);

    if ($memberId <= 0) {
        $_SESSION['error_message'] = 'Invalid member ID.';
        header('Location: admin_portal.php?tab=users#users');
        exit();
    }

    // Don't allow deleting yourself (logged-in admin)
    $currentUserId = (int)($_SESSION['member_id'] ?? 0);
    if ($memberId === $currentUserId) {
        $_SESSION['error_message'] = 'You cannot delete your own account.';
        header('Location: admin_portal.php?tab=users#users');
        exit();
    }

    // Check if member has any loans
    $stmtCheck = mysqli_prepare($conn,
        "SELECT COUNT(*) AS loan_count FROM Loan WHERE member_id = ?"
    );
    if (!$stmtCheck) {
        $_SESSION['error_message'] = 'DB error (prepare check loans): ' . mysqli_error($conn);
        header('Location: admin_portal.php?tab=users#users');
        exit();
    }
    mysqli_stmt_bind_param($stmtCheck, 'i', $memberId);
    mysqli_stmt_execute($stmtCheck);
    $resCheck = mysqli_stmt_get_result($stmtCheck);
    $rowCheck = mysqli_fetch_assoc($resCheck);
    mysqli_stmt_close($stmtCheck);

    if (!empty($rowCheck['loan_count']) && (int)$rowCheck['loan_count'] > 0) {
        $_SESSION['error_message'] =
            'Cannot delete member because they have loan records.';
            header('Location: admin_portal.php?tab=users#users');
        exit();
    }

    // Actually delete the member (no loans blocking)
    $stmt = mysqli_prepare($conn, "DELETE FROM Member WHERE member_id = ?");
    if (!$stmt) {
        $_SESSION['error_message'] = 'DB error (prepare delete_member): ' . mysqli_error($conn);
        header('Location: admin_portal.php?tab=users#users');
        exit();
    }

    mysqli_stmt_bind_param($stmt, 'i', $memberId);

    if (!mysqli_stmt_execute($stmt)) {
        $_SESSION['error_message'] =
            'Error deleting member: ' . mysqli_error($conn);
        mysqli_stmt_close($stmt);
        header('Location: admin_portal.php?tab=users#users');
        exit();
    }

    mysqli_stmt_close($stmt);

    $_SESSION['success_message'] = 'Member deleted successfully.';
    header('Location: admin_portal.php?tab=users#users');
    exit();
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Get counts for dashboard
$countBooks = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM Book"))['total'];
$countMembers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM Member"))['total'];
$countActiveLoans = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM Loan WHERE return_date IS NULL"))['total'];
$countOverdue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM Loan WHERE return_date IS NULL AND due_date < CURDATE()"))['total'];

// Get all publishers for dropdown
$publishers = mysqli_query($conn, "SELECT p_name FROM Publisher ORDER BY p_name");


// Get all books (any status)
$booksSql = "
    SELECT 
        b.book_id,
        b.title,
        b.isbn,
        b.genre,
        b.publication_date,
        b.p_name AS publisher,
        b.status,
        GROUP_CONCAT(a.a_name SEPARATOR ', ') AS authors
    FROM Book b
    LEFT JOIN Author a ON a.book_id = b.book_id
    GROUP BY 
        b.book_id,
        b.title,
        b.isbn,
        b.genre,
        b.publication_date,
        b.p_name,
        b.status
    ORDER BY b.title
";

$booksResult = mysqli_query($conn, $booksSql);

// Get all loans
$loansSql = "
    SELECT
        l.loan_id,
        l.date_out,
        l.due_date,
        l.return_date,
        CONCAT(m.fname, ' ', m.lname) AS member_name,
        b.title AS books,
        b.book_id,
        b.status AS book_status
    FROM Loan l
    JOIN Member   m  ON l.member_id = m.member_id
    JOIN LoanItem li ON l.loan_id   = li.loan_id
    JOIN Book     b  ON li.book_id  = b.book_id
    ORDER BY l.date_out DESC
";
$loansResult = mysqli_query($conn, $loansSql);


// Get all members
$membersSql = "SELECT member_id, fname, lname, email, phone, role FROM Member ORDER BY member_id";
$membersResult = mysqli_query($conn, $membersSql);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        .nav-tabs .nav-link {
            color: #495057;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            color: #28a745;
            font-weight: bold;
        }
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .table-hover tbody tr:hover {
            background-color: rgba(40, 167, 69, 0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">📚 Library Admin Portal</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="book_search.php">Search Books</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="member_dashboard.php">My Account</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid my-4">
        <h2 class="mb-4">Admin Portal</h2>
        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button
                    class="nav-link <?= $activeTab === 'dashboard' ? 'active' : '' ?>"
                    id="dashboard-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#dashboard"
                    type="button"
                    role="tab"
                    aria-controls="dashboard"
                    aria-selected="<?= $activeTab === 'dashboard' ? 'true' : 'false' ?>">
                    📊 Dashboard
                </button>
            </li>

            <li class="nav-item" role="presentation">
                <button
                    class="nav-link <?= $activeTab === 'books' ? 'active' : '' ?>"
                    id="books-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#books"
                    type="button"
                    role="tab"
                    aria-controls="books"
                    aria-selected="<?= $activeTab === 'books' ? 'true' : 'false' ?>">
                    📚 Book Management
                </button>
            </li>

            <li class="nav-item" role="presentation">
                <button
                    class="nav-link <?= $activeTab === 'loans' ? 'active' : '' ?>"
                    id="loans-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#loans"
                    type="button"
                    role="tab"
                    aria-controls="loans"
                    aria-selected="<?= $activeTab === 'loans' ? 'true' : 'false' ?>">
                    🔄 Loan Management
                </button>
            </li>

            <li class="nav-item" role="presentation">
                <button
                    class="nav-link <?= $activeTab === 'users' ? 'active' : '' ?>"
                    id="users-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#users"
                    type="button"
                    role="tab"
                    aria-controls="users"
                    aria-selected="<?= $activeTab === 'users' ? 'true' : 'false' ?>">
                    👥 Member Management
                </button>
            </li>
        </ul>



        <!-- Tab Content -->
        <div class="tab-content" id="myTabContent">
            
          
    <!-- DASHBOARD TAB -->
    <div class="tab-pane fade <?= $activeTab === 'dashboard' ? 'show active' : '' ?>" id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card" style="border-left-color: #667eea;">
                            <div class="card-body">
                                <h6 class="text-muted">Total Books</h6>
                                <h3><?= $countBooks ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card" style="border-left-color: #20c997;">
                            <div class="card-body">
                                <h6 class="text-muted">Total Members</h6>
                                <h3><?= $countMembers ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card" style="border-left-color: #ffc107;">
                            <div class="card-body">
                                <h6 class="text-muted">All Loans</h6>
                                <h3><?= $countActiveLoans ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card" style="border-left-color: #dc3545;">
                            <div class="card-body">
                                <h6 class="text-muted">Overdue Loans</h6>
                                <h3><?= $countOverdue ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div> 

            <!-- TAB 2: BOOK MANAGEMENT -->
    <div class="tab-pane fade <?= $activeTab === 'books' ? 'show active' : '' ?>" id="books" role="tabpanel" aria-labelledby="books-tab">
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Add Book</h5>
        </div>
        <div class="card-body">
            <form method="post" action="admin_portal.php#books">
                <input type="hidden" name="action" value="add_book">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Author(s)</label>
                        <input
                            type="text"
                            name="author"
                            class="form-control"
                            placeholder="Separate multiple authors with commas"
                            required
                        >
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">ISBN *</label>
                        <input type="text" name="isbn" class="form-control" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Genre</label>
                        <input type="text" name="genre" class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Publisher *</label>
                        <select name="publisher" class="form-select" required>
                            <option value="">-- Select publisher --</option>
                            <?php foreach ($publisherOptions as $pName): ?>
                                <option value="<?= htmlspecialchars($pName) ?>">
                                    <?= htmlspecialchars($pName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Publication Date</label>
                        <input type="date" name="publication_date" class="form-control">
                    </div>
                </div>

                <button type="submit" class="btn btn-success">Add Book</button>
                <button type="reset" class="btn btn-secondary">Clear</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">All Books</h5>
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Book ID</th>
                        <th>ISBN</th>
                        <th>Title</th>
                        <th>Author(s)</th>
                        <th>Genre</th>
                        <th>Publisher</th>
                        <th>Publication Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$booksResult || mysqli_num_rows($booksResult) === 0): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">No books found.</td>
                    </tr>
                <?php else: ?>
                    <?php while ($b = mysqli_fetch_assoc($booksResult)): ?>
                        <tr>
                            <td><?= htmlspecialchars($b['book_id']) ?></td>
                            <td><?= htmlspecialchars($b['isbn']) ?></td>
                            <td><?= htmlspecialchars($b['title']) ?></td>
                            <td><?= htmlspecialchars($b['authors'] ?: 'N/A') ?></td>
                            <td><?= htmlspecialchars($b['genre'] ?: 'N/A') ?></td>
                            <td><?= htmlspecialchars($b['publisher']) ?></td>
                            <td><?= htmlspecialchars($b['publication_date']) ?></td>
                            <?php
                                // Map DB status to label + Bootstrap color
                                $statusLabel = 'Unknown';
                                $statusClass = 'secondary';

                                if ($b['status'] === 'AVAILABLE') {
                                    $statusLabel = 'Available';
                                    $statusClass = 'success';
                                } elseif ($b['status'] === 'ON_LOAN') {
                                    $statusLabel = 'On Loan';
                                    $statusClass = 'warning';
                                } elseif ($b['status'] === 'LOST') {   // in case you ever use LOST
                                    $statusLabel = 'Lost';
                                    $statusClass = 'danger';
                                }
                            ?>
                            <td>
                                <span class="badge bg-<?= $statusClass ?>">
                                    <?= $statusLabel ?>
                                </span>
                            </td>

                            <td>
                                <!-- EDIT BUTTON: opens modal and passes data-* attributes -->
                                <button type="button"
                                        class="btn btn-sm btn-warning"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editBookModal"
                                        data-book-id="<?= $b['book_id'] ?>"
                                        data-title="<?= htmlspecialchars($b['title'], ENT_QUOTES) ?>"
                                        data-author="<?= htmlspecialchars($b['authors'] ?: '', ENT_QUOTES) ?>"
                                        data-isbn="<?= htmlspecialchars($b['isbn'], ENT_QUOTES) ?>"
                                        data-genre="<?= htmlspecialchars($b['genre'] ?: '', ENT_QUOTES) ?>"
                                        data-publisher="<?= htmlspecialchars($b['publisher'], ENT_QUOTES) ?>"
                                        data-publication-date="<?= htmlspecialchars($b['publication_date'], ENT_QUOTES) ?>">
                                    Edit
                                </button>

                                <!-- DELETE BUTTON: posts to PHP -->
                                <form method="post"
                                      action="admin_portal.php#books"
                                      class="d-inline"
                                      onsubmit="return confirm('Delete this book? This cannot be undone.');">
                                    <input type="hidden" name="action" value="delete_book">
                                    <input type="hidden" name="book_id" value="<?= $b['book_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- EDIT BOOK MODAL -->
    <div class="modal fade" id="editBookModal" tabindex="-1" aria-labelledby="editBookModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post" action="admin_portal.php#books">
                    <input type="hidden" name="action" value="update_book">
                    <input type="hidden" name="book_id" id="editBookId">

                    <div class="modal-header">
                        <h5 class="modal-title" id="editBookModalLabel">Edit Book</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Title *</label>
                                <input type="text" class="form-control" name="title" id="editTitle" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Author(s)</label>
                                <input type="text" class="form-control" name="author" id="editAuthor" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">ISBN *</label>
                                <input type="text" class="form-control" name="isbn" id="editIsbn" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Genre</label>
                                <input type="text" class="form-control" name="genre" id="editGenre">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Publisher *</label>
                                <select name="publisher" class="form-select" id="editPublisher" required>
                                    <option value="">-- Select publisher --</option>
                                    <?php foreach ($publisherOptions as $pName): ?>
                                        <option value="<?= htmlspecialchars($pName) ?>">
                                            <?= htmlspecialchars($pName) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Publication Date</label>
                                <input type="date" class="form-control" name="publication_date" id="editPublicationDate">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>


            <!-- TAB 3: LOAN MANAGEMENT -->
            <div class="tab-pane fade <?= $activeTab === 'loans' ? 'show active' : '' ?>" id="loans" role="tabpanel" aria-labelledby="loans-tab">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Process Loan / Return</h5>
                    </div>
                    <div class="card-body">
                    <form method="post" action="admin_portal.php#loans">
                        <input type="hidden" name="action" value="create_loan">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Member Email *</label>
                                <input type="email" name="member_email" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                            <label class="form-label">Book ID *</label>
                            <input type="number" name="book_id" class="form-control" required>
                            <small class="text-muted">Use the Book ID from the Book Management table.</small>
                        </div>
                        </div>
                        <button type="submit" class="btn btn-success">Create Loan</button>
                    </form>
                    
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">All Loans</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Loan ID</th>
                                            <th>Member</th>
                                            <th>Book</th>
                                            <th>Loan Date</th>
                                            <th>Due Date</th>
                                            <th>Return Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($loansResult && mysqli_num_rows($loansResult) > 0): ?>
                                            <?php while ($loan = mysqli_fetch_assoc($loansResult)): ?>
                                                <?php
                                                    $isReturned  = !is_null($loan['return_date']);
                                                    $statusLabel = $isReturned ? 'Returned' : 'On Loan';
                                                    $statusClass = $isReturned ? 'success'  : 'warning';
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($loan['loan_id']) ?></td>
                                                    <td><?= htmlspecialchars($loan['member_name']) ?></td>
                                                    <td><?= htmlspecialchars($loan['books'] ?: 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($loan['date_out']) ?></td>
                                                    <td><?= htmlspecialchars($loan['due_date']) ?></td>
                                                    <td><?= htmlspecialchars($loan['return_date'] ?? '-') ?></td>

                                                    <!-- Status badge -->
                                                    <td>
                                                        <span class="badge bg-<?= $statusClass ?>">
                                                            <?= $statusLabel ?>
                                                        </span>
                                                    </td>

                                                    <!-- Actions: REAL forms that hit PHP -->
                                                    <td>
                                                        <!-- Mark Returned -->
                                                        <form method="post"
                                                            action="admin_portal.php#loans"
                                                            class="d-inline"
                                                            onsubmit="return confirm('Mark this loan as returned?');">
                                                            <input type="hidden" name="action" value="return_loan">
                                                            <input type="hidden" name="loan_id" value="<?= $loan['loan_id'] ?>">
                                                            <input type="hidden" name="book_id" value="<?= $loan['book_id'] ?>">
                                                            <button type="submit"
                                                                    class="btn btn-sm btn-outline-success"
                                                                    <?= $isReturned ? 'disabled' : '' ?>>
                                                                Mark Returned
                                                            </button>
                                                        </form>

                                                        <!-- Mark Lost -->
                                                        <form method="post"
                                                            action="admin_portal.php#loans"
                                                            class="d-inline"
                                                            onsubmit="return confirm('Mark this book as LOST and remove it from the catalog?');">
                                                            <input type="hidden" name="action" value="mark_lost">
                                                            <input type="hidden" name="loan_id" value="<?= $loan['loan_id'] ?>">
                                                            <input type="hidden" name="book_id" value="<?= $loan['book_id'] ?>">
                                                            <button type="submit"
                                                                    class="btn btn-sm btn-outline-danger"
                                                                    <?= $isReturned ? 'disabled' : '' ?>>
                                                                Mark Lost
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <!-- 7 columns in header ⇒ colspan=7 -->
                                                <td colspan="7" class="text-center text-muted">
                                                    No loans found.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>


                </div>
            </div>

            <!-- TAB 4: MEMBER MANAGEMENT -->
            <div class="tab-pane fade <?= $activeTab === 'users' ? 'show active' : '' ?>" id="users" role="tabpanel" aria-labelledby="users-tab">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Add  Member</h5>
                    </div>
                    <div class="card-body">
                    <form method="post" action="admin_portal.php#users">
                        <input type="hidden" name="action" value="add_member">

                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <label class="form-label">First Name *</label>
                                <input type="text" name="fname" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-4">
                                <label class="form-label">Last Name *</label>
                                <input type="text" name="lname" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-4">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Phone *</label>
                                <input type="text" name="phone" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Role *</label>
                                <select name="role" class="form-select" required>
                                    <option value="">Select Role</option>
                                    <option value="member">Member</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success">Save Member</button>
                        <button type="reset" class="btn btn-secondary">Clear</button>
                    </form>


                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">All Members</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($membersResult && mysqli_num_rows($membersResult) > 0): ?>
                                    <?php while ($member = mysqli_fetch_assoc($membersResult)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($member['member_id']); ?></td>
                                            <td><?php echo htmlspecialchars($member['fname'] . ' ' . $member['lname']); ?></td>
                                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                                            <td>
                                                <?php
                                                    $role = $member['role'] ?? 'member';
                                                    $roleClass = $role === 'admin' ? 'danger' : 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $roleClass ?>">
                                                    <?= ucfirst(htmlspecialchars($role)) ?>
                                                </span>
                                            </td>

                                            <td>
                                                <!-- Change role (admin <-> member) -->
                                                <form method="post"
                                                    action="admin_portal.php#users"
                                                    class="d-inline">
                                                    <input type="hidden" name="action" value="change_role">
                                                    <input type="hidden" name="member_id" value="<?= $member['member_id'] ?>">
                                                    <select name="role"
                                                            class="form-select form-select-sm d-inline w-auto">
                                                        <option value="member" <?= $role === 'member' ? 'selected' : '' ?>>Member</option>
                                                        <option value="admin"  <?= $role === 'admin'  ? 'selected' : '' ?>>Admin</option>
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                                        Update Role
                                                    </button>
                                                </form>

                                                <!-- Reset password -->
                                                <form method="post"
                                                    action="admin_portal.php#users"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Reset password for this member to the default?');">
                                                    <input type="hidden" name="action" value="reset_password">
                                                    <input type="hidden" name="member_id" value="<?= $member['member_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-warning">
                                                        Reset Password
                                                    </button>
                                                </form>

                                                <!-- Delete Member -->
                                                <form method="post"
                                                    action="admin_portal.php#users"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Delete this member? This will fail if they have loans.');">
                                                    <input type="hidden" name="action" value="delete_member">
                                                    <input type="hidden" name="member_id" value="<?= $member['member_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>

                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No members found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>

                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editModal = document.getElementById('editBookModal');
            if (!editModal) return;

            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget; // the Edit button that was clicked

                // read data attributes from the button
                const bookId   = button.getAttribute('data-book-id');
                const title    = button.getAttribute('data-title') || '';
                const author   = button.getAttribute('data-author') || '';
                const isbn     = button.getAttribute('data-isbn') || '';
                const genre    = button.getAttribute('data-genre') || '';
                const publisher= button.getAttribute('data-publisher') || '';
                const pubDate  = button.getAttribute('data-publication-date') || '';

                // fill the modal form fields
                editModal.querySelector('#editBookId').value          = bookId;
                editModal.querySelector('#editTitle').value           = title;
                editModal.querySelector('#editAuthor').value          = author;
                editModal.querySelector('#editIsbn').value            = isbn;
                editModal.querySelector('#editGenre').value           = genre;
                editModal.querySelector('#editPublisher').value       = publisher;
                editModal.querySelector('#editPublicationDate').value = pubDate;
            });
        });
</script>

</body>
</html>