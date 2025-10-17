import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    const toggleButtons = document.querySelectorAll('[data-toggle-password]');
    toggleButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-toggle-password');
            const target = document.getElementById(targetId);

            if (!target) {
                return;
            }

            const isPassword = target.getAttribute('type') === 'password';
            target.setAttribute('type', isPassword ? 'text' : 'password');

            const icon = button.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-eye', !isPassword);
                icon.classList.toggle('bi-eye-slash', isPassword);
            }
        });
    });

    const activeAuthTab = document.body && document.body.dataset
        ? document.body.dataset.authActive
        : null;
    if (activeAuthTab && window.bootstrap && window.bootstrap.Tab) {
        const trigger = document.querySelector(`[data-bs-target="#${activeAuthTab}"]`);
        if (trigger) {
            const tab = window.bootstrap.Tab.getOrCreateInstance(trigger);
            tab.show();
        }
    }

    const sidebarToggleButtons = document.querySelectorAll('[data-sidebar-toggle]');
    const sidebarBackdrop = document.querySelector('[data-sidebar-backdrop]');
    const sidebar = document.querySelector('[data-sidebar]');

    const closeSidebar = () => {
        document.body.classList.remove('sidebar-open');
    };

    const toggleSidebar = () => {
        if (!sidebar) {
            return;
        }
        document.body.classList.toggle('sidebar-open');
    };

    sidebarToggleButtons.forEach(button => {
        button.addEventListener('click', event => {
            event.preventDefault();
            toggleSidebar();
        });
    });

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', () => {
            closeSidebar();
        });
    }

    const largeScreenQuery = window.matchMedia('(min-width: 992px)');
    if (largeScreenQuery) {
        const handleBreakpoint = event => {
            if (event.matches) {
                closeSidebar();
            }
        };

        if (typeof largeScreenQuery.addEventListener === 'function') {
            largeScreenQuery.addEventListener('change', handleBreakpoint);
        } else if (typeof largeScreenQuery.addListener === 'function') {
            largeScreenQuery.addListener(handleBreakpoint);
        }
    }

    const accordionButtons = document.querySelectorAll('[data-sidebar-accordion]');
    accordionButtons.forEach(button => {
        const targetSelector = button.getAttribute('data-sidebar-target');
        const parentItem = button.closest('.sidebar-item');

        button.addEventListener('click', event => {
            event.preventDefault();
            const target = targetSelector ? document.querySelector(targetSelector) : null;
            const isExpanded = button.getAttribute('aria-expanded') === 'true';
            const nextExpanded = !isExpanded;

            button.setAttribute('aria-expanded', nextExpanded.toString());

            if (target) {
                target.classList.toggle('show', nextExpanded);
            }

            if (parentItem) {
                parentItem.classList.toggle('is-open', nextExpanded);
            }
        });
    });
});
