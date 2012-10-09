<?php

namespace li3_payments\extensions\adapter\gateways;
use AuthorizeNetCIM;
use AuthorizeNetCustomer;
use AuthorizeNetPaymentProfile;

class AuthorizeNet extends \lithium\core\Object {

	protected $_key;
	protected $_login;

	protected $_customer;
	protected $_payment;
	protected $_customerProfile;

	protected $_autoConfig = array('login', 'key');

	public function __construct(array $config = array()) {
		$this->_customer = new AuthorizeNetCustomer();
		$this->_payment = new AuthorizeNetPaymentProfile();
		$this->_customerProfile = new AuthorizeNetCIM($config['login'], $config['key']);
		return parent::__construct($config);
	}

	public function key(){
		return $this->_key;
	}

	public function login(){
		return $this->_login;
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
			'cardNumber' => '4007000000027',
			'expiration' => date('my', strtotime('next year'))
		);

		$payment += $defaults;

		$this->_payment->customerType = $payment['customerType'];
		$this->_payment->payment->creditCard->cardNumber = $payment['cardNumber'];
		$this->_payment->payment->creditCard->expirationDate = $payment['expiration'];

		return $this->_payment;

	}

	public function customer(array $customer = array()){

		$defaults = array(
			'description' => 'John Doe Payment Information',
			'email' => 'test@dev.com',
		);

		$customer += $defaults;

		$this->_customer->description = $customer['description'];
		$this->_customer->email = $customer['email'];
		$this->_customer->merchantCustomerId = substr(sha1(
			"{$customer['description']}{$customer['email']}"
		), 0, 20);

		return $this->_customerProfile->createCustomerProfile($this->_customer);

	}

	public function getCustomerProfile() {

		print_r($this->customer()->getCustomerProfileId()); exit;

		// if (!$response->isOk()) {
		// 	return false;
		// }
		// unset($entity->creditCard, $entity->expiration);
		// $entity->profileId = $response->getCustomerProfileId();
		// $entity->profileLookupId = $customer->merchantCustomerId;
		// return true;
		
	}


}

?>