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

$user_subject_id = isset($_SESSION['subject_id']) ? $_SESSION['subject_id'] : null;

// Get Group Averages
$groupAveragesQuery = "SELECT sg.group_number, sg.id_student_group,
    COUNT(DISTINCT u.id_users) as student_count,
    COUNT(DISTINCT a.exam_id) as total_exams,
    COUNT(a.id_answer) as total_questions,
    SUM(a.is_correct) as total_correct,
    (SUM(a.is_correct) / COUNT(a.id_answer)) * 100 as avg_success_rate,
    SUM(qr.question_score * a.is_correct) / COUNT(DISTINCT u.id_users) as avg_score_per_student,
    SUM(qr.question_score * a.is_correct) as total_group_score
    FROM student_group sg
    LEFT JOIN users u ON sg.id_student_group = u.group_id AND u.status = 'student'
    LEFT JOIN answers a ON u.id_users = a.user_id
    LEFT JOIN question_read qr ON a.id_questions = qr.id_question_text";

if (is_teacher() && $user_subject_id) {
    $groupAveragesQuery .= " LEFT JOIN exams e ON a.exam_id = e.id_exam
    WHERE e.id_subject = :subject_id";
}

$groupAveragesQuery .= " GROUP BY sg.id_student_group
    HAVING student_count > 0 AND total_questions > 0
    ORDER BY avg_success_rate DESC";

$stmt = $db->prepare($groupAveragesQuery);
if (is_teacher() && $user_subject_id) {
    $stmt->bindParam(":subject_id", $user_subject_id);
}
$stmt->execute();
$groupAverages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create PDF
class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, utf8_to_latin('Qruplar uzre Ortalamalar'), 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 10, utf8_to_latin('Tarix: ' . date('d.m.Y H:i')), 0, 1, 'C');
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_to_latin('Sehife ') . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 10);

// Table Header
$pdf->SetFillColor(99, 102, 241);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(15, 10, '#', 1, 0, 'C', true);
$pdf->Cell(50, 10, utf8_to_latin('Qrup'), 1, 0, 'C', true);
$pdf->Cell(30, 10, utf8_to_latin('Telebe'), 1, 0, 'C', true);
$pdf->Cell(35, 10, utf8_to_latin('Ugur %'), 1, 0, 'C', true);
$pdf->Cell(30, 10, utf8_to_latin('Orta Bal'), 1, 0, 'C', true);
$pdf->Cell(30, 10, utf8_to_latin('Derece'), 1, 1, 'C', true);

// Table Data
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(0, 0, 0);

$counter = 1;
foreach ($groupAverages as $group) {
    $avgSuccess = floatval($group['avg_success_rate'] ?? 0);
    $avgScore = floatval($group['avg_score_per_student'] ?? 0);

    // Performance level
    if ($avgSuccess >= 90) {
        $performanceLabel = 'Ela';
        $pdf->SetFillColor(16, 185, 129);
    } elseif ($avgSuccess >= 80) {
        $performanceLabel = 'Yaxsi';
        $pdf->SetFillColor(59, 130, 246);
    } elseif ($avgSuccess >= 70) {
        $performanceLabel = 'Orta';
        $pdf->SetFillColor(251, 191, 36);
    } else {
        $performanceLabel = 'Zeif';
        $pdf->SetFillColor(239, 68, 68);
    }

    $pdf->Cell(15, 8, $counter++, 1, 0, 'C');
    $pdf->Cell(50, 8, utf8_to_latin($group['group_number']), 1, 0, 'L');
    $pdf->Cell(30, 8, $group['student_count'] . ' nefar', 1, 0, 'C');
    $pdf->Cell(35, 8, number_format($avgSuccess, 1) . '%', 1, 0, 'C');
    $pdf->Cell(30, 8, number_format($avgScore, 1), 1, 0, 'C');

    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(30, 8, $performanceLabel, 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
}

// Summary Statistics
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 10, utf8_to_latin('Umumi Statistika'), 0, 1, 'L');

$pdf->SetFont('Arial', '', 9);
$totalStudents = array_sum(array_column($groupAverages, 'student_count'));
$avgOverall = array_sum(array_column($groupAverages, 'avg_success_rate')) / count($groupAverages);

$pdf->Cell(60, 8, utf8_to_latin('Umumi Telebe Sayi:'), 0, 0, 'L');
$pdf->Cell(0, 8, $totalStudents . ' nefar', 0, 1, 'L');

$pdf->Cell(60, 8, utf8_to_latin('Umumi Ortalama Ugur:'), 0, 0, 'L');
$pdf->Cell(0, 8, number_format($avgOverall, 1) . '%', 0, 1, 'L');

$pdf->Cell(60, 8, utf8_to_latin('Qrup Sayi:'), 0, 0, 'L');
$pdf->Cell(0, 8, count($groupAverages), 0, 1, 'L');

// Output PDF
$pdf->Output('I', 'Qrup_Ortalamalari_' . date('Y-m-d') . '.pdf');
?>
