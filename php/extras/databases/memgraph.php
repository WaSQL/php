<?php
/*
	memgraph.php - a collection of Memgraph Database functions for use by WaSQL.

	Memgraph is a graph database that uses Cypher query language and Bolt protocol.

	References:
		https://memgraph.com/docs
		https://github.com/laudis-technologies/neo4j-php-client

	Installation:
		composer require laudis/neo4j-php-client

	Configuration in config.xml:
		<memgraph_dbhost>localhost</memgraph_dbhost>
		<memgraph_dbport>7687</memgraph_dbport>
		<memgraph_dbname>memgraph</memgraph_dbname>
		<memgraph_dbuser>memgraph</memgraph_dbuser>
		<memgraph_dbpass>memgraph</memgraph_dbpass>
*/

// Load Composer autoloader
require_once(__DIR__ . '/../../../vendor/autoload.php');

use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Databags\Statement;

//---------- begin function memgraphDBConnect ----------
/**
* @describe returns connection client
* @param $params array - These can also be set in the CONFIG file with dbname_memgraph,dbuser_memgraph, and dbpass_memgraph
*	[-host] - memgraph server to connect to
* 	[-dbname] - name of database (usually 'memgraph')
* 	[-dbuser] - username (optional)
* 	[-dbpass] - password (optional)
*	[-port] - port (default 7687)
* @return connection client and sets the global $dbh_memgraph variable.
* @usage $dbh_memgraph=memgraphDBConnect($params);
*/
function memgraphDBConnect($params=array()){
	$params=memgraphParseConnectParams($params);
	global $dbh_memgraph;
	if(is_object($dbh_memgraph)){return $dbh_memgraph;}

	try{
		// Build connection URL
		//$url = "bolt://{$params['-dbhost']}:{$params['-dbport']}";
		$url = sprintf('bolt://%s:%d', $params['-dbhost'], $params['-dbport']);
		// Debug: Show what we're connecting with

		// Create client builder - use empty create() to avoid defaults
		$builder = ClientBuilder::create();
		// Add driver with authentication if credentials provided
		// Use 'memgraph' as the alias and set it as default
		if(!empty($params['-dbuser']) && !empty($params['-dbpass'])){
			//echo "Using authentication: {$params['-dbuser']}<br>";
			$builder = $builder->withDriver('memgraph', $url,
				\Laudis\Neo4j\Authentication\Authenticate::basic(
					$params['-dbuser'],
					$params['-dbpass']
				)
			);
		}
		else{
			//echo "No authentication (empty user/pass)<br>";
			$builder = $builder->withDriver('memgraph', $url);
		}

		// Set memgraph as the default driver
		$builder = $builder->withDefaultDriver('memgraph');

		$dbh_memgraph = $builder->build();

		// Debug: Check what's in the built client
		//echo "Client built successfully<br>";
		//echo "Client class: " . get_class($dbh_memgraph) . "<br>";

		// Try to get the configuration from the client
		if (method_exists($dbh_memgraph, 'getDriver')) {
			//echo "Client has getDriver method<br>";
		}

		//echo "<hr>";

		return $dbh_memgraph;
	}
	catch (Exception $e) {
		$params['-dbpass']=preg_replace('/[a-z0-9]/i','*',$params['-dbpass']);
		debugValue("memgraphDBConnect error: " . $e->getMessage() . printValue($params));
		return null;
	}
}

