<?php

// Copyright 2009, FedEx Corporation. All rights reserved.
// Version 7.0.0

require_once('../library/fedex-common.php5');

$newline = "<br />";
//The WSDL is not included with the sample code.
//Please include and reference in $path_to_wsdl variable.
$path_to_wsdl = "../wsdl/ShipService_v7.wsdl";

ini_set("soap.wsdl_cache_enabled", "0");

$client = new SoapClient($path_to_wsdl, array('trace' => 1)); // Refer to http://us3.php.net/manual/en/ref.soap.php for more information

$request['WebAuthenticationDetail'] = array('UserCredential' =>
                                                      array('Key' => 'XXX', 'Password' => 'YYY')); // Replace 'XXX' and 'YYY' with FedEx provided credentials 
$request['ClientDetail'] = array('AccountNumber' => 'XXX', 'MeterNumber' => 'XXX');// Replace 'XXX' with your account and meter number
$request['TransactionDetail'] = array('CustomerTransactionId' => '*** EmailLabel Request v7 using PHP ***');
$request['Version'] = array('ServiceId' => 'ship', 'Major' => '7', 'Intermediate' => '0', 'Minor' => '0');
$request['RequestedShipment']['DropoffType'] = 'REGULAR_PICKUP'; // valid values REGULAR_PICKUP, REQUEST_COURIER, ...
$request['RequestedShipment']['ShipTimestamp'] = date('c');
$request['RequestedShipment']['ServiceType'] = 'PRIORITY_OVERNIGHT'; // valid values STANDARD_OVERNIGHT, PRIORITY_OVERNIGHT, FEDEX_GROUND, ...
$request['RequestedShipment']['PackagingType'] = 'YOUR_PACKAGING'; // valid values FEDEX_BOX, FEDEX_PAK, FEDEX_TUBE, YOUR_PACKAGING, ...
$request['RequestedShipment']['Shipper'] = array('Contact' => array('PersonName' => 'Sender Name',
                                                                      'CompanyName' => 'Sender Company Name',
                                                                      'PhoneNumber' => '1234567890'),
                                                   'Address' => array('StreetLines' => array('Address Line 1'),
                                                                      'City' => 'Collierville',
                                                                      'StateOrProvinceCode' => 'TN',
                                                                      'PostalCode' => '38017',
                                                                      'CountryCode' => 'US',
                                                                      'Residential' => 1));
$request['RequestedShipment']['Recipient'] = array('Contact' => array('PersonName' => 'Recipient Name',
                                                                           'CompanyName' => 'Recipient Company Name',
                                                                           'PhoneNumber' => '1234567890'),
                                                        'Address' => array('StreetLines' => array('Address Line 1'),
                                                                           'City' => 'Herndon',
                                                                           'StateOrProvinceCode' => 'VA',
                                                                           'PostalCode' => '20171',
                                                                           'CountryCode' => 'US',
                                                                           'Residential' => 1));
$request['RequestedShipment']['ShippingChargesPayment'] = array('PaymentType' => 'SENDER',
                                                        'Payor' => array('AccountNumber' => 'XXX', // Replace 'XXX' with payor's account number
                                                                     'CountryCode' => 'US'));
$request['RequestedShipment']['SpecialServicesRequested'] = array('SpecialServiceTypes' => array ('RETURN_SHIPMENT', 'PENDING_SHIPMENT'),
                                                                  'EMailNotificationDetail' => array('PersonalMessage' => 'PersonalMessage',
                                                                                                      'Recipients' => array('EMailNotificationRecipientType' => 'RECIPIENT',
                                                                                                      'EMailAddress' => 'recipient@company.com',
                                                                                                      'Format' => 'HTML',
                                                                                                      'Localization' => array('LanguageCode' => 'EN', 'LocaleCode' => 'US'))),
                                                                  'ReturnShipmentDetail' => array('ReturnType' => 'PENDING',
                                                                                                  'ReturnEMailDetail' => array('MerchantPhoneNumber' => '901 999 9999', 
                                                                                                                               'AllowedSpecialServices' => 'SATURDAY_DELIVERY')),
                                                                  'PendingShipmentDetail' => array('Type' => 'EMAIL', 'ExpirationDate' => date('Y-m-d'),
                                                                                           'EmailLabelDetail' => array('NotificationEMailAddress' => 'notification@company.com',
                                                                                                                        'NotificationMessage' => '')));
                                                                                                                                 
$request['RequestedShipment']['LabelSpecification'] = array('LabelFormatType' => 'COMMON2D',
                                                            'ImageType' => 'PNG');
$request['RequestedShipment']['RateRequestTypes'] = 'ACCOUNT'; 
$request['RequestedShipment']['RateRequestTypes'] = 'LIST'; 
$request['RequestedShipment']['PackageCount'] = '1';
$request['RequestedShipment']['PackageDetail'] = 'INDIVIDUAL_PACKAGES';
$request['RequestedShipment']['RequestedPackageLineItems'] = array('0' => array('SequenceNumber' => '1',
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
    $response = $client ->createPendingShipment($request);

    if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR')
    {
        echo 'Url: '.$response -> CompletedShipmentDetail -> AccessDetail -> EmailLabelUrl.$newline;
        echo 'User Id: '.$response -> CompletedShipmentDetail -> AccessDetail -> UserId.$newline;
        echo 'Password: '.$response -> CompletedShipmentDetail -> AccessDetail -> Password.$newline;
        echo 'Tracking Number: '.$response -> CompletedShipmentDetail -> CompletedPackageDetails -> TrackingIds -> TrackingNumber.$newline;
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