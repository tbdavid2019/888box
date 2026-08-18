/* Shared 888 BOX language control for public, upload, and admin interfaces. */
(function () {
    'use strict';

    const languageStorageKey = '888box:share-language:v1';
    const supportedLanguages = ['zh-Hant', 'en'];
    const keyedTranslations = {
        languageLabel: { 'zh-Hant': '語言', en: 'Language' },
        brandName: { 'zh-Hant': '888 BOX', en: '888 BOX' }
    };
    const translations = {
        '語言': 'Language',
        '管理後台': 'Admin',
        '圖片管理': 'Image admin',
        '影片管理': 'Video admin',
        '音訊管理': 'Audio admin',
        '文件管理': 'File admin',
        '首頁': 'Home',
        '圖片中心': 'Image center',
        '影片中心': 'Video center',
        '文件中心': 'File center',
        '圖片託管': 'Image hosting',
        '影片託管': 'Video hosting',
        '文件託管': 'File hosting',
        '聲音大廳': 'Audio center',
        '支援 WebP 高效壓縮與瀑布流展示': 'WebP compression and waterfall gallery',
        '自動提取 MetaData 與 Podcast RSS 同步': 'Automatic metadata extraction and Podcast RSS sync',
        '支援 ZIP, PDF, Word 及 EPUB 線上閱讀': 'Online reading for ZIP, PDF, Word, and EPUB',
        '支援 MP3/WAV 上傳與 Podcast RSS 訂閱': 'MP3/WAV upload and Podcast RSS subscriptions',
        '自動辨識 🖼️ 圖片 · 🎬 影片 · 🎙️ 音訊 · 📂 文件 等格式並完成託管': 'Automatically detects 🖼️ images, 🎬 videos, 🎙️ audio, and 📂 files',
        '輸入圖片網址即可自動上傳，或使用 Ctrl+V 貼上': 'Enter an image URL to upload, or paste with Ctrl+V',
        '注意：部分網站設有防盜鏈，可能無法直接下載': 'Note: some sites block hotlink downloads',
        '貼上上傳': 'Paste to upload',
        '切換檔案': 'Switch files',
        '清除預覽': 'Clear preview',
        '圖片清晰度 60-100': 'Image quality 60-100',
        '設定存取密碼 (選填)': 'Set access password (optional)',
        '留空則公開': 'Leave blank for public access',
        '複製連結': 'Copy link',
        '複製 Markdown': 'Copy Markdown',
        '複製 HTML': 'Copy HTML',
        '本機上傳統計': 'Device upload statistics',
        '本批成功': 'Successful in this batch',
        '原始圖片': 'Original image',
        '壓縮後': 'Compressed',
        '壓縮率': 'Compression ratio',
        '節省空間': 'Space saved',
        '示範站點': 'Demo site',
        '所有檔案皆為公開可見且可能被刪除': 'All files are public and may be deleted',
        '圖片託管': 'Image hosting',
        '影片託管': 'Video hosting',
        '文件託管': 'File hosting',
        '上傳與處理列表': 'Upload and processing queue',
        '🚀 上傳的影片將會自動加入至 Podcast 訂閱中！': '🚀 Uploaded videos are automatically added to the Podcast feed!',
        '🚀 上傳的音訊將會自動加入至 Podcast 訂閱中！': '🚀 Uploaded audio is automatically added to the Podcast feed!',
        '返回首頁': 'Back to home',
        '返回頂部': 'Back to top',
        '登出': 'Log out',
        '系統設定': 'Settings',
        '管理分類': 'Admin sections',
        '麵包屑': 'Breadcrumb',
        '清除快取並重整': 'Clear cache and reload',
        '清除紀錄': 'Clear history',
        '最近上傳': 'Recent uploads',
        '目前還沒有最近上傳紀錄': 'No recent uploads on this device',
        '888 BOX｜個人檔案中心': '888 BOX | Personal file center',
        '888 BOX 圖片託管中心': '888 BOX Image Center',
        '888 BOX 影片託管中心': '888 BOX Video Center',
        '888 BOX 文件託管中心': '888 BOX File Center',
        '888 BOX 聲音託管中心': '888 BOX Audio Center',
        '圖片託管': 'Image hosting',
        '影片託管': 'Video hosting',
        '文件託管': 'File hosting',
        '聲音大廳': 'Audio center',
        '圖片管理後台 - 888 BOX': 'Image Admin - 888 BOX',
        '影片管理後台 - 888 BOX': 'Video Admin - 888 BOX',
        '音訊管理後台 - 888 BOX': 'Audio Admin - 888 BOX',
        '文件管理後台 - 888 BOX': 'File Admin - 888 BOX',
        '圖片管理後台': 'Image Admin',
        '影片管理後台': 'Video Admin',
        '音訊管理後台': 'Audio Admin',
        '文件管理後台': 'File Admin',
        '圖片': 'Images',
        '影片': 'Videos',
        '音訊': 'Audio',
        '文件': 'Files',
        '上傳圖片': 'Upload images',
        '上傳影片': 'Upload videos',
        '上傳音訊': 'Upload audio',
        '上傳文件': 'Upload files',
        '上傳檔案': 'Upload files',
        '複製分享連結': 'Copy share link',
        '複製圖片直連': 'Copy direct image URL',
        '編輯資訊': 'Edit information',
        '預覽': 'Preview',
        '預覽/分享': 'Preview / Share',
        '標題：': 'Title:',
        '描述：': 'Description:',
        '大小：': 'Size:',
        '時間：': 'Time:',
        '上傳時間：': 'Upload time:',
        '瀏覽：': 'Views:',
        '密碼保護': 'Password protected',
        '圖片託管中心': 'Image Center',
        '影片託管中心': 'Video Center',
        '文件託管中心': 'File Center',
        '聲音託管中心': 'Audio Center',
        '登入': 'Sign in',
        '安裝 888 BOX 應用程式': 'Install 888 BOX app',
        '安裝 888 BOX': 'Install 888 BOX',
        '加到手機主畫面，像 App 一樣快速開啟。': 'Add it to your home screen for quick App-like access.',
        '暫時不要': 'Not now',
        '系統維護': 'System maintenance',
        '最佳化資料庫': 'Optimize database',
        '檢查更新': 'Check for updates',
        '儲存設定': 'Save settings',
        '測試': 'Test',
        '複製': 'Copy',
        '分享': 'Share',
        '直連': 'Direct URL',
        '編輯': 'Edit',
        '刪除': 'Delete',
        '回到頂部': 'Back to top',
        '多選模式': 'Select mode',
        '目前沒有圖片': 'No images yet',
        '目前沒有影片': 'No videos yet',
        '目前沒有音訊': 'No audio yet',
        '目前沒有文件': 'No files yet',
        '拖曳任何檔案至此，或點擊選擇檔案': 'Drop any file here, or click to choose',
        '自動辨識 🖼️ 圖片 · 🎬 影片 · 🎙️ 音訊 · 📂 檔案 等格式並完成託管': 'Automatically detects 🖼️ images, 🎬 videos, 🎙️ audio, and 📂 files',
        '拖曳多部影片檔案至此': 'Drop multiple video files here',
        '或點擊選擇檔案 (支援 mp4, webm, mov, mkv)': 'or click to choose files (mp4, webm, mov, mkv)',
        '拖曳多部音訊檔案至此': 'Drop multiple audio files here',
        '或點擊選擇檔案 (支援 mp3, wav, aac, ogg, m4a, flac)': 'or click to choose files (mp3, wav, aac, ogg, m4a, flac)',
        '拖曳檔案至此': 'Drop files here',
        '支援 ZIP, PDF, Word, Excel, Visio, EPUB 等格式': 'Supports ZIP, PDF, Word, Excel, Visio, EPUB, and more',
        '專業、高效、安全的個人檔案中心': 'A professional, efficient, and secure personal file center',
        '檔案上傳佇列': 'File upload queue',
        '影片上傳佇列 (依序上傳)': 'Video upload queue (in order)',
        '音訊上傳佇列 (依序上傳)': 'Audio upload queue (in order)',
        '清空列表': 'Clear list',
        '開始上傳': 'Start upload',
        '開始依序上傳': 'Start sequential upload',
        '統一影片標題 (留空則使用檔名)': 'Common video title (leave blank to use file name)',
        '統一音訊標題 (留空則使用檔名)': 'Common audio title (leave blank to use file name)',
        '統一影片描述': 'Common video description',
        '統一音訊描述': 'Common audio description',
        '存取密碼 (選填)': 'Access password (optional)',
        '本機上傳統計': 'Device upload statistics',
        '僅限此裝置瀏覽器': 'This browser on this device only',
        '本批成功': 'Successful in this batch',
        '今日上傳': 'Uploaded today',
        '累計上傳': 'Total uploads',
        '上傳的影片將會自動加入至 Podcast 訂閱中！': 'Uploaded videos are automatically added to the Podcast feed!',
        '上傳的音訊將會自動加入至 Podcast 訂閱中！': 'Uploaded audio is automatically added to the Podcast feed!',
        '🎧 點此查看 Podcast RSS 連結 (XML)': '🎧 Open the Podcast RSS feed (XML)',
        '密碼保護中': 'Password protected',
        '無': 'None',
        '刪除失敗': 'Delete failed',
        '網路錯誤': 'Network error',
        '網址已複製！': 'URL copied!',
        '已複製!': 'Copied!',
        '已複製！': 'Copied!',
        '已複製連結！': 'Link copied!',
        '複製失敗，請稍後再試': 'Copy failed. Please try again later.',
        '複製失敗，請再試一次': 'Copy failed. Please try again.',
        '上傳中...': 'Uploading...',
        '上傳成功 ✅': 'Upload succeeded ✅',
        '伺服器回應異常': 'Unexpected server response',
        '網路錯誤，上傳中斷': 'Network error. Upload interrupted.',
        '解析回應失敗': 'Failed to parse response',
        '網路上傳失敗': 'Network upload failed',
        '上傳失敗': 'Upload failed',
        '查看': 'View',
        '複製連結': 'Copy link',
        '開啟': 'Open',
        '完成 (清除列表)': 'Done (clear list)',
        '未知錯誤': 'Unknown error',
        '目前受密碼保護': 'Currently password protected',
        '目前未受密碼保護': 'Currently not password protected',
        '確定要永久刪除此文件嗎？': 'Permanently delete this file?',
        '確定要刪除這部影片嗎？（將會同步從 RSS 中移除）': 'Delete this video? It will also be removed from the RSS feed.',
        '確定要刪除這首音訊嗎？（將會同步從 RSS 中移除）': 'Delete this audio? It will also be removed from the RSS feed.',
        '更新成功，RSS 已同步！': 'Updated successfully. RSS synchronized!',
        '刪除成功，RSS 已同步更新！': 'Deleted successfully. RSS updated!',
        'Podcast RSS 已重建完成': 'Podcast RSS rebuilt successfully',
        '確定要依目前資料庫內容重建 Podcast RSS 嗎？': 'Rebuild the Podcast RSS from the current database?',
        '登入': 'Sign in',
        '使用者名稱': 'Username',
        '密碼': 'Password',
        '記住我': 'Remember me',
        '忘記密碼？': 'Forgot password?',
        '快速登入 (演示模式)': 'Quick sign-in (demo mode)',
        '重設密碼': 'Reset password',
        '新密碼': 'New password',
        '確認新密碼': 'Confirm new password',
        '返回登入': 'Back to sign in',
        '送出': 'Submit',
        '取消': 'Cancel',
        '示範模式': 'Demo mode',
        '基本設定': 'Basic settings',
        '儲存方式': 'Storage method',
        '圖片代理': 'Image proxy',
        'SMTP 與檢舉設定': 'SMTP and reports',
        'SMTP 伺服器': 'SMTP server',
        'SMTP 端口': 'SMTP port',
        'SMTP 帳號': 'SMTP account',
        'SMTP 密碼': 'SMTP password',
        '啟用 TLS': 'Enable TLS',
        '管理員收件信箱': 'Admin email recipients',
        '是': 'Yes',
        '否': 'No',
        '本日': 'Today',
        '檔案大小': 'File size',
        '上傳時間': 'Upload time',
        '瀏覽次數': 'Views',
        '檢舉': 'Reports',
        '密碼': 'Password',
        'Created by': 'Created by',
        'All rights reserved': 'All rights reserved',
        'AI Agent Skills': 'AI Agent Skills'
    };

    const reverseTranslations = Object.fromEntries(
        Object.entries(translations).map(([zh, en]) => [en, zh])
    );

    const dynamicPatterns = [
        [/^上傳中\s+(\d+)%\.\.\.$/, 'Uploading $1%...'],
        [/^(\d[\d,]*) 份資產$/, '$1 assets'],
        [/^(\d[\d,]*) 部影片$/, '$1 videos'],
        [/^(\d[\d,]*) 份文件$/, '$1 files'],
        [/^(\d[\d,]*) 首音訊$/, '$1 audio items'],
        [/^大小[：:]\s*(.+)$/, 'Size: $1'],
        [/^時間[：:]\s*(.+)$/, 'Time: $1'],
        [/^瀏覽[：:]\s*(.+)$/, 'Views: $1'],
        [/^上傳時間[：:]\s*(.+)$/, 'Upload time: $1'],
        [/^檢舉[：:]\s*(.+)$/, 'Reports: $1'],
        [/^失敗[：:]\s*(.+)$/, 'Failed: $1'],
        [/^HTTP 錯誤[：:]\s*(.+)$/, 'HTTP error: $1']
    ];

    function translateDynamic(value, language) {
        if (language === 'en') {
            for (const [pattern, replacement] of dynamicPatterns) {
                if (pattern.test(value)) return value.replace(pattern, replacement);
            }
            return value;
        }
        const reversePatterns = [
            [/^(\d[\d,]*) assets$/, '$1 份資產'],
            [/^(\d[\d,]*) videos$/, '$1 部影片'],
            [/^(\d[\d,]*) files$/, '$1 份文件'],
            [/^(\d[\d,]*) audio items$/, '$1 首音訊'],
            [/^Size:\s*(.+)$/, '大小：$1'],
            [/^Time:\s*(.+)$/, '時間：$1'],
            [/^Views:\s*(.+)$/, '瀏覽：$1'],
            [/^Upload time:\s*(.+)$/, '上傳時間：$1'],
            [/^Reports:\s*(.+)$/, '檢舉：$1'],
            [/^Failed:\s*(.+)$/, '失敗：$1'],
            [/^HTTP error:\s*(.+)$/, 'HTTP 錯誤：$1']
        ];
        for (const [pattern, replacement] of reversePatterns) {
            if (pattern.test(value)) return value.replace(pattern, replacement);
        }
        return value;
    }

    function detectLanguage() {
        try {
            const storedLanguage = localStorage.getItem(languageStorageKey);
            if (supportedLanguages.includes(storedLanguage)) return storedLanguage;
        } catch (error) {
            // Private browsing may deny localStorage. Browser detection still works.
        }

        const languages = navigator.languages && navigator.languages.length
            ? navigator.languages
            : [navigator.language || ''];
        return languages.some(language => /^zh(?:-|$)/i.test(language)) ? 'zh-Hant' : 'en';
    }

    function textFor(value, language) {
        if (language === 'zh-Hant') return reverseTranslations[value] || value;
        return translations[value] || value;
    }

    function replaceKnownPhrases(value, language) {
        const entries = language === 'zh-Hant'
            ? Object.entries(reverseTranslations)
            : Object.entries(translations);
        return entries
            .sort((a, b) => b[0].length - a[0].length)
            .reduce((result, [source, target]) => result.includes(source) ? result.split(source).join(target) : result, value);
    }

    function translateTextNode(node, language) {
        const value = node.nodeValue;
        const normalized = value.trim();
        if (!normalized) return;
        const dynamicTranslated = translateDynamic(normalized, language);
        const translated = dynamicTranslated !== normalized
            ? dynamicTranslated
            : replaceKnownPhrases(textFor(normalized, language), language);
        if (translated === normalized) return;
        if (translated === normalized) return;
        node.nodeValue = value.replace(normalized, translated);
    }

    function translateAttributes(element, language) {
        ['title', 'placeholder', 'aria-label'].forEach(attribute => {
            const value = element.getAttribute(attribute);
            if (!value) return;
            const translated = replaceKnownPhrases(textFor(value, language), language);
            if (translated !== value) element.setAttribute(attribute, translated);
        });
    }

    function translateTree(root, language) {
        if (!root || root.nodeType !== Node.ELEMENT_NODE && root.nodeType !== Node.DOCUMENT_NODE) return;
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
        const textNodes = [];
        let node;
        while ((node = walker.nextNode())) textNodes.push(node);
        textNodes.forEach(textNode => {
            if (textNode.parentElement && !['SCRIPT', 'STYLE', 'NOSCRIPT'].includes(textNode.parentElement.tagName)) {
                translateTextNode(textNode, language);
            }
        });
        root.querySelectorAll?.('*').forEach(element => translateAttributes(element, language));
    }

    function applyLanguage(language) {
        if (!supportedLanguages.includes(language)) return;
        document.documentElement.lang = language;
        document.querySelectorAll('[data-i18n]').forEach(element => {
            const key = element.dataset.i18n;
            const value = keyedTranslations[key]?.[language]
                || window.BOX_I18N_STRINGS?.[language]?.[key]
                || key;
            if (value !== key) element.textContent = value;
        });
        document.querySelectorAll('[data-i18n-placeholder]').forEach(element => {
            const key = element.dataset.i18nPlaceholder;
            const value = keyedTranslations[key]?.[language]
                || window.BOX_I18N_STRINGS?.[language]?.[key]
                || key;
            element.setAttribute('placeholder', value);
        });
        document.querySelectorAll('[data-i18n-aria]').forEach(element => {
            const key = element.dataset.i18nAria;
            const value = keyedTranslations[key]?.[language]
                || window.BOX_I18N_STRINGS?.[language]?.[key]
                || key;
            element.setAttribute('aria-label', value);
        });
        translateTree(document, language);
        document.querySelectorAll('[data-language]').forEach(button => {
            const isActive = button.dataset.language === language;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
        try {
            localStorage.setItem(languageStorageKey, language);
        } catch (error) {
            // The toggle remains usable when storage is unavailable.
        }
        window.dispatchEvent(new CustomEvent('boxlanguagechange', { detail: { language } }));
        window.BOX_PWA?.applyLanguage(language);
    }

    function init() {
        const language = detectLanguage();
        document.querySelectorAll('[data-language]').forEach(button => {
            button.addEventListener('click', () => applyLanguage(button.dataset.language));
        });
        applyLanguage(language);
        const observer = new MutationObserver(records => {
            records.forEach(record => {
                record.addedNodes.forEach(node => {
                    if (node.nodeType === Node.ELEMENT_NODE) translateTree(node, language);
                    if (node.nodeType === Node.TEXT_NODE) translateTextNode(node, language);
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
        window.BOX_I18N = { applyLanguage, detectLanguage, textFor };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
}());
