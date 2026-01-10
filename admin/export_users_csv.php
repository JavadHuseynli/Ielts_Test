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

// Set headers for CSV download
$filename = 'Istifadeciler_' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Open output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM to fix Excel character encoding issues
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add header row
fputcsv($output, ['#', 'Ad Soyad', 'Qrup', 'İstifadəçi adı'], ';');

// Add data rows
$counter = 1;
foreach ($users as $user) {
    fputcsv($output, [
        $counter++,
        $user['f_name'],
        $user['group_number'] ?? 'N/A',
        $user['username']
    ], ';');
}

fclose($output);
exit();
