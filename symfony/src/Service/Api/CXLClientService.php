<?php

namespace App\Service\Api;

use Psr\Log\LoggerInterface;

class CXLClientService
{
    const LEFT_ANGLE = '&lt;';
    const RIGHT_ANGLE = '&gt;';

    const PROPERTY_TYPE_STRING = 'STRING';
    const PROPERTY_TYPE_INTEGER = 'INTEGER';

    const MACHINE_NAME = 'cylipol';

    private $url;

    private $username;

    private $password;

    private $token;

    private $logger;

    /**
     * CXLClientService constructor.
     * @param string $url
     * @param string $username
     * @param string $password
     */
    public function __construct(string $url, string $username, string $password, LoggerInterface $logger)
    {
        $this->url = $url;

        $this->username = $username;

        $this->password = $password;

        $this->logger = $logger;
    }

    /**
     * Define headers parameters
     *
     * @param string $soapAction
     * @param string $xml
     * @return array
     */
    private function prepareHeaders(string $soapAction, string $xml)
    {
        return [
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "SOAPAction: " . $soapAction,
            "Content-length: " . strlen($xml),
        ];
    }

    /**
     * Define CXL body template
     *
     * @param string $service
     * @param string $function
     * @param string $messageId
     * @param string|null $fileType
     * @param string $entityName
     * @param array $properties
     * @param boolean $lastTransactionInd
     * @param boolean $multiResponse
     * @return string
     */
    private function prepareRequestBody(
        string $service,
        string $function,
        string $messageId,
        ?string $fileType = null,
        string $entityName,
        array $properties,
        bool $lastTransactionInd = false,
        bool $multiResponse = false
    ) {
        $xml = '<soap-env:Envelope xmlns:soap-env="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/1999/XMLSchema" xmlns:xsi="http://www.w3.org/1999/XMLSchema-instance">';
        $xml .= '<soap-env:Body>';
        $xml .= '<ns1:' . $service . ' xmlns:ns1="' . $function . '">';
        $xml .= '<msgstr>';

        // ---> Request body
        $xml .= self::LEFT_ANGLE . 'Message messageId="' . $messageId . '" ' . ($fileType ? 'filetype="' . $fileType . '"' : '') . ($multiResponse ? ' multiresponse="True"' : '') . self::RIGHT_ANGLE;
        $xml .= self::LEFT_ANGLE . 'Entity name="' . $entityName . '"' . self::RIGHT_ANGLE;

        foreach ($properties as $property) {
            $xml .= self::LEFT_ANGLE . 'Property name="' . $property['name'] . '" value="' . $property['value'] . '" type="' . $property['type'] . '" /' . self::RIGHT_ANGLE;
        }

        $xml .= self::LEFT_ANGLE . '/Entity' . self::RIGHT_ANGLE;

        if ($lastTransactionInd) {
            $xml .= self::LEFT_ANGLE . 'last_transaction_ind value="1"/' . self::RIGHT_ANGLE;
        }

        $xml .= self::LEFT_ANGLE . '/Message' . self::RIGHT_ANGLE;

        // <--- Request body

        $xml .= '</msgstr>';
        $xml .= '</ns1:' . $service . '>';
        $xml .= '</soap-env:Body>';
        $xml .= '</soap-env:Envelope>';

        return $xml;
    }