//---------- begin function memgraphQueryResults ----------
/**
* @describe returns the memgraph record set from a Cypher query
* @param query string - Cypher query to execute
* @param [$params] array - parameters for the query
* @return array - returns records
* @usage
*	$recs=memgraphQueryResults("MATCH (n:Person) RETURN n LIMIT 10");
*	$recs=memgraphQueryResults("MATCH (n:Person {name: \$name}) RETURN n", array('name'=>'John'));
*/
function memgraphQueryResults($query='',$params=array()){
	if(!commonStrlen($query)){return null;}

	// Debug logging
	global $_SESSION;
	if(isset($params['-logfile']) && !empty($params['-logfile'])){
		$logfile = $params['-logfile'];
		$logdata = "=== memgraphQueryResults Debug ===\n";
		$logdata .= "Query received: " . var_export($query, true) . "\n";
		$logdata .= "Query type: " . gettype($query) . "\n";
		$logdata .= "Is numeric: " . (is_numeric($query) ? 'YES' : 'NO') . "\n";
		$logdata .= "Params keys: " . implode(', ', array_keys($params)) . "\n";
		if(isset($params['-query'])){
			$logdata .= "Params[-query]: " . var_export($params['-query'], true) . "\n";
		}
		if(isset($_SESSION['sql_last'])){
			$logdata .= "SESSION[sql_last]: " . var_export($_SESSION['sql_last'], true) . "\n";
		}
		file_put_contents($logfile, $logdata, FILE_APPEND);
	}

	// Safety check: if query is just a number, it's invalid
	if(is_numeric($query)){
		debugValue("memgraphQueryResults: Invalid query (numeric value): {$query}");
		return array();
	}

	// Trim and validate query starts with valid Cypher keyword
	$query = trim($query);
	if(!preg_match('/^(drop|match|create|merge|return|with|call|unwind|optional|show|explain|profile)/i', $query)){
		debugValue("memgraphQueryResults: Invalid query format: {$query}");
		return array();
	}

	global $dbh_memgraph;

	// Only connect if we don't already have a connection
	if(!is_object($dbh_memgraph)){
		$dbh_memgraph=memgraphDBConnect($params);
		if(!is_object($dbh_memgraph)){
			debugValue("memgraphQueryResults: Failed to connect");
			return array();
		}
	}

	try{
		// Separate query params from connection params
		$queryParams = array();
		foreach($params as $k=>$v){
			if(!stringBeginsWith($k,'-')){
				$queryParams[$k]=$v;
			}
		}

		// Execute query
		if(count($queryParams)){
			$results = $dbh_memgraph->run($query, $queryParams);
		}
		else{
			$results = $dbh_memgraph->run($query);
		}

		// Convert results to array format
		$recs = array();
		foreach($results as $result){
			$rec = array();
			foreach($result->keys() as $key){
				$value = $result->get($key);
				// Handle Node objects
				if(is_object($value) && method_exists($value, 'getProperties')){
					$rec[$key] = $value->getProperties();
				}
				// Handle arrays and primitives
				else{
					$rec[$key] = $value;
				}
			}
			$recs[] = $rec;
		}

		// If -filename is specified, write results to CSV and return count
		if(isset($params['-filename']) && !empty($params['-filename'])){
			$filename = $params['-filename'];

			// Debug: log what we got
			if(isset($params['-logfile']) && !empty($params['-logfile'])){
				$logfile = $params['-logfile'];
				$logdata = "Query: {$query}\n";
				$logdata .= "Record count: " . count($recs) . "\n";
				$logdata .= "Sample record structure: " . print_r($recs[0] ?? array(), true) . "\n";
				file_put_contents($logfile, $logdata);
			}

			// Flatten nested arrays (node properties) for CSV export
			$flatRecs = array();
			foreach($recs as $rec){
				$flatRec = array();
				foreach($rec as $key => $value){
					if(is_array($value)){
						// If value is an array (node properties), merge them at top level
						foreach($value as $subKey => $subValue){
							// Handle nested values
							if(is_array($subValue)){
								$flatRec[$subKey] = json_encode($subValue);
							}
							elseif(is_object($subValue)){
								// Handle Neo4j/Memgraph objects
								if(method_exists($subValue, 'toArray')){
									$flatRec[$subKey] = json_encode($subValue->toArray());
								}
								elseif($subValue instanceof \Iterator || $subValue instanceof \IteratorAggregate){
									$flatRec[$subKey] = json_encode(iterator_to_array($subValue));
								}
								else{
									$flatRec[$subKey] = json_encode($subValue);
								}
							}
							else{
								$flatRec[$subKey] = $subValue;
							}
						}
					}
					elseif(is_object($value)){
						// Handle Neo4j/Memgraph objects
						if(method_exists($value, 'toArray')){
							// CypherMap and similar objects have toArray()
							$arrayValue = $value->toArray();
							foreach($arrayValue as $subKey => $subValue){
								if(is_object($subValue)){
									$flatRec[$subKey] = json_encode($subValue);
								}
								elseif(is_array($subValue)){
									$flatRec[$subKey] = json_encode($subValue);
								}
								else{
									$flatRec[$subKey] = $subValue;
								}
							}
						}
						elseif($value instanceof \Iterator || $value instanceof \IteratorAggregate){
							// Handle iterable objects
							$arrayValue = iterator_to_array($value);
							foreach($arrayValue as $subKey => $subValue){
								if(is_object($subValue) || is_array($subValue)){
									$flatRec[$subKey] = json_encode($subValue);
								}
								else{
									$flatRec[$subKey] = $subValue;
								}
							}
						}
						else{
							// Last resort - JSON encode the object
							$flatRec[$key] = json_encode($value);
						}
					}
					else{
						$flatRec[$key] = $value;
					}
				}
				if(count($flatRec) > 0){
					$flatRecs[] = $flatRec;
				}
			}

			// If still no flat records but we have recs, try simple conversion
			if(count($flatRecs) == 0 && count($recs) > 0){
				$flatRecs = $recs;
			}

			// Convert to CSV
			if(function_exists('arrays2CSV')){
				$csv = arrays2CSV($flatRecs);
			}
			else{
				// Fallback CSV generation
				$csv = '';
				if(count($flatRecs) > 0){
					// Header row
					$csv .= implode(',', array_keys($flatRecs[0])) . "\n";
					// Data rows
					foreach($flatRecs as $row){
						$csv .= implode(',', array_map(function($v){
							return '"' . str_replace('"', '""', $v) . '"';
						}, $row)) . "\n";
					}
				}
			}

			// Write to file
			$ok = file_put_contents($filename, $csv);
			if($ok === false){
				debugValue("memgraphQueryResults: Failed to write to file {$filename}");
				return 0;
			}

			// Return count of records written
			return count($flatRecs);
		}

		return $recs;
	}
	catch (Exception $e) {
		$error = "memgraphQueryResults error: " . $e->getMessage() . ", Query: " . $query;
		echo $error . "<br>\n";
		debugValue($error);
		return array();
	}
}

