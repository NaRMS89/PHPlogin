<?php
// Add this function for exporting Sit-in Data to PDF
function exportSitInDataToPDF($data) {
    require_once __DIR__ . '/vendor/autoload.php'; // Adjust the path if necessary
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

    // Add table header
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(30, 7, 'ID Number', 1, 0, 'C', 1);
    $pdf->Cell(40, 7, 'Purpose', 1, 0, 'C', 1);
    $pdf->Cell(30, 7, 'Lab', 1, 0, 'C', 1);
    $pdf->Cell(40, 7, 'Login Time', 1, 0, 'C', 1);
    $pdf->Cell(40, 7, 'Logout Time', 1, 0, 'C', 1);
    $pdf->Cell(20, 7, 'Duration', 1, 1, 'C', 1);

    // Add table rows
    $pdf->SetFont('helvetica', '', 10);
    foreach ($data as $row) {
        $pdf->Cell(30, 7, $row['id_number'], 1);
        $pdf->Cell(40, 7, $row['purpose'], 1);
        $pdf->Cell(30, 7, $row['lab'], 1);
        $pdf->Cell(40, 7, $row['login_time'], 1);
        $pdf->Cell(40, 7, $row['logout_time'], 1);
        $pdf->Cell(20, 7, $row['duration'], 1, 1);
    }

    // Output PDF to browser
    $pdf->Output('sit_in_data_report.pdf', 'I');
}
