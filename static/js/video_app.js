document.addEventListener('DOMContentLoaded', () => {
    const dropZone = document.getElementById('dropZone');
    const videoInput = document.getElementById('videoInput');
    const uploadPrompt = document.getElementById('uploadPrompt');
    const queueArea = document.getElementById('queueArea');
    const fileList = document.getElementById('fileList');
    const uploadBtn = document.getElementById('uploadBtn');
    const cancelBtn = document.getElementById('cancelBtn');

    let uploadQueue = [];
    let isUploading = false;

    // Trigger file select
    dropZone.addEventListener('click', (e) => {
        if (!isUploading && e.target !== uploadBtn && e.target !== cancelBtn && !e.target.closest('.queue-item')) {
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
            // All done
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
        const descInput = document.getElementById('desc_' + id).value.trim();
        
        inputsDiv.style.display = 'none'; // Hide inputs during/after upload
        
        const progCont = document.getElementById('progCont_' + id);
        const progBar = document.getElementById('progBar_' + id);
        progCont.style.display = 'block';

        const formData = new FormData();
        formData.append('file', item.file);
        if (titleInput) formData.append('title', titleInput);
        if (descInput) formData.append('description', descInput);
        
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
});