    /**
     * Define CXL Trades request Body
     *
     * @param string $method
     * @param string $serviceName
     * @param string $dateFrom
     * @param string $dateTo
     * @param string $lastModifiedDateFrom
     * @param string $lastModifiedDateTo
     * @return string
     */
    private function prepareTradesRequestBody(string $method, string $serviceName, string $dateFrom, string $dateTo, string $lastModifiedDateFrom, string $lastModifiedDateTo)
    {
        $xml = '<soap-env:Envelope xmlns:soap-env="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/1999/XMLSchema" xmlns:xsi="http://www.w3.org/1999/XMLSchema-instance">';
        $xml .= '<soap-env:Body>';
        $xml .= '<ns1:' . $method . ' xmlns:ns1="' . $serviceName . '">';
        $xml .= '<msgstr>';

        // ---> Request body
        $xml .= self::LEFT_ANGLE . '?xml version="1.0"?'. self::RIGHT_ANGLE;
        $xml .= self::LEFT_ANGLE . 'request xmlns:xsi="http://www.w3.org/1999/XMLSchema-instance" xsi:noNamespaceSchemaLocation="request.xsd"'. self::RIGHT_ANGLE;
        $xml .= self::LEFT_ANGLE . 'mhead name="TPTReqTradeQuery"'. self::RIGHT_ANGLE;
        $xml .= self::LEFT_ANGLE . 'sender' . self::RIGHT_ANGLE . '{1DA03849-6AB4-44D4-BA8D-0A3AECFCE31E}' . self::LEFT_ANGLE . '/sender'. self::RIGHT_ANGLE;
        $xml .= self::LEFT_ANGLE . 'sent_dt' . self::RIGHT_ANGLE . '20180716111759' . self::LEFT_ANGLE . '/sent_dt'. self::RIGHT_ANGLE;
        $xml .= self::LEFT_ANGLE . 'resp_enc' . self::RIGHT_ANGLE . 'TPT' . self::LEFT_ANGLE . '/resp_enc'. self::RIGHT_ANGLE;
        $xml .= self::LEFT_ANGLE . '/mhead'. self::RIGHT_ANGLE;

        $xml .=  self::LEFT_ANGLE . 'mbody'. self::RIGHT_ANGLE;
        $xml .=  self::LEFT_ANGLE . 'data'. self::RIGHT_ANGLE;
        $xml .=     self::LEFT_ANGLE . 'TradeQuery' . self::RIGHT_ANGLE;
        $xml .=         self::LEFT_ANGLE . 'trade_dt' . self::RIGHT_ANGLE . self::LEFT_ANGLE . '![CDATA[' . $dateFrom . ':' . $dateTo . ']]' . self::RIGHT_ANGLE . self::LEFT_ANGLE . '/trade_dt' . self::RIGHT_ANGLE;
        $xml .=         self::LEFT_ANGLE . 'last_modify_dt' . self::RIGHT_ANGLE . self::LEFT_ANGLE . '![CDATA[' . $lastModifiedDateFrom . ':' . $lastModifiedDateTo . ']]' . self::RIGHT_ANGLE . self::LEFT_ANGLE . '/last_modify_dt' . self::RIGHT_ANGLE;
        $xml .=         self::LEFT_ANGLE . 'trade_status_ind' . self::RIGHT_ANGLE . self::LEFT_ANGLE . '![CDATA[Accepted|Verified|Void]]' . self::RIGHT_ANGLE . self::LEFT_ANGLE . '/trade_status_ind' . self::RIGHT_ANGLE;
        $xml .=         self::LEFT_ANGLE . 'record_type_ind' . self::RIGHT_ANGLE . '0' . self::LEFT_ANGLE . '/record_type_ind' . self::RIGHT_ANGLE;
        $xml .=         self::LEFT_ANGLE . 'groupby' . self::RIGHT_ANGLE . self::LEFT_ANGLE . '/groupby' . self::RIGHT_ANGLE;
        $xml .=         self::LEFT_ANGLE . 'select' . self::RIGHT_ANGLE;
        $xml .=             self::LEFT_ANGLE . 'detail_num/' . self::RIGHT_ANGLE;
        $xml .=             self::LEFT_ANGLE . 'trade_num/' . self::RIGHT_ANGLE;
        $xml .=             self::LEFT_ANGLE . 'trade_dt/' . self::RIGHT_ANGLE;
        $xml .=             self::LEFT_ANGLE . 'trade_type_cd/' . self::RIGHT_ANGLE;
        $xml .=             self::LEFT_ANGLE . 'trade_status_ind/' . self::RIGHT_ANGLE;
        $xml .=             self::LEFT_ANGLE . 'buy_sell_ind/' . self::RIGHT_ANGLE;
        $xml .=             self::LEFT_ANGLE . 'put_call_ind/' . self::RIGHT_ANGLE;
        $xml .=             self::LEFT_ANGLE . 'trade_qty/' . self::RIGHT_ANGLE;
        $xml .=             self::LEFT_ANGLE . 'start_dt/' . self::RIGHT_ANGLE;
        $xml .=             self::LEFT_ANGLE . 'end_dt/' . self::RIGHT_ANGLE;
        $xml .=             self::LEFT_ANGLE . 'counterpart_company_num/' . self::RIGHT_ANGLE;
        $xml .=             self::LEFT_ANGLE . 'deal_id2/' . self::RIGHT_ANGLE;
        $xml .=             self::LEFT_ANGLE . 'internal_company_num/' . self::RIGHT_ANGLE;
        $xml .=             self::LEFT_ANGLE . 'qty_uom_cd/' . self::RIGHT_ANGLE;
        $xml .=             self::LEFT_ANGLE . 'venture_cd/' . self::RIGHT_ANGLE;
        $xml .=             self::LEFT_ANGLE . 'trade_price/' . self::RIGHT_ANGLE;
        $xml .=             self::LEFT_ANGLE . 'strike_price/' . self::RIGHT_ANGLE;
        $xml .=             self::LEFT_ANGLE . 'last_modify_dt/' . self::RIGHT_ANGLE;
        $xml .=         self::LEFT_ANGLE . '/select' . self::RIGHT_ANGLE;
        $xml .=     self::LEFT_ANGLE . '/TradeQuery' . self::RIGHT_ANGLE;
        $xml .=  self::LEFT_ANGLE . '/data' . self::RIGHT_ANGLE;
        $xml .= self::LEFT_ANGLE . '/mbody' . self::RIGHT_ANGLE;
        $xml .= self::LEFT_ANGLE . '/request' . self::RIGHT_ANGLE;
        // <--- Request body

        $xml .= '</msgstr>';
        $xml .= '</ns1:' . $method . '>';
        $xml .= '</soap-env:Body>';
        $xml .= '</soap-env:Envelope>';

        return $xml;
    }

