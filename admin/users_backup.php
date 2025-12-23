<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";
checkAdminRights(); // I'll need to update this later

$pageTitle = "İstifadəçi İdarəetməsi";
include_once "../includes/header.php";

$database = new Database();
$db = $database->getConnection();

// Message variable
$message = "";

// Fetch groups for select dropdown
$query = "SELECT id_student_group, group_number FROM student_group ORDER BY group_number";
$stmt = $db->prepare($query);
$stmt->execute();
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch subjects for select dropdown
$query = "SELECT id_subject, subjectname FROM subjects ORDER BY subjectname";
$stmt = $db->prepare($query);
$stmt->execute();
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $f_name = trim($_POST['f_name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $status = $_POST['role'];
    $group_id = ($status == 'student' && !empty($_POST['group_id'])) ? $_POST['group_id'] : null;
    $subject_id = ($status == 'muellim' && !empty($_POST['subject_id'])) ? $_POST['subject_id'] : null;

    if (!empty($f_name) && !empty($username) && !empty($password)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $query = "INSERT INTO users (f_name, username, password, status, group_id, subject_id)
                      VALUES (:f_name, :username, :password, :status, :group_id, :subject_id)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":f_name", $f_name);
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":password", $hashed_password);
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":group_id", $group_id);
            $stmt->bindParam(":subject_id", $subject_id);

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

// Delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
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

// Filters
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$group_filter = isset($_GET['group']) ? $_GET['group'] : '';

// Prepare query
$query = "SELECT u.id_users, u.f_name, u.username, u.status as role, sg.group_number, s.subjectname
          FROM users u
          LEFT JOIN student_group sg ON u.group_id = sg.id_student_group
          LEFT JOIN subjects s ON u.subject_id = s.id_subject
          WHERE 1=1";

$params = array();

if (!empty($role_filter)) {
    $query .= " AND u.status = :role";
    $params[':role'] = $role_filter;
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

$roles = [
    'admin' => 'Admin',
    'prorektor' => 'Prorektor',
    'kafedra' => 'Kafedra',
    'muellim' => 'Müəllim',
    'student' => 'Tələbə'
];

$role_badges = [
    'admin' => 'danger',
    'prorektor' => 'info',
    'kafedra' => 'warning',
    'muellim' => 'primary',
    'student' => 'success'
];

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

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="" class="row g-3">
            <div class="col-md-4">
                <label for="role_filter" class="form-label">Rol</label>
                <select name="role" id="role_filter" class="form-select">
                    <option value="">Hamısı</option>
                    <?php foreach ($roles as $role_key => $role_name): ?>
                        <option value="<?php echo $role_key; ?>" <?php echo $role_filter == $role_key ? 'selected' : ''; ?>><?php echo $role_name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="group_filter" class="form-label">Qrup</label>
                <select name="group" id="group_filter" class="form-select">
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
                        <th>Rol</th>
                        <th>Qrup / Fənn</th>
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
                                    <span class="badge bg-<?php echo $role_badges[$user['role']] ?? 'secondary'; ?>">
                                        <?php echo $roles[$user['role']] ?? 'Naməlum'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($user['role'] == 'student') {
                                        echo 'Qrup: ' . ($user['group_number'] ?: '-');
                                    } elseif ($user['role'] == 'muellim') {
                                        echo 'Fənn: ' . ($user['subjectname'] ?: '-');
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
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

<!-- Add User Modal -->
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
                        <label for="role" class="form-label">Rol</label>
                        <select class="form-select" id="role" name="role" required onchange="toggleConditionalFields()">
                            <?php foreach ($roles as $role_key => $role_name): ?>
                                <option value="<?php echo $role_key; ?>"><?php echo $role_name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3" id="groupField" style="display: none;">
                        <label for="group_id" class="form-label">Qrup</label>
                        <select class="form-select" id="group_id" name="group_id">
                            <option value="">Seçin</option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?php echo $group['id_student_group']; ?>"><?php echo $group['group_number']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3" id="subjectField" style="display: none;">
                        <label for="subject_id" class="form-label">Fənn</label>
                        <select class="form-select" id="subject_id" name="subject_id">
                            <option value="">Seçin</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id_subject']; ?>"><?php echo $subject['subjectname']; ?></option>
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
function toggleConditionalFields() {
    var role = document.getElementById('role').value;
    var groupField = document.getElementById('groupField');
    var subjectField = document.getElementById('subjectField');

    if (role === 'student') {
        groupField.style.display = 'block';
        subjectField.style.display = 'none';
        document.getElementById('subject_id').value = '';
    } else if (role === 'muellim') {
        groupField.style.display = 'none';
        document.getElementById('group_id').value = '';
        subjectField.style.display = 'block';
    } else {
        groupField.style.display = 'none';
        subjectField.style.display = 'none';
        document.getElementById('group_id').value = '';
        document.getElementById('subject_id').value = '';
    }
}

// Call on page load and when the modal is shown
document.addEventListener('DOMContentLoaded', function() {
    var roleSelect = document.getElementById('role');
    if(roleSelect) {
        toggleConditionalFields();
    }
});
document.getElementById('addUserModal').addEventListener('shown.bs.modal', function () {
    // Set initial state when modal opens
    document.getElementById('role').value = 'student';
    toggleConditionalFields();
});
</script>

<?php include_once "../includes/footer.php"; ?>
