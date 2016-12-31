<?php
if(!isDBStage()){exit;}
?>
<pre>
##### Alexa/Apps/Reports
DONE: 		Get SalesTalk reports working
NOT NOW:	Determine if we can handle product names                                 
NOT NOW:	Determine if we can handle customer names
DONE:		Greeting based on users timezone
STEVE:  DONE   	Fix legends, titles and icons on D3 charts - Steve & Laurie
	DONE		remove category on title
	DONE			sql icon - use icon-database
	DONE			icons are highlighted when selected instead of hidden.
	DONE			legonds are cut off
	DONE			Move tile to second line
DEV: 		API to pull existing products and orders and populate table
			Add individual Usage Reports and tabs for tracking number of audible requests per day, minutes used per days, graph of reports used vs unused, streak count of how many days accessing reports
				STEVE: add audible history and report stats on the left menu
			Create User Account page
				Laurie will come up with the list to add to the left menu.
DONE:			Change all money utterances to dollars and cents  ($amount=money_format('%.2n', $value);)
			API to pull existing products and orders and populate table
			Product Mix Report
				Limit to Top 10  and Bottom 10 in report
			Add July data to database in order to have 3 months running data
			Create the card version list the report requested and the response for the Alexa app
			 - add images to reports table to use
			 link to account
			Create the ability to enable and disable audible reports via a spoken passcode
				When alexa launches ask for PIN if locked
				Add utterance to "lock" alexa
				add ablity on website to unlock and change pin.

			Create the WAM utility to track visitors to the website - which is critical for the conversion report
		 		Pixel code that they put on the success page
		 			unique visitor count for that client
					this is normally a javascript code that is placed on their site
				cut and paste the link
				This link needs to be displayed in their account.


##### Server Config Changes - Jake
NOT NOW:	Condense servers to Sendy - Jake
NOT NOW:	Get SSL working on Sendy
			Get monitoring for email and server uptime working - Jake
			Get GUI for server email installed - Jake
			Get Lauries mail history back
NOT NOW:	get http://odoo.skillsai.com/ working again
			Get sample Alexa app working

##### Website Changes
DONE:	Revamp About Page
DONE:	Create team Bio Page under the About menu
	Replace Branding on Hangman
	Replace Branding on Pico
	Create new ;Salestalk Product page
	Move Games to Fun Stuff under Products Page
DONE:	Modify FAQ page to have categories - like this https://dcrazed.com/build-proper-faq-pages-20-examples-explained/
	Implement shopping cart for SalesTalk
	Implement Stripe for billing
DONE:	Fix read more/collapse  (read more sometimes shows up)
	Until account is setup wit a data source, use the demo wordpress data for reports.
	Show that they are using the demo data in their account. Demo/live switch
	Add Laurie's colors to d3 charts


##### Laurie ToDo List
	Add articles to blog - Laurie
NOT NOW:	Implement Odoo for accounting - Laurie
DONE:	Determine and rank chart colors for consistency 10 with hex values
DONE:	Add comment capability to blog or revert to WordPress  -- test and fix bugs (steve)
	Create the DRIP campaign for new customers
DONE:	Powerpoint template
	Pitch deck


Login with Amazon
Skillsai-LWA-Live
Client ID:amzn1.application-oa2-client.e1bfeb1ac8f0451ab694e273c8529c95
Client Secret:fd757a39d98a567cccefb2403ab32ea890404f83c087cef4e588e388a10a930e


Skillsai-LWA-Stage
Client ID:amzn1.application-oa2-client.46f47e66046e4b57a1e646c650692b04
Client Secret:f91cdc188a17542148f12b7f64d93d6156ce70fe12e2d91d95e93a9f3a803063


Skillsai Website Login with Amazon
http://login.amazon.com/website
https://sellercentral.amazon.com/gp/homepage.html?ie=UTF8&*Version*=1&*entries*=0
Client ID: amzn1.application-oa2-client.0b9ac699611b496c8aafeaa7e2916d44
Client Secret:97b95a99929c0c965c3baf5f6af9e8047084255e44f18c025829f90f1e091235
</pre>


<pre>
::Data Sources
Account Profile Settings
	What does does the week start?
	Fiscal year starts (Jan-dec, jul 1 to june 30)
	cents or no cents

DONE::JAKE:: generate random data for test
	 STEVE - Script/page to activate the woo webhooks.
		REF: https://woothemes.github.io/woocommerce-rest-api-docs/#create-a-webhook
DONE::STEVE:: Form for user to enter URL, consumer key and consumer secret.
	STEVE DONE:	- List webhooks and check to see if we are already registered
	STEVE	- add webhook order.created, order.deleted  {order.updated gets triggered on new orders, creating duplicate entries}
		- add webhook customer.created, customer.deleted
		- add webhook product.created, product.deleted
		- show webhooks with green checkmark for ones that are activated
		- option to enable/disable webhook
	JAKE:DONE::Install Wordpress, Woocommerce, Sample Data, etc on woo.skillsai.com on second server
	JAKE::add DNS entry for woo.skillsai.com to point to second server - ROUTE 53
	Steve:Done::Figure out why json data fails with virtural columns
</pre>

<view:clientdata_woo_log>
Order Status
	Pending Payment
	Processing
	On Hold
	Completed
	Cancelled
	Refunded
	Failed
alter table clientdata_woo_orders add
	amount float(12,2) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.order.total')));



