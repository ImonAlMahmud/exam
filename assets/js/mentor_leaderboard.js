document.addEventListener('DOMContentLoaded', function() {
    const leaderboardBody = document.getElementById('leaderboard-body');

    // BASE_URL and SCHEDULE_ID are globally available from the PHP file.

    const fetchLeaderboard = async () => {
        try {
            const response = await fetch(`${BASE_URL}api/mentor/get_leaderboard.php?schedule_id=${SCHEDULE_ID}`);
            const result = await response.json();
            
            leaderboardBody.innerHTML = ''; // Clear loader

            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(entry => {
                    const score = `${entry.marks_obtained} / ${entry.total_marks}`;
                    const row = `
                        <tr>
                            <td><span class="rank-badge">#${entry.student_rank}</span></td>
                            <td>${entry.student_name}</td>
                            <td>${entry.roll_no || 'N/A'}</td>
                            <td>${entry.batch_name || 'N/A'}</td>
                            <td>${score}</td>
                            <td>${new Date(entry.submission_time).toLocaleTimeString()}</td>
                            <td class="actions">
                                <a href="${BASE_URL}student/view_answer_sheet.php?submission_id=${entry.submission_id}" target="_blank" class="btn-action" title="View Answer Sheet">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    `;
                    leaderboardBody.innerHTML += row;
                });
            } else {
                leaderboardBody.innerHTML = '<tr><td colspan="7">No submissions yet for this exam.</td></tr>';
            }
        } catch (error) {
            console.error('Error fetching leaderboard:', error);
            leaderboardBody.innerHTML = '<tr><td colspan="7">Could not load leaderboard.</td></tr>';
        }
    };

    // Initial load
    fetchLeaderboard();
});