//---------- begin function memgraphExecuteSQL ----------
/**
* @describe executes a Cypher query and returns without parsing the results
* @param $query string - Cypher query to execute
* @param [$params] array - optional connection parameters
* @return boolean returns true if query succeeded
* @usage $ok=memgraphExecuteSQL("CREATE INDEX ON :Person(name)");
*/
function memgraphExecuteSQL($query, $params=array()){
	if(!commonStrlen($query)){return 0;}

	global $dbh_memgraph;
	if(!is_object($dbh_memgraph)){
		$dbh_memgraph=memgraphDBConnect($params);
		if(!is_object($dbh_memgraph)){
			debugValue("memgraphExecuteSQL: Failed to connect");
			return false;
		}
	}

	try{
		$dbh_memgraph->run($query);
		return true;
	}
	catch (Exception $e) {
		debugValue("memgraphExecuteSQL error: " . $e->getMessage() . ", Query: " . $query);
		return false;
	}
}

//---------- begin function memgraphGetDBRecords ----------
/**
* @describe returns an array of records from a node label
* @param params array - requires either -label or a raw Cypher query
*	[-label] string - node label (like a table name)
*	[-limit] mixed - query record limit
*	[-offset] mixed - query offset limit
*	[-where] string - WHERE clause conditions
*	[-order] string - ORDER BY clause
* @return array - set of records
* @usage
*	memgraphGetDBRecords(array('-label'=>'Person'));
*	memgraphGetDBRecords("MATCH (n:Person) WHERE n.age > 25 RETURN n");
*/
function memgraphGetDBRecords($params){
	global $CONFIG;

	// If params is a string, treat it as a raw query
	if(!is_array($params)){
		$params=trim($params);
		// Skip error messages that might have been passed through
		if(stringBeginsWith($params, 'ERROR') || stringBeginsWith($params, 'Error')){
			return array();
		}
		// Check for any Cypher keyword at start
		if(preg_match('/^(match|create|merge|return|with|call|unwind|optional|show)/i',$params)){
			return memgraphQueryResults($params);
		}
		// If it's a string but doesn't match Cypher, return empty array silently
		return array();
	}

	// Determine the query to execute
	$query = '';
	if(isset($params['-query'])){
		$query = $params['-query'];
	}
	elseif(!empty($params['-label'])){
		// Build Cypher query from label
		$label = $params['-label'];
		$query = "MATCH (n:{$label})";

		// Add WHERE clause if specified
		if(!empty($params['-where'])){
			$query .= " WHERE {$params['-where']}";
		}

		$query .= " RETURN n";

		// Add ORDER BY if specified
		if(!empty($params['-order'])){
			$query .= " ORDER BY {$params['-order']}";
		}

		// Add LIMIT and SKIP
		if(!isset($params['-nolimit'])){
			$limit = isset($params['-limit']) ? $params['-limit'] : (isset($CONFIG['paging']) ? $CONFIG['paging'] : 25);
			$skip = isset($params['-offset']) ? $params['-offset'] : 0;

			if($skip > 0){
				$query .= " SKIP {$skip}";
			}
			$query .= " LIMIT {$limit}";
		}
	}
	else{
		// No query and no label specified
		return array();
	}

	// Execute the query
	$recs = memgraphQueryResults($query, $params);

	// If -filename is specified, write results to CSV and return count
	if(isset($params['-filename']) && !empty($params['-filename'])){
		$filename = $params['-filename'];

		// Flatten nested arrays (node properties) for CSV export
		$flatRecs = array();
		foreach($recs as $rec){
			$flatRec = array();
			foreach($rec as $key => $value){
				if(is_array($value)){
					// If value is an array (node properties), merge them
					foreach($value as $subKey => $subValue){
						if(!is_array($subValue) && !is_object($subValue)){
							$flatRec[$subKey] = $subValue;
						}
					}
				}
				elseif(!is_object($value)){
					$flatRec[$key] = $value;
				}
			}
			$flatRecs[] = $flatRec;
		}

		// Convert to CSV
		if(function_exists('arrays2CSV')){
			$csv = arrays2CSV($flatRecs);
		}
		else{
			// Fallback CSV generation
			$csv = '';
			if(count($flatRecs) > 0){
				// Header row
				$csv .= implode(',', array_keys($flatRecs[0])) . "\n";
				// Data rows
				foreach($flatRecs as $row){
					$csv .= implode(',', array_map(function($v){
						return '"' . str_replace('"', '""', $v) . '"';
					}, $row)) . "\n";
				}
			}
		}

		// Write to file
		$ok = file_put_contents($filename, $csv);
		if($ok === false){
			debugValue("memgraphGetDBRecords: Failed to write to file {$filename}");
			return 0;
		}

		// Return count of records written
		return count($flatRecs);
	}

	return $recs;
}

