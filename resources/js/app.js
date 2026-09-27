import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

const STORAGE_KEY = 'santa.groups';

// Groups this browser organizes, so the landing page can list them. Best effort only.
window.santaGroups = {
    all() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY)) ?? [];
        } catch {
            return [];
        }
    },
    remember(group) {
        try {
            const others = this.all().filter((g) => g.slug !== group.slug);
            localStorage.setItem(STORAGE_KEY, JSON.stringify([group, ...others]));
        } catch {
            // Storage unavailable (private mode). The admin link still works.
        }
    },
    forget(slug) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(this.all().filter((g) => g.slug !== slug)));
        } catch {}
    },
};

// Copy or share a link. Returns a label for the button to show briefly.
window.shareLink = async (url, title) => {
    if (navigator.share && matchMedia('(pointer: coarse)').matches) {
        try {
            await navigator.share({ title, url });
            return 'Shared';
        } catch {
            // Cancelled; fall through to copy.
        }
    }
    await navigator.clipboard.writeText(url);
    return 'Copied';
};

Alpine.plugin(focus);
window.Alpine = Alpine;
Alpine.start();
