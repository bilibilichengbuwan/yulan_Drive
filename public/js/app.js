
function openModal(id) {
    var m = document.getElementById(id);
    if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
    var m = document.getElementById(id);
    if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.style.display = 'none';
        document.body.style.overflow = '';
    }
});

function apiPost(url, data, callback, errorCallback) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) xhr.setRequestHeader('X-CSRF-Token', csrfMeta.content);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            try {
                var json = JSON.parse(xhr.responseText);
                if (json.code === 0) {
                    if (callback) callback(json);
                } else {
                    showAlert(json.message, 'danger');
                    if (errorCallback) errorCallback(json);
                }
            } catch(err) {
                showAlert('请求失败', 'danger');
            }
        }
    };
    xhr.send(JSON.stringify(data));
}

function showAlert(message, type) {
    var alertBox = document.getElementById('alert-box');
    if (alertBox) {
        var div = document.createElement('div');
        div.className = 'alert-' + type;
        div.textContent = message;
        alertBox.innerHTML = '';
        alertBox.appendChild(div);
        setTimeout(function() { alertBox.innerHTML = ''; }, 5000);
    } else {
        alert(message);
    }
}

function showCreateFolder() {
    document.getElementById('folderName').value = '';
    openModal('createFolderModal');
}

function createFolder() {
    var name = document.getElementById('folderName').value.trim();
    if (!name) { alert('请输入文件夹名称'); return; }
    apiPost('/api/folder/create', { parent_id: currentFolder, name: name }, function() {
        location.reload();
    });
}

function showRename(id, name) {
    document.getElementById('renameId').value = id;
    document.getElementById('renameName').value = name;
    openModal('renameModal');
}

function renameItem() {
    var id = document.getElementById('renameId').value;
    var name = document.getElementById('renameName').value.trim();
    if (!name) { alert('请输入名称'); return; }
    apiPost('/api/rename', { id: id, name: name }, function() {
        location.reload();
    });
}

function deleteItem(id) {
    if (!confirm('确定要删除此文件吗？')) return;
    apiPost('/api/delete', { ids: [id] }, function() {
        location.reload();
    });
}

function downloadFile(id) {
    apiPost('/api/download-token', { file_id: id }, function(json) {
        window.location.href = '/download?token=' + json.data.token;
    });
}

function showShare(id, name) {
    document.getElementById('shareFileId').value = id;
    document.getElementById('shareFileName').textContent = name;
    document.getElementById('sharePassword').value = '';
    document.getElementById('shareExpire').value = '7';
    document.getElementById('shareMaxDownloads').value = '0';
    openModal('shareModal');
}

function createShare() {
    var fileId = document.getElementById('shareFileId').value;
    var password = document.getElementById('sharePassword').value;
    var expireDays = document.getElementById('shareExpire').value;
    var maxDownloads = document.getElementById('shareMaxDownloads').value;
    
    apiPost('/api/share/create', {
        file_id: fileId,
        password: password,
        expire_days: expireDays,
        max_downloads: maxDownloads
    }, function(json) {
        closeModal('shareModal');
        document.getElementById('shareLink').value = window.location.origin + '/share/' + json.data.share_code;
        openModal('shareResultModal');
    });
}

function copyShareLink() {
    var link = document.getElementById('shareLink');
    link.select();
    link.setSelectionRange(0, 99999);
    document.execCommand('copy');
    alert('链接已复制到剪贴板');
}

function uploadFiles(files) {
    for (var i = 0; i < files.length; i++) {
        (function(file) {
            var formData = new FormData();
            formData.append('file', file);
            formData.append('parent_id', currentFolder);
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '/api/upload', true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) xhr.setRequestHeader('X-CSRF-Token', csrfMeta.content);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    try {
                        var json = JSON.parse(xhr.responseText);
                        if (json.code !== 0) {
                            showAlert(json.message, 'danger');
                        }
                    } catch(err) {
                        showAlert('上传失败: ' + file.name, 'danger');
                    }
                }
            };
            xhr.send(formData);
        })(files[i]);
    }
    setTimeout(function() { location.reload(); }, 2000);
}

function showChunkUpload() {
    openModal('chunkUploadModal');
}

