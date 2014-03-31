<?php
///////////////////////////////////////////////////////////////////
// PHPWeatherLib - Elliott Brueggeman v1.11
// PHP Weather Conditions Library
// Project Homepage: http://www.ebrueggeman.com/phpweatherlib
// Documentation available on project homepage
//////////////////////////////////////////////////////////////////
// Rights: Free for personal use.
// Free for commercial use if sites
// include unobtrusive link to project homepage on all 
// sites that use the PHPWeatherLib library
/////////////////////////////////////////////////////////////////
//USER CHANGEABLE VALUES
define('WEA_TIMEOUT', 5); //TIMEOUT IN SEC PER PARSE TRY

//National Oceanic and Atmospheric Administration (NOAA) XML Feed Base
define('WEA_BASE_SITE', 'http://www.weather.gov/data/current_obs/');
define('WEA_WEATHER_ICON_BASE', 'http://weather.gov/weather/images/fcicons/');

class WeatherLib
{
	var $xmlURL;
	var $xmlSite;
	var $elementArray;
	var $varCount;
	var $xml_parser;
	var $pauseHandler;
	var $mappingRef;
	var $error;
	
	function WeatherLib($site){
		if(!empty($site)){ 
			//INIT
			$this->loadMappings();
			$this->xmlSite=$site;
			$this->xmlURL = WEA_BASE_SITE . $this->xmlSite .'.xml';
			$this->varCount=0;
			$this->error='';
			
			//ATTEMPT PARSE
			if(!$this->parse()){
				return false;
			}	
		}
		else{
			$this->error='Site missing from WeatherInfo constructor';
			return false;
		}
		return true;
	}
	
	function loadMappings()
	{
		//DO NOT CHANGE
		//THIS IS THE ARRAY MAPPING
		//TO THE XML ELEMENTS
		$this->mappingRef=array(
			'WEA_LOCATION'=> 7,
			'WEA_LATITUDE'=> 9,
			'WEA_LONGITUDE'=> 10,
			'WEA_OBSERVATION_TIME'=> 11,
			'WEA_WEATHER_STRING'=> 13,
			'WEA_TEMP_F'=> 15,
			'WEA_TEMP_C'=> 16,
			'WEA_HUMIDITY'=> 17,
			'WEA_WIND_STRING'=> 18,
			'WEA_WIND_DIR'=> 19,
			'WEA_WIND_MPH'=> 21,
			'WEA_PRESSURE'=> 24,
			'WEA_DEWPOINT_F'=> 26,
			'WEA_DEWPOINT_C'=> 27,
			'WEA_HEATINDEX_F'=> 29,
			'WEA_HEATINDEX_C'=> 30,
			'WEA_WINDCHILL_F'=> 32,
			'WEA_WINDCHILL_C'=> 33,
			'WEA_VISIBILITY'=> 34,
			'WEA_ICON_BASE'=> 35,
			'WEA_ICON_FILE'=> 36);
	}
	
	function parse(){
		//SET PARSE DEPENDENT VARS
		$this->elementArray=array();
		$this->pauseHandler=false;

		//SET PARSER
		$this->xmlParser = xml_parser_create();
		xml_set_object($this->xmlParser,$this);
		xml_parser_set_option($this->xmlParser, XML_OPTION_CASE_FOLDING, true);
		xml_set_element_handler($this->xmlParser, "startElement", "endElement");
		xml_set_character_data_handler($this->xmlParser, "characterData");
		
		$data='';
		
		//USE FUNCTION file_get_contents TO OPEN XML FILE IF IT EXISTS, PHP >= 4.3 
		if(function_exists('file_get_contents')){
			//TRY TO OPEN XML
			if(!$data=$this->getFileRecentPHP()){
				//IF IT FAILS, TRY AGAIN USING OTHER METHOD
				if(!$data=$this->getFileRecentPHP()){
					return false;
				}
				else{
					//SECOND TRY WORKED, CLEAR ERROR
					$this->error='';
				}
			}
		}
		else {
			//OLDER VERSION OF PHP, USING FOPEN INSTEAD
			//TRY TO OPEN XML
			if(!$data=$this->getFileOlderPHP()){
				//IF IT FAILS, TRY AGAIN
				if(!$data=$this->getFileOlderPHP()){
					return false;
				}
				else{
					//SECOND TRY WORKED, CLEAR ERROR
					$this->error='';
				}			
			}
		}

		//READY TO PARSE XML FILE
		if (!xml_parse($this->xmlParser, $data)){
			$this->error='XML Parser Error';
			return false;
		}	
	}
	