//---------- begin function memgraphGetDBRecord ----------
/**
* @describe retrieves a single record from Memgraph based on params
* @param $params array
* 	-label - node label to query
* @return array record
* @usage $rec=memgraphGetDBRecord(array('-label'=>'Person', '-where'=>'n.name="John"'));
*/
function memgraphGetDBRecord($params=array()){
	$params['-limit'] = 1;
	$recs = memgraphGetDBRecords($params);
	if(isset($recs[0])){return $recs[0];}
	return null;
}

//---------- begin function memgraphGetDBCount ----------
/**
* @describe returns a record count based on params
* @param params array - requires -label
*	-label string - node label
*	[-where] string - WHERE clause
* @return integer count
* @usage $cnt=memgraphGetDBCount(array('-label'=>'Person'));
*/
function memgraphGetDBCount($params=array()){
	if(!isset($params['-label'])){return null;}

	$label = $params['-label'];
	$query = "MATCH (n:{$label})";

	if(!empty($params['-where'])){
		$query .= " WHERE {$params['-where']}";
	}

	$query .= " RETURN count(n) as cnt";

	$recs = memgraphQueryResults($query, $params);

	if(!isset($recs[0]['cnt'])){
		return 0;
	}
	return $recs[0]['cnt'];
}

//---------- begin function memgraphGetDBLabels ----------
/**
* @describe returns an array of node labels (similar to tables)
* @param [$params] array - optional connection parameters
* @return array returns array of labels
* @usage $labels=memgraphGetDBLabels();
*/
function memgraphGetDBLabels($params=array()){
	global $dbh_memgraph;
	// Get all distinct labels from nodes
	$query = "
		MATCH (n)
		WITH DISTINCT labels(n) as labelList
		UNWIND labelList as label
		RETURN DISTINCT label
		ORDER BY label
	";
	$recs = memgraphQueryResults($query, $params);

	$labels = array();
	foreach($recs as $rec){
		if(isset($rec['label'])){
			$labels[] = $rec['label'];
		}
	}
	return $labels;
}

// Alias for compatibility with WaSQL naming convention
function memgraphGetDBTables(){
	return memgraphGetDBLabels();
}

//---------- begin function memgraphGetDBNodeProperties ----------
/**
* @describe returns properties of nodes with a given label
* @param label string - node label
* @return array of property names
* @usage $props=memgraphGetDBNodeProperties('Person');
*/
function memgraphGetDBNodeProperties($label){
	$query = <<<ENDOFQUERY
MATCH (n:{$label})
  WITH n
  LIMIT 1000
  UNWIND keys(n) as prop
  RETURN DISTINCT prop
  ORDER BY prop
ENDOFQUERY;
	$recs = memgraphQueryResults($query);
	$properties = array();
	foreach($recs as $rec){
		if(!in_array($rec['prop'], $properties)){
			$properties[] = $rec['prop'];
		}
	}
	sort($properties);
	return $properties;
}

