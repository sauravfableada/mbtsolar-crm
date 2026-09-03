(function () {
    "use strict";

    const SKIP = /^(sr\.?\s*no|#|action|actions|image|select)$/i;
    const DATE_FIELD = /(date|created at|updated at|scheduled|follow.?up)/i;
    const NUMBER_FIELD = /(amount|price|stock|quantity|total|rate)/i;
    const SELECT_FIELD = /(status|type|priority|category|stage|role|country|city|staff|customer|make|technology|warranty|source)/i;

    function selectedPerPage() {
        return document.querySelector(".crm-auto-per-page")?.value || "10";
    }

    function addPerPage(url) {
        try {
            const parsed = new URL(url, window.location.origin);
            if (parsed.origin === window.location.origin && parsed.pathname.startsWith("/api/") && parsed.searchParams.has("page")) {
                parsed.searchParams.set("per_page", selectedPerPage());
                return parsed.toString();
            }
        } catch (error) {
            return url;
        }
        return url;
    }

    if (window.jQuery?.ajaxPrefilter) {
        window.jQuery.ajaxPrefilter(options => { if ((options.type || "GET").toUpperCase() === "GET") options.url = addPerPage(options.url); });
    }

    const nativeFetch = window.fetch?.bind(window);
    if (nativeFetch) {
        window.fetch = function (resource, options) {
            if (typeof resource === "string" && (!options?.method || options.method.toUpperCase() === "GET")) resource = addPerPage(resource);
            return nativeFetch(resource, options);
        };
    }

    function text(value) {
        return String(value || "").replace(/\s+/g, " ").trim();
    }

    function enhanceScrollableSelect(select) {
        if (select.dataset.scrollSelectInitialized === "true") return;
        select.dataset.scrollSelectInitialized = "true";

        const wrapper = document.createElement("div");
        wrapper.className = "crm-scroll-select";
        const button = document.createElement("button");
        button.type = "button";
        button.className = "form-select form-select-sm crm-scroll-select-toggle";
        const menu = document.createElement("div");
        menu.className = "crm-scroll-select-menu";
        wrapper.append(button, menu);
        select.insertAdjacentElement("afterend", wrapper);
        select.classList.add("crm-native-filter-select");

        function rebuild() {
            menu.innerHTML = "";
            Array.from(select.options).forEach(option => {
                const item = document.createElement("button");
                item.type = "button";
                item.className = "crm-scroll-select-option";
                item.textContent = option.textContent;
                item.classList.toggle("active", option.selected);
                item.addEventListener("click", () => {
                    select.value = option.value;
                    select.dispatchEvent(new Event("change", { bubbles: true }));
                    menu.classList.remove("show");
                    button.setAttribute("aria-expanded", "false");
                });
                menu.appendChild(item);
            });
            button.textContent = select.selectedOptions[0]?.textContent || select.options[0]?.textContent || "Select";
            button.disabled = select.disabled;
        }

        button.setAttribute("aria-expanded", "false");
        button.addEventListener("click", () => {
            document.querySelectorAll(".crm-scroll-select-menu.show").forEach(openMenu => { if (openMenu !== menu) openMenu.classList.remove("show"); });
            const open = menu.classList.toggle("show");
            button.setAttribute("aria-expanded", String(open));
        });
        select.addEventListener("change", rebuild);
        new MutationObserver(rebuild).observe(select, { childList: true, subtree: true, attributes: true });
        rebuild();
    }

    function enhanceAllFilterSelects(root = document) {
        root.querySelectorAll(".customer-filter-panel select, .lead-filter-panel select, .crm-auto-filter-panel select").forEach(enhanceScrollableSelect);
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
            return `<div class="col-sm-6 col-lg-3 crm-auto-filter-field" data-column="${field.index}" data-kind="date">${label}<div class="input-group input-group-sm"><span class="input-group-text"><i class="fa-regular fa-calendar"></i></span><input id="${id}" type="text" class="form-control crm-auto-date-range" placeholder="From - To" autocomplete="off" readonly></div></div>`;
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
                const rangeInput = field.querySelector('.crm-auto-date-range');
                const from = rangeInput.dataset.from || "";
                const to = rangeInput.dataset.to || "";
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
            if (cell.dataset.noFilter !== undefined) return null;
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
        toolbar.innerHTML = `<div class="d-flex justify-content-between align-items-center gap-3"><button type="button" class="btn btn-outline-dark-blue btn-sm crm-auto-filter-toggle" aria-expanded="true"><i class="fa-solid fa-filter me-1"></i>Filters <span class="badge rounded-pill text-bg-primary d-none">0</span></button><div class="crm-auto-per-page-wrap d-flex align-items-center gap-2"><label class="small text-muted text-nowrap mb-0">Show per page:</label><select class="form-select form-select-sm crm-auto-per-page"><option>10</option><option>25</option><option>50</option><option>100</option></select></div></div><div class="crm-auto-filter-panel mt-3"><div class="row g-3 align-items-end">${fields.map(fieldMarkup).join("")}<div class="col-sm-6 col-lg-3"><button type="button" class="btn btn-dark-blue btn-sm w-100 crm-auto-filter-clear crm-filter-clear-btn"><i class="fa-solid fa-rotate-left me-1"></i>Clear</button></div></div></div>`;
        wrapper.parentNode.insertBefore(toolbar, wrapper);

        const panel = toolbar.querySelector(".crm-auto-filter-panel");
        const toggle = toolbar.querySelector(".crm-auto-filter-toggle");
        const clearButton = toolbar.querySelector(".crm-auto-filter-clear");
        const perPageWrap = toolbar.querySelector(".crm-auto-per-page-wrap");
        const perPageSelect = toolbar.querySelector(".crm-auto-per-page");
        const requestedPerPage = new URL(window.location.href).searchParams.get("per_page");
        if (["10", "25", "50", "100"].includes(requestedPerPage)) perPageSelect.value = requestedPerPage;
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
            controlsRow.appendChild(perPageWrap);
            perPageWrap.classList.add("ms-md-auto");
            controlsRow.insertAdjacentElement("afterend", panel);
            toolbar.remove();
        }

        const run = () => applyFilters(table, panel, countBadge);
        panel.querySelectorAll('.crm-auto-date-range').forEach(input => {
            if (!window.flatpickr) return;
            input._rangePicker = window.flatpickr(input, {
                mode: "range",
                dateFormat: "d-m-Y",
                showMonths: 1,
                disableMobile: true,
                onChange(dates) {
                    const format = date => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
                    input.dataset.from = dates[0] ? format(dates[0]) : "";
                    input.dataset.to = dates[1] ? format(dates[1]) : "";
                    run();
                },
            });
        });
        let timer;
        toggle.addEventListener("click", event => {
            const open = panel.classList.toggle("d-none") === false;
            event.currentTarget.setAttribute("aria-expanded", String(open));
        });
        panel.addEventListener("input", () => { clearTimeout(timer); timer = setTimeout(run, 250); });
        panel.addEventListener("change", run);
        clearButton.addEventListener("click", () => {
            panel.querySelectorAll("input, select").forEach(control => {
                control.value = "";
                if (control.classList.contains("crm-auto-date-range")) {
                    control.dataset.from = "";
                    control.dataset.to = "";
                    control._rangePicker?.clear(false);
                }
            });
            run();
        });
        perPageSelect.addEventListener("change", () => {
            const url = new URL(window.location.href);
            url.searchParams.set("per_page", perPageSelect.value);
            window.history.replaceState({}, "", url);
            table.dispatchEvent(new CustomEvent("crm:per-page-change", { detail: { perPage: Number(perPageSelect.value) } }));
            const firstPage = table.closest(".card")?.querySelector('.page-link[data-page="1"]');
            if (firstPage) firstPage.click();
            else window.location.reload();
        });

        updateSelectOptions(table, panel);
        enhanceAllFilterSelects(panel);
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
        enhanceAllFilterSelects();
    }

    window.CrmTableFilter = { initialize, initializeAll };
    document.addEventListener("click", event => {
        if (!event.target.closest(".crm-scroll-select")) {
            document.querySelectorAll(".crm-scroll-select-menu.show").forEach(menu => menu.classList.remove("show"));
        }
    });
    document.readyState === "loading" ? document.addEventListener("DOMContentLoaded", initializeAll) : initializeAll();
})();
