<?php

// Copyright 2008, FedEx Corporation. All rights reserved.
// Version 5.0.0

require_once('../library/fedex-common.php5');

$newline = "<br />";
//The WSDL is not included with the sample code.
//Please include and reference in $path_to_wsdl variable.
$path_to_wsdl = "../wsdl/RateService_v5.wsdl";

ini_set("soap.wsdl_cache_enabled", "0");
 
$client = new SoapClient($path_to_wsdl, array('trace' => 1)); // Refer to http://us3.php.net/manual/en/ref.soap.php for more information

$request['WebAuthenticationDetail'] = array('UserCredential' =>
                                      array('Key' => 'XXX', 'Password' => 'YYY')); // Replace 'XXX' and 'YYY' with FedEx provided credentials 
$request['ClientDetail'] = array('AccountNumber' => 'XXX', 'MeterNumber' => 'XXX');// Replace 'XXX' with your account and meter number
$request['TransactionDetail'] = array('CustomerTransactionId' => ' *** Rate Request v5 using PHP ***');
$request['Version'] = array('ServiceId' => 'crs', 'Major' => '5', 'Intermediate' => '0', Minor => '0');
$request['ReturnTransitAndCommit'] = true;
$request['RequestedShipment']['DropoffType'] = 'REGULAR_PICKUP'; // valid values REGULAR_PICKUP, REQUEST_COURIER, ...
$request['RequestedShipment']['ShipTimestamp'] = date('c');
$request['RequestedShipment']['ServiceType'] = 'PRIORITY_OVERNIGHT'; // valid values STANDARD_OVERNIGHT, PRIORITY_OVERNIGHT, FEDEX_GROUND, ...
$request['RequestedShipment']['PackagingType'] = 'YOUR_PACKAGING'; // valid values FEDEX_BOK, FEDEX_PAK, FEDEX_TUBE, YOUR_PACKAGING, ...
$request['RequestedShipment']['Shipper'] = array('Address' => array(
                                          'StreetLines' => array('10 Fed Ex Pkwy'), // Origin details
                                          'City' => 'Memphis',
                                          'StateOrProvinceCode' => 'TN',
                                          'PostalCode' => '38115',
                                          'CountryCode' => 'US'));
$request['RequestedShipment']['Recipient'] = array('Address' => array (
                                               'StreetLines' => array('13450 Farmcrest Ct'), // Destination details
                                               'City' => 'Herndon',
                                               'StateOrProvinceCode' => 'VA',
                                               'PostalCode' => '20171',
                                               'CountryCode' => 'US'));
$request['RequestedShipment']['ShippingChargesPayment'] = array('PaymentType' => 'SENDER',
                                                        'Payor' => array('AccountNumber' => 'XXX', // Replace "XXX" with payor's account number
                                                                     'CountryCode' => 'US'));
$request['RequestedShipment']['RateRequestTypes'] = 'ACCOUNT'; 
$request['RequestedShipment']['RateRequestTypes'] = 'LIST'; 
$request['RequestedShipment']['PackageCount'] = '1';
$request['RequestedShipment']['PackageDetail'] = 'INDIVIDUAL_PACKAGES';
$request['RequestedShipment']['RequestedPackages'] = array('0' => array('SequenceNumber' => '1',
                                                                  'InsuredValue' => array('Amount' => 20.0,
                                                                                          'Currency' => 'USD'),
                                                                  'ItemDescription' => 'College Transcripts',
                                                                  'Weight' => array('Value' => 2.0,
                                                                                    'Units' => 'LB'),
                                                                  'Dimensions' => array('Length' => 25,
                                                                                        'Width' => 10,
                                                                                        'Height' => 10,
                                                                                        'Units' => 'IN'),
                                                                  'CustomerReferences' => array('CustomerReferenceType' => 'CUSTOMER_REFERENCE',
                                                                                                 'Value' => 'Undergraduate application')));

try 
{
    $response = $client ->getRates($request);
        
    if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR')
    {
        printRequestResponse($client);
    }
    else
    {
        echo 'Error in processing transaction.'. $newline. $newline; 
        foreach ($response -> Notifications as $notification)
        {           
            if(is_array($response -> Notifications))
            {              
               echo $notification -> Severity;
               echo ': ';           
               echo $notification -> Message . $newline;
            }
            else
            {
                echo $notification . $newline;
            }
        } 
    } 
    
    writeToLog($client);    // Write to log file   

} catch (SoapFault $exception) {
   printFault($exception, $client);        
}

?>