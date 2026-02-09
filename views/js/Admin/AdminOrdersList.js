document.addEventListener("MPDocumentPrintCustomEvent", async (e) => {
    const table = document.getElementById("order_grid_table");
    if (!table) return false;

    const rows = table.querySelectorAll("tbody tr");
    rows.forEach((row) => {
        const dataIdentifier = row.querySelector("td[data-identifier]");
        if (dataIdentifier) {
            const orderId = dataIdentifier.getAttribute("data-identifier");

            const columnActions = row.querySelector("td.action-type.column-actions");
            if (!columnActions) return false;

            const btnGroupContainer = columnActions.querySelector(".btn-group-action.text-right");
            if (!btnGroupContainer) return false;

            const btnGroup = btnGroupContainer.querySelector(".btn-group");
            if (!btnGroup) return false;

            const icon = document.createElement("i");
            icon.className = "fa fa-print text-danger";

            const a = document.createElement("a");
            a.className = "btn tooltip-link grid-print-order-note inline-dropdown-item";
            a.href = "javascript:DocumentPrintOrderNote(" + orderId + ")";
            a.title = "Anteprima nota magazzino";

            a.appendChild(icon);
            btnGroup.insertAdjacentElement("afterbegin", a);

            btnGroup.style.display = "flex";
            btnGroup.style.flexWrap = "wrap";
            btnGroup.style.flexDirection = "row";
            btnGroup.style.gap = "5px";
            btnGroup.style.width = "100px";
            btnGroup.style.maxWidth = "100px";
            btnGroup.style.padding = "5px";

            const btns = btnGroup.querySelectorAll(".btn");
            btns.forEach((btn) => {
                btn.style.width = "24px";
                btn.style.height = "24px";
                btn.style.display = "flex";
                btn.style.alignItems = "start";
                btn.style.justifyContent = "flex-start";
                btn.style.backgroundColor = "transparent";
                btn.style.border = "none";
                btn.style.padding = "0";
                btn.style.margin = "0";
            });

            btnGroup.classList.remove("justify-content-between");
            btnGroup.classList.remove("d-flex");
        }
    });

    //Aggiungo una nuova bulk-action
    const bulkActionsDropdown = document.querySelector(".js-bulk-actions-btn");
    if (!bulkActionsDropdown) return;

    const bulkActionContainer = bulkActionsDropdown.closest(".btn-group");
    if (!bulkActionContainer) return;

    const bulkActionDropdownMenu = bulkActionContainer.querySelector(".dropdown-menu");
    if (!bulkActionDropdownMenu) return;

    bulkActionDropdownMenu.insertAdjacentHTML(
        "beforeend",
        `
        <button id="order_grid_bulk_action_change_order_status" class="dropdown-item js-bulk-modal-form-submit-btn" type="button" onclick="DocumentPrintOrderNoteBulk()">
            <span class="material-icons">preview</span> Anteprima Massiva note ordine
        </button>
    `
    );

    //bulkActionContainer.appendChild(bulkActionDropdown);
});

async function DocumentPrintOrderNoteBulk() {
    const table = document.querySelectorAll(".js-bulk-action-checkbox:checked");
    const orderIds = [];
    table.forEach((checkbox) => {
        orderIds.push(checkbox.value);
    });

    const formData = new FormData();
    formData.append("ajax", 1);
    formData.append("action", "DocumentPrintOrderNoteBulk");
    formData.append("order_ids", JSON.stringify(orderIds));

    const response = await fetch(MpDocumentPrintAdminControllerURL, {
        method: "POST",
        body: formData
    });

    const data = await response.json();

    if (data) {
        if (data.stream.length > 0) {
            const filename = data.filename || "order_" + orderIds.join("_") + ".pdf";
            previewBase64PdfFullHeight('bulk', data.stream, filename);
        }
    } else {
        SwalError("Errore durante la chiamata Ajax!");
    }
}

async function DocumentPrintOrderNote(orderId) {
    const formData = new FormData();
    formData.append("ajax", 1);
    formData.append("action", "DocumentPrintOrderNote");
    formData.append("order_id", orderId);

    const response = await fetch(MpDocumentPrintAdminControllerURL, {
        method: "POST",
        body: formData
    });

    const data = await response.json();
    if (data) {
        if (data.stream.length > 0) {
            const filename = data.filename || "order_" + orderId + ".pdf";
            previewBase64PdfFullHeight(orderId, data.stream, filename);
        }
    } else {
        SwalError("Errore durante la chiamata Ajax!");
    }
}