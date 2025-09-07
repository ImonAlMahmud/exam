document.addEventListener('DOMContentLoaded', () => {
    // BASE_URL is globally available from the PHP file.

    // Form elements
    const profileForm = document.getElementById('profileForm');
    const passwordForm = document.getElementById('passwordForm');
    const pictureForm = document.getElementById('pictureForm');

    // Input fields
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const rollNoInput = document.getElementById('roll_no');
    const batchNameInput = document.getElementById('batch_name');
    const profilePicPreview = document.getElementById('profile-pic-preview');

    // Function to load profile data
    const loadProfile = async () => {
        try {
            const response = await fetch(`${BASE_URL}api/student/get_profile.php`);
            const result = await response.json();
            if (result.status === 'success') {
                const user = result.data;
                nameInput.value = user.name || '';
                emailInput.value = user.email || '';
                // Only show roll_no and batch_name if they exist and are for a student
                if (rollNoInput) rollNoInput.value = user.roll_no || '';
                if (batchNameInput) batchNameInput.value = user.batch_name || '';

                profilePicPreview.src = `${BASE_URL}uploads/profile_pictures/${user.profile_picture || 'default.png'}`;
            }
        } catch (error) {
            console.error('Error loading profile:', error);
            alert('Failed to load profile information.');
        }
    };

    // Handle profile info update
    profileForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = {
            name: nameInput.value,
            // Include roll_no and batch_name only if they exist in the form (i.e., for students)
            roll_no: rollNoInput ? rollNoInput.value : null,
            batch_name: batchNameInput ? batchNameInput.value : null
        };
        const response = await fetch(`${BASE_URL}api/student/update_profile.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        alert(result.message);
        if (result.status === 'success') {
            // Optionally update UI here if needed
        }
    });

    // Handle profile picture update
    pictureForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(pictureForm);
        try {
            const response = await fetch(`${BASE_URL}api/student/update_picture.php`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            alert(result.message);
            if (result.status === 'success') {
                profilePicPreview.src = `${BASE_URL}uploads/profile_pictures/${result.filepath}?t=${new Date().getTime()}`; 
            }
        } catch (error) {
            console.error('Error uploading images:', error);
            alert('Error uploading images.');
        }
    });

    // Handle password change
    passwordForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const currentPassword = document.getElementById('current_password').value;
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (newPassword !== confirmPassword) {
            alert('New password and confirm password do not match.');
            return;
        }
        if (newPassword.length < 6) { // Basic validation
            alert('New password must be at least 6 characters long.');
            return;
        }

        try {
            const response = await fetch(`${BASE_URL}api/auth/change_password.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ current_password: currentPassword, new_password: newPassword })
            });
            const result = await response.json();
            alert(result.message);
            if (response.ok) {
                passwordForm.reset(); // Clear form on success
            }
        } catch (error) {
            console.error('Error changing password:', error);
            alert('An error occurred while changing password. Please try again.');
        }
    });

    // Initial load
    loadProfile();
});