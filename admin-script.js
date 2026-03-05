// Admin Panel JavaScript with Animations
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarToggleMobile = document.getElementById('sidebarToggleMobile');
    const mainContent = document.getElementById('mainContent');
    const menuItems = document.querySelectorAll('.menu-item[data-page]');
    const pageTitle = document.getElementById('pageTitle');
    const pages = document.querySelectorAll('.page');
    const aboutModal = document.getElementById('aboutModal');
    const eventModal = document.getElementById('eventModal');
    const modalCloses = document.querySelectorAll('.modal-close');
    const aboutForm = document.getElementById('aboutForm');
    const eventForm = document.getElementById('eventForm');
    const createUserForm = document.getElementById('createUserForm');
    const addAboutBtn = document.getElementById('addAboutBtn');
    const addEventBtn = document.getElementById('addEventBtn');
    const studentDataModal = document.getElementById('studentDataModal');
    const studentDataForm = document.getElementById('studentDataForm');
    let studentEditor;

    // Initialize CKEditor
    if (document.querySelector('#studentContent')) {
        ClassicEditor
            .create(document.querySelector('#studentContent'))
            .then(editor => {
                studentEditor = editor;
            })
            .catch(error => console.error(error));
    }

    // Sidebar Toggle
    function toggleSidebar() {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('sidebar-collapsed');
    }

    sidebarToggle.addEventListener('click', toggleSidebar);
    sidebarToggleMobile.addEventListener('click', () => {
        sidebar.classList.toggle('show');
    });

    // Page Navigation with Slide Animation
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const pageName = this.getAttribute('data-page');

            // Update active menu item
            menuItems.forEach(mi => mi.classList.remove('active'));
            this.classList.add('active');

            // Hide all pages with slide out animation
            pages.forEach(page => {
                if (!page.classList.contains('hidden')) {
                    page.style.animation = 'slideOutLeft 0.3s ease-in forwards';
                    setTimeout(() => {
                        page.classList.add('hidden');
                        page.style.animation = '';
                    }, 300);
                }
            });

            // Show selected page with slide in animation
            setTimeout(() => {
                const targetPage = document.getElementById(pageName + 'Page');
                targetPage.classList.remove('hidden');
                targetPage.style.animation = 'slideInRight 0.4s ease-out';
                pageTitle.textContent = pageName.charAt(0).toUpperCase() + pageName.slice(1);
            }, 350);
        });
    });

    // Modal Functions
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.remove('show');
        document.body.style.overflow = '';
        // Reset forms
        if (modalId === 'aboutModal') {
            aboutForm.reset();
            document.getElementById('aboutId').value = '';
            document.getElementById('aboutModalTitle').textContent = 'Add About Card';
        } else if (modalId === 'eventModal') {
            eventForm.reset();
            document.getElementById('eventId').value = '';
            document.getElementById('eventModalTitle').textContent = 'Add Event';
        } else if (modalId === 'studentDataModal') {
            studentDataForm.reset();
            document.getElementById('studentContent').value = '';
            if (studentEditor) studentEditor.setData('');
        }
    }

    addAboutBtn.addEventListener('click', () => openModal('aboutModal'));
    addEventBtn.addEventListener('click', () => openModal('eventModal'));

    modalCloses.forEach(close => {
        close.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal');
            closeModal(modalId);
        });
    });

    [aboutModal, eventModal, studentDataModal].forEach(modal => {
        if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                const modalId = modal.id;
                closeModal(modalId);
            }
        });
        }
    });

    // Form Submission
    aboutForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('about_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeModal();
                showNotification('About card saved successfully!', 'success');
                // Refresh about cards
                location.reload();
            } else {
                showNotification('Error saving about card', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error saving about card', 'error');
        });
    });

    // Create User Form Submission
    if (createUserForm) {
        createUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('usercreate.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    this.reset();
                } else {
                    showNotification(data.message || 'Error creating user', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An unexpected error occurred', 'error');
            });
        });
    }

    // Toggle Password Visibility for Create User
    const toggleUserPassword = document.querySelector('#createUserForm .toggle-password');
    const createUserPasswordInput = document.getElementById('createUserPassword');

    if (toggleUserPassword && createUserPasswordInput) {
        toggleUserPassword.addEventListener('click', function() {
            const type = createUserPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            createUserPasswordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? 'lock' : 'lock_open';
        });
    }

    // Edit About Card
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-btn')) {
            const id = e.target.getAttribute('data-id');
            // Fetch card data and populate modal
            fetch(`get_about.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('aboutId').value = data.id;
                    document.getElementById('aboutTitle').value = data.title;
                    document.getElementById('aboutContent').value = data.content;
                    document.getElementById('aboutOrder').value = data.display_order;
                    document.getElementById('modalTitle').textContent = 'Edit About Card';
                    openModal();
                });
        }

        // Delete About Card
        if (e.target.classList.contains('delete-btn') && confirm('Are you sure you want to delete this item?')) {
            const id = e.target.getAttribute('data-id');
            const type = e.target.closest('.page').id.replace('Page', '');

            fetch(`delete_${type}.php?id=${id}`)
                .then(response => response.text())
                .then(() => {
                    showNotification('Item deleted successfully!', 'success');
                    location.reload();
                });
        }

        // Manage Student Data
        if (e.target.classList.contains('manage-student-btn')) {
            const username = e.target.getAttribute('data-username');
            document.getElementById('studentUsername').value = username;
            document.getElementById('studentModalTitle').textContent = 'Manage Data: ' + username;
            openModal('studentDataModal');
        }
    });

    // Handle Section Change in Student Modal
    const studentSectionSelect = document.getElementById('studentSectionId');
    if (studentSectionSelect) {
        studentSectionSelect.addEventListener('change', function() {
            const username = document.getElementById('studentUsername').value;
            const sectionId = this.value;
            if (username && sectionId) {
                // Add timestamp to prevent caching of old data
                fetch(`student_data_api.php?username=${encodeURIComponent(username)}&section_id=${encodeURIComponent(sectionId)}&t=${new Date().getTime()}`)
                    .then(response => response.json())
                    .then(data => {
                        if (studentEditor) {
                            studentEditor.setData(data.content || '');
                        } else {
                            document.getElementById('studentContent').value = data.content || '';
                        }
                    })
                    .catch(err => console.error('Error loading student data:', err));
            }
        });
    }

    // Student Data Form Submission
    if (studentDataForm) {
        studentDataForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const formData = new FormData(this);
            if (studentEditor) {
                formData.set('content', studentEditor.getData());
            }

            fetch('student_data_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Student data saved!', 'success');
                    closeModal('studentDataModal');
                } else {
                    showNotification('Error saving data', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while saving.', 'error');
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.textContent = 'Save Data';
                    submitBtn.disabled = false;
                }
            });
        });
    }

    // Notification System
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        `;

        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => notification.classList.add('show'), 100);

        // Auto remove
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);

        // Close button
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        });
    }

    // Animate stats counters
    function animateCounters() {
        const counters = document.querySelectorAll('.stat-content h3');
        counters.forEach(counter => {
            const target = parseInt(counter.textContent);
            let current = 0;
            const increment = target / 50;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    counter.textContent = target;
                    clearInterval(timer);
                } else {
                    counter.textContent = Math.floor(current);
                }
            }, 30);
        });
    }

    // Trigger animations on page load
    animateCounters();

    // Stagger animations for cards
    const cards = document.querySelectorAll('.stat-card, .about-card, .event-item');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });

    // Smooth scroll for all links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Add hover effects to buttons
    document.querySelectorAll('.btn-primary, .btn-secondary, .btn-danger').forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px) scale(1.05)';
        });

        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });

    // File Upload Handling
    const uploadForm = document.getElementById('uploadForm');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('upload_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('File uploaded successfully!', 'success');
                    // Refresh uploaded files
                    loadUploadedFiles();
                } else {
                    showNotification('Error uploading file', 'error');
                }
            });
        });
    }

    function loadUploadedFiles() {
        fetch('get_uploads.php')
            .then(response => response.json())
            .then(files => {
                const container = document.getElementById('uploadedFiles');
                container.innerHTML = files.map(file => `
                    <div class="uploaded-file">
                        <span>${file.name}</span>
                        <a href="${file.path}" target="_blank">View</a>
                        <button class="btn-danger delete-file" data-file="${file.name}">Delete</button>
                    </div>
                `).join('');
            });
    }

    // Load uploaded files on uploads page
    if (document.getElementById('uploadsPage')) {
        loadUploadedFiles();
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Escape to close modal
        if (e.key === 'Escape' && aboutModal.classList.contains('show')) {
            closeModal();
        }

        // Ctrl/Cmd + B to toggle sidebar
        if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
            e.preventDefault();
            toggleSidebar();
        }
    });

    // Add loading states
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.textContent = 'Saving...';
                submitBtn.disabled = true;
            }
        });
    });
});

// CSS Animations (added via JS for dynamic control)
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOutLeft {
        to {
            opacity: 0;
            transform: translateX(-50px);
        }
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(50px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        z-index: 3000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .notification.show {
        transform: translateX(0);
    }

    .notification-success {
        background: #28a745;
    }

    .notification-error {
        background: #dc3545;
    }

    .notification-info {
        background: #17a2b8;
    }

    .notification-close {
        background: none;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
        margin-left: 10px;
    }

    .uploaded-file {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        background: rgba(255, 255, 255, 0.8);
        border-radius: 8px;
        margin-bottom: 10px;
    }
`;
document.head.appendChild(style);