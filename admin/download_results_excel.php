<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

checkLogin();

// Permission Check - Dekan, Kafedra and Prorektor (only confirmed exams)
if (!is_dekan() && !is_prorektor() && !is_kafedra()) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$exam_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($exam_id == 0) {
    header("Location: exams.php?error=no_exam_specified");
    exit();
}

// Fetch Exam Information with confirmation check
$query = "SELECT e.id_exam, e.date_exam, e.datetime, e.confirmed_by, e.confirmed_at,
          e.confirmed_by_dekan, e.confirmed_at_dekan, e.confirmed_by_kafedra, e.confirmed_at_kafedra,
          s.subjectname, sg.group_number
          FROM exams e
          INNER JOIN subjects s ON e.id_subject = s.id_subject
          INNER JOIN student_group sg ON e.id_student_group = sg.id_student_group
          WHERE e.id_exam = :exam_id";

$stmt = $db->prepare($query);
$stmt->bindParam(":exam_id", $exam_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: exams.php?error=exam_not_found");
    exit();
}

$exam = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if BOTH dekan and kafedra confirmed
$dekanConfirmed = !empty($exam['confirmed_by_dekan']);
$kafedraConfirmed = !empty($exam['confirmed_by_kafedra']);

if (!$dekanConfirmed || !$kafedraConfirmed) {
    header("Location: exam_results.php?id=" . $exam_id . "&error=both_confirmations_required");
    exit();
}

// Fetch Results
$resultsQuery = "SELECT
    u.id_users,
    u.f_name,
    sg.group_number,
    COALESCE(SUM(CASE WHEN a.is_correct = 1 THEN qr.question_score ELSE 0 END), 0) as computer_score,
    COALESCE(ms.writing_score, 0) as writing_score,
    COALESCE(ms.speaking_score, 0) as speaking_score,
    (COALESCE(SUM(CASE WHEN a.is_correct = 1 THEN qr.question_score ELSE 0 END), 0) +
     COALESCE(ms.writing_score, 0) +
     COALESCE(ms.speaking_score, 0)) as total_score,
    (SELECT SUM(qr2.question_score)
     FROM question_read qr2
     INNER JOIN question_files qf2 ON qr2.id_read_quest_file = qf2.id_read_quest_file
     WHERE qf2.subject_id = e.id_subject) as max_score
FROM users u
INNER JOIN student_group sg ON u.group_id = sg.id_student_group
CROSS JOIN exams e
LEFT JOIN answers a ON a.user_id = u.id_users AND a.exam_id = e.id_exam
LEFT JOIN question_read qr ON a.id_questions = qr.id_question_text
LEFT JOIN manual_scores ms ON ms.user_id = u.id_users AND ms.exam_id = e.id_exam
WHERE e.id_exam = :exam_id AND u.status = 'student' AND u.group_id = e.id_student_group
GROUP BY u.id_users, u.f_name, sg.group_number, ms.writing_score, ms.speaking_score
ORDER BY u.f_name ASC";

$stmt = $db->prepare($resultsQuery);
$stmt->bindParam(":exam_id", $exam_id);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate proper Excel file
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=imtahan_neticeleri_" . $exam_id . "_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Add UTF-8 BOM
echo "\xEF\xBB\xBF";
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<xml>
<x:ExcelWorkbook>
<x:ExcelWorksheets>
<x:ExcelWorksheet>
<x:Name>İmtahan Nəticələri</x:Name>
<x:WorksheetOptions>
<x:Print>
<x:ValidPrinterInfo/>
</x:Print>
</x:WorksheetOptions>
</x:ExcelWorksheet>
</x:ExcelWorksheets>
</x:ExcelWorkbook>
</xml>
<style>
table { border-collapse: collapse; }
th, td { border: 1px solid black; padding: 5px; }
th { background-color: #4CAF50; color: white; font-weight: bold; }
</style>
</head>
<body>
<h2>İMTAHAN NƏTİCƏLƏRİ</h2>

<table border="0" cellpadding="5" style="margin-bottom: 20px;">
<tr>
<td><b>Fənn:</b></td>
<td><?php echo $exam['subjectname']; ?></td>
</tr>
<tr>
<td><b>Qrup:</b></td>
<td><?php echo $exam['group_number']; ?></td>
</tr>
<tr>
<td><b>Tarix:</b></td>
<td><?php echo date('d.m.Y', strtotime($exam['date_exam'])); ?></td>
</tr>
<tr>
<td><b>Saat:</b></td>
<td><?php echo date('H:i', strtotime($exam['datetime'])); ?></td>
</tr>
<tr>
<td><b>Status:</b></td>
<td style="color: green; font-weight: bold;">✓ TƏSDİQLƏNİB</td>
</tr>
<tr>
<td><b>Dekan təsdiqi:</b></td>
<td style="color: green;"><?php echo date('d.m.Y H:i', strtotime($exam['confirmed_at_dekan'])); ?></td>
</tr>
<tr>
<td><b>Kafedra təsdiqi:</b></td>
<td style="color: green;"><?php echo date('d.m.Y H:i', strtotime($exam['confirmed_at_kafedra'])); ?></td>
</tr>
</table>

<table border="1">
<thead>
<tr>
<th>№</th>
<th>Tələbə</th>
<th>Qrup</th>
<th>Kompüter Balı</th>
<th>Yazı Balı</th>
<th>Danışıq Balı</th>
<th>Yekun Bal</th>
</tr>
</thead>
<tbody>
<?php
$counter = 1;
foreach ($results as $result):
?>
<tr>
<td><?php echo $counter++; ?></td>
<td><?php echo $result['f_name']; ?></td>
<td><?php echo $result['group_number']; ?></td>
<td><?php echo number_format($result['computer_score'], 2); ?></td>
<td><?php echo number_format($result['writing_score'], 2); ?></td>
<td><?php echo number_format($result['speaking_score'], 2); ?></td>
<td><?php echo number_format($result['total_score'], 2); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<br><br>
<p><b>Yüklənmə tarixi:</b> <?php echo date('d.m.Y H:i'); ?></p>

<br><br>
<table border="0" width="100%" cellpadding="10" style="margin-top: 40px;">
<tr>
<td width="50%" style="vertical-align: top;">
<?php if (is_prorektor()): ?>
<p><b>Tədris işləri üzrə prorektor:</b></p>
<p><?php echo $_SESSION['name']; ?></p>
<br><br>
<p>İmza: _________________</p>
<p>Tarix: <?php echo date('d.m.Y'); ?></p>
<?php elseif (is_kafedra()): ?>
<p><b>Dillər kafedrasının müdiri:</b></p>
<p><?php echo $_SESSION['name']; ?></p>
<br><br>
<p>İmza: _________________</p>
<p>Tarix: <?php echo date('d.m.Y'); ?></p>
<?php elseif (is_dekan()): ?>
<p><b>Dekan:</b></p>
<p><?php echo $_SESSION['name']; ?></p>
<br><br>
<p>İmza: _________________</p>
<p>Tarix: <?php echo date('d.m.Y'); ?></p>
<?php endif; ?>
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
