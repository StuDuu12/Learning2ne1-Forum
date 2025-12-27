// Resources Page JavaScript

function openUploadModal() {
    document.getElementById('uploadModal').classList.add('active');
}

function closeUploadModal() {
    document.getElementById('uploadModal').classList.remove('active');
}

// Close modal when clicking outside
document.getElementById('uploadModal')?.addEventListener('click', function (e) {
    if (e.target === this) {
        closeUploadModal();
    }
});

document.getElementById('createCategoryModal')?.addEventListener('click', function (e) {
    if (e.target === this) {
        this.style.display = 'none';
    }
});

// Close modal with ESC key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeUploadModal();
        document.getElementById('createCategoryModal').style.display = 'none';
    }
});
