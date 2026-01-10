<?php
// Veritabanı bağlantısı
require_once 'includes/db.php';

$database = new Database();
$conn = $database->getConnection();

// İmtahan ID-ni GET parametrindən al
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 318;
$view_type = isset($_GET['view']) ? $_GET['view'] : 'simple';

// Son imtahanları çək
$sql_exams = "
    SELECT
        e.id_exam,
        e.date_exam,
        DATE_FORMAT(e.datetime, '%Y-%m-%d %H:%i') AS imtahan_vaxti,
        e.status,
        s.subjectname AS fenn,
        sg.group_number AS qrup,
        COUNT(DISTINCT a.user_id) AS telebe_sayi,
        COUNT(DISTINCT a.id_answer) AS cavab_sayi
    FROM exams e
    INNER JOIN subjects s ON e.id_subject = s.id_subject
    INNER JOIN student_group sg ON e.id_student_group = sg.id_student_group
    LEFT JOIN answers a ON e.id_exam = a.exam_id
    WHERE e.id_exam >= 300
    GROUP BY e.id_exam, e.date_exam, e.datetime, e.status, s.subjectname, sg.group_number
    ORDER BY e.id_exam DESC
    LIMIT 30
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
    <title>SQL Test - İmtahan Nəticələri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .main-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }
        .exam-selector {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 15px;
        }
        .stat-card h3 { margin: 0; font-size: 2.5rem; }
        .stat-card p { margin: 5px 0 0 0; opacity: 0.9; }
        .student-row:hover { background-color: #f8f9fa; }
        .badge-correct { background-color: #28a745; }
        .badge-wrong { background-color: #dc3545; }
        .score-bar {
            height: 25px;
            background: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            position: relative;
        }
        .score-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
            transition: width 0.3s ease;
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        table { font-size: 0.9rem; }
        .question-detail {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .rank-badge {
            display: inline-block;
            width: 35px;
            height: 35px;
            line-height: 35px;
            text-align: center;
            border-radius: 50%;
            font-weight: bold;
            color: white;
        }
        .rank-1 { background: linear-gradient(135deg, #FFD700, #FFA500); }
        .rank-2 { background: linear-gradient(135deg, #C0C0C0, #808080); }
        .rank-3 { background: linear-gradient(135deg, #CD7F32, #8B4513); }
        .rank-other { background: linear-gradient(135deg, #6c757d, #495057); }

        /* Matrix cədvəl üçün xüsusi stillər */
        .table-responsive {
            max-height: 80vh;
            overflow-y: auto;
        }
        .sticky-top {
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .table-bordered td {
            border: 1px solid #dee2e6 !important;
        }
        .table-bordered th {
            border: 2px solid #495057 !important;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="text-center text-white mb-4">
        <h1><i class="fas fa-chart-line"></i> İmtahan Nəticələri - SQL Test</h1>
        <p>Veritabanı analiz və test sistemi</p>
    </div>

    <!-- İmtahan Seçici -->
    <div class="main-card p-4 mb-4">
        <h4><i class="fas fa-clipboard-list"></i> İmtahan Seçin</h4>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tarix</th>
                        <th>Vaxt</th>
                        <th>Fənn</th>
                        <th>Qrup</th>
                        <th>Status</th>
                        <th>Tələbə</th>
                        <th>Cavab</th>
                        <th>Əməliyyat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exams as $exam): ?>
                    <tr class="<?= $exam['id_exam'] == $exam_id ? 'table-primary' : '' ?>">
                        <td><strong>#<?= $exam['id_exam'] ?></strong></td>
                        <td><?= date('d.m.Y', strtotime($exam['date_exam'])) ?></td>
                        <td><?= date('H:i', strtotime($exam['imtahan_vaxti'])) ?></td>
                        <td><?= htmlspecialchars($exam['fenn']) ?></td>
                        <td><span class="badge bg-info"><?= htmlspecialchars($exam['qrup']) ?></span></td>
                        <td>
                            <?php if ($exam['status'] == 'completed'): ?>
                                <span class="badge bg-success">Tamamlandı</span>
                            <?php elseif ($exam['status'] == 'in_progress'): ?>
                                <span class="badge bg-warning">Davam edir</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Gözləyir</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $exam['telebe_sayi'] ?></td>
                        <td><?= $exam['cavab_sayi'] ?></td>
                        <td>
                            <a href="?exam_id=<?= $exam['id_exam'] ?>&view=summary" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> Bax
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($exam_id): ?>

    <!-- İmtahan İnfo -->
    <?php
    $sql_info = "
        SELECT
            e.id_exam,
            e.date_exam,
            DATE_FORMAT(e.datetime, '%d.%m.%Y %H:%i') AS imtahan_vaxti,
            e.status,
            s.subjectname AS fenn,
            s.timer AS muddet,
            sg.group_number AS qrup
        FROM exams e
        INNER JOIN subjects s ON e.id_subject = s.id_subject
        INNER JOIN student_group sg ON e.id_student_group = sg.id_student_group
        WHERE e.id_exam = :exam_id
    ";
    $stmt_info = $conn->prepare($sql_info);
    $stmt_info->bindParam(':exam_id', $exam_id);
    $stmt_info->execute();
    $exam_info = $stmt_info->fetch(PDO::FETCH_ASSOC);
    ?>

    <div class="exam-selector">
        <div class="row">
            <div class="col-md-8">
                <h3><i class="fas fa-graduation-cap"></i> İmtahan #<?= $exam_info['id_exam'] ?></h3>
                <p class="mb-1"><strong>Fənn:</strong> <?= htmlspecialchars($exam_info['fenn']) ?></p>
                <p class="mb-1"><strong>Qrup:</strong> <?= htmlspecialchars($exam_info['qrup']) ?></p>
                <p class="mb-0"><strong>Tarix:</strong> <?= $exam_info['imtahan_vaxti'] ?> | <strong>Müddət:</strong> <?= $exam_info['muddet'] ?> dəqiqə</p>
            </div>
            <div class="col-md-4 text-end">
                <?php if ($exam_info['status'] == 'completed'): ?>
                    <span class="badge bg-success" style="font-size: 1.2rem; padding: 10px 20px;">
                        <i class="fas fa-check-circle"></i> Tamamlandı
                    </span>
                <?php elseif ($exam_info['status'] == 'in_progress'): ?>
                    <span class="badge bg-warning" style="font-size: 1.2rem; padding: 10px 20px;">
                        <i class="fas fa-clock"></i> Davam edir
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Statistika Kartları -->
    <?php
    $sql_stats = "
        SELECT
            COUNT(DISTINCT a.user_id) AS telebe_sayi,
            COUNT(a.id_answer) AS umumi_cavab,
            SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END) AS duzgun_cavab,
            ROUND(AVG(user_scores.umumi_bal), 2) AS orta_bal,
            MAX(user_scores.umumi_bal) AS en_yuksek,
            MIN(user_scores.umumi_bal) AS en_asagi
        FROM answers a
        LEFT JOIN (
            SELECT user_id, SUM(score_earned) AS umumi_bal
            FROM answers
            WHERE exam_id = :exam_id
            GROUP BY user_id
        ) user_scores ON a.user_id = user_scores.user_id
        WHERE a.exam_id = :exam_id
    ";
    $stmt_stats = $conn->prepare($sql_stats);
    $stmt_stats->bindParam(':exam_id', $exam_id);
    $stmt_stats->execute();
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    ?>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h3><?= $stats['telebe_sayi'] ?></h3>
                <p><i class="fas fa-users"></i> Tələbə Sayı</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h3><?= $stats['umumi_cavab'] ?></h3>
                <p><i class="fas fa-check-circle"></i> Ümumi Cavab</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <h3><?= $stats['orta_bal'] ?></h3>
                <p><i class="fas fa-chart-bar"></i> Orta Bal</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <h3><?= $stats['en_yuksek'] ?></h3>
                <p><i class="fas fa-trophy"></i> Ən Yüksək Bal</p>
            </div>
        </div>
    </div>

    <!-- Görünüş Naviqasiyası -->
    <ul class="nav nav-pills mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link <?= $view_type == 'summary' ? 'active' : '' ?>" href="?exam_id=<?= $exam_id ?>&view=summary">
                <i class="fas fa-list"></i> Xülasə
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $view_type == 'simple' ? 'active' : '' ?>" href="?exam_id=<?= $exam_id ?>&view=simple">
                <i class="fas fa-list-alt"></i> Suallar-Ballar (Sadə)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $view_type == 'matrix' ? 'active' : '' ?>" href="?exam_id=<?= $exam_id ?>&view=matrix">
                <i class="fas fa-th"></i> Matrix Görünüş
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $view_type == 'detailed' ? 'active' : '' ?>" href="?exam_id=<?= $exam_id ?>&view=detailed">
                <i class="fas fa-table"></i> Detallı
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $view_type == 'questions' ? 'active' : '' ?>" href="?exam_id=<?= $exam_id ?>&view=questions">
                <i class="fas fa-question-circle"></i> Sual-Cavab
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $view_type == 'analysis' ? 'active' : '' ?>" href="?exam_id=<?= $exam_id ?>&view=analysis">
                <i class="fas fa-chart-pie"></i> Analiz
            </a>
        </li>
    </ul>

    <!-- Məzmun -->
    <div class="main-card p-4">
        <?php if ($view_type == 'simple'): ?>
            <!-- SADƏ GÖRÜNÜŞü - SUALLAR VƏ BALLAR -->
            <?php
            $sql_simple = "
                SELECT
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
            $stmt_simple = $conn->prepare($sql_simple);
            $stmt_simple->bindParam(':exam_id', $exam_id);
            $stmt_simple->execute();
            $simple_results = $stmt_simple->fetchAll(PDO::FETCH_ASSOC);

            // Tələbələrə görə qruplaşdır
            $grouped_students = [];
            foreach ($simple_results as $row) {
                $grouped_students[$row['telebe_adi']][] = $row;
            }
            ?>

            <h4><i class="fas fa-list-alt"></i> Suallar üzrə Ballar - Sadə Görünüş</h4>
            <p class="text-muted">Hər tələbənin hər sual üzrə aldığı bal və cavablar</p>

            <?php foreach ($grouped_students as $telebe_adi => $answers):
                // Ümumi balı hesabla
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

            <div class="card mb-3">
                <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h5 class="mb-0"><i class="fas fa-user"></i> <?= htmlspecialchars($telebe_adi) ?></h5>
                            <small>Qrup: <?= htmlspecialchars($answers[0]['qrup']) ?></small>
                        </div>
                        <div class="col-md-8 text-end">
                            <span class="badge bg-light text-dark" style="font-size: 1rem; padding: 8px 12px;">
                                Düzgün: <?= $duzgun_sayi ?> / <?= count($answers) ?>
                            </span>
                            <span class="badge bg-warning text-dark" style="font-size: 1rem; padding: 8px 12px;">
                                Bal: <?= $umumi_alinan ?> / <?= $umumi_maksimum ?>
                            </span>
                            <span class="badge bg-<?= $faiz >= 80 ? 'success' : ($faiz >= 60 ? 'warning' : 'danger') ?>" style="font-size: 1rem; padding: 8px 12px;">
                                <?= $faiz ?>%
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0" style="font-size: 0.9rem;">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 60px;" class="text-center">Sual</th>
                                    <th style="width: 100px;">Tip</th>
                                    <th>Sual Mətni</th>
                                    <th style="width: 120px;">Tələbə Cavabı</th>
                                    <th style="width: 120px;">Düzgün Cavab</th>
                                    <th style="width: 80px;" class="text-center">Status</th>
                                    <th style="width: 100px;" class="text-center">Bal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($answers as $ans): ?>
                                <tr style="background-color: <?= $ans['duzgundur'] ? '#d4edda' : '#f8d7da' ?>;">
                                    <td class="text-center"><strong>#<?= $ans['sual_no'] ?></strong></td>
                                    <td>
                                        <?php
                                        if ($ans['sual_tipi'] == 'Çoxseçimli sual') {
                                            echo '<span class="badge bg-primary">Multiple</span>';
                                        } elseif ($ans['sual_tipi'] == 'Uyğunlaşdırma') {
                                            echo '<span class="badge bg-success">Matching</span>';
                                        } else {
                                            echo '<span class="badge bg-warning">Open</span>';
                                        }
                                        ?>
                                        <br>
                                        <small>
                                            <?php if ($ans['fayl_tipi'] == 'reading'): ?>
                                                <i class="fas fa-book"></i> R
                                            <?php else: ?>
                                                <i class="fas fa-headphones"></i> L
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td><small><?= htmlspecialchars($ans['sual_metni']) ?></small></td>
                                    <td><code style="font-size: 0.8rem;"><?= htmlspecialchars(substr($ans['telebe_cavabi'], 0, 20)) ?></code></td>
                                    <td><code style="font-size: 0.8rem;"><?= htmlspecialchars(substr($ans['duzgun_cavab'], 0, 20)) ?></code></td>
                                    <td class="text-center">
                                        <?php if ($ans['duzgundur']): ?>
                                            <span class="badge bg-success"><i class="fas fa-check"></i></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><i class="fas fa-times"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <strong style="font-size: 1.1rem; color: <?= $ans['duzgundur'] ? '#28a745' : '#dc3545' ?>;">
                                            <?= $ans['telebe_alinan_bal'] ?>
                                        </strong>
                                        <small class="text-muted"> / <?= $ans['sual_maksimum_bal'] ?></small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr>
                                    <td colspan="6" class="text-end"><strong>ÜMUMI:</strong></td>
                                    <td class="text-center">
                                        <strong style="font-size: 1.2rem;"><?= $umumi_alinan ?></strong>
                                        <small> / <?= $umumi_maksimum ?></small>
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

        <?php elseif ($view_type == 'matrix'): ?>
            <!-- SUALLAR ÜZRƏ BALLAR - MATRIX GÖRÜNÜŞü -->
            <?php
            // Bütün sualları çək
            $sql_all_questions = "
                SELECT DISTINCT
                    qr.id_question_text,
                    qr.question_score,
                    qt.quest_type_name,
                    qf.file_type
                FROM answers a
                INNER JOIN question_read qr ON a.id_questions = qr.id_question_text
                INNER JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
                INNER JOIN question_files qf ON qr.id_read_quest_file = qf.id_read_quest_file
                WHERE a.exam_id = :exam_id
                ORDER BY qr.id_question_text
            ";
            $stmt_questions = $conn->prepare($sql_all_questions);
            $stmt_questions->bindParam(':exam_id', $exam_id);
            $stmt_questions->execute();
            $all_questions = $stmt_questions->fetchAll(PDO::FETCH_ASSOC);

            // Tələbələri və onların cavablarını çək
            $sql_matrix = "
                SELECT
                    u.id_users,
                    u.f_name AS telebe_adi,
                    sg.group_number AS qrup,
                    a.id_questions AS sual_id,
                    a.score_earned,
                    a.is_correct,
                    qr.question_score
                FROM users u
                INNER JOIN student_group sg ON u.group_id = sg.id_student_group
                INNER JOIN answers a ON u.id_users = a.user_id
                INNER JOIN question_read qr ON a.id_questions = qr.id_question_text
                WHERE a.exam_id = :exam_id
                ORDER BY u.f_name, a.id_questions
            ";
            $stmt_matrix = $conn->prepare($sql_matrix);
            $stmt_matrix->bindParam(':exam_id', $exam_id);
            $stmt_matrix->execute();
            $matrix_data = $stmt_matrix->fetchAll(PDO::FETCH_ASSOC);

            // Məlumatları strukturlaşdır
            $students = [];
            $student_totals = [];
            foreach ($matrix_data as $row) {
                $student_key = $row['id_users'];
                if (!isset($students[$student_key])) {
                    $students[$student_key] = [
                        'name' => $row['telebe_adi'],
                        'group' => $row['qrup'],
                        'scores' => []
                    ];
                    $student_totals[$student_key] = ['earned' => 0, 'max' => 0];
                }
                $students[$student_key]['scores'][$row['sual_id']] = [
                    'earned' => $row['score_earned'],
                    'max' => $row['question_score'],
                    'correct' => $row['is_correct']
                ];
                $student_totals[$student_key]['earned'] += $row['score_earned'];
                $student_totals[$student_key]['max'] += $row['question_score'];
            }

            // Ümumi bal üzrə sırala
            uasort($students, function($a, $b) use ($student_totals) {
                $a_key = array_search($a, $students);
                $b_key = array_search($b, $students);
                return $student_totals[$b_key]['earned'] <=> $student_totals[$a_key]['earned'];
            });
            ?>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4><i class="fas fa-th"></i> Suallar üzrə Ballar - Matrix Görünüş</h4>
                    <p class="text-muted mb-0">Hər tələbənin hər sual üzrə aldığı ballar</p>
                </div>
                <div>
                    <button onclick="exportToExcel()" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Excel-ə Yüklə
                    </button>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print"></i> Çap Et
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="matrix-table" class="table table-bordered table-sm" style="font-size: 0.85rem;">
                    <thead class="table-dark sticky-top">
                        <tr>
                            <th rowspan="2" class="align-middle" style="min-width: 150px;">Tələbə</th>
                            <th rowspan="2" class="align-middle text-center">Qrup</th>
                            <?php foreach ($all_questions as $q): ?>
                                <th class="text-center" style="min-width: 50px; writing-mode: vertical-rl; transform: rotate(180deg); height: 100px;">
                                    <div style="padding: 5px;">
                                        S<?= $q['id_question_text'] ?>
                                        <br>
                                        <span class="badge bg-secondary"><?= $q['question_score'] ?></span>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                            <th rowspan="2" class="align-middle text-center bg-primary text-white" style="min-width: 80px;">TOPLAM</th>
                            <th rowspan="2" class="align-middle text-center bg-info text-white" style="min-width: 60px;">%</th>
                        </tr>
                        <tr>
                            <?php foreach ($all_questions as $q): ?>
                                <th class="text-center" style="font-size: 0.7rem;">
                                    <?php if ($q['quest_type_name'] == 'Çoxseçimli sual'): ?>
                                        <span class="badge bg-primary" style="font-size: 0.6rem;">M</span>
                                    <?php elseif ($q['quest_type_name'] == 'Uyğunlaşdırma'): ?>
                                        <span class="badge bg-success" style="font-size: 0.6rem;">U</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning" style="font-size: 0.6rem;">A</span>
                                    <?php endif; ?>

                                    <?php if ($q['file_type'] == 'reading'): ?>
                                        <i class="fas fa-book" style="font-size: 0.6rem;" title="Reading"></i>
                                    <?php else: ?>
                                        <i class="fas fa-headphones" style="font-size: 0.6rem;" title="Listening"></i>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rank = 1;
                        foreach ($students as $student_id => $student):
                            $total = $student_totals[$student_id];
                            $percentage = round(($total['earned'] / $total['max']) * 100, 1);
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($student['name']) ?></strong></td>
                            <td class="text-center"><span class="badge bg-secondary"><?= htmlspecialchars($student['group']) ?></span></td>
                            <?php foreach ($all_questions as $q): ?>
                                <td class="text-center" style="
                                    <?php
                                    if (isset($student['scores'][$q['id_question_text']])) {
                                        $score = $student['scores'][$q['id_question_text']];
                                        if ($score['correct']) {
                                            echo 'background-color: #d4edda; color: #155724;';
                                        } else {
                                            echo 'background-color: #f8d7da; color: #721c24;';
                                        }
                                    }
                                    ?>
                                ">
                                    <?php
                                    if (isset($student['scores'][$q['id_question_text']])) {
                                        $score = $student['scores'][$q['id_question_text']];
                                        echo '<strong>' . $score['earned'] . '</strong>';
                                        if ($score['earned'] != $score['max']) {
                                            echo '<br><small style="opacity: 0.6;">/' . $score['max'] . '</small>';
                                        }
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                            <td class="text-center bg-light">
                                <strong style="font-size: 1.1rem;"><?= $total['earned'] ?></strong>
                                <small style="opacity: 0.7;">/<?= $total['max'] ?></small>
                            </td>
                            <td class="text-center bg-light">
                                <strong style="color: <?= $percentage >= 80 ? '#28a745' : ($percentage >= 60 ? '#ffc107' : '#dc3545') ?>;">
                                    <?= $percentage ?>%
                                </strong>
                            </td>
                        </tr>
                        <?php
                        $rank++;
                        endforeach;
                        ?>

                        <!-- TOPLAM SƏTIR -->
                        <tr class="table-dark">
                            <td colspan="2" class="text-end"><strong>SUAL ÜZRƏ ORTALAMA:</strong></td>
                            <?php foreach ($all_questions as $q): ?>
                                <td class="text-center">
                                    <?php
                                    $question_total = 0;
                                    $question_count = 0;
                                    foreach ($students as $student) {
                                        if (isset($student['scores'][$q['id_question_text']])) {
                                            $question_total += $student['scores'][$q['id_question_text']]['earned'];
                                            $question_count++;
                                        }
                                    }
                                    $avg = $question_count > 0 ? round($question_total / $question_count, 1) : 0;
                                    echo '<strong>' . $avg . '</strong>';
                                    ?>
                                </td>
                            <?php endforeach; ?>
                            <td class="text-center">
                                <strong><?= round($total['earned'] / count($students), 1) ?></strong>
                            </td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Legend -->
            <div class="mt-3">
                <h6>İşarələr:</h6>
                <span class="badge bg-primary">M</span> = Multiple Choice (Çoxseçimli)
                <span class="badge bg-success ms-2">U</span> = Uyğunlaşdırma
                <span class="badge bg-warning ms-2">A</span> = Açıq cavab
                <span class="ms-3"><i class="fas fa-book"></i></span> = Reading
                <span class="ms-2"><i class="fas fa-headphones"></i></span> = Listening
                <br>
                <div class="mt-2">
                    <span style="background-color: #d4edda; padding: 2px 8px; border-radius: 3px;">Yaşıl</span> = Düzgün cavab
                    <span style="background-color: #f8d7da; padding: 2px 8px; border-radius: 3px; margin-left: 10px;">Qırmızı</span> = Səhv cavab
                </div>
            </div>

        <?php elseif ($view_type == 'summary'): ?>
            <!-- XÜLASƏ GÖRÜNÜŞü -->
            <?php
            $sql_summary = "
                SELECT
                    ROW_NUMBER() OVER (ORDER BY SUM(a.score_earned) DESC) AS sira,
                    u.id_users,
                    u.f_name AS telebe_adi,
                    sg.group_number AS qrup,
                    COUNT(a.id_answer) AS cavablanmis_sual,
                    SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END) AS duzgun,
                    SUM(CASE WHEN a.is_correct = 0 THEN 1 ELSE 0 END) AS sehv,
                    SUM(a.score_earned) AS toplam_bal,
                    SUM(qr.question_score) AS maksimum_bal,
                    ROUND(SUM(a.score_earned) / SUM(qr.question_score) * 100, 1) AS faiz
                FROM answers a
                INNER JOIN users u ON a.user_id = u.id_users
                INNER JOIN student_group sg ON u.group_id = sg.id_student_group
                INNER JOIN question_read qr ON a.id_questions = qr.id_question_text
                WHERE a.exam_id = :exam_id
                GROUP BY u.id_users, u.f_name, sg.group_number
                ORDER BY toplam_bal DESC
            ";
            $stmt_summary = $conn->prepare($sql_summary);
            $stmt_summary->bindParam(':exam_id', $exam_id);
            $stmt_summary->execute();
            $results = $stmt_summary->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <h4><i class="fas fa-trophy"></i> Tələbə Nəticələri - Xülasə</h4>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Sıra</th>
                            <th>Tələbə</th>
                            <th>Qrup</th>
                            <th>Sual Sayı</th>
                            <th>Düzgün</th>
                            <th>Səhv</th>
                            <th>Bal</th>
                            <th>Faiz</th>
                            <th>Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): ?>
                        <tr class="student-row">
                            <td>
                                <?php
                                $rank_class = 'rank-other';
                                if ($row['sira'] == 1) $rank_class = 'rank-1';
                                elseif ($row['sira'] == 2) $rank_class = 'rank-2';
                                elseif ($row['sira'] == 3) $rank_class = 'rank-3';
                                ?>
                                <span class="rank-badge <?= $rank_class ?>"><?= $row['sira'] ?></span>
                            </td>
                            <td><strong><?= htmlspecialchars($row['telebe_adi']) ?></strong></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($row['qrup']) ?></span></td>
                            <td><?= $row['cavablanmis_sual'] ?></td>
                            <td><span class="badge badge-correct"><?= $row['duzgun'] ?></span></td>
                            <td><span class="badge badge-wrong"><?= $row['sehv'] ?></span></td>
                            <td><strong><?= $row['toplam_bal'] ?></strong> / <?= $row['maksimum_bal'] ?></td>
                            <td><strong><?= $row['faiz'] ?>%</strong></td>
                            <td style="width: 200px;">
                                <div class="score-bar">
                                    <div class="score-fill" style="width: <?= $row['faiz'] ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($view_type == 'detailed'): ?>
            <!-- DETALLI GÖRÜNÜŞü -->
            <?php
            $sql_detailed = "
                SELECT
                    u.id_users,
                    u.f_name AS telebe_adi,
                    sg.group_number AS qrup,
                    qr.id_question_text AS sual_no,
                    qt.quest_type_name AS sual_tipi,
                    qr.question_score AS sual_bali,
                    a.user_answer AS telebe_cavabi,
                    a.correct_var AS duzgun_cavab,
                    a.is_correct,
                    a.score_earned AS qazanilan_bal
                FROM answers a
                INNER JOIN users u ON a.user_id = u.id_users
                INNER JOIN student_group sg ON u.group_id = sg.id_student_group
                INNER JOIN question_read qr ON a.id_questions = qr.id_question_text
                INNER JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
                WHERE a.exam_id = :exam_id
                ORDER BY u.f_name, qr.id_question_text
            ";
            $stmt_detailed = $conn->prepare($sql_detailed);
            $stmt_detailed->bindParam(':exam_id', $exam_id);
            $stmt_detailed->execute();
            $detailed_results = $stmt_detailed->fetchAll(PDO::FETCH_ASSOC);

            // Tələbələrə görə qruplaşdır
            $grouped = [];
            foreach ($detailed_results as $row) {
                $grouped[$row['telebe_adi']][] = $row;
            }
            ?>

            <h4><i class="fas fa-list-alt"></i> Detallı Cavablar</h4>

            <?php foreach ($grouped as $telebe => $answers): ?>
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($telebe) ?>
                        <span class="badge bg-light text-dark float-end">
                            Qrup: <?= htmlspecialchars($answers[0]['qrup']) ?>
                        </span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60px;">Sual</th>
                                    <th style="width: 120px;">Tip</th>
                                    <th>Tələbə Cavabı</th>
                                    <th>Düzgün Cavab</th>
                                    <th style="width: 80px;">Status</th>
                                    <th style="width: 80px;">Bal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($answers as $ans): ?>
                                <tr>
                                    <td class="text-center"><strong>#<?= $ans['sual_no'] ?></strong></td>
                                    <td><span class="badge bg-info"><?= htmlspecialchars($ans['sual_tipi']) ?></span></td>
                                    <td><?= htmlspecialchars(substr($ans['telebe_cavabi'], 0, 50)) ?></td>
                                    <td><?= htmlspecialchars(substr($ans['duzgun_cavab'], 0, 50)) ?></td>
                                    <td class="text-center">
                                        <?php if ($ans['is_correct']): ?>
                                            <span class="badge bg-success">✓ Düzgün</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">✗ Səhv</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <strong><?= $ans['qazanilan_bal'] ?></strong> / <?= $ans['sual_bali'] ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        <?php elseif ($view_type == 'questions'): ?>
            <!-- SUAL-CAVAB GÖRÜNÜŞü -->
            <?php
            $sql_questions = "
                SELECT
                    qr.id_question_text AS sual_no,
                    qr.question_text AS sual,
                    qt.quest_type_name AS tip,
                    qr.question_score AS bal,
                    COUNT(a.id_answer) AS cavab_sayi,
                    SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END) AS duzgun_sayi,
                    ROUND(AVG(a.score_earned), 2) AS orta_bal,
                    ROUND(SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(a.id_answer), 1) AS ugur_faizi
                FROM question_read qr
                INNER JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
                LEFT JOIN answers a ON qr.id_question_text = a.id_questions AND a.exam_id = :exam_id
                WHERE qr.id_question_text IN (
                    SELECT DISTINCT id_questions FROM answers WHERE exam_id = :exam_id
                )
                GROUP BY qr.id_question_text, qr.question_text, qt.quest_type_name, qr.question_score
                ORDER BY qr.id_question_text
            ";
            $stmt_questions = $conn->prepare($sql_questions);
            $stmt_questions->bindParam(':exam_id', $exam_id);
            $stmt_questions->execute();
            $questions = $stmt_questions->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <h4><i class="fas fa-question-circle"></i> Sual Statistikası</h4>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 60px;">No</th>
                            <th>Sual</th>
                            <th style="width: 100px;">Tip</th>
                            <th style="width: 80px;">Bal</th>
                            <th style="width: 100px;">Cavab</th>
                            <th style="width: 100px;">Düzgün</th>
                            <th style="width: 100px;">Uğur %</th>
                            <th style="width: 100px;">Orta Bal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $q): ?>
                        <tr>
                            <td class="text-center"><strong>#<?= $q['sual_no'] ?></strong></td>
                            <td><?= htmlspecialchars(substr($q['sual'], 0, 100)) ?><?= strlen($q['sual']) > 100 ? '...' : '' ?></td>
                            <td><span class="badge bg-info"><?= htmlspecialchars($q['tip']) ?></span></td>
                            <td class="text-center"><?= $q['bal'] ?></td>
                            <td class="text-center"><?= $q['cavab_sayi'] ?></td>
                            <td class="text-center"><span class="badge bg-success"><?= $q['duzgun_sayi'] ?></span></td>
                            <td class="text-center"><strong><?= $q['ugur_faizi'] ?>%</strong></td>
                            <td class="text-center"><?= $q['orta_bal'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($view_type == 'analysis'): ?>
            <!-- ANALİZ GÖRÜNÜŞü -->
            <?php
            $sql_analysis = "
                SELECT
                    u.id_users,
                    u.f_name AS telebe_adi,
                    SUM(CASE WHEN qt.question_var = 'multiple' THEN a.score_earned ELSE 0 END) AS multiple_bal,
                    SUM(CASE WHEN qt.question_var = 'multiple' THEN qr.question_score ELSE 0 END) AS multiple_maks,
                    SUM(CASE WHEN qt.question_var = 'matching' THEN a.score_earned ELSE 0 END) AS matching_bal,
                    SUM(CASE WHEN qt.question_var = 'matching' THEN qr.question_score ELSE 0 END) AS matching_maks,
                    SUM(CASE WHEN qf.file_type = 'reading' THEN a.score_earned ELSE 0 END) AS reading_bal,
                    SUM(CASE WHEN qf.file_type = 'reading' THEN qr.question_score ELSE 0 END) AS reading_maks,
                    SUM(CASE WHEN qf.file_type = 'listening' THEN a.score_earned ELSE 0 END) AS listening_bal,
                    SUM(CASE WHEN qf.file_type = 'listening' THEN qr.question_score ELSE 0 END) AS listening_maks,
                    SUM(a.score_earned) AS umumi_bal,
                    SUM(qr.question_score) AS umumi_maks
                FROM answers a
                INNER JOIN users u ON a.user_id = u.id_users
                INNER JOIN question_read qr ON a.id_questions = qr.id_question_text
                INNER JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
                INNER JOIN question_files qf ON qr.id_read_quest_file = qf.id_read_quest_file
                WHERE a.exam_id = :exam_id
                GROUP BY u.id_users, u.f_name
                ORDER BY umumi_bal DESC
            ";
            $stmt_analysis = $conn->prepare($sql_analysis);
            $stmt_analysis->bindParam(':exam_id', $exam_id);
            $stmt_analysis->execute();
            $analysis = $stmt_analysis->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <h4><i class="fas fa-chart-pie"></i> Tip və Kateqoriyaya Görə Analiz</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th rowspan="2">Tələbə</th>
                            <th colspan="2" class="text-center bg-primary">Multiple Choice</th>
                            <th colspan="2" class="text-center bg-info">Matching</th>
                            <th colspan="2" class="text-center bg-success">Reading</th>
                            <th colspan="2" class="text-center bg-warning">Listening</th>
                            <th colspan="2" class="text-center bg-danger">ÜMUMI</th>
                        </tr>
                        <tr>
                            <th class="text-center">Bal</th>
                            <th class="text-center">%</th>
                            <th class="text-center">Bal</th>
                            <th class="text-center">%</th>
                            <th class="text-center">Bal</th>
                            <th class="text-center">%</th>
                            <th class="text-center">Bal</th>
                            <th class="text-center">%</th>
                            <th class="text-center">Bal</th>
                            <th class="text-center">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analysis as $row): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['telebe_adi']) ?></strong></td>
                            <td class="text-center"><?= $row['multiple_bal'] ?>/<?= $row['multiple_maks'] ?></td>
                            <td class="text-center"><?= $row['multiple_maks'] > 0 ? round($row['multiple_bal']/$row['multiple_maks']*100, 1) : 0 ?>%</td>
                            <td class="text-center"><?= $row['matching_bal'] ?>/<?= $row['matching_maks'] ?></td>
                            <td class="text-center"><?= $row['matching_maks'] > 0 ? round($row['matching_bal']/$row['matching_maks']*100, 1) : 0 ?>%</td>
                            <td class="text-center"><?= $row['reading_bal'] ?>/<?= $row['reading_maks'] ?></td>
                            <td class="text-center"><?= $row['reading_maks'] > 0 ? round($row['reading_bal']/$row['reading_maks']*100, 1) : 0 ?>%</td>
                            <td class="text-center"><?= $row['listening_bal'] ?>/<?= $row['listening_maks'] ?></td>
                            <td class="text-center"><?= $row['listening_maks'] > 0 ? round($row['listening_bal']/$row['listening_maks']*100, 1) : 0 ?>%</td>
                            <td class="text-center"><strong><?= $row['umumi_bal'] ?>/<?= $row['umumi_maks'] ?></strong></td>
                            <td class="text-center"><strong><?= round($row['umumi_bal']/$row['umumi_maks']*100, 1) ?>%</strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Excel export funksiyası
function exportToExcel() {
    // Cədvəli tap
    var table = document.querySelector('.table-responsive table');
    if (!table) {
        alert('Cədvəl tapılmadı!');
        return;
    }

    // HTML-i kopyala
    var html = table.outerHTML;

    // Blob yarat
    var blob = new Blob([html], {
        type: 'application/vnd.ms-excel'
    });

    // Download linki yarat
    var link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = 'imtahan_neticeler_<?= $exam_id ?>_<?= date("Y-m-d") ?>.xls';
    link.click();
}

// Çap üçün CSS
var style = document.createElement('style');
style.innerHTML = `
    @media print {
        body * {
            visibility: hidden;
        }
        .main-card, .main-card * {
            visibility: visible;
        }
        .main-card {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .btn {
            display: none !important;
        }
        .nav-pills {
            display: none !important;
        }
        .exam-selector {
            background: white !important;
            color: black !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
`;
document.head.appendChild(style);
</script>
</body>
</html>
