<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";
checkAdminRights();

if (!isset($_GET['exam']) || !is_numeric($_GET['exam']) || !isset($_GET['student']) || !is_numeric($_GET['student'])) {
    header("Location: exams.php");
    exit();
}

$exam_id = intval($_GET['exam']);
$student_id = intval($_GET['student']);

$database = new Database();
$db = $database->getConnection();

// İmtahan və tələbə məlumatlarını almaq
$query = "SELECT e.id_exam, e.date_exam, e.datetime, e.status, 
          s.subjectname, sg.group_number, u.f_name, u.username
          FROM exams e
          JOIN subjects s ON e.id_subject = s.id_subject
          JOIN student_group sg ON e.id_student_group = sg.id_student_group
          JOIN users u ON u.id_users = :student_id
          WHERE e.id_exam = :exam_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":exam_id", $exam_id);
$stmt->bindParam(":student_id", $student_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: exams.php");
    exit();
}

$exam = $stmt->fetch(PDO::FETCH_ASSOC);

// İmtahan tamamlanmayıbsa, yönləndirmə
if ($exam['status'] != 'completed') {
    header("Location: exams.php");
    exit();
}

$pageTitle = "Tələbə Cavabları";
include_once "../includes/header.php";

// Tələbənin cavablarını almaq
$query = "SELECT qr.id_question_text, qr.question_text, qr.question_score, 
          qt.quest_type_name, qt.question_var, a.user_answer, a.correct_var, a.is_correct,
          a.datetime
          FROM answers a
          JOIN question_read qr ON a.id_questions = qr.id_question_text
          JOIN question_types qt ON qr.id_question_type = qt.id_quest_type
          WHERE a.exam_id = :exam_id AND a.user_id = :student_id
          ORDER BY qr.id_question_text";
$stmt = $db->prepare($query);
$stmt->bindParam(":exam_id", $exam_id);
$stmt->bindParam(":student_id", $student_id);
$stmt->execute();
$answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ümumi nəticə hesablamaq
$totalQuestions = count($answers);
$correctAnswers = 0;
$totalScore = 0;
$maxScore = 0;

foreach ($answers as $answer) {
    $maxScore += $answer['question_score'];
    if ($answer['is_correct']) {
        $correctAnswers++;
        $totalScore += $answer['question_score'];
    }
}

