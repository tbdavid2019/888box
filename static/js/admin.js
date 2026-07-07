// 配置和DOM缓存
let DOM = null;
let lazyLoadInstance = null;
let isComponentsInitialized = false;

// LazyLoad 配置（复用）
const LAZYLOAD_CONFIG = {
    elements_selector: ".lazy",
    threshold: 100,
    callback_loaded: (img) => {
        img.classList.add('loaded');
        img.parentElement.querySelector('.image-placeholder')?.remove();
    },
    callback_error: (img) => {
        img.parentElement.classList.add('load-error');
        img.parentElement.querySelector('.image-placeholder')?.remove();
    }
};

// 多选状态管理
const MultiSelectState = {
    isActive: false,
    selectedItems: new Set(),
    
    toggle() {
        this.isActive = !this.isActive;
        if (!this.isActive) this.clear();
    },
    
    clear() {
        this.selectedItems.clear();
    },
    
    add(id) {
        this.selectedItems.add(id);
    },
    
    remove(id) {
        this.selectedItems.delete(id);
    },
    
    has(id) {
        return this.selectedItems.has(id);
    },
    
    getAll() {
        return Array.from(this.selectedItems);
    },
    
    count() {
        return this.selectedItems.size;
    }
};

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', () => {
    DOM = {
        gallery: document.getElementById('gallery'),
        pagination: document.getElementById('pagination'),
        pageDisplay: document.getElementById('current-total-pages'),
        scrollTopBtn: document.querySelector('#scroll-to-top'),
        rightside: document.querySelector('.rightside')
    };
    
    initialize();
});

// 初始化
function initialize() {
    initComponents();
    setupEventHandlers();
    setupPageInput();
}

// API 工具类
const API = {
    async sendDeleteRequest(id, path) {
        const response = await fetch('/config/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${id}&path=${encodeURIComponent(path)}`
        });
        const data = await response.json();
        if (data.result !== 'success') throw new Error();
        return true;
    },

    async deleteImages(ids, paths) {
        if (!Array.isArray(ids)) {
            ids = [ids];
            paths = [paths];
        }
        
        const results = await Promise.allSettled(
            ids.map((id, i) => this.sendDeleteRequest(id, paths[i]))
        );
        
        const errors = results.filter(r => r.status === 'rejected').length;
        
        const currentPage = parseInt(DOM.pageDisplay.textContent.split('/')[0]);
        const pageData = await this.loadPage(currentPage);
        
        if (pageData.success) updatePageContent(pageData);
        
        if (errors > 0) {
            UI.showNotification(`刪除失敗 ${errors} 張圖片`, 'error');
            throw new Error();
        }
        
        UI.showNotification(ids.length > 1 ? `成功刪除 ${ids.length} 張圖片` : '刪除成功');
        return true;
    },

    async loadPage(page) {
        const response = await fetch(`/admin/index.php?page=${page}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        if (!data.success) throw new Error();
        return data;
    }
};

// UI 工具类
const UI = {
    notificationTimer: null,
    
    showNotification(message, type = 'success') {
        // 清理旧通知，避免累积
        const oldNotification = document.querySelector('.msg');
        if (oldNotification) {
            oldNotification.remove();
            if (this.notificationTimer) {
                clearTimeout(this.notificationTimer);
                this.notificationTimer = null;
            }
        }
        
        const notification = document.createElement('div');
        notification.className = `msg ${type === 'error' ? 'msg-red' : 'msg-green'}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        this.notificationTimer = setTimeout(() => {
            notification.classList.add('msg-right');
            setTimeout(() => {
                notification.remove();
                this.notificationTimer = null;
            }, 800);
        }, 1500);
    },

    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            this.showNotification('已複製到剪貼簿');
        } catch {
            const input = document.createElement('input');
            input.value = text;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            input.remove(); // 立即移除，避免累积
            this.showNotification('已複製到剪貼簿');
        }
    },

    createConfirmDialog(message, onConfirm, options = {}) {
        const { confirmText = '確認', cancelText = '取消', type = 'success' } = options;

        // 清理旧对话框
        const oldDialog = document.querySelector('.custom-confirm');
        if (oldDialog) oldDialog.remove();

        const confirmBox = document.createElement('div');
        confirmBox.className = 'custom-confirm';
        confirmBox.innerHTML = `
            <div class="confirm-message">${message}</div>
            <div class="confirm-buttons">
                <button class="btn-${type}">${confirmText}</button>
                <button class="btn-cancel">${cancelText}</button>
            </div>
        `;
        document.body.appendChild(confirmBox);
        setTimeout(() => confirmBox.classList.add('visible'), 10);

        const handleClose = (callback) => {
            confirmBox.classList.remove('visible');
            document.removeEventListener('keydown', handleKeydown);
            setTimeout(() => {
                confirmBox.remove();
                callback?.();
            }, 300);
        };

        const handleKeydown = (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleClose(onConfirm);
            } else if (e.key === 'Escape') {
                e.preventDefault();
                handleClose();
            }
        };

        document.addEventListener('keydown', handleKeydown);
        confirmBox.querySelector(`.btn-${type}`).onclick = () => handleClose(onConfirm);
        confirmBox.querySelector('.btn-cancel').onclick = () => handleClose();
    }
};

