<?php
include_once('db-connection.php');
session_start();
$records='';
$re='';
if (empty($_SESSION['auth'])){
	echo '<script>alert("Please log in to view your purchasing record");window.location.href="login.php"</script>';
}	else{
		$email = $_SESSION['auth']['em'];
		$db=dbConnect();
		$stmt=$db->prepare('SELECT distinct cart.oid FROM orders INNER JOIN cart WHERE orders.user=:email AND cart.oid=orders.oid ORDER BY cart.oid DESC LIMIT 5');
		$stmt->bindParam(':email',$email);
		$stmt->execute();
		$result=$stmt->fetchAll();
		if(!$result[0]){
			$records="<span id='empty'>".$result[0][0]."You haven't bought anything yet. </span>";
		}else {
		$records.='<tr><th>Order</th><th>Purchasing Record</th><th>Sum</th></tr>';
		$orderno = 1;
		foreach ($result as $i) {
			$re='';
			$records.='<tr><td>'.$orderno.'</td><td>';
//			$records.='<tr><td>'.$i[0].'</td><td>';
			$stmt=$db->prepare('SELECT * FROM cart WHERE oid=:oid');
			$stmt->bindParam(':oid',$i[0]);
			$stmt->execute();
			$products=$stmt->fetchAll();
			foreach($products as $value){
				$stmt=$db->prepare('SELECT name FROM products WHERE pid=:pid');
				$stmt->bindParam(':pid',$value['pid']);
				$stmt->execute();
				$product=$stmt->fetchAll();
				$re.='name: '.$product[0][0].", quantity: ".$value['quantity']."; ";
			}
			$sum=$products[0]['sum'];
			$records.=$re.'</td>';
			$records.="<td>HK$ ".$sum."</td></tr>";
			$orderno++;
		}
		}


}
?>
<!DOCTYPE html>
<html>
<style>
	#title{
		font-family:"Lucida Sans Unicode", "Lucida Grande", sans-serif;
		color:#0084cc;;
		font-size:24px;
		width:80%;
		margin:auto;
		text-align: center;
	}
	#container{
		width:85%;
		margin:auto;
		border-radius:10px;
		border-style:solid;
		border-width:5px;
		border-color:#edf3fc;
	}
	tr, td, th{
		font-family:"Lucida Sans Unicode", "Lucida Grande", sans-serif;
	}
	tr{height:30px;}
	tr:nth-child(even) {background-color: #f2f2f2}
	th {
		background-color: #0084cc;
		color: white;
	}
	#ordertable{
		width:100%;
		margin:auto;
		padding:10px;
	}
	#empty{
		font-family:"Lucida Sans Unicode", "Lucida Grande", sans-serif;
		color:#4a6187;
		margin-left:30%;
	}
</style>
<head>
	<meta charset='utf-8'>
	<link href='incl/login.css' rel='stylesheet' type='text/css'>
	<h1>Dewy's Shopping Mall</h1>
</head>
<body>
<div id='home'><a id='hometext' href='home.php'>Home</a></div>
<div id='title'>Your Most Recent 5 Orders</div>
<br>
<div id='container'>
<table id='ordertable'>
	<?php echo $records; ?>
</table>
</div>
</body>

</html>