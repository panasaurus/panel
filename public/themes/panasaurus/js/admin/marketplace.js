(function () {
    'use strict';

    var root = document.querySelector('[data-marketplace]');
    if (!root) return;

    var form = root.querySelector('[data-marketplace-filters]');
    root.querySelectorAll('[data-marketplace-select]').forEach(function (select) {
        select.addEventListener('change', function () {
            form.requestSubmit();
        });
    });

    function showFallback(image) {
        image.hidden = true;
        image.parentElement.querySelector('.marketplace-preview-fallback').hidden = false;
    }

    root.querySelectorAll('[data-marketplace-preview]').forEach(function (image) {
        image.addEventListener('error', function () { showFallback(image); });
        if (image.complete && image.naturalWidth === 0) showFallback(image);
    });

    if (window.jQuery) {
        window.jQuery('.marketplace-install-modal').on('hidden.bs.modal', function () {
            var trigger = root.querySelector('[data-target="#' + this.id + '"]');
            if (trigger) trigger.focus();
        });
    }
})();
