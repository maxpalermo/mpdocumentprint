function previewBase64PdfFullHeight(base64Pdf, filename = "document.pdf") {
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
        const blob = new Blob([bytes], { type: "application/pdf" });
        const blobUrl = URL.createObjectURL(blob);

        // 4. Prova ad aprire una nuova finestra
        const newWindow = window.open("", "_blank");

        if (!newWindow || newWindow.closed) {
            // Fallback: Anteprima a tutta altezza nella pagina corrente
            const container = document.createElement("div");
            container.style.position = "fixed";
            container.style.top = "0";
            container.style.left = "0";
            container.style.width = "100vw";
            container.style.height = "100vh";
            container.style.backgroundColor = "white";
            container.style.zIndex = "9999";
            container.style.overflow = "hidden";

            const embed = document.createElement("embed");
            embed.src = blobUrl;
            embed.type = "application/pdf";
            embed.style.width = "100%";
            embed.style.height = "100%";
            embed.style.border = "none";

            // Pulsante per chiudere
            const closeBtn = document.createElement("button");
            closeBtn.textContent = "✕";
            closeBtn.style.position = "absolute";
            closeBtn.style.top = "15px";
            closeBtn.style.right = "15px";
            closeBtn.style.background = "transparent";
            closeBtn.style.border = "none";
            closeBtn.style.fontSize = "20px";
            closeBtn.style.cursor = "pointer";
            closeBtn.onclick = () => {
                document.body.removeChild(container);
                URL.revokeObjectURL(blobUrl);
            };

            container.appendChild(embed);
            container.appendChild(closeBtn);
            document.body.appendChild(container);
        } else {
            // 5. Nuova finestra a tutta altezza
            newWindow.document.title = filename;
            newWindow.document.body.style.margin = "0";
            newWindow.document.body.style.padding = "0";
            newWindow.document.body.style.overflow = "hidden";
            newWindow.document.write(`
            <embed 
                width="100%" 
                height="100%" 
                src="${blobUrl}" 
                type="application/pdf" 
                style="position: fixed; top: 0; left: 0; border: none;"
            />
        `);

            // Pulsante per chiudere (opzionale)
            newWindow.document.body.insertAdjacentHTML(
                "beforeend",
                `
            <button onclick="window.close()" 
                style="position: absolute; top: 10px; right: 10px; z-index: 1000; background: #fff; border: 1px solid #ccc;">
                ✕
            </button>
        `
            );

            newWindow.onbeforeunload = () => URL.revokeObjectURL(blobUrl);
        }
    } catch (error) {
        console.error("Errore:", error);
        alert(`Errore: ${error.message}`);
    }
}
