<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";
checkLogin();

// Yalnız tələbələr girə bilər
if ($_SESSION['status'] != 'student') {
    echo '<div class="alert alert-danger"><i class="fas fa-ban me-2"></i>Giriş icazəsi yoxdur!</div>';
    exit;
}

// İmtahan ID-si yoxlanışı
if (!isset($_GET['exam_id']) || !is_numeric($_GET['exam_id'])) {
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Yanlış imtahan ID!</div>';
    exit;
}

$exam_id = $_GET['exam_id'];
$user_id = $_SESSION['user_id'];

$database = new Database();
$db = $database->getConnection();

// İmtahan məlumatlarını əldə etmək
$exam_query = "SELECT e.id_exam, e.date_exam, e.datetime, e.status, s.subjectname, s.timer
               FROM exams e
               JOIN subjects s ON e.id_subject = s.id_subject
               WHERE e.id_exam = :exam_id";

$exam_stmt = $db->prepare($exam_query);
$exam_stmt->bindParam(":exam_id", $exam_id);
$exam_stmt->execute();
$exam_info = $exam_stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam_info) {
    echo '<div class="alert alert-danger"><i class="fas fa-search me-2"></i>İmtahan tapılmadı!</div>';
    exit;
}

// Tələbənin bu imtahana cavab verib-vermədiyini yoxlamaq
$access_query = "SELECT COUNT(*) as count FROM answers WHERE exam_id = :exam_id AND user_id = :user_id";
$access_stmt = $db->prepare($access_query);
$access_stmt->bindParam(":exam_id", $exam_id);
$access_stmt->bindParam(":user_id", $user_id);
$access_stmt->execute();
$has_access = $access_stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

if (!$has_access) {
    echo '<div class="alert alert-warning"><i class="fas fa-lock me-2"></i>Bu imtahana hələ başlamamışsınız!</div>';
    exit;
}

// İmtahan cavablarının təfərrüatları
$query = "SELECT 
            a.id_questions,
            a.user_answer,
            a.correct_var,
            a.datetime as answer_time,
            qr.question_text,
            qr.question_score,
            qt.question_var,
            qt.quest_type_name,
            CASE 
              WHEN TRIM(LOWER(a.user_answer)) = TRIM(LOWER(a.correct_var)) THEN 1
              ELSE 0
            END as is_correct
          FROM answers a
          JOIN question_read qr ON a.id_questions = qr.id_question_text
          JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
          WHERE a.exam_id = :exam_id AND a.user_id = :user_id
          ORDER BY a.id_questions";

$stmt = $db->prepare($query);
$stmt->bindParam(":exam_id", $exam_id);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$details = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($details)) {
    echo '<div class="alert alert-warning"><i class="fas fa-question-circle me-2"></i>Bu imtahan üçün cavab təfərrüatı tapılmadı.</div>';
    exit;
}

// Statistikaları hesablamaq
$correct_count = 0;
$total_score = 0;
$max_score = 0;
$question_types = [];

foreach ($details as $detail) {
    $max_score += $detail['question_score'];
    if ($detail['is_correct']) {
        $correct_count++;
        $total_score += $detail['question_score'];
    }
    
    // Sual tiplərini qruplaşdırmaq
    $type = $detail['quest_type_name'];
    if (!isset($question_types[$type])) {
        $question_types[$type] = [
            'total' => 0,
            'correct' => 0,
            'score' => 0,
            'max_score' => 0
        ];
    }
    
    $question_types[$type]['total']++;
    $question_types[$type]['max_score'] += $detail['question_score'];
    
    if ($detail['is_correct']) {
        $question_types[$type]['correct']++;
        $question_types[$type]['score'] += $detail['question_score'];
    }
}

$percentage = $max_score > 0 ? ($total_score / $max_score) * 100 : 0;
$wrong_count = count($details) - $correct_count;

// Qiymət hesablamaq
$grade = 'F';
$grade_class = 'danger';
if ($percentage >= 90) {
    $grade = 'A+';
    $grade_class = 'success';
} elseif ($percentage >= 80) {
    $grade = 'A';
    $grade_class = 'success';
} elseif ($percentage >= 70) {
    $grade = 'B';
    $grade_class = 'primary';
} elseif ($percentage >= 60) {
    $grade = 'C';
    $grade_class = 'warning';
} elseif ($percentage >= 40) {
    $grade = 'D';
    $grade_class = 'info';
}

