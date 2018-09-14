<?php
if(isset($_COOKIE['auth'])){
	unset($_COOKIE['auth']);
	setcookie('auth',null,-1);
	session_start();
	session_unset();
	session_destroy();
	
}	else {
	header('Location: login.php', true, 302);
}


?>
<html>
    <head>
        <meta http-equiv="refresh" content="3;url=http://s2.ierg4210.ie.cuhk.edu.hk/login.php" />
        <style>
        p{  margin-top:10%;
            margin-left: 10%;
        	font-family:"Lucida Sans Unicode", "Lucida Grande", sans-serif;
			font-size: 20px;
        }
        </style>
    </head>
    <body>
        <p>You have logged out. Redirecting to login page in 3 seconds...<p>
    </body>
</html>
