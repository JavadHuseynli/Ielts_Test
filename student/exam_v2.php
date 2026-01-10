<?php
ob_start();
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

// Check if student has already completed this exam
$check_query = "SELECT id_score FROM scores WHERE exam_id = :exam_id AND user_id = :user_id";
$check_stmt = $db->prepare($check_query);
$check_stmt->bindParam(":exam_id", $exam_id);
$check_stmt->bindParam(":user_id", $user_id);
$check_stmt->execute();

if ($check_stmt->rowCount() > 0) {
    header("Location: dashboard.php?error=already_completed");
    exit();
}

// Auto-start exam if pending
if ($exam['status'] == 'pending') {
    $update_query = "UPDATE exams SET status = 'in_progress' WHERE id_exam = :exam_id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(":exam_id", $exam_id);
    $update_stmt->execute();
    $exam['status'] = 'in_progress';
}

// ====== FORM SUBMISSION HANDLING ======
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_exam'])) {
    error_log("=== EXAM SUBMISSION STARTED ===");
    try {
        $db->beginTransaction();

        // Determine the student's assigned variant to correctly score the exam
        $student_variant = null;
        $variant_check_query = "SELECT qf.file_title FROM answers a
                                JOIN question_read qr ON a.id_questions = qr.id_question_text
                                JOIN question_files qf ON qr.id_read_quest_file = qf.id_read_quest_file
                                WHERE a.exam_id = :exam_id AND a.user_id = :user_id
                                LIMIT 1";
        $variant_check_stmt = $db->prepare($variant_check_query);
        $variant_check_stmt->bindParam(":exam_id", $exam_id);
        $variant_check_stmt->bindParam(":user_id", $user_id);
        $variant_check_stmt->execute();
        $variant_result = $variant_check_stmt->fetch(PDO::FETCH_ASSOC);

        // Extract variant from standardized format "Variant X"
        if ($variant_result && !empty($variant_result['file_title']) && preg_match('/Variant\s+([A-Z0-9]+)/i', $variant_result['file_title'], $matches)) {
            $student_variant = $matches[1];
        }

        // Build the filter for the scoring query
        $variant_filter = "";
        if (!empty($student_variant)) {
            $variant_filter = "AND SUBSTRING_INDEX(TRIM(qf.file_title), ' ', -1) = :variant_code";
        }

        // Get all questions for the student's specific variant
        $query = "SELECT qr.id_question_text, qt.question_var
                  FROM question_read qr
                  JOIN question_files qf ON qr.id_read_quest_file = qf.id_read_quest_file
                  JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
                  WHERE qf.subject_id = :subject_id {$variant_filter}";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":subject_id", $exam['id_subject']);
        if (!empty($student_variant)) {
            $stmt->bindParam(":variant_code", $student_variant);
        }
        $stmt->execute();
        $all_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_score = 0;

        foreach ($all_questions as $question) {
            $question_id = $question['id_question_text'];
            $question_type = $question['question_var'];
            $user_answer = '';
            $correct_answer = '';

            // Get correct answer based on question type
            if ($question_type == 'multiple') {
                $query = "SELECT correct_v, question_score FROM multiple_questions mq
                          JOIN question_read qr ON mq.id_question_text = qr.id_question_text
                          WHERE mq.id_question_text = :id_question_text";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id_question_text", $question_id);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $correct_answer = $result ? $result['correct_v'] : '';
                $question_score = $result ? $result['question_score'] : 0;
                $user_answer = isset($_POST['answer'][$question_id]) ? $_POST['answer'][$question_id] : '';

            } elseif ($question_type == 'open') {
                $query = "SELECT corr_v, question_score FROM open_questions oq
                          JOIN question_read qr ON oq.id_question_text = qr.id_question_text
                          WHERE oq.id_question_text = :id_question_text";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id_question_text", $question_id);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $correct_answer = $result ? $result['corr_v'] : '';
                $question_score = $result ? $result['question_score'] : 0;
                $user_answer = isset($_POST['answer'][$question_id]) ? trim($_POST['answer'][$question_id]) : '';

            } elseif ($question_type == 'matching') {
                $query = "SELECT corr_variant, question_score FROM matching_questions mq
                          JOIN question_read qr ON mq.id_question_text = qr.id_question_text
                          WHERE mq.id_question_text = :id_question_text";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id_question_text", $question_id);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $correct_answer = $result ? $result['corr_variant'] : '';
                $question_score = $result ? $result['question_score'] : 0;
                $user_answer = isset($_POST['answer'][$question_id]) ? $_POST['answer'][$question_id] : '';
            } else {
                continue;
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
                    $score_earned = $question_score;
                }
            }

            $total_score += $score_earned;

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

        // Save overall score to scores table
        $now = date('Y-m-d H:i:s');
        $score_query = "INSERT INTO scores (exam_id, user_id, id_answer, score, datetime)
                        VALUES (:exam_id, :user_id, NULL, :total_score, :datetime)";
        $score_stmt = $db->prepare($score_query);
        $score_stmt->bindParam(":exam_id", $exam_id);
        $score_stmt->bindParam(":user_id", $user_id);
        $score_stmt->bindParam(":total_score", $total_score);
        $score_stmt->bindParam(":datetime", $now);
        $score_stmt->execute();

        $db->commit();

        while (ob_get_level()) {
            ob_end_clean();
        }

        header("Location: dashboard.php?success=1");
        exit();

    } catch (Exception $e) {
        error_log("=== EXCEPTION: " . $e->getMessage() . " ===");
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        die('<div style="background:#fee; padding:20px; margin:20px; border:2px solid #f00; border-radius:8px;">
            <h3 style="color:#c00;">İmtahan təsdiq xətası</h3>
            <p>' . htmlspecialchars($e->getMessage()) . '</p>
            <p><a href="dashboard.php" style="color:#00f;">Dashboard-a qayıt</a></p>
            </div>');
    }
}

