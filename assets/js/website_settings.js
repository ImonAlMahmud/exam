document.addEventListener('DOMContentLoaded', function() {
    // General Settings
    const generalSettingsForm = document.getElementById('generalSettingsForm');
    const appNameInput = document.getElementById('app_name');
    const developerNameInput = document.getElementById('developer_name');
    const supportEmailInput = document.getElementById('support_email');

    // Logo & Favicon
    const logoFaviconForm = document.getElementById('logoFaviconForm');
    const currentLogoLight = document.getElementById('current_logo_light');
    const currentFavicon = document.getElementById('current_favicon');
    const logoLightInput = document.getElementById('logo_light_input');
    const faviconInput = document.getElementById('favicon_input');

    // Hero Section
    const heroSectionForm = document.getElementById('heroSectionForm');
    const heroTitleInput = document.getElementById('hero_title');
    const heroSubtitleInput = document.getElementById('hero_subtitle');

    // Pricing Plans
    const pricingPlansList = document.getElementById('pricingPlansList');
    const addPricingPlanBtn = document.getElementById('addPricingPlanBtn');
    const pricingModal = document.getElementById('pricingModal');
    const pricingForm = document.getElementById('pricingForm');
    const pricingModalTitle = document.getElementById('pricingModalTitle');

    // Testimonials
    const testimonialsList = document.getElementById('testimonialsList');
    const addTestimonialBtn = document.getElementById('addTestimonialBtn');
    const testimonialModal = document.getElementById('testimonialModal');
    const testimonialForm = document.getElementById('testimonialForm');
    const testimonialModalTitle = document.getElementById('testimonialModalTitle');
    const currentAuthorImage = document.getElementById('current_author_image');
    const authorImageInput = document.getElementById('author_image_input');
    
    // BASE_URL is globally available from PHP

    // --- Helper to close modals ---
    document.querySelectorAll('.modal .close-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.modal').style.display = 'none';
        });
    });
    window.onclick = (event) => {
        if (event.target == pricingModal) pricingModal.style.display = 'none';
        if (event.target == testimonialModal) testimonialModal.style.display = 'none';
    };

    // --- 1. Load All Settings ---
    const loadAllSettings = async () => {
        try {
            const response = await fetch(BASE_URL + 'api/admin/get_settings.php');
            const result = await response.json();
            if (result.status === 'success') {
                const settings = result.data;
                // General
                appNameInput.value = settings.app_name || '';
                developerNameInput.value = settings.developer_name || '';
                supportEmailInput.value = settings.support_email || '';
                // Logo/Favicon
                currentLogoLight.src = `${BASE_URL}uploads/website/${settings.logo_light || 'logo-light.png'}`;
                currentFavicon.src = `${BASE_URL}uploads/website/${settings.favicon || 'favicon.ico'}`;
                // Hero Section
                heroTitleInput.value = settings.hero_title || '';
                heroSubtitleInput.value = settings.hero_subtitle || '';
            }
        } catch (error) {
            console.error('Error loading general settings:', error);
            alert('Failed to load website settings.'); // More visible error for admin
        }
    };

    // --- 2. Save General Settings ---
    generalSettingsForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(generalSettingsForm);
        const data = Object.fromEntries(formData.entries());
        try {
            const response = await fetch(BASE_URL + 'api/admin/update_general_settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            alert(result.message);
            if (response.ok) loadAllSettings(); // Refresh settings
        } catch (error) {
            console.error('Error saving general settings:', error);
            alert('Error saving general settings.');
        }
    });

    // --- 3. Upload Logo & Favicon ---
    logoFaviconForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(logoFaviconForm);
        try {
            const response = await fetch(BASE_URL + 'api/admin/upload_logo_favicon.php', {
                method: 'POST',
                body: formData // FormData handles file uploads directly
            });
            const result = await response.json();
            alert(result.message);
            if (response.ok) loadAllSettings(); // Refresh images after successful upload
        } catch (error) {
            console.error('Error uploading images:', error);
            alert('Error uploading images.');
        }
    });

    // --- 4. Save Hero Section Content ---
    heroSectionForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(heroSectionForm);
        const data = Object.fromEntries(formData.entries());
        try {
            const response = await fetch(BASE_URL + 'api/admin/update_hero_settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            alert(result.message);
            if (response.ok) loadAllSettings(); // Refresh settings
        } catch (error) {
            console.error('Error saving hero content:', error);
            alert('Error saving hero content.');
        }
    });

    // --- 5. Pricing Plans Management ---
    const loadPricingPlans = async () => {
        pricingPlansList.innerHTML = '<p class="loader">Loading pricing plans...</p>';
        try {
            const response = await fetch(BASE_URL + 'api/admin/get_pricing_plans.php');
            const result = await response.json();
            pricingPlansList.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(plan => {
                    const planCard = document.createElement('div');
                    planCard.className = `pricing-admin-card ${plan.is_popular ? 'popular' : ''}`;
                    planCard.innerHTML = `
                        <h4>${plan.plan_name} (${plan.price}${plan.frequency})</h4>
                        <p>${plan.description}</p>
                        <ul>${plan.features.split('\n').map(f => `<li>${f}</li>`).join('')}</ul>
                        <div class="actions">
                            <button class="btn-action btn-edit" data-id="${plan.id}" title="Edit Plan"><i class="fa-solid fa-pencil-alt"></i></button>
                            <button class="btn-action btn-delete" data-id="${plan.id}" title="Delete Plan"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    `;
                    pricingPlansList.appendChild(planCard);
                });
            } else {
                pricingPlansList.innerHTML = '<p>No pricing plans found. Click "Add New Plan" to create one.</p>';
            }
        } catch (error) {
            console.error('Error loading pricing plans:', error);
            pricingPlansList.innerHTML = '<p class="message error">Error loading pricing plans.</p>';
        }
    };

    addPricingPlanBtn.addEventListener('click', () => {
        pricingForm.reset();
        document.getElementById('planId').value = '';
        pricingModalTitle.textContent = 'Add New Pricing Plan';
        document.getElementById('is_popular').checked = false;
        document.getElementById('pricing_display_order').value = 10;
        pricingModal.style.display = 'block';
    });

    pricingPlansList.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.btn-edit');
        const deleteBtn = e.target.closest('.btn-delete');

        if (editBtn) {
            const planId = editBtn.getAttribute('data-id');
            // Fetch single plan details to populate form
            try {
                const response = await fetch(BASE_URL + 'api/admin/get_pricing_plans.php'); // Re-fetch all to find specific plan
                const result = await response.json();
                if (result.status === 'success') {
                    const plan = result.data.find(p => p.id == planId);
                    if (plan) {
                        pricingForm.reset();
                        pricingModalTitle.textContent = 'Edit Pricing Plan';
                        document.getElementById('planId').value = plan.id;
                        document.getElementById('plan_name').value = plan.plan_name;
                        document.getElementById('pricing_description').value = plan.description;
                        document.getElementById('price').value = plan.price;
                        document.getElementById('frequency').value = plan.frequency;
                        document.getElementById('features').value = plan.features;
                        document.getElementById('is_popular').checked = plan.is_popular == 1;
                        document.getElementById('pricing_display_order').value = plan.display_order;
                        pricingModal.style.display = 'block';
                    }
                }
            } catch (error) { console.error('Error fetching plan for edit:', error); alert('Error loading plan for edit.'); }
        } else if (deleteBtn) {
            const planId = deleteBtn.getAttribute('data-id');
            if (confirm('Are you sure you want to delete this pricing plan?')) {
                try {
                    const response = await fetch(BASE_URL + 'api/admin/delete_pricing_plan.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: planId })
                    });
                    const result = await response.json();
                    alert(result.message);
                    if (response.ok) loadPricingPlans();
                } catch (error) { console.error('Error deleting plan:', error); alert('Error deleting plan.'); }
            }
        }
    });

    pricingForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(pricingForm);
        const data = Object.fromEntries(formData.entries());
        data.is_popular = formData.has('is_popular') ? 1 : 0; // Ensure boolean checkbox sends 0 or 1
        try {
            const response = await fetch(BASE_URL + 'api/admin/save_pricing_plan.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            alert(result.message);
            if (response.ok) {
                pricingModal.style.display = 'none';
                loadPricingPlans();
            }
        } catch (error) { console.error('Error saving plan:', error); alert('Error saving pricing plan.'); }
    });

    // --- 6. Testimonials Management ---
    const loadTestimonials = async () => {
        testimonialsList.innerHTML = '<p class="loader">Loading testimonials...</p>';
        try {
            const response = await fetch(BASE_URL + 'api/admin/get_testimonials.php');
            const result = await response.json();
            testimonialsList.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(t => {
                    const testimonialCard = document.createElement('div');
                    testimonialCard.className = 'testimonial-admin-card';
                    testimonialCard.innerHTML = `
                        <img src="${BASE_URL}uploads/website/${t.author_image_url || 'default-avatar.png'}" alt="${t.author_name}" class="admin-testimonial-img">
                        <h4>${t.author_name} - ${t.author_title}</h4>
                        <p>"${t.quote_text}"</p>
                        <div class="actions">
                            <button class="btn-action btn-edit" data-id="${t.id}" title="Edit Testimonial"><i class="fa-solid fa-pencil-alt"></i></button>
                            <button class="btn-action btn-delete" data-id="${t.id}" title="Delete Testimonial"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    `;
                    testimonialsList.appendChild(testimonialCard);
                });
            } else {
                testimonialsList.innerHTML = '<p>No testimonials found. Click "Add New Testimonial" to create one.</p>';
            }
        } catch (error) {
            console.error('Error loading testimonials:', error);
            testimonialsList.innerHTML = '<p class="message error">Error loading testimonials.</p>';
        }
    };

    addTestimonialBtn.addEventListener('click', () => {
        testimonialForm.reset();
        document.getElementById('testimonialId').value = '';
        testimonialModalTitle.textContent = 'Add New Testimonial';
        currentAuthorImage.src = `${BASE_URL}uploads/website/default-avatar.png`; // Default for new
        // Clear file input specifically
        authorImageInput.value = ''; 
        testimonialModal.style.display = 'block';
    });

    testimonialsList.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.btn-edit');
        const deleteBtn = e.target.closest('.btn-delete');

        if (editBtn) {
            const testimonialId = editBtn.getAttribute('data-id');
            try {
                const response = await fetch(`${BASE_URL}api/admin/get_testimonials.php`); // Re-fetch all to find specific testimonial
                const result = await response.json();
                if (result.status === 'success') {
                    const t = result.data.find(tst => tst.id == testimonialId);
                    if (t) {
                        testimonialForm.reset();
                        testimonialModalTitle.textContent = 'Edit Testimonial';
                        document.getElementById('testimonialId').value = t.id;
                        document.getElementById('author_name').value = t.author_name;
                        document.getElementById('author_title').value = t.author_title;
                        document.getElementById('quote_text').value = t.quote_text;
                        document.getElementById('testimonial_display_order').value = t.display_order;
                        currentAuthorImage.src = `${BASE_URL}uploads/website/${t.author_image_url || 'default-avatar.png'}`;
                        // This hidden field is crucial to retain the existing image if no new one is uploaded
                        const hiddenImageInput = document.createElement('input');
                        hiddenImageInput.type = 'hidden';
                        hiddenImageInput.name = 'current_author_image_val';
                        hiddenImageInput.value = t.author_image_url;
                        testimonialForm.appendChild(hiddenImageInput);
                        authorImageInput.value = ''; // Clear file input
                        testimonialModal.style.display = 'block';
                    }
                }
            } catch (error) { console.error('Error fetching testimonial for edit:', error); alert('Error loading testimonial for edit.'); }
        } else if (deleteBtn) {
            const testimonialId = deleteBtn.getAttribute('data-id');
            if (confirm('Are you sure you want to delete this testimonial?')) {
                try {
                    const response = await fetch(BASE_URL + 'api/admin/delete_testimonial.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: testimonialId })
                    });
                    const result = await response.json();
                    alert(result.message);
                    if (response.ok) loadTestimonials();
                } catch (error) { console.error('Error deleting testimonial:', error); alert('Error deleting testimonial.'); }
            }
        }
    });

    testimonialForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(testimonialForm);
        // FormData automatically handles file uploads (e.g., author_image).
        // No need for JSON.stringify when sending FormData with files.
        try {
            const response = await fetch(BASE_URL + 'api/admin/save_testimonial.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            alert(result.message);
            if (response.ok) {
                testimonialModal.style.display = 'none';
                loadTestimonials();
            }
        } catch (error) { console.error('Error saving testimonial:', error); alert('Error saving testimonial.'); }
    });


    // --- Initial Loads ---
    loadAllSettings();
    loadPricingPlans();
    loadTestimonials();
});