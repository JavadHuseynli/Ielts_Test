<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

checkLogin(); // Ensure user is logged in
// Allow all admin roles to access dashboard
if (!is_admin() && !is_prorektor() && !is_kafedra() && !is_teacher()) {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Admin Paneli";
include_once "../includes/header.php";

$database = new Database();
$db = $database->getConnection();

// Determine user's role for dynamic content
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$user_subject_id = isset($_SESSION['subject_id']) ? $_SESSION['subject_id'] : null;
$user_group_id = isset($_SESSION['group_id']) ? $_SESSION['group_id'] : null; // For student view

// Initialize counts
$studentCount = 0;
$groupCount = 0;
$subjectCount = 0;
$examCount = 0;
$recentExams = [];

// Base queries for counts
$base_student_query = "SELECT COUNT(*) as total FROM users WHERE status = 'student'";
$base_group_query = "SELECT COUNT(*) as total FROM student_group";
$base_subject_query = "SELECT COUNT(*) as total FROM subjects";
$base_exam_query = "SELECT COUNT(*) as total FROM exams";

// Adjust queries based on role
if (is_admin() || is_prorektor() || is_kafedra()) {
    // Admin, Prorektor, Kafedra see all counts
    $stmt = $db->prepare($base_student_query); $stmt->execute(); $studentCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $stmt = $db->prepare($base_group_query); $stmt->execute(); $groupCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $stmt = $db->prepare($base_subject_query); $stmt->execute(); $subjectCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $stmt = $db->prepare($base_exam_query); $stmt->execute(); $examCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Recent exams for Admin, Prorektor, Kafedra
    $query = "SELECT e.id_exam, e.date_exam, e.datetime, s.subjectname, sg.group_number, 
              CASE 
                  WHEN e.status = 'pending' THEN 'Gözləyir'
                  WHEN e.status = 'in_progress' THEN 'Davam edir'
                  WHEN e.status = 'completed' THEN 'Tamamlanıb'
              END as status_name
              FROM exams e
              JOIN subjects s ON e.id_subject = s.id_subject
              JOIN student_group sg ON e.id_student_group = sg.id_student_group
              ORDER BY e.datetime DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recentExams = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif (is_teacher()) {
    // Teacher sees counts and exams related to their subject
    if ($user_subject_id) {
        $stmt = $db->prepare($base_subject_query . " WHERE id_subject = :subject_id"); $stmt->bindParam(":subject_id", $user_subject_id); $stmt->execute(); $subjectCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $stmt = $db->prepare($base_exam_query . " WHERE id_subject = :subject_id"); $stmt->bindParam(":subject_id", $user_subject_id); $stmt->execute(); $examCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $query = "SELECT e.id_exam, e.date_exam, e.datetime, s.subjectname, sg.group_number, 
                  CASE 
                      WHEN e.status = 'pending' THEN 'Gözləyir'
                      WHEN e.status = 'in_progress' THEN 'Davam edir'
                      WHEN e.status = 'completed' THEN 'Tamamlanıb'
                  END as status_name
                  FROM exams e
                  JOIN subjects s ON e.id_subject = s.id_subject
                  JOIN student_group sg ON e.id_student_group = sg.id_student_group
                  WHERE e.id_subject = :subject_id
                  ORDER BY e.datetime DESC LIMIT 5";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":subject_id", $user_subject_id);
        $stmt->execute();
        $recentExams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">Admin Paneli</h1>
    </div>
</div>

<div class="row mb-4">
    <?php if (is_admin() || is_prorektor() || is_kafedra()): // Admin, Prorektor, Kafedra gorur ?>
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Tələbələr</h5>
                <p class="card-text display-4"><?php echo $studentCount; ?></p>
                <?php if (is_admin()): ?><a href="users.php" class="btn btn-light btn-sm">İdarə et</a><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Qruplar</h5>
                <p class="card-text display-4"><?php echo $groupCount; ?></p>
                <?php if (is_admin()): ?><a href="groups.php" class="btn btn-light btn-sm">İdarə et</a><?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (is_admin() || is_kafedra() || is_teacher()): // Admin, Kafedra, Muellim gorur ?>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Fənlər</h5>
                <p class="card-text display-4"><?php echo $subjectCount; ?></p>
                <?php if (is_admin() || is_kafedra()): ?><a href="subjects.php" class="btn btn-light btn-sm">İdarə et</a><?php endif; ?>
                <?php if (is_teacher() && $user_subject_id): ?><a href="questions.php?subject=<?php echo $user_subject_id; ?>" class="btn btn-light btn-sm">Suallara Bax</a><?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (is_admin() || is_prorektor() || is_kafedra() || is_teacher()): // Hamı görur imtahan sayını, amma idarə etməsi roluna görə ?>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <h5 class="card-title">İmtahanlar</h5>
                <p class="card-text display-4"><?php echo $examCount; ?></p>
                <?php if (is_admin() || is_prorektor()): ?><a href="exams.php" class="btn btn-dark btn-sm">İdarə et</a><?php endif; ?>
                <?php if (is_kafedra() || is_teacher()): ?><a href="exam_results.php" class="btn btn-dark btn-sm">Nəticələrə Bax</a><?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Son imtahanlar</h5>
                <?php if (is_admin() || is_prorektor() || is_kafedra()): ?><a href="exams.php" class="btn btn-sm btn-primary">Bütün imtahanlar</a><?php endif; ?>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>İD</th>
                                <th>Fənn</th>
                                <th>Qrup</th>
                                <th>Tarix</th>
                                <th>Saat</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentExams)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">İmtahan tapılmadı</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentExams as $exam): ?>
                                    <tr>
                                        <td><?php echo $exam['id_exam']; ?></td>
                                        <td><?php echo $exam['subjectname']; ?></td>
                                        <td><?php echo $exam['group_number']; ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($exam['date_exam'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($exam['datetime'])); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = 'bg-secondary';
                                            if ($exam['status_name'] == 'Gözləyir') {
                                                $statusClass = 'bg-warning text-dark';
                                            } else if ($exam['status_name'] == 'Davam edir') {
                                                $statusClass = 'bg-success';
                                            } else if ($exam['status_name'] == 'Tamamlanıb') {
                                                $statusClass = 'bg-primary';
                                            }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo $exam['status_name']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?>