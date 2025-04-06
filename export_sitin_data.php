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

// Get filter parameters
$type = $_GET['type'] ?? '';
$lab = $_GET['lab'] ?? '';
$purpose = $_GET['purpose'] ?? '';
$fromDate = $_GET['fromDate'] ?? '';
$toDate = $_GET['toDate'] ?? '';

// Build the query with filters
$query = "SELECT 
    s.id_number,
    CONCAT(i.first_name, ' ', i.last_name) as student_name,
    s.purpose,
    s.lab,
    s.login_time,
    s.logout_time,
    CASE 
        WHEN s.logout_time IS NULL THEN 'active'
        ELSE 'completed'
    END as status
FROM sitin_report s
JOIN info i ON s.id_number = i.id_number
WHERE 1=1";

$params = [];
$types = "";

if ($lab) {
    $query .= " AND s.lab = ?";
    $params[] = $lab;
    $types .= "s";
}

if ($purpose) {
    $query .= " AND s.purpose = ?";
    $params[] = $purpose;
    $types .= "s";
}

if ($fromDate) {
    $query .= " AND DATE(s.login_time) >= ?";
    $params[] = $fromDate;
    $types .= "s";
}

if ($toDate) {
    $query .= " AND DATE(s.login_time) <= ?";
    $params[] = $toDate;
    $types .= "s";
}

$query .= " ORDER BY s.login_time DESC";

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch all data
$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Calculate duration
    if ($row['logout_time']) {
        $login = new DateTime($row['login_time']);
        $logout = new DateTime($row['logout_time']);
        $interval = $login->diff($logout);
        $row['duration'] = $interval->format('%H:%I');
    } else {
        $row['duration'] = 'Active';
    }
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
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="sitin_data.xlsx"');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
}

// Function to generate PDF
function generatePDF($data) {
    $html = '
    <html>
    <head>
        <style>
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            h1 { text-align: center; }
        </style>
    </head>
    <body>
        <h1>Sitin Data Report</h1>
        <table>
            <thead>
                <tr>
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
            <td>' . ($row['logout_time'] ? htmlspecialchars($row['logout_time']) : '-') . '</td>
            <td>' . htmlspecialchars($row['duration']) . '</td>
            <td>' . htmlspecialchars($row['status']) . '</td>
        </tr>';
    }
    
    $html .= '</tbody></table></body></html>';
    
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', true);
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="sitin_data.pdf"');
    
    echo $dompdf->output();
}

// Generate the requested export type
switch ($type) {
    case 'csv':
        generateCSV($data);
        break;
    case 'excel':
        generateExcel($data);
        break;
    case 'pdf':
        generatePDF($data);
        break;
    default:
        header("HTTP/1.1 400 Bad Request");
        echo "Invalid export type";
        break;
}

// Close database connections
mysqli_stmt_close($stmt);
mysqli_close($conn);
?> 