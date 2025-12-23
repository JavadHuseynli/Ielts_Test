<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

checkLogin();

// Permission Check: Only Admin and Prorektor can access this
if (!is_admin() && !is_prorektor()) {
    header("HTTP/1.1 403 Forbidden");
    exit("Access Denied. You do not have permission to view this page.");
}

$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

if ($exam_id === 0) {
    header("HTTP/1.1 400 Bad Request");
    exit("Invalid or missing Exam ID.");
}

$database = new Database();
$db = $database->getConnection();

// Fetch archived results for the specific exam
$query = "SELECT era.score_earned,
          e.datetime, s.subjectname, sg.group_number,
          u.f_name as student_name, u.username as student_username
          FROM exam_results_archive era
          JOIN exams e ON era.exam_id = e.id_exam
          JOIN subjects s ON e.id_subject = s.id_subject
          JOIN student_group sg ON e.id_student_group = sg.id_student_group
          JOIN users u ON era.user_id = u.id_users
          WHERE era.exam_id = :exam_id
          ORDER BY u.f_name ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(":exam_id", $exam_id);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($results)) {
    exit("No archived results found for this exam ID.");
}

// Extract exam details from the first result
$exam_details = [
    'subjectname' => $results[0]['subjectname'],
    'group_number' => $results[0]['group_number'],
    'datetime' => $results[0]['datetime'],
];

?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arxiv Nəticələri - <?php echo htmlspecialchars($exam_details['subjectname']); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border: 1px solid #dee2e6;
        }
        h1, h2 {
            text-align: center;
            color: #000;
            margin-bottom: 10px;
        }
        h1 {
            font-size: 24px;
        }
        h2 {
            font-size: 20px;
            font-weight: normal;
            color: #6c757d;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 8px 12px;
            text-align: left;
        }
        th {
            background-color: #e9ecef;
            font-weight: bold;
        }
        tbody tr:nth-child(odd) {
            background-color: #f8f9fa;
        }
        .print-button {
            display: block;
            width: 120px;
            margin: 20px auto;
            padding: 10px;
            text-align: center;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        @media print {
            body {
                background-color: #fff;
            }
            .container {
                border: none;
                box-shadow: none;
                margin: 0;
                max-width: 100%;
            }
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Arxivləşdirilmiş İmtahan Nəticəsi</h1>
        <h2><?php echo htmlspecialchars($exam_details['subjectname'] . ' - ' . $exam_details['group_number']); ?></h2>
        <p style="text-align: center; color: #6c757d;">İmtahan Tarixi: <?php echo date('d.m.Y H:i', strtotime($exam_details['datetime'])); ?></p>
        
        <table>
            <thead>
                <tr>
                    <th>№</th>
                    <th>Tələbə Adı</th>
                    <th>İstifadəçi Adı</th>
                    <th>Bal</th>
                </tr>
            </thead>
            <tbody>
                <?php $counter = 1; foreach ($results as $result): ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><?php echo htmlspecialchars($result['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($result['student_username']); ?></td>
                        <td><?php echo number_format($result['score_earned'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <a href="javascript:window.print()" class="print-button">Çap Et</a>
</body>
</html>
