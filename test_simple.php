<?php
// Veritabanı bağlantısı
require_once 'includes/db.php';

$database = new Database();
$conn = $database->getConnection();

// İmtahan ID
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 318;

// AVTOMATIK DÜZƏLDİŞ
$auto_fix = isset($_GET['auto_fix']) ? true : false;
$fix_message = '';
$fix_success = false;

if ($auto_fix) {
    try {
        $conn->beginTransaction();

        // 1. Answers cədvəlini düzəlt
        $sql_fix = "
            UPDATE answers a
            INNER JOIN question_read qr ON a.id_questions = qr.id_question_text
            SET a.score_earned = qr.question_score
            WHERE
                a.exam_id = :exam_id
                AND a.is_correct = 1
                AND a.score_earned < qr.question_score
        ";

        $stmt_fix = $conn->prepare($sql_fix);
        $stmt_fix->bindParam(':exam_id', $exam_id);
        $stmt_fix->execute();
        $fixed_count = $stmt_fix->rowCount();

        // 2. Scores cədvəlini yenilə
        $sql_scores = "
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

        $stmt_scores = $conn->prepare($sql_scores);
        $stmt_scores->bindParam(':exam_id', $exam_id);
        $stmt_scores->execute();
        $scores_count = $stmt_scores->rowCount();

        $conn->commit();

        $fix_success = true;
        $fix_message = "$fixed_count cavabın balı düzəldildi! $scores_count tələbənin toplam balı yeniləndi!";

    } catch (Exception $e) {
        $conn->rollBack();
        $fix_message = "Xəta: " . $e->getMessage();
    }
}

// İmtahan məlumatları
$sql_exam = "
    SELECT
        e.id_exam,
        e.date_exam,
        DATE_FORMAT(e.datetime, '%d.%m.%Y %H:%i') AS imtahan_vaxti,
        s.subjectname AS fenn,
        sg.group_number AS qrup
    FROM exams e
    INNER JOIN subjects s ON e.id_subject = s.id_subject
    INNER JOIN student_group sg ON e.id_student_group = sg.id_student_group
    WHERE e.id_exam = :exam_id
";
$stmt_exam = $conn->prepare($sql_exam);
$stmt_exam->bindParam(':exam_id', $exam_id);
$stmt_exam->execute();
$exam = $stmt_exam->fetch(PDO::FETCH_ASSOC);

// ƏSAS SORĞU - Tələbələr, Suallar, Cavablar, Ballar
$sql = "
    SELECT
        a.id_answer,
        u.id_users AS telebe_id,
        u.f_name AS telebe_adi,
        sg.group_number AS qrup,

        qr.id_question_text AS sual_no,
        LEFT(qr.question_text, 100) AS sual_metni,
        qr.question_score AS sual_maksimum_bal,

        qt.quest_type_name AS sual_tipi,
        qf.file_type AS fayl_tipi,

        a.user_answer AS telebe_cavabi,
        a.correct_var AS duzgun_cavab,
        a.is_correct AS duzgundur,
        a.score_earned AS telebe_alinan_bal

    FROM answers a
    INNER JOIN users u ON a.user_id = u.id_users
    INNER JOIN student_group sg ON u.group_id = sg.id_student_group
    INNER JOIN question_read qr ON a.id_questions = qr.id_question_text
    INNER JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
    INNER JOIN question_files qf ON qr.id_read_quest_file = qf.id_read_quest_file

    WHERE a.exam_id = :exam_id

    ORDER BY u.f_name, qr.id_question_text
";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':exam_id', $exam_id);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tələbələrə görə qruplaşdır
$students = [];
foreach ($results as $row) {
    $students[$row['telebe_adi']][] = $row;
}

// PROBLEMLƏRI YOXLA (düzgün cavab verib amma tam bal almayan)
$sql_problems = "
    SELECT COUNT(*) as problem_count
    FROM answers a
    INNER JOIN question_read qr ON a.id_questions = qr.id_question_text
    WHERE
        a.exam_id = :exam_id
        AND a.is_correct = 1
        AND a.score_earned < qr.question_score
";
$stmt_problems = $conn->prepare($sql_problems);
$stmt_problems->bindParam(':exam_id', $exam_id);
$stmt_problems->execute();
$problem_data = $stmt_problems->fetch(PDO::FETCH_ASSOC);
$problem_count = $problem_data['problem_count'];

