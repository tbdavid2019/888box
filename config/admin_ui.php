<?php

function renderAdminHeader($active, $title, $actions = []) {
    $tabs = [
        'image' => ['label' => '圖片', 'href' => '/admin/index.php'],
        'video' => ['label' => '影片', 'href' => '/admin/video.php'],
        'audio' => ['label' => '音訊', 'href' => '/admin/audio.php'],
        'file' => ['label' => '文件', 'href' => '/admin/file.php'],
    ];
    $currentLabel = $tabs[$active]['label'] ?? $title;
    ?>
    <header class="admin-header">
        <div class="admin-header-main">
            <div>
                <nav class="admin-breadcrumb" aria-label="麵包屑">
                    <a href="/">888box</a>
                    <span class="admin-breadcrumb-separator">/</span>
                    <a href="/admin/">管理後台</a>
                    <span class="admin-breadcrumb-separator">/</span>
                    <span aria-current="page"><?= htmlspecialchars($currentLabel) ?></span>
                </nav>
                <h1><?= htmlspecialchars($title) ?></h1>
            </div>
            <nav class="admin-tabs" aria-label="管理分類">
                <?php foreach ($tabs as $key => $tab): ?>
                    <a href="<?= htmlspecialchars($tab['href']) ?>" class="admin-tab <?= $active === $key ? 'active' : '' ?>">
                        <?= htmlspecialchars($tab['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
        <?php if (!empty($actions)): ?>
            <div class="admin-actions">
                <?php foreach ($actions as $action): ?>
                    <?php if (($action['type'] ?? 'link') === 'button'): ?>
                        <button type="button" class="admin-action" onclick="<?= htmlspecialchars($action['onclick'] ?? '') ?>">
                            <?= htmlspecialchars($action['label'] ?? '') ?>
                        </button>
                    <?php else: ?>
                        <a
                            href="<?= htmlspecialchars($action['href'] ?? '#') ?>"
                            class="admin-action <?= htmlspecialchars($action['class'] ?? '') ?>"
                            <?= !empty($action['target']) ? 'target="' . htmlspecialchars($action['target']) . '"' : '' ?>
                            <?= !empty($action['target']) ? 'rel="noopener noreferrer"' : '' ?>
                        >
                            <?= htmlspecialchars($action['label'] ?? '') ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </header>
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
            <span>© <?= date('Y') ?> 888box</span> |
            <span>Created by <a href="https://david888.com" target="_blank" rel="noopener noreferrer">DAVID888</a></span>
        </div>
    </footer>
    <?php
}
