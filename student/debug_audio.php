<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once "../includes/db.php";

$database = new Database();
$db = $database->getConnection();

$exam_id = 307;

echo "<h2>DEBUG: Audio Files for Exam $exam_id</h2>";
echo "<hr>";

// Get exam subject
$query = "SELECT e.id_exam, e.id_subject, s.subjectname
          FROM exams e
          JOIN subjects s ON e.id_subject = s.id_subject
          WHERE e.id_exam = :exam_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":exam_id", $exam_id);
$stmt->execute();
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>Exam Info:</h3>";
echo "<pre>" . print_r($exam, true) . "</pre>";
echo "<hr>";

// Get all question files for this subject
$query = "SELECT id_read_quest_file, file_title, file_path, file_type, subject_id
          FROM question_files
          WHERE subject_id = :subject_id
          ORDER BY file_type, file_title";
$stmt = $db->prepare($query);
$stmt->bindParam(":subject_id", $exam['id_subject']);
$stmt->execute();
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>All Files for Subject " . $exam['id_subject'] . ":</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Title</th><th>Type</th><th>Path in DB</th><th>File Exists?</th><th>Test Play</th></tr>";

foreach ($files as $file) {
    echo "<tr>";
    echo "<td>" . $file['id_read_quest_file'] . "</td>";
    echo "<td>" . htmlspecialchars($file['file_title']) . "</td>";
    echo "<td><strong>" . $file['file_type'] . "</strong></td>";
    echo "<td>" . htmlspecialchars($file['file_path']) . "</td>";

    // Check multiple possible paths
    $base_paths = [
        __DIR__ . '/../',  // Parent directory
        __DIR__ . '/../../',  // Grandparent
    ];

    $relative_paths = [
        $file['file_path'],
        'uploads/' . basename($file['file_path']),
        ltrim($file['file_path'], '/\\')
    ];

    $found = false;
    $found_path = '';

    foreach ($base_paths as $base) {
        foreach ($relative_paths as $rel) {
            $test_path = $base . $rel;
            if (file_exists($test_path)) {
                $found = true;
                $found_path = $test_path;
                break 2;
            }
        }
    }

    if ($found) {
        $size = filesize($found_path);
        echo "<td style='color: green;'>✓ YES<br><small>" . $found_path . "<br>Size: " . number_format($size) . " bytes</small></td>";

        if ($file['file_type'] == 'listening') {
            $relative = 'uploads/' . basename($file['file_path']);
            $stream_url = '../stream_audio.php?file=' . urlencode($relative);
            echo "<td><audio controls src='" . htmlspecialchars($stream_url) . "' preload='metadata'></audio><br>";
            echo "<small><a href='" . htmlspecialchars($stream_url) . "' target='_blank'>Direct Link</a></small></td>";
        } else {
            echo "<td>-</td>";
        }
    } else {
        echo "<td style='color: red;'>✗ NOT FOUND<br><small>Checked multiple locations</small></td>";
        echo "<td>-</td>";
    }

    echo "</tr>";
}

echo "</table>";

// Now let's check what the exam_v2.php would generate
echo "<hr><h3>What exam_v2.php sees:</h3>";

$query = "SELECT qr.id_question_text, qr.question_text,
                 qt.quest_type_name, qt.question_var,
                 qf.file_title, qf.file_path, qf.file_type, qf.id_read_quest_file
          FROM question_read qr
          JOIN question_files qf ON qr.id_read_quest_file = qf.id_read_quest_file
          JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
          WHERE qf.subject_id = :subject_id
          ORDER BY qr.id_question_text";

$stmt = $db->prepare($query);
$stmt->bindParam(":subject_id", $exam['id_subject']);
$stmt->execute();
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Total questions: " . count($questions) . "</p>";

$listening_count = 0;
foreach ($questions as $q) {
    if ($q['file_type'] == 'listening') {
        $listening_count++;
        echo "<div style='border: 2px solid blue; padding: 10px; margin: 10px;'>";
        echo "<h4>Listening Question " . $listening_count . "</h4>";
        echo "<p><strong>Question ID:</strong> " . $q['id_question_text'] . "</p>";
        echo "<p><strong>File Title:</strong> " . htmlspecialchars($q['file_title']) . "</p>";
        echo "<p><strong>File Path (DB):</strong> " . htmlspecialchars($q['file_path']) . "</p>";
        echo "<p><strong>Question:</strong> " . htmlspecialchars($q['question_text']) . "</p>";
        echo "</div>";
    }
}

echo "<p><strong>Total listening questions found: $listening_count</strong></p>";
?>
