<?php

class PayPalExpressTest extends SapphireTest {

  public $processor;
  public $data;

  function setUp() {
    parent::setUp();

    $paymentMethods = array('test' => array('PayPalExpress'));
    Config::inst()->remove('PaymentProcessor', 'supported_methods');
    Config::inst()->update('PaymentProcessor', 'supported_methods', $paymentMethods);

    Config::inst()->remove('PaymentGateway', 'environment');
    Config::inst()->update('PaymentGateway', 'environment', 'test');

    $this->processor = PaymentFactory::factory('PayPalExpress');
    $this->data = array(
      'Amount' => '10',
      'Currency' => 'USD'
    );
  }

  function testClassConfig() {
    $this->assertEquals(get_class($this->processor), 'PaymentProcessor_GatewayHosted');
    $this->assertEquals(get_class($this->processor->gateway), 'PayPalExpressGateway_Mock');
    $this->assertEquals(get_class($this->processor->payment), 'Payment');
  }

  function testGetTokenSuccess() {
    $response = new RestfulService_Response($this->processor->gateway->generateDummyTokenResponse());
    $this->assertEquals($this->processor->gateway->getToken($response), '2d6TB68159J8219744P');
  }

  function testGetTokenFailure() {
    $response = new RestfulService_Response($this->processor->gateway->generateDummyFailureResponse());
    $this->assertNull($this->processor->gateway->getToken($response));
  }
}