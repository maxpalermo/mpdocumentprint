document.addEventListener("MPDocumentPrintCustomEvent", async (e) => {
    console.log("DOMContentLoaded: MpDocumentPrint MPDocumentPrintCustomEvent");
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
    btnPrintOrder.addEventListener("click", async (e) => {
        console.log("click DocumentPrintOrderNote");
        e.preventDefault();
        e.stopPropagation();

        await DocumentPrintOrderNote(orderId);
        return false;
    });
});

async function DocumentPrintOrderNote(orderId) {
    console.log("DocumentPrintOrderNote");
    const formData = new FormData();
    formData.append("ajax", 1);
    formData.append("action", "DocumentPrintOrderNote");
    formData.append("order_id", orderId);

    const response = await fetch(MpDocumentPrintAdminControllerURL, {
        method: "POST",
        body: formData,
    });

    const json = await response.json();
    const data = json.data || json || false;
    if (data) {
        if (data.stream.length > 0) {
            const filename = data.filename || "order_" + orderId + ".pdf";
            previewBase64PdfFullHeight(orderId, data.stream, filename);
        }
    } else {
        alert("Errore durante la chiamata Ajax!");
    }
}
