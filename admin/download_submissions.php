<?php
// PHP error reporting needs to be handled carefully before sending headers for PDF/Excel.
// For now, we'll ensure it's off before outputting Excel/PDF.
error_reporting(E_ALL); // Keep for general debugging
ini_set('display_errors', 1); // Keep for general debugging
ini_set('display_startup_errors', 1);

require_once '../includes/config.php'; // Includes DB connection
require_once '../includes/auth_check.php';
require_once '../vendor/autoload.php'; // For PhpSpreadsheet and TCPDF

require_login('Admin'); // Only Admins can download reports

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use TCPDF; 

// Get report parameters
$report_type = $_GET['report_type'] ?? 'all_submissions';
$exam_id = $_GET['exam_id'] ?? null;
$format = $_GET['format'] ?? 'excel'; // 'excel' or 'pdf'

$data = [];
$report_title = '';
$exam_meta_data = []; // To store additional exam info for reports

try {
    if ($report_type === 'all_submissions') {
        $report_title = "All Exam Submissions";
        $sql = "
            SELECT 
                s.id AS submission_id,
                e.title AS exam_title,
                u.name AS student_name,
                u.roll_no,
                u.batch_name,
                s.marks_obtained,
                s.total_marks,
                s.submission_time,
                (
                    SELECT COUNT(*) + 1
                    FROM submissions s2
                    WHERE s2.schedule_id = s.schedule_id AND (s2.marks_obtained > s.marks_obtained OR (s2.marks_obtained = s.marks_obtained AND s2.submission_time < s.submission_time))
                ) AS student_rank
            FROM submissions s
            JOIN exams e ON s.exam_id = e.id
            JOIN users u ON s.student_id = u.id
            ORDER BY s.submission_time DESC
        ";
        $stmt = $pdo->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($report_type === 'submissions_by_exam' && $exam_id) {
        $stmt_exam = $pdo->prepare("SELECT title, description FROM exams WHERE id = ?");
        $stmt_exam->execute([$exam_id]);
        $exam_details = $stmt_exam->fetch(PDO::FETCH_ASSOC);
        if (!$exam_details) die("Exam not found.");
        
        $report_title = "Submissions for Exam: " . $exam_details['title'];
        $exam_meta_data['title'] = $exam_details['title'];
        $exam_meta_data['description'] = $exam_details['description'];


        $sql = "
            SELECT 
                s.id AS submission_id,
                u.name AS student_name,
                u.roll_no,
                u.batch_name,
                s.marks_obtained,
                s.total_marks,
                s.submission_time,
                (
                    SELECT COUNT(*) + 1
                    FROM submissions s2
                    WHERE s2.schedule_id = s.schedule_id AND (s2.marks_obtained > s.marks_obtained OR (s2.marks_obtained = s.marks_obtained AND s2.submission_time < s.submission_time))
                ) AS student_rank
            FROM submissions s
            JOIN users u ON s.student_id = u.id
            WHERE s.exam_id = ?
            ORDER BY student_rank ASC, s.submission_time ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$exam_id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        die("Invalid report type or missing exam ID.");
    }

} catch (Exception $e) {
    error_log("Report generation error: " . $e->getMessage());
    die("Error generating report: " . $e->getMessage());
}

// --- Generate Report based on format ---
if ($format === 'excel') {
    // IMPORTANT: Clear output buffer before sending headers
    if (ob_get_level()) {
        ob_end_clean();
    }
    // Turn off error reporting explicitly for Excel output
    ini_set('display_errors', 0);
    error_reporting(0);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Sanitize sheet title to remove invalid characters and truncate if too long
    $sanitized_report_title = preg_replace('/[\\\\\\/*?\\[\\]:]/', '', $report_title); // Remove invalid characters
    $sanitized_report_title = substr($sanitized_report_title, 0, 31); // Truncate to max 31 chars
    $sheet->setTitle($sanitized_report_title);

    // Get App Name and Logo Path
    $appName = get_setting('app_name', 'HTEC Exam System');
    $logoPath = ROOT_PATH . 'uploads/website/' . get_setting('logo_light', 'logo-light.png');

    // Add Logo (if exists)
    if (file_exists($logoPath)) {
        $drawing = new \PhpOffice\PhpSpreadsheet\Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('HTEC Logo');
        $drawing->setPath($logoPath);
        $drawing->setHeight(50); // Adjust height as needed
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX(5); // Adjust X position
        $drawing->setOffsetY(5); // Adjust Y position
        $drawing->setWorksheet($sheet);
        $sheet->getRowDimension(1)->setRowHeight(60); // Adjust row height to fit logo
    } else {
        $sheet->setCellValue('A1', $appName);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells('A1:F1');
    }
    
    // Report Header and Meta-data
    $current_row = 2; // Start after logo/app name
    if (!empty($exam_meta_data['title'])) {
        $sheet->setCellValue('A' . $current_row, 'Exam: ' . $exam_meta_data['title']);
        $sheet->getStyle('A' . $current_row)->getFont()->setBold(true);
        $sheet->mergeCells('A' . $current_row . ':F' . $current_row);
        $current_row++;
        if (!empty($exam_meta_data['description'])) {
            $sheet->setCellValue('A' . $current_row, $exam_meta_data['description']);
            $sheet->mergeCells('A' . $current_row . ':F' . $current_row);
            $current_row++;
        }
    }
    $sheet->setCellValue('A' . $current_row, 'Report Type: ' . $report_title);
    $sheet->mergeCells('A' . $current_row . ':F' . $current_row);
    $current_row++;
    $sheet->setCellValue('A' . $current_row, 'Generated On: ' . date('Y-m-d H:i:s'));
    $sheet->mergeCells('A' . $current_row . ':F' . $current_row);
    $current_row++;

    // Add empty row for spacing
    $sheet->getRowDimension($current_row)->setRowHeight(10);
    $current_row++;

    // Column Headers
    $header_cols = ['Rank', 'Student Name', 'Roll No', 'Batch', 'Score', 'Submission Time'];
    $sheet->fromArray($header_cols, NULL, 'A' . $current_row);
    $sheet->getStyle('A' . $current_row . ':F' . $current_row)->getFont()->setBold(true);
    $current_row++;

    // Data Rows
    foreach ($data as $submission) {
        $sheet->setCellValue('A' . $current_row, '#' . $submission['student_rank']);
        $sheet->setCellValue('B' . $current_row, $submission['student_name']);
        $sheet->setCellValue('C' . $current_row, $submission['roll_no']);
        $sheet->setCellValue('D' . $current_row, $submission['batch_name']);
        $sheet->setCellValue('E' . $current_row, $submission['marks_obtained'] . ' / ' . $submission['total_marks']);
        $sheet->setCellValue('F' . $current_row, date('Y-m-d H:i:s', strtotime($submission['submission_time'])));
        $current_row++;
    }

    // Auto-size columns for better readability
    foreach (range('A', 'F') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    $writer = new Xlsx($spreadsheet);
    $filename = str_replace([' ', ':', '/'], '_', $report_title) . '_' . date('Ymd_His') . '.xlsx'; // Sanitize filename

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit();

} elseif ($format === 'pdf') {
    // IMPORTANT: Clear output buffer BEFORE TCPDF initialization
    if (ob_get_level()) {
        ob_end_clean();
    }
    // Turn off error reporting explicitly for PDF output
    ini_set('display_errors', 0);
    error_reporting(0);
    
    // Create new PDF document
    class MYPDF_REPORT extends TCPDF {
        private $reportAppName;
        private $reportLogoPath;
        private $reportTitle;
        private $examTitle = '';
        private $examDescription = '';

        public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $pdfa = false) {
            parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
            $this->reportAppName = get_setting('app_name', 'HTEC Exam System');
            $this->reportLogoPath = ROOT_PATH . 'uploads/website/' . get_setting('logo_light', 'logo-light.png');
        }

        public function setReportMetaData($title, $examTitle = '', $examDescription = '') {
            $this->reportTitle = $title;
            $this.examTitle = $examTitle;
            $this.examDescription = $examDescription;
        }

        //Page header
        public function Header() {
            // Logo
            if (file_exists($this->reportLogoPath)) {
                $this.Image($this->reportLogoPath, 10, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            }
            $this->SetFont('dejavusans', 'B', 16); // Using dejavusans for Unicode (Bangla) support
            $this->Cell(0, 15, $this->reportAppName, 0, false, 'C', 0, '', 0, false, 'M', 'M');
            $this->Ln(5);
            $this->SetFont('dejavusans', '', 12);
            $this->Cell(0, 15, $this->reportTitle, 0, false, 'C', 0, '', 0, false, 'M', 'M');
            $this->Ln(5);
            if (!empty($this->examTitle)) {
                $this->SetFont('dejavusans', 'B', 10);
                $this->Cell(0, 15, 'Exam: ' . $this->examTitle, 0, false, 'C', 0, '', 0, false, 'M', 'M');
                $this->Ln(4);
            }
            $this->SetFont('dejavusans', '', 9);
            $this->Cell(0, 15, 'Generated On: ' . date('Y-m-d H:i:s'), 0, false, 'C', 0, '', 0, false, 'M', 'M');
            $this->Ln(10);
            $this->Line(PDF_MARGIN_LEFT, $this->GetY(), $this->getPageWidth() - PDF_MARGIN_RIGHT, $this->GetY());
        }

        //Page footer
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('dejavusans', 'I', 8);
            $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        }
    }

    $pdf = new MYPDF_REPORT(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->setReportMetaData($report_title, $exam_meta_data['title'] ?? '', $exam_meta_data['description'] ?? '');

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor(get_setting('app_name', 'HTEC Exam System'));
    $pdf->SetTitle($report_title);
    $pdf->SetSubject('Exam Submission Report');
    $pdf->SetKeywords('HTEC, Exam, Report, Submissions');

    $pdf->setPrintHeader(true);
    $pdf->setPrintFooter(true);

    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP + 30, PDF_MARGIN_RIGHT); // Adjust top margin for custom header
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    $pdf->AddPage();

    // Set font for Bangla support (using a Unicode font)
    $pdf->SetFont('dejavusans', '', 10); 

    // Table Headers
    $tbl = '<table border="1" cellpadding="5" cellspacing="0" style="width:100%;">';
    $tbl .= '<thead><tr style="background-color:#f0f0f0; font-weight:bold; text-align:center;">';
    $tbl .= '<td>Rank</td><td>Student Name</td><td>Roll No</td><td>Batch</td><td>Score</td><td>Submission Time</td>';
    $tbl .= '</tr></thead><tbody>';

    // Table Data
    foreach ($data as $submission) {
        $tbl .= '<tr>';
        $tbl .= '<td>#' . htmlspecialchars($submission['student_rank']) . '</td>';
        $tbl .= '<td>' . htmlspecialchars($submission['student_name']) . '</td>';
        $tbl .= '<td>' . htmlspecialchars($submission['roll_no']) . '</td>';
        $tbl .= '<td>' . htmlspecialchars($submission['batch_name']) . '</td>';
        $tbl .= '<td>' . htmlspecialchars($submission['marks_obtained']) . ' / ' . htmlspecialchars($submission['total_marks']) . '</td>';
        $tbl .= '<td>' . date('Y-m-d H:i:s', strtotime($submission['submission_time'])) . '</td>';
        $tbl .= '</tr>';
    }
    $tbl .= '</tbody></table>';

    $pdf->writeHTML($tbl, true, false, true, false, '');

    $filename = str_replace([' ', ':', '/'], '_', $report_title) . '_' . date('Ymd_His') . '.pdf'; // Sanitize filename
    $pdf->Output($filename, 'I');
    exit();
} else {
    http_response_code(400);
    die("Invalid report format.");
}