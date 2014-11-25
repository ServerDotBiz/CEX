CEX.io PHP API
===

This is a PHP class that connects to the cex.io API

##Usage:
1. Download the API source
2. Generate your API key and API secret on https://cex.io/trade/profile
3. include the CEX class
4. create a new CEX object, credentials are not needed for public methods
```php
include_once("cex.class.php");

$api_username	= false;	// your CEX username
$api_key		= false;	// your API key
$api_secret		= false;	// your API secret
$api_url		= 'https://cex.io/api';
$api_cert		= 'cacert.pem';

$CEX = new CEX($api_username, $api_key, $api_secret, $api_url, $api_cert);

$last_price = $CEX->last_price('BTC/USD');
var_dump($last_price);
```

##Public Methods and examples
These calls don't require any API credentials
```php
/**
* symbols
* Returns an array of available symbol pairs
* @return array $symbols_pair
*/
$symbols = $CEX->symbols();
var_dump($symbols);

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
$ticker = $CEX->ticker('BTC/USD');
var_dump($ticker);

/**
* Last Price
* Last Price for each trading pair will be defined as price of the last executed order for this pair.
* @param string $symbol_pair
* @return array $response_array
*/
$last_price = $CEX->last_price('BTC/USD');
var_dump($last_price);

/**
* Converter
* Converts any amount of the currency to any other currency by multiplying the amount by the last price of the chosen pair according to the current exchange rate.
* @param string $symbol_pair
* @return array $response_array
*/
$convert = $CEX->convert('BTC/USD',1.0);
var_dump($convert);

/**
* Chart
* Allows building price change charts (daily, weekly, monthly) and showing historical point in any point of the chart
* @param int $lastHours
* @param int $maxRespArrSize
* @return array $response_array
*/
$price_stats = $CEX->price_stats('BTC/USD', 24, 10);
var_dump($price_stats);

/**
* Order Book
* Returns JSON dictionary with "bids" and "asks". Each is a list of open orders and each order is represented as a list of price and amount.
* @param int $depth - limit the number of bid/ask records returned (optional)
* @return array $response_array
*/
$order_book = $CEX->order_book('BTC/USD', false);
var_dump($order_book);

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
$trade_history = $CEX->trade_history('BTC/USD', 135039);
var_dump($trade_history);
```

##Private Methods and examples
These calls require valid API credentials
```php
/**
* Balance
* @return array $response_array
*
* Returns JSON dictionary:
*		available - available balance
*		orders - balance in pending orders
*		bonus - referral program bonus
*/
$balance = $CEX->balance();
var_dump($balance);

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
$place_order = $CEX->place_order('GHS/BTC','buy',1,0.0001);
var_dump($place_order);

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
$open_orders = $CEX->open_orders('GHS/BTC');
var_dump($open_orders);

/**
* Cancel order
* @param int $id - order id
* @return bool
*
*/
$id = $place_order['id'];
$cancel_order = $CEX->cancel_order($id);
var_dump($cancel_order);
if ($cancel_order){
echo 'Order was cancelled'."\n";
} else {
echo 'Error cancelling order'."\n";
}

$open_orders = $CEX->open_orders('GHS/BTC');
var_dump($open_orders);

/**
* Hash Rate
* Returns overall hash rate in MH/s.
* @return array $response_array
*/
$hashrate = $CEX->hashrate();
var_dump($hashrate);

/**
* Workers Hash Rate
* Returns workers' hash rate and rejected shares.
* @return array $response_array
*/
$workers = $CEX->workers();
var_dump($workers);
```

##CEX documentation
* Cex.io online API documentation: https://cex.io/api