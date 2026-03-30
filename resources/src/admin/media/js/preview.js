/**
 * Media Preview - Clipboard Copy Functionality
 * Handles copying media URL to clipboard
 */

window.copyMediaUrlToClipboard = function (event) {
    const urlInput = document.getElementById('mediaUrl');
    const copyButton = event.currentTarget || event.target.closest('button');

    if (!urlInput || !copyButton) {
        console.error('[Media Preview] mediaUrl input or button not found');
        return;
    }

    const originalText = copyButton.innerHTML;
    const copiedText = copyButton.dataset.copiedText || 'Copied';
    const copyFailedText = copyButton.dataset.copyFailedText || 'Copy failed';

    urlInput.select();
    urlInput.setSelectionRange(0, urlInput.value.length);

    navigator.clipboard.writeText(urlInput.value).then(function () {
        copyButton.innerHTML = '<i class="fas fa-check"></i> ' + copiedText;
        copyButton.classList.remove('bg-green-500', 'hover:bg-green-600', 'dark:bg-green-600', 'dark:hover:bg-green-700');
        copyButton.classList.add('bg-blue-500', 'hover:bg-blue-600', 'dark:bg-blue-600', 'dark:hover:bg-blue-700');

        setTimeout(function () {
            copyButton.innerHTML = originalText;
            copyButton.classList.remove('bg-blue-500', 'hover:bg-blue-600', 'dark:bg-blue-600', 'dark:hover:bg-blue-700');
            copyButton.classList.add('bg-green-500', 'hover:bg-green-600', 'dark:bg-green-600', 'dark:hover:bg-green-700');
        }, 2000);
    }).catch(function (err) {
        try {
            document.execCommand('copy');
            copyButton.innerHTML = '<i class="fas fa-check"></i> ' + copiedText;
            setTimeout(function () {
                copyButton.innerHTML = originalText;
            }, 2000);
        } catch (e) {
            console.error('[Media Preview] Copy failed:', e);
            alert(copyFailedText);
        }
    });
};

console.log('[Media Preview] Script loaded');
