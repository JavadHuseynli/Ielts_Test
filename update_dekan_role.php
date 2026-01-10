<?php
/**
 * Verilənlər bazasına Dekan rolunu əlavə etmək üçün skript
 * Bu faylı bir dəfə işlədin və sonra silin
 */

require_once "includes/db.php";

$database = new Database();
$db = $database->getConnection();

try {
    // Users cədvəlinin status sütununu yeniləyirik və Dekan rolunu əlavə edirik
    $sql = "ALTER TABLE `users`
            MODIFY COLUMN `status` ENUM('admin', 'prorektor', 'dekan', 'kafedra', 'muellim', 'student')
            NOT NULL DEFAULT 'student'";

    $db->exec($sql);

    echo "<h1 style='color: green;'>✓ Uğurlu!</h1>";
    echo "<p>Dekan rolu verilənlər bazasına uğurla əlavə edildi.</p>";
    echo "<p><strong>İndi:</strong></p>";
    echo "<ol>";
    echo "<li>Bu faylı silin: <code>update_dekan_role.php</code></li>";
    echo "<li><a href='admin/users.php'>İstifadəçi İdarəetməsi</a> səhifəsinə keçin</li>";
    echo "<li>Yeni istifadəçi yaradarkən 'Dekan' rolunu seçə bilərsiniz</li>";
    echo "</ol>";

} catch (PDOException $e) {
    echo "<h1 style='color: red;'>✗ Xəta baş verdi!</h1>";
    echo "<p>Verilənlər bazası xətası: " . $e->getMessage() . "</p>";
    echo "<p>Əgər rol artıq əlavə edilibsə, bu xətanı nəzərə almayın və bu faylı silin.</p>";
}
?>
