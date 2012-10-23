<?php

namespace li3_payments\extensions\adapter\gateways;
use AuthorizeNetCIM;
use AuthorizeNetCustomer;
use AuthorizeNetPaymentProfile;
use AuthorizeNetTransaction;
use AuthorizeNetLineItem;

class AuthorizeNet extends \lithium\core\Object {

	protected $_key;
	protected $_login;

	protected $_customer;
	protected $_payment;
	protected $_customerProfile;
	protected $_transaction;
	protected $_mode; // "none", "testMode", "liveMode"

	protected $_autoConfig = array('login', 'key');

	/**
	 * Test card numbers for AuthNet sandbox
	 * @var array
	 */
	public static $cards = array(
		'dinersclub' 	=> '38000000000006',
		'amex'			=> '370000000000002',
		'disc'			=> '6011000000000012',
		'visa1'			=> '4007000000027',
		'visa2'			=> '4012888818888',
		'jcb'			=> '3088000000000017'
	);

	public function __construct(array $config = array()) {
		$this->_mode = isset($config['validation_mode']) ? $config['validation_mode'] : 'none';
		$this->_customer = new AuthorizeNetCustomer();
		$this->_payment = new AuthorizeNetPaymentProfile();
		$this->_customerProfile = new AuthorizeNetCIM($config['login'], $config['key']);
		$this->_transaction = new AuthorizeNetTransaction;
		return parent::__construct($config);
	}

	public function key(){
		return $this->_key;
	}

	public function login(){
		return $this->_login;
	}

	public function card($provider = 'visa1'){
		return static::$cards[$provider];
	}

	/**
	 * Build AuthNet payment object
	 * Defaults to test payment if nothing is supplied.
	 * @param  array  $payment payment details. Card #, Expiration, customer type
	 * @return object          Authorize.Net payment profile object
	 */
	public function payment(array $payment = array()){

		$defaults = array(
			'customerType' => 'individual',
			'cardNumber' => $this->card(),
			'expiration' => date('Y-m', strtotime('next year'))
		);

		$payment += $defaults;

		$this->_payment->customerType = $payment['customerType'];
		$this->_payment->payment->creditCard->cardNumber = $payment['cardNumber'];
		$this->_payment->payment->creditCard->expirationDate = $payment['expiration'];

		return $this->_payment;

	}

	/**
	 * Creates a customer profile
	 * @param  array  $customer required options to pass to authnet
	 * @return int           customer profile id
	 */
	public function customerProfile(array $customer = array()){

		$defaults = array(
			'description' => 'John Doe Payment Information',
			'email' => 'test@dev.com',
		);

		$defaults['customerid'] = substr(sha1(
			"{$defaults['description']}{$defaults['email']}"
		), 0, 20);

		$customer += $defaults;

		$this->_customer->description = $customer['description'];
		$this->_customer->email = $customer['email'];
		$this->_customer->merchantCustomerId = $customer['customerid'];

		$response = $this->_customerProfile->createCustomerProfile($this->_customer, $this->_mode);

		$return = new \stdClass();

		if($response->isOk()){

			$return->status = 'success';
			$return->profile_id = $response->getCustomerProfileId();
			$return->payment_profile = $response->getCustomerPaymentProfileIds();

		} else {
			print_r($response); exit;
			$return->status = 'failure';
			$return->code = $response->getMessageCode();
			$return->message = $response->getMessageText();

			// hacky way of returning duplicate profile id - may not even be useful
			if($response->getMessageCode() == 'E00039' 
				&& preg_match_all('/([0-9]+)/', $response->getMessageText(), $matches)){
				$return->profile_id = $matches[0][0];
			}

		}

		return $return;

	}

	public function customerPaymentProfile($payment){
		if(get_class($payment) == 'AuthorizeNetPaymentProfile'){
			return $this->_customer->paymentProfiles[] = $payment;
		} else {
			return false;
		}
	}

	/**
	 * Build a transaction
	 * @param  array  $data required fields: amount | profileId | paymentId. optional field: shippingId
	 * @return object       AuthNet transaction object
	 */
	public function runTransaction($data = array()){

		$this->_transaction->amount = $data['amount'];
		$this->_transaction->customerProfileId = $data['profileId'];
		$this->_transaction->customerPaymentProfileId = $data['paymentId'];

		if(isset($data['shippingId'])){
			$this->_transaction->customerShippingAddressId = $data['shippingId'];
		}

		// return $this->_transaction;

		$_response = $this->_customerProfile->createCustomerProfileTransaction("AuthCapture", $this->_transaction);

		$response = $_response->getTransactionResponse();

		$return = new \stdClass();

		if($response->error || $response->declined){
			$return->status = 'failure';
			$return->message = $response->response_reason_text;
			$return->error = $response->error_message;
		} else {
			$return->status = 'success';
			$return->transaction_id = $response->transaction_id;
		}

		return $return;

	}

	/**
	 * Add a line item to a transaction
	 * @param  array  $data line item details
	 * @return object       AuthNet transaction object
	 */
	public function lineItem($data = array()){

		$item = new AuthorizeNetLineItem;
		$item->itemId      = $data['id'] ?: "1";
		$item->name        = $data['name'];
		$item->description = $data['description'];
		$item->quantity    = $data['quantity'];
		$item->unitPrice   = $data['price'];
		$item->taxable     = $data['taxable'];

		$this->_transaction->lineItems[] = $item;

		return $this->_transaction;

	}



}

?>