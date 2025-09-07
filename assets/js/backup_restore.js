document.addEventListener('DOMContentLoaded', function() {
    const createBackupBtn = document.getElementById('createBackupBtn');
    const backupMessage = document.getElementById('backupMessage');
    const restoreForm = document.getElementById('restoreForm');
    const restoreMessage = document.getElementById('restoreMessage');
    
    // BASE_URL is globally available from PHP.

    // --- Create Backup ---
    createBackupBtn.addEventListener('click', async () => {
        backupMessage.innerHTML = '<div class="message info"><i class="fa-solid fa-spinner fa-spin"></i> Generating backup...</div>';
        try {
            // We directly open the URL in a new window/tab to trigger download
            window.open(BASE_URL + 'api/admin/create_backup.php', '_blank');
            backupMessage.innerHTML = '<div class="message success"><i class="fa-solid fa-check"></i> Backup initiated. Download should begin shortly.</div>';
        } catch (error) {
            console.error('Backup error:', error);
            backupMessage.innerHTML = '<div class="message error"><i class="fa-solid fa-times"></i> Failed to create backup. Check console for details.</div>';
        }
    });

    // --- Restore Database ---
    restoreForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        restoreMessage.innerHTML = '<div class="message info"><i class="fa-solid fa-spinner fa-spin"></i> Restoring database... This may take a moment and will log you out.</div>';

        const formData = new FormData(restoreForm);

        try {
            const response = await fetch(BASE_URL + 'api/admin/restore_database.php', {
                method: 'POST',
                body: formData // FormData automatically sets Content-Type for file uploads
            });
            const result = await response.json();
            
            let messageClass = response.ok ? 'success' : 'error';
            restoreMessage.innerHTML = `<div class="message ${messageClass}"><i class="fa-solid fa-${response.ok ? 'check' : 'times'}"></i> ${result.message}</div>`;

            if (response.ok) {
                // If restore is successful, the server already destroyed the session.
                // Redirect to login page.
                alert("Database restored! You will be redirected to the login page.");
                window.location.href = BASE_URL + 'login.php'; 
            }

        } catch (error) {
            console.error('Restore error:', error);
            restoreMessage.innerHTML = '<div class="message error"><i class="fa-solid fa-times"></i> An unexpected error occurred during restore. Check console.</div>';
        }
    });
});