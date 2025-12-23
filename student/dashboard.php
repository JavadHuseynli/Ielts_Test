<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";
checkLogin();

// Yalnız tələbələr girə bilər
if ($_SESSION['status'] != 'student') {
    header("Location: ../admin/dashboard.php");
    exit();
}

$pageTitle = "Tələbə Paneli";
include_once "../includes/header.php";

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];
$group_id = $_SESSION['group_id'];

// Tələbə məlumatlarını almaq
$query = "SELECT u.f_name, u.username, sg.group_number
          FROM users u
          LEFT JOIN student_group sg ON u.group_id = sg.id_student_group
          WHERE u.id_users = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Qarşıdakı imtahanları almaq
$query = "SELECT e.id_exam, e.date_exam, e.datetime, s.subjectname, s.timer
          FROM exams e
          JOIN subjects s ON e.id_subject = s.id_subject
          WHERE e.id_student_group = :group_id AND e.datetime >= NOW() AND e.status = 'pending'
          ORDER BY e.datetime ASC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(":group_id", $group_id);
$stmt->execute();
$upcoming_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Keçmiş imtahan nəticələrini almaq
$query = "SELECT e.id_exam, e.date_exam, s.subjectname, 
          COUNT(a.id_answer) as total_questions,
          SUM(a.is_correct) as correct_answers,
          SUM(qr.question_score * a.is_correct) as score
          FROM exams e
          JOIN subjects s ON e.id_subject = s.id_subject
          JOIN answers a ON e.id_exam = a.exam_id
          JOIN question_read qr ON a.id_questions = qr.id_question_text
          WHERE e.id_student_group = :group_id AND a.user_id = :user_id AND e.status = 'completed'
          GROUP BY e.id_exam
          ORDER BY e.datetime DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(":group_id", $group_id);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$exam_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>Xoş gəlmisiniz, <?php echo $student['f_name']; ?>!</h1>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Tələbə məlumatları</h5>
            </div>
            <div class="card-body">
                <p><strong>Ad:</strong> <?php echo $student['f_name']; ?></p>
                <p><strong>İstifadəçi adı:</strong> <?php echo $student['username']; ?></p>
                <p><strong>Qrup:</strong> <?php echo $student['group_number']; ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Qarşıdan gələn imtahanlar</h5>
                <a href="exams.php" class="btn btn-sm btn-dark">Bütün imtahanlar</a>
            </div>
            <div class="card-body">
                <?php if (empty($upcoming_exams)): ?>
                    <p class="text-center">Yaxın zamanda imtahanınız yoxdur.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Fənn</th>
                                    <th>Tarix</th>
                                    <th>Saat</th>
                                    <th>Müddət</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming_exams as $exam): ?>
                                    <tr>
                                        <td><?php echo $exam['subjectname']; ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($exam['date_exam'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($exam['datetime'])); ?></td>
                                        <td><?php echo $exam['timer']; ?> dəq</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Son imtahan nəticələri</h5>
                <a href="exams.php?tab=results" class="btn btn-sm btn-light">Bütün nəticələr</a>
            </div>
            <div class="card-body">
                <?php if (empty($exam_results)): ?>
                    <p class="text-center">Keçmiş imtahan nəticəsi yoxdur.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Fənn</th>
                                    <th>Tarix</th>
                                    <th>Düzgün</th>
                                    <th>Bal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($exam_results as $result): ?>
                                    <tr>
                                        <td><?php echo $result['subjectname']; ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($result['date_exam'])); ?></td>
                                        <td><?php echo $result['correct_answers']; ?> / <?php echo $result['total_questions']; ?></td>
                                        <td><?php echo number_format($result['score'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?>