// Son imtahanlar
$sql_exams = "
    SELECT
        e.id_exam,
        e.date_exam,
        s.subjectname,
        sg.group_number,
        COUNT(DISTINCT a.user_id) AS telebe_sayi
    FROM exams e
    INNER JOIN subjects s ON e.id_subject = s.id_subject
    INNER JOIN student_group sg ON e.id_student_group = sg.id_student_group
    LEFT JOIN answers a ON e.id_exam = a.exam_id
    GROUP BY e.id_exam, e.date_exam, s.subjectname, sg.group_number
    ORDER BY e.id_exam DESC
    LIMIT 50
";
$stmt_exams = $conn->prepare($sql_exams);
$stmt_exams->execute();
$exams = $stmt_exams->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İmtahan Nəticələri - Sadə Görünüş</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .correct-row { background-color: #d4edda !important; }
        .wrong-row { background-color: #f8d7da !important; }
        .student-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0 10px 0;
        }
        .score-badge {
            font-size: 1.1rem;
            padding: 8px 15px;
        }
        @media print {
            body { background: white; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="text-center text-white mb-4 no-print">
        <h1><i class="fas fa-clipboard-check"></i> İmtahan Nəticələri</h1>
        <p>Suallar üzrə detallı nəticələr</p>
    </div>

    <!-- İmtahan Seçici -->
    <div class="card mb-4 no-print">
        <div class="card-header bg-primary text-white">
            <h5><i class="fas fa-list"></i> İmtahan Seçin</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($exams as $e): ?>
                <div class="col-md-4 mb-2">
                    <a href="?exam_id=<?= $e['id_exam'] ?>"
                       class="btn <?= $e['id_exam'] == $exam_id ? 'btn-success' : 'btn-outline-primary' ?> w-100 text-start">
                        <strong>#<?= $e['id_exam'] ?></strong> -
                        <?= htmlspecialchars($e['subjectname']) ?>
                        <br>
                        <small>Qrup: <?= htmlspecialchars($e['qrup']) ?> | <?= $e['telebe_sayi'] ?> tələbə</small>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if ($exam): ?>

    <!-- İmtahan Info -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0">
                <i class="fas fa-graduation-cap"></i>
                İmtahan #<?= $exam['id_exam'] ?> - <?= htmlspecialchars($exam['fenn']) ?>
            </h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <strong>Qrup:</strong> <?= htmlspecialchars($exam['qrup']) ?>
                </div>
                <div class="col-md-4">
                    <strong>Tarix:</strong> <?= $exam['imtahan_vaxti'] ?>
                </div>
                <div class="col-md-4 text-end no-print">
                    <button onclick="window.print()" class="btn btn-sm btn-primary">
                        <i class="fas fa-print"></i> Çap Et
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- AVTOMATIK DÜZƏLDİŞ BİLDİRİŞİ -->
    <?php if ($fix_success): ?>
    <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
        <h5><i class="fas fa-check-circle"></i> Uğurlu Düzəldiş!</h5>
        <p class="mb-0"><?= $fix_message ?></p>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php elseif ($auto_fix && !$fix_success): ?>
    <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
        <h5><i class="fas fa-exclamation-triangle"></i> Xəta!</h5>
        <p class="mb-0"><?= $fix_message ?></p>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- PROBLEM XƏBƏRDARLIQ və AVTOMATIK DÜZƏLDİŞ DÜYMƏSI -->
    <?php if ($problem_count > 0 && !$auto_fix): ?>
    <div class="alert alert-warning no-print" role="alert">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5><i class="fas fa-exclamation-triangle"></i> Bal Uyğunsuzluğu Tapıldı!</h5>
                <p class="mb-0">
                    <strong><?= $problem_count ?></strong> cavab düzgündür, amma tam bal almayıb.
                    <br>
                    <small class="text-muted">Avtomatik düzəldiş ilə bu problemlər həll ediləcək.</small>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <a href="?exam_id=<?= $exam_id ?>&auto_fix=1"
                   class="btn btn-danger btn-lg"
                   onclick="return confirm('<?= $problem_count ?> cavabın balı avtomatik düzəldiləcək. Davam edək?')">
                    <i class="fas fa-magic"></i> AVTOMATIK DÜZƏLDİŞ
                </a>
            </div>
        </div>
    </div>
    <?php elseif ($problem_count == 0 && !$auto_fix): ?>
    <div class="alert alert-success no-print" role="alert">
        <h5><i class="fas fa-check-circle"></i> Heç bir problem tapılmadı!</h5>
        <p class="mb-0">Bütün düzgün cavablar tam bal alıb. Sistem qaydasındadır.</p>
    </div>
    <?php endif; ?>

    <!-- Nəticələr -->
    <?php foreach ($students as $telebe_adi => $answers):
        // Tələbənin ümumi balını hesabla
        $umumi_alinan = 0;
        $umumi_maksimum = 0;
        $duzgun_sayi = 0;
        foreach ($answers as $ans) {
            $umumi_alinan += $ans['telebe_alinan_bal'];
            $umumi_maksimum += $ans['sual_maksimum_bal'];
            if ($ans['duzgundur']) $duzgun_sayi++;
        }
        $faiz = round(($umumi_alinan / $umumi_maksimum) * 100, 1);
    ?>

    <div class="card mb-4">
        <!-- Tələbə Header -->
        <div class="student-header">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h5 class="mb-0">
                        <i class="fas fa-user"></i>
                        <?= htmlspecialchars($telebe_adi) ?>
                    </h5>
                    <small>Qrup: <?= htmlspecialchars($answers[0]['qrup']) ?></small>
                </div>
                <div class="col-md-8 text-end">
                    <span class="badge bg-light text-dark score-badge">
                        Düzgün: <?= $duzgun_sayi ?> / <?= count($answers) ?>
                    </span>
                    <span class="badge bg-warning text-dark score-badge">
                        Bal: <?= $umumi_alinan ?> / <?= $umumi_maksimum ?>
                    </span>
                    <span class="badge bg-<?= $faiz >= 80 ? 'success' : ($faiz >= 60 ? 'warning' : 'danger') ?> score-badge">
                        <?= $faiz ?>%
                    </span>
                </div>
            </div>
        </div>

        <!-- Suallar Cədvəli -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 60px;" class="text-center">Sual No</th>
                            <th style="width: 120px;">Tip</th>
                            <th>Sual</th>
                            <th style="width: 150px;">Tələbə Cavabı</th>
                            <th style="width: 150px;">Düzgün Cavab</th>
                            <th style="width: 80px;" class="text-center">Status</th>
                            <th style="width: 120px;" class="text-center">Bal</th>
                            <th style="width: 90px;" class="text-center no-print">Edit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($answers as $ans): ?>
                        <tr class="<?= $ans['duzgundur'] ? 'correct-row' : 'wrong-row' ?>">
                            <td class="text-center">
                                <strong>#<?= $ans['sual_no'] ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    <?php
                                    if ($ans['sual_tipi'] == 'Çoxseçimli sual') echo 'Multiple';
                                    elseif ($ans['sual_tipi'] == 'Uyğunlaşdırma') echo 'Matching';
                                    else echo 'Open';
                                    ?>
                                </span>
                                <br>
                                <small>
                                    <?php if ($ans['fayl_tipi'] == 'reading'): ?>
                                        <i class="fas fa-book"></i> Reading
                                    <?php else: ?>
                                        <i class="fas fa-headphones"></i> Listening
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td>
                                <small><?= htmlspecialchars($ans['sual_metni']) ?></small>
                            </td>
                            <td>
                                <code><?= htmlspecialchars(substr($ans['telebe_cavabi'], 0, 50)) ?></code>
                            </td>
                            <td>
                                <code><?= htmlspecialchars(substr($ans['duzgun_cavab'], 0, 50)) ?></code>
                            </td>
                            <td class="text-center">
                                <?php if ($ans['duzgundur']): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check"></i> Düzgün
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger">
                                        <i class="fas fa-times"></i> Səhv
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center score-cell-<?= $ans['id_answer'] ?>">
                                <strong style="font-size: 1.2rem; color: <?= $ans['duzgundur'] ? '#28a745' : '#dc3545' ?>;">
                                    <?= $ans['telebe_alinan_bal'] ?>
                                </strong>
                                <small class="text-muted"> / <?= $ans['sual_maksimum_bal'] ?></small>
                            </td>
                            <td class="text-center no-print">
                                <button class="btn btn-sm btn-warning edit-score-btn"
                                        data-id="<?= $ans['id_answer'] ?>"
                                        data-current="<?= $ans['telebe_alinan_bal'] ?>"
                                        data-max="<?= $ans['sual_maksimum_bal'] ?>"
                                        data-student="<?= htmlspecialchars($telebe_adi) ?>"
                                        data-question="<?= $ans['sual_no'] ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-secondary">
                        <tr>
                            <td colspan="7" class="text-end"><strong>ÜMUMI:</strong></td>
                            <td class="text-center">
                                <strong style="font-size: 1.3rem;">
                                    <?= $umumi_alinan ?>
                                </strong>
                                <small class="text-muted"> / <?= $umumi_maksimum ?></small>
                                <br>
                                <span class="badge bg-<?= $faiz >= 80 ? 'success' : ($faiz >= 60 ? 'warning' : 'danger') ?>">
                                    <?= $faiz ?>%
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <?php endforeach; ?>

    <?php endif; ?>

</div>

<!-- EDIT SCORE MODAL -->
<div class="modal fade" id="editScoreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> Balı Dəyişdir
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label"><strong>Tələbə:</strong></label>
                        <p id="modal-student" class="text-muted mb-0"></p>
                    </div>
                    <div class="col-6">
                        <label class="form-label"><strong>Sual:</strong></label>
                        <p id="modal-question" class="text-muted mb-0"></p>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><strong>Maksimum Bal:</strong></label>
                    <p id="modal-max" class="text-primary mb-0 h5"></p>
                </div>
                <div class="mb-3">
                    <label for="modal-new-score" class="form-label"><strong>Yeni Bal:</strong></label>
                    <input type="number"
                           class="form-control form-control-lg text-center"
                           id="modal-new-score"
                           step="0.01"
                           min="0"
                           placeholder="Balı daxil edin">
                    <small class="text-muted">Hazırki: <strong id="modal-current"></strong></small>
                </div>
                <div id="modal-message" class="alert" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Ləğv et
                </button>
                <button type="button" class="btn btn-success btn-lg" id="save-score-btn">
                    <i class="fas fa-save"></i> YADDA SAXLA
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
    const editModal = new bootstrap.Modal(document.getElementById('editScoreModal'));

    // EDIT düyməsinə klik
    $('.edit-score-btn').on('click', function() {
        currentAnswerId = $(this).data('id');
        const currentScore = $(this).data('current');
        currentMaxScore = parseFloat($(this).data('max'));
        const student = $(this).data('student');
        const question = $(this).data('question');

        // Modal məlumatları
        $('#modal-student').text(student);
        $('#modal-question').text('#' + question);
        $('#modal-max').text(currentMaxScore);
        $('#modal-current').text(currentScore);
        $('#modal-new-score').val(currentScore).attr('max', currentMaxScore).focus();
        $('#modal-message').hide();

        editModal.show();
    });

    // YADDA SAXLA
    $('#save-score-btn').on('click', function() {
        const newScore = parseFloat($('#modal-new-score').val());

        // Validasiya
        if (isNaN(newScore) || newScore < 0) {
            showMessage('❌ Xəta: Düzgün bal daxil edin!', 'danger');
            return;
        }

        if (newScore > currentMaxScore) {
            showMessage('❌ Bal maksimumdan çox ola bilməz! (Max: ' + currentMaxScore + ')', 'danger');
            return;
        }

        // Yenilə
        $.ajax({
            url: 'edit_score_handler.php',
            type: 'POST',
            data: {
                answer_id: currentAnswerId,
                new_score: newScore
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showMessage('✅ Bal uğurla yeniləndi!', 'success');

                    // Səhifədə balı yenilə
                    $('.score-cell-' + currentAnswerId + ' strong').text(newScore);

                    // 1 saniyə sonra səhifəni yenilə
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage('❌ ' + response.message, 'danger');
                }
            },
            error: function() {
                showMessage('❌ Server xətası!', 'danger');
            }
        });
    });

    function showMessage(msg, type) {
        $('#modal-message')
            .removeClass('alert-success alert-danger')
            .addClass('alert-' + type)
            .html(msg)
            .show();
    }

    // Enter ilə yadda saxla
    $('#modal-new-score').on('keypress', function(e) {
        if (e.which === 13) {
            $('#save-score-btn').click();
        }
    });
});
</script>
</body>
</html>
