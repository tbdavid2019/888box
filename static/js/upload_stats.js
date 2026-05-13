(function(window) {
    const STORAGE_KEYS = {
        image: '888box.stats.image',
        video: '888box.stats.video',
        file: '888box.stats.file'
    };

    function getStorageKey(type) {
        return STORAGE_KEYS[type] || '';
    }

    function getTodayKey() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function normalizeStats(payload) {
        const stats = payload && typeof payload === 'object' ? payload : {};
        const daily = stats.daily && typeof stats.daily === 'object' && !Array.isArray(stats.daily)
            ? stats.daily
            : {};

        const normalizedDaily = {};
        Object.keys(daily).forEach((dateKey) => {
            const value = Number(daily[dateKey]);
            if (Number.isFinite(value) && value > 0) {
                normalizedDaily[dateKey] = Math.floor(value);
            }
        });

        const total = Number(stats.total);

        return {
            daily: normalizedDaily,
            total: Number.isFinite(total) && total > 0 ? Math.floor(total) : 0
        };
    }

    function load(type) {
        const key = getStorageKey(type);
        if (!key) {
            return normalizeStats();
        }

        try {
            return normalizeStats(JSON.parse(window.localStorage.getItem(key) || '{}'));
        } catch (error) {
            return normalizeStats();
        }
    }

    function save(type, payload) {
        const key = getStorageKey(type);
        const normalized = normalizeStats(payload);
        if (!key) {
            return normalized;
        }

        window.localStorage.setItem(key, JSON.stringify(normalized));
        return normalized;
    }

    function increment(type) {
        const stats = load(type);
        const todayKey = getTodayKey();
        stats.daily[todayKey] = (stats.daily[todayKey] || 0) + 1;
        stats.total += 1;
        return save(type, stats);
    }

    function getSummary(type) {
        const stats = load(type);
        const todayKey = getTodayKey();

        return {
            daily: stats.daily,
            total: stats.total,
            todayKey: todayKey,
            today: stats.daily[todayKey] || 0
        };
    }

    function clear(type) {
        const key = getStorageKey(type);
        if (key) {
            window.localStorage.removeItem(key);
        }
    }

    window.UploadStats = {
        keys: STORAGE_KEYS,
        load: load,
        save: save,
        increment: increment,
        getSummary: getSummary,
        clear: clear,
        getTodayKey: getTodayKey
    };
})(window);
