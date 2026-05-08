(function(window) {
    const HISTORY_LIMIT = 10;
    const STORAGE_KEYS = {
        image: '888box.history.image',
        video: '888box.history.video',
        file: '888box.history.file'
    };

    function getStorageKey(type) {
        return STORAGE_KEYS[type] || '';
    }

    function safeParse(raw) {
        if (!raw) {
            return [];
        }

        try {
            const parsed = JSON.parse(raw);
            return Array.isArray(parsed) ? parsed : [];
        } catch (error) {
            return [];
        }
    }

    function basename(value, fallback) {
        if (!value) {
            return fallback;
        }

        try {
            const cleaned = value.split('?')[0].split('#')[0];
            const name = cleaned.split('/').pop();
            return name || fallback;
        } catch (error) {
            return fallback;
        }
    }

    function normalizeTimestamp(createdAt) {
        if (!createdAt) {
            return new Date().toISOString();
        }

        const date = new Date(createdAt);
        return Number.isNaN(date.getTime()) ? new Date().toISOString() : date.toISOString();
    }

    function normalizeImageEntry(payload) {
        if (!payload || !payload.url) {
            return null;
        }

        return {
            url: payload.url,
            previewUrl: payload.previewUrl || payload.url,
            filename: payload.filename || basename(payload.url, 'image'),
            createdAt: normalizeTimestamp(payload.createdAt)
        };
    }

    function normalizeVideoEntry(payload) {
        if (!payload || !payload.url) {
            return null;
        }

        const filename = payload.filename || basename(payload.url, 'video');

        return {
            url: payload.url,
            thumbnailUrl: payload.thumbnailUrl || '',
            title: (payload.title || '').trim() || filename,
            filename: filename,
            createdAt: normalizeTimestamp(payload.createdAt)
        };
    }

    function normalizeFileEntry(payload) {
        const primaryUrl = payload ? (payload.shareUrl || payload.url || '') : '';
        if (!primaryUrl) {
            return null;
        }

        const filename = payload.filename || basename(primaryUrl, 'file');

        return {
            url: primaryUrl,
            shareUrl: payload.shareUrl || '',
            rawUrl: payload.url || primaryUrl,
            title: (payload.title || '').trim() || filename,
            filename: filename,
            mimeType: payload.mimeType || '',
            createdAt: normalizeTimestamp(payload.createdAt)
        };
    }

    function normalizeEntry(type, payload) {
        if (type === 'image') {
            return normalizeImageEntry(payload);
        }
        if (type === 'video') {
            return normalizeVideoEntry(payload);
        }
        if (type === 'file') {
            return normalizeFileEntry(payload);
        }

        return null;
    }

    function load(type) {
        const key = getStorageKey(type);
        if (!key) {
            return [];
        }

        return safeParse(window.localStorage.getItem(key));
    }

    function save(type, items) {
        const key = getStorageKey(type);
        if (!key) {
            return [];
        }

        const trimmed = Array.isArray(items) ? items.slice(0, HISTORY_LIMIT) : [];
        window.localStorage.setItem(key, JSON.stringify(trimmed));
        return trimmed;
    }

    function add(type, payload) {
        const entry = normalizeEntry(type, payload);
        if (!entry) {
            return load(type);
        }

        const existingItems = load(type).filter(function(item) {
            return item && item.url !== entry.url;
        });

        existingItems.unshift(entry);
        return save(type, existingItems);
    }

    function clear(type) {
        const key = getStorageKey(type);
        if (key) {
            window.localStorage.removeItem(key);
        }
    }

    window.UploadHistory = {
        limit: HISTORY_LIMIT,
        keys: STORAGE_KEYS,
        load: load,
        save: save,
        add: add,
        clear: clear,
        normalizeEntry: normalizeEntry
    };
})(window);
