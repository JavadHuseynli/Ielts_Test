<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
if (!isset($pageTitle)) {
    $pageTitle = "Təhsil Sistemi";
}
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Təhsil Sistemi</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['status'] == 'admin'): ?>
                        <ul class="navbar-nav me-auto">
                            <li class="nav-item">
                                <a class="nav-link" href="../admin/dashboard.php">Əsas Səhifə</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../admin/groups.php">Qruplar</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../admin/users.php">İstifadəçilər</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../admin/subjects.php">Fənlər</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../admin/questions.php">Suallar</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../admin/exams.php">İmtahanlar</a>
                            </li>
                        </ul>
                    <?php else: ?>
                        <ul class="navbar-nav me-auto">
                            <li class="nav-item">
                                <a class="nav-link" href="../student/dashboard.php">Əsas Səhifə</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../student/exams.php">İmtahanlar</a>
                            </li>
                        </ul>
                    <?php endif; ?>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <?php echo $_SESSION['name']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="../includes/logout.php">Çıxış</a></li>
                            </ul>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <div class="container mt-4">