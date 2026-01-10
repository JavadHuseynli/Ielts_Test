<?php
// Bu fayl yalnız admin istifadəçisi yaratmaq üçündür
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once "includes/db.php";

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    die("Verilənlər bazası bağlantısı yaratmaq mümkün olmadı!");
}

try {
    // Admin istifadəçisi üçün açıq şifrə (hash edilmədən)
    $admin_username = "admin";
    $admin_password = "admin123"; // Açıq şifrə

    // Əvvəlcə admin hesabını siləkki yoxlayaq
    $check_query = "SELECT id_users FROM users WHERE username = :username";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":username", $admin_username);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        $delete_query = "DELETE FROM users WHERE username = :username";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->bindParam(":username", $admin_username);
        $delete_stmt->execute();
        echo "Mövcud admin hesabı silindi.<br>";
    }
    
    // Yeni admin hesabını əlavə et
    $insert_query = "INSERT INTO users (f_name, username, password, status, group_id) 
                    VALUES ('Admin', :username, :password, 'admin', NULL)";
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->bindParam(":username", $admin_username);
    $insert_stmt->bindParam(":password", $admin_password); // Açıq şifrə
    
    if ($insert_stmt->execute()) {
        echo "Admin hesabı uğurla yaradıldı!<br>";
        echo "İstifadəçi adı: <strong>admin</strong><br>";
        echo "Şifrə: <strong>admin123</strong><br>";
        echo "<br><a href='login.php' class='btn btn-primary'>Giriş səhifəsinə keç</a>";
    } else {
        echo "Admin hesabı yaradıla bilmədi!<br>";
    }
    
} catch (PDOException $e) {
    echo "Xəta: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Hesabı Yaratma</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 30px;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #0d6efd;
        }
        .btn {
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Hesabı Yaratma</h1>
        <p>Bu səhifə sistemdə admin hesabını açıq şəkildə yaratmaq üçün nəzərdə tutulub.</p>
        <p>Hesab yaradıldıqdan sonra login səhifəsinə keçə bilərsiniz.</p>
    </div>
</body>
</html>