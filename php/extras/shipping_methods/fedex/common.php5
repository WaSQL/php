<?php

// Copyright 2008, FedEx Corporation. All rights reserved.

define('TRANSACTIONS_LOG_FILE', '../fedextransactions.log');  // Transactions log file

/**
 *  Print SOAP request and response
 */
function printRequestResponse($client) {
  echo '<h2>Transaction processed successfully.</h2>'. "\n"; 
  echo '<h2>Request</h2>' . "\n";
  echo '<pre>' . htmlspecialchars($client->__getLastRequest()). '</pre>';  
  echo "\n";
   
  echo '<h2>Response</h2>'. "\n";
  echo '<pre>' . htmlspecialchars($client->__getLastResponse()). '</pre>';
  echo "\n";
}

/**
 *  Print SOAP Fault
 */  
function printFault($exception, $client) {
    echo '<h2>Fault</h2>' . "\n";                        
    echo "<b>Code:</b>{$exception->faultcode}<br>\n";
    echo "<b>String:</b>{$exception->faultstring}<br>\n";
    writeToLog($client);
}

/**
 * SOAP request/response logging to a file
 */                                  
function writeToLog($client){  
if (!$logfile = fopen(TRANSACTIONS_LOG_FILE, "a"))
{
   error_func("Cannot open " . TRANSACTIONS_LOG_FILE . " file.\n", 0);
   //exit(1);
}

fwrite($logfile, sprintf("\r%s:- %s",date("D M j G:i:s T Y"), $client->__getLastRequest(). "\n\n" . $client->__getLastResponse()));
}

?>