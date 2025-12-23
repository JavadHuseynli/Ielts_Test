<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";
checkLogin();

// Yalnız tələbələr girə bilər
if ($_SESSION['status'] != 'student') {
    header("Location: ../admin/dashboard.php");
    exit();
}

// İmtahan ID-si yoxdursa, yönləndirmə
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: exams.php");
    exit();
}

$exam_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$group_id = $_SESSION['group_id'];

$database = new Database();
$db = $database->getConnection();

// İmtahan məlumatlarını almaq
$query = "SELECT e.id_exam, e.datetime, e.status, s.subjectname, s.timer, s.id_subject
          FROM exams e
          JOIN subjects s ON e.id_subject = s.id_subject
          WHERE e.id_exam = :exam_id AND e.id_student_group = :group_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":exam_id", $exam_id);
$stmt->bindParam(":group_id", $group_id);
$stmt->execute();

// İmtahan tapılmadısa, yönləndirmə
if ($stmt->rowCount() == 0) {
    header("Location: exams.php");
    exit();
}

$exam = $stmt->fetch(PDO::FETCH_ASSOC);

// Bu tələbənin artıq bu imtahanı verib-vermədiyini yoxla
$completed_check_query = "SELECT COUNT(*) as completed_count 
                         FROM answers 
                         WHERE exam_id = :exam_id AND user_id = :user_id 
                         AND user_answer != 'EXAM_STARTED'";
$completed_check_stmt = $db->prepare($completed_check_query);
$completed_check_stmt->bindParam(":exam_id", $exam_id);
$completed_check_stmt->bindParam(":user_id", $user_id);
$completed_check_stmt->execute();
$completed_result = $completed_check_stmt->fetch(PDO::FETCH_ASSOC);

// Get the number of questions for this exam
$question_count_query = "SELECT COUNT(*) as total_questions 
                        FROM question_read qr
                        JOIN question_files qf ON qr.id_read_quest_file = qf.id_read_quest_file
                        WHERE qf.subject_id = :subject_id";
$question_count_stmt = $db->prepare($question_count_query);
$question_count_stmt->bindParam(":subject_id", $exam['id_subject']);
$question_count_stmt->execute();
$question_count = $question_count_stmt->fetch(PDO::FETCH_ASSOC);

// Check if student has already completed this exam 
$completion_threshold = $question_count['total_questions'] * 0.7;
if ($completed_result && $completed_result['completed_count'] >= $completion_threshold) {
    // Redirect to results page
    header("Location: exams.php?tab=results&already_taken=1");
    exit();
}

// İmtahan davam etmirsə, yönləndirmə
if ($exam['status'] != 'in_progress') {
    header("Location: exams.php");
    exit();
}

$pageTitle = "İmtahan: " . $exam['subjectname'];

// Timer məlumatlarını friendly formatda hazırla
$exam_duration_minutes = $exam['timer'];
$exam_duration_text = '';

if ($exam_duration_minutes >= 60) {
    $hours = floor($exam_duration_minutes / 60);
    $minutes = $exam_duration_minutes % 60;
    if ($minutes > 0) {
        $exam_duration_text = $hours . ' saat ' . $minutes . ' dəqiqə';
    } else {
        $exam_duration_text = $hours . ' saat';
    }
} else {
    $exam_duration_text = $exam_duration_minutes . ' dəqiqə';
}

// Debug parametri
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';

// Tələbənin imtahana başladığı vaxtı almaq
$query = "SELECT MIN(datetime) as start_time FROM answers WHERE exam_id = :exam_id AND user_id = :user_id AND user_answer != 'EXAM_STARTED'";
$stmt = $db->prepare($query);
$stmt->bindParam(":exam_id", $exam_id);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$start_time_row = $stmt->fetch(PDO::FETCH_ASSOC);
$start_time = $start_time_row['start_time'];

// EXAM_STARTED marker-ni yoxla
$marker_query = "SELECT datetime FROM answers WHERE exam_id = :exam_id AND user_id = :user_id AND user_answer = 'EXAM_STARTED' ORDER BY datetime DESC LIMIT 1";
$marker_stmt = $db->prepare($marker_query);
$marker_stmt->bindParam(":exam_id", $exam_id);
$marker_stmt->bindParam(":user_id", $user_id);
$marker_stmt->execute();
$marker_result = $marker_stmt->fetch(PDO::FETCH_ASSOC);

// Verilənlər bazasında file_title sütunu olub olmadığını yoxla
try {
    $column_exists = false;
    $query = "SHOW COLUMNS FROM question_files LIKE 'file_title'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $column_exists = true;
    }
} catch (PDOException $e) {
    if ($debug_mode) {
        echo "<div class='alert alert-warning'>Column check error: " . $e->getMessage() . "</div>";
    }
}

// VARIANT SİSTEMİ - ENHANCED VERSION (DÜZƏLDİLMİŞ)
if ($column_exists) {
    // Tələbənin əvvəlki cavabları varsa, onun variant-ını təyin et
    $user_variant_query = "SELECT DISTINCT qf.file_title
                          FROM answers a
                          JOIN question_read qr ON a.id_questions = qr.id_question_text
                          JOIN question_files qf ON qr.id_read_quest_file = qf.id_read_quest_file
                          WHERE a.exam_id = :exam_id AND a.user_id = :user_id AND a.user_answer != 'EXAM_STARTED'
                          LIMIT 1";
    $user_variant_stmt = $db->prepare($user_variant_query);
    $user_variant_stmt->bindParam(":exam_id", $exam_id);
    $user_variant_stmt->bindParam(":user_id", $user_id);
    $user_variant_stmt->execute();
    $user_variant_result = $user_variant_stmt->fetch(PDO::FETCH_ASSOC);
    
    $selected_variant = null;
    
    if ($user_variant_result && !empty($user_variant_result['file_title'])) {
        // Tələbənin əvvəlki cavablarından variant təyin edilir
        $variant_parts = explode(' ', trim($user_variant_result['file_title']));
        $user_variant = end($variant_parts);
        
        // Variant filterini təyin et
        if (!empty($user_variant) && preg_match('/^[A-Z]$/', $user_variant)) {
            $selected_variant = $user_variant;
        }
    }
    
    // Əgər tələbə üçün variant seçilməyibsə, yeni variant seç
    if (empty($selected_variant)) {
        // Mövcud variantları al
        $available_variants_query = "SELECT DISTINCT SUBSTRING_INDEX(file_title, ' ', -1) as variant_letter,
                                           COUNT(*) as file_count
                                    FROM question_files
                                    WHERE subject_id = :subject_id 
                                      AND file_title IS NOT NULL 
                                      AND file_title != ''
                                      AND SUBSTRING_INDEX(file_title, ' ', -1) REGEXP '^[A-Z]$'
                                    GROUP BY SUBSTRING_INDEX(file_title, ' ', -1)
                                    HAVING file_count > 0
                                    ORDER BY variant_letter";
        $available_variants_stmt = $db->prepare($available_variants_query);
        $available_variants_stmt->bindParam(":subject_id", $exam['id_subject']);
        $available_variants_stmt->execute();
        $available_variants = $available_variants_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($available_variants)) {
            // Təsadüfi variant seç
            $random_index = rand(0, count($available_variants) - 1);
            $selected_variant = $available_variants[$random_index]['variant_letter'];
        }
    }
    
    // Variant filter yaradılması
    $variant_filter = "";
    if (!empty($selected_variant)) {
        $variant_filter = "AND (SUBSTRING_INDEX(qf.file_title, ' ', -1) = '$selected_variant' OR qf.file_title LIKE '%Variant $selected_variant%')";
    }
    
    // Sualları seçilmiş variant üçün al
    $query = "SELECT qr.id_question_text, qr.question_text, qr.question_score, qt.quest_type_name, qt.question_var,
              qf.file_title, qf.file_path, qf.file_type, qf.id_read_quest_file
              FROM question_read qr
              JOIN question_files qf ON qr.id_read_quest_file = qf.id_read_quest_file
              JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
              WHERE qf.subject_id = :subject_id $variant_filter
              ORDER BY 
                CASE qf.file_type 
                    WHEN 'reading' THEN 1 
                    WHEN 'listening' THEN 2 
                    WHEN 'writing' THEN 3 
                    WHEN 'speaking' THEN 4 
                    ELSE 5 
                END, 
                qf.id_read_quest_file, qr.id_question_text";
} else {
    // file_title sütunu yoxdursa, ənənəvi sıralamanı istifadə et
    $query = "SELECT qr.id_question_text, qr.question_text, qr.question_score, qt.quest_type_name, qt.question_var,
              qf.file_path, qf.file_type, qf.id_read_quest_file
              FROM question_read qr
              JOIN question_files qf ON qr.id_read_quest_file = qf.id_read_quest_file
              JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
              WHERE qf.subject_id = :subject_id
              ORDER BY 
                CASE qf.file_type 
                    WHEN 'reading' THEN 1 
                    WHEN 'listening' THEN 2 
                    WHEN 'writing' THEN 3 
                    WHEN 'speaking' THEN 4 
                    ELSE 5 
                END, 
                qf.id_read_quest_file, qr.id_question_text";
}

