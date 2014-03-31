<?php
/*
References and contributors
	Mubashir Ali <saad_ali6@yahoo.com> originally wrote this but I have modified it tons.
	https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_InvoiceAPIExamples
	Look at the comment at the bottom of this file for CURL examples
*/
    class PaypalInvoiceAPI{
        const PAYPAL_REQUEST_DATA_FORMAT = "NV";     //NV/XML/JSON
        const LIVE_INVOICE_URL = "https://svcs.paypal.com/Invoice/";
        const SANDBOX_INVOICE_URL = "https://svcs.sandbox.paypal.com/Invoice/";

        private $mode;
        private $application_id;
        private $return_format;
        private $end_url;
        private $invoice_reminder_url;
		private $api_username;
		private $api_password;
		private $api_signature;
        /**
         *
         * @param array $params - mode=live/sandbox, application_id, return_format=XML/JSON/NV, api_username, api_password, api_signature
         */
        function __construct($params=array()){
			if(!isset($params['mode'])){$this->mode='sandbox';}
			else{$this->mode=strtolower($params['mode']);}
			//required values
			$this->application_id=$params['application_id'];
			$this->return_format=$params['return_format'];
			$this->api_username=$params['api_username'];
			$this->api_password=$params['api_password'];
			$this->api_signature=$params['api_signature'];
			if($this->mode == "live"){
				$this->invoice_reminder_url = "https://www.paypal.com/us/cgi-bin/?cmd=_pay-inv&id=";
            }
            else{
                $this->invoice_reminder_url = "https://www.sandbox.paypal.com/us/cgi-bin/?cmd=_pay-inv&id=";
            }
        }
        //---doCreateInvoice---------------------------------------
		/**
		* paypal createInvoice method
		* @param invoice array
		*	<p>Invoice array structure</p>
		* @return response array
		*/
        function doCreateInvoice($invoice){
            $this->end_url = $this->getEndPoint("createInvoice");
            $strCreateInvoice = $this->prepareCreateInvoice($invoice);
            $response = $this->curlRequest($strCreateInvoice);
            $response = $this->parseCurlResponse($response);
            return $response;
        }
        //---doSendInvoice---------------------------------------
		/**
		* paypal sendInvoice method emails previously created invoice to client
		* @param invoiceID string
		*	<p>InvoiceID from createInvoice result</p>
		* @return response array
		*/
        function doSendInvoice($invoiceID){
            $this->end_url = $this->getEndPoint("sendInvoice");
            $strSendInvoice = $this->prepareSendInvoice($invoiceID);
            $response = $this->curlRequest($strSendInvoice);
            $response = $this->parseCurlResponse($response);
            return $response;
        }
        //---doCreateAndSendInvoice---------------------------------------
		/**
		* paypal createAndSendInvoice method creates and sends the invoice all in one
		* @param invoice array
		*	<p>Invoice array structure</p>
		* @return response array
		*/
        function doCreateAndSendInvoice($invoice){
            $this->end_url = $this->getEndPoint("createAndSendInvoice");
            $strCreateAndSendInvoice = $this->prepareCreateInvoice($invoice);
            $response = $this->curlRequest($strCreateAndSendInvoice);
            $response = $this->parseCurlResponse($response);
            return $response;
        }
		//---doUpdateInvoice---------------------------------------
		/**
		* paypal updateInvoice method - updates an existing invoice
		* @param invoice array
		*	<p>Invoice array structure</p>
		* @return response array
		*/
        function doUpdateInvoice($invoice){
            $this->end_url = $this->getEndPoint("updateInvoice");
            $strUpdateInvoice = $this->prepareCreateInvoice($invoice);
            $response = $this->curlRequest($strUpdateInvoice);
            $response = $this->parseCurlResponse($response);
            return $response;
        }
		//---doGetInvoiceDetail---------------------------------------
		/**
		* paypal getInvoiceDetails method
		* @param invoiceID string
		*	<p>InvoiceID </p>
		* @return response array
		*/
		function doGetInvoiceDetail($invoiceID){
            $this->end_url = $this->getEndPoint("getInvoiceDetails");
            $strGetInvoiceDetail = $this->prepareGetInvoiceDetail($invoiceID);
            $response = $this->curlRequest($strGetInvoiceDetail);
            $response = $this->parseCurlResponse($response);
            return $response;
        }
        //---doCancelInvoice---------------------------------------
		/**
		* paypal cancelInvoice method cancels an invoice
		* @param invoice array
		*	<p>Invoice array structure</p>
		* @return response array
		*/
        function doCancelInvoice($invoice){
            $this->end_url = $this->getEndPoint("cancelInvoice");
            $strCancelInvoice = $this->prepareCancelInvoice($invoice);
            $response = $this->curlRequest($strCancelInvoice);
            $response = $this->parseCurlResponse($response);
            return $response;
        }
        //---doSearchInvoice---------------------------------------
		/**
		* paypal searchInvoices method searches invoices
		* @param invoice array
		*	<p>Invoice array structure</p>
		* @return response array
		*/
        function doSearchInvoice($invoice){
            $this->end_url = $this->getEndPoint("searchInvoices");
            $strSearchDetail = $this->prepareSearchInvoice($invoice);
            $response = $this->curlRequest($strSearchDetail);
            $response = $this->parseCurlResponse($response);
            $response['invoice']=$invoice;
            $response['request']=$strSearchDetail;
            return $response;
        }
        //---doMarkAsPaid---------------------------------------
		/**
		* paypal markAsPaid method
		* @param invoice array
		*	<p>Invoice array structure</p>
		* @return response array
		*/
        function doMarkAsPaid($invoice){
            $this->end_url = $this->getEndPoint("markAsPaid");
            $strMarkAsPaid = $this->prepareMarkAsPaid($invoice);
            $response = $this->curlRequest($strMarkAsPaid);
            $response = $this->parseCurlResponse($response);
            return $response;
        }
        
        /************** Functions Below are supporting functions for the funcitons above ************/

        function getInvoiceURL(){
            if($this->mode == "live"){return self::LIVE_INVOICE_URL;}
            else{return self::SANDBOX_INVOICE_URL;}
        }

        function getEndPoint($end_point_type){
            switch($end_point_type){
                case "createInvoice":
                    $end_point_url = $this->getInvoiceURL()."CreateInvoice";
                    break;
                case "sendInvoice":
                    $end_point_url = $this->getInvoiceURL()."SendInvoice";
                    break;
                case "createAndSendInvoice":
                    $end_point_url = $this->getInvoiceURL()."CreateAndSendInvoice";
                    break;
                case "updateInvoice":
                    $end_point_url = $this->getInvoiceURL()."UpdateInvoice";
                    break;
                case "getInvoiceDetails":
                    $end_point_url = $this->getInvoiceURL()."GetInvoiceDetails";
                    break;
                case "cancelInvoice":
                    $end_point_url = $this->getInvoiceURL()."CancelInvoice";
                    break;
                case "searchInvoices":
                    $end_point_url = $this->getInvoiceURL()."SearchInvoices";
                    break;
                case "markAsPaid":
                    $end_point_url = $this->getInvoiceURL()."MarkInvoiceAsPaid";
                    break;
                default:
                    $end_point_url = "";
                    break;
            }
            return $end_point_url;
        }

        function getInvoiceAPIHeader(){
            $headers[0] = "Content-Type: text/namevalue";               // either text/namevalue or text/xml
            $headers[1] = "X-PAYPAL-SECURITY-USERID: {$this->api_username}";    //API user
            $headers[2] = "X-PAYPAL-SECURITY-PASSWORD: {$this->api_password}";  //API PWD
            $headers[3] = "X-PAYPAL-SECURITY-SIGNATURE: {$this->api_signature}";    //API Sig
            $headers[4] = "X-PAYPAL-APPLICATION-ID: {$this->application_id}";   //APP ID
            $headers[5] = "X-PAYPAL-REQUEST-DATA-FORMAT: ".self::PAYPAL_REQUEST_DATA_FORMAT."";           //Set Name Value Request Format
            $headers[6] = "X-PAYPAL-RESPONSE-DATA-FORMAT: {$this->return_format}";          //Set Name Value Response Format  - NV/XML/JSON
            return $headers;
        }

        function curlRequest($str_req){
            // setting the curl parameters.
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->end_url);
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getInvoiceAPIHeader());

            //curl_setopt($ch, CURLOPT_HEADER, 1); // tells curl to include headers in response, use for testing
            // turning off the server and peer verification(TrustManager Concept).
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);

            // setting the NVP $my_api_str as POST FIELD to curl
            curl_setopt($ch, CURLOPT_POSTFIELDS, $str_req);

            // getting response from server
            $httpResponse = curl_exec($ch);
            if(!$httpResponse){
                $response = "{$this->end_url} failed: ".curl_error($ch)."(".curl_errno($ch).")";
                return $response;
            }
            return $httpResponse;
        }

        function parseCurlResponse($response){
			$format=$this->return_format;
			$parsed_response = array();
			switch($format){
				case 'NV':
					//name/value pairs
            		$pairs = explode("&", $response);
		            foreach ($pairs as $pair){
		                list($key,$val) = preg_split('/\=/',$pair,2);
		                if(preg_match('/^responseEnvelope\.(.+)$/i',$key,$m)){
							$parsed_response['responseEnvelope'][$m[1]]=urldecode($val);
						}
						elseif(preg_match('/^error\(([0-9]+)\)\.parameter\(([0-9]+)\)$/i',$key,$m)){
							$parsed_response['error'][$m[1]]['parameter'][$m[2]]=urldecode($val);
						}
						elseif(preg_match('/^error\(([0-9]+)\)\.(.+)$/i',$key,$m)){
							$parsed_response['error'][$m[1]][$m[2]]=urldecode($val);
						}
						else{
                        	$parsed_response[$key]=urldecode($val);
						}
		            }
            		break;
            	case 'XML':
					$parsed_response=$array = XML2Array::createArray($response);
            		break;
            	case 'JSON':
            		$parsed_response=json_decode($response,true);
            		break;
			}
            return $parsed_response;
        }

        function adjustStringLength($str, $limit = 300){
			//description must be 300 characters or less
            if( strlen($str) > $limit ){
				return substr($str, 0, $limit-1);
            }
            return $str;
        }

        function prepareCreateInvoice($invoice){
            $aryCreateInvoice = array();
            if(trim(@$invoice['language'])!= ""){
                $aryCreateInvoice['requestEnvelope.errorLanguage']  = $invoice['language'];   //en_US        //TODO
			}
            if(trim(@$invoice['merchant_email'])!= ""){
                $aryCreateInvoice['invoice.merchantEmail']          = $invoice['merchant_email'];
			}
            if(trim(@$invoice['payer_email'])!= ""){
                $aryCreateInvoice['invoice.payerEmail']             = $invoice['payer_email'];
			}
            if(trim(@$invoice['currency_code'])!= ""){
                $aryCreateInvoice['invoice.currencyCode']           = $invoice['currency_code'];   //USD TODO
			}
            if(trim(@$invoice['invoice_id'])!= ""){
                $aryCreateInvoice['invoice.number']                 = $invoice['invoice_id'];
			}
            if(trim(@$invoice['payment_terms'])!= ""){
                $aryCreateInvoice['invoice.paymentTerms']           = $invoice['payment_terms'];   //[DueOnReceipt, DueOnDateSpecified, Net10, Net15, Net30, Net45]
			}
            if(trim(@$invoice['discount_percent'])!= ""){
                $aryCreateInvoice['invoice.discountPercent']        = $invoice['discount_percent'];
			}
            if(trim(@$invoice['discount_amount'])!= ""){
                $aryCreateInvoice['invoice.discountAmount']         = $invoice['discount_amount'];
			}
            if(trim(@$invoice['invoice_terms'])!= ""){
                $aryCreateInvoice['invoice.terms']                  = $invoice['invoice_terms'];
			}
            if(trim(@$invoice['invoice_note'])!= ""){
                $aryCreateInvoice['invoice.note']                   = $invoice['invoice_note'];
			}
            if(trim(@$invoice['merchant_memo'])!= ""){
                $aryCreateInvoice['invoice.merchantMemo']           = $invoice['merchant_memo'];
			}
            if(trim(@$invoice['shipping_amount'])!= ""){
                $aryCreateInvoice['invoice.shippingAmount']         = $invoice['shipping_amount'];
			}
            if(trim(@$invoice['shipping_tax_name'])!= ""){
                $aryCreateInvoice['invoice.shippingTaxName']        = $this->adjustStringLength($invoice['shipping_tax_name']);
			}
            if(trim(@$invoice['shipping_tax_rate'])!= ""){
                $aryCreateInvoice['invoice.shippingTaxRate']        = $invoice['shipping_tax_rate'];
			}
            if(trim(@$invoice['logo_url'])!= ""){
                $aryCreateInvoice['invoice.logoUrl']                = $invoice['logo_url'];
			}
            if(trim(@$invoice['merchant_firstname'])!= ""){
                $aryCreateInvoice['invoice.merchantInfo.firstName']     = $invoice['merchant_firstname'];
			}
            if(trim(@$invoice['merchant_lastname'])!= ""){
                $aryCreateInvoice['invoice.merchantInfo.lastName']      = $invoice['merchant_lastname'];
			}
            if(trim(@$invoice['merchant_business_name'])!= ""){
                $aryCreateInvoice['invoice.merchantInfo.businessName']  = $invoice['merchant_business_name'];
			}
            if(trim(@$invoice['merchant_phone'])!= ""){
                $aryCreateInvoice['invoice.merchantInfo.phone']         = $invoice['merchant_phone'];
			}
            if(trim(@$invoice['merchant_fax'])!= ""){
                $aryCreateInvoice['invoice.merchantInfo.fax']           = $invoice['merchant_fax'];
			}
            if(trim(@$invoice['merchant_website'])!= ""){
                $aryCreateInvoice['invoice.merchantInfo.website']       = $invoice['merchant_website'];
			}
            if(trim(@$invoice['merchant_custom_value'])!= ""){
                $aryCreateInvoice['invoice.merchantInfo.customValue']   = $invoice['merchant_custom_value'];
			}
            if(trim(@$invoice['merchant_address1'])!= ""){
                $aryCreateInvoice['invoice.merchantInfo.address.line1']         = $invoice['merchant_address1'];
			}
            if(trim(@$invoice['merchant_address2'])!= ""){
                $aryCreateInvoice['invoice.merchantInfo.address.line2']         = $invoice['merchant_address2'];
			}
            if(trim(@$invoice['merchant_city'])!= ""){
                $aryCreateInvoice['invoice.merchantInfo.address.city']          = $invoice['merchant_city'];
			}
            if(trim(@$invoice['merchant_state'])!= ""){
                $aryCreateInvoice['invoice.merchantInfo.address.state']         = $invoice['merchant_state'];
			}
            if(trim(@$invoice['merchant_postal_code'])!= ""){
                $aryCreateInvoice['invoice.merchantInfo.address.postalCode']    = $invoice['merchant_postal_code'];
			}
            if(trim(@$invoice['merchant_country_code'])!= ""){
                $aryCreateInvoice['invoice.merchantInfo.address.countryCode']   = $invoice['merchant_country_code'];
			}
            if(trim(@$invoice['billing_firstname'])!= ""){
                $aryCreateInvoice['invoice.billingInfo.firstName']      = $invoice['billing_firstname'];
			}
            if(trim(@$invoice['billing_lastname'])!= ""){
                $aryCreateInvoice['invoice.billingInfo.lastName']       = $invoice['billing_lastname'];
			}
            if(trim(@$invoice['billing_business_name'])!= ""){
                $aryCreateInvoice['invoice.billingInfo.businessName']   = $invoice['billing_business_name'];
			}
            if(trim(@$invoice['billing_phone'])!= ""){
                $aryCreateInvoice['invoice.billingInfo.phone']          = $invoice['billing_phone'];
			}
            if(trim(@$invoice['billing_fax'])!= ""){
                $aryCreateInvoice['invoice.billingInfo.fax']            = $invoice['billing_fax'];
			}
            if(trim(@$invoice['billing_website'])!= ""){
                $aryCreateInvoice['invoice.billingInfo.website']        = $invoice['billing_website'];
			}
            if(trim(@$invoice['billing_custom_value'])!= ""){
                $aryCreateInvoice['invoice.billingInfo.customValue']    = $invoice['billing_custom_value'];
			}
            if(trim(@$invoice['billing_address1'])!= ""){
                $aryCreateInvoice['invoice.billingInfo.address.line1']          = $invoice['billing_address1'];
			}
            if(trim(@$invoice['billing_address2'])!= ""){
                $aryCreateInvoice['invoice.billingInfo.address.line2']          = $invoice['billing_address2'];
			}
            if(trim(@$invoice['billing_city'])!= ""){
                $aryCreateInvoice['invoice.billingInfo.address.city']           = $invoice['billing_city'];
			}
            if(trim(@$invoice['billing_state'])!= ""){
                $aryCreateInvoice['invoice.billingInfo.address.state']          = $invoice['billing_state'];
			}
            if(trim(@$invoice['billing_postalcode'])!= ""){
                $aryCreateInvoice['invoice.billingInfo.address.postalCode']     = $invoice['billing_postalcode'];
			}
            if(trim(@$invoice['billing_country_code'])!= ""){
                $aryCreateInvoice['invoice.billingInfo.address.countryCode']    = $invoice['billing_country_code'];
			}
            if(trim(@$invoice['shipping_firstname'])!= ""){
                $aryCreateInvoice['invoice.shippingInfo.firstName']     = $invoice['shipping_firstname'];
			}
            if(trim(@$invoice['shipping_lastname'])!= ""){
                $aryCreateInvoice['invoice.shippingInfo.lastName']      = $invoice['shipping_lastname'];
			}
            if(trim(@$invoice['shipping_businessname'])!= ""){
                $aryCreateInvoice['invoice.shippingInfo.businessName']  = $invoice['shipping_businessname'];
			}
            if(trim(@$invoice['shipping_phone'])!= ""){
                $aryCreateInvoice['invoice.shippingInfo.phone']         = $invoice['shipping_phone'];
			}
            if(trim(@$invoice['shipping_fax'])!= ""){
                $aryCreateInvoice['invoice.shippingInfo.fax']           = $invoice['shipping_fax'];
			}
            if(trim(@$invoice['shipping_website'])!= ""){
                $aryCreateInvoice['invoice.shippingInfo.website']       = $invoice['shipping_website'];
			}
            if(trim(@$invoice['shipping_custom_value'])!= ""){
                $aryCreateInvoice['invoice.shippingInfo.customValue']   = $invoice['shipping_custom_value'];
			}
            if(trim(@$invoice['shipping_address1'])!= ""){
                $aryCreateInvoice['invoice.shippingInfo.address.line1']         = $invoice['shipping_address1'];
			}
            if(trim(@$invoice['shipping_address2'])!= ""){
                $aryCreateInvoice['invoice.shippingInfo.address.line2']         = $invoice['shipping_address2'];
			}
            if(trim(@$invoice['shipping_city'])!= ""){
                $aryCreateInvoice['invoice.shippingInfo.address.city']          = $invoice['shipping_city'];
			}
            if(trim(@$invoice['shipping_state'])!= ""){
                $aryCreateInvoice['invoice.shippingInfo.address.state']         = $invoice['shipping_state'];
			}
            if(trim(@$invoice['shipping_postalcode'])!= ""){
                $aryCreateInvoice['invoice.shippingInfo.address.postalCode']    = $invoice['shipping_postalCode'];
			}
            if(trim(@$invoice['shipping_country_code'])!= ""){
                $aryCreateInvoice['invoice.shippingInfo.address.countryCode']   = $invoice['shipping_country_code'];        //US TODO
			}
            $nLoop = count($invoice['lineitems']);
            for($cnt=0;$cnt<$nLoop;$cnt++){
                if(trim(@$invoice['lineitems'][$cnt]['name'])!= ""){
                    $aryCreateInvoice["invoice.itemList.item({$cnt}).name"]       = $this->adjustStringLength($invoice['lineitems'][$cnt]['name']);
				}
                if(trim(@$invoice['lineitems'][$cnt]['description'])!= ""){
                    $aryCreateInvoice["invoice.itemList.item({$cnt}).description"]= $this->adjustStringLength($invoice['lineitems'][$cnt]['description']);
				}
				if(trim(@$invoice['lineitems'][$cnt]['date'])!= ""){
                    $aryCreateInvoice["invoice.itemList.item({$cnt}).date"]       = date(DATE_ATOM,strtotime($invoice['lineitems'][$cnt]['date']));
				}
                if(trim(@$invoice['lineitems'][$cnt]['quantity'])!= ""){
                    $aryCreateInvoice["invoice.itemList.item({$cnt}).quantity"]   = $invoice['lineitems'][$cnt]['quantity'];
				}
                if(trim(@$invoice['lineitems'][$cnt]['unitprice'])!= ""){
                    $aryCreateInvoice["invoice.itemList.item({$cnt}).unitPrice"]  = $invoice['lineitems'][$cnt]['unitprice'];
				}
                if(trim(@$invoice['lineitems'][$cnt]['tax_name'])!= ""){
                    $aryCreateInvoice["invoice.itemList.item({$cnt}).taxName"]    = $this->adjustStringLength($invoice['lineitems'][$cnt]['tax_name'],10);
				}
                if(trim(@$invoice['lineitems'][$cnt]['tax_rate'])!= ""){
                    $aryCreateInvoice["invoice.itemList.item({$cnt}).taxRate"]    = $invoice['lineitems'][$cnt]['tax_rate'];
				}
            }
            if(trim(@$invoice['invoice_date'])!= ""){
                $aryCreateInvoice['invoice.invoiceDate']            = date(DATE_ATOM,strtotime($invoice['invoice_date']));     //2011-12-31T05:38:48Z
			}
            if(trim(@$invoice['due_date'])!= ""){
                $aryCreateInvoice['invoice.dueDate']                = date(DATE_ATOM,strtotime($invoice['due_date']));
			}
            $request_string = http_build_query( $aryCreateInvoice );
            return $request_string;
        }

        function prepareSendInvoice($invoiceID){
            $arySendInvoice = array();
            $arySendInvoice['requestEnvelope.errorLanguage'] = "en_US";
            $arySendInvoice['invoiceID'] = $invoiceID;
            $request_string = http_build_query( $arySendInvoice );
            return $request_string;
        }

        function prepareGetInvoiceDetail($invoiceID){
            $arySendInvoice = array();
            $arySendInvoice['requestEnvelope.errorLanguage'] = "en_US";
            $arySendInvoice['invoiceID'] = $invoiceID;
            $request_string = http_build_query( $arySendInvoice );
            return $request_string;
        }

        function prepareCancelInvoice($invoice){
            $aryCancelInvoice = array();
            $aryCancelInvoice['requestEnvelope.errorLanguage'] = "en_US";
            $aryCancelInvoice['invoiceID'] = $invoice['invoice_id'];
            $aryCancelInvoice['subject']   = $invoice['subject'];
            $aryCancelInvoice['noteForPayer'] = $invoice['note'];
            $aryCancelInvoice['sendCopyToMerchant'] = "true";
            $request_string = http_build_query( $aryCancelInvoice );
            return $request_string;
        }

        function prepareSearchInvoice($invoice){
            $arySearchInvoice = array();
            if(trim(@$invoice['language'])!= ""){
				$arySearchInvoice['requestEnvelope.errorLanguage'] = $invoice['language'];  //en_US
			}
            if(trim(@$invoice['merchant_email'])!= ""){
                $arySearchInvoice['merchantEmail'] = $invoice['merchant_email'];
			}
            if(trim(@$invoice['page'])!= ""){
                $arySearchInvoice['page'] = $invoice['page'];
			}
            if(trim(@$invoice['page_size'])!= ""){
                $arySearchInvoice['pageSize'] = $invoice['page_size'];
			}
            if(trim(@$invoice['email'])!= ""){
                $arySearchInvoice['parameters.email'] = $invoice['email'];
			}
            if(trim(@$invoice['recipient_name'])!= ""){
                $arySearchInvoice['parameters.recipientName'] = $invoice['recipient_name'];
			}
            if(trim(@$invoice['business_name'])!= ""){
                $arySearchInvoice['parameters.businessName'] = $invoice['business_name'];
			}
            if(trim(@$invoice['invoice_number'])!= ""){
                $arySearchInvoice['parameters.invoiceNumber'] = $invoice['invoice_number'];
			}
            if(trim(@$invoice['status'])!= ""){
                $arySearchInvoice['parameters.status'] = $invoice['status'];///
			}
            if(trim(@$invoice['lower_amount'])!= ""){
                $arySearchInvoice['parameters.lowerAmount'] = $invoice['lower_amount'];
			}
            if(trim(@$invoice['upper_amount'])!= ""){
                $arySearchInvoice['parameters.uppderAmount'] = $invoice['upper_amount'];
			}
            if(trim(@$invoice['currency_code'])!= ""){
                $arySearchInvoice['parameters.currencyCode'] = $invoice['currency_code'];
			}
            if(trim(@$invoice['memo'])!= ""){
                $arySearchInvoice['parameters.memo'] = $invoice['memo'];
			}
            if(trim(@$invoice['origin'])!= ""){
                $arySearchInvoice['parameters.origin'] = $invoice['origin'];
			}
            if(trim(@$invoice['invoice_start_date'])!= ""){
                $arySearchInvoice['parameters.invoiceDate.startDate'] = date(DATE_ATOM,strtotime($invoice['invoice_start_date']));
			}
            if(trim(@$invoice['invoice_end_date'])!= ""){
                $arySearchInvoice['parameters.invoiceDate.endDate'] = date(DATE_ATOM,strtotime($invoice['invoice_end_date']));
			}
            if(trim(@$invoice['due_start_date'])!= ""){
                $arySearchInvoice['parameters.dueDate.startDate'] = date(DATE_ATOM,strtotime($invoice['due_start_date']));
			}
            if(trim(@$invoice['due_end_date'])!= ""){
                $arySearchInvoice['parameters.dueDate.endDate'] = date(DATE_ATOM,strtotime($invoice['due_end_date']));
			}
            if(trim(@$invoice['payment_start_date'])!= ""){
                $arySearchInvoice['parameters.paymentDate.startDate'] = date(DATE_ATOM,strtotime($invoice['payment_start_date']));
			}
            if(trim(@$invoice['payment_end_date'])!= ""){
                $arySearchInvoice['parameters.paymentDate.endDate'] = date(DATE_ATOM,strtotime($invoice['payment_end_date']));
			}
            if(trim(@$invoice['creation_start_date'])!= ""){
                $arySearchInvoice['parameters.creationDate.startDate'] = date(DATE_ATOM,strtotime($invoice['creation_start_date']));
			}
            if(trim(@$invoice['creation_end_date'])!= ""){
                $arySearchInvoice['parameters.creationDate.endDate'] = date(DATE_ATOM,strtotime($invoice['creation_end_date']));
			}
            $request_string = http_build_query( $arySearchInvoice );
            return $request_string;
        }

        function prepareMarkAsPaid($invoice){
            $aryPaidInvoice = array();
            $aryPaidInvoice['requestEnvelope.errorLanguage'] = "en_US";
            $aryPaidInvoice['invoiceID'] = $invoice['invoice_id'];
            if(isset($invoice['payment'])){
            	$aryPaidInvoice['payment']   = $invoice['payment'];
			}
            $aryPaidInvoice['payment.date']   = date(DATE_ATOM, strtotime($invoice['payment_date']));
            $aryPaidInvoice['payment.note'] = $invoice['payment_note'];
            $aryPaidInvoice['payment.method'] = $invoice['method'];
            $request_string = http_build_query( $aryPaidInvoice );
            return $request_string;
        }
    }#end of class
