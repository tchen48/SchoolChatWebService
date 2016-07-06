<?php
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
  $pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'],$db['user'], $db['pass']);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  return $pdo;
};

//controller
$app->get('/hello/{name}', function (Request $request, Response $response) {
    $name = $request->getAttribute('name');
    $this->logger->addInfo("start hello route...");
    $this->logger->addInfo("start accessing the database...");
    $statement = $this->db->query('SELECT * FROM pre_common_admincp_cmenu');
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        echo $row['id'] . ' ' . $row['utl'];
    }
    $response->getBody()->write("Hello, $name");
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