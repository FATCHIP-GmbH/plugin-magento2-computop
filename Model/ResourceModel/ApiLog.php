<?php

namespace Fatchip\Computop\Model\ResourceModel;

use Magento\Sales\Model\Order;

class ApiLog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Fields in request or response that need to be masked
     *
     * @var array
     */
    protected $maskFields = [];

    /**
     * Initialize connection and table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('computop_api_log', 'entity_id');
    }

    /**
     * Mask a given value with Xs
     *
     * @param  string $value
     * @return string
     */
    protected function maskValue($value)
    {
        for ($i = 0; $i < strlen($value); $i++) {
            $value[$i] = 'x';
        }
        return $value;
    }

    /**
     * Mask certain fields in the request array
     *
     * @param  array $array
     * @return array
     */
    protected function maskParameters($array)
    {
        foreach ($this->maskFields as $key) {
            if (isset($array[$key])) {
                $array[$key] = $this->maskValue($array[$key]);
            }
        }
        return $array;
    }

    /**
     * Returns given key from response or request or null if not set
     *
     * @param  string $key
     * @param  array $arrayA
     * @param  array $arrayB
     * @return string|null
     */
    protected function getParamValue($key, $arrayA, $arrayB = null)
    {
        if (!empty($arrayB[$key])) {
            return $arrayB[$key];
        }

        if (!empty($arrayA[$key])) {
            return $arrayA[$key];
        }
        return null;
    }

    /**
     * Cleans data for database
     *
     * @param  array $array
     * @return array
     */
    protected function cleanData($array)
    {
        if (isset($array['HMAC'])) {
            unset($array['HMAC']);
        }
        return $this->maskParameters($array);
    }

    /**
     * Save Api-log entry to database
     *
     * @param  string $requestType
     * @param  array  $request
     * @param  array  $response
     * @param  Order  $order
     * @return $this
     */
    public function addApiLogEntry($requestType, $request, $response = null, Order $order = null)
    {
        $request = $this->cleanData($request);
        $response = $this->cleanData($response);

        $this->getConnection()->insert(
            $this->getMainTable(),
            [
                'order_increment_id' => !is_null($order) ? $order->getIncrementId() : null,
                'payment_method' => !is_null($order) ? $order->getPayment()->getMethod() : null,
                'request' => $requestType,
                'response' => $this->getParamValue('Status', $response),
                'request_details' => json_encode($request),
                'response_details' => !empty($response) ? json_encode($response) : null,
                'pay_id' => $this->getParamValue('PayID', $request, $response),
                'trans_id' => $this->getParamValue('TransID', $request, $response),
                'x_id' => $this->getParamValue('XID', $request, $response),
            ]
        );
        return $this;
    }

    public function addApiLogResponse($response)
    {
        $response = $this->cleanData($response);

        $where = [
            'request = ?' => 'REDIRECT',
            'trans_id = ?' => $this->getParamValue('TransID', $response),
            'response_details IS NULL' => null,
        ];

        $this->getConnection()->update(
            $this->getMainTable(),
            [
                'response' => $this->getParamValue('Status', $response),
                'response_details' => json_encode($response),
                'pay_id' => $this->getParamValue('PayID', $response),
                'trans_id' => $this->getParamValue('TransID', $response),
                'x_id' => $this->getParamValue('XID', $response),
            ],
            $where
        );
        return $this;
    }
}

