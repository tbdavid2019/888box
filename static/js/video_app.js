document.addEventListener('DOMContentLoaded', () => {
    const dropZone = document.getElementById('dropZone');
    const videoInput = document.getElementById('videoInput');
    const uploadPrompt = document.getElementById('uploadPrompt');
    const queueArea = document.getElementById('queueArea');
    const fileList = document.getElementById('fileList');
    const uploadBtns = Array.from(document.querySelectorAll('[data-video-action="upload"]'));
    const cancelBtns = Array.from(document.querySelectorAll('[data-video-action="clear"]'));
    const historySection = document.getElementById('videoHistorySection');
    const historyList = document.getElementById('videoHistoryList');
    const historyEmpty = document.getElementById('videoHistoryEmpty');
    const clearHistoryBtn = document.getElementById('clearVideoHistoryBtn');

    let uploadQueue = [];
    let isUploading = false;

    renderVideoHistory();

    clearHistoryBtn.addEventListener('click', () => {
        window.UploadHistory.clear('video');
        renderVideoHistory();
        alert('已清除影片最近上傳紀錄');
    });

    function setUploadButtonsDisabled(disabled) {
        uploadBtns.forEach((button) => {
            button.disabled = disabled;
        });
    }

    function setCancelButtonsDisabled(disabled) {
        cancelBtns.forEach((button) => {
            button.disabled = disabled;
        });
    }

    function setUploadButtonsDisplay(display) {
        uploadBtns.forEach((button) => {
            button.style.display = display;
        });
    }

    function setCancelButtonsText(text) {
        cancelBtns.forEach((button) => {
            button.textContent = text;
        });
    }

    // Trigger file select
    dropZone.addEventListener('click', (e) => {
        if (!isUploading && !e.target.closest('[data-video-action]') && !e.target.closest('.queue-item')) {
            videoInput.click();
        }
    });

    videoInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
        videoInput.value = ''; // Reset for next selection
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

    // Paste
    document.addEventListener('paste', (e) => {
        if (isUploading) return;
        const files = [];
        if (e.clipboardData && e.clipboardData.items) {
            for (let i = 0; i < e.clipboardData.items.length; i++) {
                const item = e.clipboardData.items[i];
                if (item.kind === 'file') {
                    files.push(item.getAsFile());
                }
            }
        }
        handleFiles(files);
    });

    function handleFiles(files) {
        if (!files || files.length === 0) return;
        
        let added = false;
        Array.from(files).forEach(file => {
            const isVideo = file.type.startsWith('video/') || file.name.match(/\.(mp4|webm|mov|mkv|avi)$/i);
            if (isVideo) {
                addFileToQueue(file);
                added = true;
            }
        });

        if (added) {
            uploadPrompt.style.display = 'none';
            queueArea.style.display = 'block';
            setUploadButtonsDisplay('');
            setUploadButtonsDisabled(false);
            setCancelButtonsDisabled(false);
            setCancelButtonsText('清空列表');
        }
    }

    function addFileToQueue(file) {
        const id = 'vid_' + Math.random().toString(36).substr(2, 9);
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
                <input type="text" id="title_${id}" placeholder="Podcast 標題 (預設使用檔名)">
                <textarea id="desc_${id}" rows="2" placeholder="Podcast 描述 (可留空)"></textarea>
                <input type="password" id="pass_${id}" placeholder="存取密碼 (選填)" style="width:100%; padding:8px; border-radius:4px; border:1px solid #444; background:#222; color:#fff; margin-top:5px;">
            </div>
            <div class="progress-bar-container" style="display:none; margin: 10px 0;" id="progCont_${id}">
                <div class="progress-bar" id="progBar_${id}">0%</div>
            </div>
            <div class="queue-result" id="res_${id}">
                <div class="queue-result-row">
                    <input type="text" id="url_${id}" readonly>
                    <button onclick="copyToClipboard('url_${id}')">複製影片</button>
                </div>
                <div class="queue-result-row">
                    <input type="text" id="thumb_${id}" readonly placeholder="無封面圖">
                    <button onclick="copyToClipboard('thumb_${id}')">複製封面</button>
                </div>
            </div>
        `;
        
        fileList.appendChild(itemDiv);
        
        uploadQueue.push({
            id: id,
            file: file,
            status: 'pending' // pending, uploading, success, error
        });
    }

    cancelBtns.forEach((button) => {
        button.addEventListener('click', (e) => {
            e.stopPropagation();
            if (isUploading) return;
            uploadQueue = [];
            fileList.innerHTML = '';
            uploadPrompt.style.display = 'block';
            queueArea.style.display = 'none';
            setUploadButtonsDisplay('');
            setUploadButtonsDisabled(false);
            setCancelButtonsDisabled(false);
            setCancelButtonsText('清空列表');
        });
    });

    uploadBtns.forEach((button) => {
        button.addEventListener('click', (e) => {
            e.stopPropagation();
            if (isUploading || uploadQueue.length === 0) return;
            isUploading = true;
            setUploadButtonsDisabled(true);
            setCancelButtonsDisabled(true);
            uploadNextInQueue();
        });
    });

    function uploadNextInQueue() {
        const nextItem = uploadQueue.find(item => item.status === 'pending');
        
        if (!nextItem) {
            // All done
            isUploading = false;
            setUploadButtonsDisplay('none');
            setCancelButtonsText('完成 (清除列表)');
            setCancelButtonsDisabled(false);
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
        const descInput = document.getElementById('desc_' + id).value.trim();
        const passInput = document.getElementById('pass_' + id).value.trim();
        
        // Batch values
        const batchTitle = document.getElementById('batchTitle').value.trim();
        const batchDesc = document.getElementById('batchDesc').value.trim();
        const batchPass = document.getElementById('batchPass').value.trim();

        inputsDiv.style.display = 'none'; // Hide inputs during/after upload
        
        const progCont = document.getElementById('progCont_' + id);
        const progBar = document.getElementById('progBar_' + id);
        progCont.style.display = 'block';

        const formData = new FormData();
        formData.append('file', item.file);
        
        const finalTitle = titleInput || batchTitle;
        const finalDesc = descInput || batchDesc;
        const finalPass = passInput || batchPass;

        if (finalTitle) formData.append('title', finalTitle);
        if (finalDesc) formData.append('description', finalDesc);
        if (finalPass) formData.append('password', finalPass);
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'video.php', true);
        
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
                        document.getElementById('url_' + id).value = res.data.url;
                        document.getElementById('thumb_' + id).value = res.data.thumbnail_url || '無封面圖';

                        window.UploadHistory.add('video', {
                            url: res.data.url,
                            thumbnailUrl: res.data.thumbnail_url || '',
                            title: finalTitle || item.file.name,
                            filename: item.file.name,
                            createdAt: new Date().toISOString()
                        });
                        renderVideoHistory();
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
            
            // Proceed to next
            uploadNextInQueue();
        };

        xhr.onerror = () => {
            progCont.style.display = 'none';
            item.status = 'error';
            document.getElementById('status_' + id).className = 'queue-item-status status-error';
            document.getElementById('status_' + id).textContent = '網路錯誤，上傳中斷';
            
            // Proceed to next even if error
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

    function renderVideoHistory() {
        const entries = window.UploadHistory.load('video');
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

            if (entry.thumbnailUrl) {
                const img = document.createElement('img');
                img.className = 'history-thumb';
                img.src = entry.thumbnailUrl;
                img.alt = entry.title || entry.filename || 'video thumbnail';
                item.appendChild(img);
            } else {
                const fallback = document.createElement('div');
                fallback.className = 'history-thumb-fallback';
                fallback.textContent = '🎬';
                item.appendChild(fallback);
            }

            const content = document.createElement('div');
            content.className = 'history-content';

            const title = document.createElement('p');
            title.className = 'history-title';
            title.textContent = entry.title || entry.filename || '未命名影片';

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
            copyBtn.addEventListener('click', () => copyUrl(entry.url, '已複製影片連結！'));

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
