<?php
/**
 * BÜTÜN ROLLARI VERİLƏNLƏR BAZASINA ƏLAVƏ ET
 * PROREKTOR, DEKAN, KAFEDRA, MÜƏLLİM
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Birbaşa verilənlər bazasına qoşul
$host = "172.18.250.21:3306";
$db_name = "edu_system";
$username = "admins";
$password = "23234455";

echo "<html><head><meta charset='UTF-8'><style>
body { font-family: Arial; padding: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
.success { background: #d4edda; border-left: 4px solid #28a745; color: #155724; padding: 20px; margin: 20px 0; border-radius: 8px; }
.error { background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; padding: 20px; margin: 20px 0; border-radius: 8px; }
.info { background: #d1ecf1; border-left: 4px solid #17a2b8; color: #0c5460; padding: 20px; margin: 20px 0; border-radius: 8px; }
.warning { background: #fff3cd; border-left: 4px solid #ffc107; color: #856404; padding: 20px; margin: 20px 0; border-radius: 8px; }
h1 { color: #333; text-align: center; margin-bottom: 30px; }
code { background: #f4f4f4; padding: 2px 8px; border-radius: 4px; font-family: monospace; }
.step { background: #e7f3ff; padding: 15px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #2196F3; }
</style></head><body><div class='container'>";

echo "<h1>🔧 Bütün Rolları Əlavə Et</h1>";

try {
    // 1. Verilənlər bazasına qoşul
    echo "<div class='step'><strong>ADDIM 1:</strong> Verilənlər bazasına qoşuluram...</div>";

    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<div class='success'>✓ Verilənlər bazasına uğurla qoşuldum!</div>";

    // 2. Mövcud strukturu yoxla
    echo "<div class='step'><strong>ADDIM 2:</strong> Mövcud status sütunu yoxlanılır...</div>";

    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($column) {
        echo "<div class='info'><strong>Mövcud Status Tipi:</strong><br>";
        echo "<code>" . htmlspecialchars($column['Type']) . "</code></div>";

        // 3. Bütün rolları əlavə et
        echo "<div class='step'><strong>ADDIM 3:</strong> Bütün rollar əlavə edilir...</div>";
        echo "<div class='warning'>⏳ Zəhmət olmasa gözləyin, verilənlər bazası yenilənir...</div>";

        $alterSQL = "ALTER TABLE `users`
                     MODIFY COLUMN `status`
                     ENUM('admin', 'prorektor', 'dekan', 'kafedra', 'muellim', 'student')
                     NOT NULL DEFAULT 'student'";

        $conn->exec($alterSQL);

        echo "<div class='success'>";
        echo "<h2 style='color: #28a745; margin-top: 0;'>✓ UĞURLU! BÜTÜN ROLLAR ƏLAVƏ EDİLDİ!</h2>";
        echo "<p><strong>Əlavə edilən rollar:</strong></p>";
        echo "<ul style='line-height: 2;'>";
        echo "<li>✅ <strong>Admin</strong> - Tam idarəetmə</li>";
        echo "<li>✅ <strong>Prorektor</strong> - İmtahan idarəetməsi və nəticələr</li>";
        echo "<li>✅ <strong>Dekan</strong> - Yalnız nəticələrə baxma</li>";
        echo "<li>✅ <strong>Kafedra</strong> - Sual təsdiqi və nəticələr</li>";
        echo "<li>✅ <strong>Müəllim</strong> - Öz fənninə aid suallar</li>";
        echo "<li>✅ <strong>Tələbə</strong> - İmtahan verə bilər</li>";
        echo "</ul>";
        echo "</div>";

        // 4. Yoxlama
        echo "<div class='step'><strong>ADDIM 4:</strong> Yenilənmiş struktur yoxlanılır...</div>";

        $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
        $newColumn = $stmt->fetch(PDO::FETCH_ASSOC);

        echo "<div class='info'><strong>Yeni Status Tipi:</strong><br>";
        echo "<code>" . htmlspecialchars($newColumn['Type']) . "</code></div>";

        // 5. Növbəti addımlar
        echo "<div class='success'>";
        echo "<h3>📝 İndi nə etməlisiniz:</h3>";
        echo "<ol style='line-height: 2;'>";
        echo "<li>🗑️ <strong>Bu faylı silin:</strong> <code>fix_all_roles_now.php</code></li>";
        echo "<li>🌐 <a href='admin/users.php' style='color: #0066cc; text-decoration: none;'><strong>İstifadəçi İdarəetməsi</strong></a> səhifəsinə keçin</li>";
        echo "<li>➕ <strong>Yeni İstifadəçi</strong> düyməsinə basın</li>";
        echo "<li>👤 İstədiyiniz rolu seçin: <strong>Prorektor, Dekan, Kafedra, Müəllim</strong></li>";
        echo "<li>✅ İstifadəçini yaradın və giriş edin!</li>";
        echo "</ol>";
        echo "</div>";

        echo "<div class='info'>";
        echo "<h3>🎯 Rol Səlahiyyətləri:</h3>";
        echo "<table style='width: 100%; border-collapse: collapse; margin-top: 15px;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='padding: 10px; text-align: left; border: 1px solid #dee2e6;'>Rol</th>";
        echo "<th style='padding: 10px; text-align: left; border: 1px solid #dee2e6;'>Səlahiyyətlər</th>";
        echo "</tr>";
        echo "<tr><td style='padding: 10px; border: 1px solid #dee2e6;'><strong>Prorektor</strong></td><td style='padding: 10px; border: 1px solid #dee2e6;'>İmtahan yarada bilir, nəticələri görür və DOCX yükləyir</td></tr>";
        echo "<tr><td style='padding: 10px; border: 1px solid #dee2e6;'><strong>Dekan</strong></td><td style='padding: 10px; border: 1px solid #dee2e6;'>Yalnız nəticələrə baxır və DOC yükləyir</td></tr>";
        echo "<tr><td style='padding: 10px; border: 1px solid #dee2e6;'><strong>Kafedra</strong></td><td style='padding: 10px; border: 1px solid #dee2e6;'>Sualları təsdiq edir, nəticələri görür və DOC yükləyir</td></tr>";
        echo "<tr><td style='padding: 10px; border: 1px solid #dee2e6;'><strong>Müəllim</strong></td><td style='padding: 10px; border: 1px solid #dee2e6;'>Öz fənninə aid suallar yaradır və təsdiq edir</td></tr>";
        echo "</table>";
        echo "</div>";

    } else {
        throw new Exception("Status sütunu tapılmadı! Verilənlər bazası strukturu düzgün deyil.");
    }

} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h2>✗ VERİLƏNLƏR BAZASI XƏTASI!</h2>";
    echo "<p><strong>Xəta mesajı:</strong></p>";
    echo "<p style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h3>🔧 Həll yolları:</h3>";
    echo "<ul>";
    echo "<li>Verilənlər bazası məlumatlarını yoxlayın (host, istifadəçi adı, şifrə)</li>";
    echo "<li>MySQL serverin işlədiyini yoxlayın</li>";
    echo "<li>İstifadəçinin ALTER TABLE icazəsi olduğunu yoxlayın</li>";
    echo "</ul>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>✗ XƏTA!</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div></body></html>";
?>
