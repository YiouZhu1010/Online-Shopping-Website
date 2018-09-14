<?php

include_once('db-connection.php');
include_once('session.php');
include_once('csrf.php');

$emailErr = $loginErr = '';

session_start();

if ($_SERVER["REQUEST_METHOD"]=="POST"){
	$email = testInput($_POST['email']);
	$password = $_POST['password'];
	$action = 'login';
	if(validEmail($email)&&isUser($email,$password)&&csrf_verifyNonce($action,$_POST['nonce'])){
		$result = getUser($email);
		session_regenerate_id();
		$username = $result['user'];
	 	$isAdmin = $result['admin'];
		if($isAdmin == 1){
			setSession($username,$email,$isAdmin);
			updateAuth();
			header("Location: admin.php");
			exit();}
		else{
			setSession($username,$email,$isAdmin);
			updateAuth();
			header("Location: home.php");
			exit();
		}
	}
	else{}
}


?>

<!DOCTYPE html>
<html>
<head>
	<meta charset='utf-8'>
	<link href='incl/login.css' rel='stylesheet' type='text/css'>
	<h1> Dewy's Shopping Mall</h1>
</head>
<body>
<div id='home'><a id='hometext' href='home.php'>Home</a></div>
<div id='guidetext'>Log In</div>
<br>
<div id='field'>
<section>
	<form id='loginForm' method='post' action='login.php'>
		<span class='error'><?php echo $loginErr; ?></span>
		<div id='content'>
		<br>
		<input type='hidden' name='nonce' value='<?php echo csrf_getNonce('login'); ?>'/>
		<label for='email'>Email Address</label>
		<div><input type="email" name="email" required="true" /></div>
		<span class='error'><?php echo $emailErr; ?></span><br>
		<label for='password'>Password</label>
		<div><input type="password" name="password" required="true"/></div><br>
		<input id='button' type='submit' value="Log In"/><br><br>
		</div>
	</form>
	<div id='buttonfield'><button id="resetpw">Reset Password</button></div><br>
</section>
</div>
</body>
<script type="text/javascript">
	document.getElementById("resetpw").onclick = function () {
        location.href = "reset.php";
    };
</script>
</html>