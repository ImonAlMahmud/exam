document.addEventListener('DOMContentLoaded', function() {
    const reportTypeSelect = document.getElementById('report_type');
    const examSelectGroup = document.getElementById('examSelectGroup');
    const examSelect = document.getElementById('exam_id');
    const downloadPdfBtn = document.getElementById('downloadPdfBtn');
    const downloadExcelBtn = document.getElementById('downloadExcelBtn');
    const reportMessage = document.getElementById('reportMessage');

    // BASE_URL is globally available from the PHP file.

    // Function to fetch exams for dropdown
    const fetchExams = async () => {
        try {
            const response = await fetch(BASE_URL + 'api/exams/read.php');
            const result = await response.json();
            
            examSelect.innerHTML = '<option value="">Select an Exam</option>';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(exam => {
                    examSelect.innerHTML += `<option value="${exam.id}">${exam.title}</option>`;
                });
            } else {
                examSelect.innerHTML = '<option value="">No exams available</option>';
            }
        } catch (error) {
            console.error('Error fetching exams for report:', error);
            reportMessage.innerHTML = `<div class="message error">Error loading exams for report.</div>`;
        }
    };

    // Toggle exam select dropdown based on report type
    reportTypeSelect.addEventListener('change', function() {
        if (this.value === 'submissions_by_exam') {
            examSelectGroup.style.display = 'block';
            fetchExams(); // Load exams when relevant report type is selected
        } else {
            examSelectGroup.style.display = 'none';
            examSelect.value = ''; // Clear selection
        }
    });

    // Handle report download buttons
    downloadPdfBtn.addEventListener('click', function() {
        generateReport('pdf');
    });

    downloadExcelBtn.addEventListener('click', function() {
        generateReport('excel');
    });

    const generateReport = (format) => {
        reportMessage.innerHTML = ''; // Clear previous messages
        let url = `${BASE_URL}admin/download_submissions.php?format=${format}`;
        
        const reportType = reportTypeSelect.value;
        const examId = examSelect.value;

        if (reportType === 'all_submissions') {
            url += `&report_type=all_submissions`;
        } else if (reportType === 'submissions_by_exam') {
            if (!examId) {
                reportMessage.innerHTML = `<div class="message error">Please select an exam for this report type.</div>`;
                return;
            }
            url += `&report_type=submissions_by_exam&exam_id=${examId}`;
        }

        // Open the report URL in a new tab/window for download
        window.open(url, '_blank');
        reportMessage.innerHTML = `<div class="message success">Report generation request sent. Your download should begin shortly.</div>`;
    };

    // Initial check (in case default option needs exam select)
    if (reportTypeSelect.value === 'submissions_by_exam') {
        examSelectGroup.style.display = 'block';
        fetchExams();
    }
});