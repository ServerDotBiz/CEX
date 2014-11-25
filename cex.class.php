<?php

/**
 * CEX API class
 *
 * This class allows a user to call the cex.io API as described here: https://cex.io/api
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to The MIT License (MIT)
 *
 * @category   Bitcoin
 * @package    CEX
 * @author     Roy Boverhof <roy@server.biz>
 * @donations  16vq2qDf3VxDH56rc1p47W8tAwhwQX1z7H
 * @twitter    https://twitter.com/Boverhof
 * @copyright  2014 Roy Boverhof
 * @license    http://opensource.org/licenses/MIT
 * @version    1.0
 * @link       https://github.com/ServerDotBiz/CEX
 *
 */

class CEX {
	private $_api_username;
	private $_api_key;
	private $_api_secret;
	private $_api_nonce;
	private $_api_url;
	private $_api_cert;
	private $_currency_array;
	private $_debug;

	/**
	 * Create a new CEX object
	 * @param string $api_username
	 * @param string $api_key
	 * @param string $api_secret
	 * @param string $api_url
	 * @param string $api_cert
	 */
	public function __construct($api_username, $api_key, $api_secret, $api_url, $api_cert=false, $debug=false) {
		$this->_api_username = $api_username;
		$this->_api_key = $api_key;
		$this->_api_secret = $api_secret;
		$this->_api_nonce = time();
		$this->_api_url = $api_url;
		$this->_api_cert = $api_cert;

		$this->_symbols_pair = array(
			// USD markets
			'BTC/USD',
			'GHS/USD',
			'LTC/USD',
			'DOGE/USD',
			'DRK/USD',
			// EUR markets
			'BTC/EUR',
			'LTC/EUR',
			'DOGE/EUR',
			'DRK/EUR',
			// BTC markets
			'GHS/BTC',
			'LTC/BTC',
			'DOGE/BTC',
			'DRK/BTC',
			'NMC/BTC',
			'IXC/BTC',
			'POT/BTC',
			'ANC/BTC',
			'MEC/BTC',
			'WDC/BTC',
			'FTC/BTC',
			'DGB/BTC',
			'USDE/BTC',
			'MYR/BTC',
			'AUR/BTC',
			// LTC markets
			'GHS/LTC',
			'DOGE/LTC',
			'DRK/LTC',
			'MEC/LTC',
			'WDC/LTC',
			'ANC/LTC',
			'FTC/LTC'
		);

		$this->_debug = $debug;
	}

	/**
	 * Initialize cURL
	 * @param string $url
	 * @return array cURL handle
	 */
	private function _initCurl($url) {
		$ch = null;

		if (($ch = @curl_init($url)) == false) {
			header("HTTP/1.1 500", true, 500);
			die("Cannot initialize cUrl session, please check if cURL is enabled / installed properly.");
		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_ENCODING, 1);

		if ($this->_api_cert){
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_CAINFO, $this->_api_cert);
		} else {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}

		if ($this->_debug){
			curl_setopt($ch, CURLOPT_VERBOSE, true);
			$verbose = fopen('curl.log', 'a+');
			curl_setopt($ch, CURLOPT_STDERR, $verbose);
		}

