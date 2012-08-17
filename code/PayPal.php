<?php
/**
 * Default class for the common API of PayPal Payment pro
 */
class PayPalGateway extends PaymentGateway_GatewayHosted {
  /* PayPal constants */
  const SUCCESS_CODE = 'Success';
  const SUCCESS_WARNING = 'SuccessWithWarning';
  const FAILURE_CODE = 'Failure';
  const PAYPAL_VERSION = '51.0';

  /**
   * The data to be posted to PayPal server
   *
   * @var array
   */
  protected $postData;

  /* The PayPal base redirection URLs to process payment */
  static $payPalRedirectURL = 'https://www.paypal.com/webscr';
  static $payPalSandboxRedirectURL = 'https://www.sandbox.paypal.com/webscr';

  /**
   * Get the PayPal configuration from the config yaml file
   */
  public static function get_config() {
    return Config::inst()->get('PayPalGateway', self::get_environment());
  }

  /**
   * Get the PayPal URL (Live or Sandbox)
   */
  public static function get_url() {
    $config = self::get_config();
    return $config['url'];
  }

  /**
   * Get the PayPal redirect URL (Live or Sandbox)
   */
  public static function get_paypal_redirect_url() {
    switch (self::get_environment()) {
      case 'live':
        return self::$payPalRedirectURL;
        break;
      case 'dev':
        return self::$payPalSandboxRedirectURL;
        break;
      default:
        return null;
    }
  }

  /**
   * Get the authentication information (username, password, api signature)
   *
   * @return array
   */
  public static function get_authentication() {
    $config = self::get_config();
    return $config['authentication'];
  }

  /**
   * Get the payment action: 'Sale', 'Authorization', etc from yaml config
   */
  public static function get_action() {
    return Config::inst()->get('PayPalGateway', 'action');
  }

  public function __construct() {
    $this->gatewayURL = self::get_url();
  }

  /**
   * @see PaymentGateway::getSupportedCurrencies()
   */
  public function getSupportedCurrencies() {
    return array('AUD', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'JPY',
                 'NOK', 'NZD', 'PLN', 'GBP', 'SGD', 'SEK', 'CHF', 'USD');
  }

  /**
   * @see PaymentGateway::getSupportedCreditCardType()
   */
  public function getSupportedCreditCardType() {
    return array('visa', 'master', 'american_express');
  }

  /**
   * @see PaymentGateway::creditCardTypeIDMapping()
   */
  protected function creditCardTypeIDMapping() {
    return array(
      'visa' => 'Visa',
      'master' => 'MasterCard',
      'american_express' => 'Amex',
      // 'Maestro' => 'Maestro'
    );
  }

  /**
   * Clear the data array and prepare for a new post to PayPal.
   * Add the basic information to the data array
   */
  public function preparePayPalPost() {
    $authentication = self::get_authentication();

    $this->postData = array();
    $this->postData['USER'] = $authentication['username'];
    $this->postData['PWD'] = $authentication['password'];
    $this->postData['SIGNATURE'] = $authentication['signature'];
    $this->postData['VERSION'] = self::PAYPAL_VERSION;
  }

  /**
   * @see PaymentGateay::process()
   */
  public function process($data) {
    $this->preparePayPalPost();
    $this->postData['PAYMENTACTION'] = self::get_action();
    $this->postData['AMT'] = $data['Amount'];
    $this->postData['CURRENCY'] = $data['Currency'];
  }

  /**
  * Return an array of errors and their messages from a PayPal response
  *
  * @param SS_HTTPResponse $response
  * @return array
  */
  public function getErrors($response) {
    $errorList = array();
    $responseString = $response->getBody();
    $responseArr = $this->parseResponse($response);

    preg_match_all('/L_ERRORCODE\d+/', $responseString, $errorFields);
    preg_match_all('/L_LONGMESSAGE\d+/', $responseString, $messageFields);

    if (count($errorFields[0]) != count($messageFields[0])) {
      throw new Exception("PayPal resonse invalid: errors and messages don't match");
    } else {
      for ($i = 0; $i < count($errorFields[0]); $i++) {
        $errorField = $errorFields[0][$i];
        $errorCode = $responseArr[$errorField];
        $messageField = $messageFields[0][$i];
        $errorMessage = $responseArr[$messageField];
        $errorList[$errorCode] = $errorMessage;
      }
    }

    return $errorList;
  }

  /**
   * Parse the raw data and response from gateway
   *
   * @param $response This can be the response string itself or the
   *        string encapsulated in a HTTPResponse object
   * @return array
   */
  public function parseResponse($response) {
    if ($response instanceof RestfulService_Response) {
      parse_str($response->getBody(), $responseArr);
    } else {
      parse_str($response, $responseArr);
    }

    return $responseArr;
  }

  /**
   * Override to add buiding query string manually.
   *
   * @see PaymentGateway::postPaymentData()
   */
  public function postPaymentData($data, $endpoint = null) {
    $httpQuery = http_build_query($data);
    return parent::postPaymentData($httpQuery);
  }
}