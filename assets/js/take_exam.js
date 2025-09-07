document.addEventListener('DOMContentLoaded', () => {
    // DOM Elements
    const questionContainer = document.getElementById('question-container');
    const questionNavigation = document.getElementById('question-navigation');
    const submitExamBtn = document.getElementById('submitExamBtn');
    const timerDisplay = document.querySelector('#countdown-timer span');

    // State Variables
    let questions = [];
    let studentAnswers = []; // Format: [{question_id, selected_option_new_index, shuffled_options_map}]
    let currentPage = 0;
    let totalPages = 0;
    let examEndTime;
    let timerInterval;
    let isSubmitting = false;

    // --- 1. Fetch Questions from API ---
    const loadQuestions = async () => {
        try {
            const response = await fetch(`${baseUrl}api/student/get_exam_questions.php?schedule_id=${scheduleId}`);
            const result = await response.json();

            if (result.status === 'success' && result.data.length > 0) {
                questions = result.data;
                studentAnswers = questions.map(q => ({ 
                    question_id: q.id, 
                    selected_option_new_index: null, // Stores selected option's NEW index (1-4)
                    shuffled_options_map: q.shuffled_options_map || {1:1, 2:2, 3:3, 4:4}
                }));
                totalPages = Math.ceil(questions.length / questionsPerPage);
                renderCurrentPageQuestions();
                renderPagination();
                startExamTimer();
            } else {
                questionContainer.innerHTML = `<p>${result.message || 'No questions found for this exam.'}</p>`;
                submitExamBtn.disabled = true;
            }
        } catch (error) {
            console.error('Error loading questions:', error);
            questionContainer.innerHTML = '<p>Error loading questions. Please try refreshing.</p>';
            submitExamBtn.disabled = true;
        }
    };

    // --- 2. Render questions for the current page ---
    const renderCurrentPageQuestions = () => {
        const startIndex = currentPage * questionsPerPage;
        const endIndex = Math.min(startIndex + questionsPerPage, questions.length);
        const questionsToRender = questions.slice(startIndex, endIndex);

        let questionsHtml = '';
        questionsToRender.forEach((question, indexInPage) => {
            const globalQuestionIndex = startIndex + indexInPage;
            let optionsHtml = '<ul class="options-list">';
            
            for (let i = 1; i <= 4; i++) {
                const optionKey = `option_${i}`;
                if (question[optionKey]) {
                    const isSelected = studentAnswers[globalQuestionIndex].selected_option_new_index === i;
                    optionsHtml += `<li data-option="${i}" data-global-index="${globalQuestionIndex}" class="${isSelected ? 'selected' : ''}">${question[optionKey]}</li>`;
                }
            }
            optionsHtml += '</ul>';

            questionsHtml += `
                <div class="single-question" data-global-index="${globalQuestionIndex}">
                    <h2>Q.${globalQuestionIndex + 1}: ${question.question_text}</h2>
                    ${optionsHtml}
                </div>
            `;
        });
        questionContainer.innerHTML = questionsHtml;

        document.querySelectorAll('.options-list li').forEach(option => {
            option.addEventListener('click', handleOptionSelect);
        });
    };

    // --- 3. Render Pagination Buttons ---
    const renderPagination = () => {
        let paginationHtml = '';
        if (totalPages > 1) {
            if (currentPage > 0) {
                paginationHtml += `<button class="btn-nav btn-prev"><i class="fa-solid fa-chevron-left"></i> Prev</button>`;
            }
            for (let i = 0; i < totalPages; i++) {
                const isPageAnswered = questions.slice(i * questionsPerPage, (i + 1) * questionsPerPage)
                                                .every((_, qIndex) => {
                                                    const globalIndex = i * questionsPerPage + qIndex;
                                                    return studentAnswers[globalIndex].selected_option_new_index !== null;
                                                });
                paginationHtml += `<button class="btn-nav ${i === currentPage ? 'active' : ''} ${isPageAnswered ? 'answered-page' : ''}" data-page="${i}">${i + 1}</button>`;
            }
            if (currentPage < totalPages - 1) {
                paginationHtml += `<button class="btn-nav btn-next">Next <i class="fa-solid fa-chevron-right"></i></button>`;
            }
        }
        questionNavigation.innerHTML = paginationHtml;

        document.querySelectorAll('.btn-nav[data-page]').forEach(button => {
            button.addEventListener('click', (e) => {
                currentPage = parseInt(e.target.getAttribute('data-page'));
                renderCurrentPageQuestions();
                renderPagination();
            });
        });
        const prevBtn = document.querySelector('.btn-prev');
        if (prevBtn) prevBtn.addEventListener('click', () => {
            if (currentPage > 0) { currentPage--; renderCurrentPageQuestions(); renderPagination(); }
        });
        const nextBtn = document.querySelector('.btn-next');
        if (nextBtn) nextBtn.addEventListener('click', () => {
            if (currentPage < totalPages - 1) { currentPage++; renderCurrentPageQuestions(); renderPagination(); }
        });
    };

    // --- 4. Handle Option Selection ---
    const handleOptionSelect = (e) => {
        const selectedOptionNewIndex = parseInt(e.target.getAttribute('data-option'));
        const globalQuestionIndex = parseInt(e.target.closest('.single-question').getAttribute('data-global-index'));
        
        studentAnswers[globalQuestionIndex].selected_option_new_index = selectedOptionNewIndex;
        
        e.target.closest('.options-list').querySelectorAll('li').forEach(li => {
            li.classList.remove('selected');
        });
        e.target.classList.add('selected');
        renderPagination();
    };

    // --- 5. Submit Exam ---
    const submitExam = async (isAutoSubmit = false) => {
        if (isSubmitting) return;
        isSubmitting = true;

        if (timerInterval) clearInterval(timerInterval);

        submitExamBtn.disabled = true;
        submitExamBtn.textContent = isAutoSubmit ? 'Auto-Submitting...' : 'Submitting...';

        // Prepare answers to send to server. Convert new_index to original_index using the map.
        const answersToSubmit = studentAnswers.map(ans => {
            if (ans.selected_option_new_index === null) {
                return { question_id: ans.question_id, selected_option: null };
            }
            const originalSelectedOptionIndex = ans.shuffled_options_map[ans.selected_option_new_index];
            return {
                question_id: ans.question_id,
                selected_option: originalSelectedOptionIndex
            };
        });

        try {
            const response = await fetch(`${baseUrl}api/student/submit_exam.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    schedule_id: scheduleId,
                    answers: answersToSubmit
                })
            });
            const result = await response.json();
            
            if (response.ok) {
                alert(result.message);
                await fetch(`${baseUrl}api/student/clear_exam_session.php`, { method: 'POST' });
                window.location.href = `${baseUrl}student/student-dashboard.php`;
            } else {
                throw new Error(result.message || 'Server responded with an error.');
            }
        } catch (error) {
            console.error('Error submitting exam:', error);
            if (isAutoSubmit) {
                alert(`Auto-submission failed: ${error.message}. Your attempt may not be recorded. Redirecting to dashboard.`);
            } else {
                alert(`Error submitting exam: ${error.message}. Please try to submit again.`);
            }
            isSubmitting = false;
            submitExamBtn.disabled = false;
            submitExamBtn.textContent = 'Submit Exam';
            if (examEndTime && examEndTime.getTime() > Date.now()) startExamTimer();
        }
    };
    
    submitExamBtn.addEventListener('click', () => {
        if (confirm('Are you sure you want to submit the exam?')) {
            submitExam();
        }
    });

    // --- 6. Exam Countdown Timer ---
    const startExamTimer = () => {
        examEndTime = new Date(effectiveExamEndTimeMillis); 
        
        timerInterval = setInterval(() => {
            const now = new Date();
            const diff = examEndTime.getTime() - now.getTime();

            if (diff <= 0) {
                clearInterval(timerInterval);
                timerDisplay.textContent = '00:00:00';
                alert('Time is up! Your exam will be submitted automatically.');
                submitExam(true);
                return;
            }

            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            timerDisplay.textContent = 
                `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }, 1000);
    };

    // --- 7. "No Exit" Logic (Auto-submission on page close/refresh/navigate) ---
    window.addEventListener('beforeunload', (e) => {
        if (!isSubmitting && examEndTime && examEndTime.getTime() > Date.now()) { 
            e.preventDefault();
            e.returnValue = 'Are you sure you want to leave? Your exam will be submitted.';
            submitExam(true);
        }
    });
    
    // --- Initializer ---
    loadQuestions();
});