$percentage = $maxScore > 0 ? ($totalScore / $maxScore) * 100 : 0;
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>Tələbə Cavabları</h1>
        <p class="lead"><?php echo $exam['f_name']; ?> (<?php echo $exam['username']; ?>)</p>
        <p>
            <strong>İmtahan:</strong> <?php echo $exam['subjectname']; ?> - <?php echo $exam['group_number']; ?><br>
            <strong>Tarix:</strong> <?php echo date('d.m.Y', strtotime($exam['date_exam'])); ?>, 
            <strong>Saat:</strong> <?php echo date('H:i', strtotime($exam['datetime'])); ?>
        </p>
    </div>
    <div class="col-md-6 text-end">
        <a href="exam_results.php?id=<?php echo $exam_id; ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Nəticələrə qayıt
        </a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Düzgün cavablar</h5>
                <p class="card-text display-4"><?php echo $correctAnswers; ?> / <?php echo $totalQuestions; ?></p>
                <p><?php echo number_format(($correctAnswers / $totalQuestions) * 100, 2); ?>%</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Ümumi bal</h5>
                <p class="card-text display-4"><?php echo number_format($totalScore, 2); ?> / <?php echo number_format($maxScore, 2); ?></p>
                <p><?php echo number_format($percentage, 2); ?>%</p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Tələbənin cavabları</h5>
    </div>
    <div class="card-body">
        <?php if (empty($answers)): ?>
            <div class="alert alert-info">Bu imtahanda tələbə tərəfindən verilmiş cavab yoxdur.</div>
        <?php else: ?>
            <div class="accordion" id="answersAccordion">
                <?php 
                $counter = 1;
                foreach ($answers as $answer): 
                    $accordionId = "answer" . $answer['id_question_text'];
                    $headerClass = $answer['is_correct'] ? 'bg-success text-white' : 'bg-danger text-white';
                ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?php echo $accordionId; ?>">
                            <button class="accordion-button collapsed <?php echo $headerClass; ?>" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $accordionId; ?>" 
                                    aria-expanded="false" aria-controls="collapse<?php echo $accordionId; ?>">
                                <div class="row w-100">
                                    <div class="col-md-8">
                                        <strong>Sual <?php echo $counter++; ?>:</strong> 
                                        <?php echo substr(strip_tags($answer['question_text']), 0, 100) . (strlen(strip_tags($answer['question_text'])) > 100 ? '...' : ''); ?>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <span class="badge <?php echo $answer['is_correct'] ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $answer['is_correct'] ? 'Düzgün' : 'Yanlış'; ?>
                                        </span>
                                        <span class="badge bg-primary"><?php echo $answer['question_score']; ?> bal</span>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse<?php echo $accordionId; ?>" class="accordion-collapse collapse" 
                             aria-labelledby="heading<?php echo $accordionId; ?>" data-bs-parent="#answersAccordion">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <h5>Sual:</h5>
                                        <p><?php echo nl2br($answer['question_text']); ?></p>
                                        
                                        <?php if ($answer['question_var'] == 'multiple'): ?>
                                            <?php
                                            // Çoxseçimli sual variantlarını almaq
                                            $query = "SELECT var_a, var_b, var_c, var_d FROM multiple_questions 
                                                     WHERE id_question_text = :id_question";
                                            $stmt = $db->prepare($query);
                                            $stmt->bindParam(":id_question", $answer['id_question_text']);
                                            $stmt->execute();
                                            $options = $stmt->fetch(PDO::FETCH_ASSOC);
                                            ?>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="card">
                                                        <div class="card-header">Tələbənin cavabı</div>
                                                        <div class="card-body">
                                                            <?php if (empty($answer['user_answer'])): ?>
                                                                <p class="text-muted">Cavab verilməyib</p>
                                                            <?php else: ?>
                                                                <p>
                                                                    <?php 
                                                                    $userOption = '';
                                                                    if ($answer['user_answer'] == 'a') $userOption = $options['var_a'];
                                                                    elseif ($answer['user_answer'] == 'b') $userOption = $options['var_b'];
                                                                    elseif ($answer['user_answer'] == 'c') $userOption = $options['var_c'];
                                                                    elseif ($answer['user_answer'] == 'd') $userOption = $options['var_d'];
                                                                    
                                                                    echo '<strong>' . strtoupper($answer['user_answer']) . ')</strong> ' . $userOption;
                                                                    ?>
                                                                </p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card">
                                                        <div class="card-header">Düzgün cavab</div>
                                                        <div class="card-body">
                                                            <p>
                                                                <?php 
                                                                $correctOption = '';
                                                                if ($answer['correct_var'] == 'a') $correctOption = $options['var_a'];
                                                                elseif ($answer['correct_var'] == 'b') $correctOption = $options['var_b'];
                                                                elseif ($answer['correct_var'] == 'c') $correctOption = $options['var_c'];
                                                                elseif ($answer['correct_var'] == 'd') $correctOption = $options['var_d'];
                                                                
                                                                echo '<strong>' . strtoupper($answer['correct_var']) . ')</strong> ' . $correctOption;
                                                                ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php elseif ($answer['question_var'] == 'open'): ?>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="card">
                                                        <div class="card-header">Tələbənin cavabı</div>
                                                        <div class="card-body">
                                                            <?php if (empty($answer['user_answer'])): ?>
                                                                <p class="text-muted">Cavab verilməyib</p>
                                                            <?php else: ?>
                                                                <p><?php echo $answer['user_answer']; ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card">
                                                        <div class="card-header">Düzgün cavab</div>
                                                        <div class="card-body">
                                                            <p><?php echo $answer['correct_var']; ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php elseif ($answer['question_var'] == 'matching'): ?>
                                            <?php
                                            // Uyğunlaşdırma sual variantlarını almaq
                                            $query = "SELECT variants FROM matching_questions 
                                                     WHERE id_question_text = :id_question";
                                            $stmt = $db->prepare($query);
                                            $stmt->bindParam(":id_question", $answer['id_question_text']);
                                            $stmt->execute();
                                            $matchingOptions = $stmt->fetch(PDO::FETCH_ASSOC);
                                            ?>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="card">
                                                        <div class="card-header">Tələbənin cavabı</div>
                                                        <div class="card-body">
                                                            <?php if (empty($answer['user_answer'])): ?>
                                                                <p class="text-muted">Cavab verilməyib</p>
                                                            <?php else: ?>
                                                                <p><?php echo $answer['user_answer']; ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card">
                                                        <div class="card-header">Düzgün cavab</div>
                                                        <div class="card-body">
                                                            <p><?php echo $answer['correct_var']; ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <div class="card">
                                                    <div class="card-header">Bütün variantlar</div>
                                                    <div class="card-body">
                                                        <p><?php echo $matchingOptions['variants']; ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="mt-3">
                                            <p class="text-muted">
                                                <small>Cavab tarixi: <?php echo date('d.m.Y H:i:s', strtotime($answer['datetime'])); ?></small>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?>