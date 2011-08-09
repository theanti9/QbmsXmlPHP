<?php

/*
 * Copyright (c) 2011, CrunchyWeb, LLC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, 
 * are permitted provided that the following conditions are met:
 *
 * Redistributions of source code must retain the above copyright notice, this list 
 * of conditions and the following disclaimer.
 *
 * Redistributions in binary form must reproduce the above copyright notice, this 
 * list of conditions and the following disclaimer in the documentation and/or other
 * materials provided with the distribution.
 *
 * Neither the name of CrunchyWeb, LLC nor the names of its contributors may be used
 * to endorse or promote products derived from this software without specific prior
 * written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND 
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED 
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, 
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
 * POSSIBILITY OF SUCH DAMAGE.
 *
 */

// Define QbmsApp class - Holds information relative to the application in use
class QbmsApp {

	private $_appLoginUrl;
	private $_connTicket;
	private $_appId;
	private $_version;

	/* === Constructor === 
	 * args:
	 * 		$app_login_url      	Login url used to register the application with Intuit
	 *		$connection_ticket  	The connection ticket give by Intuit for your merchant account
	 * 		$app_id 				ID of the application being used
	 * 		$version (optional)		Version number for the application (set to 1.0 if null)
	 */
	public function __construct($app_login_url, $connection_ticket, $app_id, $version = null ) {
		$this->_appLoginUrl = $app_login_url;
		$this->_connTicket = $connection_ticket;
		$this->_appId = $app_id;
		$this->_version = ($version!==null) ? $version : "1.0";
	}

	// Uses the information provided in the constructor to generate the XML
	// for the Signon Request sent with each QBMSXML request.
	public function getSignonRequestXML() {
		$doc = new DOMDocument();

		$signonMsgRq = $doc->createElement("SignonMsgsRq");
		$doc->appendChild($signonMsgRq);

		$signonDesktopRq = $doc->createElement("SignonDesktopRq");

		$clientDateTime = $doc->createElement("ClientDateTime");
		$clientDateTime->appendChild($doc->createTextNode(date('Y-n-d\TG:i:s')));
		$signonDesktopRq->appendChild($clientDateTime);

		$applicationLogin = $doc->createElement("ApplicationLogin");
		$applicationLogin->appendChild($doc->createTextNode($this->_appLoginUrl));
		$signonDesktopRq->appendChild($applicationLogin);

		$connectionTicket = $doc->createElement("ConnectionTicket");
		$connectionTicket->appendChild($doc->createTextNode($this->_connTicket));
		$signonDesktopRq->appendChild($connectionTicket);

		$language = $doc->createElement("Language");
		$language->appendChild($doc->createTextNode("English"));
		$signonDesktopRq->appendChild($language);

		$appID = $doc->createElement("AppID");
		$appID->appendChild($doc->createTextNode($this->_appId));
		$signonDesktopRq->appendChild($appID);

		$appVer = $doc->createElement("AppVer");
		$appVer->appendChild($doc->createTextNode($this->_version));
		$signonDesktopRq->appendChild($appVer);

		$signonMsgRq->appendChild($signonDesktopRq);

		return $signonMsgRq;
	}

	/* === Getters/Setters === */
	public function getLoginUrl() {
		return $this->_appLoginUrl;
	}

	public function setLoginUrl($val) {
		$this->_appLoginUrl = $val;
	}

	public function getConnectionTicket() {
		return $this->_connTicket;
	}

	public function setConnectionTicket($val) {
		$this->_connTicket = $val;
	}

	public function getApplicationId() {
		return $this->_appId;
	}

	public function setApplicationId($val) {
		$this->_appId = $val;
	}

	public function getVersion() {
		return $this->_version;
	}

	public function setVersion($val) {
		$this->_version = (string)$val;
	}
}

// Define QbmsXmlCreditChargeRequest class
// Make Credit Charge Requests for an application
class QbmsXmlCreditChargeRequest {
	
	private $_debug;
	private $_qbmsApp;
	private $_rqId;
	private $_ccNum;
	private $_expMonth;
	private $_expYear;
	private $_isCardPresent;
	private $_amnt;
	private $_name;
	private $_ccAddress;
	private $_ccPostalCode;
	private $_salesTaxAmnt;
	private $_rqResponse;

	private $_gateway;

