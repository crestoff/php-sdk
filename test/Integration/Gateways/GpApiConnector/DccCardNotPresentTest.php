<?php

use GlobalPayments\Api\Entities\Enums\Environment;
use GlobalPayments\Api\Entities\Enums\Channel;
use GlobalPayments\Api\Entities\Enums\TransactionStatus;
use GlobalPayments\Api\PaymentMethods\CreditCardData;
use GlobalPayments\Api\ServiceConfigs\Gateways\GpApiConfig;
use GlobalPayments\Api\ServicesContainer;
use GlobalPayments\Api\Utils\GenerationUtils;
use PHPUnit\Framework\TestCase;
use GlobalPayments\Api\Utils\Logging\Logger;
use GlobalPayments\Api\Utils\Logging\SampleRequestLogger;
use \GlobalPayments\Api\Entities\GpApi\AccessTokenInfo;

class DccCardNotPresentTest extends TestCase
{
    private $currency = 'EUR';
    private $amount = 15.11;
    /** @var CreditCardData  */
    private $card;

    public function setup()
    {
        ServicesContainer::configureService($this->setUpConfig());
        $this->card = new CreditCardData();
        $this->card->number = "4006097467207025";
        $this->card->expMonth = date('m');
        $this->card->expYear = date('Y', strtotime('+1 year'));
        $this->card->cardHolderName = "James Mason";
    }

    public function setUpConfig()
    {
        $config = new GpApiConfig();
        $config->appId = 'mivbnCh6tcXhrc6hrUxb3SU8bYQPl9pd';
        $config->appKey = 'Yf6MJDNJKiqObYAb';
        $config->environment = Environment::TEST;
        $config->channel = Channel::CardNotPresent;
        $config->country = 'GB';
        $accessTokenInfo = new AccessTokenInfo();
        $accessTokenInfo->transactionProcessingAccountName = 'dcc';
        $config->accessTokenInfo = $accessTokenInfo;
//        $config->requestLogger = new SampleRequestLogger(new Logger("logs"));
        return $config;
    }

    public function testCreditGetDccInfo()
    {
        $orderId = GenerationUtils::generateOrderId();

        $dccDetails = $this->card->getDccRate()
                ->withAmount($this->amount)
                ->withCurrency($this->currency)
                ->execute();

        $this->assertNotNull($dccDetails);
        $this->assertEquals('SUCCESS', $dccDetails->responseCode);
        $this->assertEquals('AVAILABLE', $dccDetails->responseMessage);
        $this->assertNotNull($dccDetails->dccRateData);

        sleep(2);

        $response = $this->card->charge($this->amount)
            ->withCurrency($this->currency)
            ->withAllowDuplicates(true)
            ->withDccRateData($dccDetails->dccRateData)
            ->withClientTransactionId($orderId)
            ->execute();

        $this->assertNotNull($response);
        $this->assertEquals('SUCCESS', $response->responseCode);
        $this->assertEquals('CAPTURED', $response->responseMessage);
    }

    public function testCreditDccRateAuthorize()
    {
        $orderId = GenerationUtils::generateOrderId();

        $dccDetails = $this->card->getDccRate()
            ->withAmount($this->amount)
            ->withCurrency($this->currency)
            ->execute();
        $this->assertNotNull($dccDetails);
        $this->assertEquals('SUCCESS', $dccDetails->responseCode);
        $this->assertEquals('AVAILABLE', $dccDetails->responseMessage);
        $this->assertNotNull($dccDetails->dccRateData);

        sleep(2);

        $response = $this->card->authorize($this->amount)
            ->withCurrency($this->currency)
            ->withAllowDuplicates(true)
            ->withDccRateData($dccDetails->dccRateData)
            ->withClientTransactionId($orderId)
            ->execute();

        $this->assertNotNull($response);
        $this->assertEquals('SUCCESS', $response->responseCode);
        $this->assertEquals(TransactionStatus::PREAUTHORIZED, $response->responseMessage);
    }

    public function testCreditDccRateRefundStandalone()
    {
        $orderId = GenerationUtils::generateOrderId();

        $dccDetails = $this->card->getDccRate()
            ->withAmount($this->amount)
            ->withCurrency($this->currency)
            ->execute();

        $this->assertNotNull($dccDetails);
        $this->assertEquals('SUCCESS', $dccDetails->responseCode);
        $this->assertEquals('AVAILABLE', $dccDetails->responseMessage);
        $this->assertNotNull($dccDetails->dccRateData);

        sleep(2);

        $response = $this->card->refund($this->amount)
            ->withCurrency($this->currency)
            ->withAllowDuplicates(true)
            ->withDccRateData($dccDetails->dccRateData)
            ->withClientTransactionId($orderId)
            ->execute();

        $this->assertNotNull($response);
        $this->assertEquals('SUCCESS', $response->responseCode);
        $this->assertEquals('CAPTURED', $response->responseMessage);
    }

