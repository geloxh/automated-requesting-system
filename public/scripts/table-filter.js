/**
 * 
 * Client-side search + filter for all data tables.
 *
 * Usage: add data attributes to the <table>:
 *   data-filterable          — enables this script
 *   data-search-col="0,1,2" — comma-separated column indices to search
 *   data-filter-col="2"     — column index for the type/status dropdown
 *
 * Then add inside .table-wrap, before <table>:
 *   <div class="filter-bar" data-filter-bar>
 *     <input type="search" placeholder="Search..." data-search-input>
 *     <select data-filter-select>
 *       <option value="">All types</option>
 *       ... options ...
 *     </select>
 *     <span class="filter-count" data-filter-count></span>
 *   </div>
 */

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('table[data-filterable]').forEach(function (table) {
        const wrap        = table.closest('.table-wrap');
        if (!wrap) return;

        const searchInput  = wrap.querySelector('[data-search-input]');
        const filterSelect = wrap.querySelector('[data-filter-select]');
        const countEl      = wrap.querySelector('[data-filter-count]');
        const rows         = Array.from(table.querySelectorAll('tbody tr'));

        const searchCols = (table.dataset.searchCol || '0').split(',').map(Number);
        const filterCol  = table.dataset.filterCol !== undefined ? parseInt(table.dataset.filterCol) : null;

        function getCell(row, colIndex) {
            return (row.cells[colIndex]?.textContent || '').toLowerCase().trim();
        }

        function applyFilter() {
            const query      = (searchInput?.value || '').toLowerCase().trim();
            const filterVal  = (filterSelect?.value || '').toLowerCase().trim();
            let   visible    = 0;

            rows.forEach(function (row) {
                const matchesSearch = !query || searchCols.some(function (col) {
                    return getCell(row, col).includes(query);
                });

                const matchesFilter = !filterVal || filterCol === null || (
                    getCell(row, filterCol).includes(filterVal)
                );

                const show = matchesSearch && matchesFilter;
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            if (countEl) {
                countEl.textContent = visible + ' of ' + rows.length + ' result' + (rows.length !== 1 ? 's' : '');
            }
        }

        if (searchInput)  searchInput.addEventListener('input', applyFilter);
        if (filterSelect) filterSelect.addEventListener('change', applyFilter);

        // Initial count
        applyFilter();
    });
});