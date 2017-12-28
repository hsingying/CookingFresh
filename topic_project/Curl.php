<?php

class Curl {

    public function getContent($url) {
        $timeout = 30;

        // 瀏覽器設定

        $useragent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36";
        $cookie = "cookieLangId=zh_tw;";

        // 初始化curl

        $ch = curl_init();

        // 設定抓取網址

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // 逾時時間

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);

        // 抓取網頁內容

        return curl_exec($ch);
    }

}

?>
