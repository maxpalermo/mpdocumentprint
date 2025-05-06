document.addEventListener("DOMContentLoaded", async (e) => {
    const table = document.getElementById("order_grid_table");
    if (!table) return false;

    const rows = table.querySelectorAll("tbody tr");
    rows.forEach((row) => {
        const dataIdentifier = row.querySelector("td[data-identifier]");
        if (dataIdentifier) {
            const orderID = dataIdentifier.getAttribute("data-identifier");
            const columnActions = row.querySelector("td.action-type.column-actions");
            const btnGroup = columnActions.querySelector(".btn-group");
            const btnNotConsegna = btnGroup.querySelector("a.grid-visualizza-la-nota-di-consegna-row-link");
            if (btnNotConsegna) {
                btnNotConsegna.removeAttribute("href");
                btnNotConsegna.setAttribute("href", "javascript:void(0)");
                btnNotConsegna.setAttribute("onclick", "DocumentPrintOrderNote(" + orderID + ");");
            }
        }
    });
});
