<?php
date_default_timezone_set('America/Denver');
$arWhoisServer = array(
    'com'         => array('whois.crsnic.net', 'No match for'),
    'net'         => array('whois.crsnic.net', 'No match for'),   
    'org'         => array('whois.pir.org', 'NOT FOUND'),
    'biz'         => array('whois.biz', 'Not found'),
    'mobi'        => array('whois.dotmobiregistry.net', 'NOT FOUND'),
    'tv'         => array('whois.nic.tv', 'No match for'),
    'in'         => array('whois.inregistry.net', 'NOT FOUND'),
    'info'         => array('whois.afilias.net', 'NOT FOUND'),   
    'co.uk'     => array('whois.nic.uk', 'No match'),
    'co.ug'     => array('wawa.eahd.or.ug', 'No entries found'),
    'or.ug'     => array('wawa.eahd.or.ug', 'No entries found'),
    'nl'         => array('whois.domain-registry.nl', 'not a registered domain'),
    'ro'         => array('whois.rotld.ro', 'No entries found for the selected'),
    'com.au'    => array('whois.ausregistry.net.au', 'No data Found'),
    'ca'         => array('whois.cira.ca', 'AVAIL'),
    'org.uk'    => array('whois.nic.uk', 'No match'),
    'name'         => array('whois.nic.name', 'No match'),
    'us'         => array('whois.nic.us', 'Not Found'),
    'ac.ug'     => array('wawa.eahd.or.ug', 'No entries found'),
    'ne.ug'     => array('wawa.eahd.or.ug', 'No entries found'),
    'sc.ug'     => array('wawa.eahd.or.ug', 'No entries found'),
    'ws'        => array('whois.website.ws', 'No Match'),
    'be'         => array('whois.ripe.net', 'No entries'),
    'com.cn'     => array('whois.cnnic.cn', 'no matching record'),
    'net.cn'     => array('whois.cnnic.cn', 'no matching record'),
    'org.cn'     => array('whois.cnnic.cn', 'no matching record'),
    'no'        => array('whois.norid.no', 'no matches'),
    'se'         => array('whois.nic-se.se', 'No data found'),
    'nu'         => array('whois.nic.nu', 'NO MATCH for'),
    'com.tw'     => array('whois.twnic.net', 'No such Domain Name'),
    'net.tw'     => array('whois.twnic.net', 'No such Domain Name'),
    'org.tw'     => array('whois.twnic.net', 'No such Domain Name'),
    'cc'         => array('whois.nic.cc', 'No match'),
    'nl'         => array('whois.domain-registry.nl', 'is free'),
    'pl'         => array('whois.dns.pl', 'No information about'),
    'pt'         => array('whois.dns.pt', 'No match')
);
$arFailedDomain = array();
$arAvailableDomain = array();
$arUnavailableDomain = array();

function checkDomainAvailability($domain){
    global $arWhoisServer, $requestTimeout;
    // Get the domain without http:// and www.
    $domain = trim($domain);
    preg_match('@^(http://www\.|http://|www\.)?([^/]+)@i', $domain, $matches);
    $domain = $matches[2];
    // Get the tld
    $tld = explode('.', $domain, 2);
    $tld = strtolower(trim($tld[1]));
    // If the domain name is valid and we have an entry corresponding to our tld
    if(strlen($domain) <= strlen($tld) + 1){
            checkResult($domain, $tld, 'Invalid Domain name', 'error');
    }elseif(!array_key_exists($tld, $arWhoisServer)){
            checkResult($domain, $tld, "Unsupported Domain ($tld)", 'error');
    }else{
        $server = $arWhoisServer[$tld][0];
        $fp = fsockopen($server, 43, $errno, $error, $requestTimeout);
        if(!$fp) {
            checkResult($domain, $tld, "Could not connect to '$server' on port 43.", 'error');
        }else{
            // Clear the output of the previous response
            $output = '';
            $domain .= "\r\n";
            $startTime = time();
            fputs($fp, $domain);
            $i = 0;
            while(!feof($fp)){
                if($startTime + $requestTimeout <= time()){
                    $output .= fgets($fp);
                }else{
                    fclose($fp);
                    checkResult($domain, $tld, "The request timed out", 'error');
                }
            }
            fclose($fp);
            checkResult($domain, $tld, $output);
        }
    }
}

function checkResult($domain, $tld = '', $response, $status = 'success'){
    global $arWhoisServer, $arFailedDomain, $arAvailableDomain, $arUnavailableDomain;
    if($status == 'error'){
        $msg = "<span class='error'>$response</span>";
        $arFailedDomain[count($domainStatus)] = array($domain, $response);
    }else{
        if(eregi($arWhoisServer[$tld][1], $response)){
            $msg = "<span class='success'>Available</span>";
            $arAvailableDomain[count($arAvailableDomain)] = $domain;
        }else{
            $msg = "<span class='error'>Registered</span>";
            $arUnavailableDomain[count($arUnavailableDomain)] = $domain;
        }
    }
    echo $msg;
}
?>
<html>
<head>
<title>Whois Checker</title>
<style type="text/css">
body{background:#F2F2F2;font:bold 11px Verdana, Arial, sans-serif;}
.error{color:red;font-weight:bold;}
.success{color:green;font-weight:bold;}
.tblDomains{width:350px;font:bold 12px Verdana, Arial, sans-serif;border-collapse:collapse;}
.tblDomains td{text-align:left;border:1px #CCC solid;}
</style>
</head>

<body>
<table style="width:100%;height:100%;">
<tr><td>
<center>
<table class="tblDomains" cellspacing="0" cellpadding="5">
<?php
// Path to PHP Mailer
require("../../includes/contact/class.phpmailer.php");
$notifyFailedDomains = true;
$notifyUnavailableDomains = true;

$file = 'domains.txt';
$i = 0;
echo "<tr><td>Opening file '$file'</td><td>";
if(file_exists($file)){
    echo '<span class="success">SUCCESS</span></td></tr>';
    $domains = file($file);
    $cnt = count($domains);
    echo '<tr><td>Total Number of domains to check</td><td>';
    echo $cnt ? "<span class='success'>$cnt</span>" : "<span class='error'>0</span>";
    echo '</td></tr>';
    echo "<tr><td colspan='2'>Checking...<br/>";
    // Loop through each domains
    foreach($domains as $domain){
        echo "<tr><td>&bull; $domain</td><td>";
        checkDomainAvailability($domain);
        echo '</td></tr>';
    }
    echo '<tr><td colspan="2">';
    echo "<div>AVAILABLE DOMAINS</div>\n";
    foreach ($arAvailableDomain as $domain){
        echo " <div style='font-weight:bold;color:green;padding-left:20px;'>$domain</div>";
    }
    echo "<div>UNAVAILABLE</div>\n";
    foreach ($arUnavailableDomain as $domain){
        echo " <div class='failed' style='padding-left:20px;'>$domain</div>";
    }
    echo "<div>FAILED</div>\n";
    foreach ($arFailedDomain as $domain){
        echo " <div class='failed' style='padding-left:20px;'>$domain[0] : $domain[1]</div>";
    }
    echo '</td></tr>';
}else{
    echo '<span class="error">FAILED</span></td></tr>';
}
?>
</td></tr>
</table>
</center>
</td>
</tr>
</table>
</body>
</html>