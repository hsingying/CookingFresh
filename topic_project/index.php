<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        /* 雞肉*/
       $url="http://data.coa.gov.tw/Service/OpenData/FromM/PoultryTransBoiledChickenData.aspx";
       $data = file_get_contents($url);
       $data = json_decode($data,true);
       foreach($data as $value){
           echo $value['日期']." ".$value['白肉雞(1.75-1.95Kg)']."<br/>" ;
       }
        ?>
        
    </body>
</html>
