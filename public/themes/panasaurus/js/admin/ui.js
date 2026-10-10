(function () {
    'use strict';

    function initAdminUI() {
        const menuToggle = document.querySelector('[data-admin-menu-toggle]');
        const sidebar = document.querySelector('#admin-sidebar');
        const mobile = window.matchMedia('(max-width: 767px)');
        const main = document.querySelector('#admin-main');
        const footer = document.querySelector('.main-footer');
        const navbarControls = document.querySelector('.navbar-custom-menu');

        function setMenu(open, restoreFocus) {
            open = open && mobile.matches;
            document.body.classList.toggle('admin-menu-open', open);
            menuToggle.setAttribute('aria-expanded', String(open));
            menuToggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
            sidebar.inert = mobile.matches && !open;
            main.inert = open;
            footer.inert = open;
            navbarControls.inert = open;
            if (open) sidebar.querySelector('a[aria-current="page"], a').focus();
            else if (restoreFocus) menuToggle.focus();
        }

        menuToggle.addEventListener('click', function () { setMenu(!document.body.classList.contains('admin-menu-open'), true); });
        document.querySelector('[data-admin-menu-close]').addEventListener('click', function () { setMenu(false, true); });
        sidebar.addEventListener('click', function (event) { if (event.target.closest('a') && mobile.matches) setMenu(false, false); });
        mobile.addEventListener('change', function () { setMenu(false, false); });
        setMenu(false, false);

        const dialog = document.querySelector('#admin-command-dialog');
        const search = document.querySelector('#admin-command-search');
        const results = Array.from(document.querySelectorAll('#admin-command-results a'));
        const empty = document.querySelector('.admin-command-empty');
        const openSearch = document.querySelector('[data-admin-search-open]');
        document.querySelector('[data-admin-shortcut]').textContent = /Mac|iPhone|iPad/.test(navigator.platform) ? '⌘ K' : 'Ctrl K';

        function filterPages() {
            const query = search.value.trim().toLowerCase();
            let matches = 0;
            results.forEach(function (result) { result.hidden = !result.dataset.searchLabel.toLowerCase().includes(query); if (!result.hidden) matches++; });
            empty.hidden = matches > 0;
        }

        function showSearch() {
            setMenu(false, false);
            search.value = '';
            filterPages();
            dialog.showModal();
            search.focus();
        }

        openSearch.addEventListener('click', showSearch);
        document.querySelector('[data-admin-search-close]').addEventListener('click', function () { dialog.close(); });
        search.addEventListener('input', filterPages);
        dialog.addEventListener('click', function (event) {
            if (event.target !== dialog) return;
            const rect = dialog.getBoundingClientRect();
            if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close();
        });
        search.addEventListener('keydown', function (event) {
            const first = results.find(function (result) { return !result.hidden; });
            if (event.key === 'ArrowDown' && first) { event.preventDefault(); first.focus(); }
            if (event.key === 'Enter' && first) { event.preventDefault(); first.click(); }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && dialog.open) { event.preventDefault(); dialog.close(); }
            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                if (dialog.open) dialog.close(); else showSearch();
            }
            if (event.key === 'Escape' && document.body.classList.contains('admin-menu-open')) setMenu(false, true);
            if (event.key === 'Tab' && document.body.classList.contains('admin-menu-open')) {
                const links = Array.from(sidebar.querySelectorAll('a'));
                const first = menuToggle;
                const last = links[links.length - 1];
                if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
                else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
            }
        });

        const healthRows = Array.from(document.querySelectorAll('[data-admin-health]'));
        if (!healthRows.length) return;
        const refresh = document.querySelector('[data-admin-health-refresh]');
        const checked = document.querySelector('[data-admin-health-checked]');

        async function checkHealth() {
            refresh.disabled = true;
            checked.textContent = 'Checking your panel services…';
            try {
                const response = await fetch('/api/health', { headers: { Accept: 'application/json' }, signal: AbortSignal.timeout(10000) });
                if (response.status !== 200 && response.status !== 503) throw new Error('Service check unavailable');
                const data = await response.json();
                if (!data.checks) throw new Error('Invalid service status');
                healthRows.forEach(function (row) {
                    const check = data.checks[row.dataset.adminHealth];
                    const healthy = check && check.healthy === true;
                    row.className = 'admin-status admin-status--' + (healthy ? 'success' : 'danger');
                    row.textContent = check && check.skipped ? 'Not enabled' : (healthy ? 'Operational' : 'Needs attention');
                });
                checked.textContent = 'Checked ' + new Intl.DateTimeFormat(undefined, { hour: 'numeric', minute: '2-digit', second: '2-digit' }).format(new Date()) + (data.status === 'ok' ? ' · All services healthy' : ' · Check the services marked above');
            } catch (error) {
                healthRows.forEach(function (row) { row.className = 'admin-status admin-status--warning'; row.textContent = 'Unavailable'; });
                checked.textContent = 'Could not check services. Use the refresh button to try again.';
            } finally { refresh.disabled = false; }
        }

        refresh.addEventListener('click', checkHealth);
        checkHealth();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAdminUI);
    else initAdminUI();
})();
