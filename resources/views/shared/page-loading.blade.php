<style>
html { background: #fff; }
html.page-loading body { visibility: hidden; }
html.page-loading::before { content: ''; position: fixed; inset: 0; z-index: 99998; background: #fff; }
html.page-loading::after { content: ''; position: fixed; top: 0; left: 0; width: 100%; height: 3px; z-index: 99999; background: linear-gradient(90deg, #4f46e5, #818cf8, #06b6d4, #4f46e5); background-size: 200% 100%; animation: page-loading-flow 1.2s linear infinite; }
@keyframes page-loading-flow { to { background-position: -200% 0; } }
@media (prefers-reduced-motion: reduce) { html.page-loading::after { animation: none; } }
</style>
<script>
(() => {
    const root = document.documentElement;
    root.classList.add('page-loading');
    let loaded = document.readyState === 'complete';
    let initialized = false;
    let revealed = false;
    const reveal = () => {
        if (revealed || !loaded || !initialized) return;
        revealed = true;
        requestAnimationFrame(() => requestAnimationFrame(() => root.classList.remove('page-loading')));
    };
    window.addEventListener('load', () => { loaded = true; reveal(); }, {once: true});
    window.addEventListener('adapt:ready', () => { initialized = true; reveal(); }, {once: true});
    window.addEventListener('pageshow', event => { if (event.persisted) root.classList.remove('page-loading'); });
    // Keep a failed asset or initialization error from leaving the application inaccessible.
    setTimeout(() => { if (!revealed) root.classList.remove('page-loading'); }, 15000);
})();
</script>
