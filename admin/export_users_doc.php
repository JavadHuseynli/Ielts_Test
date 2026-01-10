<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";
checkAdminRights();

$database = new Database();
$db = $database->getConnection();

// Get filters from URL
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$group_filter = isset($_GET['group']) ? $_GET['group'] : '';

// Build query to get users
$query = "SELECT u.f_name, sg.group_number, u.username
          FROM users u
          LEFT JOIN student_group sg ON u.group_id = sg.id_student_group
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
$query .= " ORDER BY sg.group_number, u.f_name";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get group name if filtering by group
$group_name = '';
if (!empty($group_filter)) {
    $group_query = "SELECT group_number FROM student_group WHERE id_student_group = :group_id";
    $group_stmt = $db->prepare($group_query);
    $group_stmt->bindValue(':group_id', $group_filter);
    $group_stmt->execute();
    $group_result = $group_stmt->fetch(PDO::FETCH_ASSOC);
    if ($group_result) {
        $group_name = $group_result['group_number'];
    }
}

// Set headers for Word document download
$filename = 'Istifadeciler_' . date('Y-m-d') . '.doc';
header("Content-Type: application/vnd.ms-word");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

?>
<!DOCTYPE html>
<html lang="az" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>İstifadəçi Məlumatları</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
        }
        .container {
            margin: 20px;
        }
        h1, h2 {
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .header {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>İstifadəçi Məlumatları</h1>
            <?php if (!empty($group_name)): ?>
                <h2>Qrup: <?php echo htmlspecialchars($group_name); ?></h2>
            <?php endif; ?>
            <p style="text-align: center;">Tarix: <?php echo date('d.m.Y H:i'); ?></p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ad Soyad</th>
                    <th>Qrup</th>
                    <th>İstifadəçi adı</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $counter = 1;
                foreach ($users as $user):
                ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><?php echo htmlspecialchars($user['f_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['group_number'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">Məlumat tapılmadı.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<?php
exit();

