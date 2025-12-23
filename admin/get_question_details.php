<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";
checkAdminRights();

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'Yanlış sorğu parametri']);
    exit;
}

$question_id = intval($_GET['id']);
$database = new Database();
$db = $database->getConnection();

// İlk öncə sual tipini müəyyən etmək
$query = "SELECT qt.question_var
          FROM question_read qr
          JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
          WHERE qr.id_question_text = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $question_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    echo json_encode(['error' => 'Sual tapılmadı']);
    exit;
}

$question_type = $stmt->fetch(PDO::FETCH_ASSOC)['question_var'];
$result = [];

// Sual tipinə görə əlavə məlumatlar
if ($question_type == 'multiple') {
    $query = "SELECT var_a, var_b, var_c, var_d, correct_v 
              FROM multiple_questions
              WHERE id_question_text = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $question_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($question_type == 'open') {
    $query = "SELECT corr_v 
              FROM open_questions
              WHERE id_question_text = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $question_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($question_type == 'matching') {
    $query = "SELECT variants, corr_variant 
              FROM matching_questions
              WHERE id_question_text = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $question_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
}

echo json_encode($result);
?>