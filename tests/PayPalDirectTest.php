<?php

class PayPalDirectTest extends SapphireTest {

  public $processor;
  public $data;

  function setUp() {
    parent::setUp();

    $paymentMethods = array('test' => array('PayPalDirect'));
    Config::inst()->remove('PaymentProcessor', 'supported_methods');
    Config::inst()->update('PaymentProcessor', 'supported_methods', $paymentMethods);

    Config::inst()->remove('PaymentGateway', 'environment');
    Config::inst()->update('PaymentGateway', 'environment', 'test');

    $this->processor = PaymentFactory::factory('PayPalDirect');
    $this->data = array(
      'Amount' => '10',
      'Currency' => 'USD',
      'CreditCard' => new CreditCard(array(
        'firstName' => 'Ryan',
        'lastName' => 'Dao',
        'type' => 'master',
        'month' => '11',
        'year' => '2016',
        'number' => '4381258770269608'
      ))
    );
  }

  function testClassConfig() {
    $this->assertEquals(get_class($this->processor), 'PaymentProcessor_MerchantHosted');
    $this->assertEquals(get_class($this->processor->gateway), 'PayPalDirectGateway_Mock');
    $this->assertEquals(get_class($this->processor->payment), 'Payment');
  }

  function testPaymentSuccess() {
    $this->processor->capture($this->data);
    $this->assertEquals($this->processor->payment->Status, Payment::SUCCESS);
  }

  function testConnectionError() {
    $this->data['Amount'] = '10.01';
    $this->processor->capture($this->data);
    $this->assertEquals($this->processor->payment->Status, Payment::FAILURE);
    $this->assertEquals($this->processor->payment->HTTPStatus, '500');
  }

  function testPaymentFailure() {
    $this->data['Amount'] = '10.02';
    $this->processor->capture($this->data);
    $this->assertEquals($this->processor->payment->Status, Payment::FAILURE);
  }
}