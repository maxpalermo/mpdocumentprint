function previewBase64Pdf(base64Pdf, filename = "document.pdf") {
    try {
        // 1. Decodifica il Base64 (pulendo eventuali prefissi)
        const cleanedBase64 = base64Pdf.replace(/^data:application\/pdf;base64,/, "");
        const binaryString = atob(cleanedBase64);

        // 2. Converti in array di byte
        const bytes = new Uint8Array(binaryString.length);
        for (let i = 0; i < binaryString.length; i++) {
            bytes[i] = binaryString.charCodeAt(i);
        }

        // 3. Crea un blob PDF
        const blob = new Blob([bytes], { type: "application/pdf" });
        const blobUrl = URL.createObjectURL(blob);

        // 4. Prova ad aprire una nuova finestra
        const newWindow = window.open("", "_blank");

        if (!newWindow || newWindow.closed) {
            // Fallback 1: Usa <embed> nella pagina corrente
            const embed = document.createElement("embed");
            embed.src = blobUrl;
            embed.type = "application/pdf";
            embed.style.width = "100%";
            embed.style.height = "500px";

            // Crea un contenitore e aggiungilo al DOM
            const container = document.createElement("div");
            container.style.position = "fixed";
            container.style.top = "0";
            container.style.left = "0";
            container.style.right = "0";
            container.style.bottom = "0";
            container.style.backgroundColor = "white";
            container.style.zIndex = "9999";
            container.appendChild(embed);

            // Aggiungi un pulsante per chiudere
            const closeBtn = document.createElement("button");
            closeBtn.textContent = "Chiudi Anteprima";
            closeBtn.style.position = "absolute";
            closeBtn.style.top = "10px";
            closeBtn.style.right = "10px";
            closeBtn.onclick = () => document.body.removeChild(container);
            container.appendChild(closeBtn);

            document.body.appendChild(container);
        } else {
            // 5. Configura la nuova finestra
            newWindow.document.title = filename;
            newWindow.document.body.style.margin = "0";
            newWindow.document.write(`
        <embed 
          width="100%" 
          height="100%" 
          src="${blobUrl}" 
          type="application/pdf" 
        />
      `);

            // 6. Rilascia la risorsa quando la finestra si chiude
            newWindow.onbeforeunload = () => {
                URL.revokeObjectURL(blobUrl);
            };
        }
    } catch (error) {
        console.error("Errore durante l'anteprima del PDF:", error);
        alert("Impossibile visualizzare il PDF. Controlla la console per i dettagli.");
    }
}
