<?php
/**
 * Copyright 2008-2008 Amazon.com, Inc., or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the “License”).
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 *    http://aws.amazon.com/apache2.0/
 *
 * or in the “license” file accompanying this file.
 * This file is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND,
 * either express or implied. See the License for the specific language governing permissions and limitations under the License.
 */
require_once("../lib/amazon/AmazonMerchantAtSoapClient.php");


// seller credentials - enter your own here
$login="[UPDATE THE CODE TO INSERT YOUR LOGIN HERE]";
$password="[YOUR PASSWORD HERE]";
$merchantid="[YOUR MERCHANT_TOKEN HERE]";
$merchantname="[YOUR MERCHANT NAME HERE]";


echo "<b>==============================================================================</b><br/>\n";
echo "<b>==============================================================================</b><br/>\n";
echo "<b>Credentials</br></b>";
echo "<b>------------------------------------------------------------------------------</b><br/>\n";
echo "<br/>\n";
echo "Login: " . $login . "</br>\n";
echo "Password: " . $password . "</br>\n";
echo "Merchant ID: " . $merchantid . "</br>\n";
echo "Merchant Name: " . $merchantname . "</br>\n";

// Create a new instance of the API
$proxy = new AmazonMerchantAtSoapClient($login, $password, $merchantid, $merchantname);


/////////////////////////////////////////////////////////
// Get all order reports
/////////////////////////////////////////////////////////
$result = $proxy->getAllPendingDocumentInfo("_GET_ORDERS_DATA_");

// Get the first document id - if merchant@ result only has one order report
// it is not returned as an array, so it is converted here
$documentIDs = (!is_array($result)) ? array($result) : $result;
$documentID = $documentIDs[0]->documentID;


echo "<b>==============================================================================</b><br/>\n";
echo "<b>==============================================================================</b><br/>\n";
echo "<b>getAllPendingDocumentInfo request/response<br/></b>";
echo "<b>------------------------------------------------------------------------------</b><br/>\n";
echo "<pre>" . htmlspecialchars($proxy->client->getWire(), ENT_QUOTES) . "</pre>\n";
echo "Document ID: <pre>" . $documentID . "</pre>\n";


/////////////////////////////////////////////////////////
// Get specific order document
/////////////////////////////////////////////////////////
$result = $proxy->getDocument($documentID);

echo "<b>==============================================================================</b><br/>\n";
echo "<b>==============================================================================</b><br/>\n";
echo "<b>getDocument request/response<br/></b>\n";
echo "<b>------------------------------------------------------------------------------</b><br/>\n";
//Can view the wire contents here:
echo "<pre>" . htmlspecialchars($proxy->client->getWire(), ENT_QUOTES) . "</pre>\n";
//echo "Result: <pre>" . print_r($result, 1) . "</pre>\n";


/////////////////////////////////////////////////////////
// Post shipment confirm document
/////////////////////////////////////////////////////////
$messageType = '_POST_ORDER_FULFILLMENT_DATA_';

// NOTE: This date must be in the past
$fulfillmentDate = '2008-09-30T15:36:33';
$document = '
   <AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">
   <Header>
      <DocumentVersion>1.01</DocumentVersion>
      <MerchantIdentifier>' . $merchantid . '</MerchantIdentifier>
   </Header>
   <MessageType>OrderFulfillment</MessageType>
   <Message>
      <MessageID>1</MessageID>
      <OrderFulfillment>
         <AmazonOrderID>104-9123434-1509011</AmazonOrderID>
         <FulfillmentDate>' . $fulfillmentDate . '</FulfillmentDate>
         <FulfillmentData>
            <CarrierCode>UPS</CarrierCode>
            <ShippingMethod>Ground</ShippingMethod>
            <ShipperTrackingNumber>1Z78F01V0300349838</ShipperTrackingNumber>
         </FulfillmentData>
         <Item>
            <AmazonOrderItemCode>26944749629754</AmazonOrderItemCode>
         </Item>
      </OrderFulfillment>
   </Message>
</AmazonEnvelope>';

/////////////////////////////////////////////////////////
// Post your document here
/////////////////////////////////////////////////////////
// Uncomment here to actually post the ship confirmation
//$result = $proxy->postDocument($messageType, $document);


echo "<b>==============================================================================</b><br/>\n";
echo "<b>==============================================================================</b><br/>\n";
echo "<b>postDocument - ship fulfillment request/response<br/></b>\n";
echo "<b>------------------------------------------------------------------------------</b><br/>\n";
//Can view the wire contents here:
//echo "<pre>" . htmlspecialchars($proxy->client->getWire(), ENT_QUOTES) . "</pre>\n";
//echo "Result: <pre>" . print_r($result, 1) . "</pre>\n";


/////////////////////////////////////////////////////////
// Post refund order document
/////////////////////////////////////////////////////////
$messageType = '_POST_PAYMENT_ADJUSTMENT_DATA_';

$document = '
   <AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">
   <Header>
      <DocumentVersion>1.01</DocumentVersion>
      <MerchantIdentifier>' . $merchantid . '</MerchantIdentifier>
   </Header>
   <MessageType>OrderAdjustment</MessageType>
   <Message>
      <MessageID>1</MessageID>
      <OrderAdjustment>
         <AmazonOrderID>104-9123434-1509011</AmazonOrderID>
         <AdjustedItem>
            <AmazonOrderItemCode>26944749629754</AmazonOrderItemCode>
            <AdjustmentReason>CustomerReturn</AdjustmentReason>
            <ItemPriceAdjustments>
               <Component>
                  <Type>Principal</Type>
                  <Amount currency="USD">123.00</Amount>
               </Component>
               <Component>
                  <Type>Shipping</Type>
                  <Amount currency="USD">4.49</Amount>
               </Component>   
            </ItemPriceAdjustments>
         </AdjustedItem>
      </OrderAdjustment>
   </Message>
</AmazonEnvelope>';

/////////////////////////////////////////////////////////
// Post your document here
/////////////////////////////////////////////////////////
// Uncomment here to actually post the adjustment
//$result = $proxy->postDocument($messageType, $document);


echo "<b>==============================================================================</b><br/>\n";
echo "<b>==============================================================================</b><br/>\n";
echo "<b>postDocument - refund order request/response<br/></b>\n";
echo "<b>------------------------------------------------------------------------------</b><br/>\n";
//Can view the wire contents here:
//echo "<pre>" . htmlspecialchars($proxy->client->getWire(), ENT_QUOTES) . "</pre>\n";
//echo "Result: <pre>" . print_r($result, 1) . "</pre>\n";


/////////////////////////////////////////////////////////
// Acknowledge that the document has been processed
/////////////////////////////////////////////////////////
// Acknowledge your document here
//$documentIDs = array('string' => $documentID);
//$result = $proxy->postDocumentDownloadAck($documentIDs);
                                                                                                                                                             
echo "<b>==============================================================================</b><br/>\n";
echo "<b>==============================================================================</b><br/>\n";
echo "<b>postDocumentDownloadAck request/response<br/></b>\n";
echo "<b>------------------------------------------------------------------------------</b><br/>\n";
//Can view the wire contents here:
//echo "<pre>" . htmlspecialchars($proxy->client->getWire(), ENT_QUOTES) . "</pre>\n";
//echo "Result: <pre>" . print_r($result, 1) . "</pre>\n";

?>
