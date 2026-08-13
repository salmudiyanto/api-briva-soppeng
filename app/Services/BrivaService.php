<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BrivaService
{
    protected $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * Process Get Access Token B2B
     */
    public function getAccessToken($clientId, $privateKeyPem, $timestamp = null)
    {
        if (!$timestamp) {
            return [
                'responseCode' => '4007301',
                'responseMessage' => 'Invalid Field Format X-TIMESTAMP'
            ];
        }

        $cacheKey = 'briva_access_token_' . md5($clientId);

        if (Cache::has($cacheKey)) {
            return [
                'responseCode' => '2007300',
                'responseMessage' => 'Success (Cached)',
                'accessToken' => Cache::get($cacheKey),
                'tokenType' => 'BearerToken',
                'expiresIn' => '899'
            ];
        }
        $signature = $this->securityService->generateAsymmetricSignature($clientId, $timestamp, $privateKeyPem);

        Log::info('BrivaGetToken Request', [
            'client_id' => $clientId,
            'timestamp' => $timestamp
        ]);

        // Mock token generation for local service test
        // $accessToken = 'briva_bearer_token_' . bin2hex(random_bytes(16));
        $accessToken =  bin2hex(random_bytes(16));
        
        // Cache for 14 minutes (840 seconds)
        Cache::put($cacheKey, $accessToken, 14);

        return [
            'responseCode' => '2007300',
            'responseMessage' => 'Successful',
            'accessToken' => $accessToken,
            'tokenType' => 'BearerToken',
            'expiresIn' => '899'
        ];
    }

    /**
     * Process Inquiry Request
     */
    public function processInquiry(array $payload, $accessToken, $clientSecret, $timestamp = null)
    {
        if (!$timestamp) {
            return [
                'responseCode' => '4002401',
                'responseMessage' => 'Invalid Field Format X-TIMESTAMP'
            ];
        }
        
        // Validate required fields
        if (empty($payload['partnerServiceId']) || empty($payload['customerNo']) || empty($payload['virtualAccountNo'])) {
            return [
                'responseCode' => '4002402',
                'responseMessage' => 'Invalid Mandatory Field'
            ];
        }

        $signature = $this->securityService->generateSymmetricSignature(
            'POST',
            '/transfer-va/inquiry',
            $accessToken,
            $payload,
            $timestamp,
            $clientSecret
        );

        Log::info('BrivaInquiry Executed', [
            'virtualAccountNo' => $payload['virtualAccountNo'],
            'inquiryRequestId' => isset($payload['inquiryRequestId']) ? $payload['inquiryRequestId'] : null,
            'signature' => $signature
        ]);

        return [
            'responseCode' => '2002400',
            'responseMessage' => 'Successful',
            'virtualAccountData' => [
                'partnerServiceId' => sprintf('%08d', (int)$payload['partnerServiceId']),
                'customerNo' => $payload['customerNo'],
                'virtualAccountNo' => $payload['virtualAccountNo'],
                'virtualAccountName' => 'John Doe',
                'inquiryRequestId' => isset($payload['inquiryRequestId']) ? $payload['inquiryRequestId'] : 'inq_' . time(),
                'totalAmount' => [
                    'value' => isset($payload['amount']['value']) ? number_format((float)$payload['amount']['value'], 2, '.', '') : '200000.00',
                    'currency' => 'IDR'
                ],
                'inquiryStatus' => '00',
                'inquiryReason' => [
                    'english' => 'Success',
                    'indonesia' => 'Sukses'
                ]
            ]
        ];
    }

    /**
     * Process Payment / Posting Request
     */
    public function processPayment(array $payload, $accessToken, $clientSecret, $timestamp = null)
    {
        if (!$timestamp) {
            return [
                'responseCode' => '4002501',
                'responseMessage' => 'Invalid Field Format X-TIMESTAMP'
            ];
        }

        // Validate required fields
        if (empty($payload['partnerServiceId']) || empty($payload['customerNo']) || empty($payload['paymentRequestId'])) {
            return [
                'responseCode' => '4002502',
                'responseMessage' => 'Invalid Mandatory Field'
            ];
        }

        // Validate amount structure
        if (empty($payload['paidAmount']['value'])) {
            return [
                'responseCode' => '4002501',
                'responseMessage' => 'Invalid Field Format'
            ];
        }

        $signature = $this->securityService->generateSymmetricSignature(
            'POST',
            '/transfer-va/payment',
            $accessToken,
            $payload,
            $timestamp,
            $clientSecret
        );

        Log::info('BrivaPayment Executed', [
            'virtualAccountNo' => $payload['virtualAccountNo'],
            'paymentRequestId' => $payload['paymentRequestId'],
            'paidAmount' => $payload['paidAmount']['value'],
            'signature' => $signature
        ]);

        return [
            'responseCode' => '2002500',
            'responseMessage' => 'Successful',
            'virtualAccountData' => [
                'partnerServiceId' => sprintf('%08d', (int)$payload['partnerServiceId']),
                'customerNo' => $payload['customerNo'],
                'virtualAccountNo' => $payload['virtualAccountNo'],
                'virtualAccountName' => isset($payload['virtualAccountName']) ? $payload['virtualAccountName'] : 'John Doe',
                'paymentRequestId' => $payload['paymentRequestId'],
                'paidAmount' => [
                    'value' => number_format((float)$payload['paidAmount']['value'], 2, '.', ''),
                    'currency' => 'IDR'
                ],
                'paymentFlagStatus' => '00',
                'paymentFlagReason' => [
                    'english' => 'Success',
                    'indonesia' => 'Sukses'
                ]
            ]
        ];
    }
}
