<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

checkLogin();

// Permission Check - Only Admin can unconfirm exams
if (!is_admin()) {
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
$query = "SELECT e.id_exam, e.confirmed_by, e.confirmed_at FROM exams e WHERE e.id_exam = :exam_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":exam_id", $exam_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: exams.php?error=exam_not_found");
    exit();
}

$exam = $stmt->fetch(PDO::FETCH_ASSOC);

// Remove confirmation (set to NULL, not 'NULL')
if ($exam['confirmed_by']) {
    try {
        // CORRECTLY set to NULL without quotes
        $updateQuery = "UPDATE exams SET confirmed_by = NULL, confirmed_at = NULL WHERE id_exam = :exam_id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([':exam_id' => $exam_id]);

        // Success - redirect back
        header("Location: exam_results.php?id=" . $exam_id . "&success=exam_unconfirmed");
        exit();
    } catch (PDOException $e) {
        error_log("Təsdiq geri alma xətası: " . $e->getMessage());
        header("Location: exam_results.php?id=" . $exam_id . "&error=unconfirm_failed");
        exit();
    }
} else {
    // Already not confirmed
    header("Location: exam_results.php?id=" . $exam_id . "&info=already_not_confirmed");
    exit();
}
?>
