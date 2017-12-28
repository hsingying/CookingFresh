<?php

header("Content-Type:text/html; charset=utf-8");

require_once 'dbconnect.php';
require_once 'Curl.php';
$db = DBConnect::init();
$ingredient = array();
$recipe = array();

$sql = "SELECT * FROM `flower` ORDER BY `ingredients_id` ASC";
$result = $db->query($sql);
foreach ($result->fetchAll() as $data) {
    array_push($ingredient, $data['ingredients_id']);
}

echo $ingredient[0];

for ($i = 4460; $i < 4586; $i++) {
    $url = "https://topic-project-hsingying.c9users.io/function/recipe_1.php";
    $url.="?id=" . $ingredient[$i];
    echo $url . '<br>';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array("id" => $ingredient[$i])));
    $output = curl_exec($ch);
    curl_close($ch);
    echo $ingredient[$i] . '<br>';
}
?>

