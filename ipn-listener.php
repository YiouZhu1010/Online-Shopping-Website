<?php

define('USE_SANDBOX', 1);
define("LOG_FILE",'/var/www/error_log.log');
define('DEBUG',1);
include_once('db-connection.php');
$db = dbConnect();
// STEP 1: Read POST data

// reading posted data from directly from $_POST causes serialization 
// issues with array data in POST
// reading raw POST data from input stream instead. 
$raw_post_data = file_get_contents('php://input');
$raw_post_array = explode('&', $raw_post_data);
$myPost = array();
foreach ($raw_post_array as $keyval) {
  $keyval = explode ('=', $keyval);
  if (count($keyval) == 2)
     $myPost[$keyval[0]] = urldecode($keyval[1]);
}
// read the post from PayPal system and add 'cmd'
$req = 'cmd=_notify-validate';
if(function_exists('get_magic_quotes_gpc')) {
   $get_magic_quotes_exists = true;
}  else {$get_magic_quotes_exists = false;}
foreach ($myPost as $key => $value) {        
   if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) { 
        $value = urlencode(stripslashes($value)); 
   } else {
        $value = urlencode($value);
   }
   $req .= "&$key=$value";
}


// STEP 2: Post IPN data back to paypal to validate
if(USE_SANDBOX == true){
  $paypal_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
} else {$paypal_url = 'https://www.paypal.com/cgi-bin/webscr';}

$ch = curl_init($paypal_url);
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

// In wamp like environments that do not come bundled with root authority certificates,
// please download 'cacert.pem' from "http://curl.haxx.se/docs/caextract.html" and set the directory path 
// of the certificate as shown below.
// curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
$res = curl_exec($ch);
if( !($res) ) {
    if(curl_errno($ch)!=0){
        if(DEBUG == true){
            error_log(date('[Y-m-d H:i e] '). "Can't connect to PayPal to validate IPN message: " . curl_error($ch) . PHP_EOL, 3, LOG_FILE);
        }
    curl_close($ch);
    exit;
    } else{
        if(DEBUG == true) {
            error_log(date('[Y-m-d H:i e] '). "HTTP request of validation request:". curl_getinfo($ch, CURLINFO_HEADER_OUT) ." for IPN payload: $req" . PHP_EOL, 3, LOG_FILE);
            error_log(date('[Y-m-d H:i e] '). "HTTP response of validation request: $res" . PHP_EOL, 3, LOG_FILE);
        }
        curl_close($ch);
    }
}



// STEP 3: Inspect IPN validation result and act accordingly

if (strcmp ($res, "VERIFIED") == 0) {
    $txn_type = $_POST['txn_type'];
    if (strcmp($txn_type,'cart') != 0){
        error_log('Transaction type is not cart');
        exit();
    }else {
        $payment_status = $_POST['payment_status'];
        $payment_currency = $_POST['mc_currency'];
        $txn_id = $_POST['txn_id'];
        $receiver_email = $_POST['receiver_email'];
        $payer_email = $_POST['payer_email'];
        $custom = $_POST['custom'];
        $invoice = (int)$_POST['invoice'];
        $sum = number_format($_POST['mc_gross'],2,'.','');
        $digest = '';
        $digest = $payment_currency.";".$receiver_email.";";
        $order = '{';
        $item = array();
        $i = 1;
        while(!empty($_POST['item_number'.$i])){
            $pid = $_POST['item_number'.$i];
            $itemname = $_POST['item_name'.$i];
            $quantity = $_POST['quantity'.$i];
            $price = $_POST['mc_gross_'.$i];
            $price = number_format($price,2,'.','');
            $order .=$pid.":{".$quantity.",".$price."},";
            $temp = array("name"=>$itemname,"quantity"=>$quantity,"price"=>$price);
            $item[$pid] = $temp;
            $i++;
        }
        $order .= "}";

        // select salt and hashed digest from orders
        $stmt = $db->prepare("SELECT * FROM orders WHERE oid = :oid");
        $stmt ->bindParam(':oid',$invoice);
        $stmt ->execute();
        $orders = $stmt->fetch(PDO::FETCH_ASSOC);
        $salt = $orders['salt'];
        $digest_hashed = $orders['digest'];

        $digest .= $order.";".$sum;
        $digest_hash = hash_hmac('sha512',$digest,$salt);

        // check payment status
        if ($payment_status == 'Completed'){
            $payment_status = 1;
        } else{
            $payment_status = 2;
            error_log(date(' [Y-m-d H:i e] ') . $txn_id . " Payment is not completed" . PHP_EOL, 3, LOG_FILE);
            exit();
        }

        // check digest
        if(strcmp($digest_hash,$digest_hashed)== 0){
            error_log("Digest is valid");

            //check duplicate transactions
            $stmt = $db->prepare("SELECT * FROM cart WHERE txn_id=:txn_id");
            $stmt->bindParam(":txn_id", $txn_id);
            $stmt->execute();
            $result = $stmt->rowCount();
            if ($result > 0){
                error_log(date(' [Y-m-d H:i e] ') . $txn_id . " Transaction ID already exists" . PHP_EOL, 3, LOG_FILE);
                exit();
            }

            // update order
            $stmt = $db-> prepare("UPDATE orders SET txn_id=:txn_id,payment_status=:payment_status WHERE oid = :oid AND txn_id IS NULL");
            $stmt ->bindParam(':oid',$invoice);
            $stmt ->bindParam(':txn_id',$txn_id);
            $stmt ->bindParam(':payment_status',$payment_status);
            $stmt ->execute();
            $count = $stmt -> rowCount();
            if ($count > 0){
                $stmt = $db ->prepare("INSERT INTO cart (cid,oid,txn_id,pid,quantity,price,sum) VALUES (NULL,:oid,:txn_id,:pid,:quantity,:price,:sum)");

                foreach($item as $pid=>$info){
                    $stmt ->bindParam(':oid',$invoice);
                    $stmt ->bindParam(':txn_id',$txn_id);
                    $stmt ->bindParam(':sum',$sum);
                    $pri = number_format($info['price']/$info['quantity'],2);
                    $stmt ->bindParam(':pid',$pid);
                    $stmt ->bindParam(':quantity',$info['quantity']);
                    $stmt ->bindParam(':price',$pri);
                    $stmt->execute();
                }

            } else{
                error_log(date(' [Y-m-d H:i e] ') . $txn_id . " Transaction ID already exists" . PHP_EOL, 3, LOG_FILE);
            }

        }


    }
    if(DEBUG == true) {
        error_log("Verified IPN: $req ". PHP_EOL, 3, LOG_FILE);
    }

} else if (strcmp ($res, "INVALID") == 0) {
    // log for manual investigation
    if(DEBUG == true) {
        error_log(date('[Y-m-d H:i e] '). "Invalid IPN: $req" . PHP_EOL, 3, LOG_FILE);
    }
}
?>