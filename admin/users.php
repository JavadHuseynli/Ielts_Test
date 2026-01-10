<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";
checkAdminRights();

$database = new Database();
$db = $database->getConnection();

// Delete user - MUST BE BEFORE header.php include to allow redirect
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];

    // Build redirect URL with filters
    $redirect_params = [];
    if (isset($_GET['role']) && !empty($_GET['role'])) {
        $redirect_params['role'] = $_GET['role'];
    }
    if (isset($_GET['group']) && !empty($_GET['group'])) {
        $redirect_params['group'] = $_GET['group'];
    }
    if (isset($_GET['page']) && $_GET['page'] > 1) {
        $redirect_params['page'] = $_GET['page'];
    }

    if ($id == $_SESSION['user_id']) {
        $redirect_params['error'] = 'self_delete';
        header("Location: users.php?" . http_build_query($redirect_params));
        exit();
    } else {
        try {
            $query = "DELETE FROM users WHERE id_users = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $id);
            if ($stmt->execute()) {
                $redirect_params['success'] = 'deleted';
                header("Location: users.php?" . http_build_query($redirect_params));
                exit();
            } else {
                $redirect_params['error'] = 'delete_failed';
                header("Location: users.php?" . http_build_query($redirect_params));
                exit();
            }
        } catch (PDOException $e) {
            $redirect_params['error'] = 'db_error';
            header("Location: users.php?" . http_build_query($redirect_params));
            exit();
        }
    }
}

$pageTitle = "İstifadəçi İdarəetməsi";
include_once "../includes/header.php";

// Message variable
$message = "";
$message_type = "";

// Fetch groups (only active groups)
$query = "SELECT id_student_group, group_number FROM student_group WHERE is_archived = 0 ORDER BY group_number";
$stmt = $db->prepare($query);
$stmt->execute();
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch subjects
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
    $subject_ids = ($status == 'muellim' && !empty($_POST['subject_ids'])) ? $_POST['subject_ids'] : [];

    if (!empty($f_name) && !empty($username) && !empty($password)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user without subject_id
            $query = "INSERT INTO users (f_name, username, password, status, group_id)
                      VALUES (:f_name, :username, :password, :status, :group_id)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":f_name", $f_name);
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":password", $hashed_password);
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":group_id", $group_id);

            if ($stmt->execute()) {
                $new_user_id = $db->lastInsertId();

                // If teacher, insert subjects into teacher_subjects table
                if ($status == 'muellim' && !empty($subject_ids)) {
                    $insert_subject_query = "INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (:teacher_id, :subject_id)";
                    $subject_stmt = $db->prepare($insert_subject_query);

                    foreach ($subject_ids as $subject_id) {
                        $subject_stmt->bindParam(":teacher_id", $new_user_id);
                        $subject_stmt->bindParam(":subject_id", $subject_id);
                        $subject_stmt->execute();
                    }
                }

                $message = 'İstifadəçi uğurla əlavə edildi!';
                $message_type = 'success';
            } else {
                $message = 'İstifadəçi əlavə edilə bilmədi!';
                $message_type = 'error';
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = 'Bu istifadəçi adı artıq mövcuddur!';
                $message_type = 'error';
            } else {
                $message = 'Verilənlər bazası xətası: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    } else {
        $message = 'Bütün məlumatları doldurun!';
        $message_type = 'error';
    }
}