// 设置所有事件处理器
function setupEventHandlers() {
    setupCopyAndDelete();
    setupScrollToTop();
    setupMultiSelect();
    setupPagination();
}

// 複製和刪除事件（使用事件委托，只綁定一次）
function setupCopyAndDelete() {
    let isProcessing = false;
    
    document.addEventListener('click', async e => {
        // 分享按鈕
        const shareBtn = e.target.closest('.share-btn');
        if (shareBtn && !isProcessing) {
            e.stopPropagation();
            isProcessing = true;
            await UI.copyToClipboard(shareBtn.dataset.url);
            isProcessing = false;
            return;
        }

        // 直連按鈕
        const directBtn = e.target.closest('.direct-btn');
        if (directBtn && !isProcessing) {
            e.stopPropagation();
            isProcessing = true;
            await UI.copyToClipboard(directBtn.dataset.url);
            UI.showNotification('已複製圖片直連');
            isProcessing = false;
            return;
        }

        // 編輯按鈕
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            e.stopPropagation();
            const item = editBtn.closest('.gallery-item');
            showEditModal(editBtn.dataset.id, item);
            return;
        }

        // 刪除按鈕
        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn && !MultiSelectState.isActive) {
            e.stopPropagation();
            UI.createConfirmDialog('確定要刪除這張圖片嗎？', 
                () => API.deleteImages(deleteBtn.dataset.id, deleteBtn.dataset.path),
                { type: 'danger', confirmText: '刪除' }
            );
        }
    }, { passive: false });
}

