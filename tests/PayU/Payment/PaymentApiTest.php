<?php
/**
 * @author Leonardo Thibes <leonardothibes@gmail.com>
 * @copyright Copyright (c) The Authors
 */

namespace PayU\Payment;

use \stdClass;
use \PayU\Payment\PaymentApi;
use \PayU\Merchant\MerchantCredentials;

require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'bootstrap.php';

/**
 * @author Leonardo Thibes <leonardothibes@gmail.com>
 * @copyright Copyright (c) The Authors
 */
class PaymentApiTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentApi
     */
    protected $object = null;

    /**
     * @var MerchantCredentials
     */
    protected $credentials = null;

    /**
     * Setup.
     */
    protected function setUp()
    {
    	$this->credentials = MerchantCredentials::getInstance();
    	$this->credentials->setApiLogin(PAYU_API_LOGIN)
		                  ->setApiKey(PAYU_API_KEY);
    	$this->object = new PaymentApi($this->credentials);
    	$this->object->setStaging();
    }

    /**
     * TearDown.
     */
    protected function tearDown()
    {
    	$this->credentials->resetInstance();
    	unset($this->object, $this->credentials);
    }

    /**
     * @see ApiAbstract::getApiUrl()
     */
    public function testGetApiUrlInStaging()
    {
    	$rs = $this->object->setStaging(true)->getApiUrl();
    	$this->assertEquals('https://stg.api.payulatam.com/payments-api/4.0/service.cgi', $rs);
    }

    /**
     * @see ApiAbstract::getApiUrl()
     */
    public function testGetApiUrlInProduction()
    {
    	$rs = $this->object->setStaging(false)->getApiUrl();
    	$this->assertEquals('https://api.payulatam.com/payments-api/4.0/service.cgi', $rs);
    }

	/**
	 * @see PaymentApi::ping()
	 */
    public function testPing()
    {
    	$rs = $this->object->ping();
    	$this->assertInternalType('bool', $rs);
    	$this->assertTrue($rs);
    }

    /**
     * @see PaymentApi::ping()
     */
    public function testPingWrongCredentials()
    {
    	try {
    		$this->credentials->setApiLogin('wrong-login')
    		                  ->setApiKey('wrong-key');
    		$rs = $this->object->ping();
    	} catch (\Exception $e) {
    		$this->assertInstanceOf('\PayU\PayUException', $e);
    		$this->assertEquals('Invalid credentials', $e->getMessage());
    		$this->assertEquals(0, $e->getCode());
    	}
    }

    /**
     * @see PaymentApi::paymentMethods()
     */
    public function testPaymentMethods()
    {
    	$rs = $this->object->paymentMethods();

    	$this->assertInternalType('array', $rs);
    	$this->assertGreaterThan(0, count($rs));

    	foreach ($rs as $paymentMethod) {

    		$this->assertInstanceOf('\stdClass', $paymentMethod);

    		$this->assertTrue(isset($paymentMethod->id));
    		$this->assertTrue(is_numeric($paymentMethod->id));

    		$this->assertTrue(isset($paymentMethod->description));
    		$this->assertGreaterThan(0, strlen($paymentMethod->description));

    		$this->assertTrue(isset($paymentMethod->country));
    		$this->assertEquals(2, strlen($paymentMethod->country));
    	}
    }

    /**
     * @see PaymentApi::paymentMethods()
     */
    public function testPaymentCredentials()
    {
    	try {
    		$this->credentials->setApiLogin('wrong-login')
    	                      ->setApiKey('wrong-key');
    		$rs = $this->object->paymentMethods();
    	} catch (\Exception $e) {
    		$this->assertInstanceOf('\PayU\PayUException', $e);
    		$this->assertEquals('Invalid credentials', $e->getMessage());
    		$this->assertEquals(0, $e->getCode());
    	}
    }
}