	/* === Constructor ===
	 * Args:
	 *		QbmsApp $qbms_app		A QbmsApp object for the application making the request
	 *				$rq_id 			RequestID given to the request. Can also be randomly generated
	 *								with QbmsXmlCreditChargeRequest::genRandomRequestId()
	 *				$cc_num 		Credit card number to be charged
	 *				$exp_month		Expiration month on the card
	 *				$exp_year		Expiration year on the card
	 *				$is_present		Whether the card is present or not. Most always false
	 *				$amnt 			Amount to be charged
	 *				$name 			Name on card
	 * 				$address 		Address of card holder
	 *				$postal 		Postal code of card holder
	 * 				$salestax 		Any sales tax to be charged.
	 */
	public function __construct(QbmsApp $qbms_app, 
										$rq_id, 
										$cc_num, 
										$exp_month, 
										$exp_year, 
										$is_present, 
										$amnt, 
										$name, 
										$address, 
										$postal, 
										$salestax) {
		
		$this->_qbmsApp = $qbms_app;
		$this->_rqId = $rq_id;
		$this->_ccNum = $cc_num;
		$this->_expMonth = $exp_month;
		$this->_expYear = $exp_year;
		$this->_isCardPresent = $is_present;
		$this->_amnt = $amnt;
		$this->_name = $name;
		$this->_ccAddress = $address;
		$this->_ccPostalCode = $postal;
		$this->_salesTaxAmnt = number_format((float)$salestax,2);
		$this->_debug = false;
	}
	
	// Sets debug mode causing request xml to be printed in various stages.
	public function setDebug($onoff) {
		$this->_debug = $onoff;
	}


	// ****MUST BE CALLED BEFORE SENDING REQUEST OR AN EXCEPTION WILL BE THROWN****
	// Set the payment gateway to be used
	// This method should be passed one of the QbmsGateway constants
	public function setGateway($gateway) {
		$this->_gateway = $gateway;
	}

	/* Ability to generate a random request id
	 * Args:
	 * 		$length 	Lenght of the id (Default 8)
	 * 		$autoassign Whether or not to automatically assign the ID to
	 					the object. (Default true)
	 */
	public function genRandomRequestId($length = 8, $autoassign = true) {
		$chars = str_split("abcdefghijklmnopqrstuvwxyz1234567890");
		$generator = array_rand($chars, $length);
		$out = array();
		for ($i = 0; $i < $length; $i++) {
			$out[] = $chars[$generator[$i]];
		}
		$id = implode($out) . time();

		if ($autoassign) {
			$this->_rqId = $id;
		}
		
		return $id;
	}
	
	// Generate XML for the Credit Charge Request
	public function getCreditCardChargeRequestXML() {
		$doc = new DOMDocument();
		$doc->formatOutput = true;
		$QBMSXMLMsgsRq = $doc->createElement("QBMSXMLMsgsRq");
		$doc->appendChild($QBMSXMLMsgsRq);
		
		$customerCreditCardChargeRq = $doc->createElement("CustomerCreditCardChargeRq");
		
		$transRequestID = $doc->createElement("TransRequestID");
		$transRequestID->appendChild($doc->createTextNode($this->_rqId));
		$customerCreditCardChargeRq->appendChild($transRequestID);
		
		$creditCardNumber = $doc->createElement("CreditCardNumber");
		$creditCardNumber->appendChild($doc->createTextNode($this->_ccNum));
		$customerCreditCardChargeRq->appendChild($creditCardNumber);
		
		$expirationMonth = $doc->createElement("ExpirationMonth");
		$expirationMonth->appendChild($doc->createTextNode($this->_expMonth));
		$customerCreditCardChargeRq->appendChild($expirationMonth);
		
		$expirationYear = $doc->createElement("ExpirationYear");
		$expirationYear->appendChild($doc->createTextNode($this->_expYear));
		$customerCreditCardChargeRq->appendChild($expirationYear);
		
		$isCardPresent = $doc->createElement("IsCardPresent");
		$isCardPresent->appendChild($doc->createTextNode($this->_isCardPresent));
		$customerCreditCardChargeRq->appendChild($isCardPresent);
		
		$amount = $doc->createElement("Amount");
		$amount->appendChild($doc->createTextNode($this->_amnt));
		$customerCreditCardChargeRq->appendChild($amount);
		
		$nameOnCard = $doc->createElement("NameOnCard");
		$nameOnCard->appendChild($doc->createTextNode($this->_name));
		$customerCreditCardChargeRq->appendChild($nameOnCard);
		
		$creditCardAddress = $doc->createElement("CreditCardAddress");
		$creditCardAddress->appendChild($doc->createTextNode($this->_ccAddress));
		$customerCreditCardChargeRq->appendChild($creditCardAddress);
		
		$creditCardPostalCode = $doc->createElement("CreditCardPostalCode");
		$creditCardPostalCode->appendChild($doc->createTextNode($this->_ccPostalCode));
		$customerCreditCardChargeRq->appendChild($creditCardPostalCode);
		
		$salesTaxAmount = $doc->createElement("SalesTaxAmount");
		$salesTaxAmount->appendChild($doc->createTextNode($this->_salesTaxAmnt));
		$customerCreditCardChargeRq->appendChild($salesTaxAmount);
		
		$QBMSXMLMsgsRq->appendChild($customerCreditCardChargeRq);
		if ($this->_debug) {
			echo "MsgsRq<br /><pre>" . htmlentities($doc->saveXML()) . "</pre><br /><br />";
		}

		return $QBMSXMLMsgsRq;
			
	}

