<?php
require_once "includes/db.php";

$database = new Database();
$db = $database->getConnection();

// Check if RüfətS424 exists
$check_query = "SELECT id_users, f_name, username FROM users WHERE username = :username";
$check_stmt = $db->prepare($check_query);
$username_to_check = "RüfətS424";
$check_stmt->bindParam(':username', $username_to_check);
$check_stmt->execute();
$existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    echo "✓ İstifadəçi artıq mövcuddur:\n";
    echo "ID: " . $existing['id_users'] . "\n";
    echo "Ad: " . $existing['f_name'] . "\n";
    echo "Username: " . $existing['username'] . "\n";
} else {
    echo "✗ İstifadəçi tapılmadı. Əlavə edilir...\n\n";

    // Student data
    $f_name = "DƏMİROV RÜFƏT SAKİN OĞLU";
    $username = "RüfətS424";
    $password = "RüfətS424";
    $group_id = 54; // 424

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    try {
        $query = "INSERT INTO users (f_name, username, password, status, group_id)
                  VALUES (:f_name, :username, :password, 'student', :group_id)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':f_name', $f_name);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':group_id', $group_id);

        if ($stmt->execute()) {
            echo "✓ İstifadəçi uğurla əlavə edildi!\n";
            echo "Ad: $f_name\n";
            echo "Username: $username\n";
            echo "Parol: $password\n";
            echo "Qrup: 424\n";
        }
    } catch (PDOException $e) {
        echo "✗ Xəta: " . $e->getMessage() . "\n";
    }
}
?>
