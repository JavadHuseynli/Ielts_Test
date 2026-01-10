<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db.php';
$database = new Database();
$conn = $database->getConnection();

?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bal Problemlərini Yoxla</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding: 20px; }
        .problem-card { margin: 20px 0; }
        .alert-custom { font-size: 1.1rem; }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header bg-warning text-dark">
            <h3><i class="fas fa-search"></i> Bal Problemləri Yoxlanışı</h3>
            <p class="mb-0">Düzgün cavab verib amma tam bal almayan tələbələr</p>
        </div>
        <div class="card-body">

            <?php
            // BÜTÜN İMTAHANLAR ÜZRƏ PROBLEM SAYINI TAP
            $sql_all_problems = "
                SELECT
                    a.exam_id,
                    e.date_exam,
                    s.subjectname AS fenn,
                    sg.group_number AS qrup,
                    COUNT(*) AS problem_sayi,
                    SUM(qr.question_score - a.score_earned) AS itmiş_bal_cəmi
                FROM answers a
                INNER JOIN question_read qr ON a.id_questions = qr.id_question_text
                INNER JOIN exams e ON a.exam_id = e.id_exam
                INNER JOIN subjects s ON e.id_subject = s.id_subject
                INNER JOIN student_group sg ON e.id_student_group = sg.id_student_group
                WHERE
                    a.is_correct = 1
                    AND a.score_earned < qr.question_score
                GROUP BY a.exam_id, e.date_exam, s.subjectname, sg.group_number
                ORDER BY a.exam_id DESC
                LIMIT 50
            ";

            $stmt = $conn->prepare($sql_all_problems);
            $stmt->execute();
            $exam_problems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $total_problems = 0;
            $total_lost_points = 0;
            foreach ($exam_problems as $ep) {
                $total_problems += $ep['problem_sayi'];
                $total_lost_points += $ep['itmiş_bal_cəmi'];
            }
            ?>

            <!-- ÜMUMI STATİSTİKA -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="alert alert-danger alert-custom">
                        <h5><strong>TOPLAM PROBLEM:</strong></h5>
                        <h2 class="mb-0"><?= number_format($total_problems) ?> cavab</h2>
                        <small>Düzgün cavab verib amma tam bal almayan</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-warning alert-custom">
                        <h5><strong>İTMİŞ BAL:</strong></h5>
                        <h2 class="mb-0"><?= number_format($total_lost_points, 2) ?> bal</h2>
                        <small>Tələbələrin almalı olduğu əlavə bal</small>
                    </div>
                </div>
            </div>

            <?php if (count($exam_problems) > 0): ?>

            <!-- İMTAHAN SİYAHISI -->
            <h4 class="mt-4 mb-3">Problemli İmtahanlar:</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>İmtahan ID</th>
                            <th>Tarix</th>
                            <th>Fənn</th>
                            <th>Qrup</th>
                            <th class="text-center">Problem Sayı</th>
                            <th class="text-center">İtmiş Bal</th>
                            <th class="text-center">Əməliyyatlar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exam_problems as $ep): ?>
                        <tr>
                            <td><strong>#<?= $ep['exam_id'] ?></strong></td>
                            <td><?= htmlspecialchars($ep['date_exam']) ?></td>
                            <td><?= htmlspecialchars($ep['fenn']) ?></td>
                            <td><?= htmlspecialchars($ep['qrup']) ?></td>
                            <td class="text-center">
                                <span class="badge bg-danger" style="font-size: 1rem;">
                                    <?= $ep['problem_sayi'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-warning text-dark" style="font-size: 1rem;">
                                    <?= number_format($ep['itmiş_bal_cəmi'], 2) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="fix_scores.php?exam_id=<?= $ep['exam_id'] ?>"
                                   class="btn btn-sm btn-primary">
                                    Detallı Bax
                                </a>
                                <a href="fix_scores.php?exam_id=<?= $ep['exam_id'] ?>&fix=1"
                                   class="btn btn-sm btn-success"
                                   onclick="return confirm('Bu imtahanın ballarını düzəltmək istəyirsiniz?')">
                                    Düzəlt
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- NÜMUNƏ PROBLEMLƏR -->
            <hr class="my-4">
            <h4 class="mb-3">Nümunə Problemlər (İlk 20):</h4>
            <?php
            $sql_sample = "
                SELECT
                    a.id_answer,
                    a.exam_id,
                    u.f_name AS telebe_adi,
                    qr.id_question_text AS sual_no,
                    LEFT(qr.question_text, 80) AS sual_metni,
                    a.user_answer AS telebe_cavabi,
                    a.correct_var AS duzgun_cavab,
                    a.score_earned AS hazirki_bal,
                    qr.question_score AS olmali_bal,
                    (qr.question_score - a.score_earned) AS ferq
                FROM answers a
                INNER JOIN users u ON a.user_id = u.id_users
                INNER JOIN question_read qr ON a.id_questions = qr.id_question_text
                WHERE
                    a.is_correct = 1
                    AND a.score_earned < qr.question_score
                ORDER BY a.exam_id DESC, a.id_answer
                LIMIT 20
            ";

            $stmt_sample = $conn->prepare($sql_sample);
            $stmt_sample->execute();
            $samples = $stmt_sample->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-secondary">
                        <tr>
                            <th>ID</th>
                            <th>İmtahan</th>
                            <th>Tələbə</th>
                            <th>Sual</th>
                            <th>Cavab</th>
                            <th>Düzgün</th>
                            <th>Hazırki Bal</th>
                            <th>Olmalı Bal</th>
                            <th>Fərq</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($samples as $s): ?>
                        <tr style="background-color: #fff3cd;">
                            <td><?= $s['id_answer'] ?></td>
                            <td><strong>#<?= $s['exam_id'] ?></strong></td>
                            <td><?= htmlspecialchars($s['telebe_adi']) ?></td>
                            <td><small><?= htmlspecialchars($s['sual_metni']) ?></small></td>
                            <td><code><?= htmlspecialchars($s['telebe_cavabi']) ?></code></td>
                            <td><code><?= htmlspecialchars($s['duzgun_cavab']) ?></code></td>
                            <td class="text-center">
                                <span class="badge bg-warning text-dark"><?= $s['hazirki_bal'] ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success"><?= $s['olmali_bal'] ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-danger">-<?= $s['ferq'] ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- ƏMƏLIYYAT DÜYMƏLƏRİ -->
            <div class="alert alert-info mt-4">
                <h5><i class="fas fa-tools"></i> Növbəti Addım:</h5>
                <p>Problemləri detallı görmək və düzəltmək üçün <strong>fix_scores.php</strong> səhifəsini istifadə edin.</p>
                <a href="fix_scores.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-wrench"></i> Düzəldiş Səhifəsinə Keç
                </a>
            </div>

            <?php else: ?>

            <div class="alert alert-success">
                <h4><i class="fas fa-check-circle"></i> Heç bir problem tapılmadı!</h4>
                <p class="mb-0">Bütün düzgün cavablar tam bal alıb. Sistem qaydasındadır.</p>
            </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
