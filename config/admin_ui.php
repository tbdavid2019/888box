<?php

require_once __DIR__ . '/theme_helper.php';

function renderAdminHeader($active, $title, $actions = []) {
    $adminSections = [
        'image' => [
            'label' => '圖片',
            'href' => '/admin/index.php',
            'icon' => 'image',
        ],
        'video' => [
            'label' => '影片',
            'href' => '/admin/video.php',
            'icon' => 'video',
        ],
        'audio' => [
            'label' => '音訊',
            'href' => '/admin/audio.php',
            'icon' => 'music',
        ],
        'file' => [
            'label' => '文件',
            'href' => '/admin/file.php',
            'icon' => 'file-text',
        ],
        'users' => [
            'label' => '帳號',
            'href' => '/admin/users.php',
            'icon' => 'users',
        ],
    ];

    // 过滤掉已整合至全域導覽列的冗餘動作
    $filteredActions = [];
    foreach ($actions as $action) {
        $label = $action['label'] ?? '';
        if (in_array($label, ['帳號管理', '系統設定', '返回首頁', '登出'], true)) {
            continue;
        }
        $filteredActions[] = $action;
    }

    $iconByLabel = [
        '上傳圖片' => 'upload-cloud',
        '上傳影片' => 'upload-cloud',
        '上傳音訊' => 'upload-cloud',
        '上傳文件' => 'upload-cloud',
        '新增同事帳號' => 'user-plus',
        '新增帳號' => 'user-plus',
        'Podcast RSS' => 'rss',
        '重建 RSS' => 'refresh-cw',
        'API 介接指南' => 'terminal',
        'API 說明' => 'terminal',
    ];

    $cleanTitle = preg_replace('/後台$/u', '', $title);
    if (empty($cleanTitle)) {
        $cleanTitle = $adminSections[$active]['label'] ?? '管理';
    }
    ?>
    <header class="box-site-header box-admin-site-header">
        <div class="box-site-header-left">
            <a href="/admin/index.php" class="box-site-brand" aria-label="888 BOX 後台管理">
                <i data-lucide="box"></i>
                <span data-i18n="brandName">888 BOX</span>
            </a>
            <span class="box-admin-pill-badge" data-i18n>後台</span>
        </div>

        <nav class="box-site-section-nav box-admin-section-nav" aria-label="後台管理分類">
            <?php foreach ($adminSections as $key => $sec): ?>
                <?php $isActive = ($active === $key); ?>
                <a href="<?= htmlspecialchars($sec['href'], ENT_QUOTES, 'UTF-8') ?>"
                   class="box-site-section-link box-admin-nav-item<?= $isActive ? ' is-active' : '' ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
                    <i data-lucide="<?= htmlspecialchars($sec['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                    <span data-i18n><?= htmlspecialchars($sec['label'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="box-site-header-actions box-admin-header-actions">
            <?php if (!empty($filteredActions)): ?>
                <div class="box-admin-page-actions">
                    <?php foreach ($filteredActions as $action): ?>
                        <?php
                        $label = $action['label'] ?? '';
                        $icon = $action['icon'] ?? ($iconByLabel[$label] ?? '');
                        $isPrimary = (!empty($action['variant']) && $action['variant'] === 'primary')
                                     || str_starts_with($label, '上傳')
                                     || str_starts_with($label, '新增');
                        $btnClass = 'box-admin-btn ' . ($isPrimary ? 'box-admin-btn-primary' : 'box-admin-btn-secondary') . ' ' . ($action['class'] ?? '');
                        ?>
                        <?php if (($action['type'] ?? 'link') === 'button'): ?>
                            <button type="button" class="<?= htmlspecialchars(trim($btnClass), ENT_QUOTES, 'UTF-8') ?>" onclick="<?= htmlspecialchars($action['onclick'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <?php if ($icon): ?><i data-lucide="<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"></i><?php endif; ?>
                                <span data-i18n><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                        <?php else: ?>
                            <a href="<?= htmlspecialchars($action['href'] ?? '#', ENT_QUOTES, 'UTF-8') ?>" class="<?= htmlspecialchars(trim($btnClass), ENT_QUOTES, 'UTF-8') ?>"<?= !empty($action['onclick']) ? ' onclick="' . htmlspecialchars($action['onclick'], ENT_QUOTES, 'UTF-8') . '"' : '' ?><?= !empty($action['target']) ? ' target="' . htmlspecialchars($action['target'], ENT_QUOTES, 'UTF-8') . '" rel="noopener noreferrer"' : '' ?>>
                                <?php if ($icon): ?><i data-lucide="<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"></i><?php endif; ?>
                                <span data-i18n><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <span class="box-admin-divider" aria-hidden="true"></span>
            <?php endif; ?>

            <div class="box-admin-global-actions">
                <?php renderLanguageSwitcher(true); ?>
                <a href="#" class="box-admin-util-btn settings-link" title="系統設定" aria-label="系統設定">
                    <i data-lucide="settings"></i>
                    <span class="box-admin-util-label" data-i18n>設定</span>
                </a>
                <a href="/" class="box-admin-util-btn" title="前台首頁" aria-label="前台首頁" target="_blank" rel="noopener noreferrer">
                    <i data-lucide="home"></i>
                    <span class="box-admin-util-label" data-i18n>首頁</span>
                </a>
                <a href="/admin/index.php?logout=true" class="box-admin-util-btn box-admin-logout-btn" title="登出" aria-label="登出">
                    <i data-lucide="log-out"></i>
                    <span class="box-admin-util-label" data-i18n>登出</span>
                </a>
            </div>
        </div>
    </header>
    <?php
}

