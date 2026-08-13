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
            $res = openssl_pkey_new([
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]);
            openssl_pkey_export($res, $privateKeyPem);
        }

        $result = $this->brivaService->getAccessToken($clientId, $privateKeyPem);
        return response()->json($result, $result['responseCode'] === '2007300' ? 200 : 400);
    }

    /**
     * POST /api/snap/v1.0/transfer-va/inquiry
     */
    public function inquiry(Request $request)
    {
        $authHeader = $request->header('Authorization', '');
        $accessToken = str_replace('Bearer ', '', $authHeader);
        $clientSecret = env('BRIVA_CLIENT_SECRET', 'SECRET123');

        $result = $this->brivaService->processInquiry($request->all(), $accessToken, $clientSecret);
        $status = strpos($result['responseCode'], '200') === 0 ? 200 : 400;

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

        $result = $this->brivaService->processPayment($request->all(), $accessToken, $clientSecret);
        $status = strpos($result['responseCode'], '200') === 0 ? 200 : 400;

        return response()->json($result, $status);
    }
}
