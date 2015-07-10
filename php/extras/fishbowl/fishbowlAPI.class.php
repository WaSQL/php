<?php
/**
 * @package : FishbowlPI
 * @author : dnewsom <dave.newsom@fishbowlinventory.com>
 * @author : kbatchelor <kevin.batchelor@fishbowlinventory.com>
 * @version : 1.2
 * @date : 2010-04-29
 *
 * Utility routines for Fishbowls API
 */

class FishbowlAPI {
    public $result;
    public $statusCode;
    public $statusMsg;
    public $loggedIn;
    public $userRights;
    public $app_name;
    public $app_key;
    public $app_description;
    public $packedValue;
    public $packedLen;
    private $xmlRequest;
    private $xmlResponse;
    private $id;
    private $key;

	/**
	 * Create the connection to Fishbowl
	 * @param string $host - Fishbowl host
	 * @param string $port - Fishbowl port
	 */
    public function __construct($host, $port=28192) {
        $this->host = $host;
        $this->port = $port;
        
        $this->id = fsockopen($this->host, $this->port);
    }

    /**
     * Close the connection
     */
    public function closeConnection() {
        fclose($this->id);
    }
    
    /**
     * Set the App info - key, name, description
     */
    public function setAppInfo($key,$name,$description){
    	$this->app_key=$key;
    	$this->app_name=$name;
    	$this->app_description=$description;
	}

    /**
     * Login to Fishbowl
     * @param string $user - Pass in the username on login
     * @param string $pass - Pass in the password on login
     */
    public function login($user = null, $pass = null) {
    	if (!is_null($user)) {
    		$this->user = $user;
    	}
    	if (!is_null($pass)) {
    		$this->pass = base64_encode(md5($pass, true));
    	}
        // Parse XML
        $this->xmlRequest = "<FbiXml>\n".
			                "    <Ticket/>\n" .
             				"    <FbiMsgsRq>\n" .
			                "        <LoginRq>\n" .
             			    "            <IAID>" . $this->app_key . "</IAID>\n" .
			                "            <IAName>" . $this->app_name . "</IAName>\n" .
             			    "            <IADescription>" . $this->app_description . "</IADescription>\n" .
			                "            <UserName>" . $this->user . "</UserName>\n" .
             			    "            <UserPassword>" . $this->pass . "</UserPassword>\n" .
			                "        </LoginRq>\n" .
             			    "    </FbiMsgsRq>\n" .
			                "</FbiXml>";

        // Pack for sending
        $len = strlen($this->xmlRequest);
        $packed = pack("N", $len);
        $this->packedLen=$len;
		$this->packedValue=$packed;
        // Send and get the response
        fwrite($this->id, $packed, 4);
        fwrite($this->id, $this->xmlRequest);
        $this->getResponse();
        
        // Set the result
        $this->setResult($this->parseXML($this->xmlResponse));
        $this->setStatus('LoginRs');

        if ($this->statusCode == 1000) {
	        // Set the key
    	    $this->key = $this->result['Ticket']['Key'];
    	    $this->loggedIn = true;
    	    $this->userRights = $this->result['FbiMsgsRs']['LoginRs']['ModuleAccess']['Module'];
        } else {
        	$this->loggedIn = false;
        }
    }

    /**
     * Get customer information
     * @param string $type - What type of call are you running. Default is NameList
     * @param string $name - If your getting a specific customer you must pass in a name
     */
    public function getCustomer($type = 'NameList', $name = null) {
        // Setup XML
        if ($type == "Get") {
            $xml = "<CustomerGetRq>\n<Name>{$name}</Name>\n</CustomerGetRq>\n";
            $status = 'CustomerGetRs';
        } elseif ($type == "List") {
            $xml = "<CustomerListRq></CustomerListRq>\n";
            $status = 'CustomerListRs';
        } else {
            $xml = "<CustomerNameListRq></CustomerNameListRq>\n";
            $status = 'CustomerNameListRs';
        }
        
        // Create request and pack
		$this->createRequest($xml);
        $len = strlen($this->xmlRequest);
        $packed = pack("N", $len);

        // Send and get the response
        fwrite($this->id, $packed, 4);
        fwrite($this->id, $this->xmlRequest);
        $this->getResponse();

        // Set the result
        $this->setResult($this->parseXML($this->xmlResponse));
        $this->setStatus($status);
    }

