<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";
checkAdminRights();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: exams.php");
    exit();
}

$exam_id = intval($_GET['id']);

$database = new Database();
$db = $database->getConnection();

// İmtahan məlumatlarını almaq
$query = "SELECT e.id_exam, e.date_exam, e.datetime, e.status, s.subjectname, sg.group_number
          FROM exams e
          JOIN subjects s ON e.id_subject = s.id_subject
          JOIN student_group sg ON e.id_student_group = sg.id_student_group
          WHERE e.id_exam = :exam_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":exam_id", $exam_id);
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

$pageTitle = "İmtahan Nəticələri";
include_once "../includes/header.php";

// Tələbə nəticələrini almaq
$query = "SELECT u.id_users, u.f_name, u.username,
          COUNT(a.id_answer) as total_questions,
          SUM(a.is_correct) as correct_answers,
          SUM(qr.question_score * a.is_correct) as total_score,
          (SELECT SUM(qr2.question_score) 
           FROM question_read qr2
           JOIN question_files qf2 ON qr2.id_read_quest_file = qf2.id_read_quest_file
           WHERE qf2.subject_id = s.id_subject) as max_score
          FROM users u
          JOIN answers a ON u.id_users = a.user_id
          JOIN question_read qr ON a.id_questions = qr.id_question_text
          JOIN exams e ON a.exam_id = e.id_exam
          JOIN subjects s ON e.id_subject = s.id_subject
          WHERE a.exam_id = :exam_id
          GROUP BY u.id_users
          ORDER BY total_score DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(":exam_id", $exam_id);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ümumi statistika
$totalStudents = count($results);
$totalScore = 0;
$totalCorrect = 0;
$totalQuestions = 0;
$maxScore = $totalStudents > 0 ? $results[0]['max_score'] : 0;

foreach ($results as $result) {
    $totalScore += $result['total_score'];
    $totalCorrect += $result['correct_answers'];
    $totalQuestions += $result['total_questions'];
}

$avgScore = $totalStudents > 0 ? $totalScore / $totalStudents : 0;
$avgCorrect = $totalStudents > 0 ? $totalCorrect / $totalStudents : 0;
$avgPercent = $maxScore > 0 ? ($avgScore / $maxScore) * 100 : 0;
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>İmtahan Nəticələri</h1>
        <p class="lead"><?php echo $exam['subjectname']; ?> - <?php echo $exam['group_number']; ?></p>
        <p>Tarix: <?php echo date('d.m.Y', strtotime($exam['date_exam'])); ?>, Saat: <?php echo date('H:i', strtotime($exam['datetime'])); ?></p>
    </div>
    <div class="col-md-6 text-end">
        <a href="exams.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> İmtahanlara qayıt
        </a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Tələbə sayı</h5>
                <p class="card-text display-4"><?php echo $totalStudents; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Orta bal</h5>
                <p class="card-text display-4"><?php echo number_format($avgScore, 2); ?></p>
                <p><?php echo number_format($avgPercent, 2); ?>%</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Orta düzgün</h5>
                <p class="card-text display-4"><?php echo number_format($avgCorrect, 2); ?></p>
                <p><?php echo $totalStudents > 0 ? number_format(($totalCorrect / $totalQuestions) * 100, 2) : 0; ?>%</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <h5 class="card-title">Maksimum bal</h5>
                <p class="card-text display-4"><?php echo number_format($maxScore, 2); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Tələbə nəticələri</h5>
    </div>
    <div class="card-body">
        <?php if (empty($results)): ?>
            <div class="alert alert-info">Bu imtahanda iştirak edən tələbə yoxdur.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Ad</th>
                            <th>İstifadəçi adı</th>
                            <th>Düzgün cavablar</th>
                            <th>Bal</th>
                            <th>Faiz</th>
                            <th>Detal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        foreach ($results as $result): 
                            $percentage = ($result['total_score'] / $result['max_score']) * 100;
                            $resultClass = '';
                            
                            if ($percentage >= 85) {
                                $resultClass = 'table-success';
                            } elseif ($percentage >= 70) {
                                $resultClass = 'table-info';
                            } elseif ($percentage >= 50) {
                                $resultClass = 'table-warning';
                            } else {
                                $resultClass = 'table-danger';
                            }
                        ?>
                            <tr class="<?php echo $resultClass; ?>">
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo $result['f_name']; ?></td>
                                <td><?php echo $result['username']; ?></td>
                                <td><?php echo $result['correct_answers']; ?> / <?php echo $result['total_questions']; ?></td>
                                <td><?php echo number_format($result['total_score'], 2); ?> / <?php echo number_format($result['max_score'], 2); ?></td>
                                <td><?php echo number_format($percentage, 2); ?>%</td>
                                <td>
                                    <a href="student_answers.php?exam=<?php echo $exam_id; ?>&student=<?php echo $result['id_users']; ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-search"></i> Detal
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?>