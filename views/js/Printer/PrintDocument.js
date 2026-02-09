// === QZ Tray Secure PDF Printing ===

// Percorso del certificato QZ Tray (sostituisci con il tuo certificato PEM)
const QZ_CERTIFICATE_URL = '/modules/mpdocumentprint/views/cert/qztray-public.pem';
// Endpoint server per la firma digitale (da implementare in PHP)
const QZ_SIGNATURE_URL = '/modules/mpdocumentprint/src/Helpers/Cert/sign.php';

// Carica il certificato QZ Tray dal server
function fetchQzCertificate() {
    return fetch(QZ_CERTIFICATE_URL)
        .then(response => {
            if (!response.ok) throw new Error('Impossibile caricare il certificato QZ Tray');
            return response.text();
        });
}

// Chiede al server di firmare la stringa richiesta da QZ Tray
function fetchQzSignature(toSign) {
    return fetch(QZ_SIGNATURE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ toSign })
    })
    .then(response => {
        if (!response.ok) throw new Error('Errore nella firma digitale');
        return response.text();
    });
}

// Connessione e sicurezza QZ Tray
async function connectQzTray() {
    if (!window.qz) {
        throw new Error("QZ Tray non è caricato. Assicurati che qz-tray.js sia incluso.");
    }
    if (!qz.websocket.isActive()) {
        await qz.websocket.connect();
    }
    // Imposta certificato
    qz.security.setCertificatePromise(function(resolve, reject) {
        fetchQzCertificate().then(resolve).catch(reject);
    });
    // Imposta firma digitale (server-side, sicuro)
    qz.security.setSignaturePromise(function(toSign) {
        return function(resolve, reject) {
            fetchQzSignature(toSign).then(resolve).catch(reject);
        };
    });
}

// Ottieni lista stampanti disponibili
async function getPrinterList() {
    await connectQzTray();
    return await qz.printers.find();
}

// Seleziona stampante (puoi mostrare una select all'utente)
async function selectPrinter(printerName) {
    await connectQzTray();
    return qz.configs.create(printerName);
}

// Stampa PDF in base64 in modo sicuro
async function printPdfWithQzTray(base64Pdf, printerName = null) {
    try {
        await connectQzTray();
        let config;
        if (printerName) {
            config = qz.configs.create(printerName);
        } else {
            // Usa la stampante predefinita
            config = qz.configs.create(null);
        }
        const data = [{
            type: 'pdf',
            format: 'base64',
            data: base64Pdf
        }];
        await qz.print(config, data);
        console.log("PDF inviato a QZ Tray per la stampa");
    } catch (err) {
        if (err.message && err.message.includes('ActiveX')) {
            alert('QZ Tray non è in esecuzione o non è accessibile.');
        }
        console.error("Errore durante la stampa con QZ Tray:", err);
    }
}

// Gestione disconnessione
function disconnectQzTray() {
    if (window.qz && qz.websocket.isActive()) {
        qz.websocket.disconnect();
    }
}

// Esempio di utilizzo:
// getPrinterList().then(list => console.log(list));
// printPdfWithQzTray('<base64-pdf>', 'NomeStampante');
