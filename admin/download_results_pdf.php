<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

// UTF-8 Helper Function
function utf8_to_latin($text) {
    $replacements = array(
        'ə' => 'e', 'Ə' => 'E',
        'ğ' => 'g', 'Ğ' => 'G',
        'ı' => 'i', 'I' => 'I',
        'İ' => 'I', 'i' => 'i',
        'ö' => 'o', 'Ö' => 'O',
        'ü' => 'u', 'Ü' => 'U',
        'ş' => 's', 'Ş' => 'S',
        'ç' => 'c', 'Ç' => 'C'
    );
    return strtr($text, $replacements);
}

require_once "../includes/fpdf/fpdf.php";

checkLogin();

// Permission Check
if (!is_admin() && !is_prorektor() && !is_kafedra() && !is_teacher()) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$exam_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$teacher_subject_id = $_SESSION['subject_id'] ?? null;

if ($exam_id == 0) {
    header("Location: exams.php?error=no_exam_specified");
    exit();
}

// Fetch Exam Information
$query = "SELECT e.id_exam, e.date_exam, e.datetime, e.status, s.subjectname, s.id_subject, sg.group_number
          FROM exams e
          JOIN subjects s ON e.id_subject = s.id_subject
          JOIN student_group sg ON e.id_student_group = sg.id_student_group
          WHERE e.id_exam = :exam_id";

if (is_teacher()) {
    $query .= " AND e.id_subject = :teacher_subject_id";
}

$stmt = $db->prepare($query);
$stmt->bindParam(":exam_id", $exam_id);
if (is_teacher()) {
    $stmt->bindParam(":teacher_subject_id", $teacher_subject_id);
}
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: exams.php?error=exam_not_found_or_unauthorized");
    exit();
}

$exam = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch Student Results
$query = "SELECT u.id_users, u.f_name, u.username,
          COUNT(a.id_answer) as total_questions,
          SUM(a.is_correct) as correct_answers,
          SUM(qr.question_score * a.is_correct) as total_score,
          (SELECT SUM(qr2.question_score)
           FROM question_read qr2
           JOIN question_files qf2 ON qr2.id_read_quest_file = qf2.id_read_quest_file
           WHERE qf2.subject_id = s.id_subject) as max_score
          FROM users u
          JOIN answers a ON u.id_users = a.user_id
          JOIN question_read qr ON a.id_questions = qr.id_question_text
          JOIN exams e ON a.exam_id = e.id_exam
          JOIN subjects s ON e.id_subject = s.id_subject
          WHERE a.exam_id = :exam_id
          GROUP BY u.id_users
          ORDER BY total_score DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(":exam_id", $exam_id);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$totalStudents = count($results);
$totalScore = 0;
$totalCorrect = 0;
$totalQuestions = 0;
$maxScore = $totalStudents > 0 ? $results[0]['max_score'] : 0;

foreach ($results as $result) {
    $totalScore += $result['total_score'];
    $totalCorrect += $result['correct_answers'];
    $totalQuestions += $result['total_questions'];
}

$avgScore = $totalStudents > 0 ? $totalScore / $totalStudents : 0;
$avgCorrect = $totalStudents > 0 ? $totalCorrect / $totalStudents : 0;
$avgPercent = $maxScore > 0 ? ($avgScore / $maxScore) * 100 : 0;

// Create PDF
class PDF extends FPDF
{
    function Header()
    {
        // Logo
        $logoPath = dirname(__FILE__) . '/../images/logobbu.jpg';
        if (file_exists($logoPath)) {
            $this->Image($logoPath, 85, 10, 40);
        }

        $this->Ln(30);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 6, utf8_to_latin('AZERBAYCAN RESPUBLIKASI TEHSIL NAZIRLIYI'), 0, 1, 'C');
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 7, utf8_to_latin('BAKI BIZNES UNIVERSITETI'), 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, utf8_to_latin('Diller Kafedrasi'), 0, 1, 'C');
        $this->Ln(8);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_to_latin('Sehife ' . $this->PageNo()), 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AddPage('P'); // Portrait (A4)
$pdf->SetFont('Arial', '', 11);

// Exam Info
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(50, 7, utf8_to_latin('Qrup nomresi:'), 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 7, utf8_to_latin($exam['group_number']), 0, 1);

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(50, 7, utf8_to_latin('Fennin adi:'), 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 7, utf8_to_latin($exam['subjectname']), 0, 1);
$pdf->Ln(5);

// Table Header
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(99, 102, 241);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(10, 8, utf8_to_latin('No'), 1, 0, 'C', true);
$pdf->Cell(80, 8, utf8_to_latin('Ad'), 1, 0, 'C', true);
$pdf->Cell(50, 8, utf8_to_latin('Bal'), 1, 0, 'C', true);
$pdf->Cell(50, 8, utf8_to_latin('Faiz'), 1, 1, 'C', true);

// Table Content
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0, 0, 0);
$counter = 1;

foreach ($results as $result) {
    $percentage = ($result['total_score'] / $result['max_score']) * 100;

    $pdf->Cell(10, 7, $counter++, 1, 0, 'C');
    $pdf->Cell(80, 7, utf8_to_latin($result['f_name']), 1, 0, 'L');
    $pdf->Cell(50, 7, number_format($result['total_score'], 2), 1, 0, 'C');
    $pdf->Cell(50, 7, number_format($percentage, 2) . '%', 1, 1, 'C');
}

$pdf->Ln(15);

// Signature section
$pdf->SetFont('Arial', '', 10);

// Kafedra mudiri signature
$pdf->Cell(100, 7, utf8_to_latin('Diller kafedrasi mudiri:'), 0, 0, 'L');
$pdf->Cell(0, 7, utf8_to_latin('f.f.d dos Sevil Qurbanova'), 0, 1, 'R');

// Output PDF
$filename = 'imtahan_netice_' . $exam_id . '_' . date('Y-m-d') . '.pdf';
$pdf->Output('I', $filename);
exit();
