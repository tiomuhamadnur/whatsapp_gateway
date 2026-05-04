import './bootstrap';

function bootDataTables() {
    document.querySelectorAll('[data-data-table]').forEach((wrapper) => {
        const table = wrapper.querySelector('table');
        const search = wrapper.querySelector('[data-data-table-search]');
        const info = wrapper.querySelector('[data-data-table-info]');
        const pageLabel = wrapper.querySelector('[data-data-table-page]');
        const prev = wrapper.querySelector('[data-data-table-prev]');
        const next = wrapper.querySelector('[data-data-table-next]');
        const filterHost = wrapper.querySelector('[data-data-table-filters]');

        if (!table || !search || !info || !pageLabel || !prev || !next || !filterHost) {
            return;
        }

        const rows = Array.from(table.querySelectorAll('tbody tr'));
        const headers = Array.from(table.querySelectorAll('thead th'));
        const perPage = Number(wrapper.dataset.perPage || 10);
        let page = 1;
        const filters = [];

        headers.forEach((header, index) => {
            const label = header.textContent.trim();
            if (!label || label.toLowerCase() === 'update' || label.toLowerCase() === 'edit') {
                return;
            }

            const input = document.createElement('input');
            input.type = 'search';
            input.placeholder = `Filter ${label}`;
            input.className = 'rounded-md border border-zinc-300 px-2 py-1.5 text-xs outline-none focus:border-zinc-950';
            input.addEventListener('input', () => {
                page = 1;
                render();
            });
            filterHost.appendChild(input);
            filters.push({ index, input });
        });

        const render = () => {
            const keyword = search.value.trim().toLowerCase();
            const matched = rows.filter((row) => {
                const cells = Array.from(row.children);
                const globalMatch = row.textContent.toLowerCase().includes(keyword);
                const columnMatch = filters.every(({ index, input }) => {
                    const value = input.value.trim().toLowerCase();
                    return value === '' || (cells[index]?.textContent || '').toLowerCase().includes(value);
                });

                return globalMatch && columnMatch;
            });
            const totalPages = Math.max(1, Math.ceil(matched.length / perPage));
            page = Math.min(page, totalPages);

            rows.forEach((row) => {
                row.hidden = true;
            });

            matched.slice((page - 1) * perPage, page * perPage).forEach((row) => {
                row.hidden = false;
            });

            const start = matched.length === 0 ? 0 : (page - 1) * perPage + 1;
            const end = Math.min(page * perPage, matched.length);
            info.textContent = `${start}-${end} of ${matched.length.toLocaleString()} records`;
            pageLabel.textContent = `${page} / ${totalPages}`;
            prev.disabled = page <= 1;
            next.disabled = page >= totalPages;
            prev.classList.toggle('opacity-50', prev.disabled);
            next.classList.toggle('opacity-50', next.disabled);
        };

        search.addEventListener('input', () => {
            page = 1;
            render();
        });

        prev.addEventListener('click', () => {
            page -= 1;
            render();
        });

        next.addEventListener('click', () => {
            page += 1;
            render();
        });

        render();
    });
}

document.addEventListener('DOMContentLoaded', bootDataTables);

document.addEventListener('click', (event) => {
    const openButton = event.target.closest('[data-modal-open]');
    const closeButton = event.target.closest('[data-modal-close]');
    const sidebarButton = event.target.closest('[data-sidebar-toggle]');
    const sidebarClose = event.target.closest('[data-sidebar-close], [data-sidebar-backdrop], [data-sidebar-link]');
    const confirmButton = event.target.closest('button[data-confirm], a[data-confirm]');
    const profileToggle = event.target.closest('[data-profile-toggle]');

    if (openButton) {
        document.getElementById(openButton.dataset.modalOpen)?.showModal();
    }

    if (closeButton) {
        document.getElementById(closeButton.dataset.modalClose)?.close();
    }

    if (sidebarButton) {
        if (window.matchMedia('(min-width: 1024px)').matches) {
            document.documentElement.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebar-collapsed', document.documentElement.classList.contains('sidebar-collapsed') ? '1' : '0');
        } else {
            document.documentElement.classList.toggle('sidebar-open');
        }
    }

    if (sidebarClose && !window.matchMedia('(min-width: 1024px)').matches) {
        document.documentElement.classList.remove('sidebar-open');
    }

    if (profileToggle) {
        event.preventDefault();
        const dropdown = profileToggle.closest('[data-profile-menu]')?.querySelector('[data-profile-dropdown]');
        if (dropdown) {
            dropdown.classList.toggle('hidden');
        }
    }

    // Close profile dropdown when clicking outside
    if (!event.target.closest('[data-profile-menu]')) {
        document.querySelectorAll('[data-profile-dropdown]').forEach(el => el.classList.add('hidden'));
    }

    if (confirmButton && !confirmButton.dataset.confirmed) {
        event.preventDefault();
        const modal = document.getElementById('confirm-action-modal');
        const message = document.getElementById('confirm-action-message');
        const ok = modal?.querySelector('[data-confirm-ok]');
        const cancel = modal?.querySelector('[data-confirm-cancel]');

        if (!modal || !ok || !cancel) {
            return;
        }

        message.textContent = confirmButton.dataset.confirm || 'Are you sure you want to continue?';
        modal.showModal();

        ok.onclick = () => {
            confirmButton.dataset.confirmed = '1';
            modal.close();
            confirmButton.click();
            delete confirmButton.dataset.confirmed;
        };

        cancel.onclick = () => modal.close();
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target.closest('form[data-confirm]');

    if (!form || form.dataset.confirmed) {
        return;
    }

    event.preventDefault();

    const modal = document.getElementById('confirm-action-modal');
    const message = document.getElementById('confirm-action-message');
    const ok = modal?.querySelector('[data-confirm-ok]');
    const cancel = modal?.querySelector('[data-confirm-cancel]');

    if (!modal || !ok || !cancel) {
        return;
    }

    message.textContent = form.dataset.confirm || 'Are you sure you want to continue?';
    modal.showModal();

    ok.onclick = () => {
        form.dataset.confirmed = '1';
        modal.close();
        form.requestSubmit();
        delete form.dataset.confirmed;
    };

    cancel.onclick = () => modal.close();
});

if (localStorage.getItem('sidebar-collapsed') === '1') {
    document.documentElement.classList.add('sidebar-collapsed');
}

document.addEventListener('DOMContentLoaded', () => {
    const typeSelect = document.querySelector('select[name="type"]');
    if (typeSelect) {
        const toggleFields = () => {
            const selectedType = typeSelect.value;
            document.querySelectorAll('.location-fields').forEach(el => el.style.display = selectedType === 'location' ? 'block' : 'none');
            document.querySelectorAll('.buttons-fields').forEach(el => el.style.display = selectedType === 'buttons' ? 'block' : 'none');
        };
        typeSelect.addEventListener('change', toggleFields);
        toggleFields(); // Initial call
    }
});
