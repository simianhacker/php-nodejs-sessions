<?php 

require_once(dirname(__FILE__).'/includes/redis-session.php'); 
require_once(dirname(__FILE__).'/includes/predis/autoload.php');
Predis\Autoloader::register();

$redis   = new Predis\Client();
$session = new Session\Redis($redis, 'session_id', 'this-shoud-be-secure');
session_start();

$_SESSION['php'] = (isset($_SESSION['php']))? $_SESSION['php']+1 : 1;

header('Content-type: application/json');
echo json_encode(array('session'=>$_SESSION));