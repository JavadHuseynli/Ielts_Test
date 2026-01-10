<?php
/**
 * DEKAN ROLUNU VERİLƏNLƏR BAZASINA ƏLAVƏ ET
 * Bu faylı bir dəfə işlədin və sonra silin
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once "includes/db.php";

$database = new Database();
$db = $database->getConnection();

echo "<html><head><meta charset='UTF-8'><style>
body { font-family: Arial; padding: 40px; background: #f5f5f5; }
.success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 20px; border-radius: 8px; margin: 20px 0; }
.error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 20px; border-radius: 8px; margin: 20px 0; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 20px; border-radius: 8px; margin: 20px 0; }
h1 { color: #333; }
code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
</style></head><body>";

echo "<h1>🔧 Dekan Rolunu Əlavə Et</h1>";

try {
    // 1. Əvvəlcə cədvəlin mövcud strukturunu yoxlayaq
    echo "<div class='info'><strong>1. Mövcud struktur yoxlanılır...</strong></div>";

    $checkQuery = "SHOW COLUMNS FROM users LIKE 'status'";
    $stmt = $db->prepare($checkQuery);
    $stmt->execute();
    $column = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($column) {
        echo "<div class='info'>✓ Status sütunu tapıldı<br>";
        echo "Mövcud tip: <code>" . htmlspecialchars($column['Type']) . "</code></div>";

        // 2. Əgər 'dekan' artıq varsa, xəbərdarlıq ver
        if (strpos($column['Type'], 'dekan') !== false) {
            echo "<div class='success'>";
            echo "<h2>✓ DEKAN ROLU ARTIQ MÖVCUDDUR!</h2>";
            echo "<p>Verilənlər bazasında 'dekan' rolu artıq əlavə edilib.</p>";
            echo "<p><strong>İndi edə biləcəkləriniz:</strong></p>";
            echo "<ol>";
            echo "<li><strong>Bu faylı silin:</strong> <code>fix_dekan.php</code></li>";
            echo "<li><a href='admin/users.php'>İstifadəçi İdarəetməsi</a> səhifəsinə keçin</li>";
            echo "<li>Yeni istifadəçi yaradın və 'Dekan' rolunu seçin</li>";
            echo "</ol>";
            echo "</div>";
        } else {
            // 3. DEKAN rolunu əlavə et
            echo "<div class='info'><strong>2. Dekan rolu əlavə edilir...</strong></div>";

            $alterQuery = "ALTER TABLE `users`
                          MODIFY COLUMN `status` ENUM('admin', 'prorektor', 'dekan', 'kafedra', 'muellim', 'student')
                          NOT NULL DEFAULT 'student'";

            $db->exec($alterQuery);

            echo "<div class='success'>";
            echo "<h2>✓ UĞURLU!</h2>";
            echo "<p><strong>Dekan rolu uğurla əlavə edildi.</strong></p>";
            echo "<p><strong>İndi edə biləcəkləriniz:</strong></p>";
            echo "<ol>";
            echo "<li><strong>Bu faylı silin:</strong> <code>fix_dekan.php</code></li>";
            echo "<li><a href='admin/users.php' style='color: #0066cc;'>İstifadəçi İdarəetməsi</a> səhifəsinə keçin</li>";
            echo "<li>Yeni istifadəçi yaradın və 'Dekan' rolunu seçin</li>";
            echo "<li>Dekan hesabı ilə giriş edib ancaq nəticələrə baxa bilərsiniz</li>";
            echo "</ol>";
            echo "<p><strong>Dekan səlahiyyətləri:</strong></p>";
            echo "<ul>";
            echo "<li>✅ İmtahan nəticələrinə baxa bilir</li>";
            echo "<li>✅ Fakültə üzrə hesabatları görə bilir</li>";
            echo "<li>✅ Nəticələri ixrac edə bilir</li>";
            echo "<li>❌ Heç bir redaktə edə bilməz</li>";
            echo "</ul>";
            echo "</div>";
        }
    } else {
        throw new Exception("Status sütunu tapılmadı!");
    }

} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h2>✗ XƏTA!</h2>";
    echo "<p><strong>Verilənlər bazası xətası:</strong></p>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Ehtimal olunan səbəblər:</strong></p>";
    echo "<ul>";
    echo "<li>Verilənlər bazası əlaqəsi problemi</li>";
    echo "<li>İcazə problemi (admin hüququ lazımdır)</li>";
    echo "<li>Dekan rolu artıq əlavə edilib</li>";
    echo "</ul>";
    echo "<p>Əgər rol artıq əlavə edilibsə, bu xətanı nəzərə almayın və bu faylı silin.</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>✗ XƏTA!</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