function startChunkUpload() {
    var fileInput = document.getElementById('chunkFileInput');
    if (!fileInput.files.length) { alert('请选择文件'); return; }
    
    var file = fileInput.files[0];
    var chunkSize = 5 * 1024 * 1024;
    var totalChunks = Math.ceil(file.size / chunkSize);
    var fileMd5 = (window.crypto && crypto.randomUUID) ? crypto.randomUUID().replace(/-/g, '') : (Date.now().toString(36) + Math.random().toString(36).slice(2));
    
    var progressBar = document.querySelector('#chunkProgress .progress-bar');
    var statusDiv = document.getElementById('chunkStatus');
    
    document.getElementById('chunkProgress').style.display = 'block';
    statusDiv.innerHTML = '开始上传...';
    
    var i = 0;
    function uploadNextChunk() {
        if (i >= totalChunks) {
            statusDiv.innerHTML = '<span style="color:#10b981">上传完成！</span>';
            setTimeout(function() { location.reload(); }, 1000);
            return;
        }
        
        var start = i * chunkSize;
        var end = Math.min(start + chunkSize, file.size);
        var chunk = file.slice(start, end);
        
        var formData = new FormData();
        formData.append('chunk', chunk);
        formData.append('chunk_index', i);
        formData.append('total_chunks', totalChunks);
        formData.append('file_md5', fileMd5);
        formData.append('file_name', file.name);
        formData.append('file_size', file.size);
        formData.append('parent_id', currentFolder);
        
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/api/upload-chunk', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) xhr.setRequestHeader('X-CSRF-Token', csrfMeta.content);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    var json = JSON.parse(xhr.responseText);
                    var percent = Math.round((i + 1) / totalChunks * 100);
                    progressBar.style.width = percent + '%';
                    progressBar.textContent = percent + '%';
                    statusDiv.innerHTML = '已上传 ' + (i + 1) + '/' + totalChunks + ' 分片';
                    
                    if (json.code !== 0) {
                        statusDiv.innerHTML = '<span style="color:#e5484d">上传失败: ' + json.message + '</span>';
                        return;
                    }
                    i++;
                    uploadNextChunk();
                } catch(err) {
                    statusDiv.innerHTML = '<span style="color:#e5484d">上传失败</span>';
                }
            }
        };
        xhr.send(formData);
    }
    uploadNextChunk();
}

function toggleSelectAll(checkbox) {
    var checkboxes = document.querySelectorAll('.file-checkbox');
    for (var j = 0; j < checkboxes.length; j++) {
        checkboxes[j].checked = checkbox.checked;
    }
    updateBatchActions();
}

function updateBatchActions() {
    var selected = getSelectedIds();
    var batchDiv = document.getElementById('batch-actions');
    var countSpan = document.getElementById('selected-count');
    if (selected.length > 0) {
        batchDiv.style.display = 'flex';
        countSpan.textContent = '已选 ' + selected.length + ' 项';
    } else {
        batchDiv.style.display = 'none';
    }
}

function getSelectedIds() {
    var checkboxes = document.querySelectorAll('.file-checkbox:checked');
    var ids = [];
    for (var j = 0; j < checkboxes.length; j++) {
        ids.push(parseInt(checkboxes[j].value));
    }
    return ids;
}

function batchDelete() {
    var ids = getSelectedIds();
    if (!ids.length) { alert('请选择文件'); return; }
    if (!confirm('确定要删除选中的文件吗？')) return;
    apiPost('/api/delete', { ids: ids }, function() { location.reload(); });
}

function showBatchMove() {
    var ids = getSelectedIds();
    if (!ids.length) { alert('请选择文件'); return; }
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '/api/folder-tree', true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            try {
                var json = JSON.parse(xhr.responseText);
                if (json.code === 0) {
                    document.getElementById('folderTree').innerHTML = json.data.html;
                    openModal('moveModal');
                }
            } catch(err) {}
        }
    };
    xhr.send();
}

function moveItems() {
    var ids = getSelectedIds();
    var checked = document.querySelector('#folderTree input[name="target"]:checked');
    var targetId = checked ? checked.value : 0;
    if (!ids.length) { alert('请选择文件'); return; }
    apiPost('/api/move', { ids: ids, target_id: targetId }, function() { location.reload(); });
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        var fn = document.getElementById('folderName');
        if (fn && fn === document.activeElement) {
            createFolder();
        }
        var rn = document.getElementById('renameName');
        if (rn && rn === document.activeElement) {
            renameItem();
        }
    }
});
