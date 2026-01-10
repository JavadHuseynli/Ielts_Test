<?php
/**
 * VERİLƏNLƏR BAZASI STATUSUNU YOXLA
 * Bu fayl problemin nədə olduğunu göstərəcək
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Birbaşa verilənlər bazasına qoşul
$host = "172.18.250.21:3306";
$db_name = "edu_system";
$username = "admins";
$password = "23234455";

echo "<html><head><meta charset='UTF-8'><style>
body { font-family: Arial; padding: 40px; background: #f5f5f5; }
.container { max-width: 900px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
.success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 15px 0; border-radius: 8px; }
.error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 15px 0; border-radius: 8px; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; margin: 15px 0; border-radius: 8px; }
.warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; margin: 15px 0; border-radius: 8px; }
h1 { color: #333; text-align: center; }
code { background: #f4f4f4; padding: 3px 8px; border-radius: 4px; font-family: monospace; font-size: 14px; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
th { background: #667eea; color: white; }
tr:nth-child(even) { background: #f8f9fa; }
.big-btn { display: inline-block; margin: 20px 10px; padding: 15px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 10px; font-weight: bold; }
.big-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
</style></head><body><div class='container'>";

echo "<h1>🔍 Verilənlər Bazası Status Yoxlaması</h1>";

try {
    // Qoşul
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<div class='success'>✓ Verilənlər bazasına uğurla qoşuldu</div>";

    // 1. Status sütununun tipini yoxla
    echo "<h2>📋 1. Status Sütunu Strukturu</h2>";
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($column) {
        $currentType = $column['Type'];
        echo "<div class='info'>";
        echo "<strong>Mövcud Tip:</strong> <code>" . htmlspecialchars($currentType) . "</code><br>";
        echo "<strong>Default:</strong> <code>" . htmlspecialchars($column['Default']) . "</code>";
        echo "</div>";

        // Rolları yoxla
        if (strpos($currentType, 'prorektor') !== false) {
            echo "<div class='success'>✅ PROREKTOR rolu mövcuddur</div>";
        } else {
            echo "<div class='error'>❌ PROREKTOR rolu mövcud DEYİL</div>";
        }

        if (strpos($currentType, 'dekan') !== false) {
            echo "<div class='success'>✅ DEKAN rolu mövcuddur</div>";
        } else {
            echo "<div class='error'>❌ DEKAN rolu mövcud DEYİL</div>";
        }

        if (strpos($currentType, 'kafedra') !== false) {
            echo "<div class='success'>✅ KAFEDRA rolu mövcuddur</div>";
        } else {
            echo "<div class='error'>❌ KAFEDRA rolu mövcud DEYİL</div>";
        }

        if (strpos($currentType, 'muellim') !== false) {
            echo "<div class='success'>✅ MÜƏLLİM rolu mövcuddur</div>";
        } else {
            echo "<div class='error'>❌ MÜƏLLİM rolu mövcud DEYİL</div>";
        }
    }

    // 2. Mövcud istifadəçiləri göstər
    echo "<h2>👥 2. Mövcud İstifadəçilər</h2>";
    $stmt = $conn->query("SELECT id_users, f_name, username, status, created_at FROM users ORDER BY id_users DESC LIMIT 20");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($users) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Ad</th><th>İstifadəçi Adı</th><th>Status</th><th>Yaradılma Tarixi</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id_users']) . "</td>";
            echo "<td>" . htmlspecialchars($user['f_name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($user['status']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($user['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>Heç bir istifadəçi tapılmadı!</div>";
    }

    // 3. Statistika
    echo "<h2>📊 3. İstifadəçi Statistikası</h2>";
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM users GROUP BY status");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table>";
    echo "<tr><th>Rol</th><th>Say</th></tr>";
    foreach ($stats as $stat) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($stat['status']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($stat['count']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // 4. Tövsiyə
    echo "<h2>💡 4. Tövsiyə</h2>";

    $needsFix = false;
    if (strpos($currentType, 'prorektor') === false ||
        strpos($currentType, 'dekan') === false ||
        strpos($currentType, 'kafedra') === false ||
        strpos($currentType, 'muellim') === false) {
        $needsFix = true;
    }

    if ($needsFix) {
        echo "<div class='error'>";
        echo "<h3>⚠️ PROBLEMİ TƏSDİQ ETDİK!</h3>";
        echo "<p>Verilənlər bazasında bəzi rollar mövcud deyil.</p>";
        echo "<p><strong>Həll:</strong></p>";
        echo "</div>";
        echo "<div style='text-align: center;'>";
        echo "<a href='fix_all_roles_now.php' class='big-btn'>🔧 İNDİ DÜZƏLDİN</a>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<h3>✅ HƏR ŞEY QAYDASINDADIR!</h3>";
        echo "<p>Bütün rollar verilənlər bazasında mövcuddur.</p>";
        echo "<p>İndi <a href='admin/users.php'>İstifadəçi İdarəetməsi</a> səhifəsinə keçərək istədiyiniz rolu seçə bilərsiniz.</p>";
        echo "</div>";
    }

} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h2>✗ BAĞLANTI XƏTASI!</h2>";
    echo "<p><strong>Xəta:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div style='margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;'>";
echo "<h3>📝 Növbəti Addımlar:</h3>";
echo "<ol>";
echo "<li>Yuxarıdakı yoxlama nəticələrinə baxın</li>";
echo "<li>Əgər problem varsa, <strong>🔧 İNDİ DÜZƏLDİN</strong> düyməsinə basın</li>";
echo "<li>Problem düzəldikdən sonra bu faylı silin</li>";
echo "</ol>";
echo "</div>";

echo "</div></body></html>";
?>
