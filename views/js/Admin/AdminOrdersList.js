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
            const btnNotaConsegna = btnGroup.querySelector("a.grid-visualizza-la-nota-di-consegna-row-link");
            if (btnNotaConsegna) {
                btnNotaConsegna.removeAttribute("href");
                btnNotaConsegna.setAttribute("href", "javascript:void(0)");
                btnNotaConsegna.setAttribute("onclick", "DocumentPrintOrderNote(" + orderID + ");");
            }
        }
    });
});

async function DocumentPrintOrderNote(orderId) {
    const response = await fetch(MpDocumentPrintAdminControllerURL, {
        headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest"
        },
        method: "POST",
        body: JSON.stringify({
            ajax: 1,
            action: "DocumentPrintOrderNote",
            order_id: orderId
        })
    });
    const json = await response.json();
    const data = json.data || false;
    if (data) {
        if (data.stream.length > 0) {
            const filename = data.filename || "order_" + orderId + ".pdf";
            previewBase64PdfFullHeight(data.stream, filename);
        }
    } else {
        SwalError("Errore durante la chiamata Ajax!");
    }
}
