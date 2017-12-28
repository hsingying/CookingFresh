<?php

// 食譜名稱找食譜
error_reporting(0);
header("Content-Type:text/html; charset=utf-8");

// 連結資料庫
require_once 'dbconnect.php';
require_once 'Curl.php';
$db = db_connect::init();
$pageNum = $_GET['page'];
$pageSize = $_GET['pageSize'];
$query = $_GET['query'];
$query = urldecode($query);
$search = array();
$search = explode(" ", $query);
// 存放食譜名稱及食譜id的陣列
$recipe_array = array();
$param = array();
if (trim($query) != "") {
    
        /* 先帶ID去資料庫找有沒有食譜 */
        if (count($search) > 0) {
            $Querysql = "SELECT  distinct(`recipe_id`) , `recipe_name` ,  `recipe_img` FROM  `recipe` WHERE  `recipe_name` like ? ";
            for ($i = 1; $i < count($search); $i++) {
                $Querysql .="AND `recipe_name` like ? ";
            }
        }
        for($i=0;$i<count($search);$i++){
            array_push($param,"%".$search[$i]."%");
        }
        $Queryresult = $db->prepare($Querysql);
        $j=1;
        for($i=0;$i<count($param);$i++){
            $Queryresult->bindParam($j,$param[$i]);
            $j++;
        }
        $Queryresult->execute();
        foreach ($Queryresult->fetchAll() as $data) {
            // 將資料塞進array中
            $data['recipe_name'] = str_replace(' │ ', '', $data['recipe_name']);
            ArrayFormat($data['recipe_name'], $data['recipe_id'], $data['recipe_img'], $recipe_array);
        }
    
}
$dataCount = count($recipe_array);
// 如果沒有食譜, 傳空字串過去
if ($dataCount == 0) {
    $page = 0;
    ArrayFormat('', '', '', $recipe_array);
    $returnRecipeArray = $recipe_array;
} else {
    $page = ceil($dataCount / $pageSize);
    $dataStart = $pageNum * $pageSize - $pageSize;
    if ($pageNum * $pageSize - 1 > $dataCount) {
        $dataEnd = $dataCount;
    } else {
        $dataEnd = $pageNum * $pageSize;
    }
    $returnRecipeArray = array();
    for ($i = $dataStart; $i < $dataEnd; $i++) {
        array_push($returnRecipeArray, $recipe_array[$i]);
    }
}
$info = array('TotalPage' => $page, 'Page' => $pageNum, 'DataCount' => $dataCount, 'PageDataCount' => count($returnRecipeArray));
$returnData = array('Info' => $info, 'Recipe' => $returnRecipeArray);

echo urldecode(json_encode($returnData));

/* 將資料塞進array的function */

function ArrayFormat($data_name, $data_id, $data_picture, &$recipe_array) {
    $temp = array('RecipeName' => urlencode($data_name), 'RecipeID' => $data_id, 'RecipePicture' => urlencode($data_picture));
    array_push($recipe_array, $temp);
}

/* 幫括號加上反斜線, 以正常找尋關鍵字 */

function RuleFormat($str) {
    $str = str_replace('(', '\(', $str);
    $str = str_replace(')', '\)', $str);
    $str = str_replace('[', '\[', $str);
    $str = str_replace(']', '\]', $str);
    $str = str_replace('{', '\{', $str);
    $str = str_replace('}', '\}', $str);
    $str = str_replace('+', '\+', $str);
    $str = str_replace('【', '\【', $str);
    $str = str_replace('】', '\】', $str);
    $str = str_replace('/', '\/', $str);
    $str = str_replace('.', '\.', $str);
    $str = str_replace('^', '\^', $str);
    $str = str_replace('$', '\$', $str);
    $str = str_replace('*', '\*', $str);
    $str = str_replace('?', '\?', $str);
    $str = str_replace('|', '\|', $str);
    return $str;
}

//排除emoji(尚未測試)
function filterEmoji($str) {

    $str = preg_replace_callback(
            '/./u', function (array $match) {
        return strlen($match[0]) >= 4 ? '' : $match[0];
    }, $str);

    return $str;
}

?>