	// Generate the entire request XML, including the SignonRequest XML
	public function getFullRequestXML() {
		$doc = new DOMDocument();
		$doc->formatOutput = true;
		$qbmsxml = $doc->createElement("QBMSXML");
		$qbmsxml->appendChild($doc->importNode($this->_qbmsApp->getSignonRequestXML(), true));
		$qbmsxml->appendChild($doc->importNode($this->getCreditCardChargeRequestXML(), true));
		$doc->appendChild($qbmsxml);
		$builder = $doc->saveXML();
		if ($this->_debug) {
			echo "PreFormat:<br /><pre>" . htmlentities($builder) . "</pre><br /><br />";
		}

		// Strip out the preamble and put the custom one needed
		$builder = substr($builder, strpos($builder, '?>')+2);
		$builder = "<?xml version=\"1.0\"?>\n<?qbmsxml version=\"4.1\"?>" . $builder;

		if ($this->_debug) {
			echo "Requst:<br /><pre>" . htmlentities($builder) . "</pre><br /><br />";
		}
		return $builder;
	}
	
	// Send the request!
	public function sendRequest() {
		if (!$this->_gateway) throw new Exception("Must set gateway!");
		// Set the custom content type header
		$header[] = "Content-type: application/x-qbmsxml";
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_URL, $this->_gateway);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getFullRequestXML());
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		
		$data = curl_exec($ch);
		
		if (curl_errno($ch)) {
			die("Error: " . curl_error($ch));
		} else {
			curl_close($ch);
		}
		$this->_rqResponse = $data;
		if ($this->_debug) {
			echo "Response:<br /><pre>" . htmlentities($data) . "</pre><br /><br />";
		}
		// return the XML
		return $data;
	}

	// Get the request response as an array
	public function getRequestResponse() {
		// Make sure there is actually a response to return
		if (!$this->_rqResponse) {
			throw new Exception("Request not made yet!");
		}
		return $this->xml2array($this->_rqResponse);
	}


	/* === Getters/Setters === */

	public function getRequestId(){
		return $this->_rqId;
	}

	public function setRequestId($val){
		$this->_rqId = $val;
	}

	public function getCreditCardNumber() {
		return $this->_ccNum;
	}

	public function setCreditCardNumber($val) {
		$this->_ccNum = $val;
	}

	public function getExpirationMonth() {
		return $this->_expMonth;
	}

	public function setExpirationMonth($val) {
		$this->_expMonth = $val;
	}

	public function getExpirationYear() {
		return $this->_expYear;
	}

	public function setExpirationYear($val) {
		$this->_expYear = $val;
	}

	public function isCardPresent() {
		return $this->_isCardPresent;
	}

	public function setCardPresent($val) {
		$this->_isCardPresent = $val;
	}

	public function getAmount() {
		return $this->_amnt;
	}

	public function setAmount($val) {
		$this->_amnt = $val;
	}

	public function getCardHolderName() {
		return $this->_name;
	}

	public function setCardHolderName($val) {
		$this->_name = $val;
	}

	public function getCardAddress() {
		return $this->_ccAddress;
	}

	public function setCardAddress($val) {
		$this->_ccAddress = $val;
	}

	public function getCardPostalCode() {
		return $this->_ccPostalCode;
	}

	public function setCardPostalCode($val) {
		$this->_ccPostalCode = $val;
	}

	public function getSalesTax() {
		return $this->_salesTaxAmnt;
	}

	public function setSalesTax($val) {
		$this->_salesTaxAmnt = number_format((float)$val, 2);
	}
	

	/** 
	 * xml2array() will convert the given XML text to an array in the XML structure. 
	 * Link: http://www.bin-co.com/php/scripts/xml2array/ 
	 * Arguments : $contents - The XML text 
	 *                $get_attributes - 1 or 0. If this is 1 the function will get the attributes as well as the tag values - this results in a different array structure in the return value.
	 *                $priority - Can be 'tag' or 'attribute'. This will change the way the resulting array sturcture. For 'tag', the tags are given more importance.
	 * Return: The parsed XML in an array form. Use print_r() to see the resulting array structure. 
	 * Examples: $array =  xml2array(file_get_contents('feed.xml')); 
	 *              $array =  xml2array(file_get_contents('feed.xml', 1, 'attribute')); 
	 */ 
	private function xml2array($contents, $get_attributes=1) {
		if(!$contents) return array();
		if(!function_exists('xml_parser_create')) {
			//print "'xml_parser_create()' function not found!";
			return array();
		}
		//Get the XML parser of PHP - PHP must have this module for the parser to work
		$parser = xml_parser_create();
		xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 );
		xml_parser_set_option( $parser, XML_OPTION_SKIP_WHITE, 1 );
		xml_parse_into_struct( $parser, $contents, $xml_values );
		xml_parser_free( $parser );
		if(!$xml_values) throw new Exception("No values!");
		//Initializations
		$xml_array = array();
		$parents = array();
		$opened_tags = array();
		$arr = array();
		$current = &$xml_array;
		//Go through the tags.
		foreach($xml_values as $data) {
			unset($attributes,$value);//Remove existing values, or there will be trouble
			//This command will extract these variables into the foreach scope
			// tag(string), type(string), level(int), attributes(array).
			extract($data);//We could use the array by itself, but this cooler.
			$result = '';
			if($get_attributes) {//The second argument of the function decides this.
				$result = array();
				if(isset($value)) $result['value'] = $value;
				//Set the attributes too.
				if(isset($attributes)) {
					foreach($attributes as $attr => $val) {
						if($get_attributes == 1) $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
						/** :TODO: should we change the key name to '_attr'? Someone may use the tagname 'attr'. Same goes for 'value' too */
					}
				}
			} elseif(isset($value)) {
				$result = $value;
			}
			//See tag status and do the needed.
			if($type == "open") {//The starting of the tag '<tag>'
				$parent[$level-1] = &$current;
				if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
					$current[$tag] = $result;
					$current = &$current[$tag];
				} else { //There was another element with the same tag name
					if(isset($current[$tag][0])) {
						array_push($current[$tag], $result);
					} else {
						$current[$tag] = array($current[$tag],$result);
					}
					$last = count($current[$tag]) - 1;
					$current = &$current[$tag][$last];
				}
			} elseif($type == "complete") { //Tags that ends in 1 line '<tag />'
			//See if the key is already taken.
				if(!isset($current[$tag])) { //New Key
					$current[$tag] = $result;
				} else { //If taken, put all things inside a list(array)
					if((is_array($current[$tag]) and $get_attributes == 0)//If it is already an array...
					or (isset($current[$tag][0]) and is_array($current[$tag][0]) and $get_attributes == 1)) {
						array_push($current[$tag],$result); // ...push the new element into that array.
					} else { //If it is not an array...
						$current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
					}
				}
			} elseif($type == 'close') { //End of tag '</tag>'
				$current = &$parent[$level-1];
			}
		}
		return($xml_array);
	}
}
class QbmsGateway {
	
	const ProductionGateway = "https://merchantaccount.quickbooks.com/j/AppGateway";
	const BetaGateway = "https://merchantaccount.ptc.quickbooks.com/j/AppGateway";
	
}

?>