/*
CURL EXAMPLES Used as a reference
Creating an Invoice
	curl -s --insecure
	-H "X-PAYPAL-SECURITY-USERID: Your_API_username"
	-H "X-PAYPAL-SECURITY-PASSWORD: Your_API_password"
	-H "X-PAYPAL-SECURITY-SIGNATURE: Your_API_signature"
	-H "X-PAYPAL-REQUEST-DATA-FORMAT: NV"
	-H "X-PAYPAL-RESPONSE-DATA-FORMAT: NV"
	-H "X-PAYPAL-APPLICATION-ID: Your_AppID"
	https://svcs.sandbox.paypal.com/Invoice/CreateInvoice
	-d "requestEnvelope.errorLanguage=en_US
	&invoice.merchantEmail=merchant%40domain.com
	&invoice.payerEmail=jbui-us-business2%40paypal.com
	&invoice.currencyCode=USD
	&invoice.itemList.item(0).name=Banana+Leaf+--+001
	&invoice.itemList.item(0).description=Banana+Leaf
	&invoice.itemList.item(0).quantity=1
	&invoice.itemList.item(0).unitPrice=1
	&invoice.itemList.item(0).taxName=Tax1
	&invoice.itemList.item(0).taxRate=10.25
	&invoice.paymentTerms=Net10
	&invoice.logoUrl=https%3A%2F%2Fwww.example.com%2FYour_logo.jpg"

Sending an Invoice - The invoice ID, which is in the response to CreateInvoice,identifies the invoice to send.
	curl -s --insecure
	-H "X-PAYPAL-SECURITY-USERID: Your_API_username"
	-H "X-PAYPAL-SECURITY-PASSWORD: Your_API_password"
	-H "X-PAYPAL-SECURITY-SIGNATURE: Your_API_signature"
	-H "X-PAYPAL-REQUEST-DATA-FORMAT: NV"
	-H "X-PAYPAL-RESPONSE-DATA-FORMAT: NV"
	-H "X-PAYPAL-APPLICATION-ID: Your_AppID"
	https://svcs.sandbox.paypal.com/Invoice/SendInvoice
	-d "requestEnvelope.errorLanguage=en_US
	&invoiceID=INV2-RVY9-UWTW-64HZ-BR9W"

Creating and Sending an Invoice - equivalent to creating an invoice and sending it.
	curl -s --insecure
	-H "X-PAYPAL-SECURITY-USERID: Your_API_username"
	-H "X-PAYPAL-SECURITY-PASSWORD: Your_API_password"
	-H "X-PAYPAL-SECURITY-SIGNATURE: Your_API_signature"
	-H "X-PAYPAL-REQUEST-DATA-FORMAT: NV"
	-H "X-PAYPAL-RESPONSE-DATA-FORMAT: NV"
	-H "X-PAYPAL-APPLICATION-ID: Your_AppID"
	https://svcs.sandbox.paypal.com/Invoice/CreateAndSendInvoice
	-d "requestEnvelope.errorLanguage=en_US
	&invoice.merchantEmail=merchant%40domain.com
	&invoice.payerEmail=jbui-us-business2%40paypal.com
	&invoice.currencyCode=USD
	&invoice.itemList.item(0).name=Banana+Leaf+--+001
	&invoice.itemList.item(0).description=Banana+Leaf
	&invoice.itemList.item(0).quantity=1
	&invoice.itemList.item(0).unitPrice=1
	&invoice.itemList.item(0).taxName=Tax1
	&invoice.itemList.item(0).taxRate=10.25
	&invoice.paymentTerms=Net10
	&invoice.logoUrl=https%3A%2F%2Fwww.example.com%2FYour_logo.jpg"

Updating an Invoice - in addition to the updated fields, you must also provide all of the original fields used tocreate the invoice.
	curl -s --insecure
	-H "X-PAYPAL-SECURITY-USERID: Your_API_username"
	-H "X-PAYPAL-SECURITY-PASSWORD: Your_API_password"
	-H "X-PAYPAL-SECURITY-SIGNATURE: Your_API_signature"
	-H "X-PAYPAL-REQUEST-DATA-FORMAT: NV"
	-H "X-PAYPAL-RESPONSE-DATA-FORMAT: NV"
	-H "X-PAYPAL-APPLICATION-ID: Your_AppID"
	https://sandbox.svcs.paypal.com/Invoice/UpdateInvoice
	-d "requestEnvelope.errorLanguage=en_US
	&invoice.merchantEmail=merchant%40domain.com
	&invoice.payerEmail=jbui-us-business2%40paypal.com
	&invoice.currencyCode=USD
	&invoice.itemList.item(0).name=Banana+Leaf+--+001
	&invoice.itemList.item(0).description=Banana+Leaf
	&invoice.itemList.item(0).quantity=3
	&invoice.itemList.item(0).unitPrice=1
	&invoice.itemList.item(0).taxName=Tax1
	&invoice.itemList.item(0).taxRate=10.25
	&invoice.paymentTerms=Net10
	&invoice.logoUrl=https%3A%2F%2Fwww.example.com%2FYour_logo.jpg"

Obtaining Invoice Details
	curl -s --insecure
	-H "X-PAYPAL-SECURITY-USERID: Your_API_username"
	-H "X-PAYPAL-SECURITY-PASSWORD: Your_API_password"
	-H "X-PAYPAL-SECURITY-SIGNATURE: Your_API_signature"
	-H "X-PAYPAL-REQUEST-DATA-FORMAT: NV"
	-H "X-PAYPAL-RESPONSE-DATA-FORMAT: NV"
	-H "X-PAYPAL-APPLICATION-ID: Your_AppID"
	https://svcs.sandbox.paypal.com/Invoice/GetInvoiceDetails
	-d "requestEnvelope.detailLevel=ReturnAll
	&requestEnvelope.errorLanguage=en_US
	&invoiceID=INV2-RVY9-UWTW-64HZ-BR9W"

Canceling an Invoice
	curl -s --insecure
	-H "X-PAYPAL-SECURITY-USERID: Your_API_username"
	-H "X-PAYPAL-SECURITY-PASSWORD: Your_API_password"
	-H "X-PAYPAL-SECURITY-SIGNATURE: Your_API_signature"
	-H "X-PAYPAL-REQUEST-DA TA-FORMAT: NV"
	-H "X-PAYPAL-RESPONSE-DATA-FORMAT: NV"
	-H "X-PAYPAL-APPLICATION-ID: Your_AppID"
	https://sandbox.svcs.paypal.com/Invoice/CancelInvoice
	-d "requestEnvelope.errorLanguage=en_US
	&invoiceID=INV2-RVY9-UWTW-64HZ-BR9W"
	&subject=Cancel+it
	&noteForPayer=Cancel+it+now
	&sendCopyToMerchant=true"

Searching for Invoices - can return a maximum of 100 invoices per page (pageSize)
	curl -s --insecure
	-H "X-PAYPAL-SECURITY-USERID: Your_API_username"
	-H "X-PAYPAL-SECURITY-PASSWORD: Your_API_password"
	-H "X-PAYPAL-SECURITY-SIGNATURE: Your_API_signature"
	-H "X-PAYPAL-REQUEST-DATA-FORMAT: NV"
	-H "X-PAYPAL-RESPONSE-DATA-FORMAT: NV"
	-H "X-PAYPAL-APPLICATION-ID: Your_AppID"
	https://svcs.sandbox.paypal.com/Invoice/SearchInvoices
	-d "requestEnvelope.errorLanguage=en_US
	&merchantEmail=jb-us-seller1%40paypal.com
	&parameters.origin=API &parameters.email=jb-us-seller1%40paypal.com
	&page=1
	&pageSize=10"

Successful Result:
Array
(
    [responseEnvelope] => Array
        (
            [timestamp] => 2012-09-15T09:09:01.510-07:00
            [ack] => Success
            [correlationId] => 49968f500bf94
            [build] => 3566933
        )

    [invoiceID] => INV2-GZFF-3SRK-ABW7-ATWY
    [invoiceNumber] => DM15983-35
    [invoiceURL] => https://www.paypal.com/us/cgi-bin/?cmd=_inv-details&id=INV2-GZFF-3SRK-ABW7-ATWY
    [totalAmount] => 2135
)

Failure:
Array
(
    [responseEnvelope] => Array
        (
            [timestamp] => 2012-09-15T08:45:49.147-07:00
            [ack] => Failure
            [correlationId] => e327b456b7237
            [build] => 3566933
        )

    [error] => Array
        (
            [0] => Array
                (
                    [errorId] => 580046
                    [domain] => PLATFORM
                    [subdomain] => Application
                    [severity] => Error
                    [category] => Application
                    [message] => An invoice already exists for the merchant with this invoice number: DM15983-33
                    [parameter] => Array
                        (
                            [0] => DM15983-33
                        )

                )

        )

)

*/
?>