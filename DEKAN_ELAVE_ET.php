<?php
/**
 * DEKAN ROLUNU ƏLAVƏ ET - SADƏLƏŞDİRİLMİŞ VERSİYA
 * Bu faylı bir dəfə işlət və sil
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Verilənlər bazası məlumatları
$host = "172.18.250.21:3306";
$db_name = "edu_system";
$username = "admins";
$password = "23234455";

?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dekan Rolunu Əlavə Et</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #667eea;
            text-align: center;
            margin-bottom: 30px;
            font-size: 32px;
        }
        .status {
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            font-size: 16px;
            line-height: 1.6;
        }
        .success {
            background: #d4edda;
            border-left: 5px solid #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-left: 5px solid #dc3545;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border-left: 5px solid #17a2b8;
            color: #0c5460;
        }
        .warning {
            background: #fff3cd;
            border-left: 5px solid #ffc107;
            color: #856404;
        }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            text-align: center;
            margin: 10px 5px;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .steps {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .steps h3 {
            color: #333;
            margin-bottom: 15px;
        }
        .steps ol {
            margin-left: 20px;
        }
        .steps li {
            margin: 10px 0;
            line-height: 1.8;
        }
        code {
            background: #f4f4f4;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #e83e8c;
        }
        .icon {
            font-size: 48px;
            text-align: center;
            margin-bottom: 20px;
        }
        .center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔧</div>
        <h1>Dekan Rolunu Əlavə Et</h1>

<?php

try {
    // Verilənlər bazasına qoşul
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo '<div class="status info">✓ Verilənlər bazasına qoşuldum...</div>';

    // Mövcud strukturu yoxla
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($column) {
        $currentType = $column['Type'];
        echo '<div class="status info"><strong>Mövcud Status:</strong><br><code>' . htmlspecialchars($currentType) . '</code></div>';

        // Dekan mövcuddurmu yoxla
        if (strpos($currentType, 'dekan') !== false) {
            echo '<div class="status success">';
            echo '<div class="icon">✅</div>';
            echo '<h2 style="color: #28a745; margin: 20px 0;">DEKAN ROLU ARTIQ MÖVCUDDUR!</h2>';
            echo '<p>Verilənlər bazasında dekan rolu artıq əlavə edilib.</p>';
            echo '</div>';
        } else {
            // Dekan rolunu əlavə et
            echo '<div class="status warning">⏳ Dekan rolu əlavə edilir...</div>';

            $sql = "ALTER TABLE `users`
                    MODIFY COLUMN `status`
                    ENUM('admin', 'prorektor', 'dekan', 'kafedra', 'muellim', 'student')
                    NOT NULL DEFAULT 'student'";

            $conn->exec($sql);

            // Yoxla
            $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
            $newColumn = $stmt->fetch(PDO::FETCH_ASSOC);

            echo '<div class="status success">';
            echo '<div class="icon">🎉</div>';
            echo '<h2 style="color: #28a745; margin: 20px 0;">UĞURLU!</h2>';
            echo '<p><strong>Dekan rolu uğurla əlavə edildi!</strong></p>';
            echo '<p><strong>Yeni Status:</strong><br><code>' . htmlspecialchars($newColumn['Type']) . '</code></p>';
            echo '</div>';
        }

        // Növbəti addımlar
        echo '<div class="steps">';
        echo '<h3>📝 İndi nə etməlisiniz:</h3>';
        echo '<ol>';
        echo '<li>Bu faylı <strong>silin</strong>: <code>DEKAN_ELAVE_ET.php</code></li>';
        echo '<li><a href="admin/users.php" style="color: #667eea; font-weight: bold;">İstifadəçi İdarəetməsi</a> səhifəsinə keçin</li>';
        echo '<li><strong>"Yeni İstifadəçi"</strong> düyməsinə basın</li>';
        echo '<li>Rol bölməsində <strong>"Dekan"</strong> seçin</li>';
        echo '<li>İstifadəçini yaradın və giriş edin!</li>';
        echo '</ol>';
        echo '</div>';

        echo '<div class="status info">';
        echo '<h3>🎯 Dekan Səlahiyyətləri:</h3>';
        echo '<ul style="margin-left: 20px; margin-top: 10px; line-height: 2;">';
        echo '<li>✅ Dashboard görür</li>';
        echo '<li>✅ İmtahan nəticələrinə baxır</li>';
        echo '<li>✅ Nəticələri DOC formatında yükləyir</li>';
        echo '<li>✅ Hesabatları görə bilir</li>';
        echo '<li>❌ Heç bir dəyişiklik edə bilməz</li>';
        echo '<li>❌ İstifadəçi, fənn, imtahan əlavə edə bilməz</li>';
        echo '</ul>';
        echo '</div>';

        echo '<div class="center">';
        echo '<a href="admin/users.php" class="btn">👤 İstifadəçi Yaratmağa Keç</a>';
        echo '</div>';

    } else {
        throw new Exception("Status sütunu tapılmadı!");
    }

} catch (PDOException $e) {
    echo '<div class="status error">';
    echo '<div class="icon">❌</div>';
    echo '<h2>Verilənlər Bazası Xətası</h2>';
    echo '<p><strong>Xəta:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>Həll:</strong></p>';
    echo '<ul style="margin-left: 20px; margin-top: 10px;">';
    echo '<li>Verilənlər bazası məlumatlarını yoxlayın</li>';
    echo '<li>MySQL serverin işlədiyini yoxlayın</li>';
    echo '<li>İstifadəçinin ALTER icazəsi olduğunu təsdiqləyin</li>';
    echo '</ul>';
    echo '</div>';
} catch (Exception $e) {
    echo '<div class="status error">';
    echo '<h2>Xəta</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
}

?>
    </div>
</body>
</html>
