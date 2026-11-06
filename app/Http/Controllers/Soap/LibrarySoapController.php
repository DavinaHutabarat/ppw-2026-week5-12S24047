<?php

namespace App\Http\Controllers\Soap;

use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LibrarySoapController extends Controller
{
    /**
     * Menyajikan dokumen WSDL untuk operasi CheckClearanceStatus.
     */
    public function wsdl(Request $request): Response
    {
        $endpoint = url('/soap/sipus');

        $wsdl = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<definitions name="LibrarySoapService"
    targetNamespace="http://sipus.del.ac.id/soap"
    xmlns:tns="http://sipus.del.ac.id/soap"
    xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
    xmlns:xsd="http://www.w3.org/2001/XMLSchema"
    xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/"
    xmlns="http://schemas.xmlsoap.org/wsdl/">

    <types>
        <xsd:schema targetNamespace="http://sipus.del.ac.id/soap">
            <xsd:element name="CheckClearanceStatusRequest">
                <xsd:complexType>
                    <xsd:sequence>
                        <xsd:element name="nim_peminjam" type="xsd:string"/>
                    </xsd:sequence>
                </xsd:complexType>
            </xsd:element>
            <xsd:element name="CheckClearanceStatusResponse">
                <xsd:complexType>
                    <xsd:sequence>
                        <xsd:element name="nim_peminjam" type="xsd:string"/>
                        <xsd:element name="active_loans" type="xsd:int"/>
                        <xsd:element name="is_cleared" type="xsd:boolean"/>
                        <xsd:element name="status_message" type="xsd:string"/>
                    </xsd:sequence>
                </xsd:complexType>
            </xsd:element>
        </xsd:schema>
    </types>

    <message name="CheckClearanceStatusInput">
        <part name="parameters" element="tns:CheckClearanceStatusRequest"/>
    </message>

    <message name="CheckClearanceStatusOutput">
        <part name="parameters" element="tns:CheckClearanceStatusResponse"/>
    </message>

    <portType name="LibraryPortType">
        <operation name="CheckClearanceStatus">
            <input message="tns:CheckClearanceStatusInput"/>
            <output message="tns:CheckClearanceStatusOutput"/>
        </operation>
    </portType>

    <binding name="LibraryBinding" type="tns:LibraryPortType">
        <soap:binding style="document" transport="http://schemas.xmlsoap.org/soap/http"/>
        <operation name="CheckClearanceStatus">
            <soap:operation soapAction="http://sipus.del.ac.id/soap/CheckClearanceStatus"/>
            <input>
                <soap:body use="literal"/>
            </input>
            <output>
                <soap:body use="literal"/>
            </output>
        </operation>
    </binding>

    <service name="LibrarySoapService">
        <port name="LibraryPort" binding="tns:LibraryBinding">
            <soap:address location="{$endpoint}"/>
        </port>
    </service>
</definitions>
XML;

        return response($wsdl, 200, [
            'Content-Type' => 'application/wsdl+xml; charset=utf-8',
        ]);
    }

    /**
     * Memproses request SOAP CheckClearanceStatus.
     */
    public function handle(Request $request): Response
    {
        $rawXml = $request->getContent();

        if (trim($rawXml) === '') {
            return $this->fault('Client', 'Payload XML kosong.', 400);
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($rawXml);

        if ($xml === false) {
            libxml_clear_errors();
            return $this->fault('Client', 'XML tidak valid atau cacat (malformed XML).', 400);
        }

        // Tangkap elemen nim_peminjam tanpa terpengaruh namespace
        $nimNodes = $xml->xpath('//*[local-name()="nim_peminjam"]');

        if (empty($nimNodes)) {
            return $this->fault('Client', 'Elemen <nim_peminjam> wajib disertakan dalam SOAP Body.', 400);
        }

        $nim = trim((string) $nimNodes[0]);

        if ($nim === '') {
            return $this->fault('Client', 'Nilai <nim_peminjam> tidak boleh kosong.', 400);
        }

        // Hitung peminjaman berstatus 'dipinjam' milik NIM tersebut
        $activeLoans = Peminjaman::where('nim_peminjam', $nim)
            ->where('status', 'dipinjam')
            ->count();

        $isCleared = ($activeLoans === 0);
        $isClearedStr = $isCleared ? 'true' : 'false';
        $statusMessage = $isCleared
            ? 'Bebas pustaka, tidak ada pinjaman aktif'
            : "Terdapat {$activeLoans} peminjaman aktif yang belum dikembalikan";

        $escapedNim = htmlspecialchars($nim, ENT_XML1, 'UTF-8');
        $escapedMsg = htmlspecialchars($statusMessage, ENT_XML1, 'UTF-8');

        $responseXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sip="http://sipus.del.ac.id/soap">
    <soap:Body>
        <sip:CheckClearanceStatusResponse>
            <sip:nim_peminjam>{$escapedNim}</sip:nim_peminjam>
            <sip:active_loans>{$activeLoans}</sip:active_loans>
            <sip:is_cleared>{$isClearedStr}</sip:is_cleared>
            <sip:status_message>{$escapedMsg}</sip:status_message>
        </sip:CheckClearanceStatusResponse>
    </soap:Body>
</soap:Envelope>
XML;

        return response($responseXml, 200, [
            'Content-Type' => 'text/xml; charset=utf-8',
        ]);
    }

    /**
     * Membentuk respons SOAP Fault.
     */
    protected function fault(string $faultCode, string $faultString, int $httpStatus = 400): Response
    {
        $code = htmlspecialchars("soap:{$faultCode}", ENT_XML1, 'UTF-8');
        $string = htmlspecialchars($faultString, ENT_XML1, 'UTF-8');

        $faultXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <soap:Fault>
            <faultcode>{$code}</faultcode>
            <faultstring>{$string}</faultstring>
        </soap:Fault>
    </soap:Body>
</soap:Envelope>
XML;

        return response($faultXml, $httpStatus, [
            'Content-Type' => 'text/xml; charset=utf-8',
        ]);
    }
}
