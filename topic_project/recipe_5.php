<?php

//食材名稱找id

error_reporting(0);
header("Content-Type:text/html; charset=utf-8");

// 連結資料庫
require_once 'dbconnect.php';
require_once 'Curl.php';
$db = DBConnect::init();
$pageNum = $_GET['page'];
$pageSize = $_GET['pageSize'];
$query = $_GET['query'];
$query = urldecode($query);
$search = array();
$search = explode(" ", $query);
$ingredient_array = array();
$param = array();
if (trim($query) != "") {
    if (count($search) > 0) {
        $Querysql = "SELECT	`ingredients_id`,`ingredients_origin_name`,`ingredients_name`
				FROM	`ingredients`
				WHERE (`ingredients_name` like ? OR `ingredients_origin_name` like ?)";
        for ($i = 1; $i < count($search); $i++) {
            $Querysql .="OR (`ingredients_name` like ? OR `ingredients_origin_name` like ?)";
        }
    }

    for ($i = 0; $i < count($search); $i++) {
        array_push($param, "%" . $search[$i] . "%");
    }
    $Queryresult = $db->prepare($Querysql);
    $j = 1;
    for ($i = 0; $i < count($param); $i++) {
        $Queryresult->bindParam($j, $param[$i]);
        $Queryresult->bindParam($j + 1, $param[$i]);
        $j++;
    }
    $Queryresult->execute();
// 將資料塞進array中
    foreach ($Queryresult->fetchAll() as $data) {
        //判斷食材種類 start
        $ingredients_id = $data['ingredients_id'];
        $ingredientKind = "";
        if (strlen($ingredients_id) >= 4 && is_numeric($ingredients_id)) {
            $ingredientKind = "seafood";
        } else if ($ingredients_id == "chicken") {
            $ingredientKind = "chicken";
        } else if ($ingredients_id == "egg") {
            $ingredientKind = "egg";
        } else if ($ingredients_id == "pork") {
            $ingredientKind = "pork";
        } else {
            $ingredientKind = "vegetable";
        }
        //判斷食材種類 end
        $temp = array('IngredientKind' => $ingredientKind, 'IngredientId' => $ingredients_id, 'IngredientName' => urlencode($data['ingredients_name']), 'IngredientOriginName' => urlencode($data['ingredients_origin_name']));
        array_push($ingredient_array, $temp);
    }
}
$dataCount = count($ingredient_array);

if ($dataCount == 0) {

    $page = 0;
    $ingredient_array = array('IngredientKind' => '', 'IngredientId' => '', 'IngredientName' => '', 'IngredientOriginName' => '');
    $returnIngredient = array($ingredient_array);
} else {

    $page = ceil($dataCount / $pageSize);
    $dataStart = $pageNum * $pageSize - $pageSize;
    if ($pageNum * $pageSize - 1 >= $dataCount) {
        $dataEnd = $dataCount;
    } else {
        $dataEnd = $pageNum * $pageSize;
    }

    $returnIngredient = array();
    for ($i = $dataStart; $i < $dataEnd; $i++) {
        array_push($returnIngredient, $ingredient_array[$i]);
    }
}


$info = array('TotalPage' => $page, 'Page' => $pageNum, 'DataCount' => $dataCount, 'PageDataCount' => count($returnIngredient));
$returnData = array('Info' => $info, 'Ingredient' => $returnIngredient);


echo urldecode(json_encode($returnData));
?>