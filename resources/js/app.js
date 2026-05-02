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
            info.textContent = `${start}-${end} dari ${matched.length} data`;
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

    if (openButton) {
        document.getElementById(openButton.dataset.modalOpen)?.showModal();
    }

    if (closeButton) {
        document.getElementById(closeButton.dataset.modalClose)?.close();
    }

    if (sidebarButton) {
        document.documentElement.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebar-collapsed', document.documentElement.classList.contains('sidebar-collapsed') ? '1' : '0');
    }
});

if (localStorage.getItem('sidebar-collapsed') === '1') {
    document.documentElement.classList.add('sidebar-collapsed');
}
