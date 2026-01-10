<?php
require_once "../includes/db.php";

$database = new Database();
$db = $database->getConnection();

// Get exam 307 audio files
$query = "SELECT qf.id_read_quest_file, qf.file_title, qf.file_path, qf.file_type
          FROM question_files qf
          JOIN exams e ON qf.subject_id = e.id_subject
          WHERE e.id_exam = 307 AND qf.file_type = 'listening'";
$stmt = $db->prepare($query);
$stmt->execute();
$audio_files = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Audio Files for Exam 307</h2>";

foreach ($audio_files as $file) {
    echo "<div style='border: 1px solid #ccc; padding: 15px; margin: 10px;'>";
    echo "<h3>" . htmlspecialchars($file['file_title']) . "</h3>";
    echo "<p><strong>Database Path:</strong> " . htmlspecialchars($file['file_path']) . "</p>";

    // Check if file exists
    $possible_paths = [
        $file['file_path'],
        '../' . ltrim($file['file_path'], '/\\'),
        '../uploads/' . basename($file['file_path']),
        'uploads/' . basename($file['file_path'])
    ];

    $found_path = null;
    foreach ($possible_paths as $path) {
        echo "<p>Checking: " . htmlspecialchars($path);
        if (file_exists($path)) {
            echo " - <span style='color: green;'>✓ EXISTS (" . filesize($path) . " bytes)</span></p>";
            $found_path = $path;
            break;
        } else {
            echo " - <span style='color: red;'>✗ NOT FOUND</span></p>";
        }
    }

    if ($found_path) {
        $relative_path = 'uploads/' . basename($file['file_path']);
        $stream_url = '../stream_audio.php?file=' . urlencode($relative_path);
        echo "<p><strong>Stream URL:</strong> <a href='" . htmlspecialchars($stream_url) . "' target='_blank'>" . htmlspecialchars($stream_url) . "</a></p>";

        echo "<audio controls src='" . htmlspecialchars($stream_url) . "' style='width: 100%;'></audio>";
    } else {
        echo "<p style='color: red;'><strong>FILE NOT FOUND IN ANY LOCATION!</strong></p>";
    }

    echo "</div>";
}
?>
