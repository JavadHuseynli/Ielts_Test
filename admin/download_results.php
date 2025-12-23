<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";

checkLogin();

// Permission Check: Only Prorektor can download DOCX
if (!is_prorektor()) {
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
$query = "SELECT e.id_exam, e.date_exam, e.datetime, e.status, s.subjectname, sg.group_number
          FROM exams e
          JOIN subjects s ON e.id_subject = s.id_subject
          JOIN student_group sg ON e.id_student_group = sg.id_student_group
          WHERE e.id_exam = :exam_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":exam_id", $exam_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: exams.php?error=exam_not_found");
    exit();
}
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch Student Results for the exam
$query = "SELECT u.f_name, u.username,
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

// PHPWord library integration
require_once dirname(__FILE__) . '/../vendor/autoload.php';

$phpWord = new \PhpOffice\PhpWord\PhpWord();

// Set document properties
$phpWord->getDocInfo()->setCreator('BBU İmtahan Sistemi');
$phpWord->getDocInfo()->setTitle('İmtahan Nəticələri');

// Add section
$section = $phpWord->addSection();

// Add logo - centered
$logoPath = '/Users/javad/Developer/imtahan ingilis/images/logobbu.jpg';
if (file_exists($logoPath)) {
    // Create a borderless table to center the logo perfectly
    $logoTableStyle = array(
        'borderSize' => 0,
        'borderColor' => 'FFFFFF',
        'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        'cellMargin' => 0
    );

    $logoTable = $section->addTable($logoTableStyle);
    $logoTable->addRow();
    $logoCell = $logoTable->addCell(3000);

    // Add the image centered in the cell
    $logoParagraph = $logoCell->addTextRun(array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER));
    $logoParagraph->addImage(
        $logoPath,
        array(
            'width' => 120,
            'height' => 80
        )
    );
    $section->addTextBreak(1);
}

// Header
$section->addText(
    'AZƏRBAYCAN RESPUBLİKASI TƏHSİL NAZİRLİYİ',
    array('name' => 'Arial', 'size' => 12, 'bold' => true),
    array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER)
);

$section->addText(
    'BAKI BİZNES UNİVERSİTETİ',
    array('name' => 'Arial', 'size' => 14, 'bold' => true),
    array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER)
);

$section->addText(
    'Dillər Kafedrasının',
    array('name' => 'Arial', 'size' => 10),
    array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER)
);

$section->addTextBreak(2);

// Exam info
$section->addText(
    'İmtahan Nəticələri: ' . $exam['subjectname'] . ' - ' . $exam['group_number'],
    array('name' => 'Arial', 'size' => 12, 'bold' => true)
);

$section->addText(
    'Tarix: ' . date('d.m.Y', strtotime($exam['date_exam'])) . ', Saat: ' . date('H:i', strtotime($exam['datetime'])),
    array('name' => 'Arial', 'size' => 10)
);

$section->addTextBreak(1);

// Add table
$tableStyle = array(
    'borderSize' => 6,
    'borderColor' => '000000',
    'cellMargin' => 80,
    'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER
);

$phpWord->addTableStyle('ResultsTable', $tableStyle);
$table = $section->addTable('ResultsTable');

// Header row
$table->addRow(700);
$headerStyle = array('name' => 'Arial', 'size' => 10, 'bold' => true, 'color' => 'FFFFFF');
$headerCellStyle = array('bgColor' => '6366F1', 'valign' => 'center');

$table->addCell(1000, $headerCellStyle)->addText('№', $headerStyle, array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER));
$table->addCell(3000, $headerCellStyle)->addText('Ad', $headerStyle, array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER));
$table->addCell(2500, $headerCellStyle)->addText('İstifadəçi adı', $headerStyle, array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER));
$table->addCell(2000, $headerCellStyle)->addText('Düzgün cavablar', $headerStyle, array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER));
$table->addCell(2000, $headerCellStyle)->addText('Bal', $headerStyle, array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER));
$table->addCell(1500, $headerCellStyle)->addText('Faiz', $headerStyle, array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER));

// Data rows
$counter = 1;
$textStyle = array('name' => 'Arial', 'size' => 9);
$cellStyle = array('valign' => 'center');

foreach ($results as $result) {
    $percentage = ($result['total_score'] / $result['max_score']) * 100;

    $table->addRow();
    $table->addCell(1000, $cellStyle)->addText($counter++, $textStyle, array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER));
    $table->addCell(3000, $cellStyle)->addText($result['f_name'], $textStyle);
    $table->addCell(2500, $cellStyle)->addText($result['username'], $textStyle);
    $table->addCell(2000, $cellStyle)->addText(
        $result['correct_answers'] . '/' . $result['total_questions'],
        $textStyle,
        array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER)
    );
    $table->addCell(2000, $cellStyle)->addText(
        number_format($result['total_score'], 2) . '/' . number_format($result['max_score'], 2),
        $textStyle,
        array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER)
    );
    $table->addCell(1500, $cellStyle)->addText(
        number_format($percentage, 2) . '%',
        $textStyle,
        array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER)
    );
}

$section->addTextBreak(2);

// Signatures
// Kafedra mudiri signature
$textRun = $section->addTextRun();
$textRun->addText('Dillər kafedrasının müdiri:', array('name' => 'Arial', 'size' => 10));
$textRun->addText(str_repeat(' ', 40)); // Spacing
$textRun->addText('f.f.d dos Sevil Qurbanova', array('name' => 'Arial', 'size' => 10));

// Prorektor signature (only if user is prorektor)
if (is_prorektor()) {
    $section->addTextBreak(2);

    $prorektor = $section->addTextRun();
    $prorektor->addText('Tədris işləri üzrə prorektor:', array('name' => 'Arial', 'size' => 10));
    $prorektor->addText(str_repeat(' ', 30)); // Spacing
    $prorektor->addText('i.f.d dos Xatirə Əzizova', array('name' => 'Arial', 'size' => 10));
}

// Output DOCX
$filename = 'imtahan_netice_' . $exam_id . '_' . date('Y-m-d') . '.docx';
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save('php://output');

exit();

