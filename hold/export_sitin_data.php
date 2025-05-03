<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get parameters from the request
$type = $_GET['type'] ?? '';
$filterBy = $_GET['filterBy'] ?? '';
$filterValue = $_GET['filterValue'] ?? '';
$startDate = $_GET['startDate'] ?? '';
$endDate = $_GET['endDate'] ?? '';
$fileName = $_GET['fileName'] ?? 'sit_in_data';
$scope = $_GET['scope'] ?? 'all';

// Build the query
$query = "SELECT sr.*, i.name 
          FROM sitin_report sr 
          JOIN info i ON sr.id_number = i.id_number 
          WHERE 1=1";

$params = [];
$types = "";

if ($startDate && $endDate) {
    $query .= " AND sr.login_time BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= "ss";
}

if ($filterBy && $filterValue && $scope !== 'all') {
    $query .= " AND sr.{$filterBy} = ?";
    $params[] = $filterValue;
    $types .= "s";
}

$query .= " ORDER BY sr.login_time DESC";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Fetch all data
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Function to format data for export
function formatDataForExport($data) {
    $formattedData = [];
    
    // Add headers
    $formattedData[] = [
        'ID Number',
        'Student Name',
        'Lab',
        'Purpose',
        'Login Time',
        'Logout Time',
        'Duration'
    ];
    
    // Add data rows
    foreach ($data as $record) {
        $formattedData[] = [
            $record['id_number'],
            $record['name'],
            $record['lab'],
            $record['purpose'],
            $record['login_time'],
            $record['logout_time'],
            $record['duration']
        ];
    }
    
    return $formattedData;
}

// Export as PDF
if ($type === 'pdf') {
    require_once __DIR__ . '/vendor/autoload.php';
    $pdf = new \TCPDF();

    // Set document information
    $pdf->SetCreator('Admin Dashboard');
    $pdf->SetAuthor('System Admin');
    $pdf->SetTitle('Sit-in Data Report');
    $pdf->SetSubject('Sit-in Data');
    $pdf->SetKeywords('Sit-in, Report, PDF');

    // Add a page
    $pdf->AddPage();

    // Set header
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Sit-in Data Report', 0, 1, 'C');

    // Add report details
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 10, 'Date Range: ' . $startDate . ' to ' . $endDate, 0, 1);
    if ($filterBy && $filterValue) {
        $pdf->Cell(0, 10, 'Filter: ' . ucfirst($filterBy) . ' = ' . $filterValue, 0, 1);
    }
    $pdf->Ln(10);

    // Add table header
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(30, 7, 'ID Number', 1, 0, 'C', 1);
    $pdf->Cell(40, 7, 'Student Name', 1, 0, 'C', 1);
    $pdf->Cell(30, 7, 'Lab', 1, 0, 'C', 1);
    $pdf->Cell(40, 7, 'Purpose', 1, 0, 'C', 1);
    $pdf->Cell(40, 7, 'Login Time', 1, 0, 'C', 1);
    $pdf->Cell(40, 7, 'Logout Time', 1, 0, 'C', 1);
    $pdf->Cell(20, 7, 'Duration', 1, 1, 'C', 1);

    // Add table rows
    $pdf->SetFont('helvetica', '', 10);
    foreach ($data as $row) {
        $pdf->Cell(30, 7, $row['id_number'], 1);
        $pdf->Cell(40, 7, $row['name'], 1);
        $pdf->Cell(30, 7, $row['lab'], 1);
        $pdf->Cell(40, 7, $row['purpose'], 1);
        $pdf->Cell(40, 7, $row['login_time'], 1);
        $pdf->Cell(40, 7, $row['logout_time'], 1);
        $pdf->Cell(20, 7, $row['duration'], 1, 1);
    }

    // Output PDF
    $pdf->Output($fileName . '.pdf', 'D');
    exit();
}

// Export as Excel
elseif ($type === 'excel') {
    require_once '../vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
    
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    
    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator('Lab Management System')
        ->setLastModifiedBy('Lab Management System')
        ->setTitle('Sit-in Data Report')
        ->setSubject('Sit-in Data Report')
        ->setDescription('Sit-in Data Report generated on ' . date('Y-m-d H:i:s'));
    
    // Format the data
    $formattedData = formatDataForExport($data);
    
    // Add data to the sheet
    $sheet->fromArray($formattedData, NULL, 'A1');
    
    // Auto-size columns
    foreach (range('A', 'G') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Set headers style
    $sheet->getStyle('A1:G1')->getFont()->setBold(true);
    $sheet->getStyle('A1:G1')->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('F2F2F2');
    
    // Create the Excel file
    $writer = new Xlsx($spreadsheet);
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    // Save file to PHP output
    $writer->save('php://output');
    exit();
}

// Export as CSV
elseif ($type === 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="' . $fileName . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Format the data
    $formattedData = formatDataForExport($data);
    
    // Write data to CSV
    foreach ($formattedData as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}

// Print view
elseif ($type === 'print') {
    // Generate HTML for printing
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <title>Sit-in Data Report</title>
        <style>
            body { font-family: Arial, sans-serif; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .report-header { margin-bottom: 20px; }
            .report-footer { margin-top: 20px; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="report-header">
            <h1>Sit-in Data Report</h1>
            <p>Date Range: ' . $startDate . ' to ' . $endDate . '</p>';
    
    if ($filterBy && $filterValue) {
        $html .= '<p>Filter: ' . ucfirst($filterBy) . ' = ' . $filterValue . '</p>';
    }
    
    $html .= '</div>
        <table>
            <thead>
                <tr>
                    <th>ID Number</th>
                    <th>Student Name</th>
                    <th>Lab</th>
                    <th>Purpose</th>
                    <th>Login Time</th>
                    <th>Logout Time</th>
                    <th>Duration</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($data as $row) {
        $html .= '<tr>
            <td>' . htmlspecialchars($row['id_number']) . '</td>
            <td>' . htmlspecialchars($row['name']) . '</td>
            <td>' . htmlspecialchars($row['lab']) . '</td>
            <td>' . htmlspecialchars($row['purpose']) . '</td>
            <td>' . htmlspecialchars($row['login_time']) . '</td>
            <td>' . htmlspecialchars($row['logout_time']) . '</td>
            <td>' . htmlspecialchars($row['duration']) . '</td>
        </tr>';
    }
    
    $html .= '</tbody>
        </table>
        <div class="report-footer">
            <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
        </div>
    </body>
    </html>';
    
    echo $html;
    exit();
}

// Close database connections
$stmt->close();
$conn->close();
?> 