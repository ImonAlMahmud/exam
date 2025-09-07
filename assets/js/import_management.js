document.addEventListener('DOMContentLoaded', function() {
    const importForm = document.getElementById('importForm');
    const importBtn = document.getElementById('importBtn');
    const importResult = document.getElementById('importResult');
    const questionsTableBody = document.getElementById('questions-table-body');

    // Edit Question Modal elements
    const editQuestionModal = document.getElementById('editQuestionModal');
    const closeEditQuestionModalBtn = document.getElementById('closeEditQuestionModal');
    const editQuestionForm = document.getElementById('editQuestionForm');
    const editQuestionModalTitle = document.getElementById('editQuestionModalTitle');
    const editQuestionIdInput = document.getElementById('editQuestionId');
    const editQuestionTextarea = document.getElementById('edit_question_text');
    const editOption1Input = document.getElementById('edit_option_1');
    const editOption2Input = document.getElementById('edit_option_2');
    const editOption3Input = document.getElementById('edit_option_3');
    const editOption4Input = document.getElementById('edit_option_4');
    const editCorrectAnswerInput = document.getElementById('edit_correct_answer');

    // BASE_URL, EXAM_ID, and CURRENT_USER_ROLE are globally available from the PHP file.
    
    // Function to fetch and display existing questions
    const fetchExistingQuestions = async () => {
        questionsTableBody.innerHTML = '<tr><td colspan="2" class="loader">Loading questions...</td></tr>';
        try {
            const response = await fetch(`${BASE_URL}api/exams/get_questions.php?exam_id=${EXAM_ID}`);
            const result = await response.json();
            
            questionsTableBody.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(q => {
                    const row = `
                        <tr>
                            <td>${q.question_text}</td>
                            <td class="actions">
                                <button class="btn-action btn-edit" data-id="${q.id}" data-text="${q.question_text}" title="Edit Question"><i class="fa-solid fa-pencil-alt"></i></button>
                                <button class="btn-action btn-delete" data-id="${q.id}" data-text="${q.question_text}" title="Delete Question"><i class="fa-solid fa-trash"></i></button>
                            </td>
                        </tr>
                    `;
                    questionsTableBody.innerHTML += row;
                });
            } else if (result.status === 'error') {
                 questionsTableBody.innerHTML = `<tr><td colspan="2" class="message error">${result.message}</td></tr>`;
            } 
            else {
                questionsTableBody.innerHTML = '<tr><td colspan="2">No questions added yet.</td></tr>';
            }
        } catch (error) {
            console.error('Error fetching existing questions:', error);
            questionsTableBody.innerHTML = `<tr><td colspan="2" class="message error">Error loading questions. Network error or API problem.</td></tr>`;
        }
    };


    // Handle Import Form Submission
    importForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        importBtn.disabled = true;
        importBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Importing...';
        importResult.innerHTML = '';

        const formData = new FormData(this); // 'this' refers to the form element
        
        try {
            const response = await fetch(`${BASE_URL}api/exams/import_questions.php`, {
                method: 'POST',
                body: formData // FormData is sent directly, headers are set automatically
            });

            const result = await response.json();

            let resultClass = response.ok ? 'success' : 'error';
            let message = `<div class="message ${resultClass}">${result.message}</div>`;
            
            if (result.details && result.details.errors.length > 0) {
                message += `<p><strong>Skipped Rows:</strong></p><ul>`;
                result.details.errors.forEach(err => {
                    message += `<li>${err}</li>`;
                });
                message += `</ul>`;
            }
            importResult.innerHTML = message;

            if (response.ok) {
                fetchExistingQuestions(); // Refresh the list of questions after import
            }

        } catch (error) {
            console.error('Error during import:', error);
            importResult.innerHTML = `<div class="message error">A network error occurred during import. Please try again.</div>`;
        } finally {
            importBtn.disabled = false;
            importBtn.innerHTML = '<i class="fa-solid fa-upload"></i> Upload & Import';
        }
    });

    // --- Handle Action Buttons (Edit & Delete Question) ---
    questionsTableBody.addEventListener('click', async (e) => {
        const deleteBtn = e.target.closest('.btn-delete');
        const editBtn = e.target.closest('.btn-edit');

        if (deleteBtn) {
            const questionId = deleteBtn.getAttribute('data-id');
            const questionText = deleteBtn.getAttribute('data-text');

            if (confirm(`Are you sure you want to delete the question: "${questionText}"? This action cannot be undone.`)) {
                try {
                    const response = await fetch(`${BASE_URL}api/exams/delete_question.php`, {
                        method: 'POST', // Using POST for delete to send a body
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ question_id: questionId })
                    });
                    const result = await response.json();
                    alert(result.message);

                    if (response.ok) {
                        fetchExistingQuestions(); // Refresh the list after deletion
                    }
                } catch (error) {
                    console.error('Error deleting question:', error);
                    alert('An error occurred while deleting the question. Please try again.');
                }
            }
        }

        if (editBtn) {
            const questionId = editBtn.getAttribute('data-id');
            // Fetch question details and populate modal
            try {
                const response = await fetch(`${BASE_URL}api/exams/get_question_details.php?question_id=${questionId}`);
                const result = await response.json();

                if (result.status === 'success' && result.data) {
                    const question = result.data;
                    editQuestionIdInput.value = question.id;
                    editQuestionTextarea.value = question.question_text;
                    editOption1Input.value = question.option_1;
                    editOption2Input.value = question.option_2;
                    editOption3Input.value = question.option_3;
                    editOption4Input.value = question.option_4;
                    editCorrectAnswerInput.value = question.correct_answer;
                    
                    editQuestionModalTitle.textContent = `Edit Question: Q${question.id}`;
                    editQuestionModal.style.display = 'block';
                } else {
                    alert(result.message || 'Error loading question details.');
                }
            } catch (error) {
                console.error('Error fetching question details:', error);
                alert('An error occurred while loading question details.');
            }
        }
    });

    // --- Handle Edit Question Form Submission ---
    editQuestionForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(editQuestionForm);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch(`${BASE_URL}api/exams/update_question.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            alert(result.message);

            if (response.ok) {
                editQuestionModal.style.display = 'none';
                fetchExistingQuestions(); // Refresh the list after update
            }
        } catch (error) {
            console.error('Error updating question:', error);
            alert('An error occurred while updating the question.');
        }
    });

    // Close Edit Question Modal
    closeEditQuestionModalBtn.onclick = () => {
        editQuestionModal.style.display = 'none';
    };
    window.onclick = (event) => { // Existing global modal close, add editQuestionModal
        // ... (other modal close logic) ...
        if (event.target == editQuestionModal) editQuestionModal.style.display = 'none';
    };


    // Initial load of existing questions
    fetchExistingQuestions();
});