// 圖片編輯彈窗
function showEditModal(id, galleryItem) {
    const oldModal = document.getElementById('img-edit-modal');
    if (oldModal) oldModal.remove();

    const title       = galleryItem?.dataset.title ?? '';
    const description = galleryItem?.dataset.description ?? '';
    const hasPassword = galleryItem?.dataset.hasPassword === '1';

    const modal = document.createElement('div');
    modal.id = 'img-edit-modal';
    modal.style.cssText = 'position:fixed;inset:0;z-index:9000;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);';
    modal.innerHTML = `
        <div style="background:#1f2335;border:1px solid #414868;border-radius:16px;padding:28px 32px;min-width:340px;max-width:480px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.5);">
            <h3 style="margin:0 0 20px;color:#c0caf5;font-size:1.1rem;">✏️ 編輯圖片資訊</h3>
            <label style="display:block;margin-bottom:6px;color:#7f88b2;font-size:12px;">標題</label>
            <input id="eim-title" type="text" value="${escHtml(title)}"
                style="width:100%;box-sizing:border-box;padding:10px 12px;border-radius:8px;border:1px solid #414868;background:#1a1b26;color:#c0caf5;font-size:14px;margin-bottom:14px;outline:none;">
            <label style="display:block;margin-bottom:6px;color:#7f88b2;font-size:12px;">描述</label>
            <textarea id="eim-desc" rows="3"
                style="width:100%;box-sizing:border-box;padding:10px 12px;border-radius:8px;border:1px solid #414868;background:#1a1b26;color:#c0caf5;font-size:14px;margin-bottom:14px;resize:vertical;outline:none;">${escHtml(description)}</textarea>
            <label style="display:block;margin-bottom:8px;color:#7f88b2;font-size:12px;">密碼設定</label>
            <div style="display:flex;gap:8px;margin-bottom:${hasPassword ? '10' : '0'}px;">
                <select id="eim-pw-action"
                    style="flex:1;padding:8px 10px;border-radius:8px;border:1px solid #414868;background:#1a1b26;color:#c0caf5;font-size:13px;outline:none;">
                    <option value="keep">保持不變${hasPassword ? '（已有密碼）' : '（無密碼）'}</option>
                    <option value="set">設定新密碼</option>
                    <option value="clear">移除密碼</option>
                </select>
            </div>
            <input id="eim-pw" type="password" placeholder="輸入新密碼"
                style="display:none;width:100%;box-sizing:border-box;padding:10px 12px;border-radius:8px;border:1px solid #414868;background:#1a1b26;color:#c0caf5;font-size:14px;margin-bottom:14px;outline:none;">
            <div style="display:flex;gap:10px;margin-top:18px;">
                <button id="eim-save" style="flex:1;padding:10px;border-radius:8px;border:none;background:#7aa2f7;color:#1a1b26;font-weight:bold;font-size:14px;cursor:pointer;">儲存</button>
                <button id="eim-cancel" style="flex:1;padding:10px;border-radius:8px;border:1px solid #414868;background:transparent;color:#c0caf5;font-size:14px;cursor:pointer;">取消</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    // 密碼 action 切換顯示
    const pwAction = modal.querySelector('#eim-pw-action');
    const pwInput  = modal.querySelector('#eim-pw');
    pwAction.addEventListener('change', () => {
        pwInput.style.display = pwAction.value === 'set' ? 'block' : 'none';
    });

    // 關閉
    const close = () => modal.remove();
    modal.querySelector('#eim-cancel').addEventListener('click', close);
    modal.addEventListener('click', e => { if (e.target === modal) close(); });

    // 儲存
    modal.querySelector('#eim-save').addEventListener('click', async () => {
        const saveBtn = modal.querySelector('#eim-save');
        saveBtn.disabled = true;
        saveBtn.textContent = '儲存中…';

        const body = new URLSearchParams({
            id:              id,
            title:           modal.querySelector('#eim-title').value,
            description:     modal.querySelector('#eim-desc').value,
            password_action: pwAction.value,
            password:        pwInput.value
        });

        try {
            const res  = await fetch('/api_edit_image.php', { method: 'POST', body });
            const data = await res.json();
            if (data.result === 'success') {
                UI.showNotification('更新成功');
                // 更新 data attributes 以便下次開啟彈窗顯示新值
                if (galleryItem) {
                    galleryItem.dataset.title       = modal.querySelector('#eim-title').value;
                    galleryItem.dataset.description = modal.querySelector('#eim-desc').value;
                    galleryItem.dataset.hasPassword = pwAction.value === 'set' ? '1' : (pwAction.value === 'clear' ? '0' : galleryItem.dataset.hasPassword);
                }
                close();
            } else {
                UI.showNotification(data.message || '更新失敗', 'error');
                saveBtn.disabled = false;
                saveBtn.textContent = '儲存';
            }
        } catch {
            UI.showNotification('網路錯誤', 'error');
            saveBtn.disabled = false;
            saveBtn.textContent = '儲存';
        }
    });
}

// HTML 跳脫工具（防止 XSS 注入到 innerHTML）
function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

// 回到顶部
function setupScrollToTop() {
    let ticking = false;

    window.addEventListener('scroll', () => {
        if (ticking) return;
        
        ticking = true;
        window.requestAnimationFrame(() => {
            const shouldShow = window.scrollY > 100;
            DOM.scrollTopBtn.classList.toggle('visible', shouldShow);
            DOM.rightside.classList.toggle('shifted', shouldShow);
            ticking = false;
        });
    }, { passive: true });

    DOM.scrollTopBtn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

// 分页处理
function setupPagination() {
    let isLoading = false;
    let abortController = null;
    
    DOM.pagination.addEventListener('click', async (e) => {
        e.preventDefault();
        
        if (isLoading) return;
        
        const pageLink = e.target.closest('.page-link');
        
        if (pageLink && !pageLink.classList.contains('ellipsis', 'active')) {
            const page = pageLink.dataset.page;
            if (page) {
                // 取消之前的请求
                if (abortController) {
                    abortController.abort();
                }
                
                isLoading = true;
                abortController = new AbortController();
                
                try {
                    const response = await fetch(`/admin/index.php?page=${page}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        signal: abortController.signal
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        updatePageContent(data);
                        window.history.pushState({page}, '', `?page=${page}`);
                    }
                } catch (err) {
                    if (err.name !== 'AbortError') {
                        UI.showNotification('載入失敗', 'error');
                    }
                } finally {
                    isLoading = false;
                    abortController = null;
                }
            }
        }
    }, { passive: false });
}

// 页面输入控制
function setupPageInput() {
    const input = document.createElement('input');
    input.type = 'number';
    input.min = '1';
    input.className = 'page-input';
    input.style.display = 'none';
    
    DOM.pageDisplay.parentNode.appendChild(input);
    
    const toggle = (show) => {
        DOM.pageDisplay.style.display = show ? 'none' : 'inline-block';
        input.style.display = show ? 'inline-block' : 'none';
        if (show) input.focus();
        else input.value = '';
    };
    
    DOM.pageDisplay.addEventListener('click', () => toggle(true));
    
    input.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            const page = parseInt(input.value);
            const totalPages = parseInt(DOM.pageDisplay.textContent.split('/')[1]);
            
            if (page >= 1 && page <= totalPages) {
                window.location.href = `?page=${page}`;
            } else {
                UI.showNotification('請輸入有效頁碼', 'error');
                input.value = '';
            }
        }
    });
    
    document.addEventListener('click', (e) => {
        if (!input.contains(e.target) && e.target !== DOM.pageDisplay) {
            toggle(false);
        }
    });
}

