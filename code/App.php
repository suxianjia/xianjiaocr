<?php 
namespace xianjiaocr;
class App {
    private $tableName = '';
    private  $contentName= '';
    private  $idName= '';

    private $logmodel = 'file';
    public function __construct($tableName, $contentName, $idName,$logModel) {
        $this->tableName =  $tableName;
        $this->contentName=     $contentName;
        $this->idName=      $idName;
        $this->logmodel =  $logModel;
    }

   

// 重新保存文章内容到数据库中 
    private function reSaveArticleToDatabase(string $articleContent,int $articleId,myDatabase $myDatabase): array {
        $results = [ 'code' => 500, 'msg' => 'Failed ', 'data' => null ];
        $mysqli = $myDatabase->getConnection();
        try {
                $stmt = $mysqli->prepare("UPDATE ypc_news_base SET article_content = ? WHERE article_id = ?");
                if (!$stmt) {
                    throw new Exception("Error Processing Request", 1);
                }
    //    print_r(   ['articleContent'=>$articleContent ]  ) ;
                // echo "<p> --->".var_export(  ['articleContent'=>$articleContent ] )."     </p> " .PHP_EOL ;
                // echo "<p> --->UPDATE ：： ".var_export(  [ 'articleId'=>$articleId ] )."     </p> " .PHP_EOL ;
                $stmt->bind_param('si', $articleContent, $articleId);
                $res =  $stmt->execute(); // prepare execute
                if ( ! $res) {
                    throw new Exception("Error Processing Request", 1);
                }
                $results = ['code' => 200, 'msg' => 'Article resaved successfully.', 'data' => ['id'=> $articleId]];
                $stmt->close();  
                unset(  $res);

        } catch (Exception $e) {
            $results = ['code' => 500, 'msg' => 'Database error: ' . $e->getMessage(), 'data' => null];
        }
        return $results;
    }

    public function processAllArticles(OCRClient $ocrClient, myDatabase $myDatabase): array {


        $results = ['code' => 500, 'msg' => 'Failed', 'data' => null];
        try {
            $mysqli = $myDatabase->getConnection();

            $pageSize = 1000; // Number of articles per page
            $pageCount = $mysqli->query("SELECT COUNT(*) FROM `{$this->tableName}` ")->fetch_row()[0]; // Total number of articles
            $maxPages = ceil($pageCount / $pageSize); // Calculate total pages

            for ($currentPage = 0; $currentPage < $maxPages; $currentPage++) {
                $offset = $currentPage * $pageSize; // Calculate offset for the current page
                $stmt = $mysqli->prepare("SELECT `{$this->idName}` AS `id`, `{$this->contentName}` AS `content` FROM `{$this->tableName}` ORDER BY  `{$this->idName}` DESC LIMIT ? OFFSET ?");

                if (!$stmt) {
                    throw new Exception('Failed to prepare the database statement.');
                }

                $stmt->bind_param('ii', $pageSize, $offset);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    $stmt->close();
                    continue;
                }

                while ($row = $result->fetch_assoc()) {
                    $articleId = (int) $row['id'];
                    $articleContent = $row['content'];
            
         

                    preg_match_all('/<img(?![^>]*ocr-data)[^>]+src="([^"]+)"/i', $articleContent, $matches); // 不能包含 ocr-data 的属性 
                    //          preg_match_all('/<img[^>]+src="([^"]+)"/i', $articleContent, $matches);
                    $imagePaths = $matches[1] ?? [];
 
                    foreach ($imagePaths as $imagePath) {
                        $ocrDatatext = '';
                        //ocr-data=\"isocred\"  


               
                        // echo "<p> --->   imagePath:  $imagePath </p> " .PHP_EOL ;
                        $ocrResult = $ocrClient->processImage($imagePath);
                        // echo "<p> --->ocrResult ：： ".var_export( $ocrResult  )."    </p> " .PHP_EOL ;
                    //  var_dump(   $ocrResult); exit;
                        if ($ocrResult['code'] === 200 && isset($ocrResult['data'])) {
                            // $ocrData = json_encode($ocrResult['data'], JSON_UNESCAPED_UNICODE);
                            $ocrData = $ocrResult['data'] ;
               

                            $ocrDatatext = isset($ocrData['text']) ? $ocrData['text'] : '';
                            $ocrDatatext = "<font class='ocr-data' style='color:white; opacity:0;' >{$ocrDatatext}</font>";  
                            $articleContent = preg_replace(
                                '/(<img[^>]+src="' . preg_quote($imagePath, '/') . '"[^>]*>)/i',
                                '$1' . $ocrDatatext,
                                $articleContent
                            );
                            // img 图片标签 src 属性 后面添加 新插入 ocr-data="isocred" 属性  
                            $articleContent = preg_replace(
                                '/(<img[^>]+src="' . preg_quote($imagePath, '/') . '"[^>]*)(>)/i',
                                '$1 ocr-data="isocred"$2',
                                $articleContent
                            );

                           
                        }  else{
                             echo "|---> ocrResult  error :  {$ocrResult['code']}  --  {$ocrResult['msg']}  ".PHP_EOL;
                        }
                        echo "|---> articleId = $articleId  --   imagePath =  $imagePath --  ocrDatatext   =  $ocrDatatext  " .PHP_EOL ;
                       $log_result = myLog::writeLog($this->logmodel, $articleId, $this->idName, $this->contentName, $this->tableName, $imagePath, $ocrDatatext, $myDatabase);
                    //    var_dump(   $log_result ) ;
 
                    }

                    $saveResult = $this->reSaveArticleToDatabase($articleContent, $articleId, $myDatabase);
                    // echo "<p> --->saveResult ：： ".var_export( $saveResult  )."  </p> " .PHP_EOL ;

                    if ($saveResult['code'] !== 200) {
                        throw new Exception("Failed to update article ID {$articleId}: " . $saveResult['msg']);
                    }  
                        $results = ['code' => 200, 'msg' => "Successfully updated article ID {$articleId}.", 'data' => $articleId];
                     
                }

                $stmt->close();
            }
        } catch (Exception $e) {
            $results = ['code' => 500, 'msg' => 'Error: ' . $e->getMessage(), 'data' => null];
        } finally {
            $myDatabase->close();
        }
        return $results;
    }



}
