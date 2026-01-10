<?php
// Error reporting aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug - Veritabanı Test</h1>";

// 1. Veritabanı bağlantısını test et
echo "<h2>1. Veritabanı Bağlantısı</h2>";
try {
    require_once 'includes/db.php';
    $database = new Database();
    $conn = $database->getConnection();
    echo "✅ Veritabanı bağlantısı uğurlu!<br>";
} catch (Exception $e) {
    echo "❌ XƏTA: " . $e->getMessage() . "<br>";
    die();
}

// 2. Exam ID
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 318;
echo "<h2>2. İmtahan ID: $exam_id</h2>";

// 3. İmtahan mövcuddur?
echo "<h2>3. İmtahan Yoxlama</h2>";
try {
    $sql_check = "SELECT COUNT(*) as count FROM exams WHERE id_exam = :exam_id";
    $stmt = $conn->prepare($sql_check);
    $stmt->bindParam(':exam_id', $exam_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] > 0) {
        echo "✅ İmtahan tapıldı (ID: $exam_id)<br>";
    } else {
        echo "❌ İmtahan tapılmadı! ID: $exam_id<br>";

        // Mövcud imtahanları göstər
        echo "<h3>Mövcud İmtahanlar:</h3>";
        $sql_all = "SELECT id_exam, date_exam FROM exams ORDER BY id_exam DESC LIMIT 10";
        $stmt_all = $conn->prepare($sql_all);
        $stmt_all->execute();
        $all_exams = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

        echo "<ul>";
        foreach ($all_exams as $e) {
            echo "<li><a href='?exam_id={$e['id_exam']}'>Exam #{$e['id_exam']} - {$e['date_exam']}</a></li>";
        }
        echo "</ul>";
        die();
    }
} catch (Exception $e) {
    echo "❌ XƏTA: " . $e->getMessage() . "<br>";
    die();
}

// 4. Cavablar mövcuddur?
echo "<h2>4. Cavablar Yoxlama</h2>";
try {
    $sql_answers = "SELECT COUNT(*) as count FROM answers WHERE exam_id = :exam_id";
    $stmt = $conn->prepare($sql_answers);
    $stmt->bindParam(':exam_id', $exam_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Cavab sayı: " . $result['count'] . "<br>";

    if ($result['count'] == 0) {
        echo "❌ Bu imtahan üçün cavab yoxdur!<br>";
        die();
    }
} catch (Exception $e) {
    echo "❌ XƏTA: " . $e->getMessage() . "<br>";
    die();
}

// 5. ƏSAS SORĞU
echo "<h2>5. Əsas Sorğu</h2>";
try {
    $sql = "
        SELECT
            u.id_users AS telebe_id,
            u.f_name AS telebe_adi,
            sg.group_number AS qrup,
            qr.id_question_text AS sual_no,
            LEFT(qr.question_text, 50) AS sual_metni,
            qr.question_score AS sual_maksimum_bal,
            a.user_answer AS telebe_cavabi,
            a.correct_var AS duzgun_cavab,
            a.is_correct AS duzgundur,
            a.score_earned AS telebe_alinan_bal
        FROM answers a
        INNER JOIN users u ON a.user_id = u.id_users
        INNER JOIN student_group sg ON u.group_id = sg.id_student_group
        INNER JOIN question_read qr ON a.id_questions = qr.id_question_text
        WHERE a.exam_id = :exam_id
        ORDER BY u.f_name, qr.id_question_text
        LIMIT 10
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':exam_id', $exam_id);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Tapılan nəticə sayı: " . count($results) . "<br><br>";

    if (count($results) > 0) {
        echo "✅ Məlumatlar tapıldı!<br><br>";

        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background: #333; color: white;'>";
        echo "<th>Tələbə</th><th>Qrup</th><th>Sual No</th><th>Sual</th><th>Tələbə Cavabı</th><th>Düzgün</th><th>Bal</th><th>Max</th>";
        echo "</tr>";

        foreach ($results as $row) {
            $bg = $row['duzgundur'] ? '#d4edda' : '#f8d7da';
            echo "<tr style='background: $bg;'>";
            echo "<td>{$row['telebe_adi']}</td>";
            echo "<td>{$row['qrup']}</td>";
            echo "<td>{$row['sual_no']}</td>";
            echo "<td>" . htmlspecialchars($row['sual_metni']) . "</td>";
            echo "<td>" . htmlspecialchars($row['telebe_cavabi']) . "</td>";
            echo "<td>" . ($row['duzgundur'] ? '✓' : '✗') . "</td>";
            echo "<td><strong>{$row['telebe_alinan_bal']}</strong></td>";
            echo "<td>{$row['sual_maksimum_bal']}</td>";
            echo "</tr>";
        }
        echo "</table>";

        echo "<br><br>";
        echo "<h3>✅ UĞURLU! İndi test_simple.php açılmalıdır</h3>";
        echo "<a href='test_simple.php?exam_id=$exam_id' style='font-size: 20px; background: green; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>test_simple.php AÇ</a>";

    } else {
        echo "❌ Məlumat tapılmadı!<br>";
    }

} catch (Exception $e) {
    echo "❌ SQL XƏTASI: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