// Alias for compatibility with WaSQL naming convention
function memgraphGetDBFields($label){
	$query=<<<ENDOFQUERY
MATCH (n:{$label})
  WITH n
  LIMIT 2000
  UNWIND keys(n) as propKey
  WITH propKey, n[propKey] as propValue
  RETURN DISTINCT
      propKey as property,
      CASE
          WHEN propValue IS NULL THEN 'null'
          WHEN propValue = true OR propValue = false THEN 'boolean'
          WHEN toInteger(propValue) = propValue THEN 'integer'
          WHEN toFloat(propValue) = propValue THEN 'float'
          ELSE 'string'
      END as datatype
  ORDER BY property
ENDOFQUERY;
	$recs = memgraphQueryResults($query);
	return $recs;
	//return memgraphGetDBNodeProperties($label);
}

//---------- begin function memgraphGetDBFieldInfo ----------
/**
* @describe returns field info for a given label in WaSQL format
* @param label string - node label
* @return array of field info arrays
* @usage $fields=memgraphGetDBFieldInfo('Person');
*/
function memgraphGetDBFieldInfo($label){
	$query=<<<ENDOFQUERY
MATCH (n:{$label})
  WITH n
  LIMIT 2000
  UNWIND keys(n) as propKey
  WITH propKey, n[propKey] as propValue
  RETURN DISTINCT
      propKey as property,
      CASE
          WHEN propValue IS NULL THEN 'null'
          WHEN propValue = true OR propValue = false THEN 'boolean'
          WHEN toInteger(propValue) = propValue THEN 'integer'
          WHEN toFloat(propValue) = propValue THEN 'float'
          ELSE 'string'
      END as datatype
  ORDER BY property
ENDOFQUERY;
	$recs = memgraphQueryResults($query);
	$fieldInfo = array();
	foreach($recs as $rec){
		$prop=$rec['property'];
		$type=$rec['datatype'];
		$fieldInfo[$prop] = array(
			'_dbfield' => $prop,
			'name' => $prop,
			'_dbtype' => $type,
			'_dbtype_ex' => $type,
			'type' => $type
		);
	}
	
	return $fieldInfo;
}

// Alias for compatibility with WaSQL naming convention
function memgraphGetDBTableFields($label){
	return memgraphGetDBFieldInfo($label);
}

//---------- begin function memgraphCreateNode ----------
/**
* @describe creates a new node in Memgraph
* @param params array
*	-label string - node label (required)
*	-properties array - properties to set on the node
* @return mixed - node ID on success, error message on failure
* @usage $id=memgraphCreateNode(array('-label'=>'Person', '-properties'=>array('name'=>'John', 'age'=>30)));
*/
function memgraphCreateNode($params=array()){
	if(!isset($params['-label'])){
		return "memgraphCreateNode: No label specified";
	}

	$label = $params['-label'];
	$props = isset($params['-properties']) ? $params['-properties'] : array();

	// Build property string
	$propParts = array();
	$queryParams = array();
	foreach($props as $key => $value){
		$propParts[] = "{$key}: \${$key}";
		$queryParams[$key] = $value;
	}
	$propStr = implode(', ', $propParts);

	$query = "CREATE (n:{$label} {{$propStr}}) RETURN id(n) as node_id";

	// Debug output
	echo "Debug: Query = {$query}<br>\n";
	echo "Debug: Params = <pre>" . print_r($queryParams, true) . "</pre><br>\n";

	$recs = memgraphQueryResults($query, array_merge($params, $queryParams));

	echo "Debug: Results = <pre>" . print_r($recs, true) . "</pre><br>\n";

	if(isset($recs[0]['node_id'])){
		return $recs[0]['node_id'];
	}

	return "memgraphCreateNode: Failed to create node";
}

//---------- begin function memgraphUpdateNode ----------
/**
* @describe updates a node in Memgraph
* @param params array
*	-id integer - node ID (required)
*	-properties array - properties to update
* @return boolean
* @usage $ok=memgraphUpdateNode(array('-id'=>123, '-properties'=>array('age'=>31)));
*/
function memgraphUpdateNode($params=array()){
	if(!isset($params['-id'])){
		return false;
	}

	$id = $params['-id'];
	$props = isset($params['-properties']) ? $params['-properties'] : array();

	// Build SET clause
	$setParts = array();
	foreach($props as $key => $value){
		$setParts[] = "n.{$key} = \${$key}";
	}
	$setStr = implode(', ', $setParts);

	$query = "MATCH (n) WHERE id(n) = {$id} SET {$setStr} RETURN n";

	$recs = memgraphQueryResults($query, $props);

	return count($recs) > 0;
}

