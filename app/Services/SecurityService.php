<?php

namespace App\Services;

class SecurityService
{
    /**
     * Generate ISO8601 Timestamp with WIB timezone (+07:00)
     * Example: 2021-11-02T13:14:15.678+07:00
     */
    public function getTimestamp()
    {
        $timezone = new \DateTimeZone('Asia/Jakarta');
        $datetime = \DateTime::createFromFormat('U.u', sprintf('%.6f', microtime(true)));
        $datetime->setTimezone($timezone);
        return $datetime->format('Y-m-d\TH:i:s.vP');
    }

    /**
     * Generate Asymmetric Signature for B2B Get Token (SHA256withRSA)
     * StringToSign = Client_ID + "|" + X-TIMESTAMP
     */
    public function generateAsymmetricSignature($clientId, $timestamp, $privateKeyPem)
    {
        $stringToSign = $clientId . '|' . $timestamp;
        
        $privateKey = openssl_pkey_get_private($privateKeyPem);
        if (!$privateKey) {
            throw new \InvalidArgumentException('Invalid Private Key PEM format.');
        }

        $signature = '';
        openssl_sign($stringToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        openssl_free_key($privateKey);

        return base64_encode($signature);
    }

    /**
     * Generate Symmetric Signature for Transactional APIs (HMAC_SHA512)
     * StringToSign = HTTPMethod + ":" + EndpointUrl + ":" + AccessToken + ":" + Lowercase(HexEncode(SHA-256(Minify(RequestBody)))) + ":" + TimeStamp
     */
    public function generateSymmetricSignature($httpMethod, $endpointUrl, $accessToken, $requestBody, $timestamp, $clientSecret)
    {
        $httpMethodUpper = strtoupper($httpMethod);
        
        if (empty($requestBody)) {
            $hashedBody = strtolower(hash('sha256', ''));
        } else {
            $minifiedJson = is_string($requestBody) ? $requestBody : json_encode($requestBody, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $hashedBody = strtolower(hash('sha256', $minifiedJson));
        }

        $stringToSign = $httpMethodUpper . ':' . $endpointUrl . ':' . $accessToken . ':' . $hashedBody . ':' . $timestamp;

        $hmac = hash_hmac('sha512', $stringToSign, $clientSecret, true);
        return base64_encode($hmac);
    }
}
