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
            btnGroup.style.maxWidth = "100px";
            btnGroup.style.width = "100px";

            const btns = btnGroup.querySelectorAll(".btn");
            btns.forEach((btn) => {
                btn.style.padding = "0";
                btn.style.maxWidth = "32px";
                btn.style.width = "32px";
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

    bulkActionContainer.appendChild(bulkActionDropdown);
});

async function DocumentPrintOrderNoteBulk() {
    const table = document.querySelectorAll(".js-bulk-action-checkbox:checked");
    const orderIds = [];
    table.forEach((checkbox) => {
        orderIds.push(checkbox.value);
    });
    const response = await fetch(MpDocumentPrintAdminControllerURL, {
        headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest"
        },
        method: "POST",
        body: JSON.stringify({
            ajax: 1,
            action: "DocumentPrintOrderNoteBulk",
            order_ids: orderIds
        })
    });
    const json = await response.json();
    const data = json.data || false;
    if (data) {
        if (data.stream.length > 0) {
            const filename = data.filename || "order_" + orderIds.join("_") + ".pdf";
            previewBase64PdfFullHeight(data.stream, filename);
        }
    } else {
        SwalError("Errore durante la chiamata Ajax!");
    }
}

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
