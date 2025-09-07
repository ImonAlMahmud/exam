document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.getElementById('users-table-body');
    const filterTabs = document.querySelectorAll('.tab-link');
    
    // Create/Edit User Modal elements
    const userModal = document.getElementById('userModal');
    const userForm = document.getElementById('userForm');
    const modalTitle = document.getElementById('modalTitle');
    const createUserBtn = document.getElementById('createUserBtn');
    const closeUserModalBtn = document.getElementById('closeUserModalBtn'); // Added ID to HTML

    // Input fields for User Form
    const userIdInput = document.getElementById('userId');
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const roleSelect = document.getElementById('role');
    
    // Additional fields for Student
    let rollNoInput;
    let batchNameInput; // These will be created/managed dynamically

    // BASE_URL is globally available from the PHP file.

    // Function to dynamically add/remove student-specific fields
    const toggleStudentFields = (show) => {
        const existingRollNoGroup = document.getElementById('form-group-roll_no');
        const existingBatchNameGroup = document.getElementById('form-group-batch_name');

        if (show) {
            if (!existingRollNoGroup) {
                const rollNoGroup = document.createElement('div');
                rollNoGroup.className = 'form-group';
                rollNoGroup.id = 'form-group-roll_no';
                rollNoGroup.innerHTML = `
                    <label for="roll_no">Roll No:</label>
                    <input type="text" id="roll_no" name="roll_no">
                `;
                emailInput.closest('.form-group').after(rollNoGroup); // Insert after email
                rollNoInput = document.getElementById('roll_no');
            }
            if (!existingBatchNameGroup) {
                const batchNameGroup = document.createElement('div');
                batchNameGroup.className = 'form-group';
                batchNameGroup.id = 'form-group-batch_name';
                batchNameGroup.innerHTML = `
                    <label for="batch_name">Batch Name:</label>
                    <input type="text" id="batch_name" name="batch_name">
                `;
                rollNoInput.closest('.form-group').after(batchNameGroup); // Insert after roll_no
                batchNameInput = document.getElementById('batch_name');
            }
        } else {
            if (existingRollNoGroup) existingRollNoGroup.remove();
            if (existingBatchNameGroup) existingBatchNameGroup.remove();
            rollNoInput = null;
            batchNameInput = null;
        }
    };

    // Listen for role change in the modal
    roleSelect.addEventListener('change', () => {
        toggleStudentFields(roleSelect.value === 'Student');
    });

    // Function to fetch and display users
    const fetchUsers = async (role = 'all') => {
        try {
            const response = await fetch(`${BASE_URL}api/users/read.php?role=${role}`);
            const result = await response.json();
            tableBody.innerHTML = ''; 

            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(user => {
                    const row = `
                        <tr>
                            <td>${user.name}</td>
                            <td>${user.email}</td>
                            <td><span class="role-badge role-${user.role.toLowerCase()}">${user.role}</span></td>
                            <td class="actions">
                                <button class="btn-action btn-edit" data-id="${user.id}" title="Edit User"><i class="fa-solid fa-pencil-alt"></i></button>
                                <button class="btn-action btn-reset" data-id="${user.id}" data-name="${user.name}" title="Reset Password"><i class="fa-solid fa-key"></i></button>
                                <button class="btn-action btn-delete" data-id="${user.id}" data-name="${user.name}" title="Delete User"><i class="fa-solid fa-trash"></i></button>
                            </td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="4">No users found.</td></tr>';
            }
        } catch (error) {
            console.error('Error fetching users:', error);
            tableBody.innerHTML = '<tr><td colspan="4">Error loading data.</td></tr>';
        }
    };

    // Handle filter tab clicks
    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            filterTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            fetchUsers(this.getAttribute('data-role'));
        });
    });
    
    // --- Modal Handling ---
    createUserBtn.onclick = () => {
        userForm.reset();
        modalTitle.textContent = 'Create New User';
        userIdInput.value = '';
        passwordInput.required = true; // Password is required for new user
        passwordInput.placeholder = 'Password';
        toggleStudentFields(false); // Hide student fields initially
        userModal.style.display = 'block';
    };
    closeUserModalBtn.onclick = () => { userModal.style.display = 'none'; }; // Close button for modal
    window.onclick = (event) => { // Global close modal
        if (event.target == userModal) userModal.style.display = 'none';
    };

    // --- Handle Form Submission (Create or Update User) ---
    userForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(userForm);
        const data = Object.fromEntries(formData.entries());

        const isEditing = !!data.userId; // Check if userId exists for update

        // If editing and password field is empty, remove it from data to prevent changing
        if (isEditing && !data.password) {
            delete data.password;
        }

        try {
            const apiEndpoint = isEditing ? `${BASE_URL}api/users/update_user.php` : `${BASE_URL}api/users/create.php`;
            const response = await fetch(apiEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            alert(result.message);

            if (response.ok) {
                userModal.style.display = 'none';
                fetchUsers(document.querySelector('.tab-link.active').getAttribute('data-role'));
            }
        } catch (error) {
            console.error('Error saving user:', error);
            alert('An error occurred. Please try again.');
        }
    });

    // --- Handle Actions (Edit, Delete, Reset Password) ---
    tableBody.addEventListener('click', async (e) => {
        const deleteBtn = e.target.closest('.btn-delete');
        const resetBtn = e.target.closest('.btn-reset');
        const editBtn = e.target.closest('.btn-edit');

        // Handle Delete Action
        if (deleteBtn) {
            const userId = deleteBtn.getAttribute('data-id');
            const userName = deleteBtn.getAttribute('data-name');

            if (confirm(`Are you sure you want to delete the user "${userName}"? This action cannot be undone.`)) {
                try {
                    const response = await fetch(`${BASE_URL}api/users/delete.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: userId })
                    });
                    const result = await response.json();
                    alert(result.message);
                    if (response.ok) fetchUsers(document.querySelector('.tab-link.active').getAttribute('data-role'));
                } catch (error) {
                    console.error('Error deleting user:', error);
                    alert('An error occurred while deleting the user.');
                }
            }
        }

        // Handle Reset Password Action
        if (resetBtn) {
            const userId = resetBtn.getAttribute('data-id');
            const userName = resetBtn.getAttribute('data-name');
            const newPassword = prompt(`Enter the new password for ${userName}:`);

            if (newPassword && newPassword.trim() !== "") {
                try {
                    const response = await fetch(`${BASE_URL}api/users/reset_password.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: userId, password: newPassword })
                    });
                    const result = await response.json();
                    alert(result.message);
                } catch (error) {
                    console.error('Error resetting password:', error);
                    alert('An error occurred while resetting the password.');
                }
            } else if (newPassword !== null) { // User clicked OK but left it empty
                alert("Password cannot be empty.");
            }
        }

        // Handle Edit User Action
        if (editBtn) {
            const userId = editBtn.getAttribute('data-id');
            try {
                const response = await fetch(`${BASE_URL}api/users/get_user_details.php?user_id=${userId}`);
                const result = await response.json();

                if (result.status === 'success' && result.data) {
                    const user = result.data;
                    modalTitle.textContent = 'Edit User';
                    userIdInput.value = user.id;
                    nameInput.value = user.name;
                    emailInput.value = user.email;
                    roleSelect.value = user.role;
                    passwordInput.value = ''; // Clear password field for security
                    passwordInput.required = false; // Not required for edit
                    passwordInput.placeholder = 'Leave blank to keep current password';

                    toggleStudentFields(user.role === 'Student');
                    if (user.role === 'Student') {
                        if (rollNoInput) rollNoInput.value = user.roll_no || '';
                        if (batchNameInput) batchNameInput.value = user.batch_name || '';
                    }

                    userModal.style.display = 'block';
                } else {
                    alert(result.message || 'Error loading user details.');
                }
            } catch (error) {
                console.error('Error fetching user details:', error);
                alert('An error occurred while loading user details.');
            }
        }
    });

    // Initial load of users
    fetchUsers();
});