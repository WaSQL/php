# WaSQL - Web access to SQL

## What is WaSQL?
WaSQL is web application platform currently written in PHP with a few supporting files written in Perl.  It is designed to help you build web sites, forms, e-commerce, intranet and other custom web applications rapidly.  WaSQL deploys pages and applications using a database driven MVC architecture.  It is a stand-alone platform as it does not require any outside or 3rd party add-ons to work.  It is also a replacement for PhpMyAdmin since database schema management is built in.  User management is also built in.

## WaSQL License
WaSQL is free for both personal and business use. Read the full license [here](license.md)

## Required Skills
To use WaSQL effectively you need to know HTML5, CSS3, JavaScript SQL, and PHP.  Many functions are already built for you but you need to understand programming logic to really use it.

## Best Way to Learn WaSQL
I have found that the best way to learn WaSQL is to download it and use it.  I also recommend reading the functions found in database.php and common.php for starters.

## Where to Get Help
Documentation is built in and searchable via the backend admin Help menu. If you need additional help with your project please contact me at steve.lloyd@gmail.com.  There are also developers at http://www.devmavin.com that know WaSQL well and are willing to help.

## How can I Help WaSQL become better?
Feel free to request changes via github.  You can also help by donating to the cause.  Donations can be sent via PayPal to steve.lloyd@gmail.com

## Installation - Windows
- **Install git**
	-  you can install git by going to https://git-scm.com/download/win.  This will download the latest git client.  I suggest selecting "Use Git and optional Unix tools from the Windows Command Prompt".  If you are not comfortable with this option, select "Use Git from the Windows Command Prompt" option. Select the default options for the rest.
- **Install WaSQL**
	- Open a command prompt and cd to the directory you want to place the wasql folder.  Type the following command and hit enter:
		- d:\\>git clone https://github.com/WaSQL/v2.git wasql
		- in the wasql folder copy sample.config.xml to config.xml 
		- using an editor, edit config.xml. Change the dbname, dbuser, and dbpass if you want. 
- **Install WampServer**
	- you can install WAMP by going to http://sourceforge.net/projects/wampserver/ and downloading the latest install. This will install Apache, MySQL and PHP on your computer. Once installed, use the WAMP icon in the system tray to insure the following PHP extensions are enabled:
		- php_curl
		- php_mbstring
		- php_mysqli
	- add the following to the Apache httpd.conf file (changing the path to where you installed wasql):
		- in the "IfModule alias_module" section:
			- Alias /php/ "d:/wasql/php/"
			- Alias /wfiles/ "d:/wasql/wfiles/"
		- Just below the ifModule section create the following:
<pre><xmp>
	<Directory "d:/wasql/">
		Options Indexes FollowSymLinks
		AllowOverride all
		Require local
	</Directory>
</xmp></pre>

- copy sample.htaccess in the wasql folder to c:\wamp\www\ folder and name it .htaccess.
- restart Apache using the WAMP icon in the system tray.
- using the WAMP icon in the system tray, open a MySQL console and hit ENTER (default password in blank). Type the following (changing the user and pass to match the config.xml file)
	- mysql>grant all privileges on *.* to 'wasql_dbuser'@'localhost' identified by 'wasql_dbpass';
	- mysql>flush privileges;
	- mysql>create database wasql_sample;
- **Ready to try**
	- using a browser open http://localhost.  If all went well it will take a second to load and you will see the sample website.
	- using a browser open http://localhost/a.  This should take you the the wasql admin interface. Enter admin/admin as the default user/pass.

##Installation - Linux
- **Install git**
	-  if you don't already have it installed, install git.  Depending on your linux flavor this will be different.
- **Install WaSQL**
	- From a telnet prompt cd to the directory you want to place the wasql folder.  I usually place it just below document root (/var/www)  Type the following command and hit enter:
		- >git clone https://github.com/WaSQL/v2.git wasql
		- in the wasql folder copy sample.config.xml to config.xml 
		- edit config.xml. Change the dbname, dbuser, and dbpass if you want.
		- in the wasql folder copy sample.htaccess to .htaccess
		- give execute permission to the .sh files and .pl files
			- chmod 755 *.sh sh/*.sh *.pl
		- from your document root run dirsetup.sh as follows.  If you places wasql in /var/www and your documentroot was /var/www/html, then from /var/www/html
			->../wasql/dirsetup.sh
		- create a blank database called wasql_sample (to match the dbname in config.xml)
- **Ready to try**
	- using a browser open your website.  If all went well it will take a second to load and you will see the sample website.
	- using a browser open your website with /a at the end of the url.  This should take you the the wasql admin interface. Enter admin/admin as the default user/pass.

