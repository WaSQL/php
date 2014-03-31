<?php
/*------------------------------------------------------------------------------

Project Name: Street Level Address Validation using FedEx Web APIs
Written By: Carl Danley
Date: 11/1/2010
ClassName: address_validator
Description: This class will allow users to determine validity of addresses down
to the street level -- something UPS currently does NOT offer.

Note:
The FedEx Street Level Address Validation Web service does not have test
credentials and only supports Production level credentials.

//----------------------------------------------------------------------------*/
class address_validator{

	//Your FedEx credentials
	var $acct = "your fedex account number";
	var $meter = "your fedex meter number";
	var $key = "your fedex key";
	var $pass = "your fedex password";
	
	//cURL timeout
	var $timeout = 12;
	
	//cURL location - this always stays the same
	var $url = "https://gateway.fedex.com:443/web-services";
	
	/*
		Function: validate_address();
		Parameters: street1, street2 (if no street2, pass as blank string), city,
			state, zip, country
		Purpose: This is the public "getter" function that initiates street
			level validation by first generating the XML request, and then fetching
			a response using both the request and cURL.
	*/
	public function validate_address($street1, $street2, $city, $state, $zip, $country){
		//save a copy of our variables for future usage
		$this->street1 = $street1;
		$this->street2 = $street2;
		$this->city = $city;
		$this->state = $state;
		$this->zip = $zip;
		$this->country = $country;
		
		//build the XML request
		$this->build_request();
		
		//retrieve a response and return it
		return $this->fetch();
	}
	
	/*
		Function: d();
		Parameters: var = variable to dump, exit (default true) = determines
			whether or not the script should exit after immediately displaying
			contents
		Purpose: This functions purpose served for none other than that of debugging.
	*/
	public function d($var, $exit = true){
		echo "<pre>";
		var_dump($var);
		echo "</pre>";
		if($exit){
			exit;
		}
	}
	
	/*
		Function: build_request();
		Parameters: None
		Purpose: This function uses the data passed to validate_address() to
			build the XML request so that it can be sent later.
	*/
	private function build_request(){
		//make a timestamp for the request
		$timestamp = date('c');
		
		//check to see if we need to build another street line for street2
		//if it exists
		if(!empty($this->street2)){
			$street2 = "<ns1:StreetLines>$this->street2</ns1:StreetLines>";
		}
		else{
			$street2 = "";
		}
	
		//now build the request
		$this->request = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://fedex.com/ws/addressvalidation/v2">
<SOAP-ENV:Body>
	<ns1:AddressValidationRequest>
		<ns1:WebAuthenticationDetail>
			<ns1:UserCredential>
				<ns1:Key>$this->key</ns1:Key>
				<ns1:Password>$this->pass</ns1:Password>
			</ns1:UserCredential>
		</ns1:WebAuthenticationDetail>
		<ns1:ClientDetail>
			<ns1:AccountNumber>$this->acct</ns1:AccountNumber>
			<ns1:MeterNumber>$this->meter</ns1:MeterNumber>
		</ns1:ClientDetail>
		<ns1:TransactionDetail>
			<ns1:CustomerTransactionId>***Street Level Address Validation Request***</ns1:CustomerTransactionId>
		</ns1:TransactionDetail>
		<ns1:Version>
			<ns1:ServiceId>aval</ns1:ServiceId>
			<ns1:Major>2</ns1:Major>
			<ns1:Intermediate>0</ns1:Intermediate>
			<ns1:Minor>0</ns1:Minor>
		</ns1:Version>
		<ns1:RequestTimestamp>$timestamp</ns1:RequestTimestamp>
		<ns1:Options>
			<ns1:CheckResidentialStatus>true</ns1:CheckResidentialStatus>
			<ns1:MaximumNumberOfMatches>5</ns1:MaximumNumberOfMatches>
			<ns1:StreetAccuracy>LOOSE</ns1:StreetAccuracy>
			<ns1:DirectionalAccuracy>LOOSE</ns1:DirectionalAccuracy>
			<ns1:CompanyNameAccuracy>LOOSE</ns1:CompanyNameAccuracy>
			<ns1:ConvertToUpperCase>true</ns1:ConvertToUpperCase>
			<ns1:RecognizeAlternateCityNames>true</ns1:RecognizeAlternateCityNames>
			<ns1:ReturnParsedElements>true</ns1:ReturnParsedElements>
		</ns1:Options>
		<ns1:AddressesToValidate>
			<ns1:AddressId>Customer</ns1:AddressId>
			<ns1:Address>
				<ns1:StreetLines>$this->street1</ns1:StreetLines>
				$street2
				<ns1:City>$this->city</ns1:City>
				<ns1:StateOrProvinceCode>$this->state</ns1:StateOrProvinceCode>
				<ns1:PostalCode>$this->zip</ns1:PostalCode>
				<ns1:CountryCode>$this->country</ns1:CountryCode>
			</ns1:Address>
		</ns1:AddressesToValidate>
	</ns1:AddressValidationRequest>
</SOAP-ENV:Body>
</SOAP-ENV:Envelope>
XML;
	}
	
	/*
		Function: fetch();
		Parameters: None
		Purpose: This function sets up a cURL object, specifies the cURL options
			and fetchs the data response from FedEx. The :'s are replaced out of
			the response string so we can convert the string to a simplexml object
			and then to an array for easier use. The colons are restored at the
			end and then the final resultant array is returned
	*/
	private function fetch(){
		//setup the headers to be used for our cURL option HTTPHEADER
		$headers[] = "Content-type: application/xml;charset=\"utf-8\"";
		$headers[] = "Accept: text/xml";
		$headers[] = "Cache-Control: no-cache";
		$headers[] = "Pragma: no-cache";
		$headers[] = "SOAPAction: \"run\"";
		$headers[] = "Content-Length: " . strlen($this->request);
	
		//create our cURL object
		$ch = curl_init($this->url);
		
		//specify all cURL options
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->request);
		
		//fetch the result
		$result = curl_exec($ch);
		
		//close our cURL connection
		curl_close($ch);
		
		//replace colons so we can convert to simplexml
		$result = str_replace(":", "xmlcolon", $result);
		
		//load the string response into a simplexml object
		$xml = simplexml_load_string($result);
		
		//use a trick to convert the simple xml object to an associative array
		$xml = json_decode(json_encode($xml), true);
		
		//restore colons
		$xml = $this->restore_colons($xml);
		
		//return our resultant array
		return $xml;
	}
	
	/*
		Function: restore_colons();
		Parameters: $arr
		Purpose: This function uses the associative array passed as a parameter
			to recursively scan the array, replacing all "xmlcolon" strings back
			to an actual colon.
	*/
	private function restore_colons($arr){
		$new = array();
		foreach($arr as $key => $value){
			if(is_array($value)){
				$new[str_replace("xmlcolon", ":", $key)] = $this->restore_colons($value);
			}
			else{
				$new[str_replace("xmlcolon", ":", $key)] = str_replace("xmlcolon", ":", $value);
			}
		}
		return $new;
	}
}
//------------------------------------------------------------------------------
?>
