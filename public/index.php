<?php 
header("Content-Type:text/html; charset=utf-8");

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \SchoolChat\Utility\WindowsConfigurationUtility;

require '../vendor/autoload.php';
require '../lib/WindowsConfigurationUtility.php';



//setting the config for slim
$config['displayErrorDetails'] = true;

//this part should be programmed to interface and change setting accoring to OS
$configurationUtility = new WindowsConfigurationUtility();
$connectionString = $configurationUtility ->getConnectionString();
$connectionInfo = explode(";", $connectionString);
$config['db']['host']   = $connectionInfo[0];
$config['db']['dbname'] = $connectionInfo[1];
$config['db']['user']   = $connectionInfo[2];
$config['db']['pass']   = $connectionInfo[3];

$path = $configurationUtility->getLogPath();
$config['log'] = $path==""?"../logs/app.log":$path;
$app = new \Slim\App(["settings" => $config]);

//Dependency injection, inject log function and db connection function

$container = $app->getContainer();
$container['logger'] = function($c) {
    $setting = $c["settings"];
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler($setting['log']);
    $logger->pushHandler($file_handler);
    return $logger;
};
$container['db'] = function($c){
  $db = $c["settings"]['db'];
  $pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'],$db['user'], $db['pass'], 
    array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8") );
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  return $pdo;
};

//controller

$app->get('/hello/{name}', function (Request $request, Response $response) {
    $name = $request->getAttribute('name');
    $this->logger->addInfo("start hello route...");
    $this->logger->addInfo("start accessing the database...");
    $statement = $this->db->query('SELECT * FROM pre_forum_post WHERE first = 1 ORDER BY pid DESC limit 20 offset 20 ');
	$list = [["subject"=>"test"]];
	echo "</br></br>20 Latest Subject List:</br></br>";
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
		echo $row['subject']."</br>";
         array_push($list,array("subject"=>$row['subject']));
		 //echo $list;
    }
	$this->logger->addInfo("the content of the list is: ".json_encode($list));
	$response->getBody()->write(json_encode($list,JSON_UNESCAPED_UNICODE));
    return $response;
});

//TODO: need to debug getAvatar($uid)
$app->get('/getSubjects/{fid}/{startnumber}/{pnumber}', function (Request $request, Response $response) {
    $fid = (int)$request->getAttribute('fid');
	$startnumber = (int)$request->getAttribute('startnumber');
	$pnumber = (int)$request->getAttribute('pnumber');
	
    $this->logger->addInfo("start getSubjects route...");
    $this->logger->addInfo("start accessing the database...");
    $statement = $this->db->query('SELECT p.* FROM pre_forum_post p 
	WHERE p.first = 1 AND p.fid = '.$fid.' AND p.attachment > 0 AND p.invisible = 0 AND p.status = 0 
	ORDER BY p.pid DESC 
	limit '.$startnumber.','.$pnumber.'');
	
	$list = array();
	echo "</br></br>$pnumber Subjects from $startnumber In fid($fid):</br></br>";
	$configurationUtility = new WindowsConfigurationUtility();
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
		
		//$url = $configurationUtility ->getAvatar($row['uid']);
		//echo $url;
		echo $row['subject']." ---- ".$row['author']." ---- ".$row['dateline']." ---- ".$row['tid']." ---- ".$row['authorid']."</br>";
         array_push($list,array("subject"=>$row['subject'], "author"=>$row['author'], "dateline"=>$row['dateline'], "tid"=>$row['tid'], "authorid"=>$row['authorid']));
		 //echo $list;
    }
	$this->logger->addInfo("the content of the list is: ".json_encode($list));
	$response->getBody()->write(json_encode($list,JSON_UNESCAPED_UNICODE));
    return $response;
});

$app->get('/getPost/{tid}', function (Request $request, Response $response) {
    $tid = (int)$request->getAttribute('tid');
	
    $this->logger->addInfo("start getPost route...");
    $this->logger->addInfo("start accessing the database...");
    $statement = $this->db->query('SELECT p.* FROM pre_forum_post p
	WHERE p.tid = '.$tid.' AND p.invisible = 0 AND p.status = 0 
	ORDER BY p.pid ');
	
	$list = array();
	echo "</br></br>Get Post $tid :</br></br>";
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
		//$url = $configurationUtility ->getAvatar($row['uid']);
		//echo $url;
		echo $row['subject']." ---- ".$row['message']." ---- ".$row['pid']." ---- ".$row['author']." ---- ".$row['dateline']." ---- ".$row['tid']." ---- ".$row['authorid']."</br></br>";
         array_push($list,array("subject"=>$row['subject'], "message"=>$row['message'], "pid"=>$row['pid'], "author"=>$row['author'], "dateline"=>$row['dateline'], "tid"=>$row['tid'], "authorid"=>$row['authorid']));
		 //echo $list;
    }
	$this->logger->addInfo("the content of the list is: ".json_encode($list));
	$response->getBody()->write(json_encode($list,JSON_UNESCAPED_UNICODE));
    return $response;
});


$app->get('/xxx/{id}/{name}', function (Request $request, Response $response) {
    $name = $request->getAttribute('name');
	$id = $request->getAttribute('id');
    $this->logger->addInfo("start hello route...");
    $this->logger->addInfo("start accessing the database...");
    $statement = $this->db->query('SELECT * FROM pre_common_admincp_cmenu');
    
	while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        echo $row['id'] . ' ' . $row['utl'];
    }
    $response->getBody()->write("Hello, $name with ID $id");
    return $response;
});

$app->get('getLatestPostList', function(Request $request, Response $response){	
	$response->getBody()->write("{message: [\"hello\", \"world\", \"other post\"]}");
	return $response;
} );





$app->run();