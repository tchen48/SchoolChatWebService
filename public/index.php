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
	//echo "</br></br>$pnumber Subjects from $startnumber In fid($fid):</br></br>";
	$configurationUtility = new WindowsConfigurationUtility();
	$defaulturl = "www.asucssa.org/uc_server/images/noavatar_small.gif";
	$preUrl = "www.asucssa.org/uc_server/data/avatar/";
	$prelocalpath = "C:\\wamp64\\www\\dev\\uc_server\\data\\avatar\\";
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
		//echo $row['uid'];
		$url = $configurationUtility ->getAvatar($row['authorid']);
		$url = $preUrl.$url;
		//$url =  file_exists($prelocalpath.$url)? $defaulturl : $preUrl.$url;
		//echo $url;
		//echo $row['subject']." ---- ".$row['author']." ---- ".$row['dateline']." ---- ".$row['tid']." ---- ".$row['authorid']." ---- ".$url."</br>";
        array_push($list,array("subject"=>$row['subject'], "author"=>$row['author'], "dateline"=>$row['dateline'], "tid"=>$row['tid'], "url"=>$url));
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
	//echo "</br></br>Get Post $tid :</br></br>";attachment
	$configurationUtility = new WindowsConfigurationUtility();
	$preUrl = "www.asucssa.org/uc_server/data/avatar/";
	$defaulturl = "www.asucssa.org/uc_server/images/noavatar_small.gif";

	
	
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
		$url = $configurationUtility ->getAvatar($row['authorid']);
		//$configurationUtility ->is404($url)? $defaulturl :
		$url = $preUrl.$url;
		$sql = "select att.attachment "
			. "from pre_forum_attachment_0 as att "
			. "inner join pre_forum_post as ps "
			. "on att.tid = ps.tid and att.pid = ps.pid "
			. "where att.tid = ".$tid." and att.pid = ".$row['pid']
			. " order by att.aid";
		$attachmentStatement = $this->db->query($sql);
		$attachmentList = array();
		while($aRow = $attachmentStatement->fetch(PDO::FETCH_ASSOC)){
			array_push($attachmentList,"www.asucssa.org/data/attachment/forum/".$aRow['attachment']);
		}
		//echo $row['subject']." ---- ".$row['message']." ---- ".$row['pid']." ---- ".$row['author']." ---- ".$row['dateline']." ---- ".$row['tid']." ---- ".$row['authorid']." ---- ".$url."</br></br>";
        array_push($list,array("subject"=>$row['subject'], "first"=>$row['first'], "message"=>$row['message'], "author"=>$row['author'], "dateline"=>$row['dateline'], "tid"=>$row['tid'], "url"=>$url,"attachment"=>$attachmentList));
		//echo $list;
    }
	$this->logger->addInfo("the content of the list is: ".json_encode($list));
	$response->getBody()->write(json_encode($list,JSON_UNESCAPED_UNICODE));
    return $response;
});



$app->get('getLatestPostList', function(Request $request, Response $response){	
	$response->getBody()->write("{message: [\"hello\", \"world\", \"other post\"]}");
	return $response;
} );


// $app->post('submitNewPost/{tid}/{uid}', function(Request $request,Response $response){
	// $this->logger->addInfo("Submitting new post...");
	// $json = $request->getBody();
    // $data = json_decode($json, true);
	// $response->getBody()->write("hello world");
	// return response;
// });


