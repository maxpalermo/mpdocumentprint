// Test: Leggi tutte le stampanti disponibili
async function testListPrinters() {
    try {
        const printers = await getPrinterList();
        console.log("Stampanti disponibili:", printers);
        alert("Stampanti disponibili:\n" + printers.join("\n"));
        return printers;
    } catch (e) {
        alert("Errore lettura stampanti: " + e);
    }
}

// Test: Imposta una stampante e restituisci la configurazione
async function testSetPrinter(printerName) {
    try {
        const config = await selectPrinter(printerName);
        console.log("Configurazione stampante:", config);
        alert("Stampante selezionata: " + printerName);
        return config;
    } catch (e) {
        alert("Errore selezione stampante: " + e);
    }
}

// Test: Stampa un PDF di esempio (base64 di una pagina bianca A4)
async function testPrintSamplePdf(printerName) {
    // PDF A4 bianco (base64, 1 pagina, generato per test)
    const samplePdfBase64 =
        "JVBERi0xLjQKJeLjz9MKMSAwIG9iago8PC9UeXBlL0NhdGFsb2cvUGFnZXMgMiAwIFI+PgplbmRvYmoKMiAwIG9iago8PC9UeXBlL1BhZ2VzL0tpZHNbMyAwIFJdL0NvdW50IDE+PgplbmRvYmoKMyAwIG9iago8PC9UeXBlL1BhZ2UvUGFyZW50IDIgMCBSL1Jlc291cmNlczw8L0ZvbnQ8PC9GMSA0IDAgUj4+Pj4vTWVkaWFCb3hbMCAwIDU5NSA4NDJdL0NvbnRlbnRzIDUgMCBSPj4KZW5kb2JqCjQgMCBvYmoKPDwvVHlwZS9Gb250L1N1YnR5cGUvVHlwZTEvTmFtZS9GMS9CYXNlRm9udC9IZWx2ZXRpY2EvRW5jb2RpbmcvV2luQW5zaUVuY29kaW5nPj4KZW5kb2JqCjUgMCBvYmoKPDwvTGVuZ3RoIDY+PnN0cmVhbQpCBiAwIFIKZW5kc3RyZWFtCmVuZG9iago2IDAgb2JqCjw8L1Byb2R1Y2VyKEdlbmVyYXRlZCBieSBodHRwczovL3BzZGYubmV0KS9DcmVhdGlvbkRhdGUoRDoyMDI1MDUwNTA5MjQwMyswMScwMCcpPj4KZW5kb2JqCnhyZWYKMCA3CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDA5MCAwMDAwMCBuIAowMDAwMDAwMTYwIDAwMDAwIG4gCjAwMDAwMDAyNDYgMDAwMDAgbiAKMDAwMDAwMDM0NSAwMDAwMCBuIAowMDAwMDAwNDM4IDAwMDAwIG4gCjAwMDAwMDA1MzAgMDAwMDAgbiAKdHJhaWxlcgo8PC9TaXplIDcvUm9vdCAxIDAgUi9JbmZvIDYgMCBSCj4+CnN0YXJ0eHJlZgo1NDgKJSVFT0YK";
    await printPdfWithQzTray(samplePdfBase64, printerName);
}

// Test: Scegli un PDF dal disco e stampalo
function testPrintPdfFromFile(printerName) {
    const input = document.createElement("input");
    input.type = "file";
    input.accept = "application/pdf";
    input.onchange = async function (e) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = async function (evt) {
            // Rimuove il prefix data:application/pdf;base64,
            const base64 = evt.target.result.split(",")[1];
            await printPdfWithQzTray(base64, printerName);
        };
        reader.readAsDataURL(file);
    };
    input.click();
}

// Test: Disconnetti QZ Tray
function testDisconnectQzTray() {
    disconnectQzTray();
    alert("QZ Tray disconnesso");
}

// -- Esempi di uso rapido --
// testListPrinters();
// testSetPrinter('NomeStampante');
// testPrintSamplePdf('NomeStampante');
// testPrintPdfFromFile('NomeStampante');
// testDisconnectQzTray();