// FILE HANDLING FUNCTIONS
function getValidReadingPath($file_path) {
    if (empty($file_path)) return null;

    $possible_paths = [
        $file_path, '../' . ltrim($file_path, '/\\'), '../../' . ltrim($file_path, '/\\'),
        '../../../' . ltrim($file_path, '/\\'), '../uploads/' . basename($file_path),
        '../../uploads/' . basename($file_path), '../files/' . basename($file_path),
        '../../files/' . basename($file_path), 'uploads/' . basename($file_path),
        'files/' . basename($file_path)
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
    if (!$valid_path) return "📄 Oxunacaq mətn tapılmadı.";

    $content = file_get_contents($valid_path);
    if ($content === false || empty(trim($content))) {
        return "📄 Fayl oxuna bilmədi.";
    }

    if (!mb_check_encoding($content, 'UTF-8')) {
        $content = mb_convert_encoding($content, 'UTF-8', 'auto');
    }

    return trim($content);
}

function getValidAudioPath($file_path) {
    if (empty($file_path)) return null;

    // If already a URL, return as is
    if (filter_var($file_path, FILTER_VALIDATE_URL) || (strpos($file_path, 'http') === 0)) {
        return $file_path;
    }

    // Try different possible paths where the file might be located
    $possible_paths = [
        __DIR__ . '/../' . $file_path,
        __DIR__ . '/../' . ltrim($file_path, '/\\'),
        __DIR__ . '/../uploads/' . basename($file_path),
        __DIR__ . '/../../uploads/' . basename($file_path),
    ];

    foreach ($possible_paths as $full_path) {
        if (file_exists($full_path) && is_readable($full_path) && filesize($full_path) > 0) {
            // File found! Return stream URL
            $relative_path = 'uploads/' . basename($file_path);
            return '../stream_audio.php?file=' . urlencode($relative_path);
        }
    }

    // If not found, still try to return stream URL (maybe file is there but paths are wrong)
    // This will let stream_audio.php try to find it
    $relative_path = 'uploads/' . basename($file_path);
    return '../stream_audio.php?file=' . urlencode($relative_path);
}

// --- START RANDOM VARIANT LOGIC ---

$selected_variant = null;
$variant_filter = "";

// Check if user already has assigned questions
$check_assigned_query = "SELECT id_questions FROM answers WHERE exam_id = :exam_id AND user_id = :user_id LIMIT 1";
$check_assigned_stmt = $db->prepare($check_assigned_query);
$check_assigned_stmt->bindParam(":exam_id", $exam_id);
$check_assigned_stmt->bindParam(":user_id", $user_id);
$check_assigned_stmt->execute();
$first_assigned_question = $check_assigned_stmt->fetch(PDO::FETCH_ASSOC);

if ($first_assigned_question) {
    // User has started, determine their locked-in variant
    $variant_check_query = "SELECT qf.file_title FROM question_read qr
                            JOIN question_files qf ON qr.id_read_quest_file = qf.id_read_quest_file
                            WHERE qr.id_question_text = :question_id LIMIT 1";
    $variant_check_stmt = $db->prepare($variant_check_query);
    $variant_check_stmt->bindParam(":question_id", $first_assigned_question['id_questions']);
    $variant_check_stmt->execute();
    $variant_result = $variant_check_stmt->fetch(PDO::FETCH_ASSOC);

    // Extract variant from standardized format "Variant X"
    if ($variant_result && !empty($variant_result['file_title']) && preg_match('/Variant\s+([A-Z0-9]+)/i', $variant_result['file_title'], $matches)) {
        $selected_variant = $matches[1];
    }
} else {
    // First time user, assign a random variant
    $available_variants_query = "SELECT DISTINCT(SUBSTRING_INDEX(TRIM(file_title), ' ', -1)) as variant_code
                                FROM question_files
                                WHERE subject_id = :subject_id AND file_title LIKE 'Variant %'";
    $available_variants_stmt = $db->prepare($available_variants_query);
    $available_variants_stmt->bindParam(":subject_id", $exam['id_subject']);
    $available_variants_stmt->execute();
    $available_variants = $available_variants_stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($available_variants)) {
        $random_index = array_rand($available_variants);
        $selected_variant = $available_variants[$random_index];
    }
}

// Build the filter for the main query
if (!empty($selected_variant)) {
    $variant_filter = "AND SUBSTRING_INDEX(TRIM(qf.file_title), ' ', -1) = :variant_code";
}

// Fetch questions based on the determined variant (or no variant if none found)
$query = "SELECT qr.id_question_text, qr.question_text, qr.question_score,
                 qt.quest_type_name, qt.question_var,
                 qf.file_title, qf.file_path, qf.file_type, qf.id_read_quest_file
          FROM question_read qr
          JOIN question_files qf ON qr.id_read_quest_file = qf.id_read_quest_file
          JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
          WHERE qf.subject_id = :subject_id {$variant_filter}
          ORDER BY qr.id_question_text";

$stmt = $db->prepare($query);
$stmt->bindParam(":subject_id", $exam['id_subject']);
if (!empty($selected_variant)) {
    $stmt->bindParam(":variant_code", $selected_variant);
}
$stmt->execute();
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If no questions were found for the variant, try fetching without a variant
if (empty($questions) && !empty($selected_variant)) {
    $query = "SELECT qr.id_question_text, qr.question_text, qr.question_score,
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
}

// If this is the first time, assign the fetched questions to the user
if (!$first_assigned_question && !empty($questions)) {
    $now = date('Y-m-d H:i:s');
    $empty_answer = '';
    $insert_query = "INSERT INTO answers (exam_id, id_questions, user_id, user_answer, correct_var, is_correct, score_earned, datetime)
                    VALUES (:exam_id, :question_id, :user_id, :user_answer, :correct_var, NULL, 0, :datetime)";
    $insert_stmt = $db->prepare($insert_query);

    foreach ($questions as $q) {
        $insert_stmt->bindParam(":exam_id", $exam_id);
        $insert_stmt->bindParam(":question_id", $q['id_question_text']);
        $insert_stmt->bindParam(":user_id", $user_id);
        $insert_stmt->bindParam(":user_answer", $empty_answer);
        $insert_stmt->bindParam(":correct_var", $empty_answer);
        $insert_stmt->bindParam(":datetime", $now);
        $insert_stmt->execute();
    }
}

// --- END RANDOM VARIANT LOGIC ---

if (empty($questions)) {
    echo '<div class="alert alert-danger">XƏTA: Bu imtahan üçün sual tapılmadı!</div>';
    exit();
}