	//USE FUNCTION file_get_contents TO OPEN XML FILE IF IT EXISTS, PHP >= 4.3 
	function getFileRecentPHP()
	{
		$data='';
		//SET TIMEOUT
		ini_set('default_socket_timeout', WEA_TIMEOUT);  
		//RETRIEVE FILE
		if(!$data=file_get_contents($this->xmlURL)){
			$this->error='XML Parser Error: file_get_contents of ' . $this->xmlURL . ' failed.';
			return false;
		}
		return $data;
	}
	
	//USE FUNCTION FOPEN FOR OLDER PHP VERSIONS OR IF OTHER FUNCTION DOESN'T WORK 
	function getFileOlderPHP()
	{
		$data='';
		//RETRIEVE FILE
		if($dataFile = @fopen($this->xmlURL, "r" )){
			while (!feof($dataFile)) {
				$data.= fgets($dataFile, 4096);
			}
			fclose($dataFile);
		}
		else{
			$this->error='XML Parser Error: fopen of ' . $this->xmlURL . ' failed.';
			return false;
		}
		return $data;
	}
	
	function startElement($parser, $name, $attrs){
		//SPECIAL CASE, ELEMENT DOESNT PARSE CORRECTLY -DONT HANDLE
		if(strtolower($name)=='pressure_string'){ $this->pauseHandler=true; }
	}
	
	function endElement($parser, $name){
		//SPECIAL CASE, ELEMENT DOESNT PARSE CORRECTLY -START HANDLING AGAIN
		if(strtolower($name)=='pressure_string'){ $this->pauseHandler=false; }
	}
	
	function characterData($parser, $data) {
		if($this->pauseHandler==false){
			$data=trim($data);
			if(!empty($data)){
				$this->elementArray[$this->varCount]=$data;
				$this->varCount++;
			}
		}
	}
	
	//RETRIEVE ERROR TEXT
	function getError(){ return $this->error; }
	function get_error(){ return $this->error; }
	function hasError(){ if(!empty($this->error)){ return true; } return false; }
	function has_error(){ if(!empty($this->error)){ return true; } return false; }
	
	//USER FUNCTIONS TO RETRIEVE WEATHER INFO
	function get_location(){ return $this->elementArray[$this->mappingRef['WEA_LOCATION']]; }
	function get_latitude(){ return $this->elementArray[$this->mappingRef['WEA_LATITUDE']]; }
	function get_longitude(){ return $this->elementArray[$this->mappingRef['WEA_LONGITUDE']]; }
	function get_observation_time(){ return $this->elementArray[$this->mappingRef['WEA_OBSERVATION_TIME']]; }
	function get_weather_string(){ return $this->elementArray[$this->mappingRef['WEA_WEATHER_STRING']]; }
	function get_temp_f(){ return $this->elementArray[$this->mappingRef['WEA_TEMP_F']]; }
	function get_temp_c(){ return $this->elementArray[$this->mappingRef['WEA_TEMP_C']]; }
	function get_humidity(){ return $this->elementArray[$this->mappingRef['WEA_HUMIDITY']]; }
	function get_wind_string(){ return $this->elementArray[$this->mappingRef['WEA_WIND_STRING']]; }
	function get_wind_dir(){ return $this->elementArray[$this->mappingRef['WEA_WIND_DIR']]; }
	function get_wind_mph(){ return $this->elementArray[$this->mappingRef['WEA_WIND_MPH']]; }
	function get_pressure(){ return $this->elementArray[$this->mappingRef['WEA_PRESSURE']]; }
	function get_dewpoint_f(){ return $this->elementArray[$this->mappingRef['WEA_DEWPOINT_F']]; }
	function get_dewpoint_c(){ return $this->elementArray[$this->mappingRef['WEA_DEWPOINT_C']]; }
	function get_heatindex_f(){ return $this->elementArray[$this->mappingRef['WEA_HEATINDEX_F']]; }
	function get_heatindex_c(){ return $this->elementArray[$this->mappingRef['WEA_HEATINDEX_C']]; }
	function get_windchill_f(){ return $this->elementArray[$this->mappingRef['WEA_WINDCHILL_F']]; }
	function get_windchill_c(){ return $this->elementArray[$this->mappingRef['WEA_WINDCHILL_C']]; }
	function get_visibility(){ return $this->elementArray[$this->mappingRef['WEA_VISIBILITY']]; }
	function get_icon_base(){ return WEA_WEATHER_ICON_BASE; }
	function get_icon_file(){ return $this->get_NOAA_icon($this->get_weather_string()); }
	function get_icon(){ return $this->get_icon_base().$this->get_icon_file(); }	


