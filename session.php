<?php

include_once('db-connection.php');

function validSession(){
	if(!empty($_SESSION['auth']) && !empty($_COOKIE['auth'])){
		$token = $_SESSION['auth'];
		$expire = $token['exp'];
		if($expire < time()){
			return array('result'=> false, 'error'=>1);
		} else{
			if($token['k']!=$_COOKIE['auth']){
				return array('result'=>false,'error'=>2);
			} else{
				updateAuth();
				return array('result'=>true);
			}
		}
	}	
	else {
		return array('result'=>false, 'error'=>3);
	}

}
function setSession($username, $email, $admin){
	$token = array('em'=>$email,'user'=>$username,'admin'=>$admin);
	$_SESSION['auth']=$token;
}

function updateAuth(){
	$key = md5(uniqid());
	$exp = time()+3*24*3600;
	setcookie('auth',$key,$exp,'','',true,true);
	$_SESSION['auth']['exp']=$exp;
	$_SESSION['auth']['k']=$key;

}

function getUser($email){
	$db = dbConnect();
	$stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
	$stmt->execute(array($email));
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	return $result;
}
function testInput($data){
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	return $data;
}

function validEmail($email){
	if(empty($email)){
		$emailErr = "Email is required";
		return false;
	} else{
		if(!preg_match("/^[\w=+\-\/][\w='+\-\/\.]*@[\w\-]+(\.[\w\-]+)*(\.[\w]{2,6})$/",$email)){
			global $emailErr;
			$emailErr = "Invaild email format.<br>";
			return false;
		}  else{
			return true;
		}
	}
}

function isUser($email,$password){
	global $loginErr;
	$result = getUser($email);
	if(empty($result)){
		$loginErr = "&nbsp;Invaild email address or password.<br>";
		return false;
	}
	else{
		$saltPassword = hash_hmac('sha512',$password,$result['salt']);
		if($saltPassword == $result['saltedpassword']){
			return true;
		} else{
			$loginErr = "&nbsp;Invalid email address or password.<br>";
			return false;
		}
	}

}
?>