alter table clientdata_woo_orders add
	billto_postcode char(10) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.order.customer.billing_address.postcode')));


alter table clientdata_woo_orders add
	billto_state varchar(100) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.order.customer.billing_address.state')));

alter table clientdata_woo_orders add
	billto_country varchar(50) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.order.customer.billing_address.country')));


alter table clientdata_woo_orders add
	billto_city varchar(150) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.order.customer.billing_address.city')));




alter table clientdata_woo_orders add
	shipto_postcode char(10) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.order.customer.shipping_address.postcode')));


alter table clientdata_woo_orders modify
	shipto_state varchar(100) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.order.customer.shipping_address.state')));

alter table clientdata_woo_orders modify
	shipto_country varchar(50) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.order.customer.shipping_address.country')));


alter table clientdata_woo_orders modify
	shipto_city varchar(150) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.order.customer.shipping_address.city')));


alter table clientdata_woo_orders add
	product_ids json GENERATED ALWAYS AS (json_extract(jdoc,'$.order.line_items[*].product_id'));

alter table clientdata_woo_orders add
	product_qtys json GENERATED ALWAYS AS (json_extract(jdoc,'$.order.line_items[*].quantity'));

alter table clientdata_woo_orders add
	product_names json GENERATED ALWAYS AS (json_extract(jdoc,'$.order.line_items[*].name'));

alter table clientdata_woo_orders add
	product_categories json GENERATED ALWAYS AS (json_extract(jdoc,'$.product.categories'));

alter table clientdata_woo_orders add
	cdate datetime GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.order.created_at')));

alter table clientdata_woo_orders add
	type varchar(50) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.webhook')));

alter table clientdata_woo_orders add
	customer_id integer GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.order.customer.id')));

alter table clientdata_woo_orders add
	order_id integer GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.order.id')));

alter table clientdata_woo_orders add
	order_status varchar(100) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.order.status')));

alter table clientdata_woo_orders add
	email varchar(255) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.order.customer.billing_address.email')));

alter table clientdata_woo_orders add
	firstname varchar(255) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.order.customer.billing_address.first_name')));

alter table clientdata_woo_orders add
	lastname varchar(255) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.order.customer.billing_address.last_name')));

-----------------




alter table clientdata_woo_orders add
amount float(12,2) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.order.total')));

alter table clientdata_woo_orders add
shipto_state char(10) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.order.customer.shipping_address.state')));

alter table clientdata_woo_orders add
shipto_country char(10) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.order.customer.shipping_address.country')));

alter table clientdata_woo_orders add
	product_ids json GENERATED ALWAYS AS (json_extract(jdoc,'$.order.line_items[*].product_id'))

alter table clientdata_woo_orders add
	product_qtys json GENERATED ALWAYS AS (json_extract(jdoc,'$.order.line_items[*].quantity'))

alter table clientdata_woo_orders add
	product_names json GENERATED ALWAYS AS (json_extract(jdoc,'$.order.line_items[*].name'))

alter table clientdata_woo_orders add
	product_categories json GENERATED ALWAYS AS (json_extract(jdoc,'$.product.categories'))
alter table clientdata_woo_orders modify
cdate varchar(255) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.order.created_at')));

alter table clientdata_woo_orders add
type varchar(50) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.webhook')));

alter table clientdata_woo_orders add
customer_id integer GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.order.customer.id')));

alter table clientdata_woo_log add
	product_id json GENERATED ALWAYS AS (json_extract(jdoc,'$.product.id'))

json_extract(jdoc,'$.order.line_items[*].product_id') product_id
		,json_extract(jdoc,'$.order.line_items[*].name') product_name
---------------------


CLIENTDATA_STRIPE_ORDERS

alter table clientdata_stripe_orders add
	amount float(12,2) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.amount')));

alter table clientdata_stripe_orders add
	cdate datetime GENERATED ALWAYS AS (FROM_UNIXTIME(TRIM(BOTH '"' FROM json_extract(jdoc,'$.created'))));

alter table clientdata_stripe_orders add
	order_id varchar(255) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.id')));

alter table clientdata_stripe_orders add
	order_status varchar(100) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.status')));

alter table clientdata_stripe_orders add
	shipto_city varchar(150) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.shipping.address.city')));

alter table clientdata_stripe_orders add
	shipto_state varchar(150) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.shipping.address.state')));

alter table clientdata_stripe_orders add
	shipto_postcode varchar(150) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.shipping.address.postal_code')));

alter table clientdata_stripe_orders add
	shipto_country varchar(150) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.shipping.address.country')));

--------------------------- clientdata_woo_products
alter table clientdata_woo_products add
id integer GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.id')));


--------------------
alter table clientdata_profiles add
name varchar(255) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.name')));

alter table clientdata_profiles add
email varchar(255) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.email')));

alter table clientdata_profiles add
timezone varchar(255) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.timeZoneName')));

alter table clientdata_profiles add
postal_code varchar(25) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.postal_code')));

</view:clientdata_woo_log>

<view:clientdata_stripe_log>
alter table clientdata_stripe_log add
amount float(12,2) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.data.object.amount')));

alter table clientdata_stripe_log add
cdate datetime GENERATED ALWAYS AS (from_unixtime(TRIM(BOTH '"' FROM json_extract(jdoc,'$.created'))))

alter table clientdata_stripe_log add
type varchar(50) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.type')));

alter table clientdata_stripe_log add
customer_id integer GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.data.object.metadata.client_id')));

</view:clientdata_stripe_log>
