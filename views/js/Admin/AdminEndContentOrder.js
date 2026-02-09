
const MpDocumentPrintAdminControllerURL = "{$adminControllerURL}";
//creo un nuovo evento custom
const MPDocumentPrintCustomEvent = new CustomEvent("MPDocumentPrintCustomEvent");
document.dispatchEvent(MPDocumentPrintCustomEvent);
