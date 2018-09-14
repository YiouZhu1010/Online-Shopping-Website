<?php

include_once('csrf.php');
include_once('db-connection.php');
include_once("reset-email.php");
session_start();
$resetErr="";
if($_SERVER['REQUEST_METHOD']=='POST'){
    if(csrf_verifyNonce('resetpw',$_POST['nonce'])) {

        $email = testInput($_POST['email']);
        $db = dbConnect();
        $stmt = $db->prepare("SELECT * FROM users WHERE email=:email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $result = $stmt->fetchAll();

        if (count($result) == 0) {
            $resetErr = "&nbsp;&nbsp;The email has NOT been registered.";
        } else {
            $url = resetURL($email);
            sendEmail($email,$url);
            echo '<script>alert("An email for resetting password will be sent to your mailbox, please follow the link to reset your password."); window.location.href="home.php"</script>';
            }
    } else{
        echo "CSRF Attack!";
    }
}


?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <link href='incl/login.css' rel='stylesheet' type='text/css'>
    <h1> Dewy's Shopping Mall</h1>
    <title>Reset Password</title>
</head>
<body>
<div id='home'><a id='hometext' href='home.php'>Home</a></div>
<div id='guidetext'>Reset Password</div>
<br>
<div id='field'>
    <section>
        <form id='resetpwForm' method='post' action='reset.php'>
            <span class='error'><?php echo $resetErr; ?></span>
            <div id='content'>
                <br>
                <input type='hidden' name='nonce' value='<?php echo csrf_getNonce('resetpw'); ?>' />
                <label for='email'>Please enter your email:</label><br><br>
                <div><input id="email" type="email" name="email" required /></div>
                <br>
                <input id='button' type='submit' value="Send"/><br><br>
            </div>
        </form>
    </section>
</div>
</body>
<script type="text/javascript">
    document.getElementById("email").oninput = function () {
        this.setCustomValidity("");
    };
    document.getElementById("email").oninvalid = function () {
        this.setCustomValidity('Please enter a valid email address');
    };
</script>
</html>