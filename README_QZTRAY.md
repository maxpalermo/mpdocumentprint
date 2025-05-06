# QZ Tray Secure PDF Printing Integration

## Prerequisiti
- QZ Tray installato sul client: https://qz.io/download/
- Certificati generati per la firma digitale

## Struttura dei file

- `src/Helpers/Cert/qztray-private.pem`  
  Chiave privata per la firma digitale (permessi 600, accesso solo al web server)
- `views/cert/qztray-public.pem`  
  Certificato pubblico QZ Tray da caricare lato client
- `src/Helpers/Cert/SignHelper.php`  
  Classe helper per la firma digitale
- `src/Helpers/Cert/sign.php`  
  Endpoint POST per firmare le stringhe richieste da QZ Tray
- `views/js/Printer/PrintDocument.js`  
  Tutte le funzioni JS per la stampa sicura

## Generazione Certificati (Esempio)

```bash
# Genera chiave privata
openssl genrsa -out qztray-private.pem 2048

# Estrai il certificato pubblico
openssl req -new -x509 -key qztray-private.pem -out qztray-public.pem -days 3650 -subj "/CN=QZTray Cert"
```

- Copia la chiave privata in `src/Helpers/Cert/qztray-private.pem`
- Copia il certificato pubblico in `views/cert/qztray-public.pem`

## Sicurezza
- La chiave privata NON deve mai essere accessibile dal browser o da utenti non autorizzati.
- L'endpoint `sign.php` accetta solo POST e restituisce la firma in base64.
- Il JS comunica con l'endpoint solo per firmare le richieste QZ Tray.

## Configurazione JS
- Il percorso dell'endpoint firma è già impostato in `PrintDocument.js`:
  ```js
  const QZ_SIGNATURE_URL = '/modules/mpdocumentprint/src/Helpers/Cert/sign.php';
  ```
- Il percorso del certificato pubblico è:
  ```js
  const QZ_CERTIFICATE_URL = '/modules/mpdocumentprint/views/cert/qztray-public.pem';
  ```

## Test rapido
1. Avvia QZ Tray sul client.
2. Apri la console browser e prova:
   ```js
   getPrinterList().then(console.log)
   // Poi stampa un PDF
   printPdfWithQzTray('<base64-pdf>', 'NomeStampante')
   ```

Per problemi di sicurezza o errori di firma, verifica i permessi della chiave privata e la reachability dell'endpoint PHP.