//---------- begin function memgraphDeleteNode ----------
/**
* @describe deletes a node from Memgraph
* @param params array
*	-id integer - node ID (required)
*	[-detach] boolean - if true, also deletes relationships (default true)
* @return boolean
* @usage $ok=memgraphDeleteNode(array('-id'=>123));
*/
function memgraphDeleteNode($params=array()){
	if(!isset($params['-id'])){
		return false;
	}

	$id = $params['-id'];
	$detach = isset($params['-detach']) ? $params['-detach'] : true;

	$deleteClause = $detach ? "DETACH DELETE n" : "DELETE n";
	$query = "MATCH (n) WHERE id(n) = {$id} {$deleteClause}";

	return memgraphExecuteSQL($query);
}

//---------- begin function memgraphCreateRelationship ----------
/**
* @describe creates a relationship between two nodes
* @param params array
*	-from integer - source node ID (required)
*	-to integer - target node ID (required)
*	-type string - relationship type (required)
*	[-properties] array - properties to set on the relationship
* @return boolean
* @usage $ok=memgraphCreateRelationship(array('-from'=>1, '-to'=>2, '-type'=>'KNOWS', '-properties'=>array('since'=>2020)));
*/
function memgraphCreateRelationship($params=array()){
	if(!isset($params['-from']) || !isset($params['-to']) || !isset($params['-type'])){
		return false;
	}

	$from = $params['-from'];
	$to = $params['-to'];
	$type = $params['-type'];
	$props = isset($params['-properties']) ? $params['-properties'] : array();

	// Build property string
	$propStr = '';
	if(count($props)){
		$propParts = array();
		foreach($props as $key => $value){
			$propParts[] = "{$key}: \${$key}";
		}
		$propStr = ' {' . implode(', ', $propParts) . '}';
	}

	$query = "MATCH (a), (b) WHERE id(a) = {$from} AND id(b) = {$to} CREATE (a)-[r:{$type}{$propStr}]->(b) RETURN r";

	$recs = memgraphQueryResults($query, $props);

	return count($recs) > 0;
}

//---------- begin function memgraphParseConnectParams ----------
/**
* @describe parses the params array and checks in the CONFIG if missing
* @param [$params] array - These can also be set in the CONFIG file with dbname_memgraph,dbuser_memgraph, and dbpass_memgraph
*	[-host] - memgraph server to connect to
* 	[-dbname] - name of database
* 	[-dbuser] - username (optional for Memgraph)
* 	[-dbpass] - password (optional for Memgraph)
*	[-port] - port (default 7687)
* @return $params array
* @usage $params=memgraphParseConnectParams($params);
*/
function memgraphParseConnectParams($params=array()){
	global $CONFIG;
	global $DATABASE;
	global $USER;
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']])){
		foreach($CONFIG as $k=>$v){
			if(preg_match('/^memgraph/i',$k)){unset($CONFIG[$k]);}
		}
		foreach($DATABASE[$CONFIG['db']] as $k=>$v){
			$params["-{$k}"]=$v;
		}
	}
	//check for user specific
	if(isUser() && strlen($USER['username'])){
		foreach($params as $k=>$v){
			if(stringEndsWith($k,"_{$USER['username']}")){
				$nk=str_replace("_{$USER['username']}",'',$k);
				unset($params[$k]);
				$params[$nk]=$v;
			}
		}
	}
	//dbname
	if(!isset($params['-dbname'])){
		if(isset($CONFIG['dbname_memgraph'])){
			$params['-dbname']=$CONFIG['dbname_memgraph'];
			$params['-dbname_source']="CONFIG dbname_memgraph";
		}
		elseif(isset($CONFIG['memgraph_dbname'])){
			$params['-dbname']=$CONFIG['memgraph_dbname'];
			$params['-dbname_source']="CONFIG memgraph_dbname";
		}
		elseif(isset($CONFIG['dbname'])){
			$params['-dbname']=$CONFIG['dbname'];
			$params['-dbname_source']="CONFIG dbname";
		}
		else{return 'memgraphParseConnectParams Error: No dbname set'.printValue($CONFIG);}
	}
	else{
		$params['-dbname_source']="passed in";
	}
	//readonly
	if(!isset($params['-memgraph_readonly']) && isset($CONFIG['memgraph_readonly'])){
		$params['-readonly']=$CONFIG['memgraph_readonly'];
	}
	//dbmode
	if(!isset($params['-dbmode'])){
		if(isset($CONFIG['dbmode_memgraph'])){
			$params['-dbmode']=$CONFIG['dbmode_memgraph'];
			$params['-dbmode_source']="CONFIG dbname_memgraph";
		}
		elseif(isset($CONFIG['memgraph_dbmode'])){
			$params['-dbmode']=$CONFIG['memgraph_dbmode'];
			$params['-dbmode_source']="CONFIG memgraph_dbname";
		}
	}
	else{
		$params['-dbmode_source']="passed in";
	}
	//dbhost
	if(!isset($params['-dbhost'])){
		if(isset($CONFIG['dbhost_memgraph'])){
			$params['-dbhost']=$CONFIG['dbhost_memgraph'];
		}
		elseif(isset($CONFIG['memgraph_dbhost'])){
			$params['-dbhost']=$CONFIG['memgraph_dbhost'];
		}
		elseif(isset($CONFIG['dbhost'])){
			$params['-dbhost']=$CONFIG['dbhost'];
		}
		else{
			$params['-dbhost']='localhost';
		}
	}
	//dbport
	if(!isset($params['-dbport']) || $params['-dbport'] == 0){
		if(isset($CONFIG['dbport_memgraph'])){
			$params['-dbport']=$CONFIG['dbport_memgraph'];
		}
		elseif(isset($CONFIG['memgraph_dbport'])){
			$params['-dbport']=$CONFIG['memgraph_dbport'];
		}
		elseif(isset($CONFIG['dbport']) && $CONFIG['dbport'] != 0){
			$params['-dbport']=$CONFIG['dbport'];
		}
		else{
			$params['-dbport']=7687; // Memgraph default Bolt port
		}
	}
	//dbuser
	if(!isset($params['-dbuser'])){
		if(isset($CONFIG['dbuser_memgraph'])){
			$params['-dbuser']=$CONFIG['dbuser_memgraph'];
		}
		elseif(isset($CONFIG['memgraph_dbuser'])){
			$params['-dbuser']=$CONFIG['memgraph_dbuser'];
		}
		elseif(isset($CONFIG['dbuser'])){
			$params['-dbuser']=$CONFIG['dbuser'];
		}
		else{
			$params['-dbuser']='';
		}
	}
	//dbpass
	if(!isset($params['-dbpass'])){
		if(isset($CONFIG['dbpass_memgraph'])){
			$params['-dbpass']=$CONFIG['dbpass_memgraph'];
		}
		elseif(isset($CONFIG['memgraph_dbpass'])){
			$params['-dbpass']=$CONFIG['memgraph_dbpass'];
		}
		elseif(isset($CONFIG['dbpass'])){
			$params['-dbpass']=$CONFIG['dbpass'];
		}
		else{
			$params['-dbpass']='';
		}
	}
	return $params;
}

