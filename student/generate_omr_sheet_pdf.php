<?php
// Enable error reporting at the very top for debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_once '../vendor/autoload.php'; // For TCPDF

require_login('Student'); // Ensure only students can download

// --- Create PDF using TCPDF ---
class OMR_Sheet_PDF extends TCPDF {
    // No custom header/footer needed for a clean OMR sheet
    public function Header() {
        // Leave this empty to have no header
    }
    public function Footer() {
        // Leave this empty to have no footer
    }
}

// --- IMPORTANT: Clear output buffer before TCPDF initialization ---
if (ob_get_level()) {
    ob_end_clean();
}
// Turn off error reporting explicitly for PDF output
ini_set('display_errors', 0);
error_reporting(0);

$pdf = new OMR_Sheet_PDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor(get_setting('app_name', 'HTEC Exam System'));
$pdf->SetTitle('OMR Answer Sheet');
$pdf->SetSubject('Blank OMR Answer Sheet');
$pdf->SetKeywords('OMR, Answer, Sheet');

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15); // A4 Margins
$pdf->SetAutoPageBreak(TRUE, 15);

$pdf->AddPage();

// --- Alignment Markers (Crucial for OpenCV) ---
// Black squares at corners
$pdf->SetFillColor(0, 0, 0); // Black color
$pdf->Rect(10, 10, 10, 10, 'F'); // Top-left
$pdf->Rect(190, 10, 10, 10, 'F'); // Top-right
$pdf->Rect(10, 277, 10, 10, 'F'); // Bottom-left
$pdf->Rect(190, 277, 10, 10, 'F'); // Bottom-right


// --- Header Section ---
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetY(25); // Position below top markers
$pdf->Cell(0, 10, 'OMR ANSWER SHEET', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 8, get_setting('app_name', 'HTEC Exam System'), 0, 1, 'C');
$pdf->Ln(5);

// --- Student Info Section ---
$pdf->SetFont('helvetica', '', 11);
$pdf->SetFillColor(255, 255, 255);
$pdf->SetDrawColor(50, 50, 50);

$info_html = '
<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <td width="30%"><b>Student Name:</b></td>
        <td width="70%"></td>
    </tr>
    <tr>
        <td><b>Roll Number:</b></td>
        <td></td>
    </tr>
    <tr>
        <td><b>Exam Title:</b></td>
        <td></td>
    </tr>
    <tr>
        <td><b>Date:</b></td>
        <td></td>
    </tr>
</table>
';
$pdf->writeHTML($info_html, true, false, true, false, '');
$pdf->Ln(10);

// --- Answer Grid Section ---
$pdf->SetFont('helvetica', '', 10);

$total_questions = 50;
$questions_per_column = 25;
$options = ['A', 'B', 'C', 'D'];
$bubble_radius = 2.5; // in mm
$bubble_spacing = 8; // Horizontal spacing between bubbles
$row_height = 8; // Vertical height for each question row

$start_y = $pdf->GetY();
$col1_x = 20;
$col2_x = 115;

for ($i = 1; $i <= $total_questions; $i++) {
    $current_col = ($i <= $questions_per_column) ? 0 : 1;
    $current_x = ($current_col == 0) ? $col1_x : $col2_x;
    $current_y = $start_y + (($i - 1) % $questions_per_column) * $row_height;

    // Question Number
    $pdf->SetXY($current_x, $current_y);
    $pdf->Cell(15, 6, "{$i}.", 0, 0, 'R');

    // Bubbles
    foreach ($options as $j => $opt) {
        $bubble_x = $current_x + 20 + ($j * $bubble_spacing);
        $bubble_y = $current_y + 3; // Center vertically
        $pdf->Circle($bubble_x, $bubble_y, $bubble_radius, 0, 360, 'D'); // 'D' for draw
        $pdf->SetXY($bubble_x - 1.5, $bubble_y - 4.5); // Position for text inside bubble
        $pdf->Cell(3, 3, $opt, 0, 0, 'C');
    }
}

// --- Close and output PDF document ---
$pdf->Output('OMR_Answer_Sheet.pdf', 'I');
exit();
?>