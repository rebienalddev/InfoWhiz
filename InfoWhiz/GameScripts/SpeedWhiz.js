// Scripts/SpeedWhiz.js

document.addEventListener('DOMContentLoaded', function() {
    
    // --- NEW: Jumble Word Function ---
    function jumbleWord(word) {
        // Don't jumble very short words
        if (word.length <= 2) {
            return word;
        }
        
        let jumbledWord;
        let chars = word.split('');
        
        do {
            // Fisher-Yates Shuffle
            for (let i = chars.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [chars[i], chars[j]] = [chars[j], chars[i]]; // Swap
            }
            jumbledWord = chars.join('');
            
            // Keep jumbling if it's the same as the original, 
            // but only for words > 3 chars to avoid infinite loops on words like "eye"
        } while (word.length > 3 && jumbledWord === word); 
        
        return jumbledWord;
    }

    // Game Screens
    const setupScreen = document.getElementById('game-setup');
    const gameScreen = document.getElementById('game-play');
    const gameOverScreen = document.getElementById('game-over');
    
    // Setup Form
    const setupForm = document.getElementById('game-setup-form');
    const wordCountInput = document.getElementById('wordCount');
    const timePerWordInput = document.getElementById('timePerWord');
    const startGameBtn = document.getElementById('start-game-btn');
    const gameLoader = document.getElementById('game-loader');
    const setupError = document.getElementById('game-setup-error');
    
    // Game Play Elements
    const timerDisplay = document.getElementById('timer-display');
    const scoreDisplay = document.getElementById('score-display');
    const definitionDisplay = document.getElementById('definition-display');
    const wordDisplay = document.getElementById('word-display');
    const typingInput = document.getElementById('typing-input');
    
    // Game Over Elements
    const finalScoreDisplay = document.getElementById('final-score-display');
    const playAgainBtn = document.getElementById('play-again-btn');

    // Game State
    let gameData = [];
    let currentWordIndex = 0;
    let score = 0;
    let timePerWord = 15;
    let timeLeft = 0;
    let timerInterval = null;
    let typingTimer = null; // Timer for incorrect input feedback

    // --- 1. SETUP LOGIC ---
    if (setupForm) {
        setupForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Show loader, hide error, disable button
            gameLoader.style.display = 'block';
            setupError.classList.remove('show');
            startGameBtn.disabled = true;
            startGameBtn.textContent = 'Loading Words...';

            const wordCount = wordCountInput.value;
            timePerWord = parseInt(timePerWordInput.value, 10);
            
            try {
                const formData = new FormData();
                formData.append('action', 'get_game_data');
                formData.append('wordCount', wordCount);
                
                const response = await fetch('', { // Post to the same file (SpeedWhiz.php)
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    gameData = data.words;
                    if (gameData.length === 0) {
                        throw new Error('PDF processed, but no words were extracted.');
                    }
                    // Shuffle the words
                    gameData.sort(() => Math.random() - 0.5);
                    startGame();
                } else {
                    throw new Error(data.error);
                }
                
            } catch (error) {
                setupError.textContent = error.message;
                setupError.classList.add('show');
                gameLoader.style.display = 'none';
                startGameBtn.disabled = false;
                startGameBtn.textContent = 'Start Game';
            }
        });
    }

    // --- 2. GAME LOGIC ---
    function startGame() {
        score = 0;
        currentWordIndex = 0;
        scoreDisplay.textContent = score;
        
        loadNextWord();
        
        // Switch screens
        setupScreen.classList.remove('active');
        gameOverScreen.classList.remove('active');
        gameScreen.classList.add('active');
        
        typingInput.focus();
    }
    
    function loadNextWord() {
        if (currentWordIndex >= gameData.length) {
            endGame();
            return;
        }
        
        // Clear previous word's state
        clearInterval(timerInterval);
        typingInput.value = '';
        typingInput.classList.remove('correct', 'incorrect');
        
        // --- UPDATED: Load and Jumble Word ---
        const currentWordData = gameData[currentWordIndex];
        const correctWord = currentWordData.word;
        const jumbledWord = jumbleWord(correctWord); // Jumble the word
        
        definitionDisplay.textContent = currentWordData.definition;
        wordDisplay.textContent = jumbledWord; // Display the JUMBLED word
        // --- End of Update ---
        
        // Start timer
        timeLeft = timePerWord;
        timerDisplay.textContent = timeLeft;
        timerInterval = setInterval(updateTimer, 1000);
        
        typingInput.focus();
    }
    
    function updateTimer() {
        timeLeft--;
        timerDisplay.textContent = timeLeft;
        
        if (timeLeft <= 0) {
            // Time's up! Move to next word (no score)
            currentWordIndex++;
            loadNextWord();
        }
    }
    
    typingInput.addEventListener('input', () => {
        // The targetWord is the *correct* one from our data, not the jumbled one on screen
        const typedValue = typingInput.value;
        const targetWord = gameData[currentWordIndex].word;
        
        // Clear feedback timer
        clearTimeout(typingTimer);
        typingInput.classList.remove('incorrect');
        
        if (typedValue === targetWord) {
            // --- CORRECT ---
            score++;
            scoreDisplay.textContent = score;
            currentWordIndex++;
            typingInput.classList.add('correct');
            
            // Short delay to show "correct" feedback
            setTimeout(() => {
                loadNextWord();
            }, 300);
            
        } else if (targetWord.startsWith(typedValue)) {
            // --- Partially correct, no feedback ---
            typingInput.classList.remove('correct', 'incorrect');
        } else {
            // --- INCORRECT ---
            typingInput.classList.add('incorrect');
            // Remove "incorrect" class after a moment
            typingTimer = setTimeout(() => {
                typingInput.classList.remove('incorrect');
            }, 500);
        }
    });

    // --- 3. GAME OVER LOGIC ---
    function endGame() {
        clearInterval(timerInterval);
        
        finalScoreDisplay.textContent = `${score} / ${gameData.length}`;
        
        // Switch screens
        gameScreen.classList.remove('active');
        gameOverScreen.classList.add('active');

        // Reset setup form state
        gameLoader.style.display = 'none';
        startGameBtn.disabled = false;
        startGameBtn.textContent = 'Start Game';
    }
    
    playAgainBtn.addEventListener('click', () => {
        gameOverScreen.classList.remove('active');
        setupScreen.classList.add('active');
    });

    
    // --- SIDEBAR & UTILITY FUNCTIONS (from your ChatBot.js) ---
    
    // PDF Upload Logic (slight modification to stay on page)
    const pdfUploadForm = document.getElementById('pdfUploadForm');
    const uploadStatus = document.getElementById('uploadStatus');

    if (pdfUploadForm) {
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
                const response = await fetch('', { // Post to SpeedWhiz.php
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showUploadStatus(data.message, 'success');
                    fileInput.value = '';
                    // Reload the page to update sidebar and enable game
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showUploadStatus(data.error, 'error');
                }
            } catch (error) {
                showUploadStatus('Upload failed. Please try again.', 'error');
                console.error('Upload error:', error);
            }
        });
    }
    
    function showUploadStatus(message, type) {
        uploadStatus.textContent = message;
        uploadStatus.className = 'upload-status ' + type;
    }

    // PDF removal function (from your file)
    window.removePDF = async function() {
        if (confirm('Are you sure you want to remove the current PDF?')) {
            try {
                const formData = new FormData();
                formData.append('remove_pdf', 'true');
                
                const response = await fetch('', { // Post to SpeedWhiz.php
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    location.reload(); // Reload to update sidebar
                } else {
                    throw new Error('Failed to remove PDF');
                }
            } catch (error) {
                console.error('Error removing PDF:', error);
                alert('Failed to remove PDF. Please try again.');
            }
        }
    }

    // Home navigation (from your file)
    window.Home = function() {
        window.location.href = '../Pages/HomePage.php';
    }

    // Mobile menu function (from your file)
    function initMobileMenu() {
        const mobileMenuBtn = document.createElement('button');
        mobileMenuBtn.className = 'mobile-menu-btn';
        mobileMenuBtn.innerHTML = `
            <span></span>
            <span></span>
            <span></span>
        `;
        
        const logo = document.querySelector('.logo');
        logo.insertBefore(mobileMenuBtn, logo.firstChild);
        
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
        
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', () => {
                if (window.innerWidth < 768) {
                    toggleMobileMenu();
                }
            });
        });
        
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                mobileMenuBtn.classList.remove('active');
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }

    initMobileMenu(); // Run the mobile menu init
});