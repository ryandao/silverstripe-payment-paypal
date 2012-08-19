<?php

/**
 * Implementation of PayPalExpressCheckout
 */
class PayPalExpressGateway extends PayPalGateway {
  /**
   * The PayPal token for this transaction
   *
   * @var String
   */
  private $token = null;

  public function process($data) {
    parent::process($data);

    $this->postData['METHOD'] = 'SetExpressCheckout';
    // Add return and cancel urls
    $this->postData['RETURNURL'] = $this->returnURL;
    $this->postData['CANCELURL'] = $this->cancelURL;

    $response = $this->postPaymentData($data);
    if ($response->getStatusCode() != '200') {
      return PaymentGateway_Failure($response);
    } else {
      if ($token = $this->getToken($response)) {
        // If Authorization successful, redirect to PayPal to complete the payment
        Controller::curr()->redirect(self::get_paypal_redirect_url() . "?cmd=_express-checkout&token=$token");
      } else {
        // Otherwise, return failure message
        $errorList = $this->getErrors($response);
        return new PaymentGateway_Failure(null, null, $errorList);
      }
    }
  }

  /**
   * Get the token value from a valid HTTP response
   *
   * @param SS_HTTPResponse $response
   * @return String|null
   */
  public function getToken($response) {
    $responseArr = $this->parseResponse($response);

    if (isset($responseArr['TOKEN'])) {
      $token = $responseArr['TOKEN'];
      $this->token = $token;
      return $token;
    }

    return null;
  }

  /**
   * @see PaymentGateway_GatewayHosted
   */
  public function getResponse($response) {
    // Get the payer information
    $this->preparePayPalPost();
    $this->postData['METHOD'] = 'GetExpressCheckoutDetails';
    $this->postData['TOKEN'] = $this->token;
    $response = $this->parseResponse($this->postPaymentData($this->data));

    // If successful, complete the payment
    if ($response['ACK'] == self::SUCCESS_CODE || $response['ACK'] == self::SUCCESS_WARNING) {
      $payerID = $response['PAYERID'];

      $this->preparePayPalPost();
      $this->postData['METHOD'] = 'DoExpressCheckoutPayment';
      $this->postData['PAYERID'] = $payerID;
      $this->postData['PAYMENTREQUEST_0_PAYMENTACTION'] = (self::get_action());
      $this->postData['TOKEN'] = ($this->token);
      $response = $this->parseResponse($this->postPaymentData($this->data));

      switch ($responseArr['ACK']) {
        case self::SUCCESS_CODE:
        case self::SUCCESS_WARNING:
          return new PaymentGateway_Result();
          break;
        case self::FAILURE_CODE:
          $errorList = $this->getErrors($response);
          return new PaymentGateway_Failure(null, null, $errorList);
          break;
        default:
          return new PaymentGateway_Failure();
          break;
      }
    }
  }
}

/**
 * Gateway class to mock up PayPalExpress for testing purpose
 */
class PayPalExpressGateway_Mock extends PayPalExpressGateway {

  /* Response template strings */
  private $tokenResponseTemplate = 'TIMESTAMP=&CORRELATIONID=&TOKEN=&VERSION=BUILD=';
  private $failureResponseTemplate = 'ACK=Failure&L_ERRORCODE0=&L_SHORTMESSAGE0=&L_LONGMESSAGE0=';

  /**
   * Generate a mock token response based on the template
   */
  public function generateDummyTokenResponse() {
    $tokenResponseArr = $this->parseResponse($this->tokenResponseTemplate);

    $tokenResponseArr['TIMESTAMP'] = time();
    $tokenResponseArr['CORRELATIONID'] = 'cfcb59afaabb4';
    $tokenResponseArr['TOKEN'] = '2d6TB68159J8219744P';
    $tokenResponseArr['VERSION'] = self::PAYPAL_VERSION;
    $tokenResponseArr['BUILD'] = '1195961';

    return http_build_query($tokenResponseArr);
  }

  /**
   * Generate a mock failure response based on the template
   */
  public function generateDummyFailureResponse() {
    $failureResponseArr = $this->parseResponse($this->failureResponseTemplate);

    $failureResponseArr['L_ERRORCODE0'] = '81002';
    $failureResponseArr['L_SHORTMESSAGE0'] = 'Undefined Method';
    $failureResponseArr['L_LONGMESSAGE0'] = 'Method specified is not supported';

    return http_build_query($failureResponseArr);
  }
}
