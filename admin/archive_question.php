<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

checkLogin();

// Only admin can archive questions
if (!is_admin()) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

$database = new Database();
$db = $database->getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['question_id'])) {
    $question_id = intval($_POST['question_id']);

    try {
        $db->beginTransaction();

        // Get question details with all related info
        $query = "SELECT qr.*, qf.file_type, qf.subject_id, s.subjectname, qt.quest_type_name, qt.question_var
                  FROM question_read qr
                  JOIN question_files qf ON qr.id_read_quest_file = qf.id_read_quest_file
                  JOIN subjects s ON qf.subject_id = s.id_subject
                  JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
                  WHERE qr.id_question_text = :question_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":question_id", $question_id);
        $stmt->execute();
        $question = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$question) {
            throw new Exception("Sual tapılmadı");
        }

        // Insert into archived_questions
        $archiveQuery = "INSERT INTO archived_questions
                        (id_question_text, id_read_quest_file, id_question_type, question_text, question_score,
                         file_type, subject_id, subject_name, question_type_name, question_var, archived_by_user_id)
                        VALUES (:id_question_text, :id_read_quest_file, :id_question_type, :question_text, :question_score,
                                :file_type, :subject_id, :subject_name, :question_type_name, :question_var, :archived_by)";
        $archiveStmt = $db->prepare($archiveQuery);
        $archiveStmt->execute([
            ':id_question_text' => $question['id_question_text'],
            ':id_read_quest_file' => $question['id_read_quest_file'],
            ':id_question_type' => $question['id_question_type'],
            ':question_text' => $question['question_text'],
            ':question_score' => $question['question_score'],
            ':file_type' => $question['file_type'],
            ':subject_id' => $question['subject_id'],
            ':subject_name' => $question['subjectname'],
            ':question_type_name' => $question['quest_type_name'],
            ':question_var' => $question['question_var'],
            ':archived_by' => $_SESSION['user_id']
        ]);

        $archived_question_id = $db->lastInsertId();

        // Archive question variants based on type
        if ($question['question_var'] == 'multiple') {
            $varQuery = "SELECT * FROM multiple_questions WHERE id_question_text = :question_id";
            $varStmt = $db->prepare($varQuery);
            $varStmt->bindParam(":question_id", $question_id);
            $varStmt->execute();
            $variant = $varStmt->fetch(PDO::FETCH_ASSOC);

            if ($variant) {
                $archiveVarQuery = "INSERT INTO archived_multiple_questions
                                   (id_archived_question, var_a, var_b, var_c, var_d, correct_v)
                                   VALUES (:archived_id, :var_a, :var_b, :var_c, :var_d, :correct_v)";
                $archiveVarStmt = $db->prepare($archiveVarQuery);
                $archiveVarStmt->execute([
                    ':archived_id' => $archived_question_id,
                    ':var_a' => $variant['var_a'],
                    ':var_b' => $variant['var_b'],
                    ':var_c' => $variant['var_c'],
                    ':var_d' => $variant['var_d'],
                    ':correct_v' => $variant['correct_v']
                ]);
            }
        } elseif ($question['question_var'] == 'open') {
            $varQuery = "SELECT * FROM open_questions WHERE id_question_text = :question_id";
            $varStmt = $db->prepare($varQuery);
            $varStmt->bindParam(":question_id", $question_id);
            $varStmt->execute();
            $variant = $varStmt->fetch(PDO::FETCH_ASSOC);

            if ($variant) {
                $archiveVarQuery = "INSERT INTO archived_open_questions
                                   (id_archived_question, corr_v)
                                   VALUES (:archived_id, :corr_v)";
                $archiveVarStmt = $db->prepare($archiveVarQuery);
                $archiveVarStmt->execute([
                    ':archived_id' => $archived_question_id,
                    ':corr_v' => $variant['corr_v']
                ]);
            }
        } elseif ($question['question_var'] == 'matching') {
            $varQuery = "SELECT * FROM matching_questions WHERE id_question_text = :question_id";
            $varStmt = $db->prepare($varQuery);
            $varStmt->bindParam(":question_id", $question_id);
            $varStmt->execute();
            $variant = $varStmt->fetch(PDO::FETCH_ASSOC);

            if ($variant) {
                $archiveVarQuery = "INSERT INTO archived_matching_questions
                                   (id_archived_question, variants, corr_variant)
                                   VALUES (:archived_id, :variants, :corr_variant)";
                $archiveVarStmt = $db->prepare($archiveVarQuery);
                $archiveVarStmt->execute([
                    ':archived_id' => $archived_question_id,
                    ':variants' => $variant['variants'],
                    ':corr_variant' => $variant['corr_variant']
                ]);
            }
        }

        // Delete the original question (variants will be deleted by CASCADE)
        $deleteQuery = "DELETE FROM question_read WHERE id_question_text = :question_id";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->bindParam(":question_id", $question_id);
        $deleteStmt->execute();

        $db->commit();

        header("Location: " . $_SERVER['HTTP_REFERER'] . "&success=question_archived");
        exit();

    } catch(Exception $e) {
        $db->rollBack();
        header("Location: " . $_SERVER['HTTP_REFERER'] . "&error=archive_failed");
        exit();
    }
} else {
    header("Location: dashboard.php");
    exit();
}
?>
