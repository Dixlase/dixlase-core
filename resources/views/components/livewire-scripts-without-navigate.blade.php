{{-- Livewire v4では@livewireScriptsディレクティブは存在しない --}}
{{-- JavaScriptでdata-navigate-once属性を削除 --}}
<script{!! get_csp_nonce_attr() !!}>
(function() {
    // DOMが読み込まれた後、Livewireスクリプトのdata-navigate-once属性を削除
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.tagName === 'SCRIPT' && node.src && node.src.includes('livewire')) {
                    if (node.hasAttribute('data-navigate-once')) {
                        node.removeAttribute('data-navigate-once');
                        console.log('[Livewire] Removed data-navigate-once attribute');
                    }
                }
            });
        });
    });
    
    observer.observe(document.documentElement, {
        childList: true,
        subtree: true
    });
    
    // 既存のLivewireスクリプトからも削除
    document.addEventListener('DOMContentLoaded', function() {
        const scripts = document.querySelectorAll('script[src*="livewire"]');
        scripts.forEach(function(script) {
            if (script.hasAttribute('data-navigate-once')) {
                script.removeAttribute('data-navigate-once');
                console.log('[Livewire] Removed data-navigate-once from existing script');
            }
        });
    });
})();
</script>
