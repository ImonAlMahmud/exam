document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.getElementById('exams-table-body');
    
    // Create/Edit Exam Modal elements
    const examModal = document.getElementById('examModal');
    const examForm = document.getElementById('examForm');
    const createExamBtn = document.getElementById('createExamBtn');
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

    // View Schedules Modal elements
    const viewSchedulesModal = document.getElementById('viewSchedulesModal');
    const closeViewSchedulesModalBtn = document.getElementById('closeViewSchedulesModalBtn');
    const viewSchedulesExamTitle = document.getElementById('viewSchedulesExamTitle');
    const schedulesForExamBody = document.getElementById('schedules-for-exam-body');
    const scheduleNewFromViewBtn = document.getElementById('scheduleNewFromView');
    
    // BASE_URL is globally available from the PHP file.

    // Function to fetch and display exams
    const fetchExams = async () => {
        try {
            const response = await fetch(BASE_URL + 'api/exams/read.php');
            const result = await response.json();
            tableBody.innerHTML = ''; 

            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(exam => {
                    const examType = exam.is_omr_exam == 1 ? '<span class="role-badge role-mentor">OMR</span>' : '<span class="role-badge role-student">Online</span>';
                    
                    // Create a specific action button for OMR answer key
                    const omrAction = exam.is_omr_exam == 1 
                        ? `<a href="${BASE_URL}admin/import.php?exam_id=${exam.id}" class="btn-action btn-import" title="Manage Questions"><i class="fa-solid fa-tasks"></i></a>
                           <a href="${BASE_URL}admin/omr_answer_key.php?exam_id=${exam.id}" class="btn-action" title="Set OMR Answer Key"><i class="fa-solid fa-key"></i></a>`
                        : `<a href="${BASE_URL}admin/import.php?exam_id=${exam.id}" class="btn-action btn-import" title="Manage Questions"><i class="fa-solid fa-tasks"></i></a>`;

                    const row = `
                        <tr>
                            <td>${exam.title}</td>
                            <td>${examType}</td>
                            <td>${exam.question_count}</td>
                            <td>${exam.created_by}</td>
                            <td>${new Date(exam.created_at).toLocaleDateString()}</td>
                            <td class="actions">
                                ${omrAction}
                                <button class="btn-action btn-view-schedules" data-id="${exam.id}" data-title="${exam.title}" title="View Schedules"><i class="fa-solid fa-calendar-days"></i></button>
                                <button class="btn-action btn-schedule" data-id="${exam.id}" data-title="${exam.title}" title="Schedule Exam"><i class="fa-solid fa-calendar-alt"></i></button>
                                <button class="btn-action btn-edit" data-id="${exam.id}" title="Edit Exam Details"><i class="fa-solid fa-pencil-alt"></i></button>
                                <button class="btn-action btn-delete" data-id="${exam.id}" data-title="${exam.title}" title="Delete Exam"><i class="fa-solid fa-trash"></i></button>
                            </td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="6">No exams found. Create one!</td></tr>';
            }
        } catch (error) {
            console.error('Error fetching exams:', error);
            tableBody.innerHTML = '<tr><td colspan="6">Error loading data.</td></tr>';
        }
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
            }
        } catch (error) {
            console.error('Error fetching mentors:', error);
            mentorSelect.innerHTML = '<option value="">Error loading mentors</option>';
        }
    };

    // Function to fetch and display schedules for a specific exam
    const fetchSchedulesForExam = async (examId, examTitle) => {
        viewSchedulesExamTitle.textContent = examTitle;
        schedulesForExamBody.innerHTML = '<tr><td colspan="6" class="loader">Loading schedules...</td></tr>';
        viewSchedulesModal.style.display = 'block';

        try {
            const response = await fetch(`${BASE_URL}api/exams/get_schedules.php?exam_id=${examId}`);
            const result = await response.json();
            
            schedulesForExamBody.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(schedule => {
                    let statusClass = '';
                    if(schedule.status === 'Running') statusClass = 'status-running';
                    else if(schedule.status === 'Ended') statusClass = 'status-ended';
                    else statusClass = 'status-upcoming';

                    const row = `
                        <tr>
                            <td>${new Date(schedule.scheduled_date).toLocaleDateString()}</td>
                            <td>${schedule.start_time.substring(0,5)} - ${schedule.end_time.substring(0,5)}</td>
                            <td>${schedule.duration_minutes} mins</td>
                            <td>${schedule.mentor_name}</td>
                            <td><span class="status-badge ${statusClass}">${schedule.status}</span></td>
                            <td class="actions">
                                <a href="${BASE_URL}mentor/leaderboard.php?schedule_id=${schedule.schedule_id}" class="btn-action" title="View Leaderboard"><i class="fa-solid fa-list-ol"></i></a>
                                <!-- Edit/Delete Schedule can be added later -->
                            </td>
                        </tr>
                    `;
                    schedulesForExamBody.innerHTML += row;
                });
            } else {
                schedulesForExamBody.innerHTML = '<tr><td colspan="6">No schedules found for this exam.</td></tr>';
            }
        } catch (error) {
            console.error('Error fetching schedules:', error);
            schedulesForExamBody.innerHTML = '<tr><td colspan="6">Error loading schedules.</td></tr>';
        }

        // Update the "Schedule New" button in the modal
        scheduleNewFromViewBtn.onclick = () => {
            viewSchedulesModal.style.display = 'none'; // Close this modal
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
            fetchMentors(); // Load mentors
            scheduleModal.style.display = 'block'; // Open schedule modal
        };
    };


    // --- Create/Edit Exam Modal Handling ---
    createExamBtn.onclick = () => {
        examForm.reset();
        examModalTitle.textContent = 'Create New Exam';
        examIdInput.value = ''; // Clear hidden ID for new exam
        titleInput.value = '';
        descriptionInput.value = '';
        shuffleQuestionsCheckbox.checked = false;
        shuffleOptionsCheckbox.checked = false;
        isOmrExamCheckbox.checked = false; // Uncheck OMR checkbox for new exam
        examModal.style.display = 'block';
    };
    closeExamModalBtn.onclick = () => { examModal.style.display = 'none'; };

    // Handle Form Submission (Create or Update Exam)
    examForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(examForm);
        const data = Object.fromEntries(formData.entries());

        data.shuffle_questions = formData.has('shuffle_questions') ? 1 : 0;
        data.shuffle_options = formData.has('shuffle_options') ? 1 : 0;
        data.is_omr_exam = formData.has('is_omr_exam') ? 1 : 0; // New: Handle OMR exam flag

        const isEditing = !!data.examId; // Check if examId exists for update

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
                fetchExams(); // Refresh list after creating or updating exam
            }
        } catch (error) {
            console.error('Error saving exam:', error); 
            alert('An error occurred. Please try again.');
        }
    });

    // --- Action Button Handling (View Schedules, Schedule Exam, Edit, Delete) ---
    tableBody.addEventListener('click', async (e) => {
        const scheduleBtn = e.target.closest('.btn-schedule');
        const viewSchedulesBtn = e.target.closest('.btn-view-schedules');
        const editBtn = e.target.closest('.btn-edit'); 
        const deleteBtn = e.target.closest('.btn-delete'); 

        if (scheduleBtn) {
            const examId = scheduleBtn.getAttribute('data-id');
            const examTitle = scheduleBtn.getAttribute('data-title');

            scheduleForm.reset(); // Reset form for new schedule
            scheduleExamIdInput.value = examId;
            scheduledExamTitleSpan.textContent = examTitle;
            
            // Set default date to today
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            document.getElementById('scheduled_date').value = `${yyyy}-${mm}-${dd}`;

            // Set default times and duration
            document.getElementById('start_time').value = '09:00';
            document.getElementById('end_time').value = '10:00';
            document.getElementById('duration_minutes').value = '60';

            await fetchMentors(); // Load mentors before showing the modal
            scheduleModal.style.display = 'block';
        }

        if (viewSchedulesBtn) {
            const examId = viewSchedulesBtn.getAttribute('data-id');
            const examTitle = viewSchedulesBtn.getAttribute('data-title');
            fetchSchedulesForExam(examId, examTitle);
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
                    isOmrExamCheckbox.checked = exam.is_omr_exam == 1; // New: Set OMR checkbox
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

            if (confirm(`Are you sure you want to delete the exam "${examTitle}"? All associated questions, schedules, and submissions will also be deleted. This action cannot be undone.`)) {
                try {
                    const response = await fetch(BASE_URL + 'api/exams/delete_exam.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ exam_id: examId })
                    });
                    const result = await response.json();
                    alert(result.message);

                    if (response.ok) {
                        fetchExams(); // Refresh the list after deletion
                    }
                } catch (error) {
                    console.error('Error deleting exam:', error);
                    alert('An error occurred while deleting the exam. Please try again.');
                }
            }
        }
    });
    
    closeScheduleModalBtn.onclick = () => { scheduleModal.style.display = 'none'; };
    closeViewSchedulesModalBtn.onclick = () => { viewSchedulesModal.style.display = 'none'; };


    // Handle Form Submission (Create Schedule)
    scheduleForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(scheduleForm);
        const data = Object.fromEntries(formData.entries());

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
                fetchExams(); // Refresh exam list (e.g., if a new schedule means updated info on exam card)
            }
        } catch (error) {
            console.error('Error scheduling exam:', error);
            alert('An error occurred during scheduling. Please try again.');
        }
    });

    // Close modals when clicking outside
    window.onclick = (event) => {
        if (event.target == examModal) examModal.style.display = 'none';
        if (event.target == scheduleModal) scheduleModal.style.display = 'none';
        if (event.target == viewSchedulesModal) viewSchedulesModal.style.display = 'none';
    };

    // Initial load of exams
    fetchExams();
});