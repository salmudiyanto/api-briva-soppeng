<?php

namespace Tests\Feature;

use Tests\TestCase;

class BrivaApiTest extends TestCase
{
    /**
     * Test Get Token B2B API Endpoint
     */
    public function testGetToken()
    {
        $response = $this->json('POST', '/snap/v1.0/access-token/b2b', [], [
            'X-CLIENT-KEY' => 'CLIENT123',
            'X-TIMESTAMP' => '2021-11-02T13:14:15.678+07:00'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'responseCode',
                     'responseMessage',
                     'accessToken',
                     'tokenType',
                     'expiresIn'
                 ]);
    }

    /**
     * Test Inquiry VA API Endpoint
     */
    public function testInquiryVa()
    {
        $payload = [
            'partnerServiceId' => '00077777',
            'customerNo' => '0000000000001',
            'virtualAccountNo' => '000777770000000000001',
            'inquiryRequestId' => 'test_inq_123',
            'amount' => [
                'value' => '200000.00',
                'currency' => 'IDR'
            ]
        ];

        $response = $this->json('POST', '/snap/v1.0/transfer-va/inquiry', $payload, [
            'Authorization' => 'Bearer sample_token_123',
            'X-TIMESTAMP' => '2021-11-02T13:14:15.678+07:00'
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'responseCode' => '2002400',
                     'virtualAccountNo' => '000777770000000000001'
                 ]);
    }

    /**
     * Test Payment VA API Endpoint
     */
    public function testPaymentVa()
    {
        $payload = [
            'partnerServiceId' => '00077777',
            'customerNo' => '0000000000001',
            'virtualAccountNo' => '000777770000000000001',
            'virtualAccountName' => 'John Doe',
            'paymentRequestId' => 'test_inq_123',
            'paidAmount' => [
                'value' => '200000.00',
                'currency' => 'IDR'
            ]
        ];

        $response = $this->json('POST', '/snap/v1.0/transfer-va/payment', $payload, [
            'Authorization' => 'Bearer sample_token_123',
            'X-TIMESTAMP' => '2021-11-02T13:14:15.678+07:00'
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'responseCode' => '2002500',
                     'paymentFlagStatus' => '00'
                 ]);
    }

    /**
     * Test Get Token API without X-TIMESTAMP header
     */
    public function testGetTokenMissingTimestamp()
    {
        $response = $this->json('POST', '/snap/v1.0/access-token/b2b', [], [
            'X-CLIENT-KEY' => 'CLIENT123'
        ]);

        $response->assertStatus(400)
                 ->assertJsonFragment([
                     'responseCode' => '4007301',
                     'responseMessage' => 'Invalid Field Format'
                 ]);
    }

    /**
     * Test Get Token API without X-TIMESTAMP header when cache is already active
     */
    public function testGetTokenMissingTimestampWithActiveCache()
    {
        // First request to populate cache
        $this->json('POST', '/snap/v1.0/access-token/b2b', [], [
            'X-CLIENT-KEY' => 'CLIENT123',
            'X-TIMESTAMP' => '2021-11-02T13:14:15.678+07:00'
        ]);

        // Second request WITHOUT X-TIMESTAMP header while cache is active
        $response = $this->json('POST', '/snap/v1.0/access-token/b2b', [], [
            'X-CLIENT-KEY' => 'CLIENT123'
        ]);

        $response->assertStatus(400)
                 ->assertJsonFragment([
                     'responseCode' => '4007301',
                     'responseMessage' => 'Invalid Field Format'
                 ]);
    }
}
