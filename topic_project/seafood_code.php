<?php
$row = 1;
$row = 1;
$seafood = array();
if (($handle = fopen("fish.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $num = count($data);
             $seafood[$row][0] = $data[0];
             $seafood[$row][1] = $data[1];
              $row++;
    }
    fclose($handle);
}
//資料庫連線
require_once "dbconnect.php";
$db = DBConnect::init();
//將資料insert進資料庫
for($i = 1;$i<=count($seafood);$i++){
    $id = $seafood[$i][0];
    $name = $seafood[$i][1];
    $sql = "UPDATE `ingredients` SET ingredients_origin_name = '$name' WHERE ingredients_id = '$id'";
    $result = $db->exec($sql);
    echo $result;
    
}

?>