// Vaxt statistikası
$first_answer_time = !empty($details) ? min(array_column($details, 'answer_time')) : null;
$last_answer_time = !empty($details) ? max(array_column($details, 'answer_time')) : null;
$exam_duration = null;

if ($first_answer_time && $last_answer_time) {
    $start_time = strtotime($first_answer_time);
    $end_time = strtotime($last_answer_time);
    $exam_duration = round(($end_time - $start_time) / 60); // dəqiqələrlə
}
?>

<!-- Header Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="text-primary mb-1">
                    <i class="fas fa-graduation-cap me-2"></i>
                    <?php echo htmlspecialchars($exam_info['subjectname']); ?>
                </h4>
                <p class="text-muted mb-0">
                    <i class="fas fa-calendar me-1"></i>
                    <?php echo date('d.m.Y H:i', strtotime($exam_info['datetime'])); ?>
                    
                    <span class="ms-3">
                        <i class="fas fa-clock me-1"></i>
                        Müddət: <?php echo $exam_info['timer']; ?> dəqiqə
                    </span>
                    
                    <?php if ($exam_duration): ?>
                        <span class="ms-3">
                            <i class="fas fa-hourglass-half me-1"></i>
                            Keçirdiyin vaxt: <?php echo $exam_duration; ?> dəqiqə
                        </span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="text-end">
                <span class="badge bg-<?php echo $exam_info['status'] == 'completed' ? 'success' : 'warning'; ?> fs-6">
                    <?php echo $exam_info['status'] == 'completed' ? 'Tamamlandı' : 'Davam edir'; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row mb-4">
    <div class="col-lg-8">
        <div class="row">
            <!-- Overall Score -->
            <div class="col-md-6 mb-3">
                <div class="card border-<?php echo $grade_class; ?> h-100">
                    <div class="card-body text-center">
                        <div class="position-relative d-inline-block mb-3">
                            <svg width="100" height="100" class="position-relative">
                                <circle cx="50" cy="50" r="45" stroke="#e9ecef" stroke-width="8" fill="transparent"/>
                                <circle cx="50" cy="50" r="45" stroke="<?php echo $percentage >= 60 ? '#28a745' : '#dc3545'; ?>" 
                                        stroke-width="8" fill="transparent"
                                        stroke-dasharray="<?php echo 2 * 3.14159 * 45; ?>"
                                        stroke-dashoffset="<?php echo 2 * 3.14159 * 45 * (1 - $percentage/100); ?>"
                                        transform="rotate(-90 50 50)"/>
                            </svg>
                            <div class="position-absolute top-50 start-50 translate-middle">
                                <div class="text-<?php echo $grade_class; ?> fw-bold" style="font-size: 1.5rem;">
                                    <?php echo number_format($percentage, 0); ?>%
                                </div>
                                <div class="badge bg-<?php echo $grade_class; ?> fs-6">
                                    <?php echo $grade; ?>
                                </div>
                            </div>
                        </div>
                        <h6 class="text-muted">Ümumi Nəticə</h6>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="col-md-6 mb-3">
                <div class="row h-100">
                    <div class="col-6 mb-2">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body text-center p-3">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <h4 class="mb-1"><?php echo $correct_count; ?></h4>
                                <small>Düzgün</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-2">
                        <div class="card bg-danger text-white h-100">
                            <div class="card-body text-center p-3">
                                <i class="fas fa-times-circle fa-2x mb-2"></i>
                                <h4 class="mb-1"><?php echo $wrong_count; ?></h4>
                                <small>Səhv</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-info text-white h-100">
                            <div class="card-body text-center p-3">
                                <i class="fas fa-question-circle fa-2x mb-2"></i>
                                <h4 class="mb-1"><?php echo count($details); ?></h4>
                                <small>Toplam</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body text-center p-3">
                                <i class="fas fa-chart-line fa-2x mb-2"></i>
                                <h4 class="mb-1"><?php echo number_format($total_score, 1); ?></h4>
                                <small>Bal</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Score Details -->
    <div class="col-lg-4 mb-3">
        <div class="card h-100">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Bal Təfərrüatı</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Aldığınız bal:</span>
                    <strong class="text-success"><?php echo number_format($total_score, 1); ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Maksimum bal:</span>
                    <strong class="text-primary"><?php echo number_format($max_score, 1); ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span>İtirdiyin bal:</span>
                    <strong class="text-danger"><?php echo number_format($max_score - $total_score, 1); ?></strong>
                </div>
                <div class="progress mb-2" style="height: 12px;">
                    <div class="progress-bar bg-<?php echo $percentage >= 60 ? 'success' : 'danger'; ?>" 
                         style="width: <?php echo $percentage; ?>%"></div>
                </div>
                <div class="text-center">
                    <span class="badge bg-<?php echo $grade_class; ?> fs-6">
                        Qiymət: <?php echo $grade; ?> (<?php echo number_format($percentage, 1); ?>%)
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Question Types Analysis -->
<?php if (!empty($question_types)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Sual Tiplərə Görə Analiz</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($question_types as $type_name => $type_stats): ?>
                        <?php
                        $type_percentage = $type_stats['max_score'] > 0 ? ($type_stats['score'] / $type_stats['max_score']) * 100 : 0;
                        $type_class = $type_percentage >= 60 ? 'success' : ($type_percentage >= 40 ? 'warning' : 'danger');
                        ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card border-<?php echo $type_class; ?>">
                                <div class="card-body text-center">
                                    <h6 class="card-title text-<?php echo $type_class; ?>">
                                        <?php echo htmlspecialchars($type_name); ?>
                                    </h6>
                                    <div class="mb-2">
                                        <h4 class="text-<?php echo $type_class; ?>">
                                            <?php echo number_format($type_percentage, 1); ?>%
                                        </h4>
                                    </div>
                                    <div class="progress mb-2" style="height: 8px;">
                                        <div class="progress-bar bg-<?php echo $type_class; ?>" 
                                             style="width: <?php echo $type_percentage; ?>%"></div>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo $type_stats['correct']; ?>/<?php echo $type_stats['total']; ?> düzgün 
                                        <br>(<?php echo number_format($type_stats['score'], 1); ?>/<?php echo number_format($type_stats['max_score'], 1); ?> bal)
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Detailed Question Analysis -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-list-alt me-2"></i>Sual-Cavab Təfərrüatları</h6>
                <div>
                    <button class="btn btn-outline-primary btn-sm" onclick="toggleAllQuestions()">
                        <i class="fas fa-expand-alt me-1"></i>Hamısını Genişləndir
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="showOnlyWrong()">
                        <i class="fas fa-times-circle me-1"></i>Yalnız Səhvlər
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%">#</th>
                                <th width="30%">Sual</th>
                                <th width="20%">Sizin Cavabınız</th>
                                <th width="20%">Düzgün Cavab</th>
                                <th width="10%">Bal</th>
                                <th width="10%">Nəticə</th>
                                <th width="5%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($details as $index => $detail): ?>
                                <?php
                                $row_class = $detail['is_correct'] ? 'table-success' : 'table-danger';
                                $result_icon = $detail['is_correct'] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger';
                                $result_text = $detail['is_correct'] ? 'Düzgün' : 'Səhv';
                                ?>
                                <tr class="<?php echo $row_class; ?> question-row" data-correct="<?php echo $detail['is_correct']; ?>">
                                    <td class="text-center">
                                        <strong><?php echo $index + 1; ?></strong>
                                    </td>
                                    <td>
                                        <div class="question-preview">
                                            <strong>Sual <?php echo $index + 1; ?>:</strong>
                                            <div class="question-text-short">
                                                <?php echo htmlspecialchars(substr($detail['question_text'], 0, 100)); ?>
                                                <?php if (strlen($detail['question_text']) > 100): ?>
                                                    <span class="text-muted">...</span>
                                                    <button class="btn btn-link btn-sm p-0 ms-1" onclick="toggleQuestion(<?php echo $index; ?>)">
                                                        <i class="fas fa-expand-alt"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            <div class="question-text-full d-none">
                                                <?php echo nl2br(htmlspecialchars($detail['question_text'])); ?>
                                                <button class="btn btn-link btn-sm p-0 ms-1" onclick="toggleQuestion(<?php echo $index; ?>)">
                                                    <i class="fas fa-compress-alt"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                <i class="fas fa-tag me-1"></i>
                                                Tip: <?php echo htmlspecialchars($detail['quest_type_name']); ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="user-answer">
                                            <?php if (empty($detail['user_answer'])): ?>
                                                <span class="text-muted fst-italic">
                                                    <i class="fas fa-ban me-1"></i>Cavab verilməyib
                                                </span>
                                            <?php else: ?>
                                                <code class="bg-light p-2 rounded d-block">
                                                    <?php echo htmlspecialchars($detail['user_answer']); ?>
                                                </code>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <code class="bg-success text-white p-2 rounded d-block">
                                            <?php echo htmlspecialchars($detail['correct_var']); ?>
                                        </code>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($detail['is_correct']): ?>
                                            <span class="badge bg-success fs-6">
                                                <?php echo $detail['question_score']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger fs-6">0</span>
                                        <?php endif; ?>
                                        <small class="text-muted d-block">/ <?php echo $detail['question_score']; ?></small>
                                    </td>
                                    <td class="text-center">
                                        <i class="fas <?php echo $result_icon; ?> fa-2x"></i>
                                        <br><small class="fw-bold"><?php echo $result_text; ?></small>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!$detail['is_correct']): ?>
                                            <button class="btn btn-outline-info btn-sm" onclick="showExplanation(<?php echo $index; ?>)" title="İzah">
                                                <i class="fas fa-lightbulb"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Performance Insights -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-info">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="fas fa-brain me-2"></i>Performans Təhlili</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-success">Güclü Tərəflər:</h6>
                        <ul class="list-unstyled">
                            <?php if ($percentage >= 80): ?>
                                <li><i class="fas fa-star text-warning me-2"></i>Əla nəticə əldə etmişsiniz!</li>
                            <?php elseif ($percentage >= 60): ?>
                                <li><i class="fas fa-check-circle text-success me-2"></i>Yaxşı nəticə əldə etmişsiniz!</li>
                            <?php endif; ?>
                            
                            <?php if ($correct_count > $wrong_count): ?>
                                <li><i class="fas fa-thumbs-up text-success me-2"></i>Düzgün cavablar səhv cavablardan çoxdur</li>
                            <?php endif; ?>
                            
                            <?php
                            // Ən uğurlu sual tipi
                            $best_type = '';
                            $best_percentage = 0;
                            foreach ($question_types as $type => $stats) {
                                $type_perc = $stats['max_score'] > 0 ? ($stats['score'] / $stats['max_score']) * 100 : 0;
                                if ($type_perc > $best_percentage) {
                                    $best_percentage = $type_perc;
                                    $best_type = $type;
                                }
                            }
                            if ($best_type && $best_percentage >= 70): ?>
                                <li><i class="fas fa-medal text-warning me-2"></i><?php echo htmlspecialchars($best_type); ?> suallarında yaxşı nəticə</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <div class="col-md-6">
                        <h6 class="text-warning">Təkmilləşdirmə Sahələri:</h6>
                        <ul class="list-unstyled">
                            <?php if ($percentage < 60): ?>
                                <li><i class="fas fa-exclamation-triangle text-warning me-2"></i>Daha çox məşq etmək lazımdır</li>
                            <?php endif; ?>
                            
                            <?php if ($wrong_count > $correct_count): ?>
                                <li><i class="fas fa-times-circle text-danger me-2"></i>Səhv cavablar düzgün cavablardan çoxdur</li>
                            <?php endif; ?>
                            
                            <?php
                            // Ən zəif sual tipi
                            $worst_type = '';
                            $worst_percentage = 100;
                            foreach ($question_types as $type => $stats) {
                                $type_perc = $stats['max_score'] > 0 ? ($stats['score'] / $stats['max_score']) * 100 : 0;
                                if ($type_perc < $worst_percentage) {
                                    $worst_percentage = $type_perc;
                                    $worst_type = $type;
                                }
                            }
                            if ($worst_type && $worst_percentage < 50): ?>
                                <li><i class="fas fa-arrow-up text-info me-2"></i><?php echo htmlspecialchars($worst_type); ?> suallarında təkmilləşmə lazımdır</li>
                            <?php endif; ?>
                            
                            <?php if ($exam_duration && $exam_duration > $exam_info['timer']): ?>
                                <li><i class="fas fa-clock text-warning me-2"></i>Vaxt idarəetməsi üzərində işləmək lazımdır</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle question text
