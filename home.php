<?php

session_start();
include_once("db-connection.php");
include_once("csrf.php");
$db = dbConnect();

$sql1 = "SELECT * FROM categories";
$result = $db->query($sql1);
$categories = $result->fetchAll();

$loginInfo = 'Log In';
$link = 'login.php'; 
$checkRecord = '';

if(!empty($_SESSION['auth'])){
    $user = $_SESSION['auth'];
    $data = $user['user'];
    $loginInfo = 'Hi! '.$data.'. Log Out';
    $link = 'logout.php';
    $checkRecord = '<li><a href="show-record.php">History</a></li>';

}
function genCategory($categories){
    foreach ($categories as $value) {
        echo '<li> <a href="home.php?catid='.$value['catid'].'">' .$value['name']. '</a></li>';
    }
}
function genNavigation(){
    $db = dbConnect();
    if (isset($_GET["catid"])) {
                $catid = htmlspecialchars($_GET["catid"]);
                $stmt = $db->prepare("SELECT * FROM categories WHERE catid=(:catid)");
                $stmt->bindParam(':catid',$catid);
                $stmt->execute();
                $cat_name = $stmt->fetch(PDO::FETCH_ASSOC);
                echo '&gt; <a href="home.php?catid='.$catid.'">'.$cat_name['name'].'</a>';
            }
    if (isset($_GET["pid"])) {
                $pid = htmlspecialchars($_GET["pid"]);
                $stmt=$db->prepare('SELECT * FROM products WHERE pid = :pid');
                $stmt->bindParam(':pid',$pid);
                $stmt->execute();
                $result=$stmt->fetch(PDO::FETCH_ASSOC);
                $prod_name = $result["name"];
                $catid = $result["catid"];
                $stmt = $db->prepare("SELECT * FROM categories WHERE catid=(:catid)");
                $stmt->bindParam(':catid',$catid);
                $stmt->execute();
                $cat_name = $stmt->fetch(PDO::FETCH_ASSOC);
                echo '&gt; <a href="home.php?catid='.$catid.'">'.$cat_name['name'].'</a> &gt; ';
                echo '<a href="home.php?pid='.$pid.'">'.$prod_name.'</a>';
            }
}

function genProducts(){
    $db = dbConnect();
    if (isset($_GET["catid"])) {
                $catid = htmlspecialchars($_GET["catid"]);
                $stmt = $db->prepare("SELECT * FROM products WHERE catid=:catid");
                $stmt ->bindParam(':catid',$catid);
                $stmt->execute();
                $products= $stmt->fetchAll();
                foreach($products as $value){  
                    echo '<div class="item"><a href="home.php?pid='.$value['pid'].'"><img class="image" src="img/'.$value['image'].'" alt='.$value['name'].'></a><br><a class="productname" href="home.php?pid='.$value['pid'].'">'.$value['name'].'<br></a><br><div class="price">HK$ '.$value['price'].'</div><button class="addtocart" id="tocart'.$value['pid'].'">Add to cart</button></div>';}
        }
    if (isset($_GET["pid"])) {
                $pid = $_GET["pid"];
                $stmt=$db->prepare('SELECT * FROM products WHERE pid = :pid');
                $stmt->bindParam(':pid',$pid);
                $stmt->execute();
                $products=$stmt->fetch(PDO::FETCH_ASSOC);
                 echo '<table id="table"><tr><td><img class="bigpic" src="img/'.$products['image'].'"> </td><td><section><header>'.$products['name'].'</header><p>'.$products['description'].'</p><p class="subprice">HK$ '.$products['price'].'</p><button class="addtocart" id="tocart'.$products['pid'].'">Add to cart</button></section></td><tr></table>';
        }
    if (empty($_GET)) {
                $sql = "SELECT * FROM products";
                $result = $db->query($sql);
                $products = $result->fetchAll();
                foreach ($products as $value) {
                    echo '<div class="item">';
                    echo '<a href="home.php?pid='.$value['pid'].'"><img class="image" src="img/'.$value['image'].'" alt='.$value['name'].')></a><br>';
                    echo '<a class="productname" href="home.php?pid='.$value['pid'].'">'.$value['name'].'<br></a><br>';
                    echo '<div class="price">HK$ '.$value['price'].'</div>';
                    echo '<button class="addtocart" id="tocart'.$value['pid'].'">Add to cart</button></div>';
                }
        }
}

function genScript(){
    $db = dbConnect();
    $sql = "SELECT * FROM products";
    $result = $db->query($sql);
    $products = $result->fetchAll();
    foreach($products as $a){
        echo 'var el = document.getElementById("tocart'.$a['pid'].'");';
        echo 'if(el){el.addEventListener("click",addToCarttemp('.$a['pid'].'));}';
    }
}

function genAddtemp(){
    echo 'function addToCarttemp(i){
    return function(){addToCart(i);};}';
}



?>


<!DOCTYPE html>
<html lang="en-US">
<head>

<link rel="stylesheet" type="text/css" href="incl/style.css">
<ul id='headnav'>
  <li><a href="home.php">Home</a></li>
  <span id='forcheckrecord'><?php echo $checkRecord;?></span>
  <li id='resetpwbutton'><a href="reset.php">Reset Password</a></li>
  <li id='signupbutton'><a href="signup.php">Sign up</a></li>
  <li id='loginbutton'><a href=<?php echo $link; ?>><?php echo $loginInfo; ?></a></li>
</ul>
<br><br><br>
<title>IERG4210 Dewy Shopping Mall</title>
<h1>IERG4210 Dewy Shopping Mall</h1>
</head>

<body>

<div id="shoppingcartall">
<img id="cart" src="img/cart.png" alt="shoppingcart"><span>shopping cart</span>
<div id="shoppinglist">
<div id="sltitle">Shopping List</div>
<div id="sltable">
</div>
 </div>
</div>

<div id="wholelayout">
<div class="categorybar">
<br><br>
<ul class="categorybar">
<li><a id="category" href="home.php">&nbsp;Category</a></li>
<?php genCategory($categories); ?>
</ul>
</div>
<div id="rightpart">
<nav id="navigation">
<a href="home.php">Home</a>
<!-- generate navigations -->
<?php genNavigation(); ?>
</nav>

<br>
<div id="itemlist">
<!-- generate items -->
<?php  genProducts(); ?>

</div>
</div>
</div>
<footer>IERG4210 2016 Created by ZHU Yiou | &nbsp;
<div class="fb-share-button" data-href="https://secure.s2.ierg4210.ie.cuhk.edu.hk" data-layout="button" data-size="large" data-mobile-iframe="true"><a class="fb-xfbml-parse-ignore" target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Fsecure.s2.ierg4210.ie.cuhk.edu.hk%2F&amp;src=sdkpreparse">Share</a></div>
</footer>

</body>

<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_GB/sdk.js#xfbml=1&version=v2.8";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<script><?php genScript(); genAddtemp(); ?>
    var nonce = <?php echo csrf_getNonce('submitcart')?>;
</script>
<script type="text/javascript" src="cart.js"></script>
</html>