//---------- begin function memgraphListRecords ----------
/**
* @describe returns an html table of records from a memgraph database. refer to databaseListRecords
*/
function memgraphListRecords($params=array()){
	$params['-database']='memgraph';
	return databaseListRecords($params);
}

//---------- begin function memgraphGetRelationshipTypes ----------
/**
* @describe returns all relationship types in the database
* @return array
* @usage $types=memgraphGetRelationshipTypes();
*/
function memgraphGetRelationshipTypes(){
	// Get all distinct relationship types
	$query = "
		MATCH ()-[r]->()
		RETURN DISTINCT type(r) as type
		ORDER BY type
	";
	$recs = memgraphQueryResults($query);

	$types = array();
	foreach($recs as $rec){
		if(isset($rec['type'])){
			$types[] = $rec['type'];
		}
	}
	return $types;
}

//---------- begin function memgraphGetStats ----------
/**
* @describe returns database statistics
* @return array
* @usage $stats=memgraphGetStats();
*/
function memgraphGetStats(){
	$stats = array();

	// Get node count
	$nodeQuery = "MATCH (n) RETURN count(n) as node_count";
	$nodeRecs = memgraphQueryResults($nodeQuery);
	$stats['node_count'] = isset($nodeRecs[0]['node_count']) ? $nodeRecs[0]['node_count'] : 0;

	// Get relationship count
	$relQuery = "MATCH ()-[r]->() RETURN count(r) as rel_count";
	$relRecs = memgraphQueryResults($relQuery);
	$stats['relationship_count'] = isset($relRecs[0]['rel_count']) ? $relRecs[0]['rel_count'] : 0;

	// Get labels
	$stats['labels'] = memgraphGetDBLabels();
	$stats['label_count'] = count($stats['labels']);

	// Get relationship types
	$stats['relationship_types'] = memgraphGetRelationshipTypes();
	$stats['relationship_type_count'] = count($stats['relationship_types']);

	return $stats;
}

