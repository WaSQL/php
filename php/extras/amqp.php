<?php
$progpath=dirname(__FILE__);
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING | E_DEPRECATED));
include_once("{$progpath}/amqp/PhpAmqpLib/Channel/AbstractChannel.php");
include_once("{$progpath}/amqp/PhpAmqpLib/Channel/AMQPChannel.php");
include_once("{$progpath}/amqp/PhpAmqpLib/Connection/AbstractConnection.php");
include_once("{$progpath}/amqp/PhpAmqpLib/Connection/AMQPStreamConnection.php");
include_once("{$progpath}/amqp/PhpAmqpLib/Wire/AMQPAbstractCollection.php");
include_once("{$progpath}/amqp/PhpAmqpLib/Wire/AbstractClient.php");
include_once("{$progpath}/amqp/PhpAmqpLib/Wire/AMQPWriter.php");
include_once("{$progpath}/amqp/PhpAmqpLib/Wire/IO/AbstractIO.php");
include_once("{$progpath}/amqp/PhpAmqpLib/Wire/IO/StreamIO.php");
include_once("{$progpath}/amqp/PhpAmqpLib/Wire/GenericContent.php");
include_once("{$progpath}/amqp/PhpAmqpLib/Wire/AMQPReader.php");
include_once("{$progpath}/amqp/PhpAmqpLib/Wire/Constants091.php");
include_once("{$progpath}/amqp/PhpAmqpLib/Wire/AMQPTable.php");
include_once("{$progpath}/amqp/PhpAmqpLib/Message/AMQPMessage.php");
include_once("{$progpath}/amqp/PhpAmqpLib/Helper/MiscHelper.php");
include_once("{$progpath}/amqp/PhpAmqpLib/Helper/DebugHelper.php");
include_once("{$progpath}/amqp/PhpAmqpLib/Helper/Protocol/Protocol091.php");
include_once("{$progpath}/amqp/PhpAmqpLib/Helper/Protocol/Wait091.php");
include_once("{$progpath}/amqp/PhpAmqpLib/Helper/Protocol/MethodMap091.php");


use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

function amqpStreamConnection($host,$port,$user,$pass,$vhost){
	return new AMQPStreamConnection($host,$port,$user,$pass,$vhost);
}
function amqpTable($params=array()){
	return new AMQPTable($params);
}
