<?php
require_once 'includes/db.php';

// JSON faylını oxu
$jsonFile = 'json/users.json';
if (!file_exists($jsonFile)) {
    die("JSON faylı tapılmadı: $jsonFile\n");
}

$jsonData = file_get_contents($jsonFile);
$students = json_decode($jsonData, true);

if (!$students) {
    die("JSON faylı oxuna bilmədi və ya səhv formatlıdır\n");
}

// Verilənlər bazası bağlantısı
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Verilənlər bazasına qoşulmaq mümkün olmadı\n");
}

echo "Ümumi tələbə sayı: " . count($students) . "\n";
echo "İdxal başladı...\n\n";

$success_count = 0;
$error_count = 0;
$errors = [];

foreach ($students as $index => $student) {
    try {
        // Qrup ID-ni tap
        $group_number = $student['qrup'];
        $query = "SELECT id_student_group FROM student_group WHERE group_number = :group_number";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':group_number', $group_number);
        $stmt->execute();

        $group = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$group) {
            // Əgər qrup yoxdursa, yarat
            $insert_group = "INSERT INTO student_group (group_number) VALUES (:group_number)";
            $stmt_group = $db->prepare($insert_group);
            $stmt_group->bindParam(':group_number', $group_number);
            $stmt_group->execute();
            $group_id = $db->lastInsertId();
            echo "Yeni qrup yaradıldı: $group_number (ID: $group_id)\n";
        } else {
            $group_id = $group['id_student_group'];
        }

        // Tam ad (soyad, ad, ata adı)
        $full_name = trim($student['soyad']) . ' ' . trim($student['ad']) . ' ' . trim($student['ata_adi']);
        $full_name = strtoupper($full_name);

        // Username və parol
        $username = $student['username'];
        $password = $student['parol'];

        // Şifrəni hash-lə (bcrypt)
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // İstifadəçini əlavə et
        $insert_user = "INSERT INTO users (f_name, username, password, status, group_id)
                        VALUES (:f_name, :username, :password, 'student', :group_id)";
        $stmt_user = $db->prepare($insert_user);
        $stmt_user->bindParam(':f_name', $full_name);
        $stmt_user->bindParam(':username', $username);
        $stmt_user->bindParam(':password', $hashed_password);
        $stmt_user->bindParam(':group_id', $group_id);

        if ($stmt_user->execute()) {
            $success_count++;
            if ($success_count % 50 == 0) {
                echo "İdxal edildi: $success_count tələbə...\n";
            }
        }

    } catch (PDOException $e) {
        $error_count++;
        $error_msg = "Xəta (sıra " . ($index + 1) . ", " . ($student['ad'] ?? 'N/A') . "): " . $e->getMessage();
        $errors[] = $error_msg;

        // Username duplicate error olsa göstər
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo "XƏBƏRDARLIQ: Username artıq mövcuddur - " . ($student['username'] ?? 'N/A') . "\n";
        }
    }
}

echo "\n=== İDXAL TAMAMLANDI ===\n";
echo "Uğurlu: $success_count tələbə\n";
echo "Xətalı: $error_count tələbə\n";

if (!empty($errors)) {
    echo "\n=== XƏTALAR ===\n";
    foreach ($errors as $error) {
        echo $error . "\n";
    }
}

echo "\nBaşa çatdı!\n";
?>
