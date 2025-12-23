<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

checkLogin();
if (!is_admin()) { // Only admin can manage groups
    header("Location: dashboard.php?error=access_denied");
    exit();
}

$pageTitle = "Qrup İdarəetməsi";
include_once "../includes/header.php";

$database = new Database();
$db = $database->getConnection();

// Qrup əlavə etmək
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_group'])) {
    if (!is_admin()) {
        $message = '<div class="alert alert-danger">Bu əməliyyatı etməyə icazəniz yoxdur!</div>';
    } else {
        $group_number = trim($_POST['group_number']);
        
        if (!empty($group_number)) {
            try {
                $query = "INSERT INTO student_group (group_number) VALUES (:group_number)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":group_number", $group_number);
                
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">Qrup uğurla əlavə edildi!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Qrup əlavə edilərkən xəta baş verdi!</div>';
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $message = '<div class="alert alert-danger">Bu qrup nömrəsi artıq mövcuddur!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Verilənlər bazası xətası: ' . $e->getMessage() . '</div>';
                }
            }
        } else {
            $message = '<div class="alert alert-danger">Qrup nömrəsi boş ola bilməz!</div>';
        }
    }
}

// Qrup silmək
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (!is_admin()) {
        $message = '<div class="alert alert-danger">Bu əməliyyatı etməyə icazəniz yoxdur!</div>';
    } else {
        $id = $_GET['delete'];
        
        try {
            // Əvvəlcə yoxlamaq lazımdır ki, qrupda tələbə varmı
            $query = "SELECT COUNT(*) as student_count FROM users WHERE group_id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            $student_count = $stmt->fetch(PDO::FETCH_ASSOC)['student_count'];
            
            if ($student_count > 0) {
                $message = '<div class="alert alert-danger">Qrupda tələbələr var, silmək mümkün deyil!</div>';
            } else {
                $query = "DELETE FROM student_group WHERE id_student_group = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id", $id);
                
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">Qrup uğurla silindi!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Qrup silinərkən xəta baş verdi!</div>';
                }
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Verilənlər bazası xətası: ' . $e->getMessage() . '</div>';
        }
    }
}

// Qrupları çəkmək
$query = "SELECT sg.id_student_group, sg.group_number, COUNT(u.id_users) as student_count 
          FROM student_group sg
          LEFT JOIN users u ON sg.id_student_group = u.group_id
          GROUP BY sg.id_student_group
          ORDER BY sg.group_number";
$stmt = $db->prepare($query);
$stmt->execute();
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>Qrupların İdarə Edilməsi</h1>
    </div>
    <div class="col-md-6 text-end">
        <?php if (is_admin()): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGroupModal">
                <i class="bi bi-plus-circle"></i> Yeni Qrup
            </button>
        <?php endif; ?>
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
                        <th>Qrup Nömrəsi</th>
                        <th>Tələbə Sayı</th>
                        <th>Əməliyyatlar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($groups)): ?>
                        <tr>
                            <td colspan="4" class="text-center">Qeydə alınmış qrup yoxdur</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($groups as $group): ?>
                            <tr>
                                <td><?php echo $group['id_student_group']; ?></td>
                                <td><?php echo htmlspecialchars($group['group_number']); ?></td>
                                <td><?php echo $group['student_count']; ?></td>
                                <td>
                                    <a href="users.php?group=<?php echo $group['id_student_group']; ?>" class="btn btn-sm btn-info">
                                        <i class="bi bi-people"></i> Tələbələr
                                    </a>
                                    <?php if (is_admin() && $group['student_count'] == 0): ?>
                                        <a href="?delete=<?php echo $group['id_student_group']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu qrupu silmək istədiyinizə əminsiniz?')">
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

<?php if (is_admin()): ?>
<!-- Qrup Əlavə Et Modal -->
<div class="modal fade" id="addGroupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Qrup Əlavə Et</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="group_number" class="form-label">Qrup Nömrəsi</label>
                        <input type="text" class="form-control" id="group_number" name="group_number" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv et</button>
                    <button type="submit" name="add_group" class="btn btn-primary">Əlavə et</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include_once "../includes/footer.php"; ?>