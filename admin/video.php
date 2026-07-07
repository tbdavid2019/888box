<?php
session_start();
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: /admin/login.php');
    exit;
}

require_once '../config/database.php';
require_once '../config/rss.php';
require_once '../config/theme_helper.php';
require_once '../config/admin_ui.php';

$db = Database::getInstance();
$pdo = $db->getConnection();
$config = Database::getConfig($pdo);
$videoRssUrl = buildRssUrl('video', $config, true);

// 撈取影片
$stmt = $pdo->prepare("SELECT * FROM images WHERE url LIKE '%.mp4' OR url LIKE '%.webm' OR url LIKE '%.mov' OR url LIKE '%.mkv' ORDER BY id DESC");
$stmt->execute();
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($videos as &$video) {
    $video['url'] = getMaskedUrl($video['url'], $video['path']);
}
unset($video);
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>影片管理後台 - 888box</title>
    <link rel="shortcut icon" href="/static/favicon.svg">
    <link rel="stylesheet" href="/static/css/admin/shared.css?v=<?php echo time(); ?>">
    <?php renderThemeStyles($pdo); ?>
    <style>
        body { background: radial-gradient(circle at top, rgba(122, 162, 247, 0.14), transparent 32%), linear-gradient(180deg, #1f2335 0%, #1a1b26 42%, #16161e 100%); color: #c0caf5; font-family: var(--font-ui); margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #414868; padding-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; color: #c0caf5; }
        .nav-links a, .nav-links button { color: #7dcfff; text-decoration: none; margin-left: 15px; font-weight: bold; }
        .nav-links a:hover { color: #bb9af7; }
        .nav-links button:hover { color: #bb9af7; }
        .nav-links button { background: none; border: none; cursor: pointer; font: inherit; padding: 0; }
        .video-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; max-width: 1400px; margin: 0 auto; }
        .video-card { background: rgba(36, 40, 59, 0.94); border: 1px solid #414868; border-radius: 12px; padding: 20px; display: flex; flex-direction: column; gap: 15px; box-shadow: 0 10px 24px rgba(15, 18, 32, 0.28); transition: transform 0.2s, border-color 0.2s; }
        .video-card:hover { transform: translateY(-5px); border-color: #7aa2f7; }
        .video-card video { width: 100%; border-radius: 8px; background: #000; box-shadow: 0 2px 8px rgba(0,0,0,0.5); }
        .video-info { font-size: 14px; color: #a9b1d6; background: rgba(26, 27, 38, 0.72); padding: 15px; border-radius: 8px; border: 1px solid #414868; }
        .video-info p { margin: 8px 0; word-break: break-all; }
        .video-info strong { color: #c0caf5; }
        .video-meta-badges { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
        .video-badge { display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 999px; font-size: 12px; font-weight: bold; }
        .video-badge.password { background: rgba(247, 118, 142, 0.18); color: #f7768e; border: 1px solid rgba(247, 118, 142, 0.3); }
        .password-help { margin-top: 8px; color: #7f88b2; font-size: 13px; line-height: 1.5; }
        .actions { margin-top: auto; display: flex; justify-content: space-between; align-items: center; gap: 10px; }
        .btn-copy { background: #7aa2f7; color: #1a1b26; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; flex: 1; }
        .btn-edit { background: #bb9af7; color: #1a1b26; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; flex: 1; }
        .btn-delete { background: #f7768e; color: #1a1b26; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; flex: 1; }
        .btn-copy:hover { background: #7dcfff; }
        .btn-edit:hover { background: #c0caf5; }
        .btn-delete:hover { background: #ff9eaf; }
        .empty-state { text-align: center; color: #7f88b2; padding: 80px; font-size: 20px; grid-column: 1 / -1; background: rgba(36, 40, 59, 0.94); border-radius: 12px; border: 1px dashed #565f89; }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); backdrop-filter: blur(5px); }
        .modal-content { background-color: #24283b; margin: 10% auto; padding: 30px; border: 1px solid #414868; width: 90%; max-width: 500px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        .modal-header { margin-bottom: 20px; font-size: 20px; font-weight: bold; color: #c0caf5; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; color: #a9b1d6; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; background: rgba(26, 27, 38, 0.72); border: 1px solid #414868; color: #c0caf5; border-radius: 6px; box-sizing: border-box; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; }
    </style>
</head>
<body>
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">編輯影片資訊</div>
            <input type="hidden" id="editId">
            <input type="hidden" id="editHasPassword">
            <div class="form-group">
                <label>影片標題</label>
                <input type="text" id="editTitle">
            </div>
            <div class="form-group">
                <label>影片描述</label>
                <textarea id="editDescription" rows="4"></textarea>
            </div>
            <div class="form-group">
                <label>存取密碼</label>
                <div id="editPasswordStatus" class="password-help">目前未受密碼保護</div>
                <select id="editPasswordAction" style="width: 100%; padding: 12px; background: rgba(26, 27, 38, 0.72); border: 1px solid #414868; color: #c0caf5; border-radius: 6px; box-sizing: border-box;">
                    <option value="keep">保留目前設定</option>
                    <option value="set">設定新密碼</option>
                    <option value="clear">移除密碼保護</option>
                </select>
                <input type="password" id="editPassword" placeholder="輸入新的存取密碼" style="margin-top: 10px; display: none;" autocomplete="new-password">
                <div class="password-help">變更密碼保護會影響該影片是否出現在 Podcast RSS。</div>
            </div>
            <div class="modal-actions">
                <button class="btn-copy" style="background:#565f89; color:#c0caf5; flex:none;" onclick="closeModal()">取消</button>
                <button class="btn-copy" style="flex:none; min-width:100px;" onclick="saveMetadata()">儲存變更</button>
            </div>
        </div>
    </div>

    <?php renderAdminHeader('video', '影片管理後台', [
        ['label' => '上傳影片', 'href' => '/upload_video.php'],
        ['label' => 'Podcast RSS', 'href' => $videoRssUrl, 'target' => '_blank'],
        ['label' => '重建 RSS', 'type' => 'button', 'onclick' => 'rebuildPodcast()'],
        ['label' => '返回首頁', 'href' => '/'],
        ['label' => '登出', 'href' => '/admin/index.php?logout=true'],
    ]); ?>

    <div class="video-grid">
        <?php if (empty($videos)): ?>
            <div class="empty-state">目前沒有影片</div>
        <?php else: ?>
            <?php foreach ($videos as $video): ?>
                <?php $shareUrl = buildAssetShareUrl($video, $config); ?>
                <div class="video-card" id="video-<?= $video['id'] ?>" data-has-password="<?= empty($video['password']) ? '0' : '1' ?>">
                    <video src="<?= htmlspecialchars($video['url']) ?>" controls preload="metadata"></video>
                    <div class="video-info">
                        <p>
                            <strong>標題：</strong> <span class="v-title"><?= htmlspecialchars($video['title'] ?: $video['path']) ?></span>
                            <?php if ($video['report_count'] > 0): ?>
                                <span style="background: #f7768e; color: #1a1b26; padding: 2px 6px; border-radius: 4px; font-size: 11px; margin-left: 5px;">檢舉: <?= $video['report_count'] ?></span>
                            <?php endif; ?>
                        </p>
                        <p><strong>描述：</strong> <span class="v-desc"><?= htmlspecialchars($video['description'] ?: '無') ?></span></p>
                        <p><strong>大小：</strong> <?= number_format(floatval($video['size']) / 1024 / 1024, 2) ?> MB | <strong>瀏覽:</strong> <?= $video['view_count'] ?></p>
                        <p><strong>上傳時間：</strong> <?= htmlspecialchars($video['created_at']) ?></p>
                        <div class="video-meta-badges">
                            <?php if (!empty($video['password'])): ?>
                                <span class="video-badge password">密碼保護中</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="actions">
                        <button class="btn-copy" onclick="copyUrl('<?= htmlspecialchars($shareUrl) ?>')">分享</button>
                        <button class="btn-copy" onclick="copyUrl('<?= htmlspecialchars($video['url']) ?>')">直連</button>
                        <button class="btn-edit" onclick="openEditModal(<?= $video['id'] ?>)">編輯</button>
                        <button class="btn-delete" onclick="deleteVideo(<?= $video['id'] ?>, '<?= htmlspecialchars($video['path']) ?>')">刪除</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php renderAdminFooter(); ?>
    <script>
        function openEditModal(id) {
            const card = document.getElementById('video-' + id);
            const title = card.querySelector('.v-title').textContent;
            const desc = card.querySelector('.v-desc').textContent;
            const hasPassword = card.dataset.hasPassword === '1';
            
            document.getElementById('editId').value = id;
            document.getElementById('editTitle').value = title;
            document.getElementById('editDescription').value = desc === '無' ? '' : desc;
            document.getElementById('editHasPassword').value = hasPassword ? '1' : '0';
            document.getElementById('editPasswordStatus').textContent = hasPassword ? '目前受密碼保護' : '目前未受密碼保護';
            document.getElementById('editPasswordAction').value = 'keep';
            document.getElementById('editPassword').value = '';
            togglePasswordInput();
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function togglePasswordInput() {
            const action = document.getElementById('editPasswordAction').value;
            document.getElementById('editPassword').style.display = action === 'set' ? 'block' : 'none';
        }

        function saveMetadata() {
            const id = document.getElementById('editId').value;
            const title = document.getElementById('editTitle').value;
            const desc = document.getElementById('editDescription').value;
            const passwordAction = document.getElementById('editPasswordAction').value;
            const password = document.getElementById('editPassword').value;
            
            const formData = new FormData();
            formData.append('id', id);
            formData.append('title', title);
            formData.append('description', desc);
            formData.append('password_action', passwordAction);
            if (passwordAction === 'set') {
                formData.append('password', password);
            }
            
            fetch('/api_edit_video.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.result === 'success') {
                    const card = document.getElementById('video-' + id);
                    card.querySelector('.v-title').textContent = title;
                    card.querySelector('.v-desc').textContent = desc || '無';
                    const hasPassword = passwordAction === 'set' ? true : (passwordAction === 'clear' ? false : card.dataset.hasPassword === '1');
                    card.dataset.hasPassword = hasPassword ? '1' : '0';

                    let badge = card.querySelector('.video-badge.password');
                    if (hasPassword && !badge) {
                        badge = document.createElement('span');
                        badge.className = 'video-badge password';
                        badge.textContent = '密碼保護中';
                        card.querySelector('.video-meta-badges').appendChild(badge);
                    }
                    if (!hasPassword && badge) {
                        badge.remove();
                    }
                    closeModal();
                    alert('更新成功，RSS 已同步！');
                } else {
                    alert('更新失敗：' + data.message);
                }
            })
            .catch(err => {
                alert('網路錯誤');
            });
        }

        document.getElementById('editPasswordAction').addEventListener('change', togglePasswordInput);

        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) closeModal();
        }

        function copyUrl(url) {
            navigator.clipboard.writeText(url).then(() => {
                alert('網址已複製！');
            }).catch(err => {
                console.error('複製失敗', err);
            });
        }

        function deleteVideo(id, path) {
            if (!confirm('確定要刪除這部影片嗎？（將會同步從 RSS 中移除）')) return;
            
            const formData = new FormData();
            formData.append('id', id);
            formData.append('path', path);
            
            fetch('/api_delete_video.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.result === 'success') {
                    const card = document.getElementById('video-' + id);
                    if (card) {
                        card.style.opacity = '0';
                        setTimeout(() => card.remove(), 300);
                    }
                    alert('刪除成功，RSS 已同步更新！');
                } else {
                    alert('刪除失敗：' + data.message);
                }
            })
            .catch(err => {
                alert('網路錯誤');
                console.error(err);
            });
        }

        function rebuildPodcast() {
            if (!confirm('確定要依目前資料庫內容重建 Podcast RSS 嗎？')) return;

            fetch('/api_rebuild_podcast.php', {
                method: 'POST'
            })
            .then(res => res.json())
            .then(data => {
                if (data.result === 'success') {
                    alert('Podcast RSS 已重建完成');
                } else {
                    alert('重建失敗：' + data.message);
                }
            })
            .catch(err => {
                alert('網路錯誤');
                console.error(err);
            });
        }
    </script>
</body>
</html>
