<?
/*
  Class: GoogleCalendarWrapper
  Author: Skakunov Alex (i1t2b3@gmail.com)
  Date: 26.11.06
  Description: provides a simple tool to work with Google Calendar (add events currenly)
    You must define login and password.
    
    Class adds events into your main calendar by default.
    If you want to add events in other calendar, write its XML URL into "feed_url" property like this:

      $gc = new GoogleCalendarWrapper("email@gmail.com", "password");

      $gc->feed_url =
            "http://www.google.com/calendar/feeds/pcafiuntiuro1rs%40group.calendar.google.com/private-586fa023b6a7151779f99b/basic";
    Feel free to provide "basic" URL, it will be automatically converted to "full" one (prepare_feed_url() method)..
    How to get the XML URL: http://code.google.com/apis/gdata/calendar.html#get_feed
*/

include "MyCurl.php"; //MyCurl class is required (http://a4.users.phpclasses.org/browse/package/3547.html)

class GoogleCalendarWrapper extends MyCurl
{
  public $email;
  public $password;
  public $feed_url; //= "http://www.google.com/calendar/feeds/default/private/full";

  private $fAuth;
  public  $isLogged = false;
  public  $feed_url_prepared;
  
  
  
  function GoogleCalendarWrapper($email, $password)
  {
    $this->email = $email;
    $this->password = $password;
    $this->feed_url_prepared = $this->feed_url;
    parent::MyCurl();
  }
  
  //login with Google's technology of "ClientLogin"
  //check here: http://code.google.com/apis/accounts/AuthForInstalledApps.html
  function login()
  {
    $post_data = array();
    $post_data['Email']  = $this->email;
    $post_data['Passwd'] = $this->password;
    $post_data['source'] = "CelioCal";
    $post_data['service'] = "cl";
    $post_data['accountType'] = "GOOGLE";

    $this->getHeaders = true;
    $this->getContent = true;

    $response = $this->post("https://www.google.com/accounts/ClientLogin", $post_data, null, $http_code);

    if(200==$http_code)
    {
      $this->fAuth = parent::get_parsed($response, "Auth=");
      $this->isLogged = true;
	   
      return 1; //1
    }
    $this->isLogged = false;
    return 0; //0
  }
  
  //to make the feed URL writable, it should be ended with "private/full"
  //check this: http://code.google.com/apis/gdata/calendar.html#get_feed=
  function prepare_feed_url()
  {
    $url = parse_url($this->feed_url);
    $path = explode("/", $url["path"]);
    $size = sizeof($path);
    if($size>4)
    {
      $path[$size-1] = "full";
      $path[$size-2] = "private";
      $path = implode("/", $path);
    }
    $this->feed_url_prepared = $url["scheme"]."://".$url["host"].$path;
	return $this->feed_url_prepared;
  }
  
  //adds new event into calendar
  //filled $settings array should be provided
  function add_event($settings)
  
  {
    if(!$this->isLogged)
      $this->login();
	  
    
    if($this->isLogged)
    {
	  error_reporting(E_ALL & ~E_NOTICE);
	  
      $_entry = "<entry xmlns='http://www.w3.org/2005/Atom' xmlns:gd='http://schemas.google.com/g/2005'>
        <category scheme='http://schemas.google.com/g/2005#kind' term='http://schemas.google.com/g/2005#event'></category>
        <title type='text'>".$settings["title"]."</title>
        <content type='text'>".$settings["content"]."</content>
        <author>
          <name>".$this->email."</name>
          <email>".$this->email."</email>
        </author>
        <gd:transparency
          value='http://schemas.google.com/g/2005#event.opaque'>
        </gd:transparency>
        <gd:eventStatus
          value='http://schemas.google.com/g/2005#event.confirmed'>
        </gd:eventStatus>
        <gd:where valueString='".$settings["where"]."'></gd:where>
        <gd:when startTime='".$settings["startDay"]."T".$settings["startTime"].".000Z'
          endTime='".$settings["endDay"]."T".$settings["endTime"].".000Z'></gd:when>
      </entry>";
	  
	
      
      $this->prepare_feed_url();
      
      $header = array();
      $header[] = "Host: www.google.com";
      $header[] = "MIME-Version: 1.0";
      $header[] = "Accept: text/xml";
      $header[] = "Authorization: GoogleLogin auth=".$this->fAuth;
      $header[] = "Content-length: ".strlen($_entry);
      $header[] = "Content-type: application/atom+xml";
      $header[] = "Cache-Control: no-cache";
      $header[] = "Connection: close \r\n";
      $header[] = $_entry;
	  
	  $opts=array(
		'-headers'	=> $header);
		
		
      
	  $rtn1=postXML($this->feed_url_prepared, $_entry, $opts);
	  $split=preg_split('/[\r\n]+/',$rtn1['header']);
	  $gsess=preg_split('/ /', $split[4]);
	  	  
	  $rtn=postXML($gsess[1], $_entry, $opts);
	  
	  return $rtn1;
	  
          
    }
    else
      echo "cannot login with '".$this->email."' email and '<font color=\"lightgray\">".$this->password."</font>' password<br/>";
     return false;
  }
  
  function add_event_allday($settings)
  
