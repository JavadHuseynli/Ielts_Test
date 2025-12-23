<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

checkLogin();

// Permission Check - Only Kafedra
if (!is_kafedra()) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$exam_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($exam_id == 0) {
    header("Location: exams.php?error=no_exam_specified");
    exit();
}

// Fetch Exam Information
$query = "SELECT e.id_exam, e.confirmed_by FROM exams e WHERE e.id_exam = :exam_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":exam_id", $exam_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: exams.php?error=exam_not_found");
    exit();
}

$exam = $stmt->fetch(PDO::FETCH_ASSOC);

// Mark exam as confirmed by Kafedra
if (!$exam['confirmed_by']) {
    $updateQuery = "UPDATE exams SET confirmed_by = :user_id, confirmed_at = NOW() WHERE id_exam = :exam_id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':exam_id' => $exam_id
    ]);
}

// Redirect back to exam results
header("Location: exam_results.php?id=" . $exam_id . "&success=exam_confirmed");
exit();
?>