    /**
     * Get vendor information
     * @param string $type - What type of call are you running. Default is NameList
     * @param string $name - If your getting a specific vendor you must pass in a name
     */
    function getVendor($type = 'NameList', $name = null) {
        if ($type == "Get") {
            $xml = "<VendorGetRq>\n<Name>{$name}</Name>\n</VendorGetRq>\n";
            $status = "VendorGetRs";
        } elseif ($type == "List") {
            $xml = "<VendorListRq></VendorListRq>\n";
            $status = "VendorListRs";
        } else {
            $xml = "<VendorNameListRq></VendorNameListRq>\n";
            $status = "VendorNameListRs";
        }

        // Create request and pack
		$this->createRequest($xml);
        $len = strlen($this->xmlRequest);
        $packed = pack("N", $len);

        // Send and get the response
        fwrite($this->id, $packed, 4);
        fwrite($this->id, $this->xmlRequest);
        $this->getResponse();

        // Set the result
        $this->setResult($this->parseXML($this->xmlResponse));
        $this->setStatus($status);
    }

    /**
     * Get product information
     * @param string $type
     * @param string $productNum
     * @param integer $getImage
     * @param string $upc
     */
    public function getProducts($type = 'Get', $productNum = 'B201', $getImage = 0, $upc = null) {
        // Setup XML
        if ($type == "Get") {
            $xml = "<ProductGetRq>\n" .
                   "    <Number>{$productNum}</Number>\n" .
                   "    <GetImage>{$getImage}</GetImage>\n" .
                   "</ProductGetRq>\n";
        } elseif ($type == "Query") {
            $xml = "<ProductQueryRq>\n";
                if ($upc != null) {
                    $xml .= "    <UPC>{$upc}</UPC>\n";
                } else {
                    $xml .= "    <ProductNum>{$productNum}</ProductNum>\n";
                }
            $xml .= "    <GetImage>{$getImage}</GetImage>\n" .
                    "</ProductQueryRq>\n";
        }

        // Create request and pack
		$this->createRequest($xml);
        $len = strlen($this->xmlRequest);
        $packed = pack("N", $len);
		
        // Send and get the response
        fwrite($this->id, $packed, 4);
        fwrite($this->id, $this->xmlRequest);
        $this->getResponse();

        // Set the result
        $this->setResult($this->parseXML($this->xmlResponse));
        $this->setStatus('ProductQueryRs');
    }

	/**
	 * Get list of SO's by location group
	 * @param string $LocationGroup
	 */
	public function getSOList($LocationGroup = 'SLC') {
		// Parse XML
		$xml = "<GetSOListRq>\n<LocationGroup>{$LocationGroup}</LocationGroup>\n</GetSOListRq>\n";

        // Create request and pack
		$this->createRequest($xml);
        $len = strlen($this->xmlRequest);
        $packed = pack("N", $len);

        // Send and get the response
        fwrite($this->id, $packed, 4);
        fwrite($this->id, $this->xmlRequest);
        $this->getResponse();

        // Set the result
        $this->setResult($this->parseXML($this->xmlResponse));
        $this->setStatus('GetSOListRs');
    }
	
	/**
	 * Loads SO for a given number
	 * @param string $number
	 */
	public function getSO($number = '50032') {
		// Parse XML
		$xml = "<LoadSORq>\n<Number>{$number}</Number>\n</LoadSORq>\n";
		
        // Create request and pack
		$this->createRequest($xml);
        $len = strlen($this->xmlRequest);
        $packed = pack("N", $len);

        // Send and get the response
        fwrite($this->id, $packed, 4);
        fwrite($this->id, $this->xmlRequest);
        $this->getResponse();

        // Set the result
        $this->setResult($this->parseXML($this->xmlResponse));
        $this->setStatus('LoadSORs');
    }
	
