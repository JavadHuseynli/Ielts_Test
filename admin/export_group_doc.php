<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

checkLogin();

// Only admin can export
if (!is_admin()) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$group_id = isset($_GET['group']) ? intval($_GET['group']) : 0;

if ($group_id == 0) {
    header("Location: groups.php?error=no_group_specified");
    exit();
}

// Fetch Group Information
$query = "SELECT group_number FROM student_group WHERE id_student_group = :group_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":group_id", $group_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: groups.php?error=group_not_found");
    exit();
}

$group = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch Students
$studentsQuery = "SELECT u.id_users, u.f_name, u.username
                  FROM users u
                  WHERE u.group_id = :group_id AND u.status = 'student'
                  ORDER BY u.f_name ASC";

$stmt = $db->prepare($studentsQuery);
$stmt->bindParam(":group_id", $group_id);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate proper DOC file with encoding
header("Content-Type: application/msword; charset=utf-8");
header("Content-Disposition: attachment; filename=qrup_" . $group['group_number'] . "_" . date('Y-m-d') . ".doc");
header("Pragma: no-cache");
header("Expires: 0");

// Output UTF-8 BOM for proper encoding
echo "\xEF\xBB\xBF";
?>
<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<xml>
<w:WordDocument>
<w:View>Print</w:View>
<w:Zoom>90</w:Zoom>
</w:WordDocument>
</xml>
<style>
body { font-family: Arial, sans-serif; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { border: 1px solid #000; padding: 8px; text-align: left; }
th { background-color: #4CAF50; color: white; font-weight: bold; }
h1 { text-align: center; color: #333; }
.header-info { margin-bottom: 20px; }
</style>
</head>
<body>
<h1>QRUP TƏLƏBƏLƏRİ SİYAHISI</h1>

<div class="header-info">
<p><b>Qrup:</b> <?php echo $group['group_number']; ?></p>
<p><b>Tələbə Sayı:</b> <?php echo count($students); ?></p>
<p><b>Tarix:</b> <?php echo date('d.m.Y'); ?></p>
</div>

<?php if (empty($students)): ?>
<p style="text-align: center; color: red;"><b>Bu qrupda tələbə yoxdur.</b></p>
<?php else: ?>
<table border="1">
<thead>
<tr>
<th>№</th>
<th>Tələbə Adı</th>
<th>İstifadəçi Adı</th>
<th>Qeyd</th>
</tr>
</thead>
<tbody>
<?php
$counter = 1;
foreach ($students as $student):
?>
<tr>
<td><?php echo $counter++; ?></td>
<td><?php echo $student['f_name']; ?></td>
<td><?php echo $student['username']; ?></td>
<td style="font-size: 10px; color: #666;">Şifrə şifrələnib</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<br><br>
<p><b>Yüklənmə tarixi:</b> <?php echo date('d.m.Y H:i'); ?></p>
<p><b>Yükləyən:</b> <?php echo $_SESSION['name']; ?></p>

<br><br>
<table border="0" width="100%" style="margin-top: 40px;">
<tr>
<td width="50%" style="vertical-align: top;">
<p><b>Tərtib edən:</b></p>
<p><?php echo $_SESSION['name']; ?></p>
<br><br>
<p>İmza: _________________</p>
<p>Tarix: <?php echo date('d.m.Y'); ?></p>
</td>
<td width="50%" style="vertical-align: top;">
<p><b>Çap edən:</b></p>
<p>Ad, Soyad: _________________</p>
<br><br>
<p>İmza: _________________</p>
<p>Tarix: <?php echo date('d.m.Y'); ?></p>
</td>
</tr>
</table>
</body>
</html>