function toggleQuestion(index) {
    const row = document.querySelectorAll('.question-row')[index];
    const shortText = row.querySelector('.question-text-short');
    const fullText = row.querySelector('.question-text-full');
    
    if (shortText.classList.contains('d-none')) {
        shortText.classList.remove('d-none');
        fullText.classList.add('d-none');
    } else {
        shortText.classList.add('d-none');
        fullText.classList.remove('d-none');
    }
}

// Toggle all questions
function toggleAllQuestions() {
    const shortTexts = document.querySelectorAll('.question-text-short');
    const fullTexts = document.querySelectorAll('.question-text-full');
    
    const allExpanded = Array.from(shortTexts).every(el => el.classList.contains('d-none'));
    
    shortTexts.forEach((el, index) => {
        if (allExpanded) {
            el.classList.remove('d-none');
            fullTexts[index].classList.add('d-none');
        } else {
            el.classList.add('d-none');
            fullTexts[index].classList.remove('d-none');
        }
    });
    
    const button = event.target.closest('button');
    if (allExpanded) {
        button.innerHTML = '<i class="fas fa-expand-alt me-1"></i>Hamısını Genişləndir';
    } else {
        button.innerHTML = '<i class="fas fa-compress-alt me-1"></i>Hamısını Kiçilt';
    }
}