    /**
     * Get part information. Can be search by either PartNum or UPC
     * @param string $partNum - Pass in if you're searching for PartNum or pass in null
     * @param string $upc - Pass in if you're searching for UPC or pass in null
     */
    public function getPart($partNum = null, $upc = null) {
    	// Setup xml
    	$xml = "<PartGetRq>\n";
    	if (!is_null($partNum)) {
    		$xml .= "<Number>{$partNum}</Number>\n";
    	} else {
    		$xml .= "<Number>{$upc}</Number>\n";
    	}
    	$xml .= "</PartGetRq>\n";
		
        // Create request and pack
		$this->createRequest($xml);
        $len = strlen($this->xmlRequest);
        $packed = pack("N", $len);

        // Send and get the response
        fwrite($this->id, $packed, 4);
        fwrite($this->id, $this->xmlRequest);
        $this->getResponse();

        // Set the result
        $this->setResult($this->parseXML($this->xmlResponse));
        $this->setStatus('PartGetRs');
    }
    
    /**
     * Get inventory quantity information for a part
     * $param string $partNum
     */
    public function getInvQty($partNum) {
    	// Setup xml
    	$xml = "<InvQtyRq>\n<PartNum>{$partNum}</PartNum>\n</InvQtyRq>\n";
		
        // Create request and pack
		$this->createRequest($xml);
        $len = strlen($this->xmlRequest);
        $packed = pack("N", $len);

        // Send and get the response
        fwrite($this->id, $packed, 4);
        fwrite($this->id, $this->xmlRequest);
        $this->getResponse();

        // Set the result
        $this->setResult($this->parseXML($this->xmlResponse));
        $this->setStatus('InvQtyRs');
    }

    /**
     * Parse xml data and store the results
     */
	private function parseXML($xml, $recursive = false, $cust = false) {
		if (!$recursive) {
			$array = simplexml_load_string($xml);
		} else {
			$array = $xml;
		}
	
		$newArray = array();
		$array = (array) $array;

		foreach ($array as $key=>$value) {
			$value = (array) $value;
			if (isset($value[0])) {
				if (count($value) > 1) {
					$newArray[$key] = (array) $value;
				} else {
					$newArray[$key] = trim($value[0]);
				}
			} else {
				$newArray[$key] = $this->parseXML($value, true);
			}
		}
        if (!isset($newArray['statusMessage'])) {
            $newArray['statusMessage'] = "null";
        }
	    return $newArray;
	}
	
	/**
	 * Set the XML Request
	 * @param string $xmlData
	 */
	private function createRequest($xmlData) {
		$this->xmlRequest = $this->xmlHeader() . $xmlData . $this->xmlFooter();
	}
	
	/**
	 * Create XML header
	 */
	private function xmlHeader() {
        $xml = "<FbiXml>\n<Ticket>\n<UserID>1</UserID>\n<Key>{$this->key}</Key>\n</Ticket>\n<FbiMsgsRq>\n";
        return $xml;
	}
	
	/**
	 * Create XML foorter
	 */
	private function xmlFooter() {
        $xml = "</FbiMsgsRq>\n</FbiXml>\n";
		return $xml;
	}
	
	/**
	 * Determine the length (in bytes) of our reponse and stream it.
	 */
	private function getResponse() {
		$packed_len = stream_get_contents($this->id, 4); //The first 4 bytes contain our N-packed length
		$hdr = unpack('Nlen', $packed_len);
		$len = $hdr['len'];
		$this->xmlResponse = stream_get_contents($this->id, $len);
	}
	
	/**
	 * Set the results from a response
	 * @param array $res - This should be the parsed response from the server
	 */
	private function setResult($res) {
		$this->result = $res;
	}
	
