<?php 
//資料庫連線 
class db_connect{
      public static function init() {
          try{
                $db_host = 'db.mis.kuas.edu.tw';
                $db_name = 's1103137212';
                $db_user = 's1103137212';
                $db_paswd = 'h224426122';
                $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8";
                return new PDO($dsn, $db_user, $db_paswd);
          }catch(PDOException $e){
                print "Error!: " . $e->getMessage() . "<br/>";
                die();
            
          }
     
       
     
      }
    }
  ?>