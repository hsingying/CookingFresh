<?php

error_reporting(0);
header("Content-Type:text/html; charset=utf-8");

// 連結資料庫
require_once 'dbconnect.php';
require_once 'Curl.php';
$db = dbconnect::init();

$id = $_GET['id'];

// 食材、步驟的陣列
$ingredient = array();
$step_array = array();
$exception = array();

/* 先去資料庫找 */
$selectStepSql = "SELECT `step_seq`, `step_content`, `step_img` FROM `steps` WHERE recipe_id = '$id'";
$result = $db->query($selectStepSql);
foreach ($result->fetchAll() as $data) {
    // 將資料塞進array中
    StepArrayFormat($data['step_seq'], $data['step_content'], $data['step_img'], $step_array);
}
$selectRecipe_IngredientsSql = "SELECT DISTINCT(`recipe_detail_name`),`recipe_detail_qty`,`recipe_detail_unit` FROM `recipe_detail` WHERE recipe_id = '$id'";
$selectResult = $db->query($selectRecipe_IngredientsSql);
foreach ($selectResult->fetchAll() as $data) {
    // 將資料塞進array中
    if ($data['recipe_detail_qty'] == 0) {
        ExceptionArrayFormat($data['recipe_detail_name'], $data['recipe_detail_unit'], $exception);
    } else {
        IngredientArrayFormat($data['recipe_detail_name'], $data['recipe_detail_qty'], $data['recipe_detail_unit'], $ingredient);
    }
}
//var_dump($ingredient);
//var_dump($step_array);
// 判斷若資料庫找不到資料，就到icook爬資料回來
if (count($ingredient) == 0 && count($step_array) == 0) {
    /* 爬蟲 */

    // 食譜ID串在網址
    $url = "https://icook.tw/recipes/";
    $a = $url . $id;
    $curl = new Curl($a);
    $data = $curl->getContent($a);

    /*
      用來作為依據的tag內容
      烹煮時間 -> <span class="time">
      食材 -> <span class="pull-left ingredient-name">
      份量 -> <span class="pull-right ingredient-unit">
      步驟 -> <div class="steps" id = "steps_~~~~"><div class="media"><div class="media-body"><big>~</big>~~~</div></div></div>
     */

    // 食材
    preg_match_all('/<span class="pull-left ingredient-name">([^<>]+)<\/span>/', $data, $ingredient_name);

    // 份量
    preg_match_all('/<span class="pull-right ingredient-unit">([^<>]+)<\/span>/', $data, $ingredient_unit);

    // 步驟
    preg_match_all('/<\/big><!--([^<>]+)-->([^<>]+)<!--([^<>]+)--><\/div>/', $data, $steps);

    //人份量
    preg_match_all('/<span itemprop="recipeYield" class="portions">份量<span><b>([^<>]+)<\/b>/', $data, $recipe_unit);
    //var_dump($recipe_unit);
    $recipe_num = $recipe_unit[1][0];
    if (!$recipe_num >= 1) {
        $recipe_num = 1;
    }
    $UpdateSql = "UPDATE `recipe_new` SET `recipe_unit`= $recipe_num WHERE `recipe_id` = '$id'";
    $db->exec($UpdateSql);
    //echo $recipe_num;
    // 整理食材名稱及數量
    for ($i = 1; $i < count($ingredient_name); $i++) {
        for ($j = 0; $j < count($ingredient_name[$i]); $j++) {
            $temp_name = $ingredient_name[$i][$j];
            $temp_unit = $ingredient_unit[$i][$j];

            // 處理特殊的分量( ex: 半個, 1/2個, g, ml ...等)
            $unit_array = FormatUnit($temp_unit);
            $qty = $unit_array['Qty'];
            $unit = $unit_array['Unit'];
            //echo $qty.'<br>'.$unit;
            // 將食材資料整理至陣列
            if ($qty == 0) {
                ExceptionArrayFormat($temp_name, $unit, $exception);
            } else {
                IngredientArrayFormat($temp_name, $qty, $unit, $ingredient);
            }

            // 將需要的食材資料輸入資料庫
            $insertSql = "INSERT INTO `recipe_detail`(`recipe_id`, `recipe_detail_name`, `recipe_detail_qty`, `recipe_detail_unit`) VALUES ('$id', '$temp_name','$qty', '$unit')";
            $db->exec($insertSql);
        }
    }

    // 整理食譜步驟
    for ($i = 0; $i < 1; $i++) {
        for ($j = 0; $j < count($steps[$i]); $j++) {
            $temp_rule = RuleFormat($steps[2][$j]);

            // 步驟圖片尋找規則 => 將步驟內容串入規則內, 以確保是此步驟之圖片
            $pri_rule = '/data-strip-caption="' . trim($temp_rule) . '" href="([^<>]+)">/';
            preg_match_all($pri_rule, $data, $step_picture);


            $temp_seq = $j + 1;
            $temp_content = Format(trim($steps[$i][$j]));
            $temp_picture = $step_picture[1][0];

            // 將步驟資料整理至陣列
            StepArrayFormat($temp_seq, $temp_content, $temp_picture, $step_array);
            //echo $temp_seq." ".$temp_content." ".$temp_picture."<br/>";
            // 將料理製作步驟輸入資料庫
            $insertSql = "INSERT INTO `steps`(`recipe_id`, `step_seq`, `step_content`, `step_img`) VALUES ('$id','$temp_seq','$temp_content','$temp_picture')";
            $db->exec($insertSql);
            //echo $steps[$i][$j]."<br/>";
        }
    }
}

