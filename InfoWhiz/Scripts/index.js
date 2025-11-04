document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const studentId = document.getElementById('student_id').value.trim();
            const gradeLevel = document.getElementById('grade_level').value;
            const strand = document.getElementById('strand').value.trim();
            
            if (!username || !password || !studentId || !gradeLevel || !strand) {
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
            
            this.submit();
        });
    }
    
    function showMessage(message, type) {
        const existingMessage = document.querySelector('.error-message, .success-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        const messageDiv = document.createElement('div');
        messageDiv.className = type === 'error' ? 'error-message' : 'success-message';
        messageDiv.textContent = message;
        
        const form = document.querySelector('.login-form');
        form.insertBefore(messageDiv, form.firstChild);
        
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }
    
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
                if (value.length < 3) {
                    field.style.borderColor = 'red';
                } else {
                    field.style.borderColor = '#ddd';
                }
                break;
            case 'password':
                if (value.length < 6) {
                    field.style.borderColor = 'red';
                } else {
                    field.style.borderColor = '#ddd';
                }
                break;
            case 'student_id':
                if (!value) {
                    field.style.borderColor = 'red';
                } else {
                    field.style.borderColor = '#ddd';
                }
                break;
        }
    }
});