// 多选功能
function setupMultiSelect() {
    const toolbar = createMultiSelectToolbar();
    const multiSelectBtn = document.querySelector('.select-link');
    const selectedCountEl = toolbar.querySelector('.selected-count');
    
    // 切换多选模式
    const toggleMode = () => {
        MultiSelectState.toggle();
        
        DOM.gallery.classList.toggle('multi-select-mode');
        toolbar.classList.toggle('show');
        multiSelectBtn.classList.toggle('show');
        
        // 清理选中状态
        if (!MultiSelectState.isActive) {
            const selected = DOM.gallery.querySelectorAll('.gallery-item.selected');
            selected.forEach(item => item.classList.remove('selected'));
        }
        updateSelectedCount();
    };
    
    // 更新选中数量
    const updateSelectedCount = () => {
        selectedCountEl.textContent = `已選取 ${MultiSelectState.count()}`;
    };
    
    // 处理图片选择（使用事件委托）
    const handleItemSelection = (e) => {
        if (!MultiSelectState.isActive) return;
        
        const galleryItem = e.target.closest('.gallery-item');
        if (!galleryItem || e.target.closest('.action-buttons')) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        const itemId = galleryItem.id.slice(6); // 'image-'.length = 6
        
        if (MultiSelectState.has(itemId)) {
            MultiSelectState.remove(itemId);
            galleryItem.classList.remove('selected');
        } else {
            MultiSelectState.add(itemId);
            galleryItem.classList.add('selected');
        }
        updateSelectedCount();
    };
    
    // 删除选中的图片
    const handleDeleteSelected = () => {
        const count = MultiSelectState.count();
        if (count === 0) {
            UI.showNotification('請先選取要刪除的圖片', 'error');
            return;
        }
        
        UI.createConfirmDialog(
            `確定要刪除這 ${count} 張圖片嗎？`,
            async () => {
                try {
                    const ids = MultiSelectState.getAll();
                    const paths = ids.map(id => 
                        document.getElementById('image-' + id)?.querySelector('.delete-btn')?.dataset.path
                    ).filter(Boolean);
                    
                    await API.deleteImages(ids, paths);
                    MultiSelectState.clear();
                    toggleMode();
                } catch {}
            },
            { type: 'danger', confirmText: '刪除' }
        );
    };
    
    // 绑定事件
    DOM.gallery.addEventListener('click', handleItemSelection, { passive: false });
    multiSelectBtn.addEventListener('click', toggleMode);
    toolbar.querySelector('.delete-selected').addEventListener('click', handleDeleteSelected);
    toolbar.querySelector('.cancel-select').addEventListener('click', toggleMode);
}

// 创建多选工具栏
function createMultiSelectToolbar() {
    const toolbar = document.createElement('div');
    toolbar.className = 'multi-select-toolbar';
    toolbar.innerHTML = `
        <span class="selected-count">已選取 0</span>
        <button class="delete-selected">刪除所選</button>
        <button class="cancel-select">取消選取</button>
    `;
    document.body.appendChild(toolbar);
    return toolbar;
}

// 图片懒加载和预览
function initComponents() {
    // 销毁旧的懒加载实例
    if (lazyLoadInstance) {
        lazyLoadInstance.destroy();
        lazyLoadInstance = null;
    }
    
    // 懒加载
    lazyLoadInstance = new LazyLoad(LAZYLOAD_CONFIG);
    
    // Fancybox 只初始化一次
    if (!isComponentsInitialized) {
        Fancybox.bind('[data-fancybox="gallery"]', {
            Toolbar: { display: { right: ["slideshow", "thumbs", "close"] }},
            Thumbs: { showOnStart: false },
            hideScrollbar: false,
            Image: { zoom: false },
            Hash: false,
            on: {
                beforeShow: () => document.body.style.overflow = 'hidden',
                destroy: () => document.body.style.overflow = ''
            }
        });
        isComponentsInitialized = true;
    }
}


// 更新页面内容
function updatePageContent(data) {
    // 销毁懒加载实例
    if (lazyLoadInstance) {
        lazyLoadInstance.destroy();
        lazyLoadInstance = null;
    }
    
    // 直接替换内容（不用 RAF，减少延迟）
    DOM.gallery.innerHTML = data.html;
    DOM.pagination.innerHTML = data.pagination;
    DOM.pageDisplay.textContent = `${data.currentPage}/${data.totalPages}`;
    
    // 重新初始化懒加载（使用复用配置）
    lazyLoadInstance = new LazyLoad(LAZYLOAD_CONFIG);
}

// 页面卸载时清理资源
window.addEventListener('beforeunload', () => {
    if (lazyLoadInstance) {
        lazyLoadInstance.destroy();
        lazyLoadInstance = null;
    }
    Fancybox.close();
});