	/**
	 * Set the status code and message for the responses
	 * @param string $response - This should be the response name to get the code and message from
	 */
	private function setStatus($response) {
		if (isset($this->result[$response])) {
			$this->statusCode = $this->result[$response]['@attributes']['statusCode'];
			$this->statusMsg = $this->getStatusMessage($this->statusCode);
			//$this->statusMsg = $this->result[$response]['@attributes']['statusMessage'];
		} else {
			$this->statusCode = $this->result['FbiMsgsRs']['@attributes']['statusCode'];
			$this->statusMsg = $this->getStatusMessage($this->statusCode);
			//$this->statusMsg = $this->result['FbiMsgsRs']['@attributes']['statusMessage'];
		}

		if ($this->statusCode == 1000) {
			$this->statusMsg = $this->getStatusMessage($this->statusCode);
		}
	}
	
	/**
	 * Generate the request to send to Fishbowl from an object
	 * @param string $name
	 * @param array $array
	 */
    private function generateRequest($array, $name, $subname = null) {
        //star and end the XML document
        $this->xmlRequest = "<{$name}>\n";
        if (!is_null($subname)) {
        	$this->xmlRequest .= "\t<{$subname}>\n";
        }
        $this->generateXML($array);
        if (!is_null($subname)) {
        	$this->xmlRequest .= "\t</{$subname}>\n";
        }
        $this->xmlRequest .= "</{$name}>";
        return $this->xmlRequest;
    }
	
	/**
	 * Generate XML from an array
	 * @param array $array
	 */
    private function generateXML($array) {
        static $Depth = 0;
        $Tabs = "";
        
        // Check if this is the top value
        if (isset($array->data)) {
        	$array = $array->data;
        }

        foreach($array as $key => $value){
			unset($Tabs);
			
			// We want to have arrays, if we find an object we need to convert it
			if (is_object($value)) {
				$value = (array) $value;
			}
			
        	// Check if the node is an array or object
            if (!is_array($value)) {
            	// Add tabs so it's readable
                for ($i=1; $i<=$Depth+1; $i++) {
                	$Tabs .= "\t";
                }
                if (preg_match("/^[0-9]\$/",$key)) {
                	$key = "n{$key}";
                }
                
                // Add to the XML request
                $this->xmlRequest .= "{$Tabs}<{$key}>{$value}</{$key}>\n";
            } else {
            	// Add tabs so it's readable
                $Depth += 1;
                for ($i=1; $i<=$Depth; $i++) {
                	$Tabs .= "\t";
                }

                // Add to the XML request and send it to the next level
				$this->xmlRequest .= "{$Tabs}<{$key}>\n";
				$this->generateXML($value);
				$this->xmlRequest .= "{$Tabs}</{$key}>\n";
				$Depth -= 1;
            }
        }
        return true;
    }
    
