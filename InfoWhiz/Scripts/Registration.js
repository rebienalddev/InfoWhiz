document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form values
            const username = document.getElementById('username').value.trim();
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const studentId = document.getElementById('student_id').value.trim();
            const gradeLevel = document.getElementById('grade_level').value;
            const strand = document.getElementById('strand').value;
            
            // Validation checks
            if (!username || !password || !confirmPassword || !studentId || !gradeLevel || !strand) {
                showMessage('Please fill in all fields', 'error');
                return;
            }
            
            if (username.length < 3) {
                showMessage('Username must be at least 3 characters long', 'error');
                return;
            }
            
            if (password.length < 6) {
                showMessage('Password must be at least 6 characters long', 'error');
                return;
            }
            
            if (password !== confirmPassword) {
                showMessage('Passwords do not match', 'error');
                return;
            }
            
            // If validation passes, submit the form
            this.submit();
        });
        
        // Real-time password confirmation check
        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            passwordInput.addEventListener('input', checkPasswordMatch);
        }
    }
    
    function checkPasswordMatch() {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        const messageElement = confirmPasswordInput.parentNode.querySelector('.password-feedback');
        
        if (!messageElement) {
            const newMessageElement = document.createElement('div');
            newMessageElement.className = 'password-feedback';
            confirmPasswordInput.parentNode.appendChild(newMessageElement);
        }
        
        const feedbackElement = confirmPasswordInput.parentNode.querySelector('.password-feedback');
        
        if (confirmPassword === '') {
            feedbackElement.textContent = '';
            feedbackElement.className = 'password-feedback';
        } else if (password === confirmPassword) {
            feedbackElement.textContent = 'Passwords match';
            feedbackElement.className = 'password-feedback password-match';
        } else {
            feedbackElement.textContent = 'Passwords do not match';
            feedbackElement.className = 'password-feedback password-mismatch';
        }
    }
    
    function showMessage(message, type) {
        // Remove existing messages
        const existingMessage = document.querySelector('.error-message, .success-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        // Create new message
        const messageDiv = document.createElement('div');
        messageDiv.className = type === 'error' ? 'error-message' : 'success-message';
        messageDiv.textContent = message;
        
        // Insert message at the top of the form
        const form = document.querySelector('.login-form');
        form.insertBefore(messageDiv, form.firstChild);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 5000);
    }
    
    // Real-time validation for all fields
    const inputs = document.querySelectorAll('input, select');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
    });
    
    function validateField(field) {
        const value = field.value.trim();
        
        switch(field.id) {
            case 'username':
                if (value.length < 3 && value.length > 0) {
                    field.style.borderColor = '#dc2626';
                } else {
                    field.style.borderColor = value ? '#16a34a' : '#cbd5e1';
                }
                break;
            case 'password':
                if (value.length < 6 && value.length > 0) {
                    field.style.borderColor = '#dc2626';
                } else {
                    field.style.borderColor = value ? '#16a34a' : '#cbd5e1';
                }
                break;
            case 'student_id':
                if (!value) {
                    field.style.borderColor = '#cbd5e1';
                } else {
                    field.style.borderColor = '#16a34a';
                }
                break;
        }
    }
});