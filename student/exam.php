<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

// Authentication and Permission Check
checkLogin();
if ($_SESSION['status'] != 'student') {
    header("Location: ../admin/dashboard.php");
    exit();
}

// Validate Exam ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: exams.php");
    exit();
}

// Initialize Variables
$exam_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$group_id = $_SESSION['group_id'];
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';

// Database Connection
$database = new Database();
$db = $database->getConnection();

// Fetch Exam Information
$query = "SELECT e.id_exam, e.datetime, e.status, s.subjectname, s.timer, s.id_subject
          FROM exams e
          JOIN subjects s ON e.id_subject = s.id_subject
          WHERE e.id_exam = :exam_id AND e.id_student_group = :group_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":exam_id", $exam_id);
$stmt->bindParam(":group_id", $group_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: exams.php");
    exit();
}

$exam = $stmt->fetch(PDO::FETCH_ASSOC);

// FILE HANDLING FUNCTIONS
function getValidReadingPath($file_path) {
    if (empty($file_path)) {
        return null;
    }
    
    $possible_paths = [
        $file_path,
        '../' . ltrim($file_path, '/\\'),
        '../../' . ltrim($file_path, '/\\'),
        '../../../' . ltrim($file_path, '/\\'),
        '../uploads/' . basename($file_path),
        '../../uploads/' . basename($file_path),
        '../files/' . basename($file_path),
        '../../files/' . basename($file_path),
        'uploads/' . basename($file_path),
        'files/' . basename($file_path),
        str_replace('\\', '/', $file_path),
        str_replace('/', '\\', $file_path),
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path) && is_readable($path) && filesize($path) > 0) {
            return $path;
        }
    }
    
    return null;
}

function loadReadingContent($file_path) {
    $valid_path = getValidReadingPath($file_path);
    
    if (!$valid_path) {
        return "📄 Oxunacaq mətn tapılmadı.\n\nFayl yolu: " . $file_path;
    }
    
    $content = file_get_contents($valid_path);
    
    if ($content === false) {
        return "📄 Fayl oxuna bilmədi.\n\nFayl yolu: " . $valid_path;
    }
    
    if (empty(trim($content))) {
        return "📄 Fayl boşdur.\n\nFayl yolu: " . $valid_path;
    }
    
    $content = trim($content);
    
    if (!mb_check_encoding($content, 'UTF-8')) {
        $content = mb_convert_encoding($content, 'UTF-8', 'auto');
    }
    
    return $content;
}

function getValidAudioPath($file_path) {
    if (empty($file_path)) {
        return null;
    }
    
    if (filter_var($file_path, FILTER_VALIDATE_URL) || (strpos($file_path, 'http') === 0)) {
        return $file_path;
    }
    
    $possible_paths = [
        $file_path,
        '../' . ltrim($file_path, '/\\'),
        '../../' . ltrim($file_path, '/\\'),
        '../../../' . ltrim($file_path, '/\\'),
        '../uploads/' . basename($file_path),
        '../../uploads/' . basename($file_path),
        '../files/' . basename($file_path),
        '../../files/' . basename($file_path),
        '../audio/' . basename($file_path),
        '../../audio/' . basename($file_path),
        'uploads/' . basename($file_path),
        'files/' . basename($file_path),
        'audio/' . basename($file_path),
        str_replace('\\', '/', $file_path),
        str_replace('/', '\\', $file_path),
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path) && is_readable($path) && filesize($path) > 0) {
            return $path;
        }
    }
    
    $audio_extensions = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'mp4'];
    $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    
    if (in_array($extension, $audio_extensions)) {
        return $file_path;
    }
    
    return null;
}

// DEBUG INFORMATION
if ($debug_mode) {
    echo "<div class='alert alert-warning'>";
    echo "<h4>🔍 READING FILE DEBUG</h4>";
    echo "<strong>Current Directory:</strong> " . getcwd() . "<br>";
    
    $upload_dirs = ['../uploads', '../../uploads', 'uploads', '../files', '../../files'];
    foreach ($upload_dirs as $dir) {
        echo "<strong>$dir exists:</strong> " . (is_dir($dir) ? 'YES' : 'NO') . "<br>";
        if (is_dir($dir)) {
            $files = glob($dir . '/*.txt');
            echo "&nbsp;&nbsp;TXT files: " . count($files) . "<br>";
            if (count($files) > 0) {
                echo "&nbsp;&nbsp;Sample: " . basename($files[0]) . "<br>";
            }
        }
    }
    echo "</div>";
}

// RANDOM VARIANT SYSTEM - TAM TƏSADÜFİ
$selected_variant = null;

// Mövcud bütün variantları tap
$available_variants_query = "SELECT DISTINCT qf.file_title,
                                   COUNT(DISTINCT qr.id_question_text) as question_count
                            FROM question_files qf
                            JOIN question_read qr ON qf.id_read_quest_file = qr.id_read_quest_file
                            WHERE qf.subject_id = :subject_id 
                              AND qf.file_title IS NOT NULL 
                              AND qf.file_title != ''
                              AND qf.file_title LIKE '%Variant%'
                            GROUP BY qf.file_title
                            HAVING question_count > 0
                            ORDER BY qf.file_title";

$available_variants_stmt = $db->prepare($available_variants_query);
$available_variants_stmt->bindParam(":subject_id", $exam['id_subject']);
$available_variants_stmt->execute();
$available_variants_raw = $available_variants_stmt->fetchAll(PDO::FETCH_ASSOC);

