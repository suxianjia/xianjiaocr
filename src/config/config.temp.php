<?php


return [

    'version' => "v1.0.1"


    'db' => [
        'host' => 'DB_HOST_MASTER',
        'port' => 'DB_PORT_MASTER',
        'database' => 'DB_DATABASE_MASTER',
        'username' => 'DB_USERNAME_MASTER',
        'password' => 'DB_PASSWORD_MASTER',
    ],
    'ocr' => [
        'url' => 'OCR_URL',
        
        'token' => 'OCR_TOKEN',
        'model' => 'OCR_MODEL',
        'image_path' => 'OCR_IMAGE_PATH',
        'out_file_path' => 'OCR_OUT_FILE_PATH',
        'response_format' => 'OCR_RESPONSE_FORMAT',
    ],
    'log' => [
        'path' => __DIR__.'/temp',
        'type' => 'mysql',
 
    ],


'modelinfo' => [
    // 'ypc_news_base' , 'article_content','article_id' 
    'table_name' => 'table_name',
    'content_name' => 'content_name',
    'id_name' => 'id_name',
]



];