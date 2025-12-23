<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";
require_once "../includes/tfpdf.php";
checkAdminRights();

$database = new Database();
$db = $database->getConnection();

// Get group ID from URL
$group_id = isset($_GET['group']) ? intval($_GET['group']) : 0;

if ($group_id == 0) {
    die("Qrup ID təyin edilməyib!");
}

// Get group information
$group_query = "SELECT group_number FROM student_group WHERE id_student_group = :group_id";
$group_stmt = $db->prepare($group_query);
$group_stmt->bindValue(':group_id', $group_id);
$group_stmt->execute();
$group = $group_stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
    die("Qrup tapılmadı!");
}

$group_name = $group['group_number'];

// Get students in the group
$query = "SELECT u.f_name, u.username
          FROM users u
          WHERE u.group_id = :group_id AND u.status = 'student'
          ORDER BY u.f_name";

$stmt = $db->prepare($query);
$stmt->bindValue(':group_id', $group_id);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create PDF with UTF-8 support
class PDF extends tFPDF
{
    private $groupName = '';

    function setGroupName($name) {
        $this->groupName = $name;
    }

    function Header()
    {
        // Title
        $this->SetFont('DejaVu', 'B', 20);
        $this->Cell(0, 12, 'Qrup: ' . $this->groupName, 0, 1, 'C');

        $this->SetFont('DejaVu', '', 10);
        $this->Cell(0, 6, 'Tarix: ' . date('d.m.Y H:i'), 0, 1, 'C');
        $this->Ln(5);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('DejaVu', '', 8);
        $this->Cell(0, 10, 'Səhifə ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function TableHeader()
    {
        $this->SetFillColor(59, 130, 246); // Blue background
        $this->SetTextColor(255, 255, 255); // White text
        $this->SetFont('DejaVu', 'B', 11);

        $this->Cell(10, 10, '№', 1, 0, 'C', true);
        $this->Cell(70, 10, 'Ad Soyad', 1, 0, 'C', true);
        $this->Cell(55, 10, 'İstifadəçi adı', 1, 0, 'C', true);
        $this->Cell(55, 10, 'Parol', 1, 1, 'C', true);

        $this->SetTextColor(0, 0, 0);
    }

    function TableRow($num, $name, $username, $password, $fill = false)
    {
        $this->SetFont('DejaVu', '', 10);

        if ($fill) {
            $this->SetFillColor(240, 240, 240);
        }

        $this->Cell(10, 8, $num, 1, 0, 'C', $fill);
        $this->Cell(70, 8, $name, 1, 0, 'L', $fill);
        $this->Cell(55, 8, $username, 1, 0, 'L', $fill);
        $this->Cell(55, 8, $password, 1, 1, 'L', $fill);
    }
}

// Create instance of PDF
$pdf = new PDF();
$pdf->setGroupName($group_name);
$pdf->AliasNbPages();

// Add DejaVu font (supports UTF-8 and Azerbaijani characters)
$pdf->AddFont('DejaVu', '', 'DejaVuSans.ttf', true);
$pdf->AddFont('DejaVu', 'B', 'DejaVuSans-Bold.ttf', true);

$pdf->AddPage();
$pdf->SetFont('DejaVu', '', 10);

// Add table header
$pdf->TableHeader();

// Add data rows
$counter = 1;
foreach ($students as $student) {
    $fill = ($counter % 2 == 0);
    // Password is same as username
    $pdf->TableRow(
        $counter,
        $student['f_name'],
        $student['username'],
        $student['username'], // Password same as username
        $fill
    );
    $counter++;
}

// Add summary at the bottom
$pdf->Ln(5);
$pdf->SetFont('DejaVu', 'B', 11);
$pdf->Cell(0, 8, 'Ümumi tələbə sayı: ' . count($students), 0, 1, 'R');

// Output PDF
$filename = 'qrup_' . $group_name . '_' . date('Y-m-d') . '.pdf';
$pdf->Output('D', $filename);
exit();
?>
