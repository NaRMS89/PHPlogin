<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get the export format and data from the request
$format = $_GET['format'] ?? '';
$data = json_decode($_GET['data'] ?? '[]', true);

// Validate the data
if (empty($data)) {
    die('No data to export');
}

// Function to format the data for export
function formatDataForExport($data) {
    $formattedData = [];
    
    // Add headers
    $formattedData[] = [
        'Student ID',
        'Purpose',
        'Lab',
        'Login Time',
        'Logout Time',
        'Duration'
    ];
    
    // Add data rows
    foreach ($data as $record) {
        $loginTime = new DateTime($record['login_time']);
        $logoutTime = new DateTime($record['logout_time']);
        $duration = $loginTime->diff($logoutTime);
        
        $formattedData[] = [
            $record['id_number'],
            $record['purpose'],
            $record['lab'],
            $record['login_time'],
            $record['logout_time'],
            $duration->format('%H:%I')
        ];
    }
    
    return $formattedData;
}

// Export as PDF
if ($format === 'pdf') {
    require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Lab Management System');
    $pdf->SetTitle('Sit-in Data Report');
    
    // Set default header data
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'Sit-in Data Report', 'Generated on: ' . date('Y-m-d H:i:s'));
    
    // Set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Create the table content
    $formattedData = formatDataForExport($data);
    
    // Create the HTML table
    $html = '<table border="1" cellpadding="4">
                <thead>
                    <tr style="background-color: #f2f2f2;">';
    
    // Add headers
    foreach ($formattedData[0] as $header) {
        $html .= '<th>' . htmlspecialchars($header) . '</th>';
    }
    
    $html .= '</tr></thead><tbody>';
    
    // Add data rows
    for ($i = 1; $i < count($formattedData); $i++) {
        $html .= '<tr>';
        foreach ($formattedData[$i] as $cell) {
            $html .= '<td>' . htmlspecialchars($cell) . '</td>';
        }
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    
    // Output the HTML content
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Close and output PDF document
    $pdf->Output('sit-in_report.pdf', 'D');
    exit();
}

// Export as Excel
elseif ($format === 'excel') {
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
    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Set headers style
    $sheet->getStyle('A1:F1')->getFont()->setBold(true);
    $sheet->getStyle('A1:F1')->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('F2F2F2');
    
    // Create the Excel file
    $writer = new Xlsx($spreadsheet);
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="sit-in_report.xlsx"');
    header('Cache-Control: max-age=0');
    
    // Save file to PHP output
    $writer->save('php://output');
    exit();
}

// Export as CSV
elseif ($format === 'csv') {
    // Set headers for download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sit-in_report.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Format the data
    $formattedData = formatDataForExport($data);
    
    // Write data to CSV
    foreach ($formattedData as $row) {
        fputcsv($output, $row);
    }
    
    // Close the stream
    fclose($output);
    exit();
}

// Invalid format
else {
    die('Invalid export format');
}
?> 