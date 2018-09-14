<?php

include_once("csrf.php");
include_once("db-connection.php");
session_start();

if($_SERVER['REQUEST_METHOD']=='POST'){
    if(csrf_verifyNonce('newpassword',$_POST['nonce'])){
        $password = $_POST['password'];
        $email = testInput($_POST['email']);
        $db = dbConnect();
        $stmt=$db->prepare("SELECT * FROM users WHERE email=:email");
        $stmt->bindParam(':email',$email);
        $stmt->execute();
        $admin = 0;
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $salt = $result['salt'];
        $saltedpassword = hash_hmac('sha512', $password, $salt);
        $update = $db->prepare("UPDATE users SET saltedpassword=:saltedpassword WHERE email=:email");
        $update->bindParam(':email', $email);
        $update->bindParam(':saltedpassword', $saltedpassword);
        $update->execute();
        $update = $db->prepare("INSERT INTO passwords (active,email,nonce) VALUES ('1',:email,NULL)");
        $update->bindParam(':email', $email);
        $update->execute();
        echo '<script>alert("You have successfully changed your password!");window.location.href="login.php"</script>';
        exit();
    } else{
        echo '<script>alert("Invalid Token Found");</script>';
    }
} else if($_SERVER['REQUEST_METHOD'] == 'GET'){
    $resetNonce='';
    $emailadd='';
    if (isset($_GET['email']) && isset($_GET['nonce'])) {
        $db = dbConnect();
        $stmt = $db->prepare("UPDATE passwords SET active=0 WHERE email=:email AND nonce=:nonce");
        $email=testInput($_GET['email']);
        $emailadd=$email;
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':nonce', $_GET['nonce']);
        $stmt->execute();
        $result=$stmt->rowCount();
        if($result>0){
            $resetNonce = csrf_getNonce('newpassword');
        } else{
            echo '<script>alert("The link is not valid any more. Please send the reset email again.");window.location.href="reset.php"</script>';
        }
    }
    else{
        echo '<script>alert("The link is invalid.");</script>';
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
    <title>Reset Password</title>
</head>
<body>
<!-- <div id='home'><a id='hometext' href='home.php'>Home</a></div> -->
<div id='guidetext'>Reset Password</div>
<br>
<div id='field'>
    <section>
        <form id='resetForm' method='post' action='reset-password.php'>
            <div id='content'>
                <br>
                <input type='hidden' name='email' value='<?php echo $emailadd; ?>'/>
                <input type='hidden' name='nonce' value='<?php echo $resetNonce; ?>'/>
                <label for='password'>Please enter your new password</label>
                <div><input id='password' type="password" name="password" required="true" pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$" /></div>
                <br>
                <input id='button' type='submit' value="Reset"/><br><br>
            </div>
        </form>
    </section>
</div>
</body>
<script type="text/javascript">
    document.getElementById("password").oninput = function () {
        this.setCustomValidity("");
    }
    document.getElementById("password").oninvalid = function () {
        this.setCustomValidity('Must contain at least 8 characters, including at least 1 letter and number.');
    };
</script>

</html>