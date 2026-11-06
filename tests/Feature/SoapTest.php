<?php

namespace Tests\Feature;

use App\Models\Peminjaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoapTest extends TestCase
{
    public function test_wsdl_endpoint_returns_xml_document(): void
    {
        $response = $this->get('/soap/sipus.wsdl');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/wsdl+xml', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('<definitions', $response->getContent());
        $this->assertStringContainsString('CheckClearanceStatus', $response->getContent());
    }

    public function test_soap_check_clearance_with_active_loans(): void
    {
        // NIM 12S24001 memiliki pinjaman aktif dari seeder (atau kita pastikan ada)
        $xmlPayload = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sip="http://sipus.del.ac.id/soap">
    <soap:Body>
        <sip:CheckClearanceStatusRequest>
            <sip:nim_peminjam>12S24001</sip:nim_peminjam>
        </sip:CheckClearanceStatusRequest>
    </soap:Body>
</soap:Envelope>
XML;

        $response = $this->call('POST', '/soap/sipus', [], [], [], [
            'CONTENT_TYPE' => 'text/xml; charset=utf-8',
        ], $xmlPayload);

        $response->assertStatus(200);
        $this->assertStringContainsString('<sip:nim_peminjam>12S24001</sip:nim_peminjam>', $response->getContent());
        $this->assertStringContainsString('<sip:is_cleared>false</sip:is_cleared>', $response->getContent());
    }

    public function test_soap_check_clearance_cleared_student(): void
    {
        $xmlPayload = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Body>
        <CheckClearanceStatusRequest>
            <nim_peminjam>12S99999</nim_peminjam>
        </CheckClearanceStatusRequest>
    </soapenv:Body>
</soapenv:Envelope>
XML;

        $response = $this->call('POST', '/soap/sipus', [], [], [], [
            'CONTENT_TYPE' => 'text/xml; charset=utf-8',
        ], $xmlPayload);

        $response->assertStatus(200);
        $this->assertStringContainsString('<sip:active_loans>0</sip:active_loans>', $response->getContent());
        $this->assertStringContainsString('<sip:is_cleared>true</sip:is_cleared>', $response->getContent());
    }

    public function test_soap_fault_on_empty_nim(): void
    {
        $xmlPayload = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <nim_peminjam></nim_peminjam>
    </soap:Body>
</soap:Envelope>
XML;

        $response = $this->call('POST', '/soap/sipus', [], [], [], [
            'CONTENT_TYPE' => 'text/xml; charset=utf-8',
        ], $xmlPayload);

        $response->assertStatus(400);
        $this->assertStringContainsString('<soap:Fault>', $response->getContent());
        $this->assertStringContainsString('tidak boleh kosong', $response->getContent());
    }

    public function test_soap_fault_on_malformed_xml(): void
    {
        $xmlPayload = '<broken-xml><soap:Envelope>';

        $response = $this->call('POST', '/soap/sipus', [], [], [], [
            'CONTENT_TYPE' => 'text/xml; charset=utf-8',
        ], $xmlPayload);

        $response->assertStatus(400);
        $this->assertStringContainsString('<soap:Fault>', $response->getContent());
    }
}
