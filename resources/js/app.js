import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

const STORAGE_KEY = 'giver.groups';

// Groups this browser organizes, so the landing page can list them. Best effort only.
window.giverGroups = {
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

// navigator.clipboard only exists on https/localhost, so fall back to a hidden textarea on plain http.
window.copyText = async (text) => {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return true;
        }
    } catch {
        // Permission denied; try the legacy path.
    }

    const area = document.createElement('textarea');
    area.value = text;
    area.setAttribute('readonly', '');
    area.style.cssText = 'position:fixed;top:0;left:0;opacity:0';
    document.body.appendChild(area);
    area.select();
    area.setSelectionRange(0, text.length);
    try {
        return document.execCommand('copy');
    } catch {
        return false;
    } finally {
        area.remove();
    }
};

// Copy a link. Returns a label for the button to show briefly.
window.copyLink = async (url) => (await window.copyText(url)) ? 'Copied' : 'Press ⌘/Ctrl+C';

// Copy or share a link. Returns a label for the button to show briefly.
window.shareLink = async (url, title) => {
    if (navigator.share && window.isSecureContext && matchMedia('(pointer: coarse)').matches) {
        try {
            await navigator.share({ title, url });
            return 'Shared';
        } catch {
            // Cancelled; fall through to copy.
        }
    }
    return window.copyLink(url);
};

Alpine.plugin(focus);
window.Alpine = Alpine;
Alpine.start();
