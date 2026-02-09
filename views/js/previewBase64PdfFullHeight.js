function previewBase64PdfFullHeight(orderId, base64Pdf, filename = "document.pdf") {
    try {
        // 1. Decodifica e pulisci il Base64
        const cleanedBase64 = base64Pdf.replace(/^data:application\/pdf;base64,/, "");
        const binaryString = atob(cleanedBase64);

        // 2. Converti in array di byte
        const bytes = new Uint8Array(binaryString.length);
        for (let i = 0; i < binaryString.length; i++) {
            bytes[i] = binaryString.charCodeAt(i);
        }

        // 3. Crea blob e URL
        const blob = new Blob([bytes], {
            type: "application/pdf"
        });

        const filename = "order_" + orderId + ".pdf";
        openBlobPdfInNewTab(blob, filename);
    } catch (error) {
        console.error("Errore:", error);
        alert(`Errore: ${error.message}`);
    }
}

function openBlobPdfInNewTab(blob, filename = "document.pdf") {
    const blobUrl = URL.createObjectURL(blob);
    window.open(blobUrl, "_blank");
}