$app->post('/submitNewPost/{tid}/{uid}', function (Request $request, Response $response) {
    
	
	$this->logger->addInfo("Submitting new post...");
	$tid = (int)$request->getAttribute('tid');
	$uid = (int)$request->getAttribute('uid');
	$first = false;
	$time = time();
	$date = date("Ymd");
	try {
			$statement = $this->db->query('SELECT * FROM pre_forum_sofa WHERE tid='.$tid);
			//$statement->execute();
			if($statement->rowCount()>=1)
				$first = true;
			 if($first){
				 $statement = $this->db->prepare('INSERT INTO pre_forum_threadpartake SET tid='.$tid.' , uid='.$uid.' , dateline='.$time);
				 $statement->execute();
			 }
			$statement = $this->db->prepare("insert into pre_forum_post_tableid  (pid) values (null)");
			$statement->execute();
			$pid = $this->db->lastInsertId();
			//$pid = '26717';$statement = $this->db->query('SELECT p.* FROM pre_forum_post p
			//WHERE p.tid = '.$tid.'
			$stetement = $this->db->query('SELECT t.* FROM pre_forum_post t WHERE t.tid = '.$tid.' LIMIT 1');
			$stetement->execute();
			$row = $stetement->fetch();
			$json = $request->getBody();
			$data = json_decode($json, true);
			$fid =$row["fid"];
			$subject = $row["subject"];
			$statement = $this->db->query('SELECT * FROM `pre_common_member` where uid = '.$uid.' LIMIT 1');
			$row = $statement->fetch();
			$username = $row["username"];
			$time = time();
			
			$sql = 'INSERT INTO pre_forum_post (fid, tid, first, author, authorid,subject,dateline,message,
					useip,port,invisible,anonymous,usesig,htmlon,bbcodeoff,smileyoff,parseurloff,
					attachment,status,pid)
					values(:fid, :tid,0,:name,:uid,\'\',:time,:message,\'::1\',\'51867\' , 0, 0 ,
					1 , 0 , -1 , -1 , 0, 0 , 0,:pid)';
			$statement = $this->db->prepare($sql);
			 $statement->execute(array(
						 "fid" => $fid,
						 "tid" => $tid,
						 "uid" => $uid,
						 "name"=>$username,
						 "time"=> $time,
						 "message"=>$data['message'],
						 "pid"=>$pid
					 ));		
			$statement = $this->db->prepare('INSERT INTO pre_common_member_action_log SET uid='.$uid.', action=1 , `dateline`='.$time);
			$statement->execute();
			$statement = $this->db->prepare('UPDATE pre_common_stat SET post=post+1 WHERE daytime = '.$date);  //date("Ymd")
			$statement->execute();
			 if($first){
				 $statement = $this->db->prepare('DELETE FROM pre_forum_sofa WHERE tid='.$tid);
				 $statement->execute();
			 }
			$statement = $this->db->query('SELECT * FROM pre_common_credit_rule_log WHERE uid = '.$uid.' AND rid = 2 LIMIT 1');
			
			if($statement->rowCount() < 1 ){
				 $statement = $this->db->prepare('INSERT INTO pre_common_credit_rule_log SET uid='.$uid.', cyclenum = 1, total = 1, dateline = '.time());
				 $statement->execute();
			}
			else {
				$row = $statement->fetch();
				$statement = $this->db->prepare('UPDATE pre_common_credit_rule_log SET cyclenum=cyclenum+1,total=total+1,dateline='.time().',extcredits1=0,extcredits2=1,extcredits3=0 where clid = '.$row['clid']);
				$statement->execute();
			}
			$statement = $this->db->prepare('UPDATE pre_common_member_count SET extcredits2=extcredits2+1,posts=posts+1 WHERE uid IN ('.$uid.')');
			$statement->execute();
			$statement = $this->db->prepare('UPDATE  pre_common_member SET credits=credits+2 WHERE uid='.$uid);
			$statement->execute();

			$statement = $this->db->prepare('UPDATE  pre_common_member_status SET lastpost='.time().' WHERE `uid` IN('.$uid.')');
			$statement->execute();
			
			$lastPost = $tid.' '.$subject.' '.time().' '.$username;
			$statement = $this->db->prepare('UPDATE  pre_forum_forum SET lastpost=\''.$lastPost.'\' WHERE fid='.$fid);
			$statement->execute();
			$statement = $this->db->prepare('UPDATE pre_forum_forum SET posts=posts+1, todayposts=todayposts+1 WHERE `fid`='.$fid);
			$statement->execute();
			$statement = $this->db->prepare('UPDATE pre_forum_thread SET maxposition=maxposition+1,lastposter=\''.$username.'\',replies=replies+1,lastpost='.$time.' WHERE tid='.$tid);
			$statement->execute();
			$result = array("result"=>$first);
			
			$response->getBody()->write(json_encode($result,JSON_UNESCAPED_UNICODE));
	} catch (Exception $e) {
		$response->getBody()->write('error----'.$e->getMessage());
	}
    return $response;
});
$app->post('/validate', function (Request $request, Response $response) {
	try{
		$json = $request->getBody();
		$data = json_decode($json, true);
		$username = $data["username"];
		$password = $data["password"];
		$statement = $this->db->query("SELECT * FROM pre_ucenter_members WHERE username = '".$username."'");
		$row = $statement->fetch();
		if(md5(md5($password).$row["salt"]) == $row["password"] ){
			$result = array("result"=>true, "token"=>$row["uid"]);
			return $response->getBody()->write(json_encode($result,JSON_UNESCAPED_UNICODE));
		}
		else {
			$result = array("result"=>false, "token"=>"");
			return $response->getBody()->write(json_encode($result,JSON_UNESCAPED_UNICODE));
		} 
	}
	catch (Exception $e) {
		$response->getBody()->write('error----'.$e->getMessage());
	}
});


$app->run();