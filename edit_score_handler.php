<?php
// Ayrı handler fayl - istənilən səhifədən istifadə edilə bilər
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db.php';
$database = new Database();
$conn = $database->getConnection();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answer_id = isset($_POST['answer_id']) ? intval($_POST['answer_id']) : 0;
    $new_score = isset($_POST['new_score']) ? floatval($_POST['new_score']) : 0;

    if ($answer_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Yanlış answer ID']);
        exit;
    }

    if ($new_score < 0) {
        echo json_encode(['success' => false, 'message' => 'Bal mənfi ola bilməz']);
        exit;
    }

    try {
        $conn->beginTransaction();

        // 1. Cavabı yenilə
        $sql_update = "UPDATE answers SET score_earned = :new_score WHERE id_answer = :answer_id";
        $stmt = $conn->prepare($sql_update);
        $stmt->bindParam(':new_score', $new_score);
        $stmt->bindParam(':answer_id', $answer_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // 2. Exam ID və User ID tap
            $sql_get_info = "SELECT exam_id, user_id FROM answers WHERE id_answer = :answer_id";
            $stmt_info = $conn->prepare($sql_get_info);
            $stmt_info->bindParam(':answer_id', $answer_id);
            $stmt_info->execute();
            $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

            if ($info) {
                $exam_id = $info['exam_id'];
                $user_id = $info['user_id'];

                // 3. Yeni toplam balı hesabla
                $sql_calc = "SELECT SUM(score_earned) as new_total FROM answers WHERE exam_id = :exam_id AND user_id = :user_id";
                $stmt_calc = $conn->prepare($sql_calc);
                $stmt_calc->bindParam(':exam_id', $exam_id);
                $stmt_calc->bindParam(':user_id', $user_id);
                $stmt_calc->execute();
                $result = $stmt_calc->fetch(PDO::FETCH_ASSOC);
                $new_total = $result['new_total'];

                // 4. Scores cədvəlini yenilə
                $sql_update_scores = "UPDATE scores SET score = :new_total WHERE exam_id = :exam_id AND user_id = :user_id";
                $stmt_scores = $conn->prepare($sql_update_scores);
                $stmt_scores->bindParam(':new_total', $new_total);
                $stmt_scores->bindParam(':exam_id', $exam_id);
                $stmt_scores->bindParam(':user_id', $user_id);
                $stmt_scores->execute();
            }

            $conn->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Bal və toplam bal uğurla yeniləndi!',
                'new_score' => $new_score
            ]);
        } else {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'Heç bir dəyişiklik edilmədi']);
        }
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Xəta: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Yanlış sorğu metodu']);
}
?>
