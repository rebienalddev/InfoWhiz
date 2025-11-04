document.addEventListener('DOMContentLoaded', () => {

    const libraryContainer = document.getElementById('pdf-library-container');

    libraryContainer.addEventListener('click', (e) => {

        if (e.target.classList.contains('delete')) {
            handleDelete(e.target);
        }
    });

    async function handleDelete(deleteButton) {
        const pdfItem = deleteButton.closest('.pdf-item');
        
        const filename = pdfItem.dataset.filename;

        if (!filename) {
            alert('Error: Could not find filename.');
            return;
        }

        if (!confirm(`Are you sure you want to delete "${filename}"?`)) {
            return;
        }

        const formData = new FormData();
        formData.append('delete_file', filename);

        try {
            const response = await fetch('PDFLibrary.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                pdfItem.style.transition = 'opacity 0.5s, transform 0.5s';
                pdfItem.style.opacity = '0';
                pdfItem.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    pdfItem.remove();
                    if (libraryContainer.children.length === 0) {
                        libraryContainer.innerHTML = `
                            <div class="library-message">
                                No PDF files have been uploaded yet.
                            </div>
                        `;
                    }
                }, 500);
            } else {
                alert('Error: ' + data.error);
            }

        } catch (error) {
            console.error('Deletion error:', error);
            alert('An error occurred. Please check the console and try again.');
        }
    }
});