<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

checkLogin();

// Only admin can update database
if (!is_admin()) {
    die("Yalnız admin bu əməliyyatı edə bilər!");
}

$database = new Database();
$db = $database->getConnection();

echo "<h2>Database Yeniləmə - Təsdiq Sistemı</h2>";
echo "<p>Təsdiq sistemi üçün yeni field-lər əlavə edilir...</p>";

try {
    // Check and add new confirmation fields one by one
    $columns = [
        'confirmed_by_dekan' => "ALTER TABLE exams ADD COLUMN confirmed_by_dekan INT DEFAULT NULL AFTER confirmed_at",
        'confirmed_at_dekan' => "ALTER TABLE exams ADD COLUMN confirmed_at_dekan DATETIME DEFAULT NULL AFTER confirmed_by_dekan",
        'confirmed_by_kafedra' => "ALTER TABLE exams ADD COLUMN confirmed_by_kafedra INT DEFAULT NULL AFTER confirmed_at_dekan",
        'confirmed_at_kafedra' => "ALTER TABLE exams ADD COLUMN confirmed_at_kafedra DATETIME DEFAULT NULL AFTER confirmed_by_kafedra"
    ];

    foreach ($columns as $columnName => $sql) {
        // Check if column exists
        $checkQuery = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS
                       WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = 'exams'
                       AND COLUMN_NAME = :column_name";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([':column_name' => $columnName]);
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] == 0) {
            // Column doesn't exist, add it
            $db->exec($sql);
            echo "<p style='color: green;'>✓ Field əlavə edildi: $columnName</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Field artıq mövcuddur: $columnName</p>";
        }
    }

    // Migrate old confirmations to new fields
    echo "<p>Köhnə təsdiqləri köçürürük...</p>";

    // Move old confirmations to kafedra field
    $sql2 = "UPDATE exams
             SET confirmed_by_kafedra = confirmed_by,
                 confirmed_at_kafedra = confirmed_at
             WHERE confirmed_by IS NOT NULL AND confirmed_by_kafedra IS NULL";

    $stmt = $db->prepare($sql2);
    $stmt->execute();
    $count = $stmt->rowCount();

    echo "<p style='color: green;'>✓ $count təsdiq köçürüldü (Kafedra field-inə)</p>";

    echo "<h3 style='color: blue;'>Yeniləmə tamamlandı!</h3>";
    echo "<p><a href='exams.php'>İmtahanlar səhifəsinə qayıt</a></p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>Xəta: " . $e->getMessage() . "</p>";
}
?>