  {
    if(!$this->isLogged)
      $this->login();
	  
    
    if($this->isLogged)
    {
	  error_reporting(E_ALL & ~E_NOTICE);
	  
      $_entry = "<entry xmlns='http://www.w3.org/2005/Atom' xmlns:gd='http://schemas.google.com/g/2005'>
        <category scheme='http://schemas.google.com/g/2005#kind' term='http://schemas.google.com/g/2005#event'></category>
        <title type='text'>".$settings["title"]."</title>
        <content type='text'>".$settings["content"]."</content>
        <author>
          <name>".$this->email."</name>
          <email>".$this->email."</email>
        </author>
        <gd:transparency
          value='http://schemas.google.com/g/2005#event.opaque'>
        </gd:transparency>
        <gd:eventStatus
          value='http://schemas.google.com/g/2005#event.confirmed'>
        </gd:eventStatus>
        <gd:where valueString='".$settings["where"]."'></gd:where>
        <gd:when startTime='".$settings["startDay"]."' "."endTime='".$settings["endDay"]."'></gd:when>
      </entry>";
	  
	  $rtn=array();
      
	  $header = array();
      $header[] = "Host: www.google.com";
      $header[] = "MIME-Version: 1.0";
      $header[] = "Accept: text/xml";
      $header[] = "Authorization: GoogleLogin auth=".$this->fAuth;
      $header[] = "Content-length: ".strlen($_entry);
      $header[] = "Content-type: application/atom+xml";
      $header[] = "Cache-Control: no-cache";
      $header[] = "Connection: close \r\n";
      $header[] = $_entry;
	  
	  $opts=array(
		'-headers'	=> $header);
      
	  $rtn[1]=postXMLOld($this->feed_url_prepared, $_entry, $opts);
	  $split=preg_split('/[\r\n]+/',$rtn[1]['header']);
	  $gsess=preg_split('/ /', $split[4]);
	  	  
	  $rtn[2]=postXMLOld($gsess[1], $_entry, $opts);
	  return $rtn[2];

           
    }
    else
      echo "cannot login with '".$this->email."' email and '<font color=\"lightgray\">".$this->password."</font>' password<br/>";
     return false;
  }
  
	function getEvent($settings){
		$rtn='';
		if(!$this->isLogged)
		$this->login();
		   
		if($this->isLogged)
		{
			$header = array();
			  $header[] = "Host: www.google.com";
			  $header[] = "MIME-Version: 1.0";
			  $header[] = "Accept: text/xml";
			  $header[] = "Authorization: GoogleLogin auth=".$this->fAuth;
			  $header[] = "Cache-Control: no-cache";
			  $header[] = "Connection: close \r\n";
	  
			$opts=array(
				'-headers'	=> $header,
				'-method'	=> "GET" );
      
			$rtn=postURL($settings, $opts);
			$split=preg_split('/[\r\n]+/',$rtn['header']);
			$gsess=preg_split('/ /', $split[4]);
						
			$rtn=postURL($gsess[1], $opts);
			$rtn=soap2XML($rtn['body']);
		}
		return $rtn;
  }
  
  function deleteEvent($settings=array()){
		$rtn='';
		if(!$this->isLogged)
		$this->login();
		    
		if($this->isLogged)
		{
			$header = array();
			  $header[] = "Host: www.google.com";
			  $header[] = "MIME-Version: 1.0";
			  $header[] = "Accept: text/xml";
			  $header[] = "Authorization: GoogleLogin auth=".$this->fAuth;
			  $header[] = "Content-length: ".strlen($_entry);
			  //$header[] = "Content-type: application/atom+xml";
			  $header[] = "Cache-Control: no-cache";
			  $header[] = "Connection: close \r\n";
			  $header[] = "If-Match: *";
			  			  
			$opts=array(
				'-headers'	=> 	$header,
				'-method'	=> 	"DELETE");
      
			$rtn=postURL($settings['url'], $opts);
		}
		return $rtn;
  }
  
  function editEvent($settings){
		$rtn='';
		if(!$this->isLogged)
		$this->login();
	  
    
		if($this->isLogged)
		{
		
			$_entry = "<entry xmlns='http://www.w3.org/2005/Atom' xmlns:gd='http://schemas.google.com/g/2005'>
							<category scheme='http://schemas.google.com/g/2005#kind' term='http://schemas.google.com/g/2005#event'></category>
							<id>".$settings['url']."</id>
							<author>
							<name>".$this->email."</name>
							<email>".$this->email."</email>
							</author>
							<title type='text'>".$settings['title']."</title>
							<content type='text'>".$settings['content']."</content>
							<link rel='edit' type='application/atom+xml'
							href='".$settings['url']."' />
							<gd:where valueString='".$settings['where']."'></gd:where>
							<gd:when startTime='".$settings["startDay"]."' "."endTime='".$settings["endDay"]."'></gd:when>
						</entry>";
			//return printValue($settings);
			$header = array();
			  $header[] = "Host: www.google.com";
			  $header[] = "MIME-Version: 1.0";
			  $header[] = "Accept: text/xml";
			  $header[] = "Authorization: GoogleLogin auth=".$this->fAuth;
			  $header[] = "Content-length: ".strlen($_entry);
			  $header[] = "Content-type: application/atom+xml";
			  $header[] = "Cache-Control: no-cache";
			  $header[] = "Connection: close \r\n";
			  $header[] = $_entry;

	  
			$opts=array(
				'-headers'	=> $header,
				'-method'	=> "PUT" );
      
			$rtn=postURL($settings['url'], $opts);
			//$split=preg_split('/[\r\n]+/',$rtn['header']);
			//$gsess=preg_split('/ /', $split[4]);
						
			//$rtn=postURL($gsess[1], $opts);
			//$rtn=soap2XML($rtn['body']);
		}
		return $rtn;
  }

  
}

?>