    public function testCreditDccRateReversal()
    {
        $orderId = GenerationUtils::generateOrderId();

        $dccDetails = $this->card->getDccRate()
            ->withAmount($this->amount)
            ->withCurrency($this->currency)
            ->execute();

        $this->assertNotNull($dccDetails);
        $this->assertEquals('SUCCESS', $dccDetails->responseCode);
        $this->assertEquals('AVAILABLE', $dccDetails->responseMessage);
        $this->assertNotNull($dccDetails->dccRateData);

        sleep(2);

        $transaction = $this->card->charge($this->amount)
            ->withCurrency($this->currency)
            ->withAllowDuplicates(true)
            ->withDccRateData($dccDetails->dccRateData)
            ->withClientTransactionId($orderId)
            ->execute();

        $this->assertNotNull($transaction);
        $this->assertEquals('SUCCESS', $transaction->responseCode);
        $this->assertEquals(TransactionStatus::CAPTURED, $transaction->responseMessage);

        $reverse = $transaction->reverse()
            ->withDccRateData($transaction->dccRateData)
            ->execute();

        $this->assertNotNull($reverse);
        $this->assertEquals('SUCCESS', $reverse->responseCode);
        $this->assertEquals(TransactionStatus::REVERSED, $reverse->responseMessage);
    }

    public function testCreditDccRateRefund()
    {
        $orderId = GenerationUtils::generateOrderId();

        $dccDetails = $this->card->getDccRate()
            ->withAmount($this->amount)
            ->withCurrency($this->currency)
            ->execute();

        $this->assertNotNull($dccDetails);
        $this->assertEquals('SUCCESS', $dccDetails->responseCode);
        $this->assertEquals('AVAILABLE', $dccDetails->responseMessage);
        $this->assertNotNull($dccDetails->dccRateData);

        sleep(2);

        $transaction = $this->card->charge($this->amount)
            ->withCurrency($this->currency)
            ->withAllowDuplicates(true)
            ->withDccRateData($dccDetails->dccRateData)
            ->withClientTransactionId($orderId)
            ->execute();

        $this->assertNotNull($transaction);
        $this->assertEquals('SUCCESS', $transaction->responseCode);
        $this->assertEquals(TransactionStatus::CAPTURED, $transaction->responseMessage);

        $reverse = $transaction->refund()
            ->withCurrency($this->currency)
            ->withDccRateData($dccDetails->dccRateData)
            ->execute();

        $this->assertNotNull($reverse);
        $this->assertEquals('SUCCESS', $reverse->responseCode);
        $this->assertEquals(TransactionStatus::CAPTURED, $reverse->responseMessage);
    }

    public function testAuthorizationThenCapture()
    {
        $dccDetails = $this->card->getDccRate()
            ->withAmount($this->amount)
            ->withCurrency($this->currency)
            ->execute();

        $this->assertNotNull($dccDetails);
        $this->assertEquals('SUCCESS', $dccDetails->responseCode);
        $this->assertEquals('AVAILABLE', $dccDetails->responseMessage);
        $this->assertNotNull($dccDetails->dccRateData);

        sleep(2);

        $transaction = $this->card->authorize($this->amount)
            ->withCurrency($this->currency)
            ->withAllowDuplicates(true)
            ->withDccRateData($dccDetails->dccRateData)
            ->execute();

        $this->assertNotNull($transaction);
        $this->assertEquals('SUCCESS', $transaction->responseCode);
        $this->assertEquals(TransactionStatus::PREAUTHORIZED, $transaction->responseMessage);

        $capture = $transaction->capture()
            ->withDccRateData($dccDetails->dccRateData)
            ->execute();

        $this->assertNotNull($capture);
        $this->assertEquals('SUCCESS', $capture->responseCode);
        $this->assertEquals(TransactionStatus::CAPTURED, $capture->responseMessage);
    }

    public function testCardTokenizationThenPayingWithToken()
    {
        $response = $this->card->tokenize()->execute();

        $this->assertNotNull($response);
        $this->assertEquals('SUCCESS', $response->responseCode);
        $this->assertEquals('ACTIVE', $response->responseMessage);

        $tokenizedCard = new CreditCardData();
        $tokenizedCard->token = $response->token;
        $tokenizedCard->cardHolderName = "James Mason";

        $dccDetails = $tokenizedCard->getDccRate()
            ->withAmount($this->amount)
            ->withCurrency($this->currency)
            ->execute();

        $this->assertNotNull($dccDetails);
        $this->assertEquals('SUCCESS', $dccDetails->responseCode);
        $this->assertEquals('AVAILABLE', $dccDetails->responseMessage);
        $this->assertNotNull($dccDetails->dccRateData);

        sleep(2);

        $response = $tokenizedCard->charge($this->amount)
            ->withCurrency($this->currency)
            ->withDccRateData($dccDetails->dccRateData)
            ->execute();

        $this->assertNotNull($response);
        $this->assertEquals('SUCCESS', $response->responseCode);
        $this->assertEquals(TransactionStatus::CAPTURED, $response->responseMessage);
    }
}