$variant_groups = [];
if (!empty($available_variants_raw)) {
    foreach ($available_variants_raw as $variant_file) {
        if (preg_match('/Variant\s+([A-Z0-9]+)/i', $variant_file['file_title'], $matches)) {
            $variant_code = $matches[1];
            if (!isset($variant_groups[$variant_code])) {
                $variant_groups[$variant_code] = 0;
            }
            $variant_groups[$variant_code] += $variant_file['question_count'];
        }
    }
    
    if (!empty($variant_groups)) {
        // HƏR DƏFƏ TAM TƏSADÜFİ VARIANT SEÇ
        $variant_keys = array_keys($variant_groups);
        
        // Hazırkı vaxt + user ID + exam ID ilə seed yarad
        $random_seed = time() + $user_id + $exam_id + rand(1, 1000);
        srand($random_seed);
        
        $random_index = rand(0, count($variant_keys) - 1);
        $selected_variant = $variant_keys[$random_index];
        
        if ($debug_mode) {
            echo "<div class='alert alert-success'>";
            echo "<strong>🎲 TAM TƏSADÜFİ VARIANT SEÇİMİ:</strong><br>";
            echo "Mövcud variantlar: " . implode(', ', $variant_keys) . "<br>";
            echo "Random seed: $random_seed<br>";
            echo "Seçilən index: $random_index<br>";
            echo "Seçilən variant: <strong style='color: red; font-size: 1.2em;'>$selected_variant</strong><br>";
            echo "Vaxt: " . date('Y-m-d H:i:s') . "<br>";
            echo "</div>";
        }
    }
} else {
    if ($debug_mode) {
        echo "<div class='alert alert-warning'>";
        echo "<strong>⚠️ Heç bir variant tapılmadı!</strong><br>";
        echo "Subject ID: " . $exam['id_subject'] . "<br>";
        echo "</div>";
    }
}

// FETCH QUESTIONS WITH RANDOM SHUFFLE
if (!empty($selected_variant)) {
    $query = "SELECT qr.id_question_text, qr.question_text, qr.question_score, 
                     qt.quest_type_name, qt.question_var,
                     qf.file_title, qf.file_path, qf.file_type, qf.id_read_quest_file
              FROM question_read qr
              JOIN question_files qf ON qr.id_read_quest_file = qf.id_read_quest_file
              JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
              WHERE qf.subject_id = :subject_id 
                AND qf.file_title IS NOT NULL 
                AND qf.file_title != ''
                AND qf.file_title LIKE :variant_pattern
              ORDER BY RAND()"; // RANDOM ORDER!
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":subject_id", $exam['id_subject']);
    $variant_pattern = '%Variant ' . $selected_variant . '%';
    $stmt->bindParam(":variant_pattern", $variant_pattern);
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($debug_mode && !empty($questions)) {
        echo "<div class='alert alert-info'>";
        echo "<strong>📝 SUAL SEÇİMİ (Variant: $selected_variant)</strong><br>";
        echo "Tapılan sual sayı: " . count($questions) . "<br>";
        echo "SQL Pattern: $variant_pattern<br>";
        echo "İlk 3 sualın ID-ləri: ";
        for ($i = 0; $i < min(3, count($questions)); $i++) {
            echo $questions[$i]['id_question_text'] . " ";
        }
        echo "<br></div>";
    }
    
} else {
    // Variant yoxdursa - bütün sualları random götür
    $query = "SELECT qr.id_question_text, qr.question_text, qr.question_score, 
                     qt.quest_type_name, qt.question_var,
                     qf.file_title, qf.file_path, qf.file_type, qf.id_read_quest_file
              FROM question_read qr
              JOIN question_files qf ON qr.id_read_quest_file = qf.id_read_quest_file
              JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
              WHERE qf.subject_id = :subject_id 
                AND (qf.file_title IS NULL OR qf.file_title = '')
              ORDER BY RAND()"; // RANDOM ORDER!
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":subject_id", $exam['id_subject']);
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($debug_mode) {
        echo "<div class='alert alert-warning'>";
        echo "<strong>📝 VARİANTSIZ SUAL SEÇİMİ</strong><br>";
        echo "Tapılan sual sayı: " . count($questions) . "<br>";
        echo "</div>";
    }
}

// Fallback if no questions found for selected variant
if (empty($questions) && !empty($selected_variant)) {
    $query = "SELECT qr.id_question_text, qr.question_text, qr.question_score, 
                     qt.quest_type_name, qt.question_var,
                     qf.file_title, qf.file_path, qf.file_type, qf.id_read_quest_file
              FROM question_read qr
              JOIN question_files qf ON qr.id_read_quest_file = qf.id_read_quest_file
              JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
              WHERE qf.subject_id = :subject_id 
                AND (qf.file_title IS NULL OR qf.file_title = '')
              ORDER BY 
                CASE qf.file_type 
                    WHEN 'reading' THEN 1 
                    WHEN 'listening' THEN 2 
                    WHEN 'writing' THEN 3 
                    WHEN 'speaking' THEN 4 
                    ELSE 5 
                END, 
                qf.id_read_quest_file, qr.id_question_text";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":subject_id", $exam['id_subject']);
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $selected_variant = null;
}

// Check if questions exist
if (empty($questions)) {
    echo '<div class="alert alert-danger">';
    echo '<i class="fas fa-exclamation-triangle me-2"></i>';
    echo '<strong>XƏTA: Bu imtahan üçün sual tapılmadı!</strong><br>';
    echo '<br><button class="btn btn-primary" onclick="window.location.reload()">Səhifəni Yenilə</button>';
    echo '</div>';
    exit();
}

// Get user's previous answers
$user_answers = array();
$query = "SELECT id_questions, user_answer FROM answers 
          WHERE exam_id = :exam_id AND user_id = :user_id AND user_answer != 'EXAM_STARTED'";
$stmt = $db->prepare($query);
$stmt->bindParam(":exam_id", $exam_id);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$answers_result = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($answers_result as $answer) {
    $user_answers[$answer['id_questions']] = $answer['user_answer'];
}

// GROUP QUESTIONS AND LOAD CONTENT
$grouped_questions = array();
$file_contents = array();
$audio_paths = array();

foreach ($questions as $question) {
    $file_id = isset($question['id_read_quest_file']) ? $question['id_read_quest_file'] : 0;
    $file_type = isset($question['file_type']) ? $question['file_type'] : 'reading';
    $group_key = $file_type . '_' . $file_id;
    
    if (!isset($grouped_questions[$group_key])) {
        $grouped_questions[$group_key] = array(
            'questions' => array(),
            'file_type' => $file_type,
            'file_path' => isset($question['file_path']) ? $question['file_path'] : null,
            'file_id' => $file_id,
            'variant' => $selected_variant
        );
        
        // Load reading content
        if ($file_type == 'reading' && !empty($question['file_path'])) {
            $reading_content = loadReadingContent($question['file_path']);
            $file_contents[$group_key] = $reading_content;
        }
        
        // Load audio paths
        if ($file_type == 'listening' && !empty($question['file_path'])) {
            $audio_path = getValidAudioPath($question['file_path']);
            $audio_paths[$group_key] = $audio_path;
        }
    }
    
    $grouped_questions[$group_key]['questions'][] = $question;
}

