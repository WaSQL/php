********************************************************************************
                 Authorize.net AIM Interface using CURL
********************************************************************************

COPYRIGHT

   (c) 2005 - Micah Carrick 
   email@micahcarrick.com
   http://www.micahcarrick.com

   You are free to use, distribute, and modify this software under the terms of 
   the GNU General Public License.  See the included license.txt file for the
   complete terms and conditions.


INTRODUCTION

   This PHP class is intended to create an easy means of implementing your own
   script to handle processing payments via authorize.net's AIM API interface. 
   Unlinke some of the other code snippets out there, it is not intended to do
   ALL the work.  I'm trying to leave all the flexibilty in the hands of the
   PHP developer-- that should be you.

   You should have the AIM documentation (available from authorize.net in PDF
   format) at hand.  This documentation will give you a reference to all the 
   field names, valid values for said names, response codes, and alot of other
   information which you WILL need to understand how to use this class.
   http://www.authorize.net/support/AIM_guide.pdf


FILES

   authorizenet.class.php   -   The main class file
   demo.php                 -   A demonstration on using the class
   readme.txt               -   This readme file
   license.txt              -   GNU General Public License terms and conditions


REQUIREMENTS

   - PHP 4+ (I tested it using PHP 4.3.10) with CURL and SSL support installed
   - An authorize.net AIM merchant account.  You will need your login name and
     either your password OR a transaction key (tran_key).
