document.addEventListener('DOMContentLoaded', function() {
    const mentorExamsTableBody = document.getElementById('mentor-exams-table-body');
    const mentorScheduledExamsTableBody = document.getElementById('mentor-scheduled-exams-table-body');
    
    // Create Exam Modal elements
    const examModal = document.getElementById('examModal');
    const examForm = document.getElementById('examForm');
    const createExamNavLink = document.getElementById('createExamNavLink'); // New Nav Link
    const closeExamModalBtn = document.getElementById('closeExamModalBtn');
    const examModalTitle = document.getElementById('examModalTitle');
    const examIdInput = document.getElementById('examId'); 
    const titleInput = document.getElementById('title');
    const descriptionInput = document.getElementById('description');
    const shuffleQuestionsCheckbox = document.getElementById('shuffle_questions');
    const shuffleOptionsCheckbox = document.getElementById('shuffle_options');
    const isOmrExamCheckbox = document.getElementById('is_omr_exam');

    // Schedule Exam Modal elements
    const scheduleModal = document.getElementById('scheduleModal');
    const scheduleForm = document.getElementById('scheduleForm');
    const closeScheduleModalBtn = document.getElementById('closeScheduleModalBtn');
    const scheduledExamTitleSpan = document.getElementById('scheduledExamTitle');
    const scheduleExamIdInput = document.getElementById('scheduleExamId');
    const mentorSelect = document.getElementById('mentor_id');
    const createScheduleBtn = scheduleForm.querySelector('.btn-primary');
    
    // BASE_URL and CURRENT_MENTOR_ID are globally available from the PHP file.

    // --- Fetch & Display Mentor's Exams ---
    const fetchMentorExams = async () => {
        try {
            const response = await fetch(BASE_URL + 'api/mentor/get_my_exams.php');
            const result = await response.json();
            
            mentorExamsTableBody.innerHTML = ''; 
            mentorScheduledExamsTableBody.innerHTML = '';

            if (result.status === 'success') {
                // Display created exams
                if (result.data.created_exams.length > 0) {
                    result.data.created_exams.forEach(exam => {
                        const examType = exam.is_omr_exam == 1 ? '<span class="role-badge role-mentor">OMR</span>' : '<span class="role-badge role-student">Online</span>';
                        
                        // NEW: Create both Manage Questions and Set OMR Answer Key buttons for OMR exams
                        let manageActionHtml = '';
                        if (exam.is_omr_exam == 1) {
                            manageActionHtml = `
                                <a href="${BASE_URL}admin/import.php?exam_id=${exam.id}" class="btn-action btn-import" title="Manage Questions"><i class="fa-solid fa-tasks"></i></a>
                                <a href="${BASE_URL}admin/omr_answer_key.php?exam_id=${exam.id}" class="btn-action" title="Set OMR Answer Key"><i class="fa-solid fa-key"></i></a>
                            `;
                        } else {
                            manageActionHtml = `<a href="${BASE_URL}admin/import.php?exam_id=${exam.id}" class="btn-action btn-import" title="Manage Questions"><i class="fa-solid fa-tasks"></i></a>`;
                        }

                        const row = `
                            <tr>
                                <td>${exam.title}</td>
                                <td>${examType}</td>
                                <td>${exam.question_count}</td>
                                <td>${new Date(exam.created_at).toLocaleDateString()}</td>
                                <td class="actions">
                                    ${manageActionHtml}
                                    <button class="btn-action btn-schedule" data-id="${exam.id}" data-title="${exam.title}" title="Schedule Exam"><i class="fa-solid fa-calendar-alt"></i></button>
                                    <button class="btn-action btn-edit" data-id="${exam.id}" title="Edit Exam Details"><i class="fa-solid fa-pencil-alt"></i></button>
                                    <button class="btn-action btn-delete" data-id="${exam.id}" data-title="${exam.title}" title="Delete Exam"><i class="fa-solid fa-trash"></i></button>
                                </td>
                            </tr>
                        `;
                        mentorExamsTableBody.innerHTML += row;
                    });
                } else {
                    mentorExamsTableBody.innerHTML = '<tr><td colspan="5">No exams created by you.</td></tr>';
                }

                // Display scheduled exams (remains the same as previous updates)
                if (result.data.scheduled_exams.length > 0) {
                    result.data.scheduled_exams.forEach(schedule => {
                        let statusClass = '';
                        let timerDisplay = '';
                        
                        const examStartDateTimeLocal = new Date(schedule.scheduled_start_datetime_local); 
                        const examEndDateTimeLocal = new Date(schedule.scheduled_end_datetime_local);
                        const nowLocal = new Date();

                        if (nowLocal >= examStartDateTimeLocal && nowLocal < examEndDateTimeLocal) {
                            schedule.status = 'Running';
                        } else if (nowLocal < examStartDateTimeLocal) {
                            schedule.status = 'Upcoming';
                        } else {
                            schedule.status = 'Ended';
                        }


                        if(schedule.status === 'Running') {
                            statusClass = 'status-running';
                            timerDisplay = `<span class="status-badge ${statusClass}">Running</span>`;
                        } else if(schedule.status === 'Ended') {
                            statusClass = 'status-ended';
                            timerDisplay = `<span class="status-badge ${statusClass}">Ended</span>`;
                        } else { // Upcoming
                            statusClass = 'status-upcoming';
                            const diff = examStartDateTimeLocal.getTime() - nowLocal.getTime();
                            if (diff > 0) {
                                timerDisplay = `<span class="countdown-timer" data-starttime="${examStartDateTimeLocal.toISOString()}"></span>`;
                            } else {
                                timerDisplay = `<span class="status-badge ${statusClass}">Starting Soon</span>`;
                            }
                        }

                        const row = `
                            <tr>
                                <td>${schedule.exam_title}</td>
                                <td>${new Date(schedule.scheduled_start_datetime_local).toLocaleDateString()}</td>
                                <td>${new Date(schedule.scheduled_start_datetime_local).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})} - ${new Date(schedule.scheduled_end_datetime_local).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</td>
                                <td>${schedule.duration_minutes} mins</td>
                                <td>${timerDisplay}</td>
                                <td class="actions">
                                    <a href="${BASE_URL}mentor/leaderboard.php?schedule_id=${schedule.schedule_id}" class="btn-action" title="View Leaderboard"><i class="fa-solid fa-list-ol"></i></a>
                                </td>
                            </tr>
                        `;
                        mentorScheduledExamsTableBody.innerHTML += row;
                    });
                    startCountdownTimers();
                } else {
                    mentorScheduledExamsTableBody.innerHTML = '<tr><td colspan="6">No exams scheduled by you.</td></tr>';
                }

            } else {
                console.error('API Error in fetchMentorExams:', result.message || 'Unknown error');
                mentorExamsTableBody.innerHTML = '<tr><td colspan="5" class="loader">Error loading data.</td></tr>';
                mentorScheduledExamsTableBody.innerHTML = '<tr><td colspan="6" class="loader">Error loading data.</td></tr>';
            }
        } catch (error) {
            console.error('Network or Parse Error in fetchMentorExams:', error);
            mentorExamsTableBody.innerHTML = '<tr><td colspan="5" class="loader">Error loading data.</td></tr>';
            mentorScheduledExamsTableBody.innerHTML = '<tr><td colspan="6" class="loader">Error loading data.</td></tr>';
        }
    };

    // --- Countdown Timer Logic ---
    const startCountdownTimers = () => {
        const timers = document.querySelectorAll('#mentor-scheduled-exams-table-body .countdown-timer');
        timers.forEach(timer => {
            const startTime = new Date(timer.getAttribute('data-starttime'));
            
            if (timer.dataset.intervalId) {
                clearInterval(parseInt(timer.dataset.intervalId));
            }

            const interval = setInterval(() => {
                const now = new Date();
                const diff = startTime.getTime() - now.getTime();

                if (diff <= 0) {
                    clearInterval(interval);
                    timer.innerHTML = 'Starting...';
                    setTimeout(fetchMentorExams, 2000);
                    return;
                }

                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                timer.innerHTML = `${days}d ${String(hours).padStart(2, '0')}h ${String(minutes).padStart(2, '0')}m ${String(seconds).padStart(2, '0')}s`;
            }, 1000);
            timer.dataset.intervalId = interval.toString();
        });
    };


    // Function to fetch mentors and populate the dropdown
    const fetchMentors = async () => {
        try {
            const response = await fetch(BASE_URL + 'api/users/get_mentors.php');
            const result = await response.json();
            
            mentorSelect.innerHTML = '<option value="">Select a Mentor</option>'; 
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(mentor => {
                    mentorSelect.innerHTML += `<option value="${mentor.id}">${mentor.name}</option>`;
                });
                if (CURRENT_MENTOR_ID) {
                    mentorSelect.value = CURRENT_MENTOR_ID;
                }
            }
        } catch (error) {
            console.error('Error fetching mentors:', error);
            mentorSelect.innerHTML = '<option value="">Error loading mentors</option>';
        }
    };

    // --- Create Exam Modal Handling (from nav link) ---
    createExamNavLink.addEventListener('click', (e) => {
        e.preventDefault();
        examForm.reset();
        examModalTitle.textContent = 'Create New Exam';
        examIdInput.value = ''; 
        titleInput.value = '';
        descriptionInput.value = '';
        shuffleQuestionsCheckbox.checked = false;
        shuffleOptionsCheckbox.checked = false;
        isOmrExamCheckbox.checked = false;
        examModal.style.display = 'block';
    });
    closeExamModalBtn.onclick = () => { examModal.style.display = 'none'; };

    // Handle Form Submission (Create or Update Exam)
    examForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(examForm);
        const data = Object.fromEntries(formData.entries());

        data.shuffle_questions = formData.has('shuffle_questions') ? 1 : 0;
        data.shuffle_options = formData.has('shuffle_options') ? 1 : 0;
        data.is_omr_exam = formData.has('is_omr_exam') ? 1 : 0;

        const isEditing = !!data.examId;

        try {
            const apiEndpoint = isEditing ? BASE_URL + 'api/exams/update_exam.php' : BASE_URL + 'api/exams/create.php';
            const response = await fetch(apiEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            alert(result.message);

            if (response.ok) {
                examModal.style.display = 'none';
                fetchMentorExams();
            }
        } catch (error) {
            console.error('Error saving exam:', error); 
            alert('An error occurred. Please try again.');
        }
    });

    // --- Action Button Handling (Created Exams) ---
    mentorExamsTableBody.addEventListener('click', async (e) => {
        const scheduleBtn = e.target.closest('.btn-schedule');
        const editBtn = e.target.closest('.btn-edit'); 
        const deleteBtn = e.target.closest('.btn-delete'); 
        const manageOmrKeyBtn = e.target.closest('.btn-action[title="Set OMR Answer Key"]'); // Specific for OMR Answer Key
        const manageQuestionsBtn = e.target.closest('.btn-action[title="Manage Questions"]'); // Specific for Online/OMR Questions

        if (scheduleBtn) {
            const examId = scheduleBtn.getAttribute('data-id');
            const examTitle = scheduleBtn.getAttribute('data-title');
            scheduleForm.reset();
            scheduleExamIdInput.value = examId;
            scheduledExamTitleSpan.textContent = examTitle;
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            document.getElementById('scheduled_date').value = `${yyyy}-${mm}-${dd}`;
            document.getElementById('start_time').value = '09:00';
            document.getElementById('end_time').value = '10:00';
            document.getElementById('duration_minutes').value = '60';
            await fetchMentors();
            scheduleModal.style.display = 'block';
        }

        if (editBtn) {
            const examId = editBtn.getAttribute('data-id');
            try {
                const response = await fetch(`${BASE_URL}api/exams/get_exam_details.php?exam_id=${examId}`);
                const result = await response.json();
                if (result.status === 'success' && result.data) {
                    const exam = result.data;
                    examModalTitle.textContent = 'Edit Exam';
                    examIdInput.value = exam.id;
                    titleInput.value = exam.title;
                    descriptionInput.value = exam.description;
                    shuffleQuestionsCheckbox.checked = exam.shuffle_questions == 1;
                    shuffleOptionsCheckbox.checked = exam.shuffle_options == 1;
                    isOmrExamCheckbox.checked = exam.is_omr_exam == 1;
                    examModal.style.display = 'block';
                } else {
                    alert(result.message || 'Error loading exam details.');
                }
            } catch (error) {
                console.error('Error fetching exam details for edit:', error);
                alert('An error occurred while loading exam details.');
            }
        }

        if (deleteBtn) {
            const examId = deleteBtn.getAttribute('data-id');
            const examTitle = deleteBtn.getAttribute('data-title'); 
            if (confirm(`Are you sure you want to delete the exam "${examTitle}"?`)) {
                try {
                    const response = await fetch(BASE_URL + 'api/exams/delete_exam.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ exam_id: examId })
                    });
                    const result = await response.json();
                    alert(result.message);
                    if (response.ok) {
                        fetchMentorExams();
                    }
                } catch (error) {
                    console.error('Error deleting exam:', error);
                    alert('An error occurred while deleting the exam.');
                }
            }
        }

        // --- NEW: Handle OMR Answer Key Button ---
        if (manageOmrKeyBtn) {
            const examId = manageOmrKeyBtn.getAttribute('data-id');
            // Redirect to the OMR Answer Key management page
            window.location.href = `${BASE_URL}admin/omr_answer_key.php?exam_id=${examId}`;
        }

        // --- NEW: Handle Manage Questions Button (for both Online and OMR) ---
        // This is the existing behavior for btn-import which is now generalized
        if (manageQuestionsBtn) {
            const examId = manageQuestionsBtn.getAttribute('data-id');
            window.location.href = `${BASE_URL}admin/import.php?exam_id=${examId}`;
        }
    });
    
    closeScheduleModalBtn.onclick = () => { scheduleModal.style.display = 'none'; };

    // Handle Form Submission (Create Schedule)
    scheduleForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(scheduleForm);
        const data = Object.fromEntries(formData.entries());
        createScheduleBtn.disabled = true;
        createScheduleBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Scheduling...';

        try {
            const response = await fetch(BASE_URL + 'api/exams/schedule_exam.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            alert(result.message);
            if (response.ok) {
                scheduleModal.style.display = 'none';
                fetchMentorExams();
            }
        } catch (error) {
            console.error('Error scheduling exam:', error);
            alert('An error occurred during scheduling.');
        } finally {
            createScheduleBtn.disabled = false;
            createScheduleBtn.innerHTML = 'Create Schedule';
        }
    });

    // Close modals when clicking outside
    window.onclick = (event) => {
        if (event.target == examModal) examModal.style.display = 'none';
        if (event.target == scheduleModal) scheduleModal.style.display = 'none';
    };

    // Initial load of mentor's exams and schedules
    fetchMentorExams();

    // Auto-refresh scheduled exams list every 30 seconds
    setInterval(fetchMentorExams, 30000);

    // Clean up intervals on page unload
    window.addEventListener('beforeunload', () => {
        document.querySelectorAll('.countdown-timer').forEach(timer => {
            if (timer.dataset.intervalId) {
                clearInterval(parseInt(timer.dataset.intervalId));
            }
        });
    });
});