// Show only wrong answers
function showOnlyWrong() {
    const rows = document.querySelectorAll('.question-row');
    const button = event.target.closest('button');
    
    const onlyWrongVisible = Array.from(rows).some(row => 
        row.dataset.correct === '1' && row.classList.contains('d-none')
    );
    
    rows.forEach(row => {
        if (onlyWrongVisible) {
            // Show all
            row.classList.remove('d-none');
            button.innerHTML = '<i class="fas fa-times-circle me-1"></i>Yalnız Səhvlər';
        } else {
            // Show only wrong
            if (row.dataset.correct === '1') {
                row.classList.add('d-none');
            }
            button.innerHTML = '<i class="fas fa-eye me-1"></i>Hamısını Göstər';
        }
    });
}

// Show explanation (placeholder)
function showExplanation(index) {
    alert('Bu funksiya hələ hazırlanır. Səhv cavablar üçün əlavə izahlar əlavə ediləcək.');
}

// Progress bar animation
document.addEventListener('DOMContentLoaded', function() {
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach((bar, index) => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.transition = 'width 1.5s ease-in-out';
            bar.style.width = width;
        }, 200 + (index * 100));
    });
});
</script>

<style>
.question-preview {
    max-width: 300px;
}

.user-answer code {
    word-break: break-word;
    white-space: pre-wrap;
}