$stmt = $db->prepare($query);
$stmt->bindParam(":subject_id", $exam['id_subject']);
$stmt->execute();
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug üçün variant məlumatları
if ($debug_mode && !empty($selected_variant)) {
    echo "<div class='alert alert-info'>";
    echo "<strong>🎯 Seçilmiş Variant:</strong> $selected_variant<br>";
    echo "<strong>📊 Variant Filter:</strong> " . htmlspecialchars($variant_filter) . "<br>";
    echo "<strong>📝 Tapılan Sualların sayı:</strong> " . count($questions) . "<br>";
    
    // Hər tip üçün say
    $type_counts = [];
    foreach ($questions as $q) {
        $type = $q['file_type'] ?? 'unknown';
        $type_counts[$type] = ($type_counts[$type] ?? 0) + 1;
    }
    echo "<strong>📊 Tipə görə paylanma:</strong> " . json_encode($type_counts);
    echo "</div>";
}

// Əgər heç bir sual tapılmadısa və variant var idisə, variant olmadan yenidən cəhd et
if (empty($questions) && !empty($selected_variant)) {
    echo "<div class='alert alert-warning'>";
    echo "<strong>⚠️ Variant $selected_variant üçün sual tapılmadı!</strong><br>";
    echo "Bütün suallar göstəriləcək...";
    echo "</div>";
    
    // Variant filteri olmadan yenidən sorğu
    $query = "SELECT qr.id_question_text, qr.question_text, qr.question_score, qt.quest_type_name, qt.question_var,
              qf.file_title, qf.file_path, qf.file_type, qf.id_read_quest_file
              FROM question_read qr
              JOIN question_files qf ON qr.id_read_quest_file = qf.id_read_quest_file
              JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
              WHERE qf.subject_id = :subject_id
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
    
    // Variant sıfırla
    $selected_variant = null;
}

// İmtahana yeni başlanırsa və ya start_time yoxdursa
if (!$start_time) {
    // Əgər marker varsa, onun vaxtını istifadə et
    if ($marker_result && $marker_result['datetime']) {
        $start_time = $marker_result['datetime'];
    } else {
        // Yeni imtahan başlanır
        $start_time = date('Y-m-d H:i:s');
        
        // İlk sualı al
        $first_question_id = !empty($questions) ? $questions[0]['id_question_text'] : 1;

        // EXAM_STARTED marker-ini əlavə et
        $first_answer_query = "INSERT INTO answers (exam_id, user_id, id_questions, user_answer, correct_var, datetime) 
                              VALUES (:exam_id, :user_id, :question_id, 'EXAM_STARTED', 'EXAM_STARTED', :start_time)
                              ON DUPLICATE KEY UPDATE datetime = :start_time, user_answer = 'EXAM_STARTED'";
        $first_answer_stmt = $db->prepare($first_answer_query);
        $first_answer_stmt->bindParam(":exam_id", $exam_id);
        $first_answer_stmt->bindParam(":user_id", $user_id);
        $first_answer_stmt->bindParam(":question_id", $first_question_id);
        $first_answer_stmt->bindParam(":start_time", $start_time);
        
        try {
            $first_answer_stmt->execute();
        } catch (PDOException $e) {
            if ($debug_mode) {
                echo "<div class='alert alert-warning'>Start time marker xətası: " . $e->getMessage() . "</div>";
            }
        }
    }
}

// Vaxt yoxlanışı
$current_time = time();
$start_timestamp = strtotime($start_time);
$elapsed_minutes = ($current_time - $start_timestamp) / 60;
$remaining_minutes = max(0, $exam['timer'] - $elapsed_minutes);

// Əgər vaxt bitibsə, otomatik olaraq results səhifəsinə yönləndir
if ($remaining_minutes <= 0) {
    // İmtahan statusunu completed et
    $complete_query = "UPDATE exams SET status = 'completed' WHERE id_exam = :exam_id";
    $complete_stmt = $db->prepare($complete_query);
    $complete_stmt->bindParam(":exam_id", $exam_id);
    $complete_stmt->execute();
    
    // Nəticələr səhifəsinə yönləndir
    header("Location: exams.php?tab=results&timeout=1");
    exit();
}

// AJAX vaxt yoxlanışı
if (isset($_GET['check_time']) && $_GET['check_time'] == '1') {
    header('Content-Type: application/json');
    
    $current_time = time();
    $start_timestamp = strtotime($start_time);
    $elapsed_seconds = $current_time - $start_timestamp;
    $exam_duration_seconds = $exam['timer'] * 60;
    $remaining_seconds = max(0, $exam_duration_seconds - $elapsed_seconds);
    
    $response = [
        'time_expired' => $remaining_seconds <= 0,
        'remaining_seconds' => $remaining_seconds,
        'elapsed_seconds' => $elapsed_seconds,
        'server_time' => date('Y-m-d H:i:s'),
        'start_time' => $start_time
    ];
    
    echo json_encode($response);
    exit();
}

