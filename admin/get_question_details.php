<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

// checkAdminRights() is too restrictive, teachers also need this
checkLogin();
if (!is_admin() && !is_kafedra() && !is_teacher()) {
    echo json_encode(['error' => 'Access Denied']);
    exit;
}

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'Yanlış sorğu parametri']);
    exit;
}

$question_id = intval($_GET['id']);
$database = new Database();
$db = $database->getConnection();

$base_query = "SELECT qr.id_question_text, qr.question_text, qr.question_score, qt.id_quest_type, qt.question_var
               FROM question_read qr
               JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
               WHERE qr.id_question_text = :id";
$stmt = $db->prepare($base_query);
$stmt->bindParam(":id", $question_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    echo json_encode(['error' => 'Sual tapılmadı']);
    exit;
}

$result = $stmt->fetch(PDO::FETCH_ASSOC);
$question_var = $result['question_var'];

$details_result = [];
if ($question_var == 'multiple') {
    $query = "SELECT var_a, var_b, var_c, var_d, correct_v FROM multiple_questions WHERE id_question_text = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $question_id);
    $stmt->execute();
    $details_result = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($question_var == 'open') {
    $query = "SELECT corr_v FROM open_questions WHERE id_question_text = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $question_id);
    $stmt->execute();
    $details_result = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($question_var == 'matching') {
    $query = "SELECT variants, corr_variant FROM matching_questions WHERE id_question_text = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $question_id);
    $stmt->execute();
    $details_result = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Merge the base result with the details result
if ($details_result) {
    $result = array_merge($result, $details_result);
}

echo json_encode($result);
?>