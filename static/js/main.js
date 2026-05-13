import { Navigation, UI } from './upload/utils.js';
import { ImageHandler } from './upload/handler.js';

// 配置和DOM缓存
let CONFIG = null;
let DOM = null;
let imageHandler = null;
const IMAGE_HISTORY_TYPE = 'image';

// 图片预览状态管理
const PreviewState = {
    images: [],
    currentIndex: 0,
    uploadedUrls: {},
    uploadStatus: {},
    
    addImage(file) {
        this.images.push({ file, preview: null, uploaded: false });
    },
    
    clear() {
        Object.assign(this, { images: [], currentIndex: 0, uploadedUrls: {}, uploadStatus: {} });
    },
    
    setPreview(index, dataUrl) {
        if (this.images[index]) this.images[index].preview = dataUrl;
    },
    
    setUploadedUrl(index, url) {
        this.uploadedUrls[index] = url;
        if (this.images[index]) this.images[index].uploaded = true;
        this.uploadStatus[index] = 'completed';
    },
    
    setUploadStatus(index, status) {
        this.uploadStatus[index] = status;
    },
    
    getUploadStatus(index) {
        return this.uploadStatus[index] || 'pending';
    },
    
    getUploadedUrl(index) {
        return this.uploadedUrls[index];
    },
    
    getCurrentUrl() {
        return this.uploadedUrls[this.currentIndex];
    },
    
    next() {
        return this.move(1);
    },
    
    prev() {
        return this.move(-1);
    },
    
    move(direction) {
        if (this.images.length === 0) return false;
        
        let newIndex = this.currentIndex + direction;
        
        // 循环滚动逻辑
        if (newIndex >= this.images.length) {
            newIndex = 0; // 到达末尾，跳转到开头
        } else if (newIndex < 0) {
            newIndex = this.images.length - 1; // 到达开头，跳转到末尾
        }
        
        this.currentIndex = newIndex;
        return true;
    },
    
    goTo(index) {
        if (index >= 0 && index < this.images.length) {
            this.currentIndex = index;
            return true;
        }
        return false;
    },
    
    getAllUploadedUrls() {
        return Object.values(this.uploadedUrls);
    }
};

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', () => {
    const scriptTag = document.querySelector('script[src*="static/js/main.js"]');
    CONFIG = {
        allowedTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
        maxFileSize: parseInt(scriptTag?.dataset?.maxFileSize) || 0
    };
    
    // DOM元素缓存
    DOM = {
        imageInput: document.getElementById('imageInput'),
        imagePreview: document.getElementById('imagePreview'),
        imagePreviewContainer: document.getElementById('imagePreviewContainer'),
        prevButton: document.getElementById('prevButton'),
        nextButton: document.getElementById('nextButton'),
        imageCounter: document.getElementById('imageCounter'),
        qualityInput: document.getElementById('qualityInput'),
        qualityOutput: document.getElementById('qualityOutput'),
        progressBar: document.getElementById('progressBar'),
        progressContainer: document.getElementById('progressContainer'),
        originalWidth: document.getElementById('originalWidth'),
        originalHeight: document.getElementById('originalHeight'),
        originalSize: document.getElementById('originalSize'),
        compressedWidth: document.getElementById('compressedWidth'),
        compressedHeight: document.getElementById('compressedHeight'),
        compressedSize: document.getElementById('compressedSize'),
        deleteImageButton: document.getElementById('deleteImageButton'),
        imageUploadBox: document.getElementById('imageUploadBox'),
        pasteOrUrlInput: document.getElementById('pasteOrUrlInput'),
        thumbnailStrip: document.getElementById('thumbnailStrip'),
        thumbnailScrollContainer: document.getElementById('thumbnailScrollContainer'),
        uploadContainer: document.querySelector('.upload-container'),
        historySection: document.getElementById('imageHistorySection'),
        historyList: document.getElementById('imageHistoryList'),
        historyEmpty: document.getElementById('imageHistoryEmpty'),
        clearHistoryBtn: document.getElementById('clearImageHistoryBtn'),
        sessionCount: document.getElementById('imageSessionCount'),
        dailyCount: document.getElementById('imageDailyCount'),
        totalCount: document.getElementById('imageTotalCount')
    };
    
    initialize();
});

