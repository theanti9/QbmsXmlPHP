<?php

// Settings
// Change these to the appropriate values for your application
$debug = true;
$appLoginUrl = "paytest.example.com";
$connectionTicket = "TGT-35-OIJnoi2349Oijf90a8fjaQ";
$appId = "12345678";

// If the form has been submitted
if (isset($_POST['confirm'])) {
	// include the library
	include("QBMSXML.class.php");

	$data = $_POST;
	// Create the app object
	$app = new QbmsApp($appLoginUrl, $connectionTicket, $appId);
	// new request
	$rq = new QbmsXmlCreditChargeRequest($app, 
											0, 
											$data['ccnum'], 
											$data['expmonth'], 
											$data['expyear'], 
											false, 
											$data['amount'], 
											$data['name'], 
											$data['address'],
											$data['postalcode'], 
											0.00);
	// use random ID
	$rq->genRandomRequestId(10, true);
	// set debugging
	$rq->setDebug($debug);
	// set appropriate gateway
	if ($debug) {
		$rq->setGateway(QbmsGateway::BetaGateway);
	} else {
		$rq->setGateway(QbmsGateway::ProductionGateway);
	}
	// send the request
	$rq->sendRequest();

	// get the request response array
	$arr_card_return = $rq->getRequestResponse();

	// request status number
	$statuscode = $arr_card_return['QBMSXML']['QBMSXMLMsgsRs']['CustomerCreditCardChargeRs']['attr']['statusCode'];
	// request status message
	$statusmsg = $arr_card_return['QBMSXML']['QBMSXMLMsgsRs']['CustomerCreditCardChargeRs']['attr']['statusMessage'];
	// transaction id
	$cctranid = $arr_card_return['QBMSXML']['QBMSXMLMsgsRs']['CustomerCreditCardChargeRs']['CreditCardTransID']['value'];
	// authentication code
	$authcode = $arr_card_return['QBMSXML']['QBMSXMLMsgsRs']['CustomerCreditCardChargeRs']['AuthorizationCode']['value'];
	// Client transaction id
	$clienttranid = $arr_card_return['QBMSXML']['QBMSXMLMsgsRs']['CustomerCreditCardChargeRs']['ClientTransID']['value'];

	// show whether the request succeeded or failed
	if ($statuscode == "0") {
		echo "<h1>Success!</h1>";
	} else {
		echo "<h1>Failure!</h1><p>{$statuscode}: {$statusmsg}</p>";
	}

}


?>

<form action="" method="POST">
	<label for="name">Name on card:</label> <input type="text" id="name" name="name" /><br />
	<label for="address">Street address:</label> <input type="text" id="address" name="address" /><br />
	<label for="postalcode">Postal code:</label> <input type="text" id="postalcode" name="postalcode" /><br />
	<label for="amount">Charge amount</label> <input type="text" id="amount" name="amount" /><br />
	<label for="ccnum">Credit card number</label> <input type="text" id="ccnum" name="ccnum" /><br />
	<label for="expmonth">Expiration month</label> <input type="text" id="expmonth" name="expmonth" /><br />
	<label for="expyear">Expiration year</label> <input type="text" id="expyear" name="expyear" /><br />
	<input type="hidden" name="confirm" value="true" />
	<input type="submit" value="Pay" />
</form>