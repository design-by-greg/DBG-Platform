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

    function updateLabel(input, label) {
        if (!label) {
            return;
        }

        var count = input.files ? input.files.length : 0;
        label.textContent = count > 0 ? count + ' file(s) selected' : 'Drop files here or click to select';
    }
});
