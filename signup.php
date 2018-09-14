<?php

include_once("csrf.php");
include_once("db-connection.php");
session_start();
$signupErr='';
if($_SERVER['REQUEST_METHOD']=='POST'){
    if(csrf_verifyNonce('signup',$_POST['nonce'])){
        $username = testInput($_POST['username']);
        $email = testInput($_POST['email']);
        $password = $_POST['password'];
        $db = dbConnect();
        $stmt=$db->prepare("SELECT * FROM users WHERE email=:email");
        $stmt->bindParam(':email',$email);
        $stmt->execute();
        $result=$stmt->fetchAll();
        if(count($result)!=0){
            $signupErr="&nbsp;&nbsp;The email has been registered.";
        } else {
            $admin = 0;
            $salt = mt_rand();
            $saltedpassword = hash_hmac('sha512', $password, $salt);
            $update = $db->prepare("INSERT INTO users (email,user,salt,saltedpassword,admin) VALUES (:email,:user,:salt,:saltedpassword,:admin)");
            $update->bindParam(':email', $email);
            $update->bindParam(':user', $username);
            $update->bindParam(':salt', $salt);
            $update->bindParam(':saltedpassword', $saltedpassword);
            $update->bindParam(':admin', $admin);
            $update->execute();
            $update = $db->prepare("INSERT INTO passwords (active,email,nonce) VALUES ('1',:email,NULL)");
            $update->bindParam(':email', $email);
            $update->execute();
            echo '<script>alert("You have successfully signed up!");window.location.href="login.php"</script>';
            exit();
        }
    } else{
        echo "CSRF Attack!";
    }
}
function testInput($data){
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <link href='incl/login.css' rel='stylesheet' type='text/css'>
    <h1> Dewy's Shopping Mall</h1>
    <title>Sign Up</title>
</head>
<body>
<div id='home'><a id='hometext' href='home.php'>Home</a></div>
<div id='guidetext'>Sign up</div>
<br>
<div id='field'>
    <section>
        <form id='signupForm' method='post' action='signup.php'>
         <span class='error'><?php echo $signupErr; ?></span>
            <div id='content'>
                <br>
                <input type='hidden' name='nonce' value='<?php echo csrf_getNonce('signup'); ?>'/> 
                <label for='username'>Username</label>
                <div><input id='username' type="text" name="username" required="true" pattern="^[A-Za-z\d]{3,}$" /></div>
                <br>
                <label for='email'>Email Address</label>
                <div><input id="email" type="email" name="email" required="true" /></div>
                <br>
                <label for='password'>Password</label>
                <div><input id='password' type="password" name="password" required="true" pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$" /></div>
                <br>
                <input id='button' type='submit' value="Sign up"/><br><br>
            </div>
        </form>
    </section>
</div>
</body>
<script type="text/javascript">
    document.getElementById("email").oninput = function () {
        this.setCustomValidity("");
    }
    document.getElementById("email").oninvalid = function () {
        this.setCustomValidity('Please enter a valid email address');
    };
    document.getElementById("password").oninput = function () {
        this.setCustomValidity("");
    }
    document.getElementById("password").oninvalid = function () {
        this.setCustomValidity('Must contain at least 8 characters, including at least 1 letter and number.');
    };
    document.getElementById("username").oninput = function () {
        this.setCustomValidity("");
    }
    document.getElementById("username").oninvalid = function () {
        this.setCustomValidity('Please enter minimum 3 characters of alphabet or number');
    };
</script>

</html>