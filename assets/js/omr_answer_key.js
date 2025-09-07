document.addEventListener('DOMContentLoaded', function() {
    const answerKeyForm = document.getElementById('answerKeyForm');
    const uploadBtn = document.getElementById('uploadAnswerKeyBtn');
    const uploadResultMessage = document.getElementById('uploadResultMessage');
    const answerKeysTableBody = document.getElementById('answer-keys-table-body');
    
    // BASE_URL and EXAM_ID are globally available from PHP.

    // --- Fetch & Display Existing Answer Keys ---
    const fetchAnswerKeys = async () => {
        try {
            const response = await fetch(`${BASE_URL}api/admin/get_answer_keys.php?exam_id=${EXAM_ID}`);
            const result = await response.json();
            
            answerKeysTableBody.innerHTML = ''; 

            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(key => {
                    const row = `
                        <tr>
                            <td>${key.id}</td>
                            <td>${key.uploaded_by}</td>
                            <td>${new Date(key.created_at).toLocaleString()}</td>
                            <td class="actions">
                                <button class="btn-action btn-delete-key" data-id="${key.id}" title="Delete Answer Key"><i class="fa-solid fa-trash"></i></button>
                            </td>
                        </tr>
                    `;
                    answerKeysTableBody.innerHTML += row;
                });
            } else if (result.status === 'error') {
                answerKeysTableBody.innerHTML = `<tr><td colspan="4" class="message error">${result.message}</td></tr>`;
            } else {
                answerKeysTableBody.innerHTML = '<tr><td colspan="4">No answer keys uploaded for this exam yet.</td></tr>';
            }
        } catch (error) {
            console.error('Error fetching answer keys:', error);
            answerKeysTableBody.innerHTML = '<tr><td colspan="4" class="message error">Error loading answer keys.</td></tr>';
        }
    };

    // --- Handle Answer Key Upload ---
    answerKeyForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading...';
        uploadResultMessage.innerHTML = '';

        const formData = new FormData(this); 

        try {
            const response = await fetch(`${BASE_URL}api/admin/upload_answer_key.php`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            let messageClass = response.ok ? 'success' : 'error';
            uploadResultMessage.innerHTML = `<div class="message ${messageClass}">${result.message}</div>`;

            if (response.ok) {
                fetchAnswerKeys();
                answerKeyForm.reset();
            }
        } catch (error) {
            console.error('Error uploading answer key:', error);
            uploadResultMessage.innerHTML = `<div class="message error">A network error occurred. Please try again.</div>`;
        } finally {
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="fa-solid fa-upload"></i> Upload Answer Key';
        }
    });

    // --- Handle Delete Answer Key ---
    answerKeysTableBody.addEventListener('click', async (e) => {
        const deleteBtn = e.target.closest('.btn-delete-key');
        if (deleteBtn) {
            const keyId = deleteBtn.getAttribute('data-id');
            if (confirm(`Are you sure you want to delete Answer Key #${keyId}? This action cannot be undone.`)) {
                try {
                    const response = await fetch(`${BASE_URL}api/admin/delete_answer_key.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ answer_key_id: keyId })
                    });
                    const result = await response.json();
                    alert(result.message);
                    if (response.ok) {
                        fetchAnswerKeys(); // Refresh the list
                    }
                } catch (error) {
                    console.error('Error deleting answer key:', error);
                    alert('An error occurred while deleting the answer key.');
                }
            }
        }
    });

    // Initial load
    fetchAnswerKeys();
});