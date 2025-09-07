<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';
require_once 'vendor/autoload.php'; // Use Composer's autoloader

require_login('Student'); // Only logged-in students can download their own answer sheets

$submission_id = isset($_GET['submission_id']) ? (int)$_GET['submission_id'] : 0;
$student_id = $_SESSION['user_id'];

// --- Security Check: Ensure the logged-in student owns this submission ---
$stmt_check = $pdo->prepare("
    SELECT s.*, e.title as exam_title, u.name as student_name, u.roll_no, u.batch_name 
    FROM submissions s 
    JOIN exams e ON s.exam_id = e.id 
    JOIN users u ON s.student_id = u.id
    WHERE s.id = ? AND s.student_id = ?
");
$stmt_check->execute([$submission_id, $student_id]);
$submission = $stmt_check->fetch(PDO::FETCH_ASSOC);

if (!$submission) {
    die("Error: Submission not found or you do not have permission to view it.");
}

// --- Fetch all questions and student's answers for this submission ---
$sql = "
    SELECT 
        q.id as question_id,
        q.question_text, 
        q.option_1, q.option_2, q.option_3, q.option_4, 
        q.correct_answer,
        a.selected_option
    FROM questions q
    LEFT JOIN answers a ON q.id = a.question_id AND a.submission_id = ?
    WHERE q.exam_id = ?
    ORDER BY q.id ASC
";
$stmt_answers = $pdo->prepare($sql);
$stmt_answers->execute([$submission_id, $submission['exam_id']]);
$questions = $stmt_answers->fetchAll(PDO::FETCH_ASSOC);

// --- Create PDF using TCPDF ---
// Make sure TCPDF is correctly installed via Composer and autoload.php is included.
class MYPDF extends TCPDF {
    private $appName;
    private $logoPath;

    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $pdfa = false) {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
        $this->appName = get_setting('app_name', 'HTEC Exam System');
        $this->logoPath = ROOT_PATH . 'uploads/website/' . get_setting('logo_light', 'logo-light.png');
    }

    // Page header
    public function Header() {
        // Check if logo file exists
        if (file_exists($this->logoPath)) {
            $this->Image($this->logoPath, 10, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 15, $this->appName, 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(5);
        $this->SetFont('helvetica', '', 12);
        $this->Cell(0, 15, 'Answer Sheet', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(10);
        $this->Line(PDF_MARGIN_LEFT, $this->GetY(), $this->getPageWidth() - PDF_MARGIN_RIGHT, $this->GetY());
    }

    // Page footer
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor(get_setting('app_name'));
$pdf->SetTitle('Answer Sheet - ' . $submission['exam_title']);
$pdf->SetSubject('Exam Answer Sheet');
$pdf->SetKeywords('HTEC, Exam, Answer, Sheet, PDF');

// Remove default header/footer
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);

$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

$pdf->AddPage();

// --- PDF Content ---
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Write(0, htmlspecialchars($submission['exam_title']), '', 0, 'C', true, 0, false, false, 0);
$pdf->Ln(5);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 0, 'Student Name: ' . htmlspecialchars($submission['student_name']), 0, 1, 'L');
if (!empty($submission['roll_no'])) {
    $pdf->Cell(0, 0, 'Roll No: ' . htmlspecialchars($submission['roll_no']), 0, 1, 'L');
}
if (!empty($submission['batch_name'])) {
    $pdf->Cell(0, 0, 'Batch: ' . htmlspecialchars($submission['batch_name']), 0, 1, 'L');
}
$pdf->Cell(0, 0, 'Score: ' . $submission['marks_obtained'] . ' / ' . $submission['total_marks'], 0, 1, 'L');
$pdf->Ln(10);
$pdf->SetDrawColor(200, 200, 200); // Light grey line

$question_num = 1;
foreach ($questions as $q) {
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->MultiCell(0, 5, "Q{$question_num}: " . htmlspecialchars($q['question_text']), 0, 'L', false, 1);
    $pdf->Ln(2);
    $pdf->SetFont('helvetica', '', 10);

    for ($i = 1; $i <= 4; $i++) {
        $option_text = htmlspecialchars($q['option_' . $i]);
        $html = "({$i}) {$option_text}";
        
        $is_correct_answer = ($i == $q['correct_answer']);
        $is_selected_answer = ($q['selected_option'] !== null && $i == $q['selected_option']);

        // Determine background color for options
        if ($is_selected_answer && $is_correct_answer) {
            $pdf->SetFillColor(217, 255, 217); // Light Green: Selected and Correct
        } elseif ($is_selected_answer && !$is_correct_answer) {
            $pdf->SetFillColor(255, 217, 217); // Light Red: Selected but Incorrect
        } elseif ($is_correct_answer) {
            $pdf->SetFillColor(230, 242, 255); // Light Blue: Correct Answer (if not selected by student)
        } else {
            $pdf->SetFillColor(255, 255, 255); // White: Normal Option
        }
        
        $pdf->MultiCell(0, 8, $html, 0, 'L', true, 1); // 'true' for fill
    }
    $pdf->Ln(8);
    $question_num++;
}

// Close and output PDF document
$pdf->Output('Answer_Sheet_' . $submission_id . '.pdf', 'I');
exit();
?>