.table td {
    vertical-align: middle;
}

.badge {
    font-size: 0.8rem;
}

.progress-bar {
    transition: width 1.5s ease-in-out;
}

.card {
    border-radius: 10px;
}

.card-header {
    border-radius: 10px 10px 0 0 !important;
}

.question-row {
    cursor: pointer;
    transition: all 0.3s ease;
}

.question-row:hover {
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.table-success {
    background-color: rgba(40, 167, 69, 0.1) !important;
}

.table-danger {
    background-color: rgba(220, 53, 69, 0.1) !important;
}

.btn-link {
    text-decoration: none !important;
}

.btn-link:hover {
    text-decoration: underline !important;
}

/* Custom scrollbar for table */
.table-responsive::-webkit-scrollbar {
    height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeInUp 0.6s ease-out;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .question-preview {
        max-width: none;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    h4 {
        font-size: 1.1rem;
    }
    
    .progress {
        height: 8px;
    }
}

/* Print styles */
@media print {
    .btn {
        display: none !important;
    }
    
    .card {
        border: 1px solid #ddd !important;
        break-inside: avoid;
    }
    
    .table-responsive {
        overflow: visible !important;
    }
    
    .question-text-full {
        display: block !important;
    }
    
    .question-text-short {
        display: none !important;
    }
}

/* Color variations for different question types */
.border-multiple_choice {
    border-left: 4px solid #007bff !important;
}

.border-open_ended {
    border-left: 4px solid #28a745 !important;
}

.border-true_false {
    border-left: 4px solid #ffc107 !important;
}

.border-matching {
    border-left: 4px solid #6f42c1 !important;
}

/* Performance indicators */
.performance-excellent {
    background: linear-gradient(45deg, #28a745, #20c997);
    color: white;
}

.performance-good {
    background: linear-gradient(45deg, #007bff, #6610f2);
    color: white;
}

.performance-average {
    background: linear-gradient(45deg, #ffc107, #fd7e14);
    color: white;
}

.performance-poor {
    background: linear-gradient(45deg, #dc3545, #e83e8c);
    color: white;
}

/* Tooltip styles */
.tooltip-inner {
    max-width: 300px;
    text-align: left;
}

/* Modal enhancements */
.modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.modal-header {
    border-radius: 15px 15px 0 0;
    border-bottom: none;
    padding: 1.5rem;
}

.modal-body {
    padding: 1.5rem;
}

/* Accessibility improvements */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Focus styles */
.btn:focus,
.form-control:focus {
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Loading states */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Success/Error message animations */
.alert {
    animation: slideInDown 0.5s ease-out;
}

@keyframes slideInDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Question type icons */
.question-type-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
    color: white;
}

.question-type-multiple { background-color: #007bff; }
.question-type-open { background-color: #28a745; }
.question-type-true-false { background-color: #ffc107; }
.question-type-matching { background-color: #6f42c1; }

/* Improved table styling */
.table thead th {
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.table tbody tr:hover {
    background-color: rgba(0,0,0,0.02);
}

/* Badge improvements */
.badge {
    font-weight: 500;
    letter-spacing: 0.3px;
}

/* Code block styling */
code {
    font-size: 0.9em;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
}

/* Performance chart styling */
.performance-chart {
    position: relative;
    height: 200px;
}

/* Statistics cards hover effects */
.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
}

/* Improved spacing */
.mb-half {
    margin-bottom: 0.5rem !important;
}

.mt-half {
    margin-top: 0.5rem !important;
}

/* Custom utilities */
.text-shadow {
    text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
}

.box-shadow-sm {
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}

.box-shadow-lg {
    box-shadow: 0 1rem 3rem rgba(0,0,0,0.175);
}

/* Dark mode support (if needed) */
@media (prefers-color-scheme: dark) {
    .card {
        background-color: #2d3748;
        border-color: #4a5568;
    }
    
    .table {
        color: #e2e8f0;
    }
    
    .table-dark {
        background-color: #1a202c;
    }
}
</style>

<!-- Additional JavaScript for enhanced functionality -->
<script>
// Advanced functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Smooth scrolling for internal links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
    
    // Auto-resize text areas
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    });
    
    // Print functionality
    if (document.getElementById('printBtn')) {
        document.getElementById('printBtn').addEventListener('click', function() {
            window.print();
        });
    }
    
    // Export functionality
    if (document.getElementById('exportBtn')) {
        document.getElementById('exportBtn').addEventListener('click', function() {
            exportToCSV();
        });
    }
    
    // Search functionality
    const searchInput = document.getElementById('questionSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterQuestions(this.value);
        });
    }
    
    // Performance insights toggle
    const insightsToggle = document.getElementById('toggleInsights');
    if (insightsToggle) {
        insightsToggle.addEventListener('click', function() {
            const insights = document.getElementById('performanceInsights');
            insights.classList.toggle('d-none');
            this.textContent = insights.classList.contains('d-none') ? 'Təhlili Göstər' : 'Təhlili Gizlət';
        });
    }
});

// Filter questions based on search
function filterQuestions(searchTerm) {
    const rows = document.querySelectorAll('.question-row');
    const term = searchTerm.toLowerCase();
    
    rows.forEach(row => {
        const questionText = row.querySelector('.question-text-short, .question-text-full').textContent.toLowerCase();
        const userAnswer = row.querySelector('.user-answer').textContent.toLowerCase();
        const correctAnswer = row.querySelector('code').textContent.toLowerCase();
        
        if (questionText.includes(term) || userAnswer.includes(term) || correctAnswer.includes(term)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Export results to CSV
function exportToCSV() {
    const data = [];
    const headers = ['Sual №', 'Sual Mətni', 'Sizin Cavabınız', 'Düzgün Cavab', 'Bal', 'Nəticə'];
    data.push(headers);
    
    const rows = document.querySelectorAll('.question-row');
    rows.forEach((row, index) => {
        const cells = row.querySelectorAll('td');
        const rowData = [
            index + 1,
            cells[1].textContent.trim().replace(/\s+/g, ' '),
            cells[2].textContent.trim(),
            cells[3].textContent.trim(),
            cells[4].textContent.trim(),
            cells[5].textContent.trim()
        ];
        data.push(rowData);
    });
    
    const csvContent = data.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'imtahan_tefsiruat.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+P for print
    if (e.ctrlKey && e.key === 'p') {
        e.preventDefault();
        window.print();
    }
    
    // Ctrl+E for export
    if (e.ctrlKey && e.key === 'e') {
        e.preventDefault();
        exportToCSV();
    }
    
    // Escape to close modals
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) bsModal.hide();
        });
    }
});

// Enhanced performance analytics
function generatePerformanceReport() {
    const correctAnswers = <?php echo $correct_count; ?>;
    const totalQuestions = <?php echo count($details); ?>;
    const percentage = <?php echo $percentage; ?>;
    
    const report = {
        overall: {
            score: percentage,
            grade: '<?php echo $grade; ?>',
            correct: correctAnswers,
            total: totalQuestions
        },
        recommendations: []
    };
    
    if (percentage < 60) {
        report.recommendations.push('Əsas mövzuları təkrar etmək');
        report.recommendations.push('Daha çox məşq sualları həll etmək');
    }
    
    if (percentage >= 80) {
        report.recommendations.push('Əla nəticə! Hazırkı səviyyəni qorumaq');
    }
    
    return report;
}

// Lazy loading for large content
function lazyLoadContent() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                observer.unobserve(img);
            }
        });
    });
    
    document.querySelectorAll('.lazy').forEach(img => {
        observer.observe(img);
    });
}

