<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";
checkAdminRights();

$pageTitle = "İmtahan İdarəetməsi";
include_once "../includes/header.php";

$database = new Database();
$db = $database->getConnection();

// Mesaj dəyişəni
$message = "";

// Fənnləri almaq
$query = "SELECT id_subject, subjectname FROM subjects ORDER BY subjectname";
$stmt = $db->prepare($query);
$stmt->execute();
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Qrupları almaq
$query = "SELECT id_student_group, group_number FROM student_group ORDER BY group_number";
$stmt = $db->prepare($query);
$stmt->execute();
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// İmtahan əlavə etmək
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_exam'])) {
    $subject_id = $_POST['subject_id'];
    $group_id = $_POST['group_id'];
    $exam_date = $_POST['exam_date'];
    $exam_time = $_POST['exam_time'];
    
    // Tarix və vaxtı birləşdirmək
    $datetime = $exam_date . ' ' . $exam_time . ':00';
    
    try {
        $query = "INSERT INTO exams (date_exam, id_subject, id_student_group, datetime, status) 
                  VALUES (:date_exam, :subject_id, :group_id, :datetime, 'pending')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":date_exam", $exam_date);
        $stmt->bindParam(":subject_id", $subject_id);
        $stmt->bindParam(":group_id", $group_id);
        $stmt->bindParam(":datetime", $datetime);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">İmtahan uğurla əlavə edildi!</div>';
        } else {
            $message = '<div class="alert alert-danger">İmtahan əlavə edilərkən xəta baş verdi!</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Verilənlər bazası xətası: ' . $e->getMessage() . '</div>';
    }
}

// İmtahan statusunu dəyişmək
if (isset($_GET['start']) && is_numeric($_GET['start'])) {
    $id = $_GET['start'];
    
    try {
        $query = "UPDATE exams SET status = 'in_progress' WHERE id_exam = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">İmtahan başladıldı!</div>';
        } else {
            $message = '<div class="alert alert-danger">İmtahan başladılarkən xəta baş verdi!</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Verilənlər bazası xətası: ' . $e->getMessage() . '</div>';
    }
}

if (isset($_GET['complete']) && is_numeric($_GET['complete'])) {
    $id = $_GET['complete'];
    
    try {
        $query = "UPDATE exams SET status = 'completed' WHERE id_exam = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">İmtahan tamamlandı!</div>';
        } else {
            $message = '<div class="alert alert-danger">İmtahan tamamlanarkən xəta baş verdi!</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Verilənlər bazası xətası: ' . $e->getMessage() . '</div>';
    }
}

// İmtahan silmək
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        // İmtahanda cavablar varmı yoxlamaq
        $query = "SELECT COUNT(*) as answer_count FROM answers WHERE exam_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        $answer_count = $stmt->fetch(PDO::FETCH_ASSOC)['answer_count'];
        
        if ($answer_count > 0) {
            $message = '<div class="alert alert-danger">Bu imtahanda cavablar var, silmək mümkün deyil!</div>';
        } else {
            $query = "DELETE FROM exams WHERE id_exam = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $id);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">İmtahan uğurla silindi!</div>';
            } else {
                $message = '<div class="alert alert-danger">İmtahan silinərkən xəta baş verdi!</div>';
            }
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Verilənlər bazası xətası: ' . $e->getMessage() . '</div>';
    }
}

// Filtrlər
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$subject_filter = isset($_GET['subject']) ? $_GET['subject'] : '';
$group_filter = isset($_GET['group']) ? $_GET['group'] : '';

// Sorğunu hazırlamaq
$query = "SELECT e.id_exam, e.date_exam, e.datetime, e.status, s.subjectname, sg.group_number
          FROM exams e
          JOIN subjects s ON e.id_subject = s.id_subject
          JOIN student_group sg ON e.id_student_group = sg.id_student_group
          WHERE 1=1";

$params = array();

if (!empty($status_filter)) {
    $query .= " AND e.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($subject_filter)) {
    $query .= " AND e.id_subject = :subject_id";
    $params[':subject_id'] = $subject_filter;
}

if (!empty($group_filter)) {
    $query .= " AND e.id_student_group = :group_id";
    $params[':group_id'] = $group_filter;
}

$query .= " ORDER BY e.datetime DESC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>İmtahanların İdarə Edilməsi</h1>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExamModal">
            <i class="bi bi-plus-circle"></i> Yeni İmtahan
        </button>
    </div>
