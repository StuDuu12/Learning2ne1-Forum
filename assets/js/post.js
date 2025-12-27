// Edit modal functions
function openEditModal() {
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function (event) {
    const modal = document.getElementById('editModal');
    if (event.target === modal) {
        closeEditModal();
    }
};

function showReplyForm(commentId, isNestedReply = false, replyToName = '') {
    const form = document.getElementById('reply-form-' + commentId);
    const isVisible = form.style.display === 'block';

    // Hide all other reply forms
    document.querySelectorAll('[id^="reply-form-"]').forEach((f) => (f.style.display = 'none'));

    // Toggle current form
    form.style.display = isVisible ? 'none' : 'block';

    // Focus textarea if showing
    if (!isVisible) {
        const textarea = form.querySelector('textarea');
        if (textarea) {
            textarea.focus();
            // Add mention if nested reply
            if (isNestedReply && replyToName) {
                textarea.value = '@' + replyToName + ' ';
            }
        }
    }
}

// Close reply form when clicking outside
document.addEventListener('click', function (e) {
    if (!e.target.closest('.btn-reply') && !e.target.closest('[id^="reply-form-"]')) {
        document.querySelectorAll('[id^="reply-form-"]').forEach((f) => (f.style.display = 'none'));
    }
});
