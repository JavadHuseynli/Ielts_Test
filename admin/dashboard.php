<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";
checkAdminRights();

$pageTitle = "Admin Paneli";
include_once "../includes/header.php";

// Məlumatlar bazasından statistika üçün sorğular
$database = new Database();
$db = $database->getConnection();

// Ümumi tələbə sayı
$query = "SELECT COUNT(*) as total FROM users WHERE status = 'student'";
$stmt = $db->prepare($query);
$stmt->execute();
$studentCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Ümumi qrup sayı
$query = "SELECT COUNT(*) as total FROM student_group";
$stmt = $db->prepare($query);
$stmt->execute();
$groupCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Ümumi fənn sayı
$query = "SELECT COUNT(*) as total FROM subjects";
$stmt = $db->prepare($query);
$stmt->execute();
$subjectCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Ümumi imtahan sayı
$query = "SELECT COUNT(*) as total FROM exams";
$stmt = $db->prepare($query);
$stmt->execute();
$examCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Son 5 imtahan
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
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">Admin Paneli</h1>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Tələbələr</h5>
                <p class="card-text display-4"><?php echo $studentCount; ?></p>
                <a href="users.php" class="btn btn-light btn-sm">İdarə et</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Qruplar</h5>
                <p class="card-text display-4"><?php echo $groupCount; ?></p>
                <a href="groups.php" class="btn btn-light btn-sm">İdarə et</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Fənlər</h5>
                <p class="card-text display-4"><?php echo $subjectCount; ?></p>
                <a href="subjects.php" class="btn btn-light btn-sm">İdarə et</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <h5 class="card-title">İmtahanlar</h5>
                <p class="card-text display-4"><?php echo $examCount; ?></p>
                <a href="exams.php" class="btn btn-dark btn-sm">İdarə et</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Son imtahanlar</h5>
                <a href="exams.php" class="btn btn-sm btn-primary">Bütün imtahanlar</a>
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