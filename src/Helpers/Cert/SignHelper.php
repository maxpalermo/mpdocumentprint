<?php
namespace MpSoft\MpDocumentPrint\Cert;

class SignHelper
{
    /**
     * Firma la stringa fornita usando la chiave privata PEM
     * @param string $toSign
     * @param string|null $privateKeyPath
     * @return string Firma base64
     * @throws \Exception
     */
    public static function sign($toSign, $privateKeyPath = null)
    {
        if ($privateKeyPath === null) {
            $privateKeyPath = __DIR__ . '/qztray-private.pem';
        }
        if (!file_exists($privateKeyPath)) {
            throw new \Exception('Private key not found');
        }
        $privateKey = file_get_contents($privateKeyPath);
        if (!$privateKey) {
            throw new \Exception('Private key not readable');
        }
        $pkeyid = openssl_get_privatekey($privateKey);
        if (!$pkeyid) {
            throw new \Exception('Invalid private key');
        }
        $signature = '';
        if (!openssl_sign($toSign, $signature, $pkeyid, OPENSSL_ALGO_SHA1)) {
            openssl_free_key($pkeyid);
            throw new \Exception('Unable to sign');
        }
        openssl_free_key($pkeyid);
        return base64_encode($signature);
    }
}
