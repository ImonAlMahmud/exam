document.addEventListener('DOMContentLoaded', function() {
    const upcomingExamsList = document.getElementById('upcoming-exams-list');
    const totalExamsTakenDisplay = document.getElementById('totalExamsTaken');
    const averageScoreDisplay = document.getElementById('averageScore');
    const examHistoryBody = document.getElementById('exam-history-body');

    // OMR Modal elements
    const omrModal = document.getElementById('omrSubmissionModal');
    const omrForm = document.getElementById('omrSubmissionForm'); // The hidden form for submission
    const closeOmrModalBtn = document.getElementById('closeOmrModalBtn');
    const omrModalTitle = document.getElementById('omrModalTitle');
    const omrScheduleIdInput = document.getElementById('omrScheduleId');
    const omrAnswerKeyIdInput = document.getElementById('omrAnswerKeyId');
    const omrExamTitleSpan = document.getElementById('omrExamTitle');
    const omrSubmissionMessage = document.getElementById('omrSubmissionMessage');

    // Camera/Image elements
    const omrVideoStream = document.getElementById('omrVideoStream');
    const omrCanvas = document.getElementById('omrCanvas');
    const omrImagePreview = document.getElementById('omrImagePreview');
    const omrSheetDataInput = document.getElementById('omrSheetData'); // Hidden input for image data

    // Action buttons for OMR modal
    const startCameraButton = document.getElementById('startCameraButton');
    const takePictureButton = document.getElementById('takePictureButton');
    const retakePictureButton = document.getElementById('retakePictureButton');
    const uploadOcrButton = document.getElementById('uploadOcrButton');

    let cameraStream = null; // To hold the camera stream

    // BASE_URL is globally available from the PHP file.

    // --- Helper to stop camera stream ---
    const stopCamera = () => {
        if (cameraStream) {
            cameraStream.getTracks().forEach(track => track.stop());
            omrVideoStream.srcObject = null;
            cameraStream = null;
        }
    };

    // --- Open OMR Modal and initialize camera ---
    const openOmrModal = async (scheduleId, examTitle, answerKeyId) => {
        omrForm.reset();
        omrScheduleIdInput.value = scheduleId;
        omrAnswerKeyIdInput.value = answerKeyId;
        omrExamTitleSpan.textContent = examTitle;
        omrSubmissionMessage.innerHTML = '';
        
        // Hide previous elements, show camera start button
        omrVideoStream.style.display = 'none';
        omrCanvas.style.display = 'none';
        omrImagePreview.style.display = 'none';
        takePictureButton.style.display = 'none';
        retakePictureButton.style.display = 'none';
        uploadOcrButton.style.display = 'none';
        startCameraButton.style.display = 'inline-block'; // Show start camera button
        
        omrModal.style.display = 'block';
    };

    // --- Start Camera ---
    startCameraButton.addEventListener('click', async () => {
        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } }); // Prefer rear camera on mobile
            omrVideoStream.srcObject = cameraStream;
            omrVideoStream.style.display = 'block';
            startCameraButton.style.display = 'none';
            takePictureButton.style.display = 'inline-block';
            omrSubmissionMessage.innerHTML = '';
        } catch (err) {
            console.error('Error accessing camera:', err);
            omrSubmissionMessage.innerHTML = '<div class="message error">Error accessing camera. Please ensure permissions are granted.</div>';
        }
    });

    // --- Take Picture ---
    takePictureButton.addEventListener('click', () => {
        if (cameraStream) {
            const context = omrCanvas.getContext('2d');
            omrCanvas.width = omrVideoStream.videoWidth;
            omrCanvas.height = omrVideoStream.videoHeight;
            context.drawImage(omrVideoStream, 0, 0, omrCanvas.width, omrCanvas.height);
            
            const imageDataURL = omrCanvas.toDataURL('image/png'); // Get image data
            omrSheetDataInput.value = imageDataURL; // Store in hidden input

            omrImagePreview.src = imageDataURL;
            omrImagePreview.style.display = 'block';
            omrVideoStream.style.display = 'none'; // Hide video stream
            takePictureButton.style.display = 'none';
            startCameraButton.style.display = 'none'; // Ensure start button is hidden
            retakePictureButton.style.display = 'inline-block';
            uploadOcrButton.style.display = 'inline-block';
        }
    });

    // --- Retake Picture ---
    retakePictureButton.addEventListener('click', () => {
        omrImagePreview.style.display = 'none';
        omrSheetDataInput.value = ''; // Clear previous image data
        omrVideoStream.style.display = 'block'; // Show video stream again
        startCameraButton.style.display = 'none'; // Ensure start button is hidden
        takePictureButton.style.display = 'inline-block';
        retakePictureButton.style.display = 'none';
        uploadOcrButton.style.display = 'none';
    });

    // --- Upload and Evaluate OMR ---
    uploadOcrButton.addEventListener('click', async () => {
        omrSubmissionMessage.innerHTML = '<div class="message info"><i class="fa-solid fa-spinner fa-spin"></i> Submitting OMR for evaluation...</div>';
        uploadOcrButton.disabled = true;

        const formData = new FormData();
        formData.append('schedule_id', omrScheduleIdInput.value);
        formData.append('answer_key_id', omrAnswerKeyIdInput.value);
        formData.append('exam_title', omrExamTitleSpan.textContent); // Pass exam title for logging

        // Convert base64 image data from canvas to a Blob (File-like object)
        const imageDataURL = omrSheetDataInput.value;
        if (!imageDataURL) {
            omrSubmissionMessage.innerHTML = '<div class="message error">No image to upload. Please take a picture first.</div>';
            uploadOcrButton.disabled = false;
            return;
        }
        const blob = await (await fetch(imageDataURL)).blob();
        formData.append('omr_sheet', blob, 'omr_sheet.png'); // Append as a file

        try {
            const response = await fetch(BASE_URL + 'api/student/submit_omr.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            let messageClass = response.ok ? 'success' : 'error';
            omrSubmissionMessage.innerHTML = `<div class="message ${messageClass}">${result.message}</div>`;
            
            if (response.ok) {
                stopCamera(); // Stop camera on successful submission
                setTimeout(() => {
                    omrModal.style.display = 'none';
                    fetchExamStats(); // Refresh stats
                    if (typeof fetchExamHistory === 'function') fetchExamHistory(); // Also refresh history if available
                }, 2000);
            }
        } catch (error) {
            console.error('Error submitting OMR:', error);
            omrSubmissionMessage.innerHTML = '<div class="message error">An error occurred. Please try again.</div>';
        } finally {
            uploadOcrButton.disabled = false;
        }
    });


    // --- Fetch & Display Upcoming/Running Exams (existing code) ---
    const fetchUpcomingExams = async () => {
        try {
            const response = await fetch(BASE_URL + 'api/student/get_exams.php');
            const result = await response.json();

            upcomingExamsList.innerHTML = ''; // Clear loader

            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(exam => {
                    const examCard = document.createElement('div');
                    examCard.className = 'exam-card';
                    
                    const examStartDateTimeLocal = new Date(exam.scheduled_start_datetime_local); 
                    const examEndDateTimeLocal = new Date(exam.scheduled_end_datetime_local);
                    const nowLocal = new Date(); 
                    
                    let actionButton = '';
                    let timerHtml = '';

                    if (nowLocal >= examStartDateTimeLocal && nowLocal < examEndDateTimeLocal) {
                        exam.status = 'Running';
                    } else if (nowLocal < examStartDateTimeLocal) {
                        exam.status = 'Upcoming';
                    } else { 
                        exam.status = 'Ended';
                    }

                    if (exam.status === 'Running') {
                        examCard.classList.add('running');
                        if (exam.is_omr_exam == 1) {
                            if (exam.answer_key_id) { 
                                actionButton = `
                                    <a href="${BASE_URL}student/view_omr_questions.php?exam_id=${exam.exam_id}" target="_blank" class="btn-join">View Questions</a>
                                    <button class="btn-join btn-submit-omr" data-schedule-id="${exam.id}" data-exam-title="${exam.title}" data-answer-key-id="${exam.answer_key_id}">Submit OMR</button>
                                `;
                            } else {
                                actionButton = `<button class="btn-join" disabled>No Answer Key Set</button>`;
                            }
                        } else {
                            actionButton = `<a href="${BASE_URL}student/take_exam.php?schedule_id=${exam.id}" class="btn-join">Join Exam</a>`;
                        }
                        timerHtml = `<div class="countdown-timer"><i class="fa-solid fa-hourglass-start"></i> Running now!</div>`;
                    } else if (exam.status === 'Upcoming') {
                        examCard.classList.add('upcoming');
                        actionButton = `<button class="btn-join" disabled>${exam.is_omr_exam ? 'View Questions' : 'Join Exam'}</button>`;
                        const diff = examStartDateTimeLocal.getTime() - nowLocal.getTime();
                        if (diff > 0) {
                            timerHtml = `<div class="countdown-timer" data-starttime="${examStartDateTimeLocal.toISOString()}"><i class="fa-solid fa-hourglass-half"></i> <span></span></div>`;
                        } else {
                            timerHtml = `<div class="countdown-timer"><i class="fa-solid fa-hourglass-half"></i> Starting soon...</div>`;
                        }
                    } else if (exam.status === 'Ended') {
                        examCard.classList.add('ended');
                        actionButton = `<button class="btn-join" disabled>Ended</button>`;
                        timerHtml = `<div class="countdown-timer"><i class="fa-solid fa-hourglass-end"></i> Exam Ended</div>`;
                    }


                    examCard.innerHTML = `
                        <div class="exam-info">
                            <h4>${exam.title} <span class="badge">${exam.is_omr_exam ? 'OMR' : 'Online'}</span></h4>
                            <p>${exam.description || 'No description provided.'}</p>
                            <small>Date: ${new Date(exam.scheduled_start_datetime_local).toLocaleDateString()} | Time: ${new Date(exam.scheduled_start_datetime_local).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})} - ${new Date(exam.scheduled_end_datetime_local).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</small>
                        </div>
                        <div class="exam-action">
                            ${timerHtml}
                            ${actionButton}
                        </div>
                    `;
                    upcomingExamsList.appendChild(examCard);
                });
                startCountdownTimers();
            } else {
                upcomingExamsList.innerHTML = '<p>No upcoming or running exams scheduled at the moment. Please check back later.</p>';
            }
        } catch (error) {
            console.error('Error fetching upcoming exams:', error);
            upcomingExamsList.innerHTML = '<p>Could not load upcoming exams. Please try refreshing the page. Check console for more details.</p>';
        }
    };

    // --- Countdown Timer Logic (existing code) ---
    const startCountdownTimers = () => {
        const timers = document.querySelectorAll('.countdown-timer[data-starttime]');
        timers.forEach(timer => {
            const startTime = new Date(timer.getAttribute('data-starttime'));
            const timerSpan = timer.querySelector('span');
            if (timer.dataset.intervalId) { clearInterval(parseInt(timer.dataset.intervalId)); }
            const interval = setInterval(() => {
                const now = new Date();
                const diff = startTime.getTime() - now.getTime();
                if (diff <= 0) {
                    clearInterval(interval);
                    timerSpan.innerHTML = 'Starting...';
                    setTimeout(fetchUpcomingExams, 2000);
                    return;
                }
                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                if (days === 0 && hours === 0) {
                    if (minutes === 5 && seconds === 0) { alert('Exam starting in 5 minutes!'); } 
                    else if (minutes === 1 && seconds === 0) { alert('Exam starting in 1 minute!'); }
                }
                timerSpan.innerHTML = `${days}d ${String(hours).padStart(2, '0')}h ${String(minutes).padStart(2, '0')}m ${String(seconds).padStart(2, '0')}s`;
            }, 1000);
            timer.dataset.intervalId = interval.toString();
        });
    };

    // --- Fetch & Display Exam Stats (existing code) ---
    const fetchExamStats = async () => {
        try {
            const response = await fetch(BASE_URL + 'api/student/get_submissions.php');
            const result = await response.json();
            let totalExams = 0;
            let totalScoreSum = 0;
            if (result.status === 'success' && result.data.length > 0) {
                totalExams = result.data.length;
                result.data.forEach(sub => {
                    const percentage = (sub.total_marks > 0) ? ((sub.marks_obtained / sub.total_marks) * 100) : 0;
                    totalScoreSum += parseFloat(percentage);
                });
                totalExamsTakenDisplay.textContent = totalExams;
                averageScoreDisplay.textContent = totalExams > 0 ? (totalScoreSum / totalExams).toFixed(0) + '%' : '0%';
            } else {
                totalExamsTakenDisplay.textContent = '0';
                averageScoreDisplay.textContent = '0%';
            }
        } catch (error) {
            console.error('Error fetching exam stats:', error);
            totalExamsTakenDisplay.textContent = 'Error';
            averageScoreDisplay.textContent = 'Error';
        }
    };


    // --- Event Listener for OMR Submit Button (delegate to parent) ---
    upcomingExamsList.addEventListener('click', (e) => {
        const submitOmrBtn = e.target.closest('.btn-submit-omr');
        if (submitOmrBtn) {
            const scheduleId = submitOmrBtn.getAttribute('data-schedule-id');
            const examTitle = submitOmrBtn.getAttribute('data-exam-title');
            const answerKeyId = submitOmrBtn.getAttribute('data-answer-key-id');
            
            if (!answerKeyId || answerKeyId === 'null') { 
                alert('No answer key has been set for this OMR exam. Please contact your mentor/admin.');
                return;
            }

            openOmrModal(scheduleId, examTitle, answerKeyId); // Call helper to open modal
        }
    });
    
    closeOmrModalBtn.onclick = () => {
        omrModal.style.display = 'none';
        stopCamera(); // Stop camera when modal is closed
    };
    
    omrForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        omrSubmissionMessage.innerHTML = '<div class="message info"><i class="fa-solid fa-spinner fa-spin"></i> Submitting...</div>';
        
        const formData = new FormData(); // Manually create FormData
        formData.append('schedule_id', omrScheduleIdInput.value);
        formData.append('answer_key_id', omrAnswerKeyIdInput.value);
        formData.append('exam_title', omrExamTitleSpan.textContent); // Pass exam title for logging

        // Get image data from canvas and convert to Blob
        const imageDataURL = omrSheetDataInput.value;
        if (!imageDataURL) {
            omrSubmissionMessage.innerHTML = '<div class="message error">No image to upload. Please take a picture first.</div>';
            return;
        }
        const blob = await (await fetch(imageDataURL)).blob();
        formData.append('omr_sheet', blob, 'omr_sheet.png'); // Append as a file

        try {
            const response = await fetch(BASE_URL + 'api/student/submit_omr.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            let messageClass = response.ok ? 'success' : 'error';
            omrSubmissionMessage.innerHTML = `<div class="message ${messageClass}">${result.message}</div>`;
            
            if (response.ok) {
                stopCamera(); // Stop camera on successful submission
                setTimeout(() => {
                    omrModal.style.display = 'none';
                    fetchExamStats(); // Refresh stats
                    if (typeof fetchExamHistory === 'function') fetchExamHistory();
                }, 2000);
            }
        } catch (error) {
            console.error('Error submitting OMR:', error);
            omrSubmissionMessage.innerHTML = '<div class="message error">An error occurred. Please try again.</div>';
        }
    });

    // --- Initializers & Auto-refresh ---
    fetchUpcomingExams();
    fetchExamStats(); 
    
    setInterval(fetchUpcomingExams, 10000); 
    setInterval(fetchExamStats, 60000); 

    window.addEventListener('beforeunload', () => {
        document.querySelectorAll('.countdown-timer').forEach(timer => {
            if (timer.dataset.intervalId) {
                clearInterval(parseInt(timer.dataset.intervalId));
            }
        });
        stopCamera(); // Stop camera if user leaves the page
    });

    // --- Global Modal Close Listener ---
    window.addEventListener('click', (event) => {
        if (event.target == omrModal) {
            omrModal.style.display = 'none';
            stopCamera(); // Stop camera when modal is closed by clicking outside
        }
    });
});