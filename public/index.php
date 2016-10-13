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


$app->post('/submitNewSubject/{fid}/{uid}', function (Request $request, Response $response) {
	
	$this->logger->addInfo("Submitting new topic...");
	$fid = (int)$request->getAttribute('fid');
	$uid = (int)$request->getAttribute('uid');
	$postData  = json_decode($request->getBody(),true);
	$subject = $postData["subject"];
	$content = $postData["message"];
	$time = time();
	$date = date("Ymd");
	try {
		$statement = $this->db->query('SELECT * FROM `pre_common_member` where uid = '.$uid.' LIMIT 1');
		$row = $statement->fetch();
		$author = $row["username"];
		
		$statement = $this->db->prepare("INSERT INTO pre_forum_thread SET fid=:fid , posttableid=0 , readperm=0 , 
		price=0 , typeid=4 , sortid=0 , author= :author, `authorid`=:uid , `subject`=:subject , dateline=:time, 
		`lastpost`=:time , `lastposter`=:author , `displayorder`='0' , `digest`='0' , `special`='0' , `attachment`='0' , 
		`moderated`='0' , `status`='32' , `isgroup`='0' , `replycredit`='0' , `closed`='0'");
		$parameter = array("author"=>$author,"fid"=>$fid,"uid"=>$uid,"subject"=>$subject,"content"=>$content,"time"=>$time, "date"=>$date);
		$statement->execute($parameter);
		$tid = $this->db->lastInsertId();
		$statement = $this->db->prepare("INSERT INTO pre_forum_newthread SET `tid`=:tid , `fid`=:fid , 
		`dateline` = :tid");
		$parameter = array("fid"=>$fid,"tid"=>$tid);
		$statement->execute($parameter);
		
		$statement = $this->db->prepare("INSERT INTO pre_common_member_action_log SET `uid`=:uid , `action`='0' , `dateline`=:time");
		$parameter = array("time"=>$time,"uid"=>$uid);
		$statement->execute($parameter);
		
		$statement = $this->db->prepare("UPDATE  pre_common_member_field_home SET `recentnote`=:subject WHERE `uid`=:uid");
		$parameter = array("subject"=>$subject,"uid"=>$uid);
		$statement->execute($parameter);
		
		$statement = $this->db->prepare("insert into pre_forum_post_tableid  (pid) values (null)");
		$statement->execute();
		$pid = $this->db->lastInsertId();
		
		
		$statement = $this->db->prepare("INSERT INTO pre_forum_post SET `fid`=:fid , `tid`=:tid , `first`='1' , `author`=:author , `authorid`=:uid , `subject`=:subject , 
		`dateline`=:time , `message`=:content , `useip`='::1' , `port`='64212' , `invisible`='0' , `anonymous`='0' , `usesig`='1' , `htmlon`='0' , `bbcodeoff`='-1' , 
		`smileyoff`='-1' , `parseurloff`=0 , `attachment`='0' , `tags`='' , `replycredit`='0' , `status`='0' , `pid`=:pid");
		$parameter = array("fid"=>$fid,"tid"=>$tid,"author"=>$author,"uid"=>$uid,"subject"=>$subject,"time"=>$time,"content"=>$content,"pid"=>$pid);
		$statement->execute($parameter);
		
		$statement = $this->db->prepare("UPDATE pre_common_stat SET `thread`=`thread`+1 WHERE `daytime` = "+$date);
		
		
		$statement = $this->db->query('SELECT * FROM pre_common_credit_rule_log WHERE uid = '.$uid.' AND rid = 1 LIMIT 1');
		
		//rid 1 means post new topic, 2 means new post
		if($statement->rowCount() < 1 ){
			 $statement = $this->db->prepare('INSERT INTO pre_common_credit_rule_log SET uid='.$uid.', cyclenum = 1, total = 1, dateline = '.time());
			 $statement->execute();
		}
		else {
			$row = $statement->fetch();
			$statement = $this->db->prepare('UPDATE pre_common_credit_rule_log SET cyclenum=cyclenum+1,total=total+1,dateline='.time().',extcredits1=0,extcredits2=2,extcredits3=0 where clid = '.$row['clid']);
			$statement->execute();
		}
		
		$statement = $this->db->prepare('UPDATE pre_common_member_count SET extcredits2=extcredits2+2,threads=threads+1,posts=posts+1 WHERE uid IN ('.$uid.')');
		$statement->execute();
		$statement = $this->db->prepare('UPDATE  pre_common_member SET credits=credits+5 WHERE uid='.$uid);
		$statement->execute();
		
		$lastPost = $tid.' '.$subject.' '.time().' '.$author;
		$statement = $this->db->prepare("UPDATE  pre_common_member_status SET `lastpost`=".$time." WHERE `uid` IN(".$uid.")");
		$statement->execute();
		$statement = $this->db->prepare("UPDATE  pre_forum_forum SET `lastpost`='".$lastPost."' WHERE `fid`=".$fid);
		$statement->execute();
		$statement = $this->db->prepare("UPDATE pre_forum_forum SET threads=threads+'1', posts=posts+'1', todayposts=todayposts+'1' WHERE `fid`=".$fid);
		$statement->execute();
		$statement = $this->db->prepare("INSERT INTO pre_forum_sofa SET `tid`=".$tid." , `fid`=".$fid);
		$statement->execute();
		$result = array("result"=>"success");
		$response->getBody()->write(json_encode($result,JSON_UNESCAPED_UNICODE));
	} 
	catch (Exception $e) {
		$response->getBody()->write('error----'.$e->getMessage());
	}
    return $response;
});

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

$app->post('/register', function (Request $request, Response $response) {
	try{
		$json = $request->getBody();

		$data = json_decode($json, true);
		$username = $data["username"];
		$password = $data["password"];
        $email = $data["email"];
	    $time = time();
	    $date = date("Ymd");
        $salt = "f2c349";
        $enpass = md5(md5($password).$salt);

        $statement = $this->db->query("SELECT * FROM `asucssao_asu`.pre_ucenter_members WHERE username = '".$username."'");

        if($statement->rowCount()>=1){
            $result = array("success" => false, "message"=>"User name existed");
            return $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        $statement = $this->db->query("SELECT * FROM `asucssao_asu`.pre_ucenter_members WHERE email = '".$email."'");

        if($statement->rowCount()>=1){
            $result = array("success" => false, "message"=>"Email existed");
            return $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        
        $statement = $this->db->prepare("INSERT INTO `asucssao_asu`.pre_ucenter_members SET  secques='', username='".$username."', password='".$enpass."', email='".$email."', regip='::1', regdate='".$time."', salt='".$salt."'");
        $statement->execute();

        $uid =  $this->db->lastInsertId();

        // $statement = $this->db->prepare("INSERT INTO `asucssao_asu`.pre_ucenter_memberfields SET uid='".$uid."'");
        // $statement->execute();  //DEFAULT VALUE
		
        $statement = $this->db->prepare("INSERT INTO pre_common_regip SET `ip`='::1' , `count`='1' , `dateline`='".$time."'");
        $statement->execute();
        $statement = $this->db->prepare("REPLACE INTO pre_common_member SET `uid`='".$uid."' , `username`='".$username."' , `password`='".$enpass."' , `email`='".$email."' , `adminid`='0' , `groupid`='10' , `regdate`='".$time."' , `emailstatus`='0' , `credits`='0' , `timeoffset`='9999'");
        $statement->execute();
	    $statement = $this->db->prepare("REPLACE INTO pre_common_member_status SET `uid`='".$uid."' , `regip`='::1' , `lastip`='::1' , `lastvisit`='".$time."' , `lastactivity`='".$time."' , `lastpost`='0' , `lastsendmail`='0'");
        $statement->execute();
	    $statement = $this->db->prepare("REPLACE INTO pre_common_member_count SET `uid`='".$uid."' , `extcredits1`='0' , `extcredits2`='0' , `extcredits3`='0' , `extcredits4`='0' , `extcredits5`='0' , `extcredits6`='0' , `extcredits7`='0' , `extcredits8`='0'");
        $statement->execute();
	    // $statement = $this->db->prepare("RREPLACE INTO pre_common_member_profile SET `uid`='".$uid."'");
        // $statement->execute();  // SYNTAX ERROR
		
	    // $statement = $this->db->prepare("REPLACE INTO pre_common_member_field_forum SET `uid`='".$uid."'");
        // $statement->execute(); // FIELD MEDAL HAS NO DEFULT VALUE


	    $statement = $this->db->prepare("UPDATE  pre_common_member_count SET `oltime`='0' WHERE `uid`='".$uid."'");
        $statement->execute();

	    $statement = $this->db->prepare("UPDATE  pre_common_member_status SET `lastip`='::1' , `port`='51220' , `lastactivity`='".$time."' , `lastvisit`='".$time."' WHERE `uid`='".$uid."'");
        $statement->execute();
	    $statement = $this->db->prepare("INSERT INTO pre_common_statuser SET `uid`='".$uid."' , `daytime`='".$date."' , `type`='login'");
        $statement->execute();
	    $statement = $this->db->prepare("DELETE FROM pre_common_statuser WHERE `daytime` != '".$date."'");
        $statement->execute();
	    // $statement = $this->db->prepare("INSERT INTO pre_common_stat SET `daytime`='".$date."' , `login`='1'");
        // $statement->execute();   // dulicate day time, just record login, canbe ignore now

		
	    $statement = $this->db->prepare("SELECT * FROM pre_common_credit_rule_log WHERE uid=".$uid." AND rid=15");
        $statement->execute();
	    $statement = $this->db->prepare("INSERT INTO pre_common_credit_rule_log SET `uid`='".$uid."' , `rid`='15' , `fid`='0' , `total`='1' , `cyclenum`='1' , `dateline`='1476339469' , `extcredits1`='0' , `extcredits2`='2' , `extcredits3`='0'");
        $statement->execute();
	    $statement = $this->db->prepare("SELECT * FROM pre_common_member_count WHERE `uid`='".$uid."'");
        $statement->execute();
	    $statement = $this->db->prepare("UPDATE pre_common_member_count SET `extcredits2`=`extcredits2`+'2' WHERE uid = '".$uid."'");
        $statement->execute();


	    $statement = $this->db->prepare("UPDATE  pre_common_member SET `credits`='2' WHERE `uid`='".$uid."'");
        $statement->execute();
	    $statement = $this->db->prepare("UPDATE pre_common_stat SET `register`=`register`+1 WHERE `daytime` = '".$time."'");
        $statement->execute();

		
		$result = array("success"=>true);
		return $response->getBody()->write(json_encode($result,JSON_UNESCAPED_UNICODE));
	}
	catch (Exception $e) {
		$response->getBody()->write('error----'.$e->getMessage());
	}

});


$app->run();