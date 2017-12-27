<?php
$row = 1;
$row = 1;
$flower = array();
if (($handle = fopen("flower.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $num = count($data);
             $flower[$row][0] = $data[0];
             $flower[$row][1] = $data[1];
             $flower[$row][2] = $data[1];
             if($data[2]!=''){
                 $flower[$row][2].='-'.$data[2];
             }
              $row++;
    }
    fclose($handle);
}
//print_r($flower);
//資料庫連線
require_once "dbconnect.php";
$db = dbconnect::init();
//將資料insert進資料庫
for($i = 1;$i<=count($flower);$i++){
    $id = $flower[$i][0];
    $name = $flower[$i][1];
    $origin = $flower[$i][2];
    //echo "123";
    $sql = "INSERT INTO `flower`(`ingredients_id`, `ingredients_name`, `ingredients_origin_name`) VALUES ('$id','$name','$origin')";
    $result = $db->exec($sql);
    echo $result;
    
}

?>