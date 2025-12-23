<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";
checkAdminRights();

$pageTitle = "İstifadəçi İdarəetməsi";
include_once "../includes/header.php";

$database = new Database();
$db = $database->getConnection();

// Mesaj dəyişəni
$message = "";

// Qrupları çəkmək (select üçün)
$query = "SELECT id_student_group, group_number FROM student_group ORDER BY group_number";
$stmt = $db->prepare($query);
$stmt->execute();
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// İstifadəçi əlavə etmək
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $f_name = trim($_POST['f_name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $status = $_POST['status'];
    $group_id = ($status == 'admin' || empty($_POST['group_id'])) ? null : $_POST['group_id'];
    
    if (!empty($f_name) && !empty($username) && !empty($password)) {
        try {
            // Şifrəni hash etmək
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO users (f_name, username, password, status, group_id) 
                      VALUES (:f_name, :username, :password, :status, :group_id)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":f_name", $f_name);
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":password", $hashed_password);
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":group_id", $group_id);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">İstifadəçi uğurla əlavə edildi!</div>';
            } else {
                $message = '<div class="alert alert-danger">İstifadəçi əlavə edilərkən xəta baş verdi!</div>';
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = '<div class="alert alert-danger">Bu istifadəçi adı artıq mövcuddur!</div>';
            } else {
                $message = '<div class="alert alert-danger">Verilənlər bazası xətası: ' . $e->getMessage() . '</div>';
            }
        }
    } else {
        $message = '<div class="alert alert-danger">Bütün məlumatları doldurun!</div>';
    }
}

// İstifadəçi silmək
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Öz hesabını silməməli
    if ($id == $_SESSION['user_id']) {
        $message = '<div class="alert alert-danger">Öz hesabınızı silə bilməzsiniz!</div>';
    } else {
        try {
            $query = "DELETE FROM users WHERE id_users = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $id);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">İstifadəçi uğurla silindi!</div>';
            } else {
                $message = '<div class="alert alert-danger">İstifadəçi silinərkən xəta baş verdi!</div>';
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Verilənlər bazası xətası: ' . $e->getMessage() . '</div>';
        }
    }
}

// Filtrlər
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$group_filter = isset($_GET['group']) ? $_GET['group'] : '';

// Sorğunu hazırlamaq
$query = "SELECT u.id_users, u.f_name, u.username, u.status, sg.group_number
          FROM users u
          LEFT JOIN student_group sg ON u.group_id = sg.id_student_group
          WHERE 1=1";

$params = array();

if (!empty($status_filter)) {
    $query .= " AND u.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($group_filter)) {
    $query .= " AND u.group_id = :group_id";
    $params[':group_id'] = $group_filter;
}

$query .= " ORDER BY u.status, u.f_name";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>İstifadəçilərin İdarə Edilməsi</h1>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus"></i> Yeni İstifadəçi
        </button>
    </div>
</div>

<?php echo $message; ?>

<!-- Filtrlər -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="" class="row g-3">
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">Hamısı</option>
                    <option value="admin" <?php echo $status_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="student" <?php echo $status_filter == 'student' ? 'selected' : ''; ?>>Tələbə</option>
                </select>
            </div>
            <div class="col-md-4">
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
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">Filtr</button>
                <a href="users.php" class="btn btn-secondary">Sıfırla</a>
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
                        <th>Ad</th>
                        <th>İstifadəçi adı</th>
                        <th>Status</th>
                        <th>Qrup</th>
                        <th>Əməliyyatlar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="text-center">İstifadəçi tapılmadı</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id_users']; ?></td>
                                <td><?php echo $user['f_name']; ?></td>
                                <td><?php echo $user['username']; ?></td>
                                <td>
                                    <?php if ($user['status'] == 'admin'): ?>
                                        <span class="badge bg-danger">Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Tələbə</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $user['group_number'] ?: '-'; ?></td>
                                <td>
                                    <?php if ($user['id_users'] != $_SESSION['user_id']): ?>
                                        <a href="?delete=<?php echo $user['id_users']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu istifadəçini silmək istədiyinizə əminsiniz?')">
                                            <i class="bi bi-trash"></i> Sil
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Cari istifadəçi</span>
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

<!-- İstifadəçi Əlavə Et Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni İstifadəçi Əlavə Et</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="f_name" class="form-label">Ad</label>
                        <input type="text" class="form-control" id="f_name" name="f_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">İstifadəçi adı</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Şifrə</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required onchange="toggleGroupField()">
                            <option value="student">Tələbə</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3" id="groupField">
                        <label for="group_id" class="form-label">Qrup</label>
                        <select class="form-select" id="group_id" name="group_id">
                            <option value="">Seçin</option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?php echo $group['id_student_group']; ?>"><?php echo $group['group_number']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv et</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Əlavə et</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleGroupField() {
    var status = document.getElementById('status').value;
    var groupField = document.getElementById('groupField');
    
    if (status === 'admin') {
        groupField.style.display = 'none';
        document.getElementById('group_id').value = '';
    } else {
        groupField.style.display = 'block';
    }
}

// İlk yükləmədə çağır
document.addEventListener('DOMContentLoaded', toggleGroupField);
</script>

<?php include_once "../includes/footer.php"; ?>