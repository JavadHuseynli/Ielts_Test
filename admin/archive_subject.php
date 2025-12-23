<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

checkLogin();

// Only admin can archive subjects
if (!is_admin()) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

$database = new Database();
$db = $database->getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['subject_id'])) {
    $subject_id = intval($_POST['subject_id']);

    try {
        $db->beginTransaction();

        // Get subject details
        $query = "SELECT * FROM subjects WHERE id_subject = :subject_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":subject_id", $subject_id);
        $stmt->execute();
        $subject = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$subject) {
            throw new Exception("Fənn tapılmadı");
        }

        // Count questions for this subject
        $countQuery = "SELECT COUNT(DISTINCT qr.id_question_text) as total_questions
                      FROM question_read qr
                      JOIN question_files qf ON qr.id_read_quest_file = qf.id_read_quest_file
                      WHERE qf.subject_id = :subject_id";
        $countStmt = $db->prepare($countQuery);
        $countStmt->bindParam(":subject_id", $subject_id);
        $countStmt->execute();
        $counts = $countStmt->fetch(PDO::FETCH_ASSOC);

        // Count exams for this subject
        $examCountQuery = "SELECT COUNT(*) as total_exams FROM exams WHERE id_subject = :subject_id";
        $examCountStmt = $db->prepare($examCountQuery);
        $examCountStmt->bindParam(":subject_id", $subject_id);
        $examCountStmt->execute();
        $examCounts = $examCountStmt->fetch(PDO::FETCH_ASSOC);

        // Archive the subject
        $archiveQuery = "INSERT INTO archived_subjects
                        (id_subject, subjectname, timer, total_questions, total_exams, archived_by_user_id)
                        VALUES (:id_subject, :subjectname, :timer, :total_questions, :total_exams, :archived_by)";
        $archiveStmt = $db->prepare($archiveQuery);
        $archiveStmt->execute([
            ':id_subject' => $subject['id_subject'],
            ':subjectname' => $subject['subjectname'],
            ':timer' => $subject['timer'],
            ':total_questions' => $counts['total_questions'] ?? 0,
            ':total_exams' => $examCounts['total_exams'] ?? 0,
            ':archived_by' => $_SESSION['user_id']
        ]);

        // Archive all questions for this subject
        $questionsQuery = "SELECT qr.*, qf.file_type, qf.subject_id, s.subjectname, qt.quest_type_name, qt.question_var
                          FROM question_read qr
                          JOIN question_files qf ON qr.id_read_quest_file = qf.id_read_quest_file
                          JOIN subjects s ON qf.subject_id = s.id_subject
                          JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
                          WHERE qf.subject_id = :subject_id";
        $questionsStmt = $db->prepare($questionsQuery);
        $questionsStmt->bindParam(":subject_id", $subject_id);
        $questionsStmt->execute();
        $questions = $questionsStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($questions as $question) {
            // Archive question
            $archiveQuestQuery = "INSERT INTO archived_questions
                                 (id_question_text, id_read_quest_file, id_question_type, question_text, question_score,
                                  file_type, subject_id, subject_name, question_type_name, question_var, archived_by_user_id)
                                 VALUES (:id_question_text, :id_read_quest_file, :id_question_type, :question_text, :question_score,
                                         :file_type, :subject_id, :subject_name, :question_type_name, :question_var, :archived_by)";
            $archiveQuestStmt = $db->prepare($archiveQuestQuery);
            $archiveQuestStmt->execute([
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

            // Archive question variants
            if ($question['question_var'] == 'multiple') {
                $varQuery = "SELECT * FROM multiple_questions WHERE id_question_text = :question_id";
                $varStmt = $db->prepare($varQuery);
                $varStmt->bindParam(":question_id", $question['id_question_text']);
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
                $varStmt->bindParam(":question_id", $question['id_question_text']);
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
                $varStmt->bindParam(":question_id", $question['id_question_text']);
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
        }

        // Delete the subject (this will cascade delete questions, exams, etc.)
        $deleteQuery = "DELETE FROM subjects WHERE id_subject = :subject_id";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->bindParam(":subject_id", $subject_id);
        $deleteStmt->execute();

        $db->commit();

        header("Location: subjects.php?success=subject_archived");
        exit();

    } catch(Exception $e) {
        $db->rollBack();
        header("Location: subjects.php?error=archive_failed&message=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: subjects.php");
    exit();
}
?>
