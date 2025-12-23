<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";
checkAdminRights();

$pageTitle = "Fənn İdarəetməsi";
include_once "../includes/header.php";

$database = new Database();
$db = $database->getConnection();

// Fənn əlavə etmək
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_subject'])) {
    $subjectname = trim($_POST['subjectname']);
    $timer = trim($_POST['timer']);
    
    if (!empty($subjectname) && is_numeric($timer)) {
        try {
            $query = "INSERT INTO subjects (subjectname, timer) VALUES (:subjectname, :timer)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":subjectname", $subjectname);
            $stmt->bindParam(":timer", $timer);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Fənn uğurla əlavə edildi!</div>';
            } else {
                $message = '<div class="alert alert-danger">Fənn əlavə edilərkən xəta baş verdi!</div>';
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Verilənlər bazası xətası: ' . $e->getMessage() . '</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Bütün məlumatları düzgün doldurun!</div>';
    }
}

// Fənn silmək
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        // İlk öncə fənin imtahanlarda istifadə edilib-edilmədiyini yoxlamaq
        $query = "SELECT COUNT(*) as exam_count FROM exams WHERE id_subject = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        $exam_count = $stmt->fetch(PDO::FETCH_ASSOC)['exam_count'];
        
        if ($exam_count > 0) {
            $message = '<div class="alert alert-danger">Bu fənn imtahanlarda istifadə olunub, silmək mümkün deyil!</div>';
        } else {
            $query = "DELETE FROM subjects WHERE id_subject = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $id);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Fənn uğurla silindi!</div>';
            } else {
                $message = '<div class="alert alert-danger">Fənn silinərkən xəta baş verdi!</div>';
            }
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Verilənlər bazası xətası: ' . $e->getMessage() . '</div>';
    }
}

// Fənnləri çəkmək
$query = "SELECT s.id_subject, s.subjectname, s.timer, 
          COUNT(DISTINCT qf.id_read_quest_file) as file_count,
          COUNT(DISTINCT qr.id_question_text) as question_count,
          COUNT(DISTINCT e.id_exam) as exam_count
          FROM subjects s
          LEFT JOIN question_files qf ON s.id_subject = qf.subject_id
          LEFT JOIN question_read qr ON qf.id_read_quest_file = qr.id_read_quest_file
          LEFT JOIN exams e ON s.id_subject = e.id_subject
          GROUP BY s.id_subject
          ORDER BY s.subjectname";
$stmt = $db->prepare($query);
$stmt->execute();
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>Fənnlərin İdarə Edilməsi</h1>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
            <i class="bi bi-plus-circle"></i> Yeni Fənn
        </button>
    </div>
</div>

<?php echo $message; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fənn adı</th>
                        <th>Vaxt (dəq)</th>
                        <th>Fayl sayı</th>
                        <th>Sual sayı</th>
                        <th>İmtahan sayı</th>
                        <th>Əməliyyatlar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subjects)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Qeydə alınmış fənn yoxdur</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($subjects as $subject): ?>
                            <tr>
                                <td><?php echo $subject['id_subject']; ?></td>
                                <td><?php echo $subject['subjectname']; ?></td>
                                <td><?php echo $subject['timer']; ?></td>
                                <td><?php echo $subject['file_count']; ?></td>
                                <td><?php echo $subject['question_count']; ?></td>
                                <td><?php echo $subject['exam_count']; ?></td>
                                <td>
                                    <a href="questions.php?subject=<?php echo $subject['id_subject']; ?>" class="btn btn-sm btn-info">
                                        <i class="bi bi-list-check"></i> Suallar
                                    </a>
                                    <?php if ($subject['exam_count'] == 0): ?>
                                        <a href="?delete=<?php echo $subject['id_subject']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu fənni silmək istədiyinizə əminsiniz?')">
                                            <i class="bi bi-trash"></i> Sil
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Fənn Əlavə Et Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Fənn Əlavə Et</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="subjectname" class="form-label">Fənn adı</label>
                        <input type="text" class="form-control" id="subjectname" name="subjectname" required>
                    </div>
                    <div class="mb-3">
                        <label for="timer" class="form-label">İmtahan vaxtı (dəqiqə)</label>
                        <input type="number" class="form-control" id="timer" name="timer" min="1" max="300" value="60" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv et</button>
                    <button type="submit" name="add_subject" class="btn btn-primary">Əlavə et</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?>