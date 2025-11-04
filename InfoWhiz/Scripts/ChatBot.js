document.addEventListener('DOMContentLoaded', function() {
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');
    const chatContainer = document.getElementById('chatContainer');
    const typingIndicator = document.getElementById('typingIndicator');
    const sendButton = document.getElementById('sendButton');
    const resetButton = document.getElementById('resetButton');
    const welcomeMessage = document.getElementById('welcomeMessage');
    const pdfUploadForm = document.getElementById('pdfUploadForm');
    const uploadStatus = document.getElementById('uploadStatus');
    
    // Auto-resize textarea
    messageInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    // Handle PDF upload
    pdfUploadForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const fileInput = document.getElementById('pdfFile');
        if (!fileInput.files[0]) {
            showUploadStatus('Please select a PDF file.', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('pdf_upload', fileInput.files[0]);
        
        showUploadStatus('Uploading PDF...', 'loading');
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showUploadStatus(data.message, 'success');
                fileInput.value = '';
                // Reload to show the current PDF
                setTimeout(() => location.reload(), 1000);
            } else {
                showUploadStatus(data.error, 'error');
            }
        } catch (error) {
            showUploadStatus('Upload failed. Please try again.', 'error');
            console.error('Upload error:', error);
        }
    });
    
    function showUploadStatus(message, type) {
        uploadStatus.textContent = message;
        uploadStatus.className = 'upload-status ' + type;
    }
    
    // Handle reset button
    resetButton.addEventListener('click', async function() {
        if (confirm('Are you sure you want to reset the chat? This will clear all messages.')) {
            try {
                const formData = new FormData();
                formData.append('reset', 'true');
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Clear all messages
                    const messages = chatContainer.querySelectorAll('.message');
                    messages.forEach(msg => msg.remove());
                    
                    // Show welcome message again
                    if (welcomeMessage) {
                        welcomeMessage.style.display = 'block';
                    }
                }
            } catch (error) {
                console.error('Error resetting chat:', error);
                alert('Failed to reset chat. Please try again.');
            }
        }
    });
    
    // Handle form submission with AJAX
    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const message = messageInput.value.trim();
        if (!message) return;
        
        // Add user message to chat immediately
        addMessage(message, 'user');
        
        // Clear input and reset height
        messageInput.value = '';
        messageInput.style.height = 'auto';
        
        // Disable send button and show typing indicator
        sendButton.disabled = true;
        typingIndicator.style.display = 'block';
        scrollToBottom();
        
        try {
            // Send message to Gemini AI via PHP backend
            const formData = new FormData();
            formData.append('prompt', message);
            
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Hide typing indicator and add response
                typingIndicator.style.display = 'none';
                addMessage(data.response, 'bot');
            } else {
                throw new Error('API returned error');
            }
        } catch (error) {
            // Handle errors
            typingIndicator.style.display = 'none';
            addMessage("Sorry, I encountered an error. Please try again.", 'bot');
            console.error('Error:', error);
        } finally {
            // Re-enable send button
            sendButton.disabled = false;
            messageInput.focus();
        }
    });
    
    // Add message to chat
    function addMessage(text, sender) {
        // Remove welcome message if it's the first real message
        if (welcomeMessage && welcomeMessage.style.display !== 'none') {
            welcomeMessage.style.display = 'none';
        }
        
        const messageElement = document.createElement('div');
        messageElement.classList.add('message');
        messageElement.classList.add(sender === 'user' ? 'user-message' : 'bot-message');
        messageElement.textContent = text;
        
        chatContainer.appendChild(messageElement);
        scrollToBottom();
    }
    
    // Scroll to bottom of chat
    function scrollToBottom() {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
    
    // Insert suggestion into input field
    window.insertSuggestion = function(text) {
        messageInput.value = text;
        messageInput.focus();
        messageInput.style.height = 'auto';
        messageInput.style.height = (messageInput.scrollHeight) + 'px';
    };
    
    // Allow sending with Enter key (but allow Shift+Enter for new line)
    messageInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            chatForm.dispatchEvent(new Event('submit'));
        }
    });
    
    // Focus input on load
    messageInput.focus();
});

// Fixed PDF removal function
async function removePDF() {
    if (confirm('Are you sure you want to remove the current PDF?')) {
        try {
            const formData = new FormData();
            formData.append('remove_pdf', 'true');
            
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                // Reload the page to reflect the changes
                location.reload();
            } else {
                throw new Error('Failed to remove PDF');
            }
        } catch (error) {
            console.error('Error removing PDF:', error);
            alert('Failed to remove PDF. Please try again.');
        }
    }
}

function Home() {
    window.location.href = '../Pages/HomePage.php';
}

// Mobile menu functionality
function initMobileMenu() {
    // Create mobile menu button
    const mobileMenuBtn = document.createElement('button');
    mobileMenuBtn.className = 'mobile-menu-btn';
    mobileMenuBtn.innerHTML = `
        <span></span>
        <span></span>
        <span></span>
    `;
    
    // Insert mobile menu button at the beginning of logo
    const logo = document.querySelector('.logo');
    logo.insertBefore(mobileMenuBtn, logo.firstChild);
    
    // Create sidebar overlay
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
    
    const sidebar = document.querySelector('.sidebar');
    
    function toggleMobileMenu() {
        mobileMenuBtn.classList.toggle('active');
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
    }
    
    mobileMenuBtn.addEventListener('click', toggleMobileMenu);
    overlay.addEventListener('click', toggleMobileMenu);
    
    // Close mobile menu when clicking on nav items
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', () => {
            if (window.innerWidth < 768) {
                toggleMobileMenu();
            }
        });
    });
    
    // Close menu on window resize
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
            mobileMenuBtn.classList.remove('active');
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
}

// Initialize mobile menu when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initMobileMenu();
});