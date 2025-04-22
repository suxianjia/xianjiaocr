<?php 


return [

   "version" => 'v1.0.0',
   // define("APP_DEBUG" , true  );
   // define("APP_ENV" , "dev"  );
   // define("APP_PATH" , __DIR__."/../"  );
   'app_debug' => true,
   'app_env' => '',//dev,prod
   'app_path' => '',

   'runtime_path' => '', //用户输入  路径 

    //
    // 'user_config_file.php' => time() ,

 
// Database configuration
 'mysql' =>  [
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => ' ',
    'username' => 'root',
    'password' => ' ',
    'ssssssss' => "sssssss",
    "dsn" =>"mysql:host=localhost;dbname=mydatabase",
 ],
// ocr
 'ocr' => [
    'url' => 'https://ai.gitee.com/v1/images/ocr',
    'token' => ' ',
    'model' => 'GOT-OCR2_0',
    'image_path' => __DIR__.'/runtime' ,
    'out_file_path' => __DIR__.'/runtime' ,
    'response_format' => 'text',
 ],
//log
 'log' => [
    'log_path' => __DIR__.'/runtime',
    'log_mode' => 'mysql',
    'runtime_path' => __DIR__.'/runtime',
 
 ],
// modelinfo
 'modelinfo' =>  [
    'table_name' => ' ',
    'content_name' => ' ',
    'id_name' => ' ',
 ],
// Set the table name, content name, and ID name

];