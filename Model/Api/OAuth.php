<?php

namespace Fatchip\Computop\Model\Api;

class OAuth
{
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * @var \Fatchip\Computop\Helper\Base
     */
    protected $baseHelper;

    /**
     * @var string
     */
    protected $apiUrlTest = "https://test.computop-paygate.com/authorization/oauth/token";

    /**
     * @var string
     */
    protected $apiUrlLive = "https://www.computop-paygate.com/authorization/oauth";

    /**
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Fatchip\Computop\Helper\Base       $baseHelper
     */
    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Fatchip\Computop\Helper\Base $baseHelper
    ) {
        $this->curl = $curl;
        $this->baseHelper = $baseHelper;
    }

    /**
     * @return string
     */
    protected function getApiUrl()
    {
        if (false) { ///@TODO Implement
            return $this->apiUrlLive;
        }
        return $this->apiUrlTest;
    }

    public function getOAuthToken()
    {
        /*
        $request = [
            "grant_type" => "client_credentials",
            "client_id" => $this->baseHelper->getConfigParam('merchantid'),
            "client_secret" => $this->baseHelper->getConfigParam('restapikey'),
        ];*/

        $url = "https://test.computop-paygate.com/api/v1/payments";
        $request = [
            "transactionId" => "124191502925",
            "amount" => [
                "currency" => "EUR",
                "value" => "6400",
            ],
            "language" => "de",
            "urls" => [
                "success" => "https://localhost/computop/onepage/returned/",
                "failure" => "https://localhost/computop/onepage/failure/",
                "notify" => "https://localhost/computop/notify/index/",
            ],
            "order" => [
                "id" => "000000027",
                "description" => [
                    "Bestellung 9",
                ]
            ],
            "payment" => [
                "method" => "giropay",
            ],
        ];
/*
        "payment": {
        "method": "giropay",
                "giropay": {
            "sellingPoint": "sp",
                    "service": "products",
                    "scheme": "gir",
                    "account": {
                "number": "12345",
                        "code": "RABONL2U",
                        "accountHolder": "John Doe "
                    }
                }
            }
*/

        $this->curl->addHeader("authorization", "Basic ".base64_encode($this->baseHelper->getConfigParam('merchantid').":".$this->baseHelper->getConfigParam('restapikey')));
        $this->curl->addHeader("accept", "application/json");
        $this->curl->addHeader("Content-Type", "application/json");
        $this->curl->post($url, json_encode($request));

        $response = $this->curl->getBody();
    }
/**
 * curl --request POST \
 * --url 'https://{yourDomain}/oauth/token' \
 * --header 'content-type: application/x-www-form-urlencoded' \
 * --data grant_type=client_credentials \
 * --data client_id=YOUR_CLIENT_ID \
 * --data client_secret=YOUR_CLIENT_SECRET \
 * --data audience=YOUR_API_IDENTIFIER
 */
}