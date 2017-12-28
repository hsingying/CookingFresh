<?php

error_reporting(0);
header("Content-Type:text/html; charset=utf-8");

// 連結資料庫
require_once 'dbconnect.php';
require_once 'Curl.php';
$db = DBConnect::init();

$id = $_GET['id'];

// 存放食譜名稱及食譜id的陣列
$recipe_array = array();

/* 先帶ID去資料庫找有沒有食譜 */
$Querysql = "SELECT DISTINCT(rn.`recipe_id`), `recipe_name`, `recipe_img` FROM `recipe_new` rn join `recipe_ingredients` ri on rn.`recipe_id` = ri.`recipe_id`  where `ingredients_id` = '$id' and `recipe_name` <> ''";
$Queryresult = $db->query($Querysql);
foreach ($Queryresult->fetchAll() as $data) {
    // 將資料塞進array中
    $recipe_name = str_replace(' │ ', '', $data['recipe_name']);
    ArrayFormat($recipe_name, $data['recipe_id'], $data['recipe_img'], $recipe_array);
}

// 如果資料庫沒有資料, 則去icook爬資料回來
if (count($recipe_array) == 0) {

    /* 先去資料庫找name */
    $sql = "SELECT	`ingredients_name`
            FROM	`ingredients`
            WHERE `ingredients_id` = '$id'";

    $result = $db->query($sql);
    $result = $result->fetch();
    $name = $result['ingredients_name'];

    /* 爬蟲 */

    // 食材名稱串在網址必須進行urlencode
    $name = urlencode($name);
    $url = "https://icook.tw/recipes/search?q=&ingredients=";
    $a = $url . $name;
    $curl = new Curl($a);
    $data = $curl->getContent($a);
    // 抓取頁面顯示總筆數
    preg_match_all('/<span class="recipe-total">([^<>]+)<\/span>/', $data, $total);

    // 因為一次只能抓一頁的內容
    // 所以先抓出一頁之後去抓取總筆數
    // 計算出會有幾頁(若餘數不為零則頁數+1)在去串頁數的參數在url中
    // 用迴圈將內容撈出來
    $total_page = preg_replace("/,/", "", $total[1][0]) / 12;
    $mod = preg_replace("/,/", "", $total[1][0]) % 12;
    if ($mod != 0) {
        $total_page+=1;
    }
    if ($total_page > 3) {
        $total_page = 3;
    }

    for ($p = 1; $p <= $total_page; $p++) {
        $Url = $a . "&page=" . $p;
        $curl = new Curl($Url);
        $data = $curl->getContent($Url);
        // 用html的attribute和tag去抓內容(菜名/id)
        preg_match_all('/data-title="([^<>]+)" class="recipe-name"/', $data, $recipe_name);
        preg_match_all('/class="recipe-name" href="\/recipes\/([^<>]+)">/', $data, $recipe_id);

        // 抓出來會是陣列所以用for把他們挑出來
        for ($i = 1; $i < count($recipe_name); $i++) {
            for ($j = 0; $j < count($recipe_name[$i]); $j++) {

                $rule_name = RuleFormat($recipe_name[$i][$j]);

                // 將食譜名稱放入搜尋圖片條件
                $pri_rule = '/img alt="' . trim($rule_name) . '" data-echo="([^<>]+)" class="img-responsive"/';
                preg_match_all($pri_rule, $data, $recipe_picture);

                if ($recipe_picture[1] == null) {
                    $pri_rule = '/img alt="' . $rule_name . '" data-echo="([^<>]+)" class="img-responsive"/';
                    preg_match_all($pri_rule, $data, $recipe_picture);
                }

                // 將ID及名稱放入變數
                $temp_id = $recipe_id[$i][$j];
                $temp_name = $recipe_name[$i][$j];
                $temp_picture = $recipe_picture[1][0];

                // 將食譜資料寫入資料庫\

                $IfInsert = TRUE;
                $beforeInsert = "select count(`recipe_id`) as recipeCNT from `recipe_new` where recipe_id = $temp_id";
                $IsInsert = $db->query($beforeInsert);
                foreach ($IsInsert->fetchAll() as $row) {
                    if ($row['recipeCNT'] > 0) {
                        $IfInsert = FALSE;
                    }
                }
                if ($IfInsert) {
                    $insertSql = "INSERT INTO `recipe_new`(`recipe_name`, `recipe_id`, `recipe_img`,`recipe_unit`) VALUES ('$temp_name','$temp_id','$temp_picture',0)";
                    $db->exec($insertSql);
                }
                $sql = "INSERT INTO `recipe_ingredients`(`recipe_id`,`ingredients_id`) VALUES('$temp_id','$id')";
                $db->exec($sql);
            }
        }
    }
    /* 帶ID去資料庫找食譜 */
    $Querysql = "SELECT rn.`recipe_id`, `recipe_name`, `recipe_img` FROM `recipe_new` rn join `recipe_ingredients` ri on rn.`recipe_id` = ri.`recipe_id`  where `ingredients_id` = '$id' and `recipe_name` <> ''";
    $Queryresult = $db->query($Querysql);
    foreach ($Queryresult->fetchAll() as $data) {
        // 將資料塞進array中
        ArrayFormat($data['recipe_name'], $data['recipe_id'], $data['recipe_img'], $recipe_array);
    }
// 如果沒有食譜, 傳空字串過去
    if (count($recipe_array) == 0) {
        ArrayFormat('', '', '', $recipe_array);
    }
}



echo urldecode(json_encode($recipe_array));

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
    $str = str_replace("\n", '', $str);
    $str = str_replace("\r", '', $str);
    $str = str_replace("\r\n", '', $str);
    return $str;
}

?>