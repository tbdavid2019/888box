document.addEventListener('DOMContentLoaded', () => {
    const dropZone = document.getElementById('dropZone');
    const videoInput = document.getElementById('videoInput');
    const uploadPrompt = document.getElementById('uploadPrompt');
    const previewArea = document.getElementById('previewArea');
    const videoPlayer = document.getElementById('videoPlayer');
    const fileInfo = document.getElementById('fileInfo');
    const uploadBtn = document.getElementById('uploadBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const progressContainer = document.getElementById('progressContainer');
    const progressBar = document.getElementById('progressBar');
    
    const resultPanel = document.getElementById('resultPanel');
    const newUploadBtn = document.getElementById('newUploadBtn');

    let currentFile = null;
    let xhr = null;
    let objectUrl = null;

    // Trigger file select
    dropZone.addEventListener('click', (e) => {
        if (e.target !== uploadBtn && e.target !== cancelBtn && e.target !== videoPlayer) {
            videoInput.click();
        }
    });

    videoInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });

    // Drag and Drop
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });
    
    dropZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
    });
    
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });

    // Paste
    document.addEventListener('paste', (e) => {
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
        const file = files[0];
        
        // 驗證是否為影片 (很寬鬆的判斷，只要 MimeType 有 video 或是副檔名是影片即可)
        const isVideo = file.type.startsWith('video/') || file.name.match(/\.(mp4|webm|mov|mkv|avi)$/i);
        if (!isVideo) {
            alert('請上傳影片檔案！若要上傳圖片請至首頁。');
            return;
        }

        currentFile = file;
        
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
        }
        objectUrl = URL.createObjectURL(file);
        
        uploadPrompt.style.display = 'none';
        previewArea.style.display = 'block';
        videoPlayer.src = objectUrl;
        
        const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
        fileInfo.textContent = `檔案準備就緒: ${file.name} (${sizeMB} MB)`;
    }

    cancelBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        resetUI();
    });

    uploadBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        if (!currentFile) return;
        startUpload(currentFile);
    });

    newUploadBtn.addEventListener('click', () => {
        resultPanel.style.display = 'none';
        dropZone.style.display = 'block';
        resetUI();
    });

    function startUpload(file) {
        uploadBtn.disabled = true;
        cancelBtn.disabled = true;
        progressContainer.style.display = 'block';
        
        const formData = new FormData();
        formData.append('file', file);
        
        xhr = new XMLHttpRequest();
        xhr.open('POST', 'video.php', true);
        
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percent + '%';
                progressBar.textContent = percent + '%';
            }
        });

        xhr.onload = () => {
            uploadBtn.disabled = false;
            cancelBtn.disabled = false;
            if (xhr.status === 200) {
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (res.result === 'success') {
                        showSuccess(res.data);
                    } else {
                        alert('上傳失敗: ' + (res.message || '未知錯誤'));
                        resetProgress();
                    }
                } catch (err) {
                    alert('伺服器回應異常！');
                    resetProgress();
                }
            } else {
                alert('伺服器錯誤: ' + xhr.status);
                resetProgress();
            }
        };

        xhr.onerror = () => {
            uploadBtn.disabled = false;
            cancelBtn.disabled = false;
            alert('網路錯誤，上傳中斷！');
            resetProgress();
        };

        xhr.send(formData);
    }

    function showSuccess(data) {
        dropZone.style.display = 'none';
        resultPanel.style.display = 'block';
        
        document.getElementById('resVideoUrl').value = data.url;
        document.getElementById('resThumbUrl').value = data.thumbnail_url || '無';
        
        const resStr = (data.metadata && data.metadata.width) ? `${data.metadata.width}x${data.metadata.height}` : '未知';
        const durStr = (data.metadata && data.metadata.duration) ? `${Math.round(data.metadata.duration)} 秒` : '未知';
        const sizeMB = (data.size / (1024 * 1024)).toFixed(2);

        document.getElementById('resRes').textContent = resStr;
        document.getElementById('resDur').textContent = durStr;
        document.getElementById('resSize').textContent = sizeMB + ' MB';
    }

    function resetUI() {
        currentFile = null;
        if (xhr) { xhr.abort(); xhr = null; }
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
        uploadPrompt.style.display = 'block';
        previewArea.style.display = 'none';
        videoPlayer.src = '';
        videoInput.value = '';
        resetProgress();
    }

    function resetProgress() {
        progressContainer.style.display = 'none';
        progressBar.style.width = '0%';
        progressBar.textContent = '0%';
    }

    window.copyToClipboard = function(id) {
        const el = document.getElementById(id);
        el.select();
        document.execCommand('copy');
        alert('已複製到剪貼簿！');
    };
});