<?php

include_once('db-connection.php');
include_once('session.php');
include_once('csrf.php');

session_start();
$checkSession = validSession();
if($checkSession['result']==false){
    echo json_encode(array('failed' => 'Please log in first'));
    exit();
}
if(!csrf_verifyNonce('submitcart',$_POST['nonce'])){
    echo json_encode(array('failed'=> 'CSRF attack detected'));
    exit();
}

$db = dbConnect();
$req = json_decode($_POST['cart']);
$sum = 0.00;
if($req == NULL){
    echo json_encode(array('failed'=>'Nothing in the shopping cart'));
    exit;
} else{
    $order = "{";
    foreach($req as $pid=>$quantity){
        $pid =(int)$pid;
        $quantity = (int)$quantity;
        if($quantity <= 0){
            exit;
        }
        $stmt = $db->prepare("SELECT * FROM products WHERE pid=:pid");
        $stmt->bindParam(":pid",$pid);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        $price = $product['price'];
        $price = number_format($price*$quantity,2,'.','');
        $order = $order.$pid.":{".$quantity.",".$price."},";
        $sum = $sum + $price;
    }
    $order = $order."}";
    $email = 'dewyeo-business@gmail.com';
    $currency = 'HKD';
    $user = $_SESSION['auth']['em'];
    $sum = (float)$sum;
    $sum = number_format($sum, 2, '.','');
    $salt =uniqid(mt_rand(), true);
    $digest = "HKD;dewyeo-business@gmail.com;".$order.";".$sum;
    $digest_hash = hash_hmac("sha512",$digest,$salt);
    
    $stmt = $db->prepare("INSERT INTO orders (oid,txn_id,user,payment_status,currency,merchant_email,salt,digest,sum) VALUES (NULL,NULL,:user,0,:currency,:email,:salt,:digest,:sum)");
    $stmt->bindParam(':user',$user);
    $stmt->bindParam(':email',$email);
    $stmt->bindParam(':currency',$currency);
    $stmt->bindParam(':salt',$salt);
    $stmt->bindParam(':digest',$digest_hash);
    $stmt->bindParam(':sum',$sum);
    $stmt->execute();
    $oid = $db->lastInsertId();

    echo json_encode(array('success'=>array('order_id'=>$oid,'digest'=>$digest_hash,'merchant_email'=>'dewyeo-business@gmail.com','currency'=>'HKD','order_details'=>json_encode($order))));

}



?>