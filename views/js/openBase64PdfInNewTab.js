function openBase64PdfInNewTab(base64Pdf, filename = "document.pdf") {
    // Decodifica il Base64
    const binaryString = atob(base64Pdf);

    // Converte in un array di byte
    const bytes = new Uint8Array(binaryString.length);
    for (let i = 0; i < binaryString.length; i++) {
        bytes[i] = binaryString.charCodeAt(i);
    }

    // Crea un blob PDF
    const blob = new Blob([bytes], { type: "application/pdf" });

    // Crea un URL oggetto per il blob
    const blobUrl = URL.createObjectURL(blob);

    // Apre in una nuova scheda
    const newWindow = window.open(blobUrl, "_blank");

    // Se il browser blocca i popup, fallback con un link cliccabile
    if (!newWindow || newWindow.closed || typeof newWindow.closed === "undefined") {
        const link = document.createElement("a");
        link.href = blobUrl;
        link.target = "_blank";
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Rilascia l'URL dopo il click (timeout per sicurezza)
        setTimeout(() => URL.revokeObjectURL(blobUrl), 100);
    } else {
        // Rilascia l'URL quando la nuova finestra si chiude
        newWindow.onload = () => {
            setTimeout(() => URL.revokeObjectURL(blobUrl), 100);
        };
    }
}

// Esempio di utilizzo:
// const base64Pdf = "JVBERi0xLjQK..."; // Il tuo PDF in Base64
// openBase64PdfInNewTab(base64Pdf, "mio-documento.pdf");
