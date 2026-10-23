import './bootstrap';

/*
 * Admin light / dark switch. The first paint is set by a tiny script in <head>;
 * this only handles the button. The choice stays in this browser (localStorage).
 */
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-theme-toggle]');

    if (button === null) {
        return;
    }

    const root = document.documentElement;
    const dark = ! root.classList.contains('dark');

    root.classList.toggle('dark', dark);

    try {
        localStorage.setItem('lp-theme', dark ? 'dark' : 'light');
    } catch (error) {
        // Private mode: the choice just lasts until the page is closed.
    }

    syncThemeButtons();
});

function syncThemeButtons() {
    const dark = document.documentElement.classList.contains('dark');

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        const label = dark ? button.dataset.labelToLight : button.dataset.labelToDark;

        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);
    });

    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', dark ? '#0b1512' : '#187c60');
}

syncThemeButtons();
