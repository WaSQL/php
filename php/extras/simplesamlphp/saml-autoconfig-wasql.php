<?php
// ATTRIBUTION: https://github.com/jpf/okta-simplesamlphp-example/blob/master/saml-autoconfig.php with modifications, distributed under the same license

/* -*- coding: utf-8 -*-
 * Copyright 2015 Okta, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/*
 * metadata_url_for contains PER APPLICATION configuration settings.
 * Each SAML service that you support will have different values here.
 *
 * NOTE:
 *   This is implemented as an array for DEMONSTRATION PURPOSES ONLY.
 *   On a production system, this information should be stored as approprate
 *   With each key below mapping to your concept of "customer company",
 *   "group", "organization", "team", etc.
 *   This should also be stored in your production datastore.
 */
$metadata_url_for = array(
    /* WARNING WARNING WARNING
     *   You MUST remove the testing IdP (idp.oktadev.com) from a production system,
     *   as the testing IdP will allow ANYBODY to log in as ANY USER!
     * WARNING WARNING WARNING
     * For testing with http://saml.oktadev.com use the line below:
     */
     // 'test' => 'http://idp.oktadev.com/metadata',
);

// For WaSQL implementation, populate the $metadata_url_for service provider array from the WaSQL user authentication configuration
global $CONFIG;
if(!isset($CONFIG['okta_simplesamlphp_service_provider_id'])) {
	$error_msg="FATAL ERROR: SimpleSAMLphp configuration missing service provider ID. Please add the service provider ID used in the Okta application SAML URL settings to the WaSQL configuration user authentication settings for Okta SAML.";
	$ok=commonLogMessage('user',$error_msg);
	echo $error_msg;
	die;
}
if(!isset($CONFIG['okta_metadata_url'])) {
	$error_msg="FATAL ERROR: SimpleSAMLphp configuration missing Okta SAML metadata URL. Please add the metadata URL from the Okta application to the WaSQL configuration user authentication settings for Okta SAML.";
	$ok=commonLogMessage('user',$error_msg);
	echo $error_msg;
	die;
}
$metadata_url_for = array(
	$CONFIG['okta_simplesamlphp_service_provider_id'] => $CONFIG['okta_metadata_url'],
);

foreach($metadata_url_for as $idp_name => $metadata_url) {
  /*
   * Fetch SAML metadata from the URL.
   * NOTE:
   *  SAML metadata changes very rarely. On a production system,
   *  this data should be cached as approprate for your production system.
   */
  $metadata_xml = file_get_contents($metadata_url);

  /*
   * Parse the SAML metadata using SimpleSAMLphp's parser.
   * See also: modules/metaedit/www/edit.php:34
   */
  SimpleSAML_Utilities::validateXMLDocument($metadata_xml, 'saml-meta');
  $entities = SimpleSAML_Metadata_SAMLParser::parseDescriptorsString($metadata_xml);
  $entity = array_pop($entities);
  $idp = $entity->getMetadata20IdP();
  $entity_id = $idp['entityid'];

  /*
   * Remove HTTP-POST endpoints from metadata,
   * since we only want to make HTTP-GET AuthN requests.
   */
  for($x = 0; $x < sizeof($idp['SingleSignOnService']); $x++) {
    $endpoint = $idp['SingleSignOnService'][$x];
    if($endpoint['Binding'] == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST') {
      unset($idp['SingleSignOnService'][$x]);
    }
  }

  /*
   * Don't sign AuthN requests.
   */
  if(isset($idp['sign.authnrequest'])) {
    unset($idp['sign.authnrequest']);
  }

  /*
   * Set up the "$config" and "$metadata" variables as used by SimpleSAMLphp.
   */
  $config[$idp_name] = array(
    'saml:SP',
    'entityID' => null,
    'idp' => $entity_id,
    // NOTE: This is how you configure RelayState on the server side.
    // 'RelayState' => "",
  );

  $metadata[$entity_id] = $idp;
}