// 食譜陣列
$recipe_array = array();

// 去資料庫尋找食譜主要資料(因為食材名稱可能重複 所以加上DISTINCT 避免重複抓取)
$selectRecipeSql = "SELECT DISTINCT (`recipe_id`), `recipe_name`, `recipe_img`, `recipe_unit` FROM `recipe_new` WHERE `recipe_id` = '$id'";
$recipeResult = $db->query($selectRecipeSql);
foreach ($recipeResult->fetchAll() as $data) {
    $data['recipe_name'] = str_replace(' │ ', '', $data['recipe_name']);
    //$result_recipe_name = filterEmoji($data['recipe_name']);
    $temp = array('RecipeName' => urlencode($data['recipe_name']), 'RecipeID' => $data['recipe_id'], 'RecipePicture' => urlencode($data['recipe_img']), 'RecipeUnit' => $data['recipe_unit']);
    array_push($recipe_array, $temp);
}
//確定沒有重複的值
$ingredient = array_unique($ingredient);
/* 整理為JSON格式 */
$return_data = array('description' => $recipe_array, 'ingredient' => $ingredient, 'exception' => $exception, 'step' => $step_array);

//$s=urldecode(json_encode($return_data));

echo urldecode(json_encode($return_data));

/* echo "<html> <script>
  var s = JSON.parse('$s');
  console.log(s);
  </script></html>"; */



/* 陣列整理function */

// 食材整理
function IngredientArrayFormat($name, $qty, $unit, &$ingredient) {
    $temp = array('IngredientName' => urlencode($name), 'IngredientQty' => $qty, 'IngredientUnit' => urlencode($unit));
    array_push($ingredient, $temp);
}

// 步驟整理
function StepArrayFormat($seq, $content, $picture, &$step_array) {
    // 將步驟順序, 內容, 圖片依序放入
    $temp = array('StepSeq' => $seq, 'StepContent' => urlencode($content), 'StepPicture' => urlencode($picture));
    array_push($step_array, $temp);
}

// 例外處理
function ExceptionArrayFormat($name, $unit, &$exception) {
    $temp = array('IngredientName' => urlencode($name), 'IngredientUnit' => urlencode($unit));
    array_push($exception, $temp);
}