// Sort by type
$type_order = ['reading' => 1, 'listening' => 2, 'writing' => 3, 'speaking' => 4];
uksort($grouped_questions, function($a, $b) use ($type_order) {
    $type_a = explode('_', $a)[0];
    $type_b = explode('_', $b)[0];
    
    $order_a = isset($type_order[$type_a]) ? $type_order[$type_a] : 999;
    $order_b = isset($type_order[$type_b]) ? $type_order[$type_b] : 999;
    
    return $order_a - $order_b;
});

// Calculate statistics
$question_stats = [
    'reading' => 0,
    'listening' => 0,
    'writing' => 0,
    'speaking' => 0,
    'total' => 0
];

foreach ($grouped_questions as $group_data) {
    $type = $group_data['file_type'];
    $count = count($group_data['questions']);
    if (isset($question_stats[$type])) {
        $question_stats[$type] += $count;
    }
    $question_stats['total'] += $count;
}

$pageTitle = "İmtahan: " . $exam['subjectname'];

// FORM SUBMISSION HANDLING
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_exam'])) {
    try {
        $db->beginTransaction();
        
        $all_questions = [];
        foreach ($grouped_questions as $group_data) {
            $all_questions = array_merge($all_questions, $group_data['questions']);
        }
        
        foreach ($all_questions as $question) {
            $question_id = $question['id_question_text'];
            $question_type = $question['question_var'];
            $user_answer = '';
            $correct_answer = '';
            
            // Get correct answer based on question type
            if ($question_type == 'multiple') {
                $query = "SELECT correct_v FROM multiple_questions WHERE id_question_text = :id_question_text";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id_question_text", $question_id);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $correct_answer = $result ? $result['correct_v'] : '';
                $user_answer = isset($_POST['answer'][$question_id]) ? $_POST['answer'][$question_id] : '';
                
            } elseif ($question_type == 'open') {
                $query = "SELECT corr_v FROM open_questions WHERE id_question_text = :id_question_text";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id_question_text", $question_id);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $correct_answer = $result ? $result['corr_v'] : '';
                $user_answer = isset($_POST['answer'][$question_id]) ? trim($_POST['answer'][$question_id]) : '';
                
            } elseif ($question_type == 'matching') {
                $query = "SELECT corr_variant FROM matching_questions WHERE id_question_text = :id_question_text";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id_question_text", $question_id);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $correct_answer = $result ? $result['corr_variant'] : '';
                $user_answer = isset($_POST['answer'][$question_id]) ? $_POST['answer'][$question_id] : '';
            }
            
            // Check correctness
            $is_correct = null;
            $score_earned = 0;
            
            if (!empty($user_answer) && !empty($correct_answer)) {
                if ($question_type == 'multiple' || $question_type == 'matching') {
                    $is_correct = (strtolower(trim($user_answer)) == strtolower(trim($correct_answer))) ? 1 : 0;
                } elseif ($question_type == 'open') {
                    $user_lower = strtolower(trim($user_answer));
                    $correct_lower = strtolower(trim($correct_answer));
                    
                    if ($user_lower == $correct_lower) {
                        $is_correct = 1;
                    } elseif (strpos($user_lower, $correct_lower) !== false || strpos($correct_lower, $user_lower) !== false) {
                        $is_correct = 1;
                    } else {
                        $is_correct = 0;
                    }
                }
                
                if ($is_correct == 1) {
                    $score_earned = $question['question_score'];
                }
            }
            
            // Save answer to database
            $query = "SELECT id_answer FROM answers 
                      WHERE exam_id = :exam_id AND id_questions = :question_id AND user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":exam_id", $exam_id);
            $stmt->bindParam(":question_id", $question_id);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            
            $now = date('Y-m-d H:i:s');
            
            if ($stmt->rowCount() > 0) {
                // Update existing answer
                $answer_id = $stmt->fetch(PDO::FETCH_ASSOC)['id_answer'];
                
                $query = "UPDATE answers 
                          SET user_answer = :user_answer, correct_var = :correct_answer, 
                              is_correct = :is_correct, score_earned = :score_earned, datetime = :datetime
                          WHERE id_answer = :id_answer";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":user_answer", $user_answer);
                $stmt->bindParam(":correct_answer", $correct_answer);
                $stmt->bindParam(":is_correct", $is_correct);
                $stmt->bindParam(":score_earned", $score_earned);
                $stmt->bindParam(":datetime", $now);
                $stmt->bindParam(":id_answer", $answer_id);
                $stmt->execute();
                
            } else {
                // Insert new answer
                $query = "INSERT INTO answers (exam_id, id_questions, user_id, user_answer, correct_var, 
                                             is_correct, score_earned, datetime)
                          VALUES (:exam_id, :question_id, :user_id, :user_answer, :correct_answer, 
                                  :is_correct, :score_earned, :datetime)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":exam_id", $exam_id);
                $stmt->bindParam(":question_id", $question_id);
                $stmt->bindParam(":user_id", $user_id);
                $stmt->bindParam(":user_answer", $user_answer);
                $stmt->bindParam(":correct_answer", $correct_answer);
                $stmt->bindParam(":is_correct", $is_correct);
                $stmt->bindParam(":score_earned", $score_earned);
                $stmt->bindParam(":datetime", $now);
                $stmt->execute();
            }
        }
        
        // Update exam status to completed
        $complete_query = "UPDATE exams SET status = 'completed' WHERE id_exam = :exam_id";
        $complete_stmt = $db->prepare($complete_query);
        $complete_stmt->bindParam(":exam_id", $exam_id);
        $complete_stmt->execute();
        
        $db->commit();
        
        // Redirect to results page
        header("Location: exams.php?tab=results&success=1");
        exit();
        
    } catch (PDOException $e) {
        $db->rollBack();
        echo '<div class="alert alert-danger">Xəta: ' . $e->getMessage() . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- CACHE PREVENTION -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        min-height: 100vh;
        color: #1e293b;
    }
    
    .exam-container {
        max-width: 1600px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .exam-header {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: white;
        padding: 2rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }
    
    .variant-info {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 1rem 2rem;
        border-radius: 8px;
        margin-bottom: 2rem;
        text-align: center;
    }
    
    .exam-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-bottom: 3rem;
    }
    
    .exam-section.no-reading {
        grid-template-columns: 1fr;
    }
    
    .questions-panel {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
    }
    
    .reading-panel {
        background: white;
        border-radius: 12px;
        padding: 0;
        width: 700px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
        max-height: 1200px;
        overflow: hidden;
    }
    
    .reading-header {
        background: linear-gradient(135deg, #06b6d4 0%, #0284c7 100%);
        color: white;
        padding: 1rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .reading-content {
        padding: 2rem;
        max-height: 1100px;
        overflow-y: auto;
        font-family: Georgia, 'Times New Roman', serif;
        font-size: 1.1rem;
        line-height: 1.8;
        background: #f8fafc;
    }
    
    .reading-controls {
        display: flex;
        gap: 0.5rem;
    }
    
    .font-btn {
        width: 32px;
        height: 32px;
        border: none;
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .font-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
    }
    
    .question-item {
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
    }
    
    .question-item:hover {
        border-color: #2563eb;
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
    }
    
    .form-check {
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 6px;
        padding: 1rem;
        margin-bottom: 0.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .form-check:hover {
        border-color: #2563eb;
        background: #eff6ff;
    }
    
    .form-check:has(.form-check-input:checked) {
        background: #eff6ff;
        border-color: #2563eb;
    }
    
    .submit-container {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        margin-top: 2rem;
    }
    
    .btn-submit {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 1rem 3rem;
        font-size: 1.25rem;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
    }
    
    .alert {
        border-radius: 8px;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        border: none;
    }
    
    .alert-info {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        color: #1e40af;
        border-left: 4px solid #2563eb;
    }
    
    .alert-warning {
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        color: #92400e;
        border-left: 4px solid #f59e0b;
    }
    
    .alert-danger {
        background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%);
        color: #b91c1c;
        border-left: 4px solid #ef4444;
    }
    
    .alert-success {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        color: #15803d;
        border-left: 4px solid #10b981;
    }
    
    .audio-controls {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin: 2rem 0;
    }
    
    .audio-btn {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .audio-btn:hover {
        transform: translateY(-2px);
    }
    
    @media (max-width: 992px) {
        .exam-section {
            grid-template-columns: 1fr !important;
            gap: 1.5rem;
        }
        
        .reading-panel {
            order: -1;
            max-height: 400px;
        }
        
        .audio-controls {
            flex-wrap: wrap;
        }
        
        .audio-btn {
            min-width: 120px;
        }
    }
    </style>
</head>
<body>
    <div class="exam-container">
        <!-- Exam Header -->
        <div class="exam-header">
            <h1 class="mb-2">
                <i class="fas fa-graduation-cap me-2"></i>
                İmtahan: <?php echo htmlspecialchars($exam['subjectname']); ?>
            </h1>
            <p class="mb-0 opacity-75">
                <i class="fas fa-clock me-2"></i>
                Müddət: <?php echo $exam['timer']; ?> dəqiqə
            </p>
        </div>

      

        <?php if ($debug_mode): ?>
        <div class="alert alert-info">
            <strong>🔍 Debug rejimi aktivdir</strong>
            <a href="?id=<?php echo $exam_id; ?>" class="btn btn-sm btn-outline-primary ms-2">Debug-sız göstər</a>
        </div>
        <?php endif; ?>

        <form method="post" action="" id="examForm">
            <?php 
            $question_counter = 1;
            
            foreach ($grouped_questions as $group_key => $group_data): 
                $file_questions = $group_data['questions'];
                $file_type = $group_data['file_type'];
                $has_reading_content = ($file_type == 'reading' && isset($file_contents[$group_key]));
                $has_listening_content = ($file_type == 'listening' && isset($audio_paths[$group_key]) && !empty($audio_paths[$group_key]));
                $has_material_panel = $has_reading_content || $has_listening_content;
            ?>
            
            <div class="exam-section <?php echo $has_material_panel ? '' : 'no-reading'; ?>">
                <!-- Questions Panel -->
                <div class="questions-panel">
                    <h3 class="text-<?php echo $file_type == 'reading' ? 'info' : ($file_type == 'listening' ? 'success' : ($file_type == 'writing' ? 'warning' : 'danger')); ?> mb-4">
                        <i class="fas fa-<?php echo $file_type == 'reading' ? 'book-open' : ($file_type == 'listening' ? 'headphones' : ($file_type == 'writing' ? 'pen' : 'microphone')); ?> me-2"></i>
                        <?php echo ucfirst($file_type); ?> Sualları (<?php echo count($file_questions); ?> ədəd)
                    </h3>
                    
                    <?php foreach ($file_questions as $question): ?>
                        <div class="question-item">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">
                                    <i class="fas fa-question-circle me-2"></i>
                                    Sual <?php echo $question_counter; ?>
                                </h5>
                                <span class="badge bg-<?php echo $file_type == 'reading' ? 'info' : ($file_type == 'listening' ? 'success' : ($file_type == 'writing' ? 'warning' : 'danger')); ?>">
                                    <?php echo $question['question_score']; ?> bal
                                </span>
                            </div>
                            
                            <p class="mb-3 fw-medium"><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></p>
                            
                            <?php
                            // Display options based on question type
                            if ($question['question_var'] == 'multiple') {
                                $query = "SELECT var_a, var_b, var_c, var_d FROM multiple_questions 
                                          WHERE id_question_text = :id_question_text";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(":id_question_text", $question['id_question_text']);
                                $stmt->execute();
                                $options = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($options) {
                                    $current_answer = isset($user_answers[$question['id_question_text']]) ? 
                                                    $user_answers[$question['id_question_text']] : '';
                                ?>
                                    
                                    <?php if (!empty($options['var_a'])): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="answer[<?php echo $question['id_question_text']; ?>]" 
                                            id="option_a_<?php echo $question['id_question_text']; ?>" value="a"
                                            <?php echo $current_answer == 'a' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="option_a_<?php echo $question['id_question_text']; ?>">
                                            <strong>A)</strong> <?php echo htmlspecialchars($options['var_a']); ?>
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($options['var_b'])): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="answer[<?php echo $question['id_question_text']; ?>]" 
                                            id="option_b_<?php echo $question['id_question_text']; ?>" value="b"
                                            <?php echo $current_answer == 'b' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="option_b_<?php echo $question['id_question_text']; ?>">
                                            <strong>B)</strong> <?php echo htmlspecialchars($options['var_b']); ?>
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($options['var_c'])): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="answer[<?php echo $question['id_question_text']; ?>]" 
                                            id="option_c_<?php echo $question['id_question_text']; ?>" value="c"
                                            <?php echo $current_answer == 'c' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="option_c_<?php echo $question['id_question_text']; ?>">
                                            <strong>C)</strong> <?php echo htmlspecialchars($options['var_c']); ?>
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($options['var_d'])): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="answer[<?php echo $question['id_question_text']; ?>]" 
                                            id="option_d_<?php echo $question['id_question_text']; ?>" value="d"
                                            <?php echo $current_answer == 'd' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="option_d_<?php echo $question['id_question_text']; ?>">
                                            <strong>D)</strong> <?php echo htmlspecialchars($options['var_d']); ?>
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                    
                                <?php } else { ?>
                                    <div class="alert alert-danger">Variant məlumatları tapılmadı.</div>
                                <?php }
                                
                            } elseif ($question['question_var'] == 'open') {
                                // Open-ended question
                                $current_answer = isset($user_answers[$question['id_question_text']]) ? 
                                                  $user_answers[$question['id_question_text']] : '';
                                ?>
                                
                                <div class="mb-3">
                                    <label for="open_answer_<?php echo $question['id_question_text']; ?>" class="form-label fw-bold">Cavabınız:</label>
                                    <textarea class="form-control" id="open_answer_<?php echo $question['id_question_text']; ?>" 
                                        name="answer[<?php echo $question['id_question_text']; ?>]" rows="4" 
                                        placeholder="Cavabınızı buraya yazın..."><?php echo htmlspecialchars($current_answer); ?></textarea>
                                </div>
                                
                            <?php } elseif ($question['question_var'] == 'matching') {
                                // Matching question
                                $query = "SELECT variants, corr_variant FROM matching_questions 
                                          WHERE id_question_text = :id_question_text";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(":id_question_text", $question['id_question_text']);
                                $stmt->execute();
                                $matching = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($matching) {
                                    $variants = explode(',', $matching['variants']);
                                    $current_answer = isset($user_answers[$question['id_question_text']]) ? 
                                                    $user_answers[$question['id_question_text']] : '';
                                    ?>
                                    
                                    <div class="mb-3">
                                        <label for="matching_answer_<?php echo $question['id_question_text']; ?>" class="form-label fw-bold">Düzgün uyğunluğu seçin:</label>
                                        <select class="form-select" id="matching_answer_<?php echo $question['id_question_text']; ?>" 
                                            name="answer[<?php echo $question['id_question_text']; ?>]">
                                            <option value="">-- Seçin --</option>
                                            <?php foreach ($variants as $variant): ?>
                                                <option value="<?php echo htmlspecialchars(trim($variant)); ?>" 
                                                    <?php echo $current_answer == trim($variant) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars(trim($variant)); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                <?php } else { ?>
                                    <div class="alert alert-danger">Uyğunluq məlumatları tapılmadı.</div>
                                <?php }
                            } ?>
                        </div>
                        <?php $question_counter++; ?>
                    <?php endforeach; ?>
                </div>
                
                <!-- Material Panel - Reading or Listening -->
                <?php if ($has_material_panel): ?>
                <div class="reading-panel">
                    <?php if ($has_reading_content): ?>
                        <!-- Reading Panel -->
                        <div class="reading-header">
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <h5 class="mb-0">
                                    <i class="fas fa-book-reader me-2"></i>
                                    Oxunacaq mətn
                                </h5>
                                <div class="reading-controls">
                                    <button type="button" class="font-btn" onclick="changeFontSize(-0.1)" title="Şrift kiçilt">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <button type="button" class="font-btn" onclick="changeFontSize(0.1)" title="Şrift böyüt">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button type="button" class="font-btn" onclick="toggleFullscreen()" title="Tam ekran">
                                        <i class="fas fa-expand-arrows-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="reading-content" id="readingContent">
                            <?php 
                            if (!empty($file_contents[$group_key])) {
                                echo nl2br(htmlspecialchars($file_contents[$group_key]));
                            } else {
                                echo "<div class='alert alert-warning text-center p-4'>";
                                echo "<i class='fas fa-exclamation-triangle fa-2x mb-3 text-warning'></i><br>";
                                echo "<h5>Oxunacaq mətn tapılmadı</h5>";
                                echo "<p class='mb-2'>Fayl yolu: <code>" . htmlspecialchars($group_data['file_path']) . "</code></p>";
                                
                                if ($debug_mode) {
                                    echo "<p class='small text-muted mb-0'>Debug rejimində daha ətraflı məlumat üçün səhifəni yeniləyin.</p>";
                                } else {
                                    echo "<p class='small text-muted mb-0'>Problemin həlli üçün <code>?debug=1</code> əlavə edin.</p>";
                                }
                                echo "</div>";
                            }
                            ?>
                        </div>
                        
                    <?php elseif ($has_listening_content): ?>
                        <!-- Listening Panel -->
                        <div class="reading-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <h5 class="mb-0">
                                    <i class="fas fa-headphones me-2"></i>
                                    Dinləmə materialı
                                </h5>
                                <div class="reading-controls">
                                    <button type="button" class="font-btn" onclick="toggleFullscreen()" title="Tam ekran">
                                        <i class="fas fa-expand-arrows-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="reading-content" style="background: #f0fdf4; display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 400px;">
                            <?php if (!empty($audio_paths[$group_key])): ?>
                                <div class="text-center w-100" style="max-width: 600px;">
                                    <div class="mb-4">
                                        <i class="fas fa-play-circle fa-4x text-success mb-3"></i>
                                        <h4 class="text-success">Dinləmə Audio</h4>
                                    </div>
                                    
                                    <audio controls preload="metadata" class="w-100 mb-4" style="height: 60px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);" id="mainAudio_<?php echo $group_key; ?>">
                                        <source src="<?php echo htmlspecialchars($audio_paths[$group_key]); ?>" type="audio/mpeg">
                                        <source src="<?php echo htmlspecialchars($audio_paths[$group_key]); ?>" type="audio/mp3">
                                        <source src="<?php echo htmlspecialchars($audio_paths[$group_key]); ?>" type="audio/wav">
                                        <source src="<?php echo htmlspecialchars($audio_paths[$group_key]); ?>" type="audio/ogg">
                                        <source src="<?php echo htmlspecialchars($audio_paths[$group_key]); ?>" type="audio/m4a">
                                        Sizin brauzeriniz audio elementini dəstəkləmir.
                                    </audio>
                                    
                                    <div class="row g-2 mb-4">
                                        <div class="col-md-3">
                                            <button type="button" class="btn btn-success w-100" onclick="playAudio('<?php echo $group_key; ?>')">
                                                <i class="fas fa-play me-2"></i>Oynat
                                            </button>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="button" class="btn btn-warning w-100" onclick="pauseAudio('<?php echo $group_key; ?>')">
                                                <i class="fas fa-pause me-2"></i>Dayan
                                            </button>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="button" class="btn btn-info w-100" onclick="restartAudio('<?php echo $group_key; ?>')">
                                                <i class="fas fa-redo me-2"></i>Yenidən
                                            </button>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="button" class="btn btn-secondary w-100" onclick="adjustVolume('<?php echo $group_key; ?>')">
                                                <i class="fas fa-volume-up me-2"></i>Səs
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <h6 class="text-info mb-2"><i class="fas fa-info-circle me-2"></i>Dinləmə Təlimatları:</h6>
                                        <ul class="mb-0 small text-start">
                                            <li>Audio-nu diqqətlə dinləyin və sualları cavablandırın</li>
                                            <li>Audio-nu lazım gəldikdə yenidən dinləyə bilərsiniz</li>
                                            <li>Əgər audio yüklənmirsə, səhifəni yenidən yükləyin</li>
                                            <li>Səs səviyyəsini tənzimləmək üçün "Səs" düyməsini istifadə edin</li>
                                        </ul>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning text-center p-4">
                                    <i class="fas fa-exclamation-triangle fa-2x mb-3 text-warning"></i><br>
                                    <h5>Audio fayl tapılmadı</h5>
                                    <p class="mb-2">Fayl yolu: <code><?php echo htmlspecialchars($group_data['file_path']); ?></code></p>
                                    
                                    <?php if ($debug_mode): ?>
                                        <p class="small text-muted mb-0">Debug rejimində daha ətraflı məlumat üçün səhifəni yeniləyin.</p>
                                    <?php else: ?>
                                        <p class="small text-muted mb-0">Problemin həlli üçün <code>?debug=1</code> əlavə edin.</p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <?php endforeach; ?>
            
            <!-- Submit Button -->
            <div class="submit-container">
                <button type="submit" name="submit_exam" class="btn-submit" onclick="return confirm('İmtahanı bitirmək istədiyinizə əminsiniz?')">
                    <i class="fas fa-check-circle me-2"></i>İmtahanı bitir (<?php echo $question_stats['total']; ?> sual)
                </button>
                <p class="mt-3 text-muted mb-0">
                    <small>
                        <?php
                        $total_points = 0;
                        foreach ($grouped_questions as $group_data) {
                            foreach ($group_data['questions'] as $q) {
                                $total_points += $q['question_score'];
                            }
                        }
                        
                        $section_info = [];
                        if ($question_stats['reading'] > 0) $section_info[] = "Reading: {$question_stats['reading']} sual";
                        if ($question_stats['listening'] > 0) $section_info[] = "Listening: {$question_stats['listening']} sual";
                        if ($question_stats['writing'] > 0) $section_info[] = "Writing: {$question_stats['writing']} sual";
                        if ($question_stats['speaking'] > 0) $section_info[] = "Speaking: {$question_stats['speaking']} sual";
                        
                        echo implode(' • ', $section_info);
                        echo "<br>Cəmi: {$total_points} bal";
                        
                        if (!empty($selected_variant)) {
                            echo " • Variant: $selected_variant";
                        }
                        ?>
                    </small>
                </p>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    let currentFontSize = 1.1;
    
    // Font size control functions
    function changeFontSize(delta) {
        const readingContent = document.getElementById('readingContent');
        if (readingContent) {
            currentFontSize += delta;
            currentFontSize = Math.max(0.8, Math.min(1.6, currentFontSize));
            readingContent.style.fontSize = currentFontSize + 'rem';
        }
    }
    
    // Fullscreen toggle function
    function toggleFullscreen() {
        const readingPanel = document.querySelector('.reading-panel');
        if (readingPanel) {
            if (!document.fullscreenElement) {
                readingPanel.requestFullscreen().catch(err => {
                    console.log('Fullscreen error:', err);
                });
            } else {
                document.exitFullscreen();
            }
        }
    }
    
    // Audio control functions
    function playAudio(groupKey) {
        const audio = document.getElementById('mainAudio_' + groupKey);
        if (audio) {
            audio.play().catch(err => {
                console.log('Audio play error:', err);
                alert('Audio oynadılmadı. Səhifəni yenidən yükləyin.');
            });
        }
    }
    
    function pauseAudio(groupKey) {
        const audio = document.getElementById('mainAudio_' + groupKey);
        if (audio) {
            audio.pause();
        }
    }
    
    function restartAudio(groupKey) {
        const audio = document.getElementById('mainAudio_' + groupKey);
        if (audio) {
            audio.currentTime = 0;
            audio.play().catch(err => {
                console.log('Audio restart error:', err);
            });
        }
    }
    
    function adjustVolume(groupKey) {
        const audio = document.getElementById('mainAudio_' + groupKey);
        if (audio) {
            const currentVolume = Math.round(audio.volume * 100);
            const newVolume = prompt('Səs səviyyəsini daxil edin (0-100):', currentVolume);
            
            if (newVolume !== null && !isNaN(newVolume)) {
                const volume = Math.max(0, Math.min(100, parseInt(newVolume))) / 100;
                audio.volume = volume;
            }
        }
    }
    
    // Document ready functions
    document.addEventListener('DOMContentLoaded', function() {
        // SHUFFLE MULTIPLE CHOICE OPTIONS
        const questionItems = document.querySelectorAll('.question-item');
        questionItems.forEach(item => {
            const formChecks = item.querySelectorAll('.form-check');
            if (formChecks.length > 1) {
                // Convert NodeList to Array
                const formChecksArray = Array.from(formChecks);
                
                // Shuffle the array
                for (let i = formChecksArray.length - 1; i > 0; i--) {
                    const j = Math.floor(Math.random() * (i + 1));
                    [formChecksArray[i], formChecksArray[j]] = [formChecksArray[j], formChecksArray[i]];
                }
                
                // Re-append in new order
                const parent = formChecks[0].parentNode;
                formChecksArray.forEach(check => {
                    parent.appendChild(check);
                });
            }
        });
        
        // Form submission handling
        document.getElementById('examForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[name="submit_exam"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Təqdim edilir...';
            }
        });
        
        // Enhanced form validation
        const inputs = document.querySelectorAll('input[type="radio"], textarea, select');
        inputs.forEach(input => {
            input.addEventListener('change', function() {
                const questionItem = this.closest('.question-item');
                if (questionItem) {
                    if (this.type === 'radio' && this.checked) {
                        questionItem.style.backgroundColor = '#f0fdf4';
                        questionItem.style.borderColor = '#10b981';
                    } else if ((this.type === 'textarea' || this.tagName === 'SELECT') && this.value.trim() !== '') {
                        questionItem.style.backgroundColor = '#f0fdf4';
                        questionItem.style.borderColor = '#10b981';
                    }
                }
            });
        });
        
        // Timer functionality (if needed)
        <?php if ($exam['timer'] > 0): ?>
        let timeLeft = <?php echo $exam['timer'] * 60; ?>; // Convert minutes to seconds
        
        function updateTimer() {
            const hours = Math.floor(timeLeft / 3600);
            const minutes = Math.floor((timeLeft % 3600) / 60);
            const seconds = timeLeft % 60;
            
            const timerDisplay = document.createElement('div');
            timerDisplay.id = 'examTimer';
            timerDisplay.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                color: white;
                padding: 1rem;
                border-radius: 8px;
                font-weight: bold;
                z-index: 1000;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            `;
            
            if (hours > 0) {
                timerDisplay.innerHTML = `<i class="fas fa-clock me-2"></i>${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            } else {
                timerDisplay.innerHTML = `<i class="fas fa-clock me-2"></i>${minutes}:${seconds.toString().padStart(2, '0')}`;
            }
            
            const existingTimer = document.getElementById('examTimer');
            if (existingTimer) {
                existingTimer.innerHTML = timerDisplay.innerHTML;
            } else {
                document.body.appendChild(timerDisplay);
            }
            
            if (timeLeft <= 0) {
                alert('Vaxt bitdi! İmtahan avtomatik olaraq təqdim ediləcək.');
                document.getElementById('examForm').submit();
                return;
            }
            
            timeLeft--;
        }
        
        // Update timer every second
        updateTimer();
        setInterval(updateTimer, 1000);
        <?php endif; ?>
        
        console.log('🎲 RANDOM EXAM SYSTEM LOADED!');
        console.log('Total questions:', <?php echo $question_stats['total']; ?>);
        console.log('Random variant:', '<?php echo $selected_variant ?: 'NONE'; ?>');
        console.log('Current time:', new Date().toLocaleString());
    });
    
    // Prevent accidental page refresh
    window.addEventListener('beforeunload', function(e) {
        const confirmationMessage = 'İmtahan davam edir. Səhifəni tərk etmək istədiyinizə əminsiniz?';
        e.returnValue = confirmationMessage;
        return confirmationMessage;
    });
    
    // Remove beforeunload when form is submitted
    document.getElementById('examForm').addEventListener('submit', function() {
        window.removeEventListener('beforeunload', function() {});
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl + Enter to submit
        if (e.ctrlKey && e.key === 'Enter') {
            if (confirm('İmtahanı bitirmək istədiyinizə əminsiniz?')) {
                document.getElementById('examForm').submit();
            }
        }
        
        // F11 for fullscreen reading panel
        if (e.key === 'F11') {
            e.preventDefault();
            toggleFullscreen();
        }
        
        // Prevent F5 refresh accidentally
        if (e.key === 'F5') {
            e.preventDefault();
            if (confirm('Səhifəni yeniləmək istədiyinizə əminsiniz? Cavablarınız itə bilər.')) {
                location.reload();
            }
        }
    });
    
    // Save answers to localStorage as backup (if needed)
    function saveAnswersToLocal() {
        const formData = new FormData(document.getElementById('examForm'));
        const answers = {};
        
        for (let [key, value] of formData.entries()) {
            if (key.startsWith('answer[')) {
                answers[key] = value;
            }
        }
        
        try {
            localStorage.setItem('exam_backup_<?php echo $exam_id; ?>', JSON.stringify(answers));
        } catch (e) {
            console.log('LocalStorage not available');
        }
    }
    
    // Load backup answers on page load
    function loadBackupAnswers() {
        try {
            const backup = localStorage.getItem('exam_backup_<?php echo $exam_id; ?>');
            if (backup) {
                const answers = JSON.parse(backup);
                for (let [key, value] of Object.entries(answers)) {
                    const input = document.querySelector(`[name="${key}"]`);
                    if (input) {
                        if (input.type === 'radio') {
                            const radioInput = document.querySelector(`[name="${key}"][value="${value}"]`);
                            if (radioInput) radioInput.checked = true;
                        } else if (input.tagName === 'TEXTAREA' || input.tagName === 'SELECT') {
                            input.value = value;
                        }
                    }
                }
            }
        } catch (e) {
            console.log('Error loading backup answers');
        }
    }
    
    // Auto-save answers periodically
    setInterval(saveAnswersToLocal, 10000); // Save every 10 seconds
    
    // Load backup on page load
    loadBackupAnswers();
    </script>

    <!-- Additional JavaScript for enhanced functionality -->
    <script>
    // Progress indicator
    function updateProgress() {
        const totalQuestions = <?php echo $question_stats['total']; ?>;
        const answeredQuestions = document.querySelectorAll('input[type="radio"]:checked, textarea:not([value=""]), select:not([value=""])').length;
        const progress = Math.round((answeredQuestions / totalQuestions) * 100);
        
        let progressBar = document.getElementById('progressBar');
        if (!progressBar) {
            progressBar = document.createElement('div');
            progressBar.id = 'progressBar';
            progressBar.style.cssText = `
                position: fixed;
                bottom: 20px;
                left: 20px;
                right: 20px;
                background: rgba(255, 255, 255, 0.9);
                padding: 1rem;
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                z-index: 1000;
                backdrop-filter: blur(10px);
            `;
            document.body.appendChild(progressBar);
        }
        
      
    }
    
    // Update progress when answers change
    document.addEventListener('change', updateProgress);
    document.addEventListener('input', updateProgress);
    
    // Initial progress update
    setTimeout(updateProgress, 1000);
    
    // Smooth scrolling to next question
    function scrollToNextQuestion(currentQuestion) {
        const questions = document.querySelectorAll('.question-item');
        const currentIndex = Array.from(questions).indexOf(currentQuestion);
        
        if (currentIndex < questions.length - 1) {
            const nextQuestion = questions[currentIndex + 1];
            nextQuestion.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
            
            // Highlight briefly
            nextQuestion.style.boxShadow = '0 0 20px rgba(37, 99, 235, 0.3)';
            setTimeout(() => {
                nextQuestion.style.boxShadow = '';
            }, 2000);
        }
    }
    
    // Add click handlers for automatic scrolling
    document.querySelectorAll('.question-item input, .question-item textarea, .question-item select').forEach(input => {
        input.addEventListener('change', function() {
            const questionItem = this.closest('.question-item');
            setTimeout(() => scrollToNextQuestion(questionItem), 500);
        });
    });
    
    // Warning for unanswered questions before submit
    document.getElementById('examForm').addEventListener('submit', function(e) {
        const totalQuestions = <?php echo $question_stats['total']; ?>;
        const answeredQuestions = document.querySelectorAll('input[type="radio"]:checked').length + 
                                 document.querySelectorAll('textarea').filter(ta => ta.value.trim() !== '').length +
                                 document.querySelectorAll('select').filter(sel => sel.value !== '').length;
        
        if (answeredQuestions < totalQuestions) {
            const unanswered = totalQuestions - answeredQuestions;
            const proceed = confirm(`${unanswered} sual cavabsız qalıb. Davam etmək istəyirsiniz?`);
            
            if (!proceed) {
                e.preventDefault();
                
                // Scroll to first unanswered question
                const firstUnanswered = document.querySelector('.question-item:not(:has(input:checked, textarea:not([value=""]), select:not([value=""])))');
                if (firstUnanswered) {
                    firstUnanswered.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                    firstUnanswered.style.border = '3px solid #ef4444';
                    setTimeout(() => {
                        firstUnanswered.style.border = '';
                    }, 3000);
                }
            }
        }
    });
    
    // Add visual feedback for answered questions
    function markAnsweredQuestions() {
        document.querySelectorAll('.question-item').forEach(item => {
            const hasAnswer = item.querySelector('input:checked') || 
                            (item.querySelector('textarea') && item.querySelector('textarea').value.trim() !== '') ||
                            (item.querySelector('select') && item.querySelector('select').value !== '');
            
            if (hasAnswer) {
                item.classList.add('answered');
                item.style.borderLeftColor = '#10b981';
                item.style.borderLeftWidth = '4px';
            } else {
                item.classList.remove('answered');
                item.style.borderLeftColor = '';
                item.style.borderLeftWidth = '';
            }
        });
    }
    
    // Update answered questions styling
    document.addEventListener('change', markAnsweredQuestions);
    document.addEventListener('input', markAnsweredQuestions);
    
    // Initial marking
    setTimeout(markAnsweredQuestions, 1000);
    
    // Add confirmation dialog with better styling
    function customConfirm(message, callback) {
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            backdrop-filter: blur(5px);
        `;
        
        const dialog = document.createElement('div');
        dialog.style.cssText = `
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            text-align: center;
        `;
        
        dialog.innerHTML = `
            <div class="mb-4">
                <i class="fas fa-question-circle fa-3x text-warning mb-3"></i>
                <h4>Təsdiq</h4>
                <p class="text-muted">${message}</p>
            </div>
            <div class="d-flex gap-2 justify-content-center">
                <button class="btn btn-success px-4" onclick="confirmAction(true)">
                    <i class="fas fa-check me-2"></i>Bəli
                </button>
                <button class="btn btn-secondary px-4" onclick="confirmAction(false)">
                    <i class="fas fa-times me-2"></i>Xeyr
                </button>
            </div>
        `;
        
        overlay.appendChild(dialog);
        document.body.appendChild(overlay);
        
        window.confirmAction = function(result) {
            document.body.removeChild(overlay);
            callback(result);
            delete window.confirmAction;
        };
    }
    </script>

    <!-- Print styles for exam -->
    <style media="print">
    .exam-header, .submit-container, .reading-controls, .audio-controls, #progressBar, #examTimer {
        display: none !important;
    }
    
    .exam-section {
        grid-template-columns: 1fr !important;
        break-inside: avoid;
    }
    
    .question-item {
        break-inside: avoid;
        margin-bottom: 1rem;
    }
    
    .reading-content {
        max-height: none !important;
        overflow: visible !important;
    }
    
    body {
        background: white !important;
        color: black !important;
    }
    </style>

</body>
</html>