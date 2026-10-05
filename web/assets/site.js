// The hosted documentation has no Studio backend. Keep local Studio links live.
if (!location.pathname.includes('/appyhp/studio/')) {
    const install = new URL('../install.html', document.currentScript.src).href;
    document.querySelectorAll('a[href="/appyhp/studio"]').forEach(function (link) {
        link.href = install;
        link.textContent = 'Install AppyHP';
    });
}