</div>

<?php echo $message; ?>

<!-- Filtrlər -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="" class="row g-3">
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">Hamısı</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Gözləyir</option>
                    <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>Davam edir</option>
                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Tamamlanıb</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="subject" class="form-label">Fənn</label>
                <select name="subject" id="subject" class="form-select">
                    <option value="">Hamısı</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['id_subject']; ?>" <?php echo $subject_filter == $subject['id_subject'] ? 'selected' : ''; ?>>
                            <?php echo $subject['subjectname']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="group" class="form-label">Qrup</label>
                <select name="group" id="group" class="form-select">
                    <option value="">Hamısı</option>
                    <?php foreach ($groups as $group): ?>
                        <option value="<?php echo $group['id_student_group']; ?>" <?php echo $group_filter == $group['id_student_group'] ? 'selected' : ''; ?>>
                            <?php echo $group['group_number']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">Filtr</button>
                <a href="exams.php" class="btn btn-secondary">Sıfırla</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fənn</th>
                        <th>Qrup</th>
                        <th>Tarix</th>
                        <th>Saat</th>
                        <th>Status</th>
                        <th>Əməliyyatlar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($exams)): ?>
                        <tr>
                            <td colspan="7" class="text-center">İmtahan tapılmadı</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($exams as $exam): ?>
                            <tr>
                                <td><?php echo $exam['id_exam']; ?></td>
                                <td><?php echo $exam['subjectname']; ?></td>
                                <td><?php echo $exam['group_number']; ?></td>
                                <td><?php echo date('d.m.Y', strtotime($exam['date_exam'])); ?></td>
                                <td><?php echo date('H:i', strtotime($exam['datetime'])); ?></td>
                                <td>
                                    <?php
                                    $statusText = '';
                                    $statusClass = '';
                                    
                                    if ($exam['status'] == 'pending') {
                                        $statusText = 'Gözləyir';
                                        $statusClass = 'bg-warning text-dark';
                                    } elseif ($exam['status'] == 'in_progress') {
                                        $statusText = 'Davam edir';
                                        $statusClass = 'bg-success';
                                    } elseif ($exam['status'] == 'completed') {
                                        $statusText = 'Tamamlanıb';
                                        $statusClass = 'bg-primary';
                                    }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                </td>
                                <td>
                                    <?php if ($exam['status'] == 'pending'): ?>
                                        <a href="?start=<?php echo $exam['id_exam']; ?>" class="btn btn-sm btn-success" onclick="return confirm('İmtahanı başlatmaq istədiyinizə əminsiniz?')">
                                            <i class="bi bi-play"></i> Başlat
                                        </a>
                                        <a href="?delete=<?php echo $exam['id_exam']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu imtahanı silmək istədiyinizə əminsiniz?')">
                                            <i class="bi bi-trash"></i> Sil
                                        </a>
                                    <?php elseif ($exam['status'] == 'in_progress'): ?>
                                        <a href="?complete=<?php echo $exam['id_exam']; ?>" class="btn btn-sm btn-primary" onclick="return confirm('İmtahanı tamamlamaq istədiyinizə əminsiniz?')">
                                            <i class="bi bi-check-circle"></i> Tamamla
                                        </a>
                                    <?php elseif ($exam['status'] == 'completed'): ?>
                                        <a href="exam_results.php?id=<?php echo $exam['id_exam']; ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-graph-up"></i> Nəticələr
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

<!-- İmtahan Əlavə Et Modal -->
<div class="modal fade" id="addExamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni İmtahan Əlavə Et</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="subject_id" class="form-label">Fənn</label>
                        <select class="form-select" id="subject_id" name="subject_id" required>
                            <option value="">Seçin</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id_subject']; ?>"><?php echo $subject['subjectname']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="group_id" class="form-label">Qrup</label>
                        <select class="form-select" id="group_id" name="group_id" required>
                            <option value="">Seçin</option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?php echo $group['id_student_group']; ?>"><?php echo $group['group_number']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="exam_date" class="form-label">Tarix</label>
                        <input type="date" class="form-control" id="exam_date" name="exam_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="exam_time" class="form-label">Saat</label>
                        <input type="time" class="form-control" id="exam_time" name="exam_time" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv et</button>
                    <button type="submit" name="add_exam" class="btn btn-primary">Əlavə et</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?>