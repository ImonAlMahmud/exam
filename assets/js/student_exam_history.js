document.addEventListener('DOMContentLoaded', function() {
    const examHistoryBody = document.getElementById('exam-history-body');

    // BASE_URL is globally available from the PHP file.

    // --- Fetch & Display Exam History ---
    const fetchExamHistory = async () => {
        try {
            const response = await fetch(BASE_URL + 'api/student/get_submissions.php');
            const result = await response.json();
            
            examHistoryBody.innerHTML = ''; // Clear loader

            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(sub => {
                    const score = `${sub.marks_obtained} / ${sub.total_marks}`;
                    const percentage = (sub.total_marks > 0) ? ((sub.marks_obtained / sub.total_marks) * 100).toFixed(0) : 0;

                    // Parse date as local time, as PHP is now sending local date/time
                    const submissionDate = new Date(sub.scheduled_date); 
                    
                    const row = `
                        <tr>
                            <td>${sub.exam_title}</td>
                            <td>${submissionDate.toLocaleDateString()}</td>
                            <td>${score} (${percentage}%)</td>
                            <td><span class="rank-badge">#${sub.student_rank}</span></td>
                            <td class="actions">
                                <a href="${BASE_URL}student/view_answer_sheet.php?submission_id=${sub.submission_id}" target="_blank" class="btn-action" title="View Answer Sheet">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    `;
                    examHistoryBody.innerHTML += row;
                });
            } else {
                examHistoryBody.innerHTML = '<tr><td colspan="5">You have not taken any exams yet.</td></tr>';
            }
        } catch (error) {
            console.error('Error fetching exam history:', error);
            examHistoryBody.innerHTML = '<tr><td colspan="5">Could not load exam history. Please try refreshing the page.</td></tr>';
        }
    };

    // --- Initializers & Auto-refresh ---
    fetchExamHistory();
    // Refresh history every minute (optional)
    setInterval(fetchExamHistory, 60000); 
});