<?php
/**
 * @author Leonardo Thibes <leonardothibes@gmail.com>
 * @copyright Copyright (c) The Authors
 */

namespace PayU\Payment;

use \PayU\Payment\PaymentException;
use \PayU\Api\ApiAbstract;
use \PayU\Api\ApiStatus;
use PayU\Entity\Transaction\BankTransferEntity;
use PayU\Entity\Transaction\ExtraParametersEntity;
use \PayU\Payment\PaymentTypes;
use \PayU\Entity\RequestEntity;
use \PayU\Entity\Transaction\TransactionEntity;

use \SimpleXMLElement;
use \Exception;
use \stdClass;

/**
 * Payent api class.
 *
 * @package PayU\Payment
 * @author Leonardo Thibes <leonardothibes@gmail.com>
 * @copyright Copyright (c) The Authors
 */
class PaymentApi extends ApiAbstract
{
    /**
     * PayU user id from fraud check.
     */
    const USER_ID = 80200;

    /**
     * Payment api url for production.
     * @var string
     */
    protected $apiUrlProduction = 'https://api.payulatam.com/payments-api/4.0/service.cgi';

    /**
     * Payment api url for staging.
     * @var string
     */
    protected $apiUrlStaging = 'https://stg.api.payulatam.com/payments-api/4.0/service.cgi';

    /**
     * Get html tags from PayU's fraud check.
     *
     * OBS: Client browser's need flash plugin to work.
     *
     * @param  string $deviceSessionId
     * @return string
     */
    public static function getHtml($deviceSessionId)
    {
        ob_start();
        require_once dirname(__FILE__) . '//Html/HtmlTags.phtml';
        $html = ob_get_contents();
        ob_end_clean();

        $replace = array(
            '$[deviceSessionId]' => $deviceSessionId,
            '$[usuarioId]'       => self::USER_ID,
        );
        return strtr($html, $replace);
    }

