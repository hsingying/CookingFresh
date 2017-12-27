<?php
error_reporting(0);
header("Content-Type:text/html; charset=utf-8");

// 連結資料庫
require_once 'dbconnect.php';
require_once 'Curl.php';
$db = dbconnect::init();

$id = $_GET['id'];

//$sql = "SELECT  `ingredients_origin_name` FROM `ingredients` WHERE `ingredients_id` = '$id'";
$sql = "SELECT	`ingredients_origin_name`,`ingredients_name`
FROM	`ingredients`
WHERE `ingredients_id` = '$id'";
$result = $db->query($sql);
$result = $result->fetch();
$ingredient_name = array();
$temp = array('IngredientName'=>urlencode($result['ingredients_name']),'IngredientOriginName'=>urlencode($result['ingredients_origin_name']));
array_push($ingredient_name,$temp);
echo urldecode(json_encode($ingredient_name));
?>