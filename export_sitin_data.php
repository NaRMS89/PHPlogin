<?php
session_start();
include("../includes/database.php");
require_once('../vendor/autoload.php'); // Make sure to install required packages via composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

// Check for required libraries
if (!file_exists('tcpdf/tcpdf.php') || !file_exists('PHPExcel/PHPExcel.php')) {
    die('Required libraries are missing. Please install TCPDF and PHPExcel.');
}

require_once('tcpdf/tcpdf.php');
require_once('PHPExcel/PHPExcel.php');

// Class type declarations
if (!class_exists('PHPExcel')) {
    class PHPExcel {
        public function setActiveSheetIndex($index) {
            return $this;
        }
        public function getActiveSheet() {
            return new PHPExcel_Worksheet();
        }
    }
}

if (!class_exists('PHPExcel_Worksheet')) {
    class PHPExcel_Worksheet {
        public function setCellValueByColumnAndRow($col, $row, $value) {}
    }
}

if (!class_exists('PHPExcel_IOFactory')) {
    class PHPExcel_IOFactory {
        public static function createWriter($excel, $format) {
            return new PHPExcel_Writer();
        }
    }
}

if (!class_exists('PHPExcel_Writer')) {
    class PHPExcel_Writer {
        public function save($filename) {}
    }
}

if (!class_exists('TCPDF')) {
    class TCPDF {
        public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false) {}
        public function SetCreator($creator) {}
        public function SetAuthor($author) {}
        public function SetTitle($title) {}
        public function SetMargins($left, $top, $right) {}
        public function SetHeaderMargin($margin) {}
        public function SetFooterMargin($margin) {}
        public function AddPage() {}
        public function SetFont($family, $style = '', $size = 0) {}
        public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '') {}
        public function Ln($h = null) {}
        public function SetFillColor($r, $g = null, $b = null) {}
        public function Output($name = 'doc.pdf', $dest = 'I') {}
    }
}

// Define PDF constants if not already defined
if (!defined('PDF_PAGE_ORIENTATION')) {
    define('PDF_PAGE_ORIENTATION', 'P');
    define('PDF_UNIT', 'mm');
    define('PDF_PAGE_FORMAT', 'A4');
    define('PDF_CREATOR', 'Admin Dashboard');
}

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php");
    exit;
}

// Get export parameters
$format = $_GET['format'] ?? '';
$lab = $_GET['lab'] ?? '';
$purpose = $_GET['purpose'] ?? '';

// Prepare the base query
$query = "SELECT s.id_number, CONCAT(i.first_name, ' ', i.last_name) as student_name, 
          s.purpose, s.lab, s.login_time, s.logout_time, 
          TIMEDIFF(COALESCE(s.logout_time, NOW()), s.login_time) as duration,
          s.status
          FROM sitin_report s
          JOIN info i ON s.id_number = i.id_number
          WHERE 1=1";

// Add filters if provided
if ($lab) {
    $query .= " AND s.lab = ?";
}
if ($purpose) {
    $query .= " AND s.purpose = ?";
}

$query .= " ORDER BY s.login_time DESC";

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $query);

if ($lab && $purpose) {
    mysqli_stmt_bind_param($stmt, "ss", $lab, $purpose);
} elseif ($lab) {
    mysqli_stmt_bind_param($stmt, "s", $lab);
} elseif ($purpose) {
    mysqli_stmt_bind_param($stmt, "s", $purpose);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch all data
$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

// Function to generate CSV
function generateCSV($data) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sitin_data.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add headers
    fputcsv($output, ['ID Number', 'Student Name', 'Purpose', 'Lab', 'Login Time', 'Logout Time', 'Duration', 'Status']);
    
    // Add data
    foreach ($data as $row) {
        fputcsv($output, [
            $row['id_number'],
            $row['student_name'],
            $row['purpose'],
            $row['lab'],
            $row['login_time'],
            $row['logout_time'] ?: '-',
            $row['duration'],
            $row['status']
        ]);
    }
    
    fclose($output);
}

// Function to generate Excel
function generateExcel($data) {
    require_once '../vendor/autoload.php';
    
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Add headers
    $headers = ['ID Number', 'Student Name', 'Purpose', 'Lab', 'Login Time', 'Logout Time', 'Duration', 'Status'];
    $sheet->fromArray($headers, NULL, 'A1');
    
    // Add data
    $row = 2;
    foreach ($data as $item) {
        $sheet->setCellValue('A' . $row, $item['id_number']);
        $sheet->setCellValue('B' . $row, $item['student_name']);
        $sheet->setCellValue('C' . $row, $item['purpose']);
        $sheet->setCellValue('D' . $row, $item['lab']);
        $sheet->setCellValue('E' . $row, $item['login_time']);
        $sheet->setCellValue('F' . $row, $item['logout_time'] ?: '-');
        $sheet->setCellValue('G' . $row, $item['duration']);
        $sheet->setCellValue('H' . $row, $item['status']);
        $row++;
    }
    
    // Auto-size columns
    foreach (range('A', 'H') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Style headers
    $headerStyle = $sheet->getStyle('A1:H1');
    $headerStyle->getFont()->setBold(true);
    $headerStyle->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('CCCCCC');
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="sitin_data.xlsx"');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
}

// Function to generate PDF
function generatePDF($data) {
    require_once '../vendor/autoload.php';
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Lab Management System');
    $pdf->SetAuthor('Admin');
    $pdf->SetTitle('Sit-in Data Report');
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Create the table content
    $html = '<h1>Sit-in Data Report</h1>';
    $html .= '<table border="1" cellpadding="4">
                <thead>
                    <tr style="background-color: #CCCCCC;">
                        <th>ID Number</th>
                        <th>Student Name</th>
                        <th>Purpose</th>
                        <th>Lab</th>
                        <th>Login Time</th>
                        <th>Logout Time</th>
                        <th>Duration</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>';
    
    foreach ($data as $row) {
        $html .= '<tr>
                    <td>' . htmlspecialchars($row['id_number']) . '</td>
                    <td>' . htmlspecialchars($row['student_name']) . '</td>
                    <td>' . htmlspecialchars($row['purpose']) . '</td>
                    <td>' . htmlspecialchars($row['lab']) . '</td>
                    <td>' . htmlspecialchars($row['login_time']) . '</td>
                    <td>' . htmlspecialchars($row['logout_time'] ?: '-') . '</td>
                    <td>' . htmlspecialchars($row['duration']) . '</td>
                    <td>' . htmlspecialchars($row['status']) . '</td>
                </tr>';
    }
    
    $html .= '</tbody></table>';
    
    // Output the HTML content
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Close and output PDF document
    $pdf->Output('sitin_data.pdf', 'D');
}

// Handle the export based on format
switch ($format) {
    case 'csv':
        generateCSV($data);
        break;
    case 'excel':
        generateExcel($data);
        break;
    case 'pdf':
        generatePDF($data);
        break;
    case 'print':
        // For print, we'll return JSON data that the frontend can use
        header('Content-Type: application/json');
        echo json_encode($data);
        break;
    default:
        header("HTTP/1.1 400 Bad Request");
        echo "Invalid export format";
        break;
}

// Close database connections
mysqli_stmt_close($stmt);
mysqli_close($conn);
?> 