// Get user's previous answers
$user_answers = array();
$query = "SELECT id_questions, user_answer FROM answers
          WHERE exam_id = :exam_id AND user_id = :user_id";
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
            'file_title' => isset($question['file_title']) ? $question['file_title'] : 'Unnamed Section',
            'file_type' => $file_type,
            'file_path' => isset($question['file_path']) ? $question['file_path'] : null,
            'file_id' => $file_id
        );

        if ($file_type == 'reading' && !empty($question['file_path'])) {
            $file_contents[$group_key] = loadReadingContent($question['file_path']);
        }

        if ($file_type == 'listening' && !empty($question['file_path'])) {
            $audio_paths[$group_key] = getValidAudioPath($question['file_path']);
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

// Build all questions array for navigation
$all_questions_flat = [];
foreach ($grouped_questions as $group_key => $group_data) {
    foreach ($group_data['questions'] as $q) {
        $all_questions_flat[] = [
            'id' => $q['id_question_text'],
            'type' => $group_data['file_type'],
            'group_key' => $group_key,
            'question' => $q
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İmtahan: <?php echo htmlspecialchars($exam['subjectname']); ?></title>

    <!-- CACHE PREVENTION -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.4s ease-out',
                        'slide-in-right': 'slideInRight 0.4s ease-out',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                        slideInRight: {
                            '0%': { transform: 'translateX(20px)', opacity: '0' },
                            '100%': { transform: 'translateX(0)', opacity: '1' },
                        }
                    }
                }
            }
        }
    </script>

    <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

    * {
        font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: #f1f5f9; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    .glass {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    .gradient-text {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .radio-option {
        position: relative;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    .radio-option::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(99, 102, 241, 0.1);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .radio-option:hover::before {
        width: 300px;
        height: 300px;
    }

    .radio-option input[type="radio"]:checked + .radio-circle {
        border-color: #6366f1;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
    }

    .radio-option input[type="radio"]:checked + .radio-circle::after {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
    }

    .radio-circle {
        position: relative;
        width: 24px;
        height: 24px;
        border: 2px solid #cbd5e1;
        border-radius: 50%;
        flex-shrink: 0;
        transition: all 0.3s ease;
    }

    .radio-circle::after {
        content: '';
        position: absolute;
        width: 8px;
        height: 8px;
        background: white;
        border-radius: 50%;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0);
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    .question-nav-item {
        transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    .question-nav-item.answered {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
    }

    .question-nav-item.current {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
        transform: scale(1.15);
        box-shadow: 0 8px 25px rgba(99, 102, 241, 0.5);
    }

    .question-nav-item:not(.current):not(.answered) {
        background: white;
    }

    .question-nav-item:not(.current):not(.answered):hover {
        transform: scale(1.1);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .progress-bar {
        position: relative;
        overflow: hidden;
    }

    .progress-bar::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        animation: shimmer 2s infinite;
    }

    @keyframes shimmer {
        0% { left: -100%; }
        100% { left: 100%; }
    }

    .question-container {
        display: none;
    }

    .question-container.active {
        display: block;
        animation: fadeIn 0.3s ease-in-out;
    }
    </style>
</head>
<body class="bg-[#f5f7fa] h-screen flex flex-col">

    <!-- Top Bar -->
    <div class="glass border-b border-gray-200 px-6 py-3 shadow-sm">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold gradient-text">IELTS Reading Test</h1>
            <div class="flex items-center gap-6">
                <!-- Auto-save indicator -->
                <div id="saveIndicator" class="flex items-center gap-2 text-green-600 opacity-0 transition-opacity duration-300">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span class="text-sm font-medium">Serverə saxlanıldı</span>
                </div>

                <div class="flex items-center gap-2 text-gray-600">
                    <i class="fas fa-clock"></i>
                    <span class="font-semibold" id="timer"><?php echo $exam['timer']; ?>:00</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white text-sm shadow-lg">
                        <i class="fas fa-user"></i>
                    </div>
                    <span class="text-sm text-gray-700 font-medium"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 flex overflow-hidden">

        <!-- Left Panel - Reading Material -->
        <div class="w-1/2 bg-white border-r border-gray-200 flex flex-col">
            <!-- Section Tabs -->
            <div class="flex border-b border-gray-200 bg-gray-50">
                <button class="section-tab px-6 py-3 font-semibold text-blue-600 border-b-2 border-blue-600" data-type="reading">
                    <i class="fas fa-book-open mr-2"></i>Reading
                </button>
                <button class="section-tab px-6 py-3 font-semibold text-gray-500 hover:text-gray-700" data-type="listening">
                    <i class="fas fa-headphones mr-2"></i>Listening
                </button>
            </div>

            <!-- Material Content Containers -->
            <div class="flex-1 overflow-y-auto p-8" id="materialContainer">
                <?php foreach ($grouped_questions as $group_key => $group_data): ?>
                    <?php if ($group_data['file_type'] == 'reading' && isset($file_contents[$group_key])): ?>
                        <div class="material-content" data-group="<?php echo $group_key; ?>" data-type="reading" style="display:none;">
                            <div class="max-w-3xl">
                                <h2 class="text-2xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($group_data['file_title']); ?></h2>
                                <div class="prose prose-lg">
                                    <p class="text-gray-700 leading-relaxed">
                                        <?php echo nl2br(htmlspecialchars($file_contents[$group_key])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($group_data['file_type'] == 'listening' && isset($audio_paths[$group_key])): ?>
                        <div class="material-content" data-group="<?php echo $group_key; ?>" data-type="listening" style="display:none;">
                            <div class="max-w-3xl">
                                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                                    <i class="fas fa-headphones mr-2 text-green-600"></i><?php echo htmlspecialchars($group_data['file_title']); ?>
                                </h2>

                                <!-- Play Counter Warning -->
                                <div class="mb-4 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded-lg">
                                    <div class="flex items-center">
                                        <i class="fas fa-exclamation-triangle text-yellow-600 mr-3 text-xl"></i>
                                        <div>
                                            <p class="font-semibold text-yellow-800">Diqqət!</p>
                                            <p class="text-sm text-yellow-700">Bu audio-nu yalnız <strong>5 dəfə yenidən başlada</strong> bilərsiniz. Play/Pause ilə sərbəst dinləyə bilərsiniz.</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Play Counter Display -->
                                <div class="mb-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg">
                                                <i class="fas fa-redo text-white text-lg"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-600 font-medium">Restart sayı</p>
                                                <p class="text-2xl font-bold text-gray-800">
                                                    <span id="playCount_<?php echo $group_key; ?>">0</span> / 5
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex gap-1" id="playDots_<?php echo $group_key; ?>">
                                            <div class="w-3 h-3 rounded-full bg-gray-300"></div>
                                            <div class="w-3 h-3 rounded-full bg-gray-300"></div>
                                            <div class="w-3 h-3 rounded-full bg-gray-300"></div>
                                            <div class="w-3 h-3 rounded-full bg-gray-300"></div>
                                            <div class="w-3 h-3 rounded-full bg-gray-300"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl p-8 shadow-lg">
                                    <!-- Debug info -->
                                    <div class="mb-2 p-2 bg-yellow-100 text-xs">
                                        <strong>Audio URL:</strong> <?php echo htmlspecialchars($audio_paths[$group_key]); ?>
                                    </div>

                                    <audio controls preload="metadata" class="w-full mb-4" id="audio_<?php echo $group_key; ?>" data-group="<?php echo $group_key; ?>">
                                        <source src="<?php echo htmlspecialchars($audio_paths[$group_key]); ?>" type="audio/mpeg">
                                        Sizin brauzeriniz audio elementini dəstəkləmir.
                                    </audio>
                                    <div class="flex gap-3 mt-4">
                                        <button type="button" class="play-btn flex-1 px-4 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed"
                                                data-group="<?php echo $group_key; ?>"
                                                id="playBtn_<?php echo $group_key; ?>">
                                            <i class="fas fa-play mr-2"></i>Play
                                        </button>
                                        <button type="button" class="pause-btn flex-1 px-4 py-2 bg-orange-600 text-white rounded-xl hover:bg-orange-700 transition"
                                                data-group="<?php echo $group_key; ?>">
                                            <i class="fas fa-pause mr-2"></i>Pause
                                        </button>
                                        <button type="button" class="restart-btn flex-1 px-4 py-2 bg-gray-600 text-white rounded-xl hover:bg-gray-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed"
                                                data-group="<?php echo $group_key; ?>"
                                                id="restartBtn_<?php echo $group_key; ?>">
                                            <i class="fas fa-redo mr-2"></i>Restart
                                        </button>
                                    </div>

                                    <!-- Remaining Plays Message -->
                                    <div class="mt-4 text-center">
                                        <p class="text-sm font-medium text-gray-600" id="remainingMsg_<?php echo $group_key; ?>">
                                            Qalan restart: <span class="text-green-600 font-bold">5</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Right Panel - Questions -->
        <div class="w-1/2 bg-gray-50 flex flex-col">

            <form method="post" action="" id="examForm">

                <!-- Question Area -->
                <div class="flex-1 overflow-y-auto p-8">
                    <div class="max-w-2xl mx-auto">

                        <?php
                        $question_index = 0;
                        foreach ($all_questions_flat as $q_data):
                            $question = $q_data['question'];
                            $question_index++;
                            $question_id = $question['id_question_text'];
                        ?>

                        <!-- Question Container -->
                        <div class="question-container <?php echo $question_index == 1 ? 'active' : ''; ?>"
                             data-question-index="<?php echo $question_index; ?>"
                             data-question-id="<?php echo $question_id; ?>"
                             data-group="<?php echo $q_data['group_key']; ?>"
                             data-type="<?php echo $q_data['type']; ?>">

                            <!-- Question Header -->
                            <div class="mb-6">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-sm font-medium text-gray-500">Sual <?php echo $question_index; ?>/<?php echo $question_stats['total']; ?></span>
                                    <span class="px-3 py-1 bg-<?php echo $q_data['type'] == 'reading' ? 'blue' : 'green'; ?>-100 text-<?php echo $q_data['type'] == 'reading' ? 'blue' : 'green'; ?>-700 rounded-full text-sm font-semibold capitalize">
                                        <?php echo $q_data['type']; ?>
                                    </span>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-800">
                                    <?php echo nl2br(htmlspecialchars($question['question_text'])); ?>
                                </h3>
                            </div>

                            <!-- Answer Options -->
                            <div class="space-y-3">
                                <?php if ($question['question_var'] == 'multiple'): ?>
                                    <?php
                                    $query = "SELECT var_a, var_b, var_c, var_d FROM multiple_questions
                                              WHERE id_question_text = :id_question_text";
                                    $stmt = $db->prepare($query);
                                    $stmt->bindParam(":id_question_text", $question_id);
                                    $stmt->execute();
                                    $options = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $current_answer = isset($user_answers[$question_id]) ? $user_answers[$question_id] : '';

                                    if ($options):
                                        foreach (['a' => $options['var_a'], 'b' => $options['var_b'], 'c' => $options['var_c'], 'd' => $options['var_d']] as $key => $value):
                                            if (!empty($value)):
                                    ?>
                                    <label class="radio-option flex items-center gap-4 p-4 rounded-xl border-2 border-gray-200 cursor-pointer hover:border-blue-300 hover:bg-blue-50 transition">
                                        <input type="radio" name="answer[<?php echo $question_id; ?>]" value="<?php echo $key; ?>" class="hidden answer-input"
                                               <?php echo $current_answer == $key ? 'checked' : ''; ?>>
                                        <div class="radio-circle"></div>
                                        <span class="text-gray-700"><?php echo htmlspecialchars($value); ?></span>
                                    </label>
                                    <?php
                                            endif;
                                        endforeach;
                                    endif;
                                    ?>

                                <?php elseif ($question['question_var'] == 'open'): ?>
                                    <?php $current_answer = isset($user_answers[$question_id]) ? $user_answers[$question_id] : ''; ?>
                                    <div class="mb-3">
                                        <textarea class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-blue-400 focus:outline-none answer-input"
                                                  name="answer[<?php echo $question_id; ?>]"
                                                  rows="4"
                                                  placeholder="Cavabınızı buraya yazın..."><?php echo htmlspecialchars($current_answer); ?></textarea>
                                    </div>

                                <?php elseif ($question['question_var'] == 'matching'): ?>
                                    <?php
                                    $query = "SELECT variants FROM matching_questions WHERE id_question_text = :id_question_text";
                                    $stmt = $db->prepare($query);
                                    $stmt->bindParam(":id_question_text", $question_id);
                                    $stmt->execute();
                                    $matching = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $variants = explode(',', $matching['variants']);
                                    $current_answer = isset($user_answers[$question_id]) ? $user_answers[$question_id] : '';
                                    ?>
                                    <div class="mb-3">
                                        <select class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-blue-400 focus:outline-none answer-input"
                                                name="answer[<?php echo $question_id; ?>]">
                                            <option value="">-- Seçin --</option>
                                            <?php foreach ($variants as $variant): ?>
                                                <option value="<?php echo htmlspecialchars(trim($variant)); ?>"
                                                    <?php echo $current_answer == trim($variant) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars(trim($variant)); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Navigation Buttons -->
                            <div class="flex gap-3 mt-8">
                                <button type="button" class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-xl font-semibold hover:bg-gray-300 transition prev-btn" <?php echo $question_index == 1 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-arrow-left mr-2"></i>Əvvəlki
                                </button>
                                <button type="button" class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition next-btn">
                                    Növbəti<i class="fas fa-arrow-right ml-2"></i>
                                </button>
                            </div>

                        </div>

                        <?php endforeach; ?>

                    </div>
                </div>

                <!-- Bottom - Question Navigation -->
                <div class="border-t border-gray-200 bg-white flex flex-col" style="max-height: 280px;">
                    <!-- Scrollable Question Navigation Area -->
                    <div class="overflow-y-auto flex-1 p-6" style="max-height: 200px;">
                        <div class="max-w-2xl mx-auto">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-semibold text-gray-700">Suallar</h4>
                                <div class="flex items-center gap-4 text-sm">
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                        <span class="text-gray-600">Cavablandı</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                        <span class="text-gray-600">Cari</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 bg-gray-200 rounded-full"></div>
                                        <span class="text-gray-600">Cavablanmayıb</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Reading Section -->
                            <?php if ($question_stats['reading'] > 0): ?>
                            <div class="mb-4">
                                <div class="text-xs font-semibold text-gray-500 mb-2 flex items-center gap-2">
                                    <i class="fas fa-book-open"></i>
                                    READING (1-<?php echo $question_stats['reading']; ?>)
                                </div>
                                <div class="grid grid-cols-10 gap-2">
                                    <?php for ($i = 1; $i <= $question_stats['reading']; $i++): ?>
                                    <button type="button" class="question-nav-item w-10 h-10 rounded-lg font-semibold text-sm bg-gray-100 hover:bg-gray-200 transition <?php echo $i == 1 ? 'current' : ''; ?>"
                                            data-question="<?php echo $i; ?>"><?php echo $i; ?></button>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Listening Section -->
                            <?php if ($question_stats['listening'] > 0): ?>
                            <div class="mb-4">
                                <div class="text-xs font-semibold text-gray-500 mb-2 flex items-center gap-2">
                                    <i class="fas fa-headphones"></i>
                                    LISTENING (<?php echo $question_stats['reading'] + 1; ?>-<?php echo $question_stats['reading'] + $question_stats['listening']; ?>)
                                </div>
                                <div class="grid grid-cols-10 gap-2">
                                    <?php for ($i = $question_stats['reading'] + 1; $i <= $question_stats['reading'] + $question_stats['listening']; $i++): ?>
                                    <button type="button" class="question-nav-item w-10 h-10 rounded-lg font-semibold text-sm bg-gray-100 hover:bg-gray-200 transition"
                                            data-question="<?php echo $i; ?>"><?php echo $i; ?></button>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Fixed Submit Button -->
                    <div class="border-t border-gray-200 bg-white p-4">
                        <div class="max-w-2xl mx-auto">
                            <button type="submit" name="submit_exam" class="w-full py-3 bg-green-600 text-white rounded-xl font-semibold hover:bg-green-700 transition submit-btn shadow-lg">
                                <i class="fas fa-check-circle mr-2"></i>İmtahanı Bitir
                            </button>
                        </div>
                    </div>
                </div>

            </form>

        </div>
    </div>

    <script>
    let currentQuestionIndex = 1;
    const totalQuestions = <?php echo $question_stats['total']; ?>;
    let isFormSubmitting = false;

    // ====== LOCAL STORAGE AND DATABASE SAVE FUNCTIONS ======
    const STORAGE_KEY = 'exam_<?php echo $exam_id; ?>_user_<?php echo $user_id; ?>_answers';
    const EXAM_ID = <?php echo $exam_id; ?>;
    const USER_ID = <?php echo $user_id; ?>;

    // Queue for database saves to avoid too many requests
    let saveQueue = new Set();
    let saveInProgress = false;

    // Save all answers to localStorage
    function saveAnswersToLocalStorage() {
        const answers = {};

        // Get all radio button answers
        document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
            const name = radio.name;
            const questionId = name.match(/answer\[(\d+)\]/)?.[1];
            if (questionId) {
                answers[questionId] = {
                    type: 'radio',
                    value: radio.value
                };
            }
        });

        // Get all textarea answers
        document.querySelectorAll('textarea.answer-input').forEach(textarea => {
            const name = textarea.name;
            const questionId = name.match(/answer\[(\d+)\]/)?.[1];
            if (questionId && textarea.value.trim() !== '') {
                answers[questionId] = {
                    type: 'textarea',
                    value: textarea.value
                };
            }
        });

        // Get all select answers
        document.querySelectorAll('select.answer-input').forEach(select => {
            const name = select.name;
            const questionId = name.match(/answer\[(\d+)\]/)?.[1];
            if (questionId && select.value !== '') {
                answers[questionId] = {
                    type: 'select',
                    value: select.value
                };
            }
        });

        // Save to localStorage
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({
                answers: answers,
                lastSaved: new Date().toISOString(),
                currentQuestion: currentQuestionIndex
            }));
            console.log('✓ Cavablar localStorage-ə saxlanıldı (ehtiyat nüsxə)');
        } catch (e) {
            console.error('localStorage saxlama xətası:', e);
        }
    }

    // Show save indicator briefly
    function showSaveIndicator() {
        const indicator = document.getElementById('saveIndicator');
        if (indicator) {
            indicator.style.opacity = '1';
            setTimeout(() => {
                indicator.style.opacity = '0';
            }, 2000);
        }
    }

    // Save single answer to database via AJAX
    function saveAnswerToDatabase(questionId, answerValue) {
        return fetch('save_answer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                exam_id: EXAM_ID,
                question_id: questionId,
                answer: answerValue
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('✓ Cavab serverə saxlanıldı: Q' + questionId);
                showSaveIndicator();
                return true;
            } else {
                console.error('Cavab saxlanmadı:', data.error);
                return false;
            }
        })
        .catch(error => {
            console.error('Server xətası:', error);
            return false;
        });
    }

    // Process save queue - save answers to database in batches
    async function processSaveQueue() {
        if (saveInProgress || saveQueue.size === 0) return;

        saveInProgress = true;
        const questionIds = Array.from(saveQueue);
        saveQueue.clear();

        for (const questionId of questionIds) {
            // Get current answer value
            let answerValue = '';

            // Check radio
            const radio = document.querySelector(`input[name="answer[${questionId}]"]:checked`);
            if (radio) {
                answerValue = radio.value;
            } else {
                // Check textarea
                const textarea = document.querySelector(`textarea[name="answer[${questionId}]"]`);
                if (textarea) {
                    answerValue = textarea.value;
                } else {
                    // Check select
                    const select = document.querySelector(`select[name="answer[${questionId}]"]`);
                    if (select) {
                        answerValue = select.value;
                    }
                }
            }

            if (answerValue !== '') {
                await saveAnswerToDatabase(questionId, answerValue);
            }
        }

        saveInProgress = false;

        // If more items were added while we were processing, process again
        if (saveQueue.size > 0) {
            setTimeout(processSaveQueue, 100);
        }
    }

    // Add answer to save queue
    function queueAnswerSave(questionId) {
        saveQueue.add(questionId);

        // Debounce - wait 1 second before saving to avoid too many requests
        clearTimeout(window.dbSaveTimeout);
        window.dbSaveTimeout = setTimeout(() => {
            processSaveQueue();
        }, 1000);
    }

    // Load answers from localStorage (only if not already filled from database)
    function loadAnswersFromLocalStorage() {
        try {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (!saved) {
                console.log('localStorage-də saxlanılmış cavab tapılmadı');
                return;
            }

            const data = JSON.parse(saved);
            console.log('localStorage-dən cavablar yüklənir...', data);

            let loadedCount = 0;

            if (data.answers) {
                Object.keys(data.answers).forEach(questionId => {
                    const answer = data.answers[questionId];

                    // Check if this question already has an answer from database (PHP)
                    let hasDbAnswer = false;

                    if (answer.type === 'radio') {
                        const existingRadio = document.querySelector(`input[name="answer[${questionId}]"]:checked`);
                        hasDbAnswer = existingRadio !== null;

                        // Only load from localStorage if no database answer
                        if (!hasDbAnswer) {
                            const radio = document.querySelector(`input[name="answer[${questionId}]"][value="${answer.value}"]`);
                            if (radio) {
                                radio.checked = true;
                                loadedCount++;
                            }
                        }
                    } else if (answer.type === 'textarea') {
                        const textarea = document.querySelector(`textarea[name="answer[${questionId}]"]`);
                        if (textarea) {
                            hasDbAnswer = textarea.value.trim() !== '';

                            // Only load from localStorage if no database answer
                            if (!hasDbAnswer) {
                                textarea.value = answer.value;
                                loadedCount++;
                            }
                        }
                    } else if (answer.type === 'select') {
                        const select = document.querySelector(`select[name="answer[${questionId}]"]`);
                        if (select) {
                            hasDbAnswer = select.value !== '';

                            // Only load from localStorage if no database answer
                            if (!hasDbAnswer) {
                                select.value = answer.value;
                                loadedCount++;
                            }
                        }
                    }
                });

                if (loadedCount > 0) {
                    console.log('✓ localStorage-dən ' + loadedCount + ' cavab yükləndi (verilənlər bazasında olmayan)');
                } else {
                    console.log('✓ Bütün cavablar artıq verilənlər bazasından yüklənib');
                }

                // Restore current question position
                if (data.currentQuestion) {
                    currentQuestionIndex = data.currentQuestion;
                }
            }
        } catch (e) {
            console.error('localStorage yükləmə xətası:', e);
        }
    }

    // Clear localStorage
    function clearLocalStorage() {
        try {
            localStorage.removeItem(STORAGE_KEY);
            console.log('✓ localStorage təmizləndi');
        } catch (e) {
            console.error('localStorage təmizləmə xətası:', e);
        }
    }

    // Show specific question
    function showQuestion(index) {
        // Hide all questions
        document.querySelectorAll('.question-container').forEach(q => {
            q.classList.remove('active');
        });

        // Show target question
        const targetQuestion = document.querySelector(`.question-container[data-question-index="${index}"]`);
        if (targetQuestion) {
            targetQuestion.classList.add('active');
            currentQuestionIndex = index;

            // Update material panel
            const groupKey = targetQuestion.dataset.group;
            const type = targetQuestion.dataset.type;

            // Switch to the correct tab
            document.querySelectorAll('.section-tab').forEach(tab => {
                tab.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
                tab.classList.add('text-gray-500');
                if (tab.dataset.type === type) {
                    tab.classList.remove('text-gray-500');
                    tab.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
                }
            });

            document.querySelectorAll('.material-content').forEach(m => m.style.display = 'none');
            const material = document.querySelector(`.material-content[data-group="${groupKey}"]`);
            if (material) {
                material.style.display = 'block';
            }

            // Update navigation buttons
            updateNavButtons();
            updateQuestionNav();

            // Save current position to localStorage
            saveAnswersToLocalStorage();
        }
    }

    // Update nav buttons state
    function updateNavButtons() {
        document.querySelectorAll('.prev-btn').forEach(btn => {
            btn.disabled = currentQuestionIndex === 1;
            btn.style.opacity = currentQuestionIndex === 1 ? '0.5' : '1';
        });

        document.querySelectorAll('.next-btn').forEach(btn => {
            if (currentQuestionIndex === totalQuestions) {
                btn.style.display = 'none';
            } else {
                btn.style.display = 'block';
            }
        });
    }

    // Update question navigation indicators
    function updateQuestionNav() {
        document.querySelectorAll('.question-nav-item').forEach(item => {
            item.classList.remove('current');
        });

        const currentNavItem = document.querySelector(`.question-nav-item[data-question="${currentQuestionIndex}"]`);
        if (currentNavItem) {
            currentNavItem.classList.add('current');
        }

        // Mark answered questions
        document.querySelectorAll('.question-container').forEach((container, idx) => {
            const questionNum = idx + 1;
            const hasAnswer = container.querySelector('.answer-input:checked') ||
                             (container.querySelector('textarea.answer-input') && container.querySelector('textarea.answer-input').value.trim() !== '') ||
                             (container.querySelector('select.answer-input') && container.querySelector('select.answer-input').value !== '');

            const navItem = document.querySelector(`.question-nav-item[data-question="${questionNum}"]`);
            if (navItem && hasAnswer && questionNum !== currentQuestionIndex) {
                navItem.classList.add('answered');
            } else if (navItem && questionNum !== currentQuestionIndex) {
                navItem.classList.remove('answered');
            }
        });
    }

    // Next/Prev button handlers
    document.addEventListener('click', function(e) {
        if (e.target.closest('.next-btn')) {
            if (currentQuestionIndex < totalQuestions) {
                showQuestion(currentQuestionIndex + 1);
            }
        }

        if (e.target.closest('.prev-btn')) {
            if (currentQuestionIndex > 1) {
                showQuestion(currentQuestionIndex - 1);
            }
        }

        // Question nav item click
        if (e.target.closest('.question-nav-item')) {
            const questionNum = parseInt(e.target.closest('.question-nav-item').dataset.question);
            showQuestion(questionNum);
        }
    });

    // Radio button selection
    document.querySelectorAll('.radio-option').forEach(option => {
        option.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                updateQuestionNav();
            }
        });
    });

    // Answer input change handler - save to both localStorage and database
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('answer-input') || e.target.type === 'radio') {
            updateQuestionNav();
            saveAnswersToLocalStorage();

            // Extract question ID and queue for database save
            const name = e.target.name;
            const questionId = name.match(/answer\[(\d+)\]/)?.[1];
            if (questionId) {
                queueAnswerSave(questionId);
            }
        }
    });

    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('answer-input')) {
            updateQuestionNav();

            // Debounce saving for text inputs
            clearTimeout(window.saveTimeout);
            window.saveTimeout = setTimeout(() => {
                saveAnswersToLocalStorage();

                // Extract question ID and queue for database save
                const name = e.target.name;
                const questionId = name.match(/answer\[(\d+)\]/)?.[1];
                if (questionId) {
                    queueAnswerSave(questionId);
                }
            }, 500);
        }
    });

    // Timer
    <?php if ($exam['timer'] > 0): ?>
    let timeLeft = <?php echo $exam['timer'] * 60; ?>;

    function updateTimer() {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;

        const timerEl = document.getElementById('timer');
        if (timerEl) {
            timerEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }

        if (timeLeft <= 0) {
            alert('Vaxt bitdi! İmtahan avtomatik olaraq təqdim ediləcək.');
            document.getElementById('examForm').submit();
            return;
        }

        timeLeft--;
    }

    updateTimer();
    setInterval(updateTimer, 1000);
    <?php endif; ?>

    // Form submission
    let confirmationDone = false;

    document.getElementById('examForm').addEventListener('submit', function(e) {
        if (confirmationDone) {
            const submitBtn = this.querySelector('.submit-btn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Təqdim edilir...';
            }
            isFormSubmitting = true;
            return true;
        }

        e.preventDefault();

        const answeredCount = document.querySelectorAll('.answer-input:checked').length +
                             Array.from(document.querySelectorAll('textarea.answer-input')).filter(ta => ta.value.trim() !== '').length +
                             Array.from(document.querySelectorAll('select.answer-input')).filter(sel => sel.value !== '').length;

        let confirmMessage = 'İmtahanı bitirmək istədiyinizə əminsiniz?';

        if (answeredCount < totalQuestions) {
            const unanswered = totalQuestions - answeredCount;
            confirmMessage = `Diqqət! ${unanswered} sual cavabsız qalıb.\n\nİmtahanı bitirmək istədiyinizə əminsiniz?`;
        }

        if (confirm(confirmMessage)) {
            confirmationDone = true;
            isFormSubmitting = true;

            // Clear localStorage when submitting exam
            clearLocalStorage();

            const submitBtn = this.querySelector('.submit-btn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Təqdim edilir...';

                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'submit_exam';
                hiddenInput.value = '1';
                this.appendChild(hiddenInput);

                HTMLFormElement.prototype.submit.call(this);
            }
        }
    });

    // Prevent accidental navigation away
    window.addEventListener('beforeunload', function(e) {
        if (!isFormSubmitting) {
            const confirmationMessage = 'İmtahan davam edir. Səhifəni tərk etmək istədiyinizə əminsiniz?';
            e.returnValue = confirmationMessage;
            return confirmationMessage;
        }
    });

    // Audio Play Count Tracking
    const audioPlayCounts = {};
    const MAX_PLAYS = 5;

    // Initialize play counts from localStorage
    function initAudioPlayCounts() {
        const savedCounts = localStorage.getItem('exam_<?php echo $exam_id; ?>_audio_plays');
        if (savedCounts) {
            Object.assign(audioPlayCounts, JSON.parse(savedCounts));
        }

        // Update UI for all audio players
        document.querySelectorAll('audio[data-group]').forEach(audio => {
            const groupKey = audio.dataset.group;
            if (!audioPlayCounts[groupKey]) {
                audioPlayCounts[groupKey] = 0;
            }
            updateAudioUI(groupKey);
        });
    }

    // Update audio UI based on play count
    function updateAudioUI(groupKey) {
        const count = audioPlayCounts[groupKey] || 0;
        const remaining = MAX_PLAYS - count;

        // Update counter display
        const counterEl = document.getElementById('playCount_' + groupKey);
        if (counterEl) {
            counterEl.textContent = count;
            counterEl.className = count >= MAX_PLAYS ? 'text-red-600' : 'text-gray-800';
        }

        // Update dots
        const dotsContainer = document.getElementById('playDots_' + groupKey);
        if (dotsContainer) {
            const dots = dotsContainer.querySelectorAll('div');
            dots.forEach((dot, idx) => {
                if (idx < count) {
                    dot.className = 'w-3 h-3 rounded-full bg-green-500 shadow-lg';
                } else {
                    dot.className = 'w-3 h-3 rounded-full bg-gray-300';
                }
            });
        }

        // Update remaining message
        const msgEl = document.getElementById('remainingMsg_' + groupKey);
        if (msgEl) {
            if (remaining > 0) {
                msgEl.innerHTML = `Qalan restart: <span class="text-green-600 font-bold">${remaining}</span>`;
            } else {
                msgEl.innerHTML = `<span class="text-red-600 font-bold"><i class="fas fa-ban mr-1"></i>Restart limiti doldu!</span>`;
            }
        }

        // Disable Restart button if limit reached, but keep Play button enabled
        const playBtn = document.getElementById('playBtn_' + groupKey);
        const restartBtn = document.getElementById('restartBtn_' + groupKey);

        if (count >= MAX_PLAYS) {
            // Only disable Restart button
            if (restartBtn) {
                restartBtn.disabled = true;
                restartBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
            // Keep Play button enabled for pause/resume
            if (playBtn) {
                playBtn.disabled = false;
                playBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        } else {
            // Enable both buttons
            if (playBtn) {
                playBtn.disabled = false;
                playBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            if (restartBtn) {
                restartBtn.disabled = false;
                restartBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    }

    // Save play counts to localStorage
    function saveAudioPlayCounts() {
        localStorage.setItem('exam_<?php echo $exam_id; ?>_audio_plays', JSON.stringify(audioPlayCounts));
    }

    // Play button handler
    document.addEventListener('click', function(e) {
        if (e.target.closest('.play-btn')) {
            const btn = e.target.closest('.play-btn');
            const groupKey = btn.dataset.group;
            const audio = document.getElementById('audio_' + groupKey);

            if (!audio) {
                console.error('Audio element not found for group:', groupKey);
                alert('Audio element tapılmadı. Səhifəni yeniləyib yenidən cəhd edin.');
                return;
            }

            if (btn.disabled) {
                console.log('Play button is disabled');
                return;
            }

            console.log('Playing audio:', audio.src);
            console.log('Audio ready state:', audio.readyState);
            console.log('Audio paused:', audio.paused);

            // If audio is paused or ended, play it
            if (audio.paused || audio.ended) {
                audio.play().then(() => {
                    console.log('Audio playing successfully');
                    btn.innerHTML = '<i class="fas fa-pause mr-2"></i>Pause';
                }).catch(err => {
                    console.error('Audio play error:', err);
                    console.error('Audio src:', audio.src);
                    console.error('Audio error code:', audio.error ? audio.error.code : 'none');
                    alert('Audio oynadılmadı. Fayl tapılmadı və ya oxuna bilmir.\n\nXəta: ' + err.message);
                });
            } else {
                // If already playing, pause it
                audio.pause();
                btn.innerHTML = '<i class="fas fa-play mr-2"></i>Play';
            }
        }

        // Pause button handler
        if (e.target.closest('.pause-btn')) {
            const btn = e.target.closest('.pause-btn');
            const groupKey = btn.dataset.group;
            const audio = document.getElementById('audio_' + groupKey);
            const playBtn = document.getElementById('playBtn_' + groupKey);

            if (audio) {
                audio.pause();
                console.log('Audio paused');
                if (playBtn) {
                    playBtn.innerHTML = '<i class="fas fa-play mr-2"></i>Play';
                }
            }
        }

        // Restart button handler - THIS increments the count
        if (e.target.closest('.restart-btn')) {
            const btn = e.target.closest('.restart-btn');
            const groupKey = btn.dataset.group;
            const audio = document.getElementById('audio_' + groupKey);
            const playBtn = document.getElementById('playBtn_' + groupKey);

            if (!audio || btn.disabled) {
                console.log('Restart button disabled or audio not found');
                return;
            }

            const currentCount = audioPlayCounts[groupKey] || 0;

            if (currentCount >= MAX_PLAYS) {
                alert('Restart limiti doldu! Artıq yenidən başlada bilməzsiniz.\n\nLakin Play/Pause düymələri ilə dinləməyə davam edə bilərsiniz.');
                return;
            }

            // Increment count for restart (only restart uses up a play)
            audioPlayCounts[groupKey] = currentCount + 1;
            saveAudioPlayCounts();
            updateAudioUI(groupKey);

            console.log('Restarting audio, play count:', audioPlayCounts[groupKey]);

            // Restart audio from beginning
            audio.currentTime = 0;
            audio.play().then(() => {
                console.log('Audio restarted successfully');
                if (playBtn) {
                    playBtn.innerHTML = '<i class="fas fa-pause mr-2"></i>Pause';
                }
            }).catch(err => {
                console.error('Audio restart error:', err);
                alert('Audio yenidən başladılmadı. Fayl tapılmadı və ya oxuna bilmir.\n\nXəta: ' + err.message);
            });
        }
    });

    // Note: Native audio controls are allowed now
    // Play/Pause can be used freely without counting

    // Audio error handling
    document.querySelectorAll('audio[data-group]').forEach(audio => {
        audio.addEventListener('error', function(e) {
            console.error('Audio loading error:', e);
            console.error('Audio src:', this.src);
            console.error('Audio error code:', this.error ? this.error.code : 'unknown');
            console.error('Audio error message:', this.error ? this.error.message : 'unknown');

            const errorMessages = {
                1: 'MEDIA_ERR_ABORTED - Yükləmə ləğv edildi',
                2: 'MEDIA_ERR_NETWORK - Şəbəkə xətası',
                3: 'MEDIA_ERR_DECODE - Audio faylı decode edilə bilmədi',
                4: 'MEDIA_ERR_SRC_NOT_SUPPORTED - Audio formatı dəstəklənmir və ya fayl tapılmadı'
            };

            const errorCode = this.error ? this.error.code : 0;
            const errorMsg = errorMessages[errorCode] || 'Naməlum xəta';
            console.error('Error type:', errorMsg);
        });

        audio.addEventListener('loadedmetadata', function() {
            console.log('Audio metadata loaded successfully:', this.src);
            console.log('Duration:', this.duration);
        });

        audio.addEventListener('canplay', function() {
            console.log('Audio can play:', this.src);
        });
    });

    // Initialize - Load saved answers and restore state
    initAudioPlayCounts();
    loadAnswersFromLocalStorage();
    showQuestion(currentQuestionIndex);
    updateQuestionNav();

    // Auto-save periodically (every 30 seconds) as backup
    setInterval(function() {
        if (!isFormSubmitting) {
            saveAnswersToLocalStorage();
        }
    }, 30000);

    // Section Tab Switching
    document.querySelectorAll('.section-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const targetType = this.dataset.type;

            // Update tab styling
            document.querySelectorAll('.section-tab').forEach(t => {
                t.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
                t.classList.add('text-gray-500');
            });
            this.classList.remove('text-gray-500');
            this.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');

            // Show all materials of this type
            document.querySelectorAll('.material-content').forEach(m => {
                if (m.dataset.type === targetType) {
                    m.style.display = 'block';
                } else {
                    m.style.display = 'none';
                }
            });
        });
    });
    </script>

</body>
</html>
