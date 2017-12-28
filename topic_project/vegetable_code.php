<?php

$row = 1;
$row = 1;
$vegetable1 = array();
if (($handle = fopen("vegetable1.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $num = count($data);
        
        if($data[1]!=""){
             $vegetable1[$row][0] = $data[0]."-".$data[1];
             $vegetable1[$row][1] = $data[2];
        }else{
             $vegetable1[$row][0] = $data[0];
             $vegetable1[$row][1] = $data[2];
        }
           $row++;
    }
    fclose($handle);
}

//資料庫連線
require_once "dbconnect.php";
$db = dbconnect::init();
//將資料insert進資料庫
for($i = 2;$i<=count($vegetable1);$i++){
    $id = $vegetable1[$i][1];
    $name = $vegetable1[$i][0];
    $sql = "UPDATE `ingredients` SET ingredients_origin_name = '$name' WHERE ingredients_id = '$id'";
    $result = $db->exec($sql);
    echo $result;
    
}

?>