	//pass in current condition
	//return the suggested icon
	//Thanks to Jim FitzGerald for this function
	function get_NOAA_icon($current_condition_string) {

		Switch (trim($current_condition_string)) {
			case 'Mostly Cloudy':
			case 'Mostly Cloudy with Haze':
			case 'Mostly Cloudy and Breezy':
			   return 'bkn.jpg';
			   break;
			case 'Fair':
			case 'Clear':
			case 'Fair with Haze':
			case 'Clear with Haze':
			case 'Fair and Breezy' :
			case 'Clear and Breezy' :
			   return 'skc.jpg';
			   break;
			case 'A Few Clouds' :
			case 'A Few Clouds with Haze' :
			case 'A Few Clouds and Breezy' :
			   return 'few.jpg';
			   break;
			case 'Partly Cloudy' :
			case 'Partly Cloudy with Haze' :
			case 'Partly Cloudy and Breezy':
			   return  'sct.jpg';
			   break;
			case 'Overcast' :
			case 'Overcast with Haze' :
			case 'Overcast and Breezy' :
			   return 'ovc.jpg';
			   break;
			case 'Fog/Mist':
			case 'Fog' :
			case 'Freezing Fog':
			case 'Shallow Fog' :
			case 'Partial Fog':
			case 'Patches of Fog':
			case 'Fog in Vicinity':
			case 'Freezing Fog in Vicinity':
			case 'Shallow Fog in Vicinity':
			case 'Partial Fog in Vicinity':
			case 'Patches of Fog in Vicinity':
			case 'Showers in Vicinity Fog':
			case 'Light Freezing Fog':
			case 'Heavy Freezing Fog':
				return 'fg.jpg';
				break;
			case 'Smoke' :
				return 'smoke.jpg';
				break;
			case 'Freezing Rain':
			case 'Freezing Drizzle':
			case 'Light Freezing Rain':
			case 'Light Freezing Drizzle':
			case 'Heavy Freezing Rain':
			case 'Heavy Freezing Drizzle':
			case 'Freezing Rain in Vicinity':
			case 'Freezing Drizzle in Vicinity':
				return 'fzra.jpg';
				break;
			case 'Ice Pellets':
			case 'Light Ice Pellets':
			case 'Heavy Ice Pellets':
			case 'Ice Pellets in Vicinity':
			case 'Showers Ice Pellets':
			case 'Thunderstorm Ice Pellets':
			case 'Ice Crystals':
			case 'Hail':
			case 'Small Hail/Snow Pellets':
			case 'Light Small Hail/Snow Pellets':
			case 'Heavy small Hail/Snow Pellets':
			case 'Showers Hail':
			case 'Hail Showers':
				return 'ip.jpg';
				break;
			case 'Freezing Rain Snow':
			case 'Light Freezing Rain Snow':
			case 'Heavy Freezing Rain Snow':
			case 'Freezing Drizzle Snow':
			case 'Light Freezing Drizzle Snow':
			case 'Heavy Freezing Drizzle Snow':
			case 'Snow Freezing Rain':
			case 'Light Snow Freezing Rain':
			case 'Heavy Snow Freezing Rain':
			case 'Snow Freezing Drizzle':
			case 'Light Snow Freezing Drizzle':
			case 'Heavy Snow Freezing Drizzle':
				return 'mix.jpg';
				break;
			case 'Rain Ice Pellets':
			case 'Light Rain Ice Pellets':
			case 'Heavy Rain Ice Pellets':
			case 'Drizzle Ice Pellets':
			case 'Light Drizzle Ice Pellets':
			case 'Heavy Drizzle Ice Pellets':
			case 'Ice Pellets Rain':
			case 'Light Ice Pellets Rain':
			case 'Heavy Ice Pellets Rain':
			case 'Ice Pellets Drizzle':
			case 'Light Ice Pellets Drizzle':
			case 'Heavy Ice Pellets Drizzle':
				return 'raip.jpg';
				break;
			case 'Rain Snow':
			case 'Light Rain Snow':
			case 'Heavy Rain Snow':
			case 'Snow Rain':
			case 'Light Snow Rain':
			case 'Heavy Snow Rain':
			case 'Drizzle Snow':
			case 'Light Drizzle Snow':
			case 'Heavy Drizzle Snow':
			case 'Snow Drizzle':
			case 'Light Snow Drizzle':
			case 'Heavy Drizzle Snow':
				return 'rasn.jpg';
				break;
			case 'Rain Showers':
			case 'Light Rain Showers':
			case 'Light Rain and Breezy':
			case 'Heavy Rain Showers':
			case 'Rain Showers in Vicinity':
			case 'Light Showers Rain':
			case 'Heavy Showers Rain':
			case 'Showers Rain':
			case 'Showers Rain in Vicinity':
			case 'Rain Showers Fog/Mist':
			case 'Light Rain Showers Fog/Mist':
			case 'Heavy Rain Showers Fog/Mist':
			case 'Rain Showers in Vicinity Fog/Mist':
			case 'Light Showers Rain Fog/Mist':
			case 'Heavy Showers Rain Fog/Mist':
			case 'Showers Rain Fog/Mist':
			case 'Showers Rain in Vicinity Fog/Mist':
			   return 'shra.jpg';
			   break;
			case 'Thunderstorm':
			case 'Thunderstorm Rain':
			case 'Light Thunderstorm Rain':
			case 'Heavy Thunderstorm Rain':
			case 'Thunderstorm Rain Fog/Mist':
			case 'Light Thunderstorm Rain Fog/Mist':
			case 'Heavy Thunderstorm Rain Fog and Windy':
			case 'Heavy Thunderstorm Rain Fog/Mist':
			case 'Thunderstorm Showers in Vicinity':
			case 'Light Thunderstorm Rain Haze':
			case 'Heavy Thunderstorm Rain Haze':
			case 'Thunderstorm Fog':
			case 'Light Thunderstorm Rain Fog':
			case 'Heavy Thunderstorm Rain Fog':
			case 'Thunderstorm Light Rain':
			case 'Thunderstorm Heavy Rain':
			case 'Thunderstorm Rain Fog/Mist':
			case 'Thunderstorm Light Rain Fog/Mist':
			case 'Thunderstorm Heavy Rain Fog/Mist':
			case 'Thunderstorm in Vicinity Fog/Mist':
			case 'Thunderstorm Showers in Vicinity':
			case 'Thunderstorm in Vicinity Haze':
			case 'Thunderstorm Haze in Vicinity':
			case 'Thunderstorm Light Rain Haze':
			case 'Thunderstorm Heavy Rain Haze':
			case 'Thunderstorm Fog':
			case 'Thunderstorm Light Rain Fog':
			case 'Thunderstorm Heavy Rain Fog':
			case 'Thunderstorm Hail':
			case 'Light Thunderstorm Rain Hail':
			case 'Heavy Thunderstorm Rain Hail':
			case 'Thunderstorm Rain Hail Fog/Mist':
			case 'Light Thunderstorm Rain Hail Fog/Mist':
			case 'Heavy Thunderstorm Rain Hail Fog/Hail':
			case 'Thunderstorm Showers in Vicinity Hail':
			case 'Light Thunderstorm Rain Hail Haze':
			case 'Heavy Thunderstorm Rain Hail Haze':
			case 'Thunderstorm Hail Fog':
			case 'Light Thunderstorm Rain Hail Fog':
			case 'Heavy Thunderstorm Rain Hail Fog':
			case 'Thunderstorm Light Rain Hail':
			case 'Thunderstorm Heavy Rain Hail':
			case 'Thunderstorm Rain Hail Fog/Mist':
			case 'Thunderstorm Light Rain Hail Fog/Mist':
			case 'Thunderstorm Heavy Rain Hail Fog/Mist':
			case 'Thunderstorm in Vicinity Hail':
			case 'Thunderstorm in Vicinity Hail Haze':
			case 'Thunderstorm Haze in Vicinity Hail':
			case 'Thunderstorm Light Rain Hail Haze':
			case 'Thunderstorm Heavy Rain Hail Haze':
			case 'Thunderstorm Hail Fog':
			case 'Thunderstorm Light Rain Hail Fog':
			case 'Thunderstorm Heavy Rain Hail Fog':
			case 'Thunderstorm Small Hail/Snow Pellets':
			case 'Thunderstorm Rain Small Hail/Snow Pellets':
			case 'Light Thunderstorm Rain Small Hail/Snow Pellets':
			case 'Heavy Thunderstorm Rain Small Hail/Snow Pellets':
			   return 'tsra.jpg';
			   break;
			case 'Snow':
			case 'Light Snow':
			case 'Heavy Snow':
			case 'Snow Showers':
			case 'Light Snow Showers':
			case 'Heavy Snow Showers':
			case 'Showers Snow':
			case 'Light Showers Snow':
			case 'Heavy Showers Snow':
			case 'Snow Fog/Mist':
			case 'Light Snow Fog/Mist':
			case 'Heavy Snow Fog/Mist':
			case 'Snow Showers Fog/Mist':
			case 'Light Snow Showers Fog/Mist':
			case 'Heavy Snow Showers Fog/Mist':
			case 'Showers Snow Fog/Mist':
			case 'Light Showers Snow Fog/Mist':
			case 'Heavy Showers Snow Fog/Mist':
			case 'Snow Fog':
			case 'Light Snow Fog':
			case 'Heavy Snow Fog':
			case 'Snow Showers Fog':
			case 'Light Snow Showers Fog':
			case 'Heavy Snow Showers Fog':
			case 'Showers Snow Fog':
			case 'Light Showers Snow Fog':
			case 'Heavy Showers Snow Fog':
			case 'Showers in Vicinity Snow':
			case 'Snow Showers in Vicinity':
			case 'Snow Showers in Vicinity Fog/Mist':
			case 'Snow Showers in Vicinity Fog':
			case 'Low Drifting Snow':
			case 'Blowing Snow':
			case 'Snow Low Drifting Snow':
			case 'Snow Blowing Snow':
			case 'Light Snow Low Drifting Snow':
			case 'Light Snow Blowing Snow':
			case 'Light Snow Blowing Snow Fog/Mist':
			case 'Heavy Snow Low Drifting Snow':
			case 'Heavy Snow Blowing Snow':
			case 'Thunderstorm Snow':
			case 'Light Thunderstorm Snow':
			case 'Heavy Thunderstorm Snow':
			case 'Snow Grains':
			case 'Light Snow Grains':
			case 'Heavy Snow Grains':
			case 'Heavy Blowing Snow':
			case 'Blowing Snow in Vicinity':
			   return 'sn.jpg' ;
			   break;
			case 'Windy':
			case 'Breezy':
			case 'Fair and Windy':
			case 'A Few Clouds and Windy':
			case 'Partly Cloudy and Windy':
			case 'Mostly Cloudy and Windy':
			case 'Overcast and Windy':
			   return  'wind.jpg';
			   break;
			case 'Showers in Vicinity':
			case 'Showers in Vicinity Fog/Mist':
			case 'Showers in Vicinity Fog':
			case 'Showers in Vicinity Haze':
			   return 'hi_shwrs.jpg';
			   break;
			case 'Freezing Rain Rain':
			case 'Light Freezing Rain Rain':
			case 'Heavy Freezing Rain Rain':
			case 'Rain Freezing Rain':
			case 'Light Rain Freezing Rain':
			case 'Heavy Rain Freezing Rain':
			case 'Freezing Drizzle Rain':
			case 'Light Freezing Drizzle Rain':
			case 'Heavy Freezing Drizzle Rain':
			case 'Rain Freezing Drizzle':
			case 'Light Rain Freezing Drizzle':
			case 'Heavy Rain Freezing Drizzle':
				return 'fzrara.jpg';
				break;
			case 'Thunderstorm in Vicinity':
			case 'Thunderstorm in Vicinity Fog':
			case 'Thunderstorm in Vicinity Haze':
				return 'hi_tsra.jpg';
				break;
			case 'Light Rain':
			case 'Drizzle':
			case 'Light Drizzle':
			case 'Heavy Drizzle':
			case 'Light Rain Fog/Mist':
			case 'Drizzle Fog/Mist' :
			case 'Light Drizzle Fog/Mist':
			case 'Heavy Drizzle Fog/Mist':
			case 'Light Rain Fog':
			case 'Drizzle Fog':
			case 'Light Drizzle Fog':
			case 'Heavy Drizzle Fog':
				return 'ra1.jpg';
				break;
			case  'Rain':
			case 'Heavy Rain':
			case 'Rain Fog/Mist':
			case 'Heavy Rain Fog/Mist':
			case 'Rain Fog':
			case 'Heavy Rain Fog':
			   return 'ra.jpg';
			   break;
			case 'Funnel Cloud':
			case 'Funnel Cloud in Vicinity':
			case 'Tornado/Water Spout':
			   return 'nsvrtsra.jpg';
			   break;
			case 'Dust':
			case 'Low Drifting Dust':
			case 'Blowing Dust':
			case 'Sand':
			case 'Blowing Sand':
			case 'Low Drifting Sand':
			case 'Dust/Sand Whirls':
			case 'Dust/Sand Whirls in Vicinity':
			case 'Dust Storm':
			case 'Heavy Dust Storm':
			case 'Dust Storm in Vicinity':
			case 'Sand Storm':
			case 'Heavy Sand Storm':
			case 'Sand Storm in Vicinity':
			   return 'dust.jpg';
			   break;
			case 'Haze':
			   return 'mist.jpg';
			   break;

		} // end of switch
	}   // end of fucnction

}





?>