    /**
     * Ping request for service health.
     *
     * @return bool
     * @throws PaymentException
     */
    public function ping()
    {
        try {
            $json     = '{"command": "PING"}';
            $json     = $this->addMetadata($json);
            $response = $this->curlRequest($json);
            return ($response->code == ApiStatus::SUCCESS);
        } catch (Exception $e) {
            throw new PaymentException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * List all payment methods accepted by country configuration.
     *
     * @return array
     * @throws PaymentException
     */
    public function paymentMethods()
    {
        try {
            $json     = '{"command": "GET_PAYMENT_METHODS"}';
            $json     = $this->addMetadata($json);
            $response = $this->curlRequest($json);
            return $response->paymentMethods;
        } catch (Exception $e) {
            throw new PaymentException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Get Bank list for PSE
     *
     * @return array
     * @throws PaymentException
     */
    public function bankList()
    {
        try {
            $json     = '{"command": "GET_BANKS_LIST",
               "bankListInformation": {
               "paymentMethod": "PSE",
               "paymentCountry": "CO"
            }
            }';
            $json     = $this->addMetadata($json);
            $response = $this->curlRequest($json);

            return $response->banks;
        } catch (Exception $e) {
            throw new PaymentException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Compute signature of order.
     *
     * @param string $referenceCode
     * @param stirng $tx_value
     * @param string $currency
     *
     * @return string
     */
    private function computeSignature($referenceCode, $tx_value, $currency)
    {
        $signature = sprintf(
            '%s~%s~%s~%s~%s',
            $this->credentials->getApiKey(),
            $this->credentials->getMerchantId(),
            $referenceCode,
            $tx_value,
            $currency
        );
        return sha1($signature);
    }

    /**
     * Compute the device session id.
     * @return string
     */
    public static function getDeviceSessionId()
    {
        return md5(session_id().microtime());
    }

    /**
     * Make a request "authorize", "authorizeAndCapture" and "cashCollection" methods.
     *
     * @param  TransactionEntity $transaction
     * @return stdClass
     */
    private function authorizeRequest(TransactionEntity $transaction)
    {

        $requestEntity = new RequestEntity();
        $request       = $requestEntity->setCommand('SUBMIT_TRANSACTION')
                                       ->setMerchant($this->credentials)
                                       ->setTransaction($transaction)
                                       ->setIsTest($this->isStaging);

        $this->xmlRequest->addChild('language', $request->getLanguage());
        $this->xmlRequest->addChild('command', $request->getCommand());
        if (!isset($this->xmlRequest->isTest)) {
            $this->xmlRequest->addChild('isTest', ($request->getIsTest() ? 'true' : 'false'));
        }

        $merchant = $this->xmlRequest->addChild('merchant');
        $merchant->addChild('apiLogin', $request->getMerchant()->getApiLogin());
        $merchant->addChild('apiKey', $request->getMerchant()->getApiKey());

        $xmlTransaction = $this->xmlRequest->addChild('transaction');
        $xmlTransaction->addChild('type', $transaction->getType());
        $xmlTransaction->addChild('paymentMethod', $transaction->getPaymentMethod());
        $xmlTransaction->addChild('paymentCountry', $transaction->getPaymentCountry());
        $xmlTransaction->addChild('ipAddress', $transaction->getIpAddress());
        $xmlTransaction->addChild('cookie', $transaction->getCookie());
        $xmlTransaction->addChild('userAgent', $transaction->getUserAgent());
        $xmlTransaction->addChild('deviceSessionId', $transaction->getDeviceSessionId());
        $expiration = $transaction->getExpiration();
        if ($expiration > 0) {
            $date = new \DateTime('now');
            $date->modify(sprintf('+%d day', $expiration));
            $expirationDate = $date->format('Y-m-d') . 'T' . $date->format('h:i:s');
            $xmlTransaction->addChild('expirationDate', $expirationDate);
        }
        if($transaction->getPaymentMethod() != 'PSE') {
            $creditCard = $transaction->getCreditCard();
            if (!$creditCard->isEmpty()) {
                $xmlCreditCard = $xmlTransaction->addChild('creditCard');
                $xmlCreditCard->addChild('number', $creditCard->getNumber());
                $xmlCreditCard->addChild('securityCode', $creditCard->getSecurityCode());
                $xmlCreditCard->addChild('expirationDate', $creditCard->getExpirationDate());
                $xmlCreditCard->addChild('name', $creditCard->getName());
            }
        }

        $payer = $transaction->getPayer();
        if (!$payer->isEmpty()) {
            $xmlPayer = $xmlTransaction->addChild('payer');
            $xmlPayer->addChild('fullName', $payer->getFullName());
            $xmlPayer->addChild('emailAddress', $payer->getEmailAddress());
            $xmlPayer->addChild('contactPhone', $payer->getContactPhone());
            $xmlPayer->addChild('dniNumber', $payer->getDniNumber());

            $billingAddress    = $payer->getBillingAddress();
            $xmlBillingAddress = $xmlPayer->addChild('billingAddress');
            $xmlBillingAddress->addChild('street1', $billingAddress->getStreet1());
            $xmlBillingAddress->addChild('street2', $billingAddress->getStreet2());
            $xmlBillingAddress->addChild('city', $billingAddress->getCity());
            $xmlBillingAddress->addChild('state', $billingAddress->getState());
            $xmlBillingAddress->addChild('country', $billingAddress->getCountry());
            $xmlBillingAddress->addChild('postalCode', $billingAddress->getPostalCode());
            $xmlBillingAddress->addChild('phone', $billingAddress->getPhone());
        }

        $order = $transaction->getOrder();
        if (!$order->isEmpty()) {
            $xmlOrder = $xmlTransaction->addChild('order');
            $xmlOrder->addChild('accountId', $request->getMerchant()->getAccountId());
            $xmlOrder->addChild('referenceCode', $order->getReferenceCode());
            $xmlOrder->addChild('description', $order->getDescription());
            $xmlOrder->addChild('language', $order->getLanguage());
            $xmlOrder->addChild('notifyUrl', $order->getNotifyUrl());
        }

        //Order signature.
        $additionalValues = $order->getAdditionalValues()->toArray();
        $tx_value         = $additionalValues[0]['additionalValue']['value'];
        $currency         = $additionalValues[0]['additionalValue']['currency'];
        $signature        = $this->computeSignature($order->getReferenceCode(), $tx_value, $currency);
        $xmlOrder->addChild('signature', $signature);
        //Order signature.

        $buyer = $order->getBuyer();
        if (!$buyer->isEmpty()) {
            $xmlBuyer = $xmlOrder->addChild('buyer');
            $xmlBuyer->addChild('fullName', $buyer->getFullName());
            $xmlBuyer->addChild('emailAddress', $buyer->getEmailAddress());
        }

        if (!is_null($buyer->getDniNumber())) {
            $xmlBuyer->addChild('dniNumber', $buyer->getDniNumber());
        }

        if (!is_null($buyer->getCnpj())) {
            $xmlBuyer->addChild('cnpj', $buyer->getCnpj());
        }

        $shippingAddress = $order->getShippingAddress();

        if (!$shippingAddress->isEmpty()) {
            $xmlShippingAddress = $xmlOrder->addChild('shippingAddress');
            $xmlShippingAddress->addChild('street1', $shippingAddress->getStreet1());
            $xmlShippingAddress->addChild('street2', $shippingAddress->getStreet2());
            $xmlShippingAddress->addChild('city', $shippingAddress->getCity());
            $xmlShippingAddress->addChild('state', $shippingAddress->getState());
            $xmlShippingAddress->addChild('country', $shippingAddress->getCountry());
            $xmlShippingAddress->addChild('postalCode', $shippingAddress->getPostalCode());
            $xmlShippingAddress->addChild('phone', $shippingAddress->getPhone());

            $xmlBuyerShippingAddress = $xmlBuyer->addChild('shippingAddress');
            $xmlBuyerShippingAddress->addChild('street1', $shippingAddress->getStreet1());
            $xmlBuyerShippingAddress->addChild('street2', $shippingAddress->getStreet2());
            $xmlBuyerShippingAddress->addChild('city', $shippingAddress->getCity());
            $xmlBuyerShippingAddress->addChild('state', $shippingAddress->getState());
            $xmlBuyerShippingAddress->addChild('country', $shippingAddress->getCountry());
            $xmlBuyerShippingAddress->addChild('postalCode', $shippingAddress->getPostalCode());
            $xmlBuyerShippingAddress->addChild('phone', $shippingAddress->getPhone());
        }

        $additionalValues    = $order->getAdditionalValues()->toArray();
        $xmlAdditionalValues = $xmlOrder->addChild('additionalValues');

        foreach ($additionalValues as $value) {
            $entry = $xmlAdditionalValues->addChild('entry');
            $entry->addChild('string', $value['string']);
            $additionalValue = $entry->addChild('additionalValue');
            $additionalValue->addChild('currency', $value['additionalValue']['currency']);
            $additionalValue->addChild('value', $value['additionalValue']['value']);
        }

        if($transaction->getPaymentMethod() != 'PSE') {
            /** @var ExtraParametersEntity $extraParameters */
            $extraParameters    = $transaction->getExtraParameters()->toArray();
        }
        else{
            /** @var BankTransferEntity $extraParameters */
            $extraParameters    = $transaction->getExtraParameters()->toArray();
        }

        $xmlExtraParameters = $xmlTransaction->addChild('extraParameters');
        if (count($extraParameters) > 0) {
            foreach ($extraParameters as $label => $value) {
                $entry = $xmlExtraParameters->addChild('entry');
                $entry->addChild('string', $label);
                $entry->addChild('string', $value);
            }
        }

        $this->setLastXmlRequest($this->xmlRequest);
        $response = $this->curlRequestXml(
            $this->xmlRequest->asXML()
        );

        $this->resetRequest();

        return $response;

    }

    /**
     * Authorize a payment order.
     *
     * @param  TransactionEntity $transaction
     * @return stdClass
     */
    public function authorize(TransactionEntity $transaction)
    {
        $transaction->setType(PaymentTypes::AUTHORIZATION);
        return $this->authorizeRequest($transaction);
    }

    /**
     * Capture an payment.
     */
    public function capture($orderId, $transactionId)
    {
        return $this->buildChildRequest($orderId, $transactionId, PaymentTypes::CAPTURE);
    }

    /**
     * Authorize and capture a payment order.
     *
     * @param  TransactionEntity $transaction
     * @return stdClass
     */
    public function authorizeAndCapture(TransactionEntity $transaction)
    {
        $transaction->setType(PaymentTypes::AUTHORIZATION_AND_CAPTURE);
        return $this->authorizeRequest($transaction);
    }

    /**
     * Make a cash collection request for payment order.
     *
     * @param TransactionEntity $transaction
     * @param int               $expiration
     *
     * @return stdClass
     */
    public function cashCollection(TransactionEntity $transaction, $expiration = 4)
    {
        $this->xmlRequest->addChild('isTest', 'false');
        $transaction->setType(PaymentTypes::AUTHORIZATION_AND_CAPTURE)
                    ->setExpiration($expiration);
        return $this->authorizeRequest($transaction);
    }

    /**
     * Cancel the transaction and no money is charged from the buyer.
     *
     * @param int    $orderId       Order identification of payU.
     * @param string $transactionId PayU transaction identification.
     *
     * @return stdClass
     */
    public function refund($orderId, $transactionId)
    {
        return $this->buildChildRequest($orderId, $transactionId, PaymentTypes::REFUND);
    }

    /**
     * Void transaction.
     *
     * @param $orderId
     * @param $transactionId
     *
     * @return stdClass
     */
    public function void($orderId, $transactionId)
    {
        return $this->buildChildRequest($orderId, $transactionId, PaymentTypes::VOID);
    }

    /**
     * @param $orderId
     * @param $transactionId
     * @param $transactionType
     *
     * @return stdClass
     * @throws \PayU\PayUException
     */
    protected function buildChildRequest($orderId, $transactionId, $transactionType)
    {
        $this->xmlRequest->addChild('language', $this->language);
        $this->xmlRequest->addChild('command', 'SUBMIT_TRANSACTION');
        $this->xmlRequest->addChild('isTest', ($this->isStaging ? 'true' : 'false'));

        $merchant = $this->xmlRequest->addChild('merchant');
        $merchant->addChild('apiLogin', $this->credentials->getApiLogin());
        $merchant->addChild('apiKey', $this->credentials->getApiKey());

        $xmlTransaction = $this->xmlRequest->addChild('transaction');
        $xmlTransaction->addChild('type', $transactionType);
        $xmlTransaction->addChild('parentTransactionId', $transactionId);

        $order = $xmlTransaction->addChild('order');
        $order->addChild('id', $orderId);

        $this->setLastXmlRequest($this->xmlRequest);

        $response = $this->curlRequestXml(
            $this->xmlRequest->asXML()
        );

        $this->resetRequest();
        return $response;
    }
}
