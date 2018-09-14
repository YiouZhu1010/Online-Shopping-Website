<?php 	

include_once("db-connection.php");
$db = dbConnect();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$request = $_POST["pid_list"];



if (count($request) > 0){
	foreach($request as $id){
		$sql = "SELECT * FROM products WHERE pid = '".$id."';";
		$result = $db->query($sql);
		if($result->rowCount() >= 1){
			while($record = $result->fetch(PDO::FETCH_ASSOC)) {
                $ans->$record['pid']->name = $record['name'];
                $ans->$record['pid']->price = $record['price'];
            }
		}

	}
}
else if(count($request) == 0){
	$sql = "SELECT * FROM products";
	$result = $db->query($sql);
	if($result->rowCount() >= 1){
		while ($record = $result->fetch(PDO::FETCH_ASSOC)){
			$ans->$record['pid']->name = $record['name'];
            $ans->$record['pid']->price = $record['price'];
		}
	}
}


echo json_encode($ans);


?>