   /**
    * Define authentication parameters
    *
    * @return void
    */
    private function authenticate()
    {
        $properties = [
            [
                'name' => 'login_cd',
                'value' => $this->username,
                'type' => self::PROPERTY_TYPE_STRING
            ],
            [
                'name' => 'password',
                'value' => $this->password,
                'type' => self::PROPERTY_TYPE_STRING
            ],
            [
                'name' => 'machine_name',
                'value' => self::MACHINE_NAME,
                'type' => self::PROPERTY_TYPE_STRING
            ],
            [
                'name' => 'language_code',
                'value' => 'en',
                'type' => self::PROPERTY_TYPE_STRING
            ],
            [
                'name' => 'request_source_ind',
                'value' => '1',
                'type' => self::PROPERTY_TYPE_STRING
            ],
            [
                'name' => 'client_version_cd',
                'value' => '8.7.19.0.06-75757af',
                'type' => self::PROPERTY_TYPE_STRING
            ],
        ];

        $xml = $this->prepareRequestBody('Authenticate', 'urn:Login', '0', null, 'REF_LOGIN', $properties);
        $headers = $this->prepareHeaders('urn:Authenticate::Login', $xml);
        $response = $this->sendRequest($headers, $xml);
        $responseCleaned = htmlspecialchars_decode($response);
        preg_match('/(<\?xml version="1\.0"\?><MessageResponse).*(<\/MessageResponse>)/', $responseCleaned, $matches);

        if (isset($matches[0]) && $matches[0]) {
            $xmlObject = simplexml_load_string($matches[0]);

            if (isset($xmlObject['sessionid'])) {
                $this->token = $xmlObject['sessionid'];
            } else {
                throw new \Exception('No token retrieved');
            }
        } else {
            throw new \Exception('No response found');
        }
    }