// ENHANCED AUDIO PATH FİXİNG FUNKSİYASI
function getValidAudioPath($file_path) {
    if (empty($file_path)) {
        return null;
    }
    
    // Check if it's already a valid URL or absolute path
    if (filter_var($file_path, FILTER_VALIDATE_URL) || (strpos($file_path, 'http') === 0)) {
        return $file_path;
    }
    
    // Müxtəlif path formatlarını yoxla
    $possible_paths = [
        $file_path,                                    // Orijinal path
        '../' . ltrim($file_path, '/'),               // ../ əlavə et
        '../../' . ltrim($file_path, '/'),            // ../../ əlavə et
        '../uploads/' . basename($file_path),         // uploads folderində
        '../audio/' . basename($file_path),           // audio folderində  
        '../files/' . basename($file_path),           // files folderində
        'uploads/' . basename($file_path),            // lokal uploads
        'audio/' . basename($file_path),              // lokal audio
        'files/' . basename($file_path)               // lokal files
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    // Check if original path might work for web access even if file_exists fails
    $audio_extensions = ['mp3', 'wav', 'ogg', 'm4a', 'aac'];
    $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    
    if (in_array($extension, $audio_extensions)) {
        return $file_path; // Return original path for web access
    }
    
    return null;
}

// Sualları TİPƏ VƏ FAYLA GÖRƏ qruplaşdırmaq - DÜZƏLDİLMİŞ VERSİYA
$grouped_questions = array();
$file_titles = array();

foreach ($questions as $question) {
    $file_id = isset($question['id_read_quest_file']) ? $question['id_read_quest_file'] : 0;
    $file_type = isset($question['file_type']) ? $question['file_type'] : 'reading';
    
    // Tip və fayl ID ilə key yarat
    $group_key = $file_type . '_' . $file_id;
    
    if (!isset($grouped_questions[$group_key])) {
        $grouped_questions[$group_key] = array(
            'questions' => array(),
            'file_type' => $file_type,
            'file_path' => isset($question['file_path']) ? $question['file_path'] : null,
            'file_id' => $file_id,
            'variant' => $selected_variant // Variant məlumatını əlavə et
        );
    }
    
    $grouped_questions[$group_key]['questions'][] = $question;
    
    if ($column_exists && isset($question['file_title'])) {
        $file_titles[$group_key] = $question['file_title'];
    }
}

// Tipə görə sıralama
$type_order = ['reading' => 1, 'listening' => 2, 'writing' => 3, 'speaking' => 4];
uksort($grouped_questions, function($a, $b) use ($type_order) {
    $type_a = explode('_', $a)[0];
    $type_b = explode('_', $b)[0];
    
    $order_a = isset($type_order[$type_a]) ? $type_order[$type_a] : 999;
    $order_b = isset($type_order[$type_b]) ? $type_order[$type_b] : 999;
    
    return $order_a - $order_b;
});

// Hər tipə görə nə qədər qrup olduğunu yoxla
$type_group_counts = [];
foreach ($grouped_questions as $group_key => $group_data) {
    $type = $group_data['file_type'];
    $type_group_counts[$type] = ($type_group_counts[$type] ?? 0) + 1;
}

if ($debug_mode) {
    echo "<div class='alert alert-info'>";
    echo "<strong>📊 Qrup sayları:</strong><br>";
    foreach ($type_group_counts as $type => $count) {
        echo "- $type: $count qrup<br>";
    }
    echo "</div>";
}

// Əgər hər tip üçün 1-dən çox qrup varsa, variant sistemi düzgün işləmir
$need_variant_fix = false;
foreach ($type_group_counts as $type => $count) {
    if ($count > 1 && in_array($type, ['reading', 'listening'])) {
        $need_variant_fix = true;
        break;
    }
}

if ($need_variant_fix && !empty($selected_variant)) {
    // Hər tip üçün yalnız bir qrup saxla (ən çox suallı olanı)
    $final_grouped_questions = [];
    
    foreach (['reading', 'listening', 'writing', 'speaking'] as $target_type) {
        $type_groups = [];
        
        // Bu tip üçün bütün qrupları topla
        foreach ($grouped_questions as $group_key => $group_data) {
            if ($group_data['file_type'] == $target_type) {
                $type_groups[$group_key] = $group_data;
            }
        }
        
        // Əgər bu tip üçün qruplar varsa, ən böyüyünü seç
        if (!empty($type_groups)) {
            // Ən çox suallı qrupu tap
            $max_questions = 0;
            $best_group_key = null;
            
            foreach ($type_groups as $group_key => $group_data) {
                $question_count = count($group_data['questions']);
                if ($question_count > $max_questions) {
                    $max_questions = $question_count;
                    $best_group_key = $group_key;
                }
            }
            
            // Ən yaxşı qrupu əlavə et
            if ($best_group_key) {
                $final_grouped_questions[$best_group_key] = $type_groups[$best_group_key];
            }
        }
    }
    
    $grouped_questions = $final_grouped_questions;
    
    if ($debug_mode) {
        echo "<div class='alert alert-success'>";
        echo "<strong>✅ Variant düzəldildi!</strong><br>";
        echo "Hər tip üçün yalnız bir qrup saxlanıldı.<br>";
        $new_type_counts = [];
        foreach ($grouped_questions as $group_data) {
            $type = $group_data['file_type'];
            $new_type_counts[$type] = ($new_type_counts[$type] ?? 0) + 1;
        }
        echo "<strong>Yeni qrup sayları:</strong> " . json_encode($new_type_counts);
        echo "</div>";
    }
}

// Statistika yaratmaq - variant nəzərə alınmaqla
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

// Sual yoxdursa xəta mesajı
if (empty($grouped_questions)) {
    echo '<div class="alert alert-danger">';
    echo '<i class="fas fa-exclamation-triangle me-2"></i>';
    echo '<strong>XƏTA: Bu imtahan üçün sual tapılmadı!</strong><br>';
    echo '<br><button class="btn btn-primary" onclick="window.location.reload()">Səhifəni Yenilə</button>';
    echo '</div>';
    exit();
}

// Tələbənin cavabları tapşırması
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_exam'])) {
    try {
        $db->beginTransaction();
        
        // Bütün sualları yenidən topla
        $all_questions = [];
        foreach ($grouped_questions as $group_data) {
            $all_questions = array_merge($all_questions, $group_data['questions']);
        }
        
        foreach ($all_questions as $question) {
            $question_id = $question['id_question_text'];
            $question_type = $question['question_var'];
            $user_answer = '';
            $correct_answer = '';
            
            // Sual növünə görə doğru cavabı müəyyən etmək
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
            
            // Cavab saxla
            $query = "SELECT id_answer FROM answers 
                      WHERE exam_id = :exam_id AND id_questions = :question_id AND user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":exam_id", $exam_id);
            $stmt->bindParam(":question_id", $question_id);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            
            $now = date('Y-m-d H:i:s');
            
            if ($stmt->rowCount() > 0) {
                // Mövcud cavabı yeniləmək
                $answer_id = $stmt->fetch(PDO::FETCH_ASSOC)['id_answer'];
                
                $query = "UPDATE answers 
                          SET user_answer = :user_answer, correct_var = :correct_answer, datetime = :datetime
                          WHERE id_answer = :id_answer";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":user_answer", $user_answer);
                $stmt->bindParam(":correct_answer", $correct_answer);
                $stmt->bindParam(":datetime", $now);
                $stmt->bindParam(":id_answer", $answer_id);
                $stmt->execute();
            } else {
                // Yeni cavab əlavə etmək
                $query = "INSERT INTO answers (exam_id, id_questions, user_id, user_answer, correct_var, datetime)
                          VALUES (:exam_id, :question_id, :user_id, :user_answer, :correct_answer, :datetime)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":exam_id", $exam_id);
                $stmt->bindParam(":question_id", $question_id);
                $stmt->bindParam(":user_id", $user_id);
                $stmt->bindParam(":user_answer", $user_answer);
                $stmt->bindParam(":correct_answer", $correct_answer);
                $stmt->bindParam(":datetime", $now);
                $stmt->execute();
            }
        }
        
        $db->commit();
        
        // İmtahan nəticələri səhifəsinə yönləndirmek
        header("Location: exams.php?tab=results&success=1");
        exit();
    } catch (PDOException $e) {
        $db->rollBack();
        echo '<div class="alert alert-danger">Xəta: ' . $e->getMessage() . '</div>';
    }
}

