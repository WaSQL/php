<?php

/**
 * SAML 2.0 remote IdP metadata for SimpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote
 */

// If WaSQL is serving this page, use WaSQL's SimpleSAMLphp autoconfig script, else use the standard autoconfig script
require_once(dirname(__FILE__).'/../../../../../wasql/php/database.php');
global $CONFIG;
if(isset($CONFIG)) {
	require(dirname(__FILE__).'/../saml-autoconfig-wasql.php');
}
else {
	require(dirname(__FILE__).'/../saml-autoconfig.php');
}