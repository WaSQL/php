# WaSQL - Web access to SQL
##### A multi-language Rapid Application Development platform

## What is WaSQL?
WaSQL is truly RAD, in both senses of the word. It is a PHP driven, Rapid Application Development (RAD) platform that lets you code in many different languages (whoa! that is rad!).  It is designed to help you build web sites, intranets, forms, e-commerce, and other custom web applications.  WaSQL deploys pages and applications using a database driven MVC architecture.  It is a stand-alone platform as it does not require any outside or 3rd party add-ons to work.  It is also a replacement for PhpMyAdmin since database schema management is built in.  User management is also built in.

One of the things that makes WaSQL unique from most other applications is that all of the page logic is stored in database records.  This has several advantages.  It makes hacking that pages much, much harder.  It also allows you to wrap your entire site into a Mysql dump file, move it to another server or another location, and then restore it within seconds.


## Supported Scripting Languages
WaSQL is written in PHP but supports embeded PHP, Nodejs, Python, Perl, Ruby, Vbscript, Lua, Bash, and shell scripts. Request, session, server, config, and user variables are passed in to all scripts, regardless of langauge, so you can access them in whatever language you write your code in.  Note: if you want to modify the variables you must modify them in PHP.

WaSQL is the only web development platform, that we are aware of, that lets you write in whatever language you want.  Please let us know if there are other languages you would like it to support and we will do our best to add support for it.

Caveat: your web server has to support the languages you decide to write in.

## WaSQL License
WaSQL is free for both personal and business use. Read the full license [here](license.md)

## Required Skills
To use WaSQL effectively you need to know HTML5, CSS3, JavaScript, SQL, and one of the supported programming languages. At least a basic knowledge of PHP is also very helpful.

## Where to Get Help
You can find instructional videos at https://www.youtube.com/channel/UC9FSUWNqd6dlIUq6VbOTjmw. There is also documentation is built in and searchable via the backend admin Help menu. If you need additional help with your project please contact me at steve.lloyd@gmail.com. 

## How can I Help WaSQL become better?
Feel free to request changes via github.  You can also help by donating to the cause.  Donations can be sent via PayPal to steve.lloyd@gmail.com

## Installation - Windows
- **Install git**
	-  you can install git by going to https://git-scm.com/download/win.  This will download the latest git client.  I suggest selecting "Use Git and optional Unix tools from the Windows Command Prompt".  If you are not comfortable with this option, select "Use Git from the Windows Command Prompt" option. Select the default options for the rest.
- **Install WaSQL**
	- Open a command prompt and cd to the directory you want to place the wasql folder.  Type the following command and hit enter:
		- c:\\>git clone https://github.com/WaSQL/php.git wasql
		- in the wasql folder copy sample.config.xml to config.xml 
		- using an editor, edit config.xml. Change the dbname, dbuser, and dbpass if you want. 
		- create a directory called temp in the wasql\php folder
- **Install Apache, PHP, and MySQL**
	- the easiest way I have found to install Apache, MySQL and PHP on your computer is to follow the instructions here: https://miloserdov.org/?p=7703. 
	- once installed, add the following to the Apache httpd.conf file (changing the path to where you installed wasql):
		- in the "IfModule alias_module" section:
		
```
	Alias /php/ "c:/wasql/php/"
	Alias /wfiles/ "c:/wasql/wfiles/"
</IfModule>
```

- Just below the ifModule section create the following (changing the path to where you installed wasql):

```
<Directory "c:/wasql/">
	Options Indexes FollowSymLinks
	AllowOverride all
	Require local
</Directory>
```
- make sure mod_rewrite is enabled (remove the pound sign from the front)

```
LoadModule rewrite_module modules/mod_rewrite.so
```

- copy sample.htaccess in the wasql folder to your htdocs (DOCUMENT ROOT) folder and name it .htaccess  NOTE: you may need a different text editor that allows you to save .htaccess. Make sure it does not have the .txt extension. Note: notepad will not work. Use Notepad++ or sublime.

- Open the PHP php.ini in a text editor and uncomment any extensions you want enabled. For example, extension=pgsql to enable postgres.  You will need php-zip and php-curl for sure.  

- Add your new PHP bin directory path to your PATH Environment.
- Add your new Apache bin directory path to your PATH Environment.

- restart your computer
- open a DOS console and type >mysql -u root -p <ENTER>. Then enter your password and hit <ENTER>.  Type the following (this is going to create a username and password that Wasql will use)
```
	- mysql>CREATE USER 'wasql_dbuser'@'%' IDENTIFIED with mysql_native_password BY 'wasql_dbpass';
	- mysql>GRANT ALL PRIVILEGES ON *.* TO 'wasql_dbuser'@'%' WITH GRANT OPTION;
	- mysql>flush privileges;
	- mysql>create database wasql_sample;
	- mysql>exit
```
- **Ready to try**
	- using a browser open http://localhost.  If all went well you will see the sample website wizard. Select the one you want and click on the Install button.
	- using a browser open http://localhost/a.  This should take you the the wasql admin interface. Enter admin/admin as the default user/pass.

## Installation - Linux
- **Install git**
	-  if you don't already have it installed, install git.  Depending on your linux flavor this will be different.
- **Install WaSQL**
	- From a terminal prompt cd to the directory you want to place the wasql folder.  I usually place it just below document root (/var/www)  Type the following command and hit enter:
		- >git clone https://github.com/WaSQL/php.git wasql
		- cd to the wasql folder and run> sh initialize.sh
		- edit config.xml. Change the dbname, dbuser, and dbpass if you want.
		- from your document root run dirsetup.sh as follows.  If you places wasql in /var/www and your documentroot was /var/www/html, then from /var/www/html
			->../wasql/dirsetup.sh
		- create a blank database called wasql_sample (to match the dbname in config.xml)
- **Ready to try**
	- Using a browser open your website.  If all went well you will see the sample website wizard. Select the one you want and click on the Install button.
	- using a browser open your website with /a at the end of the url.  This should take you the the wasql admin interface. Enter admin/admin as the default user/pass.

