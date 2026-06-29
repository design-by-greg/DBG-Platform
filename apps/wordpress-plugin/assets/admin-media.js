document.addEventListener('DOMContentLoaded', function () {
    var dropzones = document.querySelectorAll('[data-dbg-dropzone]');

    dropzones.forEach(function (dropzone) {
        var input = dropzone.querySelector('input[type="file"]');
        var label = dropzone.querySelector('[data-dbg-dropzone-label]');

        if (!input) {
            return;
        }

        ['dragenter', 'dragover'].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (event) {
                event.preventDefault();
                event.stopPropagation();
                dropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (event) {
                event.preventDefault();
                event.stopPropagation();
                dropzone.classList.remove('is-dragover');
            });
        });

        dropzone.addEventListener('drop', function (event) {
            if (!event.dataTransfer || !event.dataTransfer.files.length) {
                return;
            }

            input.files = event.dataTransfer.files;
            updateLabel(input, label);
        });

        dropzone.addEventListener('click', function (event) {
            if (event.target.tagName.toLowerCase() === 'input') {
                return;
            }
            input.click();
        });

        input.addEventListener('change', function () {
            updateLabel(input, label);
        });
    });

    var uploadForms = document.querySelectorAll('[data-dbg-upload-form]');

    uploadForms.forEach(function (form) {
        var progress = form.querySelector('[data-dbg-upload-progress]');
        var progressBar = form.querySelector('[data-dbg-upload-progress-bar]');
        var progressText = form.querySelector('[data-dbg-upload-progress-text]');
        var submit = form.querySelector('button[type="submit"], button:not([type])');

        if (!window.DBGPlatformMedia || !window.DBGPlatformMedia.ajaxUrl) {
            return;
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var data = new FormData(form);
            data.set('action', 'dbg_ajax_upload_media');
            data.set('nonce', window.DBGPlatformMedia.nonce);

            var request = new XMLHttpRequest();
            request.open('POST', window.DBGPlatformMedia.ajaxUrl, true);

            if (progress) {
                progress.hidden = false;
            }
            setProgress(progressBar, progressText, 0, 'Preparing upload...');

            if (submit) {
                submit.disabled = true;
            }

            request.upload.addEventListener('progress', function (event) {
                if (!event.lengthComputable) {
                    setProgress(progressBar, progressText, 0, 'Uploading...');
                    return;
                }

                var percent = Math.round((event.loaded / event.total) * 100);
                setProgress(progressBar, progressText, percent, percent + '% uploaded');
            });

            request.onreadystatechange = function () {
                if (request.readyState !== XMLHttpRequest.DONE) {
                    return;
                }

                if (submit) {
                    submit.disabled = false;
                }

                var response = null;
                try {
                    response = JSON.parse(request.responseText);
                } catch (error) {
                    setProgress(progressBar, progressText, 100, 'Upload completed, refreshing...');
                    window.location.reload();
                    return;
                }

                if (request.status >= 200 && request.status < 300 && response && response.success) {
                    setProgress(progressBar, progressText, 100, response.data.message || 'Upload completed');
                    window.setTimeout(function () {
                        window.location.reload();
                    }, 700);
                    return;
                }

                var message = response && response.data && response.data.message ? response.data.message : 'Upload failed';
                setProgress(progressBar, progressText, 100, message);
            };

            request.send(data);
        });
    });

    buildViewer();

    document.querySelectorAll('[data-dbg-viewer-open]').forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            openViewer(trigger.getAttribute('data-dbg-viewer-open'), trigger.getAttribute('data-dbg-viewer-type'), trigger.getAttribute('data-dbg-viewer-title'));
        });
    });

    function buildViewer() {
        if (document.querySelector('[data-dbg-viewer]')) {
            return;
        }

        var viewer = document.createElement('div');
        viewer.className = 'dbg-viewer';
        viewer.setAttribute('data-dbg-viewer', '');
        viewer.hidden = true;
        viewer.innerHTML = '<div class="dbg-viewer-backdrop" data-dbg-viewer-close></div><div class="dbg-viewer-dialog"><button type="button" class="dbg-viewer-close" data-dbg-viewer-close>×</button><h2 data-dbg-viewer-title></h2><div class="dbg-viewer-content" data-dbg-viewer-content></div></div>';
        document.body.appendChild(viewer);

        viewer.querySelectorAll('[data-dbg-viewer-close]').forEach(function (close) {
            close.addEventListener('click', closeViewer);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeViewer();
            }
        });
    }

    function openViewer(url, type, title) {
        var viewer = document.querySelector('[data-dbg-viewer]');
        var content = viewer.querySelector('[data-dbg-viewer-content]');
        var titleNode = viewer.querySelector('[data-dbg-viewer-title]');

        titleNode.textContent = title || 'Preview';
        content.innerHTML = '';

        if (type === 'image') {
            var image = document.createElement('img');
            image.src = url;
            image.alt = title || 'Preview';
            content.appendChild(image);
        } else if (type === 'pdf') {
            var frame = document.createElement('iframe');
            frame.src = url;
            frame.title = title || 'PDF preview';
            content.appendChild(frame);
        } else {
            var link = document.createElement('a');
            link.href = url;
            link.target = '_blank';
            link.rel = 'noopener';
            link.textContent = 'Open file';
            content.appendChild(link);
        }

        viewer.hidden = false;
        document.body.classList.add('dbg-viewer-open');
    }

    function closeViewer() {
        var viewer = document.querySelector('[data-dbg-viewer]');
        if (!viewer) {
            return;
        }
        viewer.hidden = true;
        document.body.classList.remove('dbg-viewer-open');
    }

    function updateLabel(input, label) {
        if (!label) {
            return;
        }

        var count = input.files ? input.files.length : 0;
        label.textContent = count > 0 ? count + ' file(s) selected' : 'Drop files here or click to select';
    }

    function setProgress(progressBar, progressText, percent, text) {
        if (progressBar) {
            progressBar.style.width = percent + '%';
            progressBar.setAttribute('aria-valuenow', String(percent));
        }

        if (progressText) {
            progressText.textContent = text;
        }
    }
});
