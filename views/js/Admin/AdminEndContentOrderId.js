const MpDocumentPrintAdminControllerURL = "{$adminControllerURL}";

//creo un nuovo evento custom
const MPDocumentPrintCustomEvent = new CustomEvent("MPDocumentPrintCustomEvent", {
    detail: {
        orderId: "{$id_order}",
    },
});
document.dispatchEvent(MPDocumentPrintCustomEvent);

document.addEventListener("DOMContentLoaded", () => {
    console.log("DOMContentLoaded: MpDocumentPrint OrderId select2");
    $(".select2").select2({
        language: "it",
        width: "100%",
    });
});
