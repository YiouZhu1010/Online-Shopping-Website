<?php
function dbConnect(){
$servername = "localhost";
$username = "root";
$password = "[ierg4210]";
$dbname = "shoppingmall";

$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
return $conn;
}
?>