// Edit user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_user'])) {
    $user_id = intval($_POST['edit_user_id']);
    $f_name = trim($_POST['edit_f_name']);
    $username = trim($_POST['edit_username']);
    $password = trim($_POST['edit_password']);
    $status = $_POST['edit_role'];
    $group_id = ($status == 'student' && !empty($_POST['edit_group_id'])) ? $_POST['edit_group_id'] : null;
    $subject_ids = ($status == 'muellim' && !empty($_POST['edit_subject_ids'])) ? $_POST['edit_subject_ids'] : [];

    if (!empty($f_name) && !empty($username)) {
        try {
            // Check if password needs to be updated
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET f_name = :f_name, username = :username, password = :password,
                          status = :status, group_id = :group_id
                          WHERE id_users = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":password", $hashed_password);
            } else {
                $query = "UPDATE users SET f_name = :f_name, username = :username,
                          status = :status, group_id = :group_id
                          WHERE id_users = :id";
                $stmt = $db->prepare($query);
            }

            $stmt->bindParam(":f_name", $f_name);
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":group_id", $group_id);
            $stmt->bindParam(":id", $user_id);

            if ($stmt->execute()) {
                // If teacher, update subjects in teacher_subjects table
                if ($status == 'muellim') {
                    // Delete existing subjects for this teacher
                    $delete_query = "DELETE FROM teacher_subjects WHERE teacher_id = :teacher_id";
                    $delete_stmt = $db->prepare($delete_query);
                    $delete_stmt->bindParam(":teacher_id", $user_id);
                    $delete_stmt->execute();

                    // Insert new subjects
                    if (!empty($subject_ids)) {
                        $insert_subject_query = "INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (:teacher_id, :subject_id)";
                        $subject_stmt = $db->prepare($insert_subject_query);

                        foreach ($subject_ids as $subject_id) {
                            $subject_stmt->bindParam(":teacher_id", $user_id);
                            $subject_stmt->bindParam(":subject_id", $subject_id);
                            $subject_stmt->execute();
                        }
                    }
                }

                $message = 'İstifadəçi uğurla yeniləndi!';
                $message_type = 'success';
            } else {
                $message = 'İstifadəçi yenilənə bilmədi!';
                $message_type = 'error';
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = 'Bu istifadəçi adı artıq mövcuddur!';
                $message_type = 'error';
            } else {
                $message = 'Verilənlər bazası xətası: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    } else {
        $message = 'Ad və istifadəçi adı doldurulmalıdır!';
        $message_type = 'error';
    }
}

// Handle success and error messages from redirects
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'deleted':
            $message = 'İstifadəçi uğurla silindi!';
            $message_type = 'success';
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'self_delete':
            $message = 'Öz hesabınızı silə bilməzsiniz!';
            $message_type = 'error';
            break;
        case 'delete_failed':
            $message = 'İstifadəçi silinə bilmədi!';
            $message_type = 'error';
            break;
        case 'db_error':
            $message = 'Verilənlər bazası xətası baş verdi!';
            $message_type = 'error';
            break;
    }
}

// Filters
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$group_filter = isset($_GET['group']) ? $_GET['group'] : '';

// Pagination
$items_per_page = 25;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Count query
$count_query = "SELECT COUNT(*) as total FROM users u
                LEFT JOIN student_group sg ON u.group_id = sg.id_student_group
                WHERE (sg.is_archived = 0 OR u.group_id IS NULL)";
$count_params = array();

if (!empty($role_filter)) {
    $count_query .= " AND u.status = :role";
    $count_params[':role'] = $role_filter;
}
if (!empty($group_filter)) {
    $count_query .= " AND u.group_id = :group_id";
    $count_params[':group_id'] = $group_filter;
}

$count_stmt = $db->prepare($count_query);
foreach ($count_params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_users = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_users / $items_per_page);

// Main query
$query = "SELECT u.id_users, u.f_name, u.username, u.status as role, u.group_id, sg.group_number,
          GROUP_CONCAT(DISTINCT s.subjectname SEPARATOR ', ') as subjectnames,
          GROUP_CONCAT(DISTINCT ts.subject_id) as subject_ids
          FROM users u
          LEFT JOIN student_group sg ON u.group_id = sg.id_student_group
          LEFT JOIN teacher_subjects ts ON u.id_users = ts.teacher_id
          LEFT JOIN subjects s ON ts.subject_id = s.id_subject
          WHERE (sg.is_archived = 0 OR u.group_id IS NULL)";

