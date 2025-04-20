<?php
namespace Suxianjia\xianjiaocr\orm;
use PDO;
use PDOException;
//use function typeof;
use Suxianjia\xianjiaocr\myConfig;
use Suxianjia\xianjiaocr\client\myLogClient;
class myPdo
{

    private static string $error = '';
    private static string $message = '';


    private static array $config;

    private static myPdo $instance;
    private static string $sql = '';

    private function __construct()    {    }
    private function __clone()    {    }
    public static function getInstance(): myPdo
    {

        if(!isset(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function createclient(  ): \PDO | null
    {
        $config = self::getConfig();
//var_dump($config ["dbconfig"]  ["connections"]  ["mysql"]  ["dsn"] );


            $dsn = $config ["dbconfig"]  ["connections"]  ["mysql"]  ["dsn"];//'mysql:host=localhost;dbname=mydatabase';
            $username =  $config ["dbconfig"]  ["connections"]  ["mysql"] ["username"];//'username';
            $password =  $config ["dbconfig"]  ["connections"]  ["mysql"] ["password"];//'password';

            try {
                $pdo = new \PDO($dsn, $username, $password);
                    // 设置错误模式为异常
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
//                echo "成功连接到数据库";
                self::setMessages( "成功连接到数据库");
                return $pdo;
            } catch (PDOException $e) {
//                echo "连接数据库失败: " . $e->getMessage();
//                return  ['error' => $e->getMessage()];
                 self::setMessages( $e->getMessage());
                 return null;
            }

    }


 
    // Existing methods and properties

    public static function getcounts($whereStr, $tablename) {
        $client = self::createclient();
        // Example implementation: Adjust this query as per your database schema
        $sql = "SELECT COUNT(*) as count FROM {$tablename} WHERE {$whereStr}";
        self::setSql( $sql  );
        $stmt = $client->query($sql);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
 


    public static  function getdata($offset = 0,$limit = 10,$field = "", $whereStr =  "1 = 1", $tablename = 'account_ad' , $key='id'):array | null
    {
        $client = self::createclient();
        if ($client == null ) { return null;}
        if ($whereStr == '') { $whereStr == '1 = 1';}
        $sql = "SELECT {$field} FROM `{$tablename}` WHERE {$whereStr} ORDER BY `{$key}` DESC LIMIT  :start , :num  ";
        self::setSql( $sql  );
        $stmt = $client->prepare($sql);
        $stmt->bindValue(':start', $offset, \PDO::PARAM_INT);
        $stmt->bindValue(':num',$limit,\PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static  function getMessage( ): string
    {
    return self::$message;
    }
    public static  function getError(): string
    {
        return self::$error;
    }
    public static  function  setMessages( string $message ): void
    {
         self::$message = $message;
    }
    public static  function  setError( string $error ): void
    {
        self::$error = $error;
    }

    public static function setConfig( array $config ): void
    {
        self::$config = $config;
    }
    public static function getConfig(): array
    {
        return self::$config;
    }

    public static function setSql(string $sqlstr = ''): void
    {
        self::$sql = $sqlstr;
    }
    public static function getSql(): string
    {
        return self::$sql;
    }



}