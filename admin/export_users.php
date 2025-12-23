<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once "../includes/db.php";
require_once "../includes/auth.php";
require_once "../includes/tfpdf.php";
checkAdminRights();

$database = new Database();
$db = $database->getConnection();

// Get filters from URL
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$group_filter = isset($_GET['group']) ? $_GET['group'] : '';

// Build query to get users (excluding archived groups)
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

// Create PDF with UTF-8 support
class PDF extends tFPDF
{
    private $groupName = '';

    function setGroupName($name) {
        $this->groupName = $name;
    }

    function Header()
    {
        // Logo or title
        $this->SetFont('DejaVu', 'B', 18);
        $this->Cell(0, 10, 'İstifadəçi Məlumatları', 0, 1, 'C');

        if (!empty($this->groupName)) {
            $this->SetFont('DejaVu', '', 12);
            $this->Cell(0, 8, 'Qrup: ' . $this->groupName, 0, 1, 'C');
        }

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
        $this->Cell(80, 10, 'Ad Soyad', 1, 0, 'C', true);
        $this->Cell(40, 10, 'Qrup', 1, 0, 'C', true);
        $this->Cell(60, 10, 'İstifadəçi adı', 1, 1, 'C', true);

        $this->SetTextColor(0, 0, 0);
    }

    function TableRow($num, $name, $group, $username, $fill = false)
    {
        $this->SetFont('DejaVu', '', 10);

        if ($fill) {
            $this->SetFillColor(240, 240, 240);
        }

        $this->Cell(10, 8, $num, 1, 0, 'C', $fill);
        $this->Cell(80, 8, $name, 1, 0, 'L', $fill);
        $this->Cell(40, 8, $group, 1, 0, 'C', $fill);
        $this->Cell(60, 8, $username, 1, 1, 'L', $fill);
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
foreach ($users as $user) {
    $fill = ($counter % 2 == 0);
    $pdf->TableRow(
        $counter,
        $user['f_name'],
        $user['group_number'] ?? 'N/A',
        $user['username'],
        $fill
    );
    $counter++;
}

// Output PDF
$filename = 'istifadeciler_' . date('Y-m-d_H-i-s') . '.pdf';
$pdf->Output('D', $filename);
exit();
?>