// 初始化
function initialize() {
    imageHandler = new ImageHandler(CONFIG, DOM, PreviewState);
    
    imageHandler.setupEventListeners();
    setupNavigationListeners();
    setupHistoryListeners();
    loadSavedQuality();
    UI.updateCopyButtonsState(false);
    UI.updateLinkDisplays(null); // 初始化显示示例内容
    renderImageStats();
    renderImageHistory();
}

// 设置导航监听器
function setupNavigationListeners() {
    const prev = () => imageHandler.prevImage();
    const next = () => imageHandler.nextImage();
    const clear = () => {
        UI.clearImageInfo(DOM);
        imageHandler.cleanup();
        UI.showNotification('圖片資訊已清除');
    };
    
    Navigation.setupKeyboard(prev, next, clear, () => PreviewState.images.length > 0);
    Navigation.setupWheel(DOM.uploadContainer, prev, next, () => PreviewState.images.length > 1);
    Navigation.setupTouch(DOM.imagePreviewContainer, prev, next, () => PreviewState.images.length > 1);
    Navigation.setupButtons(DOM.prevButton, DOM.nextButton, prev, next);
}

// 加载保存的压缩率
function loadSavedQuality() {
    const savedQuality = localStorage.getItem('imageQuality');
    if (savedQuality) {
        DOM.qualityInput.value = savedQuality;
        DOM.qualityOutput.textContent = savedQuality;
    }
}

function setupHistoryListeners() {
    DOM.clearHistoryBtn?.addEventListener('click', () => {
        window.UploadHistory.clear(IMAGE_HISTORY_TYPE);
        renderImageHistory();
        UI.showNotification('已清除圖片最近上傳紀錄');
    });

    window.addEventListener('image-upload-history-updated', renderImageHistory);
    window.addEventListener('image-upload-stats-updated', renderImageStats);
}

function renderImageStats() {
    const summary = window.UploadStats.getSummary(IMAGE_HISTORY_TYPE);
    const sessionTotal = PreviewState.images.length;

    DOM.sessionCount.textContent = `${UI.uploadedCount} / ${sessionTotal}`;
    DOM.dailyCount.textContent = String(summary.today || 0);
    DOM.totalCount.textContent = String(summary.total || 0);
}

function renderImageHistory() {
    const entries = window.UploadHistory.load(IMAGE_HISTORY_TYPE);

    DOM.historyList.innerHTML = '';

    if (entries.length === 0) {
        DOM.historySection.hidden = true;
        DOM.historyEmpty.style.display = 'block';
        return;
    }

    DOM.historySection.hidden = false;
    DOM.historyEmpty.style.display = 'none';

    entries.forEach((entry) => {
        const item = document.createElement('article');
        item.className = 'image-history-item';

        const thumb = document.createElement('img');
        thumb.className = 'image-history-thumb';
        thumb.src = entry.previewUrl || entry.url;
        thumb.alt = entry.filename || 'recent image';

        const title = document.createElement('div');
        title.className = 'image-history-title';
        title.textContent = entry.filename || '未命名圖片';

        const link = document.createElement('a');
        link.className = 'image-history-link';
        link.href = entry.url;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.textContent = entry.url;

        const meta = document.createElement('div');
        meta.className = 'image-history-meta';
        meta.textContent = formatTimestamp(entry.createdAt);

        const actions = document.createElement('div');
        actions.className = 'image-history-actions';

        const copyBtn = document.createElement('button');
        copyBtn.type = 'button';
        copyBtn.className = 'image-history-btn';
        copyBtn.textContent = '複製';
        copyBtn.addEventListener('click', () => copyHistoryUrl(entry.url));

        const openLink = document.createElement('a');
        openLink.className = 'image-history-open';
        openLink.href = entry.url;
        openLink.target = '_blank';
        openLink.rel = 'noopener noreferrer';
        openLink.textContent = '開啟';

        actions.appendChild(copyBtn);
        actions.appendChild(openLink);

        item.appendChild(thumb);
        item.appendChild(title);
        item.appendChild(link);
        item.appendChild(meta);
        item.appendChild(actions);
        DOM.historyList.appendChild(item);
    });
}

function formatTimestamp(createdAt) {
    const date = new Date(createdAt);
    if (Number.isNaN(date.getTime())) {
        return '時間未知';
    }
    return date.toLocaleString('zh-Hant-TW', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

async function copyHistoryUrl(url) {
    try {
        await navigator.clipboard.writeText(url);
        UI.showNotification('已複製圖片連結');
    } catch (error) {
        console.error('複製失敗:', error);
        UI.showNotification('複製失敗，請再試一次', 'error');
    }
}
