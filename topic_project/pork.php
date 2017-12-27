<?php
/*豬肉*/
$url="http://data.coa.gov.tw/Service/OpenData/FromM/AnimalTransData.aspx";
       $data = file_get_contents($url);
       $data = json_decode($data,true);
       foreach($data as $value){
           echo $value['交易日期']." ".$value['規格豬(75公斤以上)-平均價格']."<br/>" ;
       }



?>