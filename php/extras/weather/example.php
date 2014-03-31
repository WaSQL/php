<?php
//////////////////////////////
//PHPWEATHERLIB EXAMPLE  
/////////////////////////////
//CREATED BY: ELLIOTT BRUEGGEMAN
//DISTRIBUTION: BUNDLED WITH THE PHPWEATHERLIB LIBRARY v1.1
//PROJECT HOMEPAGE: http://www.ebrueggeman.com/phpweatherlib
//////////////////////////////////////////////////////////////

//INCLUDE THE LIBRARY FILE
//**CHANGE** TO RELATIVE LOCATION OF phpweatherlib.php FILE
include_once('phpweatherlib.php');

$displayWeather=true;

//CREATE OUR WEATHERLIB OBJECT FOR NEW YORK CITY (WEATHER STATION KNYC)
$weatherLib=new WeatherLib('KNYC');
if($weatherLib->has_error())
{
	echo "ERROR: ".$weatherLib->get_error();
	$displayWeather=false;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>PHPWeatherLib Example</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="weather_styles.css" rel="stylesheet" type="text/css" media="all" />
</head>
<body>
<?php if($displayWeather==true){ ?>
<table class="table_weather_widget">
  <tr> 
    <th colspan="2"><div>Weather For <?php echo $weatherLib->get_location(); ?> 
      </div></th>
  </tr>
  <tr> 
    <td rowspan="3"><p><img src="<?php echo $weatherLib->get_icon(); ?>" alt="Weather Icon" name="currentConditions" id="currentConditions" /></p></td>
    <td>Current Conditions:<strong> <?php echo $weatherLib->get_weather_string(); ?></strong></td>
  </tr>
  <tr> 
    <td>Temp: <strong><?php echo $weatherLib->get_temp_f(); ?> F</strong></td>
  </tr>
  <tr> 
    <td><?php echo $weatherLib->get_observation_time(); ?></td>
  </tr>
</table>
<?php } //END IF ?>
</body>
</html>