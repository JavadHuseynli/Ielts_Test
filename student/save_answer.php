<?php
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Don't display errors in AJAX response
require_once "../includes/db.php";
require_once "../includes/auth.php";

// Set JSON header
header('Content-Type: application/json');

// Authentication check
checkLogin();
if ($_SESSION['status'] != 'student') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['exam_id']) || !isset($input['question_id']) || !isset($input['answer'])) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit();
    }

    $exam_id = intval($input['exam_id']);
    $question_id = intval($input['question_id']);
    $user_answer = $input['answer'];
    $user_id = $_SESSION['user_id'];

    // Validate that this exam belongs to the user's group
    $database = new Database();
    $db = $database->getConnection();

    $check_query = "SELECT e.id_exam FROM exams e
                    WHERE e.id_exam = :exam_id
                    AND e.id_student_group = :group_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":exam_id", $exam_id);
    $check_stmt->bindParam(":group_id", $_SESSION['group_id']);
    $check_stmt->execute();

    if ($check_stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid exam']);
        exit();
    }

    // Check if answer already exists
    $query = "SELECT id_answer FROM answers
              WHERE exam_id = :exam_id
              AND id_questions = :question_id
              AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":exam_id", $exam_id);
    $stmt->bindParam(":question_id", $question_id);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();

    $now = date('Y-m-d H:i:s');

    if ($stmt->rowCount() > 0) {
        // Update existing answer
        $answer_id = $stmt->fetch(PDO::FETCH_ASSOC)['id_answer'];
        $update_query = "UPDATE answers
                        SET user_answer = :user_answer, datetime = :datetime
                        WHERE id_answer = :id_answer";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(":user_answer", $user_answer);
        $update_stmt->bindParam(":datetime", $now);
        $update_stmt->bindParam(":id_answer", $answer_id);
        $update_stmt->execute();
    } else {
        // Insert new answer
        $insert_query = "INSERT INTO answers (exam_id, id_questions, user_id, user_answer, datetime)
                        VALUES (:exam_id, :question_id, :user_id, :user_answer, :datetime)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(":exam_id", $exam_id);
        $insert_stmt->bindParam(":question_id", $question_id);
        $insert_stmt->bindParam(":user_id", $user_id);
        $insert_stmt->bindParam(":user_answer", $user_answer);
        $insert_stmt->bindParam(":datetime", $now);
        $insert_stmt->execute();
    }

    echo json_encode([
        'success' => true,
        'question_id' => $question_id,
        'saved_at' => $now
    ]);

} catch (Exception $e) {
    error_log("Save answer error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
?>
