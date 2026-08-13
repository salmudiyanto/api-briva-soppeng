<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BrivaService;

class BrivaController extends Controller
{
    protected $brivaService;

    public function __construct(BrivaService $brivaService)
    {
        $this->brivaService = $brivaService;
    }

    /**
     * POST /api/snap/v1.0/access-token/b2b
     */
    public function getToken(Request $request)
    {
        $clientId = $request->header('X-CLIENT-KEY', env('BRIVA_CLIENT_ID', 'CLIENT123'));
        $privateKeyPem = env('BRIVA_PRIVATE_KEY', '');

        // If no private key configured, generate a test RSA key pair
        if (empty($privateKeyPem)) {
            $config = [
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ];
            
            // Perbaikan untuk environment XAMPP di Windows yang sering kehilangan path openssl.cnf
            if (file_exists('c:/xampp/php/extras/ssl/openssl.cnf')) {
                $config['config'] = 'c:/xampp/php/extras/ssl/openssl.cnf';
            } elseif (file_exists('c:/xampp/apache/conf/openssl.cnf')) {
                $config['config'] = 'c:/xampp/apache/conf/openssl.cnf';
            }

            $res = openssl_pkey_new($config);
            
            if ($res) {
                openssl_pkey_export($res, $privateKeyPem, null, $config);
            } else {
                return response()->json([
                    'responseCode' => '500',
                    'responseMessage' => 'Gagal membuat mock private key RSA. Silakan isi BRIVA_PRIVATE_KEY di file .env',
                    'error_detail' => openssl_error_string()
                ], 500);
            }
        }

        $timestamp = $request->header('X-TIMESTAMP');

        $result = $this->brivaService->getAccessToken($clientId, $privateKeyPem, $timestamp);
        $status = (int) substr($result['responseCode'], 0, 3);

        $this->logAccess($request, $result, $clientId);

        return response()->json($result, $status);
    }

    /**
     * POST /api/snap/v1.0/transfer-va/inquiry
     */
    public function inquiry(Request $request)
    {
        $authHeader = $request->header('Authorization', '');
        $accessToken = str_replace('Bearer ', '', $authHeader);
        $clientSecret = env('BRIVA_CLIENT_SECRET', 'SECRET123');
        $timestamp = $request->header('X-TIMESTAMP');

        $result = $this->brivaService->processInquiry($request->all(), $accessToken, $clientSecret, $timestamp);
        $status = (int) substr($result['responseCode'], 0, 3);

        $this->logAccess($request, $result);

        return response()->json($result, $status);
    }

    /**
     * POST /api/snap/v1.0/transfer-va/payment
     */
    public function payment(Request $request)
    {
        $authHeader = $request->header('Authorization', '');
        $accessToken = str_replace('Bearer ', '', $authHeader);
        $clientSecret = env('BRIVA_CLIENT_SECRET', 'SECRET123');
        $timestamp = $request->header('X-TIMESTAMP');

        $result = $this->brivaService->processPayment($request->all(), $accessToken, $clientSecret, $timestamp);
        $status = (int) substr($result['responseCode'], 0, 3);

        $this->logAccess($request, $result);

        return response()->json($result, $status);
    }

    /**
     * Catat riwayat akses ke tabel t_briva_api_access_logs
     */
    protected function logAccess(Request $request, array $result, $clientId = null)
    {
        try {
            \DB::table('t_briva_api_access_logs')->insert([
                'client_id' => $clientId ?: $request->header('X-CLIENT-KEY'),
                'endpoint' => $request->path(),
                'http_method' => $request->method(),
                'request_headers' => json_encode([
                    'X-CLIENT-KEY' => $request->header('X-CLIENT-KEY'),
                    'X-TIMESTAMP' => $request->header('X-TIMESTAMP'),
                    'Content-Type' => $request->header('Content-Type'),
                ]),
                'request_payload' => json_encode($request->all()),
                'response_code' => isset($result['responseCode']) ? $result['responseCode'] : null,
                'response_payload' => json_encode($result),
                'ip_address' => $request->ip(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to log API access to DB: ' . $e->getMessage());
        }
    }
}
