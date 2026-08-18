<?php

function renderAdminHeader($active, $title, $actions = []) {
    ?>
    <?php
    $headerActions = array_map(static function ($action) {
        $iconByLabel = [
            '上傳圖片' => 'upload-cloud',
            '上傳影片' => 'upload-cloud',
            '上傳音訊' => 'upload-cloud',
            '上傳文件' => 'upload-cloud',
            '系統設定' => 'settings',
            '返回首頁' => 'home',
            '登出' => 'log-out',
            'Podcast RSS' => 'rss',
            '重建 RSS' => 'refresh-cw',
        ];
        if (empty($action['icon']) && isset($iconByLabel[$action['label'] ?? ''])) {
            $action['icon'] = $iconByLabel[$action['label']];
        }
        return $action;
    }, $actions);
    renderSiteHeader($title, $headerActions, $active);
    ?>
    <?php
}

function renderAdminFooter() {
    ?>
    <footer class="admin-footer">
        <div class="admin-footer-links">
            <a href="/admin/index.php">圖片管理</a>
            <a href="/admin/video.php">影片管理</a>
            <a href="/admin/audio.php">音訊管理</a>
            <a href="/admin/file.php">文件管理</a>
            <a href="/skill.php" target="_blank" rel="noopener noreferrer">AI Agent Skills</a>
        </div>
        <div>
            <span>© <?= date('Y') ?> 888 BOX</span> |
            <span>Created by <a href="https://david888.com" target="_blank" rel="noopener noreferrer">DAVID888</a></span>
        </div>
    </footer>
    <?php renderI18nAssets('admin'); ?>
    <?php
}
