// Create Post Page JavaScript

let selectedFiles = [];

// Handle file input change
document.getElementById('file-input')?.addEventListener('change', function (e) {
    const files = Array.from(e.target.files);
    selectedFiles = [...selectedFiles, ...files];
    displayPreviews();
});

// Display file previews
function displayPreviews() {
    const previewContainer = document.getElementById('image-preview');
    if (!previewContainer) return;

    previewContainer.innerHTML = '';

    selectedFiles.forEach((file, index) => {
        const previewItem = document.createElement('div');
        previewItem.className = 'preview-item';

        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            previewItem.appendChild(img);
        } else {
            const fileInfo = document.createElement('div');
            fileInfo.className = 'file-info';
            fileInfo.innerHTML = `
                <div style="font-size: 2rem;">📄</div>
                <div style="font-size: 0.85rem; text-align: center; word-break: break-all;">${file.name}</div>
            `;
            previewItem.appendChild(fileInfo);
        }

        const removeBtn = document.createElement('button');
        removeBtn.className = 'remove-btn';
        removeBtn.innerHTML = '×';
        removeBtn.type = 'button';
        removeBtn.onclick = () => removeFile(index);
        previewItem.appendChild(removeBtn);

        previewContainer.appendChild(previewItem);
    });

    // Update file input
    updateFileInput();
}

// Remove file from selection
function removeFile(index) {
    selectedFiles.splice(index, 1);
    displayPreviews();
}

// Update file input with current files
function updateFileInput() {
    const fileInput = document.getElementById('file-input');
    if (!fileInput) return;

    const dataTransfer = new DataTransfer();
    selectedFiles.forEach((file) => dataTransfer.items.add(file));
    fileInput.files = dataTransfer.files;
}

// Add poll option
function addPollOption() {
    const container = document.getElementById('poll-options');
    if (!container) return;

    const count = container.children.length + 1;
    const input = document.createElement('input');
    input.type = 'text';
    input.name = 'poll_options[]';
    input.placeholder = 'Lựa chọn ' + count;
    input.className = 'form-control';
    input.style.padding = '0.75rem';
    input.style.border = '2px solid var(--bg-grey)';
    input.style.borderRadius = '8px';
    container.appendChild(input);
}
