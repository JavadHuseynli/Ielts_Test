<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db.php';
$database = new Database();
$conn = $database->getConnection();

$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 318;
$fix = isset($_GET['fix']) ? true : false;

// TƏK CAVABI YENILƏ (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_single'])) {
    header('Content-Type: application/json');

    $answer_id = intval($_POST['answer_id']);
    $new_score = floatval($_POST['new_score']);

    try {
        $sql_update = "UPDATE answers SET score_earned = :new_score WHERE id_answer = :answer_id";
        $stmt = $conn->prepare($sql_update);
        $stmt->bindParam(':new_score', $new_score);
        $stmt->bindParam(':answer_id', $answer_id);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Bal yeniləndi!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bal Uyğunsuzluqları - Düzəldiş</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding: 20px; }
        .problem-row { background-color: #fff3cd; }
        .fixed-row { background-color: #d4edda; }
        .edit-btn { min-width: 80px; }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header bg-danger text-white">
            <h3><i class="fas fa-exclamation-triangle"></i> Bal Uyğunsuzluqları - İmtahan #<?= $exam_id ?></h3>
        </div>
        <div class="card-body">

            <?php
            // UYĞUNSUZLUQLARI TAP
            $sql_problems = "
                SELECT
                    a.id_answer,
                    a.exam_id,
                    u.f_name AS telebe_adi,
                    qr.id_question_text AS sual_no,
                    LEFT(qr.question_text, 100) AS sual_metni,
                    qr.question_score AS sual_maksimum_bal,
                    a.user_answer AS telebe_cavabi,
                    a.correct_var AS duzgun_cavab,
                    a.is_correct AS duzgundur,
                    a.score_earned AS telebe_alinan_bal,
                    (qr.question_score - a.score_earned) AS ferq

                FROM answers a
                INNER JOIN users u ON a.user_id = u.id_users
                INNER JOIN question_read qr ON a.id_questions = qr.id_question_text

                WHERE
                    a.exam_id = :exam_id
                    AND a.is_correct = 1  -- Düzgün cavab verib
                    AND a.score_earned < qr.question_score  -- Amma tam bal almayıb

                ORDER BY u.f_name, qr.id_question_text
            ";

            $stmt = $conn->prepare($sql_problems);
            $stmt->bindParam(':exam_id', $exam_id);
            $stmt->execute();
            $problems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $problem_count = count($problems);
            ?>

            <div class="alert alert-warning">
                <h5><i class="fas fa-search"></i> Tapılan Uyğunsuzluq Sayı: <strong><?= $problem_count ?></strong></h5>
                <p class="mb-0">Düzgün cavab verib, amma tam bal almayan tələbələr</p>
            </div>

            <?php if ($problem_count > 0): ?>

                <?php if (!$fix): ?>
                    <!-- DÜZƏLDİŞ DÜYMƏSİ -->
                    <div class="alert alert-info">
                        <h5><i class="fas fa-wrench"></i> Düzəldiş</h5>
                        <p>Bu uyğunsuzluqları avtomatik düzəltmək istəyirsiniz?</p>
                        <a href="?exam_id=<?= $exam_id ?>&fix=1" class="btn btn-danger btn-lg">
                            <i class="fas fa-magic"></i> HAMISI DÜZƏLSİN (<?= $problem_count ?> cavab)
                        </a>
                    </div>
                <?php else: ?>
                    <!-- DÜZƏLDİŞ EDİLİR -->
                    <div class="alert alert-success">
                        <h5><i class="fas fa-check-circle"></i> Düzəldiş aparılır...</h5>
                    </div>

                    <?php
                    try {
                        $conn->beginTransaction();

                        // 1. Answers cədvəlini yenilə
                        $sql_update = "
                            UPDATE answers a
                            INNER JOIN question_read qr ON a.id_questions = qr.id_question_text
                            SET a.score_earned = qr.question_score
                            WHERE
                                a.exam_id = :exam_id
                                AND a.is_correct = 1
                                AND a.score_earned < qr.question_score
                        ";

                        $stmt_update = $conn->prepare($sql_update);
                        $stmt_update->bindParam(':exam_id', $exam_id);
                        $stmt_update->execute();

                        $updated_count = $stmt_update->rowCount();

                        // 2. Scores cədvəlini yenilə (toplam balları)
                        $sql_update_scores = "
                            UPDATE scores sc
                            INNER JOIN (
                                SELECT
                                    a.exam_id,
                                    a.user_id,
                                    SUM(a.score_earned) AS yeni_toplam
                                FROM answers a
                                WHERE a.exam_id = :exam_id
                                GROUP BY a.exam_id, a.user_id
                            ) AS calc ON sc.exam_id = calc.exam_id AND sc.user_id = calc.user_id
                            SET sc.score = calc.yeni_toplam
                            WHERE sc.exam_id = :exam_id
                        ";

                        $stmt_scores = $conn->prepare($sql_update_scores);
                        $stmt_scores->bindParam(':exam_id', $exam_id);
                        $stmt_scores->execute();

                        $scores_updated = $stmt_scores->rowCount();

                        $conn->commit();

                        echo "<div class='alert alert-success'>";
                        echo "<h4>✅ UĞURLU!</h4>";
                        echo "<p><strong>$updated_count</strong> cavabın balı düzəldildi!</p>";
                        echo "<p><strong>$scores_updated</strong> tələbənin toplam balı yeniləndi!</p>";
                        echo "<a href='?exam_id=$exam_id' class='btn btn-primary'>Yenidən Yoxla</a> ";
                        echo "<a href='test_simple.php?exam_id=$exam_id' class='btn btn-success'>Nəticələrə Bax</a>";
                        echo "</div>";

                    } catch (Exception $e) {
                        $conn->rollBack();
                        echo "<div class='alert alert-danger'>";
                        echo "<h4>❌ XƏTA!</h4>";
                        echo "<p>" . $e->getMessage() . "</p>";
                        echo "</div>";
                    }
                    ?>
                <?php endif; ?>

                <!-- PROBLEM SİYAHISI -->
                <h5 class="mt-4">Problem Siyahısı:</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Tələbə</th>
                                <th>Sual No</th>
                                <th>Sual</th>
                                <th>Cavab</th>
                                <th>Düzgün</th>
                                <th>Status</th>
                                <th>Alınan Bal</th>
                                <th>Olmalı Bal</th>
                                <th>Fərq</th>
                                <th>Əməliyyat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($problems as $p): ?>
                            <tr class="<?= $fix ? 'fixed-row' : 'problem-row' ?>" id="row-<?= $p['id_answer'] ?>">
                                <td><?= $p['id_answer'] ?></td>
                                <td><?= htmlspecialchars($p['telebe_adi']) ?></td>
                                <td class="text-center"><strong>#<?= $p['sual_no'] ?></strong></td>
                                <td><small><?= htmlspecialchars($p['sual_metni']) ?></small></td>
                                <td class="text-center"><code><?= htmlspecialchars($p['telebe_cavabi']) ?></code></td>
                                <td class="text-center"><code><?= htmlspecialchars($p['duzgun_cavab']) ?></code></td>
                                <td class="text-center">
                                    <span class="badge bg-success">✓ Düzgün</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark score-display-<?= $p['id_answer'] ?>"><?= $p['telebe_alinan_bal'] ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success"><?= $p['sual_maksimum_bal'] ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger ferq-display-<?= $p['id_answer'] ?>">-<?= $p['ferq'] ?></span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-primary edit-btn"
                                            data-id="<?= $p['id_answer'] ?>"
                                            data-current="<?= $p['telebe_alinan_bal'] ?>"
                                            data-max="<?= $p['sual_maksimum_bal'] ?>"
                                            data-student="<?= htmlspecialchars($p['telebe_adi']) ?>"
                                            data-question="<?= $p['sual_no'] ?>">
                                        <i class="fas fa-edit"></i> EDIT
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle"></i> Heç bir uyğunsuzluq tapılmadı!</h5>
                    <p class="mb-0">Bütün düzgün cavablar tam bal alıb.</p>
                    <a href="test_simple.php?exam_id=<?= $exam_id ?>" class="btn btn-primary mt-2">
                        Nəticələrə Bax
                    </a>
                </div>
            <?php endif; ?>

            <!-- DİGƏR İMTAHANLAR -->
            <hr>
            <h5>Digər İmtahanları Yoxla:</h5>
            <?php
            $sql_exams = "SELECT id_exam, date_exam FROM exams WHERE id_exam >= 300 ORDER BY id_exam DESC LIMIT 20";
            $stmt_exams = $conn->prepare($sql_exams);
            $stmt_exams->execute();
            $exams = $stmt_exams->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <div class="row">
                <?php foreach ($exams as $e): ?>
                <div class="col-md-3 mb-2">
                    <a href="?exam_id=<?= $e['id_exam'] ?>" class="btn btn-<?= $e['id_exam'] == $exam_id ? 'primary' : 'outline-secondary' ?> w-100">
                        Exam #<?= $e['id_exam'] ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> Balı Dəyişdir
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label"><strong>Tələbə:</strong></label>
                    <p id="edit-student" class="text-muted"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label"><strong>Sual No:</strong></label>
                    <p id="edit-question" class="text-muted"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label"><strong>Maksimum Bal:</strong></label>
                    <p id="edit-max" class="text-muted"></p>
                </div>
                <div class="mb-3">
                    <label for="new-score" class="form-label"><strong>Yeni Bal:</strong></label>
                    <input type="number"
                           class="form-control form-control-lg"
                           id="new-score"
                           step="0.01"
                           min="0"
                           placeholder="Yeni balı daxil edin">
                    <small class="text-muted">Hazırki bal: <span id="edit-current"></span></small>
                </div>
                <div id="edit-message" class="alert" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv et</button>
                <button type="button" class="btn btn-success" id="save-score-btn">
                    <i class="fas fa-save"></i> Yadda Saxla
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let currentAnswerId = null;
    let currentMaxScore = null;
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));

    // EDIT düyməsinə klik
    $('.edit-btn').on('click', function() {
        currentAnswerId = $(this).data('id');
        const currentScore = $(this).data('current');
        currentMaxScore = parseFloat($(this).data('max'));
        const student = $(this).data('student');
        const question = $(this).data('question');

        // Modal məlumatlarını doldur
        $('#edit-student').text(student);
        $('#edit-question').text('#' + question);
        $('#edit-max').text(currentMaxScore);
        $('#edit-current').text(currentScore);
        $('#new-score').val(currentScore);
        $('#new-score').attr('max', currentMaxScore);
        $('#edit-message').hide();

        // Modalı aç
        editModal.show();
    });

    // YADDA SAXLA düyməsi
    $('#save-score-btn').on('click', function() {
        const newScore = parseFloat($('#new-score').val());

        // Yoxlama
        if (isNaN(newScore)) {
            showMessage('Xəta: Düzgün bal daxil edin!', 'danger');
            return;
        }

        if (newScore < 0) {
            showMessage('Xəta: Bal mənfi ola bilməz!', 'danger');
            return;
        }

        if (newScore > currentMaxScore) {
            showMessage('Xəta: Bal maksimum baldan çox ola bilməz! (Max: ' + currentMaxScore + ')', 'danger');
            return;
        }

        // AJAX ilə yenilə
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: {
                update_single: 1,
                answer_id: currentAnswerId,
                new_score: newScore
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showMessage('✅ Bal uğurla yeniləndi!', 'success');

                    // Səhifədəki balı yenilə
                    $('.score-display-' + currentAnswerId).text(newScore);

                    // Fərqi yenilə
                    const ferq = currentMaxScore - newScore;
                    if (ferq > 0) {
                        $('.ferq-display-' + currentAnswerId).text('-' + ferq.toFixed(2));
                    } else {
                        $('.ferq-display-' + currentAnswerId).text('0');
                        // Sətiri yaşıl et
                        $('#row-' + currentAnswerId).removeClass('problem-row').addClass('fixed-row');
                    }

                    // 1.5 saniyə sonra modalı bağla
                    setTimeout(function() {
                        editModal.hide();
                        location.reload(); // Səhifəni yenilə
                    }, 1500);
                } else {
                    showMessage('❌ Xəta: ' + response.message, 'danger');
                }
            },
            error: function() {
                showMessage('❌ Server xətası!', 'danger');
            }
        });
    });

    function showMessage(msg, type) {
        $('#edit-message')
            .removeClass('alert-success alert-danger')
            .addClass('alert-' + type)
            .html(msg)
            .show();
    }
});
</script>
</body>
</html>
