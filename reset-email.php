<?php

function testInput($data){
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function sendEmail($email,$link){
    require 'sendgrid/vendor/autoload.php';

    $from = new SendGrid\Email(null, "resetpassword@dewyshoppingmall.com");
    $subject = "Reset Password";
    $to = new SendGrid\Email(null, $email);
    $content = new SendGrid\Content("text/plain", "Please click the following link to reset your password : ".$link."   .Thanks!");
    $mail = new SendGrid\Mail($from, $subject, $to, $content);

    error_log("sending link ".$link);

    $apiKey = "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";
    $sg = new \SendGrid($apiKey);

    $sg->client->mail()->send()->post($mail);
}

function resetURL($email){
    $db = dbConnect();
    $stmt=$db->prepare("UPDATE passwords SET active = 0 WHERE email=:email");
    $stmt->bindParam(':email',$email);
    $stmt->execute();
    $nonce=csrf_getNonce('reset');
    $stmt = $db->prepare("INSERT INTO passwords (email, nonce, active) VALUES (:email,:nonce,'1')");
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':nonce', $nonce);
    $stmt->execute();
    $url = "https://secure.s2.ierg4210.ie.cuhk.edu.hk/reset-password.php?email=" . urlencode($email) . "&nonce=" . urlencode($nonce);
    return $url;
}
?>
