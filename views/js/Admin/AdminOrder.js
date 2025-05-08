document.addEventListener("MPDocumentPrintCustomEvent", async (e) => {
    const orderId = e.detail.orderId;
    const orderActions = document.querySelector(".order-actions");
    if (!orderActions) return false;

    const btnPrintOrder = orderActions.querySelector(".btn.btn-action.js-print-order-view-page");
    if (!btnPrintOrder) return false;

    const i = btnPrintOrder.querySelector("i");
    btnPrintOrder.innerHTML = "";
    btnPrintOrder.appendChild(i);
    btnPrintOrder.appendChild(document.createTextNode("Anteprima nota magazzino"));
    btnPrintOrder.className = "btn btn-action js-view-order-note";
    btnPrintOrder.addEventListener("click", () => {
        DocumentPrintOrderNote(orderId);
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