// Tələbənin əvvəlki cavablarını almaq
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

// Fayl məzmunları cache
$file_contents = array();
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
    /* ENHANCED EXAM SYSTEM CSS */
    :root {
        --primary-color: #2563eb;
        --secondary-color: #64748b;
        --success-color: #10b981;
        --warning-color: #f59e0b;
        --danger-color: #ef4444;
        --info-color: #06b6d4;
        
        --bg-primary: #ffffff;
        --bg-secondary: #f8fafc;
        --bg-tertiary: #f1f5f9;
        
        --text-primary: #1e293b;
        --text-secondary: #64748b;
        --text-muted: #94a3b8;
        
        --border-color: #e2e8f0;
        --border-radius: 12px;
        --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.1);
        --shadow-xl: 0 20px 40px rgba(0, 0, 0, 0.15);
        
        --transition-base: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        min-height: 100vh;
        margin: 0;
        padding: 0;
        color: var(--text-primary);
        line-height: 1.6;
    }

    .container-fluid {
        max-width: 1920px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* ENHANCED EXAM HEADER */
    .exam-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, #1d4ed8 100%);
        color: white;
        padding: 2rem;
        border-radius: var(--border-radius);
        margin-bottom: 2rem;
        box-shadow: var(--shadow-xl);
        position: relative;
        overflow: hidden;
    }

    .exam-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .exam-subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
        margin-bottom: 0;
    }

    /* TIMER CONTAINER - ENHANCED */
    .timer-container {
        background: rgba(255, 255, 255, 0.15);
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 20px;
        padding: 2rem;
        backdrop-filter: blur(15px);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        position: relative;
        overflow: hidden;
    }

    .timer-display {
        text-align: center;
        color: white;
        position: relative;
        z-index: 2;
    }

    .timer-label {
        font-size: 0.9rem;
        font-weight: 600;
        opacity: 0.9;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .timer-value {
        font-family: 'Monaco', 'Cascadia Code', 'Consolas', 'Courier New', monospace;
        font-size: 2.5rem;
        font-weight: 700;
        text-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        margin-bottom: 0.5rem;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1.2;
    }

    .timer-status {
        font-size: 0.8rem;
        opacity: 0.85;
        font-weight: 500;
    }

    /* EXAM LAYOUT - IMPROVED GRID */
    .exam-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-bottom: 3rem;
    }

    /* When no material panel, questions take full width */
    .exam-section.no-material {
        grid-template-columns: 1fr;
    }

    .exam-section.no-material .questions-panel {
        max-width: 800px;
        margin: 0 auto;
    }

    /* QUESTIONS PANEL */
    .questions-panel {
        background: var(--bg-primary);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-xl);
        overflow: hidden;
        height: 900px;
        border: 1px solid var(--border-color);
    }

    .questions-header {
        padding: 1.5rem 2rem;
        position: sticky;
        top: 0;
        z-index: 10;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .reading-header {
        background: linear-gradient(135deg, var(--info-color) 0%, #0284c7 100%);
        color: white;
    }

    .listening-header {
        background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
        color: white;
    }

    .writing-header {
        background: linear-gradient(135deg, var(--warning-color) 0%, #d97706 100%);
        color: white;
    }

    .speaking-header {
        background: linear-gradient(135deg, var(--danger-color) 0%, #dc2626 100%);
        color: white;
    }

    .questions-container {
        padding: 1.5rem 0.5rem 1rem;
        max-height: 800px;
        overflow-y: auto;
        scroll-behavior: smooth;
    }

    /* QUESTION ITEMS */
    .question-item {
        background: var(--bg-secondary);
        border: 2px solid var(--border-color);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: var(--transition-base);
        position: relative;
    }

    .question-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        width: 4px;
        height: 100%;
        background: var(--border-color);
        transition: var(--transition-base);
        border-radius: 0 4px 4px 0;
    }

    .question-item:hover::before {
        background: var(--primary-color);
        width: 6px;
    }

    .question-item.active::before {
        background: var(--success-color);
        width: 6px;
    }

    .question-item.active {
        background: #f0fdf4;
        border-color: var(--success-color);
        transform: translateX(8px);
        box-shadow: var(--shadow-lg);
    }

    .question-item:hover {
        transform: translateX(4px);
        box-shadow: var(--shadow-lg);
        border-color: var(--primary-color);
    }

    .question-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--border-color);
    }

    .question-body p {
        font-size: 1.1rem;
        line-height: 1.6;
        margin-bottom: 1rem;
        color: var(--text-primary);
        font-weight: 500;
    }

    /* OPTIONS */
    .options-container {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .form-check {
        background: var(--bg-primary);
        border: 2px solid var(--border-color);
        border-radius: 8px;
        padding: 1rem;
        margin: 0;
        cursor: pointer;
        transition: var(--transition-base);
    }

    .form-check:hover {
        border-color: var(--primary-color);
        background: #eff6ff;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
    }

    .form-check:has(.form-check-input:checked) {
        background: #eff6ff;
        border-color: var(--primary-color);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
    }

    .form-check-label {
        font-size: 0.95rem;
        line-height: 1.4;
        cursor: pointer;
        width: 100%;
        margin: 0;
        font-weight: 500;
    }

    /* MATERIAL PANEL */
    .material-panel {
        background: var(--bg-primary);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-xl);
        overflow: hidden;
        position: sticky;
        top: 20px;
        height: fit-content;
        max-height: calc(100vh - 40px);
        border: 1px solid var(--border-color);
    }

    .material-header {
        padding: 1.5rem 2rem;
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .material-controls {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .font-controls {
        display: flex;
        gap: 0.25rem;
    }

    .font-controls button {
        width: 32px;
        height: 32px;
        border: none;
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border-radius: 6px;
        cursor: pointer;
        transition: var(--transition-base);
    }

    .font-controls button:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: scale(1.1);
    }

    /* READING CONTENT */
    .reading-content {
        padding: 2rem;
        overflow-y: auto;
        max-height: calc(100vh - 140px);
        font-family: Georgia, 'Times New Roman', serif;
        font-size: 1.05rem;
        line-height: 1.8;
        color: var(--text-primary);
        background: var(--bg-primary);
        scroll-behavior: smooth;
        position: relative;
    }

    .reading-progress {
        position: absolute;
        top: 0;
        left: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--info-color), #0284c7);
        transition: width 0.3s ease;
        z-index: 50;
    }

    /* LISTENING CONTENT */
    .listening-content {
        padding: 2rem;
        background: #f8fafc;
        overflow-y: auto;
        max-height: calc(100vh - 140px);
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: 400px;
    }

    .audio-player-container {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        border: 2px solid var(--success-color);
        border-radius: var(--border-radius);
        padding: 2rem;
        text-align: center;
        margin-bottom: 1rem;
    }

    .audio-player-container audio {
        width: 100%;
        max-width: 500px;
        height: 60px;
        border-radius: var(--border-radius);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 1.5rem;
    }

    .audio-controls {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .audio-controls .btn {
        padding: 0.875rem 1.5rem;
        font-weight: 500;
        border-radius: 8px;
        transition: var(--transition-base);
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        cursor: pointer;
    }

    .audio-controls .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    }

    /* SUBMIT BUTTON */
    .submit-container {
        background: var(--bg-primary);
        border-radius: var(--border-radius);
        padding: 2rem;
        box-shadow: var(--shadow-lg);
        margin-top: 2rem;
        text-align: center;
        border: 1px solid var(--border-color);
    }

    .submit-container .btn {
        min-width: 300px;
        font-size: 1.25rem;
        padding: 1.5rem 3rem;
        background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
        color: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-lg);
        border: none;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition-base);
    }

    .submit-container .btn:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(16, 185, 129, 0.35);
    }

    /* ALERTS */
    .alert {
        border-radius: var(--border-radius);
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        border: none;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .alert-info {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        color: #1e40af;
        border-left: 4px solid var(--info-color);
    }

    .alert-warning {
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        color: #92400e;
        border-left: 4px solid var(--warning-color);
    }

    .alert-danger {
        background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%);
        color: #b91c1c;
        border-left: 4px solid var(--danger-color);
    }

    .alert-success {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        color: #15803d;
        border-left: 4px solid var(--success-color);
    }

    /* BADGES */
    .badge {
        font-size: 0.8rem;
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }

    /* RESPONSIVE DESIGN */
    @media (max-width: 992px) {
        .exam-section {
            grid-template-columns: 1fr !important;
            gap: 1.5rem;
        }
        
        .material-panel {
            position: relative;
            top: 0;
            max-height: 500px;
            order: -1;
            margin-bottom: 2rem;
        }
        
        .reading-content, .listening-content {
            max-height: 400px;
        }
    }

    @media (max-width: 768px) {
        .container-fluid {
            padding: 0 15px;
        }
        
        .exam-header {
            padding: 1.5rem;
        }
        
        .exam-title {
            font-size: 2rem;
        }
        
        .timer-container {
            padding: 1.5rem;
            margin-top: 1rem;
        }
        
        .timer-value {
            font-size: 2rem;
        }
        
        .questions-container,
        .reading-content,
        .listening-content {
            padding: 1rem;
        }
        
        .question-item {
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .audio-controls {
            grid-template-columns: 1fr;
        }
        
        .submit-container .btn {
            min-width: auto;
            width: 100%;
            padding: 1.25rem 2rem;
            font-size: 1.125rem;
        }
    }
    </style>
</head>

<body>
    <div class="container-fluid">
        <!-- Exam Header -->
        <div class="exam-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="exam-title mb-0">
                        <i class="fas fa-graduation-cap me-2"></i>
                        İmtahan: <?php echo htmlspecialchars($exam['subjectname']); ?>
                    </h1>
                    <p class="exam-subtitle">
                        <i class="fas fa-info-circle me-2"></i>
                        İmtahan müddəti: <strong><?php echo $exam_duration_text; ?></strong>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="timer-container">
                        <div class="timer-display" id="timerDisplay">
                            <div class="timer-label">Qalan vaxt</div>
                            <div class="timer-value" id="timer">--:--</div>
                            <div class="timer-status" id="timerStatus">Hesablanır...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($debug_mode): ?>
            <div class="alert alert-info">
                <strong>🔍 Debug məlumatları:</strong>
                <a href="?id=<?php echo $exam_id; ?>" class="btn btn-sm btn-outline-primary ms-2">Debug-sız göstər</a>
            </div>
        <?php endif; ?>

        <form method="post" action="" id="examForm">
            <?php 
            $question_counter = 1;
            
            // Hər bir tip/fayl qrupu üçün 
            foreach ($grouped_questions as $group_key => $group_data): 
                $file_questions = $group_data['questions'];
                $file_type = $group_data['file_type'];
                $file_path = $group_data['file_path'];
                $file_id = $group_data['file_id'];
                
                // ENHANCED FILE EXISTENCE CHECK
                $show_material_panel = false;
                $material_content = '';
                $panel_title = '';
                $panel_class = '';
                $panel_icon = '';
                
                if ($file_type == 'reading' && !empty($file_path)) {
                    // Reading file yoxlanışı
                    $reading_path = $file_path;
                    $reading_exists = file_exists($reading_path);
                    
                    if (!$reading_exists) {
                        // Alternative paths for reading files
                        $alt_paths = [
                            '../' . ltrim($reading_path, '/'),
                            '../../' . ltrim($reading_path, '/'),
                            '../uploads/' . basename($reading_path),
                            '../files/' . basename($reading_path),
                            'uploads/' . basename($reading_path),
                            'files/' . basename($reading_path)
                        ];
                        
                        foreach ($alt_paths as $alt_path) {
                            if (file_exists($alt_path)) {
                                $reading_path = $alt_path;
                                $reading_exists = true;
                                break;
                            }
                        }
                    }
                    
                    if ($reading_exists) {
                        $show_material_panel = true;
                        $panel_title = 'Oxunacaq mətn';
                        $panel_class = 'reading-header';
                        $panel_icon = 'fas fa-book-reader';
                        
                        // Reading content hazırla
                        if (!isset($file_contents[$reading_path])) {
                            $file_contents[$reading_path] = file_get_contents($reading_path);
                        }
                    }
                    
                } elseif ($file_type == 'listening' && !empty($file_path)) {
                    // Audio file yoxlanışı
                    $audio_path = getValidAudioPath($file_path);
                    $audio_exists = !empty($audio_path) && (file_exists($audio_path) || filter_var($audio_path, FILTER_VALIDATE_URL));
                    
                    // Audio formatını yoxla
                    $audio_formats = ['mp3', 'wav', 'ogg', 'm4a', 'aac'];
                    $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                    
                    if ($audio_exists && in_array($file_extension, $audio_formats)) {
                        $show_material_panel = true;
                        $panel_title = 'Dinləmə materialı';
                        $panel_class = 'listening-header';
                        $panel_icon = 'fas fa-headphones';
                        
                        // Web üçün relative path hazırla
                        $web_audio_path = $audio_path;
                    }
                }
                
                // Debug məlumatları
                if ($debug_mode) {
                    echo "<div class='alert alert-info'>";
                    echo "<strong>🔍 File Check Debug for {$file_type}:</strong><br>";
                    echo "File path: " . htmlspecialchars($file_path ?: 'NULL') . "<br>";
                    echo "Show material panel: " . ($show_material_panel ? 'YES' : 'NO') . "<br>";
                    
                    if ($file_type == 'reading') {
                        echo "Reading path checked: " . htmlspecialchars($reading_path ?? 'N/A') . "<br>";
                        echo "Reading exists: " . (isset($reading_exists) && $reading_exists ? 'YES' : 'NO') . "<br>";
                    } elseif ($file_type == 'listening') {
                        echo "Audio path: " . htmlspecialchars($audio_path ?? 'N/A') . "<br>";
                        echo "Audio exists: " . (isset($audio_exists) && $audio_exists ? 'YES' : 'NO') . "<br>";
                        echo "File extension: " . ($file_extension ?? 'N/A') . "<br>";
                    }
                    echo "</div>";
                }
                
                // Fayl başlığını təyin et
                $file_title = isset($file_titles[$group_key]) ? $file_titles[$group_key] : ucfirst($file_type) . " Bölməsi";
            ?>
            
            <!-- Exam Section Layout - ONLY if questions exist -->
            <?php if (!empty($file_questions)): ?>
            <div class="exam-section <?php echo $show_material_panel ? '' : 'no-material'; ?>">
                <!-- Questions Panel (Left) - ALWAYS SHOW -->
                <div class="questions-panel">
                    <div class="questions-header <?php echo "Salamlar"; ?>-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-<?php echo $file_type == 'reading' ? 'book-open' : ($file_type == 'listening' ? 'headphones' : ($file_type == 'writing' ? 'pen' : 'microphone')); ?> me-2"></i>
                                <?php echo ucfirst($file_type); ?> Sualları (<?php echo count($file_questions); ?> ədəd)
                            </h5>
                            <?php if (!empty($selected_variant)): ?>
                                <span class="badge bg-light text-dark">Variant <?php echo $selected_variant; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="questions-container" id="<?php echo $file_type; ?>QuestionsContainer">
                        <?php foreach ($file_questions as $question): ?>
                            <div class="question-item <?php echo $question_counter == 1 ? 'active' : ''; ?>" data-question="<?php echo $question_counter; ?>">
                                <div class="question-header">
                                    <h6 class="mb-0 text-<?php echo $file_type == 'reading' ? 'info' : ($file_type == 'listening' ? 'success' : ($file_type == 'writing' ? 'warning' : 'danger')); ?>">
                                        <i class="fas fa-question-circle me-2"></i>
                                        Sual <?php echo $question_counter; ?>
                                    </h6>
                                    <span class="badge bg-<?php echo $file_type == 'reading' ? 'info' : ($file_type == 'listening' ? 'success' : ($file_type == 'writing' ? 'warning' : 'danger')); ?>"><?php echo $question['question_score']; ?> bal</span>
                                </div>
                                <div class="question-body">
                                    <p class="mb-3 fw-medium"><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></p>
                                    
                                    <?php
                                    // Sual növünə görə variantları göstərmək
                                    if ($question['question_var'] == 'multiple') {
                                        // Çoxseçimli sual
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
                                            
                                            <div class="options-container">
                                                <?php if (isset($options['var_a']) && $options['var_a'] !== ""): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="answer[<?php echo $question['id_question_text']; ?>]" 
                                                        id="option_a_<?php echo $question['id_question_text']; ?>" value="a"
                                                        <?php echo $current_answer == 'a' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="option_a_<?php echo $question['id_question_text']; ?>">
                                                        <strong>A)</strong> <?php echo htmlspecialchars($options['var_a']); ?>
                                                    </label>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($options['var_b']) && $options['var_b'] !== ""): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="answer[<?php echo $question['id_question_text']; ?>]" 
                                                        id="option_b_<?php echo $question['id_question_text']; ?>" value="b"
                                                        <?php echo $current_answer == 'b' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="option_b_<?php echo $question['id_question_text']; ?>">
                                                        <strong>B)</strong> <?php echo htmlspecialchars($options['var_b']); ?>
                                                    </label>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($options['var_c']) && $options['var_c'] !== ""): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="answer[<?php echo $question['id_question_text']; ?>]" 
                                                        id="option_c_<?php echo $question['id_question_text']; ?>" value="c"
                                                        <?php echo $current_answer == 'c' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="option_c_<?php echo $question['id_question_text']; ?>">
                                                        <strong>C)</strong> <?php echo htmlspecialchars($options['var_c']); ?>
                                                    </label>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($options['var_d']) && $options['var_d'] !== ""): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="answer[<?php echo $question['id_question_text']; ?>]" 
                                                        id="option_d_<?php echo $question['id_question_text']; ?>" value="d"
                                                        <?php echo $current_answer == 'd' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="option_d_<?php echo $question['id_question_text']; ?>">
                                                        <strong>D)</strong> <?php echo htmlspecialchars($options['var_d']); ?>
                                                    </label>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                        <?php } else { ?>
                                            <div class="alert alert-danger">Variant məlumatları tapılmadı.</div>
                                        <?php }
                                        
                                    } elseif ($question['question_var'] == 'open') {
                                        // Açıq tipli sual
                                        $current_answer = isset($user_answers[$question['id_question_text']]) ? 
                                                          $user_answers[$question['id_question_text']] : '';
                                        ?>
                                        
                                        <div class="mb-3">
                                            <label for="open_answer_<?php echo $question['id_question_text']; ?>" class="form-label fw-bold small">Cavabınız:</label>
                                            <textarea class="form-control" id="open_answer_<?php echo $question['id_question_text']; ?>" 
                                                name="answer[<?php echo $question['id_question_text']; ?>]" rows="4" 
                                                placeholder="Cavabınızı buraya yazın..."><?php echo htmlspecialchars($current_answer); ?></textarea>
                                        </div>
                                        
                                    <?php } elseif ($question['question_var'] == 'matching') {
                                        // Uyğunluq tipli sual
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
                                                <label for="matching_answer_<?php echo $question['id_question_text']; ?>" class="form-label fw-bold small">Düzgün uyğunluğu seçin:</label>
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
                            </div>
                            <?php $question_counter++; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Material Panel (Right) - ONLY SHOW if material exists -->
                <?php if ($show_material_panel): ?>
                <div class="material-panel">
                    <div class="material-header <?php echo $panel_class; ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="<?php echo $panel_icon; ?> me-2"></i>
                                <?php echo $panel_title; ?>
                            </h5>
                            <?php if ($file_type == 'reading'): ?>
                                <div class="material-controls">
                                    <div class="font-controls me-3">
                                        <button type="button" onclick="changeFontSize(-0.1)" title="Şrift kiçilt">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <button type="button" onclick="changeFontSize(0.1)" title="Şrift böyüt">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <button type="button" class="btn btn-outline-light btn-sm" onclick="toggleReadingSize()" id="expandBtn">
                                        <i class="fas fa-expand-arrows-alt me-1"></i>Genişləndir
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="material-controls">
                                    <button type="button" class="btn btn-outline-light btn-sm" onclick="toggleListeningSize()" id="expandListeningBtn">
                                        <i class="fas fa-expand-arrows-alt me-1"></i>Genişləndir
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($file_type == 'reading'): ?>
                            <div class="reading-progress" id="readingProgress"></div>
                        <?php else: ?>
                            <div class="listening-progress" id="listeningProgress"></div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($file_type == 'reading'): ?>
                        <div class="reading-content" id="readingContent">
                            <?php echo nl2br(htmlspecialchars($file_contents[$reading_path])); ?>
                            
                            <div class="material-nav" id="readingNav" style="position: absolute; top: 50%; right: 10px; transform: translateY(-50%); display: flex; flex-direction: column; gap: 0.5rem; opacity: 0.6;">
                                <button type="button" onclick="scrollToTop('reading')" title="Başa qayıt" style="width: 40px; height: 40px; border-radius: 50%; border: none; background: rgba(6, 182, 212, 0.1); color: #0284c7; cursor: pointer;">
                                    <i class="fas fa-chevron-up"></i>
                                </button>
                                <button type="button" onclick="scrollToBottom('reading')" title="Sona get" style="width: 40px; height: 40px; border-radius: 50%; border: none; background: rgba(6, 182, 212, 0.1); color: #0284c7; cursor: pointer;">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="listening-content" id="listeningContent">
                            <div class="audio-player-container">
                                <h4 class="text-success mb-3">
                                    <i class="fas fa-play-circle me-2"></i>
                                    Listening Audio
                                </h4>
                                
                                <audio controls preload="metadata" id="mainAudio" class="mb-3">
                                    <source src="<?php echo htmlspecialchars($web_audio_path); ?>" type="audio/mpeg">
                                    <source src="<?php echo htmlspecialchars($web_audio_path); ?>" type="audio/wav">
                                    <source src="<?php echo htmlspecialchars($web_audio_path); ?>" type="audio/ogg">
                                    <source src="<?php echo htmlspecialchars($web_audio_path); ?>" type="audio/mp3">
                                    Sizin brauzeriniz audio elementini dəstəkləmir.
                                </audio>
                                
                                <div class="audio-controls">
                                    <button type="button" class="btn btn-success" onclick="playMainAudio()" title="Oynat">
                                        <i class="fas fa-play me-2"></i>Dinləməni başlat
                                    </button>
                                    <button type="button" class="btn btn-warning" onclick="pauseMainAudio()" title="Dayan">
                                        <i class="fas fa-pause me-2"></i>Dayandır
                                    </button>
                                    <button type="button" class="btn btn-info" onclick="restartAudio()" title="Yenidən">
                                        <i class="fas fa-redo me-2"></i>Yenidən
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="adjustVolume()" title="Səs">
                                        <i class="fas fa-volume-up me-2"></i>Səs
                                    </button>
                                </div>
                                
                                <div class="mt-3 p-3 bg-white rounded border">
                                    <h6 class="text-success"><i class="fas fa-info-circle me-2"></i>Dinləmə Təlimatları:</h6>
                                    <ul class="mb-0 small">
                                        <li>Audio-nu diqqətlə dinləyin və sualları cavablandırın</li>
                                        <li>Audio-nu lazım gəldikdə yenidən dinləyə bilərsiniz</li>
                                        <li>Əgər audio yüklənmirsə, səhifəni yeniləyin</li>
                                        <li>Səsi uyğun səviyyədə tənzimləyin</li>
                                    </ul>
                                </div>
                                
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-file-audio me-1"></i>
                                            Audio Status: <span id="audioStatus" class="badge bg-secondary">Gözləyir</span>
                                        </small>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            Müddət: <span id="audioDuration">--:--</span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="material-nav" id="listeningNav" style="position: absolute; top: 50%; right: 10px; transform: translateY(-50%); display: flex; flex-direction: column; gap: 0.5rem; opacity: 0.6;">
                                <button type="button" onclick="scrollToTop('listening')" title="Başa qayıt" style="width: 40px; height: 40px; border-radius: 50%; border: none; background: rgba(6, 182, 212, 0.1); color: #0284c7; cursor: pointer;">
                                    <i class="fas fa-chevron-up"></i>
                                </button>
                                <button type="button" onclick="scrollToBottom('listening')" title="Sona get" style="width: 40px; height: 40px; border-radius: 50%; border: none; background: rgba(6, 182, 212, 0.1); color: #0284c7; cursor: pointer;">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; // !empty($file_questions) ?>
            
            <?php endforeach; // foreach grouped_questions ?>
            
            <!-- Submit Button -->
            <div class="submit-container">
                <div class="text-center">
                    <button type="submit" name="submit_exam" class="btn" onclick="return confirm('İmtahanı bitirmək istədiyinizə əminsiniz?')">
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
                            
                            echo implode(' + ', $section_info);
                            echo " = Cəmi {$total_points} bal";
                            
                            // Variant məlumatı əlavə et
                            if (!empty($selected_variant)) {
                                echo "<br><strong>Variant: $selected_variant</strong>";
                            }
                            ?>
                        </small>
                    </p>
                </div>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Global variables
    let currentFontSize = 1.05;
    let isExpanded = false;
    let isListeningExpanded = false;

    // Timer variables
    var examDuration = <?php echo $exam['timer']; ?> * 60;
    var startTimeString = "<?php echo $start_time; ?>";
    var serverTime = new Date("<?php echo date('Y-m-d\TH:i:s'); ?>");
    var serverStartTime = new Date(startTimeString);
    var currentTime = new Date();
    var timezoneOffset = currentTime.getTime() - serverTime.getTime();
    var adjustedStartTime = serverStartTime.getTime() + timezoneOffset;
    var elapsedSeconds = Math.floor((currentTime.getTime() - adjustedStartTime) / 1000);
    var remainingSeconds = Math.max(0, examDuration - elapsedSeconds);
    var totalDuration = <?php echo $exam['timer']; ?>;

    // Timer Update Function
    function updateTimer() {
        const timerElement = document.getElementById("timer");
        const timerDisplay = document.getElementById("timerDisplay");
        const timerStatus = document.getElementById("timerStatus");
        
        if (remainingSeconds <= 0) {
            clearInterval(timerInterval);
            timerElement.innerHTML = "⏰ Vaxt bitdi!";
            timerStatus.innerHTML = "İmtahan müddəti başa çatdı";
            
            setTimeout(function() {
                alert('İmtahan vaxtı bitdi! Form avtomatik olaraq təqdim edilir.');
                document.getElementById("examForm").submit();
            }, 2000);
            return;
        }
        
        var hours = Math.floor(remainingSeconds / 3600);
        var minutes = Math.floor((remainingSeconds % 3600) / 60);
        var seconds = remainingSeconds % 60;
        
        var timeString = '';
        if (hours > 0) {
            timeString = (hours < 10 ? "0" + hours : hours) + ":";
        }
        timeString += (minutes < 10 ? "0" + minutes : minutes) + ":" + (seconds < 10 ? "0" + seconds : seconds);
        
        timerElement.innerHTML = timeString;
        
        var remainingMinutes = Math.ceil(remainingSeconds / 60);
        var statusText = '';
        
        if (remainingSeconds <= 60) {
            statusText = "⚠️ " + remainingSeconds + " saniyə qalıb - SON DƏQIQƏLƏR!";
        } else if (remainingMinutes <= 5) {
            statusText = "🔴 " + remainingMinutes + " dəqiqə qalıb - TƏLƏS!";
        } else if (remainingMinutes <= 15) {
            statusText = "🟡 " + remainingMinutes + " dəqiqə qalıb - Az vaxt";
        } else {
            statusText = "🟢 " + remainingMinutes + " dəqiqə qalıb";
        }
        
        timerStatus.innerHTML = statusText;
        remainingSeconds--;
    }

    // Audio functions
    function playMainAudio() {
        const audio = document.getElementById('mainAudio');
        const statusElement = document.getElementById('audioStatus');
        
        if (audio) {
            audio.play()
                .then(() => {
                    if (statusElement) {
                        statusElement.textContent = 'Oynadılır';
                        statusElement.className = 'badge bg-success';
                    }
                })
                .catch(error => {
                    console.error('Playback failed:', error);
                    if (statusElement) {
                        statusElement.textContent = 'Xəta';
                        statusElement.className = 'badge bg-danger';
                    }
                });
        }
    }

    function pauseMainAudio() {
        const audio = document.getElementById('mainAudio');
        const statusElement = document.getElementById('audioStatus');
        
        if (audio) {
            audio.pause();
            if (statusElement) {
                statusElement.textContent = 'Dayandırıldı';
                statusElement.className = 'badge bg-warning';
            }
        }
    }

    function restartAudio() {
        const audio = document.getElementById('mainAudio');
        const statusElement = document.getElementById('audioStatus');
        
        if (audio) {
            audio.currentTime = 0;
            audio.play()
                .then(() => {
                    if (statusElement) {
                        statusElement.textContent = 'Yenidən başladı';
                        statusElement.className = 'badge bg-info';
                    }
                })
                .catch(error => {
                    console.error('Restart failed:', error);
                });
        }
    }

    function adjustVolume() {
        const audio = document.getElementById('mainAudio');
        if (audio) {
            const currentVolume = audio.volume;
            const newVolume = currentVolume >= 0.5 ? 0.3 : 1.0;
            audio.volume = newVolume;
        }
    }

    // Font size change
    function changeFontSize(delta) {
        const readingContent = document.getElementById('readingContent');
        if (readingContent) {
            currentFontSize += delta;
            currentFontSize = Math.max(0.8, Math.min(1.4, currentFontSize));
            readingContent.style.fontSize = currentFontSize + 'rem';
        }
    }

    // Toggle functions
    function toggleReadingSize() {
        const readingContent = document.getElementById('readingContent');
        const expandBtn = document.getElementById('expandBtn');
        
        if (readingContent && expandBtn) {
            if (isExpanded) {
                readingContent.style.maxHeight = 'calc(100vh - 140px)';
                expandBtn.innerHTML = '<i class="fas fa-expand-arrows-alt me-1"></i>Genişləndir';
                isExpanded = false;
            } else {
                readingContent.style.maxHeight = 'calc(100vh - 80px)';
                expandBtn.innerHTML = '<i class="fas fa-compress-arrows-alt me-1"></i>Kiçilt';
                isExpanded = true;
            }
        }
    }

    function toggleListeningSize() {
        const listeningContent = document.getElementById('listeningContent');
        const expandBtn = document.getElementById('expandListeningBtn');
        
        if (listeningContent && expandBtn) {
            if (isListeningExpanded) {
                listeningContent.style.maxHeight = 'calc(100vh - 140px)';
                expandBtn.innerHTML = '<i class="fas fa-expand-arrows-alt me-1"></i>Genişləndir';
                isListeningExpanded = false;
            } else {
                listeningContent.style.maxHeight = 'calc(100vh - 80px)';
                expandBtn.innerHTML = '<i class="fas fa-compress-arrows-alt me-1"></i>Kiçilt';
                isListeningExpanded = true;
            }
        }
    }

    // Scroll functions
    function scrollToTop(type) {
        const container = document.getElementById(type + 'Content');
        if (container) {
            container.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    function scrollToBottom(type) {
        const container = document.getElementById(type + 'Content');
        if (container) {
            container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
        }
    }

    var timerInterval;

    // Page load handler
    document.addEventListener('DOMContentLoaded', function() {
        // Timer başlat
        if (remainingSeconds > 0) {
            updateTimer();
            timerInterval = setInterval(updateTimer, 1000);
        }
        
        // Audio event listeners
        document.querySelectorAll('audio').forEach(function(audio) {
            const statusElement = document.getElementById('audioStatus');
            const durationElement = document.getElementById('audioDuration');
            
            audio.addEventListener('loadedmetadata', function() {
                if (durationElement && audio.duration) {
                    const minutes = Math.floor(audio.duration / 60);
                    const seconds = Math.floor(audio.duration % 60);
                    durationElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                }
            });
            
            audio.addEventListener('canplay', function() {
                if (statusElement) {
                    statusElement.textContent = 'Hazır';
                    statusElement.className = 'badge bg-success';
                }
            });
            
            audio.addEventListener('error', function(e) {
                console.error('Audio error:', e);
                if (statusElement) {
                    statusElement.textContent = 'Xəta';
                    statusElement.className = 'badge bg-danger';
                }
            });
        });
        
        // Form submission
        document.getElementById('examForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[name="submit_exam"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Təqdim edilir...';
            }
        });
        
        console.log('Exam System loaded successfully!');
    });
    </script>
</body>
</html>

<?php
// Auto-save handling
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['auto_save'])) {
    // Auto-save logic here
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    exit();
}
?>