/**
 * Media Index - Delete Modal and Copy URL Functionality
 * Handles media deletion confirmation and URL copying
 */

let currentFileId = null;
let currentFileName = '';

/**
 * Open delete confirmation modal
 */
window.openDeleteModal = function (fileId, fileName) {
    currentFileId = fileId;
    currentFileName = fileName;

    const modal = document.getElementById('deleteModal');
    if (!modal) {
        console.error('[Media Index] deleteModal not found');
        return;
    }

    const messageElement = modal.querySelector('.modal-message p');
    if (messageElement) {
        const deleteMessage = modal.dataset.deleteMessage || '「{fileName}」を削除しますか？この操作は取り消せません。';
        messageElement.textContent = deleteMessage.replace('{fileName}', fileName);
    }

    const form = document.getElementById('deleteForm');
    if (form) {
        const baseUrl = form.dataset.baseUrl || '/admin/media/delete';
        form.action = `${baseUrl}/${fileId}`;
    }

    if (typeof window.openModal === 'function') {
        window.openModal('deleteModal');
    } else {
        console.error('[Media Index] openModal function not found');
    }
};

/**
 * Copy media URL to clipboard
 */
window.copyMediaUrl = function (url, button) {
    const originalIcon = button.innerHTML;

    navigator.clipboard.writeText(url).then(function () {
        button.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i>';
        button.classList.remove('action-btn--copy');
        button.classList.add('action-btn--success');

        setTimeout(function () {
            button.innerHTML = originalIcon;
            button.classList.remove('action-btn--success');
            button.classList.add('action-btn--copy');
        }, 2000);
    }).catch(function (err) {
        try {
            const textArea = document.createElement('textarea');
            textArea.value = url;
            textArea.style.position = 'fixed';
            textArea.style.left = '-9999px';
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);

            button.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i>';
            setTimeout(function () {
                button.innerHTML = originalIcon;
            }, 2000);
        } catch (e) {
            console.error('[Media Index] Copy failed:', e);
            button.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i>';
            setTimeout(function () {
                button.innerHTML = originalIcon;
            }, 2000);
        }
    });
};

console.log('[Media Index] Script loaded');