    /**
     * Send CXL request to get data from API
     *
     * @param array $headers
     * @param string $xml
     * @param boolean $authenticated
     * @return boolean
     */
    public function sendRequest(array $headers, string $xml, bool $authenticated = false)
    {
        $url = $this->url;

        if ($authenticated && $this->token) {
            $url .= $this->token;
        }

        $soap_do = curl_init();

        curl_setopt($soap_do, CURLOPT_URL, $url);
        curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 3000);
        curl_setopt($soap_do, CURLOPT_TIMEOUT,        120000);
        curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($soap_do, CURLOPT_POST,           true);
        curl_setopt($soap_do, CURLOPT_POSTFIELDS,     $xml);
        curl_setopt($soap_do, CURLOPT_HTTPHEADER,     $headers);
        curl_setopt($soap_do, CURLINFO_HEADER_OUT, true);

        $curl_exec = curl_exec($soap_do);

        if (curl_error($soap_do)) {
            $error_msg = curl_error($soap_do);
            $this->logger->error($error_msg);
        }

        $responseCode = curl_getinfo($soap_do, CURLINFO_HTTP_CODE);

        if ($responseCode != 200) {
            $this->logger->error("CXL HTTP Error: " . $responseCode);
        }

        if (isset($error_msg)) {
            throw new \Exception($error_msg);
        }

        curl_close($soap_do);

        return $curl_exec;
    }

    /**
     * Retrieve products from CXL
     *
     * @return void
     */
    public function getProducts()
    {
        $this->authenticate();

        $properties = [
            [
                'name' => 'status_ind',
                'value' => '1',
                'type' => self::PROPERTY_TYPE_INTEGER
            ],
            [
                'name' => 'quote_def_type_ind',
                'value' => '0',
                'type' => self::PROPERTY_TYPE_INTEGER
            ]
        ];

        $xml = $this->prepareRequestBody(
            'GetReferenceDataItem',
            'urn:ReferenceData',
            'messageid',
            'TPT',
            'MKT_QUOTE_DEFINITION',
            $properties,
            true,
            true
        );

        $headers = $this->prepareHeaders('urn:ReferenceData::GetReferenceDataItem', $xml, true);
        return $this->sendRequest($headers, $xml, true);
    }

    /**
     * Retrieve strategies from API
     *
     * @return void
     */
    public function getStrategies()
    {
        $this->authenticate();

        $properties = [
            [
                'name' => 'status_ind',
                'value' => '1',
                'type' => self::PROPERTY_TYPE_INTEGER
            ],
            [
                'name' => 'lob_cd',
                'value' => 'RISK MGT',
                'type' => self::PROPERTY_TYPE_STRING
            ]
        ];

        $xml = $this->prepareRequestBody(
            'GetReferenceDataItem',
            'urn:ReferenceData',
            'messageid',
            'TPT',
            'ORG_STRATEGY',
            $properties,
            true,
            true
        );

        $headers = $this->prepareHeaders('urn:ReferenceData::GetReferenceDataItem', $xml, true);


        return $this->sendRequest($headers, $xml, true);
    }

    /**
     * Retrieve trades from CXL
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param string $lastModifiedDateFrom
     * @param string $lastModifiedDateTo
     * @return void
     */
    public function getTrades(string $dateFrom, string $dateTo, string $lastModifiedDateFrom, string $lastModifiedDateTo)
    {
        $this->authenticate();

        $xml = $this->prepareTradesRequestBody('GetTradeSummaryList', 'TradeQuery', $dateFrom, $dateTo, $lastModifiedDateFrom, $lastModifiedDateTo);

        $headers = $this->prepareHeaders('urn:TradeQuery::GetTradeSummaryList', $xml, true);

        return $this->sendRequest($headers, $xml, true);
    }
}
