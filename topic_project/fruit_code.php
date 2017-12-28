<?php
$row = 1;
$row = 1;
$fruit = array();
if (($handle = fopen("fruit.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $num = count($data);
        $data[0] = str_replace("?","",$data[0]);
        if($data[1]!=""){
             $fruit[$row][0] = $data[0]."-".$data[1];
             $fruit[$row][1] = $data[2];
        }else{
             $fruit[$row][0] = $data[0];
             $fruit[$row][1] = $data[2];
        }
           $row++;
    }
    fclose($handle);
}

//資料庫連線
require_once "dbconnect.php";
$db = dbconnect::init();
//將資料insert進資料庫
for($i = 1;$i<=count($fruit);$i++){
    $id = $fruit[$i][1];
    $id = str_replace('*','',$id);
    $id = str_replace('(註1)','',$id);
    $id = str_replace('(註2)','',$id);
    $id = str_replace('(註3)','',$id);
    $id = str_replace('(註4)','',$id);
    $name = $fruit[$i][0];
    $sql = "UPDATE `ingredients` SET ingredients_origin_name = '$name' WHERE ingredients_id = '$id'";
    $result = $db->exec($sql);
    echo $result;
    
}
?>