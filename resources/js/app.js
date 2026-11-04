import pullToRefresh from './pull-to-refresh';
import postComposer from './post-composer';
import profileImageCropper from './profile-image-cropper';
import coffeeMap from './coffee-map';

function registerPostComposer() {
    window.Alpine?.data('postComposer', postComposer);
    window.Alpine?.data('pullToRefresh', pullToRefresh);
    window.Alpine?.data('profileImageCropper', profileImageCropper);
    window.Alpine?.data('coffeeMap', coffeeMap);
}
document.addEventListener('alpine:init', registerPostComposer, { once: true });
if (window.Alpine) registerPostComposer();

function syncCoffeeNavigation() {
    const path = window.location.pathname.replace(/\/+$/, '') || '/';
    const section = path === '/map' ? 'map'
        : path === '/experiences/create' ? 'post'
        : path === '/budget' ? 'budget'
        : path === '/profile' || path.startsWith('/profile/') || path.startsWith('/settings') ? 'profile'
        : 'discover';

    document.querySelectorAll('[data-coffee-navigation-link]').forEach(link => {
        const active = link.dataset.coffeeNavigationLink === section;
        if (active) link.setAttribute('aria-current', 'page');
        else link.removeAttribute('aria-current');

        link.classList.toggle('text-brand', active);
        link.classList.toggle('text-muted', !active);
        if (link.dataset.navStyle === 'desktop') {
            link.classList.toggle('bg-soft', active);
            link.classList.toggle('font-semibold', active);
        } else {
            link.classList.toggle('font-bold', active);
        }
        const dot = link.querySelector('[data-nav-dot]');
        if (dot) dot.hidden = !active;
    });

    const search = document.querySelector('[data-coffee-search] input[name="search"]');
    if (search) search.value = new URLSearchParams(window.location.search).get('search') || '';
}

document.addEventListener('livewire:navigated', syncCoffeeNavigation);
document.addEventListener('DOMContentLoaded', syncCoffeeNavigation);
if (document.readyState !== 'loading') syncCoffeeNavigation();

document.addEventListener('submit', event => {
    const form = event.target.closest('[data-coffee-search]');
    if (!form || !window.Livewire) return;
    event.preventDefault();
    const url = new URL(form.action);
    const query = new FormData(form).get('search');
    if (query) url.searchParams.set('search', String(query));
    window.Livewire.navigate(url.href);
});