$params = array();

if (!empty($role_filter)) {
    $query .= " AND u.status = :role";
    $params[':role'] = $role_filter;
}
if (!empty($group_filter)) {
    $query .= " AND u.group_id = :group_id";
    $params[':group_id'] = $group_filter;
}

$query .= " GROUP BY u.id_users ORDER BY u.status, u.f_name LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$roles = [
    'admin' => 'Admin',
    'prorektor' => 'Prorektor',
    'dekan' => 'Dekan',
    'kafedra' => 'Kafedra',
    'muellim' => 'Müəllim',
    'student' => 'Tələbə'
];

$role_colors = [
    'admin' => 'from-red-500 to-pink-500',
    'prorektor' => 'from-blue-500 to-cyan-500',
    'dekan' => 'from-teal-500 to-cyan-500',
    'kafedra' => 'from-amber-500 to-orange-500',
    'muellim' => 'from-purple-500 to-pink-500',
    'student' => 'from-green-500 to-emerald-500'
];

$role_permissions = [
    'admin' => [
        'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        'title' => 'Tam İdarəetmə',
        'permissions' => [
            'Bütün istifadəçiləri idarə edə bilir',
            'Fənləri yarada və silə bilir',
            'İmtahanları yaradıb idarə edə bilir',
            'Bütün nəticələri görə bilir',
            'Sualları təsdiq edə bilir'
        ]
    ],
    'prorektor' => [
        'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
        'title' => 'Prorektor Səlahiyyətləri',
        'permissions' => [
            'Tələbələrin imtahan nəticələrini görə bilir',
            'Kafedranın bəzi səlahiyyətlərini icra edir',
            'İmtahanları idarə edə bilir',
            'Ümumi hesabatları görə bilir'
        ]
    ],
    'dekan' => [
        'icon' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z',
        'title' => 'Dekan Səlahiyyətləri',
        'permissions' => [
            'Tələbələrin imtahan nəticələrini görə bilir',
            'Fakültə üzrə hesabatları görə bilir',
            'İmtahan prosesini izləyə bilir',
            'Nəticələri ixrac edə bilir'
        ]
    ],
    'kafedra' => [
        'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
        'title' => 'Kafedra Səlahiyyətləri',
        'permissions' => [
            'Müəllimlərin təsdiq etdiyi sualları görə bilir',
            'Tələbələrin imtahan nəticələrini görə bilir',
            'Nəticələri ixrac edə bilir',
            'İmtahan prosesini izləyə bilir'
        ]
    ],
    'muellim' => [
        'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        'title' => 'Müəllim Səlahiyyətləri',
        'permissions' => [
            'Öz fənninə aid sualları görə bilir',
            'Sualları təsdiq edib doğrulaya bilir',
            'Fənnə aid imtahan nəticələrini görə bilir',
            'Sual əlavə edə bilir'
        ]
    ],
    'student' => [
        'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        'title' => 'Tələbə Səlahiyyətləri',
        'permissions' => [
            'İmtahan verə bilir',
            'Öz nəticələrini görə bilir',
            'İmtahan tarixçəsini görə bilir'
        ]
    ]
];
?>

<style>
.permission-card {
    transition: all 0.3s ease;
}