function renderAdminFooter() {
    ?>
    <div id="settings-modal" class="modal">
        <div class="modal-content"></div>
    </div>
    <footer class="admin-footer">
        <div class="admin-footer-links">
            <a href="/admin/index.php">圖片管理</a>
            <a href="/admin/video.php">影片管理</a>
            <a href="/admin/audio.php">音訊管理</a>
            <a href="/admin/file.php">文件管理</a>
            <a href="/admin/users.php">帳號管理</a>
            <a href="/skill.php" target="_blank" rel="noopener noreferrer">AI Agent Skills</a>
        </div>
        <div>
            <span>© <?= date('Y') ?> 888 BOX</span> |
            <span>Created by <a href="https://david888.com" target="_blank" rel="noopener noreferrer">DAVID888</a></span>
        </div>
    </footer>
    <?php renderI18nAssets('admin'); ?>
    <script>
    if (typeof window.UI === 'undefined') {
        window.UI = {
            showNotification: function(msg, type) {
                var toast = document.createElement('div');
                toast.className = 'admin-toast admin-toast-' + (type || 'info');
                toast.textContent = msg;
                toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:99999;padding:10px 18px;border-radius:8px;background:' + (type === 'error' ? '#f7768e' : (type === 'success' ? '#9ece6a' : '#7aa2f7')) + ';color:#1a1b26;font-weight:700;box-shadow:0 8px 24px rgba(0,0,0,0.3);transition:all .3s ease;';
                document.body.appendChild(toast);
                setTimeout(function() {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-10px)';
                    setTimeout(function() { toast.remove(); }, 300);
                }, 3000);
            },
            createConfirmDialog: function(message, onConfirm, options) {
                if (window.confirm(message)) {
                    if (typeof onConfirm === 'function') onConfirm();
                }
            },
            copyToClipboard: async function(text) {
                try {
                    await navigator.clipboard.writeText(text);
                    this.showNotification('已複製到剪貼簿', 'success');
                } catch (e) {
                    this.showNotification('複製失敗', 'error');
                }
            }
        };
    }
    </script>
    <script src="/static/js/settings.js?v=<?= time() ?>"></script>
    <?php
}