    /**
     * Check if the user has rights to functions
     * @param string $module
     * @param string $right
     */
    public function checkAccessRights($module, $right) {
    	// Check if the user is admin
    	if ($this->user == 'admin') {
    		return true;
    	}
    	
    	// Check if the user has an rights
    	if (!is_array($this->userRights)) {
    		return false;
    	}
    	
    	// Create the access right
    	$accessRight = $module . "-" . $right;
    	if (in_array($accessRight, $this->userRights)) {
    		return true;
    	} else {
    		return false;
    	}
    }
    /**
	* getStatusMessage
	*/
	private function getStatusMessage($code) {
        switch($code) {
            case "1000":
                $value = "Success!";
                break;
            case "1001":
                $value = "Unknown Message Received";
                break;
            case "1002":
                $value = "Connection to Fishbowl Server was lost";
                break;
            case "1003":
                $value = "Some Requests had errors -- now isn't that helpful...";
                break;
            case "1004":
                $value = "There was an error with the database.";
                break;
            case "1009":
                $value = "Fishbowl Server has been shut down.";
                break;
            case "1010":
                $value = "You have been logged off the server by an administrator.";
                break;
            case "1012":
                $value = "Unknown request function.";
                break;
            case "1100":
                $value = "Unknown login error occurred.";
                break;
            case "1110":
                $value = "A new Integrated Application has been added to Fishbowl Inventory. Please contact your Fishbowl Inventory Administrator to approve this Integrated Application.";
                break;
            case "1111":
                $value = "This Integrated Application registration key does not match.";
                break;
            case "1112":
                $value = "This Integrated Application has not been approved by the Fishbowl Inventory Administrator.";
                break;
            case "1120":
                $value = "Invalid Username or Password.";
                break;
            case "1130":
                $value = "Invalid Ticket passed to Fishbowl Inventory Server.";
                break;
            case "1131":
                $value = "Invalid Key value.";
                break;
            case "1140":
                $value = "Initialization token is not correct type.";
                break;
            case "1150":
                $value = "Request was invalid";
                break;
            case "1160":
                $value = "Response was invalid.";
                break;
            case "1162":
            	$value = "The login limit has been reached for the server's key.";
            	break;
            case "1200":
                $value = "Custom Field is invalid.";
                break;
            case "1500":
                $value = "The import was not properly formed.";
                break;
            case "1501":
                $value = "That import type is not supported";
                break;
            case "1502":
                $value = "File not found.";
                break;
            case "1503":
                $value = "That export type is not supported.";
                break;
            case "1504":
                $value = "File could not be written to.";
                break;
            case "1505":
                $value = "The import data was of the wrong type.";
                break;
            case "2000":
                $value = "Was not able to find the Part {0}.";
                break;
            case "2001":
                $value = "The part was invalid.";
                break;
            case "2100":
                $value = "Was not able to find the Product {0}.";
                break;
            case "2101":
                $value = "The product was invalid.";
                break;
            case "2200":
                $value = "The yield failed.";
                break;
            case "2201":
                $value = "Commit failed.";
                break;
            case "2202":
                $value = "Add initial inventory failed.";
                break;
            case "2203":
                $value = "Can not adjust committed inventory.";
                break;
            case "2300":
                $value = "Was not able to find the Tag number {0}.";
                break;
            case "2301":
                $value = "The tag is invalid.";
                break;
            case "2302":
                $value = "The tag move failed.";
                break;
            case "2303":
                $value = "Was not able to save Tag number {0}.";
                break;
            case "2304":
                $value = "Not enough available inventory in Tagnumber {0}.";
                break;
            case "2305":
                $value = "Tag number {0} is a location.";
                break;
            case "2400":
                $value = "Invalid UOM.";
                break;
            case "2401":
                $value = "UOM {0} not found.";
                break;
            case "2402":
                $value = "Integer UOM {0} cannot have non-integer quantity.";
                break;
            case "2500":
                $value = "The Tracking is not valid.";
                break;
            case "2510":
                $value = "Serial number is missing.";
                break;
            case "2511":
                $value = "Serial number is null.";
                break;
            case "2512":
                $value = "Serial number is duplicate.";
                break;
            case "2513":
                $value = "Serial number is not valid.";
                break;
            case "2600":
                $value = "Location not found.";
                break;
            case "2601":
                $value = "Invalid location.";
                break;
            case "2602":
                $value = "Location Group {0} not found.";
                break;
            case "3000":
                $value = "Customer {0} not found.";
                break;
            case "3001":
                $value = "Customer is invalid.";
                break;
            case "3100":
                $value = "Vendor {0} not found.";
                break;
            case "3101":
                $value = "Vendor is invalid.";
                break;
            case "4000":
                $value = "There was an error load PO {0}.";
                break;
            case "4001":
                $value = "Unknow status {0}.";
                break;
            case "4002":
                $value = "Unknown carrier {0}.";
                break;
            case "4003":
                $value = "Unknown QuickBooks class {0}.";
                break;
            case "4004":
                $value = "PO does not have a PO number. Please turn on the auto-assign PO number option in the purchase order module options.";
                break;
        }

        return $value;
    }
}

?>