.permission-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.notification {
    animation: slideInDown 0.5s ease-out;
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-100%);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<div class="p-6" x-data="userManagement()">

    <!-- Notification -->
    <?php if (!empty($message)): ?>
    <div x-show="showNotification"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="notification fixed top-4 right-4 z-50 max-w-md">
        <div class="<?php echo $message_type === 'success' ? 'bg-gradient-to-r from-green-500 to-emerald-600' : 'bg-gradient-to-r from-red-500 to-pink-600'; ?> text-white px-6 py-4 rounded-xl shadow-2xl flex items-center space-x-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <?php if ($message_type === 'success'): ?>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                <?php else: ?>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                <?php endif; ?>
            </svg>
            <span class="font-medium"><?php echo $message; ?></span>
            <button @click="showNotification = false" class="ml-auto">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-4xl font-extrabold bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent mb-2">
                İstifadəçilərin İdarəsi
            </h1>
            <p class="text-gray-600 font-medium">Sistem istifadəçilərini və səlahiyyətlərini idarə edin</p>
        </div>
        <div class="flex gap-3">
            <button @click="showPermissions = !showPermissions"
                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-gray-700 to-gray-800 text-white font-semibold rounded-xl shadow-lg hover:shadow-gray-500/50 transition-all hover:scale-105">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Səlahiyyətlər
            </button>
            <button @click="showModal = true"
                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-purple-500/50 transition-all hover:scale-105">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Yeni İstifadəçi
            </button>
        </div>
    </div>

    <!-- Permissions Panel -->
    <div x-show="showPermissions"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-y-4"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="mb-8"
         style="display: none;">
        <div class="bg-gradient-to-r from-gray-900 to-gray-800 rounded-2xl p-6 border border-gray-700">
            <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                <svg class="w-6 h-6 mr-2 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                İstifadəçi Rolları və Səlahiyyətləri
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                <?php foreach ($role_permissions as $role_key => $permission): ?>
                <div class="permission-card bg-gray-800/50 rounded-xl p-5 border border-gray-700">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-12 h-12 bg-gradient-to-br <?php echo $role_colors[$role_key]; ?> rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $permission['icon']; ?>"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-white"><?php echo $roles[$role_key]; ?></h4>
                            <p class="text-xs text-gray-400"><?php echo $permission['title']; ?></p>
                        </div>
                    </div>
                    <ul class="space-y-2">
                        <?php foreach ($permission['permissions'] as $perm): ?>
                        <li class="flex items-start text-xs text-gray-300">
                            <svg class="w-4 h-4 text-green-400 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span><?php echo $perm; ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl p-6 mb-8 border border-gray-200 shadow-sm">
        <form method="get" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="role_filter" class="block text-sm font-medium text-gray-700 mb-2">Rol</label>
                <select name="role" id="role_filter" class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-gray-900 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                    <option value="">Hamısı</option>
                    <?php foreach ($roles as $role_key => $role_name): ?>
                        <option value="<?php echo $role_key; ?>" <?php echo $role_filter == $role_key ? 'selected' : ''; ?>><?php echo $role_name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="group_filter" class="block text-sm font-medium text-gray-700 mb-2">Qrup</label>
                <select name="group" id="group_filter" class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-gray-900 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                    <option value="">Hamısı</option>
                    <?php foreach ($groups as $group): ?>
                        <option value="<?php echo $group['id_student_group']; ?>" <?php echo $group_filter == $group['id_student_group'] ? 'selected' : ''; ?>>
                            <?php echo $group['group_number']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-5 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-medium rounded-xl hover:shadow-lg hover:shadow-purple-500/50 transition-all">
                    Filtr
                </button>
                <a href="users.php" class="px-5 py-3 bg-gray-100 text-gray-700 font-medium rounded-xl hover:bg-gray-200 transition">
                    Sıfırla
                </a>
            </div>
            <div class="flex items-end">
                <a href="export_users.php?<?php echo http_build_query(array_filter(['role' => $role_filter, 'group' => $group_filter])); ?>"
                   class="w-full px-5 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white font-medium rounded-xl hover:shadow-lg hover:shadow-green-500/50 transition-all inline-flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export
                </a>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-2xl overflow-hidden border border-gray-200 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">İstifadəçi</th>
                        <!-- <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">İstifadəçi adı</th> -->
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Rol</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Qrup / Fənn</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Əməliyyatlar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                <p class="text-sm font-medium text-gray-500">İstifadəçi tapılmadı</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50 transition-colors group">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-mono text-gray-600">#<?php echo $user['id_users']; ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gradient-to-br <?php echo $role_colors[$user['role']] ?? 'from-gray-500 to-gray-600'; ?> rounded-xl flex items-center justify-center text-white font-bold text-sm mr-3 shadow-lg group-hover:scale-110 transition-transform">
                                            <?php echo strtoupper(substr($user['f_name'], 0, 1)); ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo $user['f_name']; ?></div>
                                            <div class="text-sm text-gray-500"><?php echo $user['username']; ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-gradient-to-r <?php echo $role_colors[$user['role']] ?? 'from-gray-500 to-gray-600'; ?> shadow-lg">
                                        <?php echo $roles[$user['role']] ?? 'Naməlum'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php
                                    if ($user['role'] == 'student' && $user['group_number']) {
                                        echo '<span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-green-50 text-green-700 text-xs font-medium border border-green-200"><svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>' . $user['group_number'] . '</span>';
                                    } elseif ($user['role'] == 'muellim' && $user['subjectnames']) {
                                        echo '<span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-purple-50 text-purple-700 text-xs font-medium border border-purple-200"><svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>' . $user['subjectnames'] . '</span>';
                                    } else {
                                        echo '<span class="text-gray-400">-</span>';
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Edit Button - Material Design -->
                                        <button type="button"
                                                @click="openEdit(<?php echo $user['id_users']; ?>, '<?php echo htmlspecialchars($user['f_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>', '<?php echo $user['role']; ?>', <?php echo $user['group_id'] ?? 'null'; ?>, '<?php echo isset($user['subject_ids']) ? htmlspecialchars($user['subject_ids'], ENT_QUOTES) : ''; ?>')"
                                                class="group relative inline-flex items-center justify-center px-4 py-2.5 overflow-hidden font-medium text-indigo-600 transition duration-300 ease-out border-2 border-indigo-500 rounded-lg shadow-md hover:shadow-xl cursor-pointer">
                                            <span class="absolute inset-0 flex items-center justify-center w-full h-full text-white duration-300 -translate-x-full bg-indigo-500 group-hover:translate-x-0 ease pointer-events-none">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </span>
                                            <span class="absolute flex items-center justify-center w-full h-full text-indigo-600 transition-all duration-300 transform group-hover:translate-x-full ease font-semibold text-sm pointer-events-none">
                                                Dəyişdir
                                            </span>
                                            <span class="relative invisible pointer-events-none">Dəyişdir</span>
                                        </button>

                                        <?php if ($user['id_users'] != $_SESSION['user_id']): ?>
                                            <!-- Delete Button - Material Design -->
                                            <a href="?delete=<?php echo $user['id_users']; ?>"
                                               onclick="return confirm('Bu istifadəçini silmək istədiyinizə əminsiniz?')"
                                               class="group relative inline-flex items-center justify-center px-4 py-2.5 overflow-hidden font-medium text-red-600 transition duration-300 ease-out border-2 border-red-500 rounded-lg shadow-md hover:shadow-xl cursor-pointer">
                                                <span class="absolute inset-0 flex items-center justify-center w-full h-full text-white duration-300 -translate-x-full bg-red-500 group-hover:translate-x-0 ease pointer-events-none">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </span>
                                                <span class="absolute flex items-center justify-center w-full h-full text-red-600 transition-all duration-300 transform group-hover:translate-x-full ease font-semibold text-sm pointer-events-none">
                                                    Sil
                                                </span>
                                                <span class="relative invisible pointer-events-none">Sil</span>
                                            </a>
                                        <?php else: ?>
                                            <!-- Current User Badge -->
                                            <span class="inline-flex items-center px-4 py-2.5 bg-gray-100 text-gray-500 border-2 border-gray-300 rounded-lg cursor-not-allowed font-semibold text-sm">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                                Siz
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="py-4 flex items-center justify-between">
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    <span class="font-medium"><?php echo $total_users; ?></span> nəticədən
                    <span class="font-medium"><?php echo $offset + 1; ?></span> -
                    <span class="font-medium"><?php echo min($offset + $items_per_page, $total_users); ?></span> göstərilir
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php
                    $window = 2;
                    $show_first = $page > $window + 1;
                    $show_last = $page < $total_pages - $window;
                    ?>

                    <!-- Previous Button -->
                    <a href="?page=<?php echo max(1, $page - 1); ?>&<?php echo http_build_query(array_merge($_GET, ['page' => max(1, $page - 1)])); ?>"
                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Əvvəlki</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                    </a>

                    <?php if ($show_first): ?>
                        <a href="?page=1&<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">1</a>
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - $window); $i <= min($total_pages, $page + $window); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="<?php echo $i == $page ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($show_last): ?>
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                        <a href="?page=<?php echo $total_pages; ?>&<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium"><?php echo $total_pages; ?></a>
                    <?php endif; ?>

                    <!-- Next Button -->
                    <a href="?page=<?php echo min($total_pages, $page + 1); ?>&<?php echo http_build_query(array_merge($_GET, ['page' => min($total_pages, $page + 1)])); ?>"
                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Növbəti</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                    </a>
                </nav>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Add User Modal -->
    <div x-show="showModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="showModal = false"
         class="fixed inset-0 bg-gray-500 bg-opacity-75 z-50 flex items-center justify-center p-4">
        <div @click.stop class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-auto">
            <form method="post" action="">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Yeni İstifadəçi Yarat</h3>
                    <div class="mt-6 space-y-4">
                        <div>
                            <label for="f_name" class="block text-sm font-medium text-gray-700">Ad Soyad</label>
                            <input type="text" name="f_name" id="f_name" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-700">İstifadəçi adı</label>
                                <input type="text" name="username" id="username" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                            </div>
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700">Şifrə</label>
                                <input type="password" name="password" id="password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                            </div>
                        </div>
                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-700">Rol</label>
                            <select name="role" id="role" onchange="toggleFields()" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" required>
                                <?php foreach ($roles as $role_key => $role_name): ?>
                                    <option value="<?php echo $role_key; ?>"><?php echo $role_name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="groupField" class="hidden">
                            <label for="group_id" class="block text-sm font-medium text-gray-700">Qrup</label>
                            <select name="group_id" id="group_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="">Qrup seçin...</option>
                                <?php foreach ($groups as $group): ?>
                                    <option value="<?php echo $group['id_student_group']; ?>"><?php echo htmlspecialchars($group['group_number']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="subjectField" class="hidden">
                            <label for="subject_ids" class="block text-sm font-medium text-gray-700">Fənnlər (Çoxlu seçim mümkündür)</label>
                            <select name="subject_ids[]" id="subject_ids" multiple size="8" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id_subject']; ?>"><?php echo htmlspecialchars($subject['subjectname']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Ctrl/Cmd basaraq bir neçə fənn seçə bilərsiniz</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" name="add_user" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Yarat
                    </button>
                    <button type="button" @click="showModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                        Ləğv Et
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div x-show="showEditModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="showEditModal = false"
         class="fixed inset-0 bg-gray-500 bg-opacity-75 z-50 flex items-center justify-center p-4">
        <div @click.stop class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-auto">
            <form method="post" action="">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">İstifadəçini Redaktə Et</h3>
                    <div class="mt-6 space-y-4">
                        <input type="hidden" name="edit_user_id" id="edit_user_id">
                        <div>
                            <label for="edit_f_name" class="block text-sm font-medium text-gray-700">Ad Soyad</label>
                            <input type="text" name="edit_f_name" id="edit_f_name" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                        </div>
                        <div>
                            <label for="edit_username" class="block text-sm font-medium text-gray-700">İstifadəçi adı</label>
                            <input type="text" name="edit_username" id="edit_username" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                        </div>
                        <div>
                            <label for="edit_password" class="block text-sm font-medium text-gray-700">Yeni Şifrə (istəyə bağlı)</label>
                            <input type="password" name="edit_password" id="edit_password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500">Şifrəni dəyişdirmək istəmirsinizsə boş buraxın</p>
                        </div>
                        <div>
                            <label for="edit_role" class="block text-sm font-medium text-gray-700">Rol</label>
                            <select name="edit_role" id="edit_role" onchange="toggleEditFields()" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" required>
                                <?php foreach ($roles as $role_key => $role_name): ?>
                                    <option value="<?php echo $role_key; ?>"><?php echo $role_name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="editGroupField" class="hidden">
                            <label for="edit_group_id" class="block text-sm font-medium text-gray-700">Qrup</label>
                            <select name="edit_group_id" id="edit_group_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="">Qrup seçin...</option>
                                <?php foreach ($groups as $group): ?>
                                    <option value="<?php echo $group['id_student_group']; ?>"><?php echo htmlspecialchars($group['group_number']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="editSubjectField" class="hidden">
                            <label for="edit_subject_ids" class="block text-sm font-medium text-gray-700">Fənnlər (Çoxlu seçim mümkündür)</label>
                            <select name="edit_subject_ids[]" id="edit_subject_ids" multiple size="8" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id_subject']; ?>"><?php echo htmlspecialchars($subject['subjectname']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Ctrl/Cmd basaraq bir neçə fənn seçə bilərsiniz</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" name="edit_user" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Yenilə
                    </button>
                    <button type="button" @click="showEditModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                        Ləğv Et
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Alpine.js Component
function userManagement() {
    return {
        showModal: false,
        showEditModal: false,
        showPermissions: false,
        showNotification: <?php echo !empty($message) ? 'true' : 'false'; ?>,

        // Open edit modal method
        openEdit(userId, fName, username, role, groupId, subjectIds) {
            console.log('Opening edit modal for user:', userId);

            // Set form values
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_f_name').value = fName;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_role').value = role;
            document.getElementById('edit_password').value = '';

            // Clear previous selections
            document.getElementById('edit_group_id').value = '';
            const subjectSelect = document.getElementById('edit_subject_ids');
            if (subjectSelect) {
                for (let i = 0; i < subjectSelect.options.length; i++) {
                    subjectSelect.options[i].selected = false;
                }
            }

            // Set group or subjects based on role
            if (role === 'student' && groupId) {
                document.getElementById('edit_group_id').value = groupId;
            } else if (role === 'muellim' && subjectIds) {
                const subjectIdArray = subjectIds.split(',').filter(id => id.trim() !== '');
                for (let i = 0; i < subjectSelect.options.length; i++) {
                    if (subjectIdArray.includes(subjectSelect.options[i].value)) {
                        subjectSelect.options[i].selected = true;
                    }
                }
            }

            // Toggle fields visibility
            toggleEditFields();

            // Open modal
            this.showEditModal = true;
        }
    }
}

function toggleFields() {
    const role = document.getElementById('role').value;
    const groupField = document.getElementById('groupField');
    const subjectField = document.getElementById('subjectField');

    groupField.style.display = 'none';
    subjectField.style.display = 'none';

    if (role === 'student') {
        groupField.style.display = 'block';
    } else if (role === 'muellim') {
        subjectField.style.display = 'block';
    }
}

function toggleEditFields() {
    const role = document.getElementById('edit_role').value;
    const groupField = document.getElementById('editGroupField');
    const subjectField = document.getElementById('editSubjectField');

    groupField.style.display = 'none';
    subjectField.style.display = 'none';

    if (role === 'student') {
        groupField.style.display = 'block';
    } else if (role === 'muellim') {
        subjectField.style.display = 'block';
    }
}

document.addEventListener('DOMContentLoaded', toggleFields);
</script>

<?php include_once "../includes/footer.php"; ?>