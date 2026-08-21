const shell = document.getElementById('adminShell');
const toggle = document.getElementById('sidebarToggle');
const shade = document.getElementById('mobileShade');

if (shell && toggle) {
    const desktopQuery = window.matchMedia('(min-width: 768px)');

    if (localStorage.getItem('forest-sidebar') === 'collapsed' && desktopQuery.matches) {
        shell.classList.add('is-collapsed');
    }

    const closeMobile = () => shell.classList.remove('mobile-open');

    toggle.addEventListener('click', () => {
        if (desktopQuery.matches) {
            shell.classList.toggle('is-collapsed');
            localStorage.setItem(
                'forest-sidebar',
                shell.classList.contains('is-collapsed') ? 'collapsed' : 'expanded',
            );
            return;
        }

        shell.classList.toggle('mobile-open');
    });

    shade?.addEventListener('click', closeMobile);

    desktopQuery.addEventListener('change', (event) => {
        shell.classList.remove('mobile-open');

        if (!event.matches) {
            shell.classList.remove('is-collapsed');
        } else if (localStorage.getItem('forest-sidebar') === 'collapsed') {
            shell.classList.add('is-collapsed');
        }
    });
}
