<?
/*
  Class: MyCurl
  Author: Skakunov Alex (i1t2b3@gmail.com)
  Date: 26.11.06
  Description: provides a simple tool to GET/POST data with help of CURL library
*/
class MyCurl
{
  public $getHeaders = true;//headers will be added to output
  public $getContent = true; //contens will be added to output
  public $followRedirects = true; //should the class go to another URL, if the current is "HTTP/1.1 302 Moved Temporarily"
  
  private $fCookieFile;
  private $fSocket;
  
  function MyCurl()
  {
    $this->fCookieFile = tempnam("/tmp", "g_");
  }

  function init()
  {
    return $this->fSocket = curl_init();
  }
  
  function setopt($opt, $value)
  {
    return curl_setopt($this->fSocket, $opt, $value);
  }
  
  function load_defaults()
  {
    $this->setopt(CURLOPT_RETURNTRANSFER, 1);
    $this->setopt(CURLOPT_FOLLOWLOCATION, $this->followRedirects);
    $this->setopt(CURLOPT_REFERER, "http://google.com");
    $this->setopt(CURLOPT_VERBOSE, false); 
    $this->setopt(CURLOPT_SSL_VERIFYPEER, false);
    $this->setopt(CURLOPT_SSL_VERIFYHOST, false);
    $this->setopt(CURLOPT_HEADER, $this->getHeaders);
    $this->setopt(CURLOPT_NOBODY, !$this->getContent);
    $this->setopt(CURLOPT_COOKIEJAR, $this->fCookieFile);
    $this->setopt(CURLOPT_COOKIEFILE, $this->fCookieFile);
    $this->setopt(CURLOPT_USERAGENT, "MyCurl");
    $this->setopt(CURLOPT_POST, 1);
    $this->setopt(CURLOPT_CUSTOMREQUEST,'POST');
    $fp = fopen("c:\\__curl.log", "a");
    if($fp)
      $this->setopt(CURLOPT_STDERR, $fp);
  }

  function destroy()
  {
    return curl_close($this->fSocket);
  }

  function head($url)
  {
    $this->init();
    if($this->fSocket)
    {
      $this->getHeaders = true;
      $this->getContent = false;
      $this->load_defaults();
      $this->setopt(CURLOPT_POST, 0);
      $this->setopt(CURLOPT_CUSTOMREQUEST,'HEAD');
      $this->setopt(CURLOPT_URL, $url);
      $result = curl_exec($this->fSocket);
      $this->destroy();
      return $result;
    }
    return 0;
  }

  function get($url)
  {
    $this->init();
    if($this->fSocket)
    {
      $this->load_defaults();
      $this->setopt(CURLOPT_POST, 0);
      $this->setopt(CURLOPT_CUSTOMREQUEST,'GET');
      $this->setopt(CURLOPT_URL, $url);
      $result = curl_exec($this->fSocket);
      $this->destroy();
      return $result;
    }
    return 0;
  }

  function post($url, $post_data, $arr_headers=array(), &$http_code)
  {
    $this->init();
    if($this->fSocket)
    {
      $post_data = $this->compile_post_data($post_data);
      $this->load_defaults();
      if(!empty($post_data))
        $this->setopt(CURLOPT_POSTFIELDS, $post_data);

      if(!empty($arr_headers))
        $this->setopt(CURLOPT_HTTPHEADER, $arr_headers);
      
      $this->setopt(CURLOPT_URL, $url);

      $result = curl_exec($this->fSocket);
      $http_code = curl_getinfo($this->fSocket, CURLINFO_HTTP_CODE);
      $this->destroy();
      return $result;
    }
    return 0;
  }

  function compile_post_data($post_data)
  {
    $o="";
    if(!empty($post_data))
      foreach ($post_data as $k=>$v)
        $o.= $k."=".urlencode($v)."&";
    return substr($o,0,-1);
  }
  
  function get_parsed($result, $bef, $aft="")
  {
    $line=1;
    $len = strlen($bef);
    $pos_bef = strpos($result, $bef);
    if($pos_bef===false)
      return "";
    $pos_bef+=$len;
    
    if(empty($aft))
    { //try to search up to the end of line
      $pos_aft = strpos($result, "\n", $pos_bef);
      if($pos_aft===false)
        $pos_aft = strpos($result, "\r\n", $pos_bef);
    }
    else
      $pos_aft = strpos($result, $aft, $pos_bef);
    
    if($pos_aft!==false)
      $rez = substr($result, $pos_bef, $pos_aft-$pos_bef);
    else
      $rez = substr($result, $pos_bef);
    
    return $rez;
  }

}
?>