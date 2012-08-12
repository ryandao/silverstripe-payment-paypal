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
        return PaymentGateway_Failure(null, null, $errorList);
      }
    }
  }

  /**
   * Get the token value from a valid HTTP response
   *
   * @param SS_HTTPResponse $response
   * @return String token or null
   */
  public function getToken($response) {
    $responseArr = $this->parseResponse($response);

    if (isset($response['TOKEN'])) {
      $token = $response['TOKEN'];
      $this->token = $token;
      return $token;
    }

    return null;
  }

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
