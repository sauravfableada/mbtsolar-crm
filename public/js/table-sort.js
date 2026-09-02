(function () {
    "use strict";

    const SKIPPED_HEADERS = new Set(["action", "actions", "select"]);

    function normalizedText(value) {
        return String(value || "").replace(/\s+/g, " ").trim();
    }

    function comparableValue(cell) {
        if (!cell) return "";

        const explicitValue = cell.dataset.sortValue;
        const value = normalizedText(explicitValue !== undefined ? explicitValue : cell.textContent);
        const numericValue = Number(value.replace(/[^0-9.-]/g, ""));

        if (value && Number.isFinite(numericValue) && /\d/.test(value)) {
            return { type: "number", value: numericValue };
        }

        const dateValue = Date.parse(value);
        if (value && /\d/.test(value) && Number.isFinite(dateValue)) {
            return { type: "number", value: dateValue };
        }

        return { type: "text", value: value.toLocaleLowerCase() };
    }

    function compareCells(firstCell, secondCell, direction) {
        const first = comparableValue(firstCell);
        const second = comparableValue(secondCell);
        let result;

        if (first.type === "number" && second.type === "number") {
            result = first.value - second.value;
        } else {
            result = String(first.value).localeCompare(String(second.value), undefined, {
                numeric: true,
                sensitivity: "base",
            });
        }

        return direction === "asc" ? result : -result;
    }

    function rowGroups(tbody) {
        const groups = [];

        Array.from(tbody.children).forEach(row => {
            if (!(row instanceof HTMLTableRowElement)) return;

            if (row.classList.contains("details-row") && groups.length) {
                groups[groups.length - 1].push(row);
                return;
            }

            groups.push([row]);
        });

        return groups;
    }

    function sortTableBody(table, columnIndex, direction) {
        Array.from(table.tBodies).forEach(tbody => {
            const groups = rowGroups(tbody);
            const sortableGroups = groups.filter(group => {
                const row = group[0];
                return row.cells.length > columnIndex && !row.querySelector("td[colspan]");
            });

            if (sortableGroups.length < 2) return;

            sortableGroups.sort((first, second) =>
                compareCells(first[0].cells[columnIndex], second[0].cells[columnIndex], direction)
            );

            const fragment = document.createDocumentFragment();
            sortableGroups.forEach(group => group.forEach(row => fragment.appendChild(row)));
            tbody.appendChild(fragment);
        });
    }

    function indicatorMarkup() {
        const indicator = document.createElement("span");
        indicator.className = "crm-table-sort-indicator";
        indicator.setAttribute("aria-hidden", "true");
        indicator.innerHTML = '<i class="fa-solid fa-caret-up sort-up"></i><i class="fa-solid fa-caret-down sort-down"></i>';
        return indicator;
    }

    function updateIndicators(table, activeButton, direction) {
        table.querySelectorAll("thead .crm-table-sort-button").forEach(button => {
            const isActive = button === activeButton;
            button.classList.toggle("active", isActive);
            button.classList.toggle("sort-asc", isActive && direction === "asc");
            button.classList.toggle("sort-desc", isActive && direction === "desc");
            button.setAttribute("aria-sort", isActive ? (direction === "asc" ? "ascending" : "descending") : "none");
        });
    }

    function makeButton(th, table, columnIndex) {
        const existingButton = th.querySelector(":scope > .customer-sort-button, :scope > .crm-table-sort-button");
        const label = normalizedText(existingButton ? existingButton.textContent : th.textContent).toLocaleLowerCase();

        if (th.dataset.noSort !== undefined || SKIPPED_HEADERS.has(label) || th.querySelector('input[type="checkbox"]')) {
            return null;
        }

        let button = existingButton;
        if (button) {
            button.classList.remove("customer-sort-button");
            button.classList.add("crm-table-sort-button");
            const oldIndicator = button.querySelector(".customer-sort-indicator, .crm-table-sort-indicator");
            if (oldIndicator) oldIndicator.remove();
        } else {
            button = document.createElement("button");
            button.type = "button";
            button.className = "crm-table-sort-button";
            while (th.firstChild) button.appendChild(th.firstChild);
            th.appendChild(button);
        }

        button.appendChild(indicatorMarkup());
        button.dataset.columnIndex = String(columnIndex);
        return button;
    }

    function initializeTable(table) {
        if (table.dataset.crmSortInitialized === "true" || table.dataset.noTableSort !== undefined) return;

        const headerRow = table.tHead?.rows?.[0];
        if (!headerRow || !table.tBodies.length) return;

        table.dataset.crmSortInitialized = "true";
        const buttons = [];

        Array.from(headerRow.cells).forEach((th, columnIndex) => {
            const button = makeButton(th, table, columnIndex);
            if (!button) return;
            buttons.push(button);

            button.addEventListener("click", () => {
                const currentColumn = table.dataset.sortColumn;
                const columnKey = button.dataset.sort || String(columnIndex);
                const direction = currentColumn === columnKey && table.dataset.sortDirection === "asc" ? "desc" : "asc";

                table.dataset.sortColumn = columnKey;
                table.dataset.sortDirection = direction;
                updateIndicators(table, button, direction);

                if (table.dataset.sortMode === "server") {
                    table.dispatchEvent(new CustomEvent("crm:table-sort", {
                        detail: { column: columnKey, direction, columnIndex },
                    }));
                } else {
                    sortTableBody(table, columnIndex, direction);
                }
            });
        });

        const initialColumn = table.dataset.sortColumn;
        if (initialColumn) {
            const activeButton = buttons.find(button =>
                (button.dataset.sort || button.dataset.columnIndex) === initialColumn
            );
            if (activeButton) updateIndicators(table, activeButton, table.dataset.sortDirection || "asc");
        }

        if (table.dataset.sortMode !== "server") {
            const observeBodies = observer => {
                Array.from(table.tBodies).forEach(tbody => observer.observe(tbody, { childList: true }));
            };
            const observer = new MutationObserver(() => {
                const activeButton = table.querySelector("thead .crm-table-sort-button.active");
                if (!activeButton) return;
                observer.disconnect();
                sortTableBody(table, Number(activeButton.dataset.columnIndex), table.dataset.sortDirection || "asc");
                observeBodies(observer);
            });
            observeBodies(observer);
        }
    }

    function initializeAll() {
        document.querySelectorAll("table.table-hover").forEach(initializeTable);
    }

    window.CrmTableSort = { initialize: initializeTable, initializeAll };

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initializeAll);
    } else {
        initializeAll();
    }
})();