//---------- begin function memgraphGetDBTableIndexes ----------
/**
* @describe returns indexes for a given label (Memgraph uses indexes on node properties)
* @param label string - node label
* @return array of indexes
* @usage $indexes=memgraphGetDBTableIndexes('Person');
*/
function memgraphGetDBTableIndexes($label){
	// Memgraph stores index info in system tables
	$query = "SHOW INDEX INFO";
	$recs = memgraphQueryResults($query);

	// Filter for the specific label if recs contain label info
	$indexes = array();
	if(is_array($recs)){
		foreach($recs as $rec){
			// Check if this index applies to our label
			if(isset($rec['label']) && $rec['label'] == $label){
				// Handle properties - could be array or string
				$props = '';
				$props_key = '';
				if(isset($rec['properties'])){
					if(is_array($rec['properties'])){
						$props = implode(',', $rec['properties']);
						$props_key = implode('_', $rec['properties']);
					}
					else{
						$props = $rec['properties'];
						$props_key = str_replace(',', '_', $rec['properties']);
					}
				}

				$indexes[] = array(
					'key_name' => isset($rec['label']) ? $rec['label'].'_'.$props_key : 'index',
					'column_name' => $props,
					'is_primary' => 0,
					'is_unique' => isset($rec['type']) ? ($rec['type'] == 'label+property' ? 0 : 0) : 0,
					'seq_in_index' => 1,
					'index_type' => isset($rec['type']) ? $rec['type'] : 'label+property'
				);
			}
		}
	}

	return $indexes;
}

//---------- begin function memgraphNamedQueryList ----------
/**
* @describe returns a list of predefined admin queries for Memgraph
* @return array of query definitions
* @usage $queries=memgraphNamedQueryList();
*/
function memgraphNamedQueryList(){
	return array(
		array(
			'code'=>'stats',
			'icon'=>'icon-list',
			'name'=>'Stats'
		),
		array(
			'code'=>'labels',
			'icon'=>'icon-table',
			'name'=>'Labels (Node Types)'
		),
		array(
			'code'=>'relationships',
			'icon'=>'icon-flow-branch',
			'name'=>'Relationship Types'
		),
		array(
			'code'=>'indexes',
			'icon'=>'icon-marker',
			'name'=>'Indexes'
		),
		array(
			'code'=>'storage',
			'icon'=>'icon-database',
			'name'=>'Storage Info'
		),
		array(
			'code'=>'node_counts',
			'icon'=>'icon-chart-bar',
			'name'=>'Node Counts by Label'
		)
	);
}

//---------- begin function memgraphNamedQuery ----------
/**
* @describe returns pre-built Cypher queries based on name
* @param name string - query name (stats, labels, relationships, indexes, etc)
* @param str string - optional parameter for some queries
* @return query string
* @usage $query=memgraphNamedQuery('stats');
*/
function memgraphNamedQuery($name,$str=''){
	switch(strtolower($name)){
		case 'stats':
			return <<<ENDOFQUERY
MATCH (n)
  WITH count(n) as node_count
  MATCH ()-[r]->()
  WITH node_count, count(r) as rel_count
  MATCH (n)
  WITH node_count, rel_count, n
  LIMIT 10000
  WITH node_count, rel_count, labels(n) as labelList
  UNWIND labelList as label
  WITH node_count, rel_count, collect(DISTINCT label) as all_labels
  MATCH ()-[r]->()
  WITH node_count, rel_count, all_labels, type(r) as relType
  LIMIT 1000
  RETURN
      node_count as nodes,
      rel_count as relationships,
      size(all_labels) as labels,
      count(DISTINCT relType) as relationship_types
ENDOFQUERY;
		break;

		case 'labels':
			return <<<ENDOFQUERY
MATCH (n)
		WITH DISTINCT labels(n) as labelList
		UNWIND labelList as label
		RETURN DISTINCT label
		ORDER BY label
ENDOFQUERY;
		break;

		case 'relationships':
		case 'relationship_types':
			return <<<ENDOFQUERY
MATCH ()-[r]->()
WITH type(r) as relationship_type
RETURN
	relationship_type as name,
	count(*) as count
ORDER BY count DESC, name
ENDOFQUERY;
		break;

		case 'indexes':
			return <<<ENDOFQUERY
SHOW INDEX INFO
ENDOFQUERY;
		break;

		case 'storage':
		case 'storage_info':
			return <<<ENDOFQUERY
SHOW STORAGE INFO
ENDOFQUERY;
		break;

		case 'node_counts':
		case 'count':
			return <<<ENDOFQUERY
MATCH (n)
WITH DISTINCT labels(n) as labelList
UNWIND labelList as label
WITH label
MATCH (n)
WHERE label IN labels(n)
RETURN
	label,
	count(n) as count
ORDER BY count DESC, label
ENDOFQUERY;
		break;

		case 'sample':
			if(strlen($str)){
				return "MATCH (n:{$str}) RETURN n LIMIT 10";
			}
			return "MATCH (n) RETURN n LIMIT 10";
		break;

		default:
			return "// Unknown query: {$name}";
		break;
	}
}