// Initialize lazy loading
lazyLoadContent();

// Performance monitoring
function trackUserInteraction(action, element) {
    // This can be used for analytics
    console.log(`User performed: ${action} on`, element);
}

// Add click tracking to buttons
document.querySelectorAll('button').forEach(btn => {
    btn.addEventListener('click', function() {
        trackUserInteraction('click', this.textContent);
    });
});
</script>

<!-- Add Print Button to Header -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add print and export buttons to the header
    const headerActions = document.querySelector('.modal-header');
    if (headerActions) {
        const actionsDiv = document.createElement('div');
        actionsDiv.className = 'd-flex gap-2 me-3';
        actionsDiv.innerHTML = `
            <button class="btn btn-outline-light btn-sm" onclick="window.print()" title="Çap et">
                <i class="fas fa-print"></i>
            </button>
            <button class="btn btn-outline-light btn-sm" onclick="exportToCSV()" title="Export">
                <i class="fas fa-download"></i>
            </button>
        `;
        headerActions.insertBefore(actionsDiv, headerActions.lastElementChild);
    }
});
</script>

<?php
// Performance insights data for JavaScript
$js_insights = [
    'totalQuestions' => count($details),
    'correctAnswers' => $correct_count,
    'wrongAnswers' => $wrong_count,
    'percentage' => $percentage,
    'grade' => $grade,
    'examDuration' => $exam_duration,
    'allowedTime' => $exam_info['timer'],
    'questionTypes' => $question_types
];
?>

<script>
// Pass PHP data to JavaScript
const examInsights = <?php echo json_encode($js_insights); ?>;

// Use this data for advanced analytics
console.log('Exam Insights:', examInsights);
</script>   