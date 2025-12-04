<?php
require_once 'config.php';

$searchTerm = trim($_GET['q'] ?? '');

$baseSql = "
    SELECT 
        b.book_id,
        b.title,
        b.isbn,
        b.genre,
        b.publication_date,
        p.p_name AS publisher,
        b.status,
        GROUP_CONCAT(a.a_name SEPARATOR ', ') AS authors
    FROM Book b
    JOIN Publisher p ON b.p_name = p.p_name
    LEFT JOIN Author a ON a.book_id = b.book_id
    WHERE b.status IN ('AVAILABLE', 'ON_LOAN')
";

if ($searchTerm !== '') {
    $baseSql .= "
        AND (
            b.title    LIKE ?
            OR a.a_name  LIKE ?
            OR b.isbn    LIKE ?
            OR p.p_name  LIKE ?
            OR b.genre   LIKE ?
        )
    ";
}

$baseSql .= "
    GROUP BY b.book_id
    ORDER BY b.title
";

if ($searchTerm !== '') {
    $stmt = mysqli_prepare($conn, $baseSql);
    $like = '%' . $searchTerm . '%';
    mysqli_stmt_bind_param($stmt, 'sssss', $like, $like, $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $booksResult = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
} else {
    $booksResult = mysqli_query($conn, $baseSql);
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Search - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .table-hover tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">📚 Library System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="book_search.php">Search Books</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="member_dashboard.php">My Account</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h2 class="mb-4">Available Books</h2>
        
        <!-- Search Bar -->
        <div class="card mb-4">
            <div class="card-body">
                <form class="row g-2" method="get" action="book_search.php">
                    <div class="col-md-8">
                        <input type="text"
                            class="form-control"
                            id="searchInput"
                            name="q"
                            placeholder="Search by title, author, ISBN, publisher, or genre..."
                            value="<?= htmlspecialchars($searchTerm) ?>">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </div>
                    <div class="col-md-2 d-grid">
                        <a href="book_search.php" class="btn btn-outline-secondary">Clear</a>
                    </div>
                </form>
            </div>
        </div>




        <!-- Books Table -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Book Catalog</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="booksTable">
                        <thead>
                            <tr>
                                <th>ISBN</th>
                                <th>Title</th>
                                <th>Author(s)</th>
                                <th>Genre</th>
                                <th>Publisher</th>
                                <th>Publication Date</th>
                                <th>status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$booksResult || mysqli_num_rows($booksResult) === 0): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No books found.</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($book = mysqli_fetch_assoc($booksResult)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($book['isbn']) ?></td>
                                        <td><strong><?= htmlspecialchars($book['title']) ?></strong></td>
                                        <td><?= htmlspecialchars($book['authors'] ?: 'N/A') ?></td>
                                        <td><?= htmlspecialchars($book['genre'] ?: 'N/A') ?></td>
                                        <td><?= htmlspecialchars($book['publisher']) ?></td>
                                        <td><?= htmlspecialchars($book['publication_date']) ?></td>
                                        <td><?= htmlspecialchars($book['status'] ?? '') ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>

                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>


    </script>
</body>
</html>