document.addEventListener('DOMContentLoaded', () => {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const uploadPrompt = document.getElementById('uploadPrompt');
    const queueArea = document.getElementById('queueArea');
    const fileList = document.getElementById('fileList');
    const uploadBtn = document.getElementById('uploadBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const historySection = document.getElementById('fileHistorySection');
    const historyList = document.getElementById('fileHistoryList');
    const historyEmpty = document.getElementById('fileHistoryEmpty');
    const clearHistoryBtn = document.getElementById('clearFileHistoryBtn');

    let uploadQueue = [];
    let isUploading = false;

    renderFileHistory();

    clearHistoryBtn.addEventListener('click', () => {
        window.UploadHistory.clear('file');
        renderFileHistory();
        alert('已清除文件最近上傳紀錄');
    });

    // Trigger file select
    dropZone.addEventListener('click', (e) => {
        if (!isUploading && e.target !== uploadBtn && e.target !== cancelBtn && !e.target.closest('.queue-item')) {
            fileInput.click();
        }
    });

    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
        fileInput.value = ''; // Reset for next selection
    });

    // Drag and Drop
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        if (!isUploading) dropZone.classList.add('dragover');
    });
    
    dropZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
    });
    
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if (!isUploading) handleFiles(e.dataTransfer.files);
    });

    function handleFiles(files) {
        if (!files || files.length === 0) return;
        
        let added = false;
        Array.from(files).forEach(file => {
            // 檔案過濾（排除圖片影片，因為它們有專屬入口，但也可以選擇全開放）
            addFileToQueue(file);
            added = true;
        });

        if (added) {
            uploadPrompt.style.display = 'none';
            queueArea.style.display = 'block';
        }
    }

    function addFileToQueue(file) {
        const id = 'f_' + Math.random().toString(36).substr(2, 9);
        const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
        
        const itemDiv = document.createElement('div');
        itemDiv.className = 'queue-item';
        itemDiv.id = id;
        
        itemDiv.innerHTML = `
            <div class="queue-item-header">
                <div>
                    <div class="queue-item-title">${file.name}</div>
                    <div class="queue-item-size">${sizeMB} MB</div>
                </div>
                <div class="queue-item-status status-pending" id="status_${id}">等待上傳...</div>
            </div>
            <div class="queue-inputs" id="inputs_${id}">
                <input type="text" id="title_${id}" placeholder="文件標題 (可留空)">
                <input type="password" id="pass_${id}" placeholder="存取密碼 (選填)">
                <textarea id="desc_${id}" rows="2" placeholder="文件描述 (可留空)"></textarea>
            </div>
            <div class="progress-bar-container" style="display:none; margin: 10px 0;" id="progCont_${id}">
                <div class="progress-bar" id="progBar_${id}">0%</div>
            </div>
            <div class="queue-result" id="res_${id}" style="display:none;">
                <div class="queue-result-row">
                    <input type="text" id="url_${id}" readonly>
                    <button onclick="copyToClipboard('url_${id}')">複製連結</button>
                </div>
            </div>
        `;
        
        fileList.appendChild(itemDiv);
        
        uploadQueue.push({
            id: id,
            file: file,
            status: 'pending'
        });
    }

    cancelBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        if (isUploading) return;
        uploadQueue = [];
        fileList.innerHTML = '';
        uploadPrompt.style.display = 'block';
        queueArea.style.display = 'none';
    });

    uploadBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        if (isUploading || uploadQueue.length === 0) return;
        isUploading = true;
        uploadBtn.disabled = true;
        cancelBtn.disabled = true;
        uploadNextInQueue();
    });

    function uploadNextInQueue() {
        const nextItem = uploadQueue.find(item => item.status === 'pending');
        
        if (!nextItem) {
            isUploading = false;
            uploadBtn.style.display = 'none';
            cancelBtn.textContent = '完成 (清除列表)';
            cancelBtn.disabled = false;
            return;
        }

        uploadSingleFile(nextItem);
    }

    function uploadSingleFile(item) {
        item.status = 'uploading';
        const id = item.id;
        
        document.getElementById('status_' + id).className = 'queue-item-status status-uploading';
        document.getElementById('status_' + id).textContent = '上傳中...';
        
        const inputsDiv = document.getElementById('inputs_' + id);
        const titleInput = document.getElementById('title_' + id).value.trim();
        const passInput = document.getElementById('pass_' + id).value.trim();
        const descInput = document.getElementById('desc_' + id).value.trim();
        
        inputsDiv.style.display = 'none';
        
        const progCont = document.getElementById('progCont_' + id);
        const progBar = document.getElementById('progBar_' + id);
        progCont.style.display = 'block';

        const formData = new FormData();
        formData.append('file', item.file);
        formData.append('action', 'upload_file');
        if (titleInput) formData.append('title', titleInput);
        if (passInput) formData.append('password', passInput);
        if (descInput) formData.append('description', descInput);
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'api_file.php', true); // 我們需要建立這個 API
        
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progBar.style.width = percent + '%';
                progBar.textContent = percent + '%';
            }
        });

        xhr.onload = () => {
            progCont.style.display = 'none';
            if (xhr.status === 200) {
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (res.result === 'success') {
                        item.status = 'success';
                        document.getElementById('status_' + id).className = 'queue-item-status status-success';
                        document.getElementById('status_' + id).textContent = '上傳成功 ✅';
                        
                        const resDiv = document.getElementById('res_' + id);
                        resDiv.style.display = 'flex';
                        document.getElementById('url_' + id).value = res.data.share_url || res.data.url;

                        window.UploadHistory.add('file', {
                            shareUrl: res.data.share_url || '',
                            url: res.data.url || '',
                            title: titleInput || item.file.name,
                            filename: item.file.name,
                            mimeType: item.file.type || '',
                            createdAt: new Date().toISOString()
                        });
                        renderFileHistory();
                    } else {
                        item.status = 'error';
                        document.getElementById('status_' + id).className = 'queue-item-status status-error';
                        document.getElementById('status_' + id).textContent = '失敗: ' + (res.message || '未知錯誤');
                    }
                } catch (err) {
                    item.status = 'error';
                    document.getElementById('status_' + id).className = 'queue-item-status status-error';
                    document.getElementById('status_' + id).textContent = '伺服器回應異常';
                }
            } else {
                item.status = 'error';
                document.getElementById('status_' + id).className = 'queue-item-status status-error';
                document.getElementById('status_' + id).textContent = 'HTTP 錯誤: ' + xhr.status;
            }
            uploadNextInQueue();
        };

        xhr.onerror = () => {
            progCont.style.display = 'none';
            item.status = 'error';
            document.getElementById('status_' + id).className = 'queue-item-status status-error';
            document.getElementById('status_' + id).textContent = '網路錯誤';
            uploadNextInQueue();
        };

        xhr.send(formData);
    }

    window.copyToClipboard = function(id) {
        const el = document.getElementById(id);
        el.select();
        document.execCommand('copy');
        alert('已複製連結！');
    };

    function renderFileHistory() {
        const entries = window.UploadHistory.load('file');
        historyList.innerHTML = '';

        if (entries.length === 0) {
            historySection.hidden = true;
            historyEmpty.style.display = 'block';
            return;
        }

        historySection.hidden = false;
        historyEmpty.style.display = 'none';

        entries.forEach((entry) => {
            const item = document.createElement('article');
            item.className = 'history-item';

            const fallback = document.createElement('div');
            fallback.className = 'history-thumb-fallback';
            fallback.textContent = fileGlyph(entry.filename);
            item.appendChild(fallback);

            const content = document.createElement('div');
            content.className = 'history-content';

            const title = document.createElement('p');
            title.className = 'history-title';
            title.textContent = entry.title || entry.filename || '未命名文件';

            const link = document.createElement('a');
            link.className = 'history-link';
            link.href = entry.url;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.textContent = entry.url;

            const meta = document.createElement('div');
            meta.className = 'history-meta';
            meta.textContent = formatTimestamp(entry.createdAt);

            const actions = document.createElement('div');
            actions.className = 'history-actions';

            const copyBtn = document.createElement('button');
            copyBtn.type = 'button';
            copyBtn.className = 'history-action copy';
            copyBtn.textContent = '複製';
            copyBtn.addEventListener('click', () => copyUrl(entry.url, '已複製文件連結！'));

            const openLink = document.createElement('a');
            openLink.className = 'history-action open';
            openLink.href = entry.url;
            openLink.target = '_blank';
            openLink.rel = 'noopener noreferrer';
            openLink.textContent = '開啟';

            actions.appendChild(copyBtn);
            actions.appendChild(openLink);

            content.appendChild(title);
            content.appendChild(link);
            content.appendChild(meta);
            content.appendChild(actions);
            item.appendChild(content);
            historyList.appendChild(item);
        });
    }

    function fileGlyph(filename) {
        const lowerName = (filename || '').toLowerCase();
        if (lowerName.endsWith('.pdf')) return 'PDF';
        if (lowerName.endsWith('.doc') || lowerName.endsWith('.docx')) return 'DOC';
        if (lowerName.endsWith('.xls') || lowerName.endsWith('.xlsx')) return 'XLS';
        if (lowerName.endsWith('.zip')) return 'ZIP';
        if (lowerName.endsWith('.epub')) return 'EPUB';
        return 'FILE';
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

    function copyUrl(url, successMessage) {
        navigator.clipboard.writeText(url).then(() => {
            alert(successMessage);
        }).catch((err) => {
            console.error('複製失敗', err);
            alert('複製失敗，請稍後再試');
        });
    }
});
