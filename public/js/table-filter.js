(function () {
    "use strict";

    const SKIP = /^(sr\.?\s*no|#|action|actions|image|select)$/i;
    const DATE_FIELD = /(date|created at|updated at|scheduled|follow.?up)/i;
    const NUMBER_FIELD = /(amount|price|stock|quantity|total|rate)/i;
    const SELECT_FIELD = /(status|type|priority|category|stage|role|country|city|staff|customer|make|technology|warranty|source)/i;

    function text(value) {
        return String(value || "").replace(/\s+/g, " ").trim();
    }

    function slug(value) {
        return text(value).toLowerCase().replace(/[^a-z0-9]+/g, "_").replace(/^_|_$/g, "");
    }

    function primaryRows(table) {
        return Array.from(table.tBodies).flatMap(tbody => Array.from(tbody.rows)).filter(row =>
            !row.classList.contains("details-row") && !row.querySelector("td[colspan]")
        );
    }

    function fieldMarkup(field) {
        const id = `crm-filter-${field.tableId}-${field.index}`;
        const label = `<label class="form-label small fw-semibold" for="${id}">${field.label}</label>`;

        if (field.kind === "date") {
            return `<div class="col-sm-6 col-lg-3 crm-auto-filter-field" data-column="${field.index}" data-kind="date">${label}<div class="d-flex gap-2"><input id="${id}" type="date" class="form-control form-control-sm" data-bound="from" aria-label="${field.label} from"><input type="date" class="form-control form-control-sm" data-bound="to" aria-label="${field.label} to"></div></div>`;
        }
        if (field.kind === "number") {
            return `<div class="col-sm-6 col-lg-3 crm-auto-filter-field" data-column="${field.index}" data-kind="number">${label}<div class="d-flex gap-2"><input id="${id}" type="number" class="form-control form-control-sm" data-bound="min" placeholder="Min"><input type="number" class="form-control form-control-sm" data-bound="max" placeholder="Max"></div></div>`;
        }
        if (field.kind === "select") {
            return `<div class="col-sm-6 col-lg-3 crm-auto-filter-field" data-column="${field.index}" data-kind="select">${label}<select id="${id}" class="form-select form-select-sm"><option value="">All ${field.label.toLowerCase()}</option></select></div>`;
        }
        return `<div class="col-sm-6 col-lg-3 crm-auto-filter-field" data-column="${field.index}" data-kind="text">${label}<input id="${id}" type="search" class="form-control form-control-sm" placeholder="Search ${field.label.toLowerCase()}"></div>`;
    }

    function updateSelectOptions(table, panel) {
        const rows = primaryRows(table);
        panel.querySelectorAll('.crm-auto-filter-field[data-kind="select"]').forEach(field => {
            const select = field.querySelector("select");
            const selected = select.value;
            const values = new Map();
            rows.forEach(row => {
                const value = text(row.cells[Number(field.dataset.column)]?.textContent);
                if (value && value !== "-") values.set(value.toLowerCase(), value);
            });
            Array.from(values.values()).sort((a, b) => a.localeCompare(b, undefined, { numeric: true })).forEach(value => {
                if (!Array.from(select.options).some(option => option.value === value.toLowerCase())) {
                    select.add(new Option(value, value.toLowerCase()));
                }
            });
            select.value = selected;
        });
    }

    function rowMatches(row, panel) {
        return Array.from(panel.querySelectorAll(".crm-auto-filter-field")).every(field => {
            const cellText = text(row.cells[Number(field.dataset.column)]?.textContent);
            const normalized = cellText.toLowerCase();

            if (field.dataset.kind === "select") {
                const value = field.querySelector("select").value;
                return !value || normalized === value;
            }
            if (field.dataset.kind === "date") {
                const timestamp = Date.parse(cellText);
                const from = field.querySelector('[data-bound="from"]').value;
                const to = field.querySelector('[data-bound="to"]').value;
                if (!from && !to) return true;
                if (!Number.isFinite(timestamp)) return false;
                if (from && timestamp < new Date(`${from}T00:00:00`).getTime()) return false;
                if (to && timestamp > new Date(`${to}T23:59:59`).getTime()) return false;
                return true;
            }
            if (field.dataset.kind === "number") {
                const value = Number(cellText.replace(/[^0-9.-]/g, ""));
                const min = field.querySelector('[data-bound="min"]').value;
                const max = field.querySelector('[data-bound="max"]').value;
                if (!min && !max) return true;
                if (!Number.isFinite(value)) return false;
                return (!min || value >= Number(min)) && (!max || value <= Number(max));
            }
            const value = field.querySelector("input").value.trim().toLowerCase();
            return !value || normalized.includes(value);
        });
    }

    function applyFilters(table, panel, countBadge) {
        let activeCount = 0;
        panel.querySelectorAll("input, select").forEach(control => { if (control.value) activeCount += 1; });
        countBadge.textContent = String(activeCount);
        countBadge.classList.toggle("d-none", activeCount === 0);

        primaryRows(table).forEach(row => {
            const visible = rowMatches(row, panel);
            row.style.display = visible ? "" : "none";
            const details = row.nextElementSibling;
            if (details?.classList.contains("details-row") && !visible) details.style.display = "none";
        });
    }

    function initialize(table, sequence) {
        if (table.dataset.autoFilterInitialized === "true" || table.dataset.noAutoFilter !== undefined) return;
        const header = table.tHead?.rows?.[0];
        if (!header || !table.tBodies.length) return;

        const tableId = table.id || `table-${sequence}`;
        const fields = Array.from(header.cells).map((cell, index) => {
            const label = text(cell.textContent);
            if (!label || SKIP.test(label)) return null;
            const kind = DATE_FIELD.test(label) ? "date" : NUMBER_FIELD.test(label) ? "number" : SELECT_FIELD.test(label) ? "select" : "text";
            return { tableId, label, index, kind };
        }).filter(Boolean);
        if (!fields.length) return;

        table.dataset.autoFilterInitialized = "true";
        const wrapper = table.closest(".table-responsive") || table;
        const toolbar = document.createElement("div");
        toolbar.className = "crm-auto-filter-wrap px-3 pt-3";
        toolbar.innerHTML = `<button type="button" class="btn btn-outline-dark-blue btn-sm crm-auto-filter-toggle" aria-expanded="true"><i class="fa-solid fa-filter me-1"></i>Filters <span class="badge rounded-pill text-bg-primary d-none">0</span></button><div class="crm-auto-filter-panel mt-3"><div class="row g-3 align-items-end">${fields.map(fieldMarkup).join("")}<div class="col-sm-6 col-lg-3"><button type="button" class="btn btn-dark-blue btn-sm w-100 crm-auto-filter-clear"><i class="fa-solid fa-rotate-left me-1"></i>Clear Filters</button></div></div></div>`;
        wrapper.parentNode.insertBefore(toolbar, wrapper);

        const panel = toolbar.querySelector(".crm-auto-filter-panel");
        const toggle = toolbar.querySelector(".crm-auto-filter-toggle");
        const clearButton = toolbar.querySelector(".crm-auto-filter-clear");
        const countBadge = toolbar.querySelector(".badge");
        const card = table.closest(".card");
        const searchInput = card?.querySelector('input[id*="Search"], input[id*="search"], input[type="search"]');
        const searchGroup = searchInput?.closest(".input-group");
        const controlsRow = searchGroup?.parentElement;

        if (controlsRow && card?.contains(controlsRow)) {
            Array.from(controlsRow.children).forEach(child => {
                if (child !== searchGroup && child.matches("h1, h2, h3, h4, h5, h6, p, .section-title")) {
                    child.remove();
                }
            });
            controlsRow.classList.remove("justify-content-between");
            controlsRow.classList.add("justify-content-start");
            searchGroup.insertAdjacentElement("afterend", toggle);
            controlsRow.insertAdjacentElement("afterend", panel);
            toolbar.remove();
        }

        const run = () => applyFilters(table, panel, countBadge);
        let timer;
        toggle.addEventListener("click", event => {
            const open = panel.classList.toggle("d-none") === false;
            event.currentTarget.setAttribute("aria-expanded", String(open));
        });
        panel.addEventListener("input", () => { clearTimeout(timer); timer = setTimeout(run, 250); });
        panel.addEventListener("change", run);
        clearButton.addEventListener("click", () => {
            panel.querySelectorAll("input, select").forEach(control => { control.value = ""; });
            run();
        });

        updateSelectOptions(table, panel);
        let observing = false;
        const observer = new MutationObserver(() => {
            if (observing) return;
            observing = true;
            updateSelectOptions(table, panel);
            run();
            observing = false;
        });
        Array.from(table.tBodies).forEach(tbody => observer.observe(tbody, { childList: true }));
    }

    function initializeAll() {
        document.querySelectorAll("table.table-hover").forEach((table, index) => initialize(table, index));
    }

    window.CrmTableFilter = { initialize, initializeAll };
    document.readyState === "loading" ? document.addEventListener("DOMContentLoaded", initializeAll) : initializeAll();
})();