		return($ch);
	}

	/**
	 * Generate signature for private API calls
	 * @return string $signature
	 */
	private function _signature() {
		$message = $this->_api_nonce.$this->_api_username.$this->_api_key;
		$hash = hash_hmac('sha256', $message, $this->_api_secret);
		$signature = strtoupper($hash);

		return $signature;
	}

	/**
	 * Call CEX API
	 * @param string $method (POST / GET)
	 * @param string $path
	 * @param array $data
	 * @param bool $private
	 * @return array $response_array
	 */
	private function _callAPI($method, $path, $data, $private=false){
		$ch = $this->_initCurl($this->_api_url.$path);

		if ($method != 'GET'){
			if ($private){
				$this->_api_nonce = $this->_api_nonce+1;

				$authentication = array(
					'key'=>$this->_api_key,
					'signature'=>$this->_signature(),
					'nonce'=>$this->_api_nonce,
				);

				if ($data){
					$query_data = array_merge($authentication, $data);
				} else {
					$query_data = $authentication;
				}
			}

			$post_query = http_build_query($query_data);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_query);

			if ($method == 'POST') {
				curl_setopt($ch, CURLOPT_POST, true);
			} else if ($method == 'PUT') {
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
			}
		}

		$response_data = curl_exec($ch);
		if (curl_errno($ch)) {
			$response_array = array(
				'error'=>'cURL error: '.curl_error($ch)
			);
		} else {
			$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($response_code == 200){
				$response_array = json_decode($response_data, true);
			} else {
				$response_array = array(
					'error'=>'HTTP response:'.$response_code.' '.$response_data
				);
			}
		}

		return $response_array;
	}

	/**
	 * Check if a symbol pair is in the array of allows symbol pairs
	 * @param string $symbol_pair
	 * @return bool
	 */
	private function _check_symbol_pair($symbol_pair){
		if (in_array($symbol_pair, $this->_symbols_pair)){
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Public functions, can be called without API credentials
	 */

	/**
	 * symbols
	 * Returns an array of available symbol pairs
	 * @return array $symbols_pair
	 */
	public function symbols(){
		return $this->_symbols_pair;
	}

	/**
	 * Ticker
	 * Get the symbols ticker
	 * @param string $symbol_pair
	 * @return array $response_array
	 *
	 *  Returns JSON dictionary:
	 *		last - last BTC price
	 *		high - last 24 hours price high
	 *		low - last 24 hours price low
	 *		volume - last 24 hours volume
	 *		bid - highest buy order
	 *		ask - lowest sell order
	 */
	public function ticker($symbol_pair){
		if ($this->_check_symbol_pair($symbol_pair)){
			$response_array = $this->_callAPI('GET', '/ticker/'.$symbol_pair, false, false);
		} else {
			$response_array = array('error'=>'please use a valid symbol pair');
		}

		return $response_array;
	}

	/**
	 * Last Price
	 * Last Price for each trading pair will be defined as price of the last executed order for this pair.
	 * @param string $symbol_pair
	 * @return array $response_array
	 */
	public function last_price($symbol_pair){
		if ($this->_check_symbol_pair($symbol_pair)){
			$response_array = $this->_callAPI('GET', '/last_price/'.$symbol_pair, false, false);
		} else {
			$response_array = array('error'=>'please use a valid symbol pair');
		}

		return $response_array;
	}

	/**
	 * Converter
	 * Converts any amount of the currency to any other currency by multiplying the amount by the last price of the chosen pair according to the current exchange rate.
	 * @param string $symbol_pair
	 * @return array $response_array
	 */
	public function convert($symbol_pair, $value){

		$data = array(
			'amnt'=>floatval($value)
		);

		if ($this->_check_symbol_pair($symbol_pair)){
			$response_array = $this->_callAPI('POST', '/convert/'.$symbol_pair, $data, false);
		} else {
			$response_array = array('error'=>'please use a valid symbol pair');
		}

		return $response_array;
	}

	/**
	 * Chart
	 * Allows building price change charts (daily, weekly, monthly) and showing historical point in any point of the chart
	 * @param int $lastHours
	 * @param int $maxRespArrSize
	 * @return array $response_array
	 */
	public function price_stats($symbol_pair, $lastHours, $maxRespArrSize){

		$data = array(
			'lastHours'=>intval($lastHours),
			'maxRespArrSize'=>intval($maxRespArrSize),
		);

		if ($this->_check_symbol_pair($symbol_pair)){
			$response_array = $this->_callAPI('POST', '/price_stats/'.$symbol_pair, $data, false);
		} else {
			$response_array = array('error'=>'please use a valid symbol pair');
		}

		return $response_array;
	}

	/**
	 * Order Book
	 * Returns JSON dictionary with "bids" and "asks". Each is a list of open orders and each order is represented as a list of price and amount.
	 * @param int $depth - limit the number of bid/ask records returned (optional)
	 * @return array $response_array
	 */
	public function order_book($symbol_pair, $depth=false){

		// TODO: $depth doesn't seem to work
		if ($depth){
			$data = array(
				'depth'=>intval($depth),
			);
		} else {
			$data = false;
		}

		if ($this->_check_symbol_pair($symbol_pair)){
			$response_array = $this->_callAPI('POST', '/order_book/'.$symbol_pair, $data, false);
		} else {
			$response_array = array('error'=>'please use a valid symbol pair');
		}

		return $response_array;
	}

	/**
	 * Trade history
	 * @param int $since - return trades with tid >= since
	 * @return array $response_array
	 *
	 * Returns a list of recent trades, where each trade is a JSON dictionary:
	 *		tid - trade id
	 *		amount - trade amount
	 *		date - UNIX timestamp
	 */
	public function trade_history($symbol_pair, $since){

		$data = array(
			'since'=>intval($since),
		);

		if ($this->_check_symbol_pair($symbol_pair)){
			$response_array = $this->_callAPI('POST', '/trade_history/'.$symbol_pair, $data, false);
		} else {
			$response_array = array('error'=>'please use a valid symbol pair');
		}

		return $response_array;
	}

	/**
	 * Private functions, needs valid API credentials
	 */

	/**
	 * Balance
	 * @return array $response_array
	 *
	 * Returns JSON dictionary:
	 *		available - available balance
	 *		orders - balance in pending orders
	 *		bonus - referral program bonus
	 */
	public function balance(){
		$response_array = $this->_callAPI('POST', '/balance/', false, true);

		return $response_array;
	}

	/**
	 * Open orders
	 * @return array $response_array
	 *
	 * Returns JSON list of open orders. Each order is represented as dictionary:
	 *		id - order id
	 *		time - timestamp
	 *		price - price
	 *		amount - amount
	 *		pending - pending amount (if partially executed)
	 */
	public function open_orders($symbol_pair){
		if ($this->_check_symbol_pair($symbol_pair)){
			$response_array = $this->_callAPI('POST', '/open_orders/'.$symbol_pair, false, true);
		} else {
			$response_array = array('error'=>'please use a valid symbol pair');
		}
		return $response_array;
	}

	/**
	 * Cancel order
	 * @param int $id - order id
	 * @return bool
	 *
	 */
	public function cancel_order($id){
		$data = array(
			'id'=>$id,
		);

		$response_array = $this->_callAPI('POST', '/cancel_order/', $data, true);

		// TODO: clean this up, reponse is either boolean or array, assume error when array.
		if (is_array($response_array)){
			if (isset($response_array['error'])){
				$response = false;
			}
		} else {
			if ($response_array === true){
				$response = true;
			}
		}

		return $response;
	}

	/**
	 * Place order
	 * @param string type - 'buy' or 'sell'
	 * @param float amount
	 * @param float price
	 * @return array $response_array
	 *
	 * Returns JSON dictionary representing order:
	 *		id - order id
	 *		time - timestamp
	 *		type - buy or sell
	 *		price - price
	 *		amount - amount
	 *		pending - pending amount (if partially executed)
	 */
	public function place_order($symbol_pair,$type,$amount,$price){

		$type_array = array(
			'buy',
			'sell'
		);

		if (in_array($type, $type_array)){
			$data = array(
				'type'=>$type,
				'amount'=>floatval($amount),
				'price'=>floatval($price),
			);

			if ($this->_check_symbol_pair($symbol_pair)){
				$response_array = $this->_callAPI('POST', '/place_order/'.$symbol_pair, $data, true);
			} else {
				$response_array = array('error'=>'please use a valid symbol pair');
			}
		} else {
				$response_array = array('error'=>'unknown type: '.$type);
		}

		return $response_array;
	}

	/**
	 * Hash Rate
	 * Returns overall hash rate in MH/s.
	 * @return array $response_array
	 */
	public function hashrate(){
		$response_array = $this->_callAPI('POST', '/ghash.io/hashrate', false, true);

		return $response_array;
	}

	/**
	 * Workers Hash Rate
	 * Returns workers' hash rate and rejected shares.
	 * @return array $response_array
	 */
	public function workers(){
		$response_array = $this->_callAPI('POST', '/ghash.io/workers', false, true);

		return $response_array;
	}
}