// 幫括號加上反斜線, 以正常找尋關鍵字
function RuleFormat($str) {
    $str = str_replace('(', '\(', $str);
    $str = str_replace(')', '\)', $str);
    $str = str_replace('[', '\[', $str);
    $str = str_replace(']', '\]', $str);
    $str = str_replace('{', '\{', $str);
    $str = str_replace('}', '\}', $str);
    $str = str_replace('<!--', '', $str);
    $str = str_replace('-->', '', $str);
    $str = str_replace("\n", '', $str);
    $str = str_replace("\r", '', $str);
    $str = str_replace("\r\n", '', $str);
    $str = str_replace(" ", '', $str);
    return $str;
}

function Format($str) {
    $str = str_replace("\n", '', $str);
    $str = str_replace("\r", '', $str);
    $str = str_replace("\r\n", '', $str);
    $str = str_replace('<!--', '', $str);
    $str = str_replace('-->', '', $str);
    $str = str_replace('</big>', '', $str);
    $str = str_replace('</div>', '', $str);
    $str = str_replace(" ", '', $str);
    return $str;
}

// 單位及數量處理
function FormatUnit($temp_unit) {

    $temp_unit = str_replace('半', '0.5', $temp_unit);
    $temp_unit = str_replace('~', '-', $temp_unit);
    $temp_unit = str_replace('～', '-', $temp_unit);
    $temp_unit = str_replace('－', '-', $temp_unit);
    $temp_unit = str_replace('到', '0.5', $temp_unit);
    $temp_unit = str_replace('分之', '/', $temp_unit);
    $temp_unit = str_replace('EL', '湯匙', $temp_unit);
    $temp_unit = str_replace('TL', '茶匙', $temp_unit);
    $temp_unit = str_replace('g', '克', $temp_unit);
    $temp_unit = str_replace('ml', '毫升', $temp_unit);
    $temp_unit = str_replace('c.c.', '毫升', $temp_unit);
    $temp_unit = str_replace('一', '1', $temp_unit);
    $temp_unit = str_replace('二', '2', $temp_unit);
    $temp_unit = str_replace('兩', '2', $temp_unit);
    $temp_unit = str_replace('三', '3', $temp_unit);
    $temp_unit = str_replace('四', '4', $temp_unit);
    $temp_unit = str_replace('五', '5', $temp_unit);
    $temp_unit = str_replace('六', '6', $temp_unit);
    $temp_unit = str_replace('七', '7', $temp_unit);
    $temp_unit = str_replace('八', '8', $temp_unit);
    $temp_unit = str_replace('九', '9', $temp_unit);
    $temp_unit = str_replace('十', '10', $temp_unit);




    if (!stristr($temp_unit, '/') === false) {
        $a = strstr($temp_unit, '/');
        $b = strstr($temp_unit, '/', true);
        $a = str_replace('/', '', $a);
        $a*=1;
        $str = $b . '/' . $a;
        $temp_number = round($b / $a, 1);
        $temp_unit = str_replace($str, '', $temp_unit);
    } else if (!stristr($temp_unit, '-') === false) {
        $temp_str = explode('-', $temp_unit);
        $temp_unit = $temp_str[1];
        $temp_number = $temp_unit * 1;
        if ($temp_number != 0) {
            $temp_unit = str_replace($temp_number, '', $temp_unit);
        }
    } else {
        $temp_number = $temp_unit * 1;
        if ($temp_number != 0) {
            $temp_unit = str_replace($temp_number, '', $temp_unit);
        }
    }

    $unit = array('Qty' => $temp_number, 'Unit' => $temp_unit);
    return $unit;
}

//排出emoji(尚未測試)
function filterEmoji($str) {

    $str = preg_replace_callback(
            '/./u', function (array $match) {
        return strlen($match[0]) >= 4 ? '' : $match[0];
    }, $str);

    return $str;
}

?> 