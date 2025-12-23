<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

checkLogin();

// Permission Check - Only Kafedra
if (!is_kafedra()) {
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

// Fetch Exam Information
$query = "SELECT e.id_exam, e.date_exam, e.datetime, e.status, e.confirmed_by, s.subjectname, s.id_subject, sg.group_number
          FROM exams e
          JOIN subjects s ON e.id_subject = s.id_subject
          JOIN student_group sg ON e.id_student_group = sg.id_student_group
          WHERE e.id_exam = :exam_id";

$stmt = $db->prepare($query);
$stmt->bindParam(":exam_id", $exam_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: exams.php?error=exam_not_found_or_unauthorized");
    exit();
}

$exam = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if exam is confirmed
if (!$exam['confirmed_by']) {
    header("Location: exam_results.php?id=" . $exam_id . "&error=exam_not_confirmed");
    exit();
}

// Fetch Student Results with manual scores
$query = "SELECT u.id_users, u.f_name, u.username, sg.group_number,
          COUNT(a.id_answer) as total_questions,
          SUM(a.is_correct) as correct_answers,
          SUM(qr.question_score * a.is_correct) as computer_score,
          COALESCE(ms.writing_score, 0) as writing_score,
          COALESCE(ms.speaking_score, 0) as speaking_score,
          (SUM(qr.question_score * a.is_correct) + COALESCE(ms.writing_score, 0) + COALESCE(ms.speaking_score, 0)) as total_score,
          (SELECT SUM(qr2.question_score)
           FROM question_read qr2
           JOIN question_files qf2 ON qr2.id_read_quest_file = qf2.id_read_quest_file
           WHERE qf2.subject_id = s.id_subject) as max_score
          FROM users u
          JOIN answers a ON u.id_users = a.user_id
          JOIN question_read qr ON a.id_questions = qr.id_question_text
          JOIN exams e ON a.exam_id = e.id_exam
          JOIN subjects s ON e.id_subject = s.id_subject
          LEFT JOIN student_group sg ON u.group_id = sg.id_student_group
          LEFT JOIN manual_scores ms ON ms.exam_id = a.exam_id AND ms.user_id = u.id_users
          WHERE a.exam_id = :exam_id
          GROUP BY u.id_users
          ORDER BY total_score DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(":exam_id", $exam_id);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for Word document
header("Content-Type: application/vnd.ms-word");
header("Content-Disposition: attachment; filename=imtahan_kafedra_tesdiq_" . $exam_id . "_" . date('Y-m-d') . ".doc");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 12pt;
            font-weight: bold;
            margin: 5px 0;
        }
        .header h2 {
            font-size: 14pt;
            font-weight: bold;
            margin: 5px 0;
        }
        .header p {
            font-size: 10pt;
            margin: 5px 0;
        }
        .info {
            margin-bottom: 20px;
        }
        .info p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #6366f1;
            color: white;
            font-weight: bold;
        }
        td.name {
            text-align: left;
        }
        .signature {
            margin-top: 40px;
        }
        .signature table {
            width: 100%;
            border: none;
            margin-bottom: 0;
        }
        .signature td {
            border: none;
            padding: 0;
        }
        .signature .left {
            text-align: left;
            width: 40%;
        }
        .signature .right {
            text-align: right;
            width: 60%;
        }
    </style>
</head>
<body>
    <div class="logo">
        <img src="/Users/javad/Developer/imtahan ingilis/images/logobbu.jpg" width="150" alt="BBU Logo">
    </div>

    <div class="header">
        <h1>AZƏRBAYCAN RESPUBLİKASI TƏHSİL NAZİRLİYİ</h1>
        <h2>BAKI BİZNES UNİVERSİTETİ</h2>
        <p>Dillər Kafedrasının</p>
    </div>

    <div class="info">
        <p><strong>Qrup nömrəsi:</strong> <?php echo htmlspecialchars($exam['group_number']); ?></p>
        <p><strong>Fənnin adı:</strong> <?php echo htmlspecialchars($exam['subjectname']); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Ad</th>
                <th>Kompüter balı</th>
                <th>Yazı balı</th>
                <th>Danışıq balı</th>
                <th>Ümumi bal</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $counter = 1;
            foreach ($results as $result):
            ?>
            <tr>
                <td><?php echo $counter++; ?></td>
                <td class="name"><?php echo htmlspecialchars($result['f_name']); ?></td>
                <td><?php echo number_format($result['computer_score'], 2); ?></td>
                <td><?php echo number_format($result['writing_score'], 2); ?></td>
                <td><?php echo number_format($result['speaking_score'], 2); ?></td>
                <td><?php echo number_format($result['total_score'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="signature">
        <table>
            <tr>
                <td class="left">Dillər kafedrasının müdiri:</td>
                <td class="right">f.f.d dos Sevil Qurbanova</td>
            </tr>
        </table>
    </div>
</body>
</html>
