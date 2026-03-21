/**
 * form-table.js
 * Handles dynamic row add/remove and optional column recalculation
 * for all form tables in the processing system.
 *
 * Usage: add data attributes to the table:
 *   data-table-id   — the table's id
 *   data-recalc     — "items" | "amount-only" | "ot" | none
 *   data-total-id   — id of the total input to update
 */

function recalcItems(tableId, totalId) {
    let total = 0;
    document.querySelectorAll(`#${tableId} tbody tr`).forEach(row => {
        const price = parseFloat(row.querySelector('.unit-price')?.value) || 0;
        const qty   = parseFloat(row.querySelector('.qty')?.value) || 0;
        const amt   = price * qty;
        const amtEl = row.querySelector('.row-amount');
        if (amtEl) amtEl.value = amt.toFixed(2);
        total += amt;
    });
    const totalEl = document.getElementById(totalId);
    if (totalEl) totalEl.value = total.toFixed(2);
}

function recalcAmounts(tableId, totalId, balanceId, advanceId) {
    let total = 0;
    document.querySelectorAll(`#${tableId} .row-amount`).forEach(i => total += parseFloat(i.value) || 0);
    const totalEl = document.getElementById(totalId);
    if (totalEl) totalEl.value = total.toFixed(2);
    if (balanceId && advanceId) {
        const advance = parseFloat(document.getElementById(advanceId)?.value) || 0;
        const balEl = document.getElementById(balanceId);
        if (balEl) balEl.value = (advance - total).toFixed(2);
    }
}

function recalcOT(tableId, totalId) {
    let total = 0;
    document.querySelectorAll(`#${tableId} .ot-hours`).forEach(i => total += parseFloat(i.value) || 0);
    const totalEl = document.getElementById(totalId);
    if (totalEl) totalEl.value = total.toFixed(1);
}

function initTable(config) {
    const { tableId, addBtnId, recalc, totalId, balanceId, advanceId } = config;

    const runRecalc = () => {
        if (recalc === 'items')       recalcItems(tableId, totalId);
        if (recalc === 'amount-only') recalcAmounts(tableId, totalId, balanceId, advanceId);
        if (recalc === 'ot')          recalcOT(tableId, totalId);
    };

    document.getElementById(addBtnId)?.addEventListener('click', () => {
        const tbody = document.querySelector(`#${tableId} tbody`);
        const row = tbody.rows[0].cloneNode(true);
        row.querySelectorAll('input').forEach(i => i.value = '');
        tbody.appendChild(row);
    });

    document.addEventListener('input', runRecalc);

    document.addEventListener('click', e => {
        if (!e.target.classList.contains('remove-row')) return;
        const tbody = document.querySelector(`#${tableId} tbody`);
        if (tbody.rows.length > 1) e.target.closest('tr').remove();
        runRecalc();
    });
}