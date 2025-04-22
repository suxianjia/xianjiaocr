<?php 
namespace Suxianjia\xianjiaocr;
use Exception;
 
if (!defined('myAPP_VERSION')) {        exit('myAPP_VERSION is not defined'); }
if (!defined('myAPP_ENV')  ) {          exit ('myAPP_ENV is not defined'); }
if (!defined('myAPP_DEBUG')) {          exit('myAPP_DEBUG is not defined'); }
if (!defined('myAPP_PATH')) {           exit('myAPP_PATH is not defined'); }
if (!defined('myAPP_RUNRIMT_PATH')) {   exit('myAPP_RUNRIMT_PATH is not defined'); }



class myConfig {
    private static $instance = null;

    private static $version = 'v1.0.0';
    private static $app_debug=true;
    private static $app_env = 'dev';//dev,prod
    private static $app_path = __DIR__.'/../../';
    private static $runtime_path =  __DIR__.'/../../runtime'; //用户输入  路径 
 
 
    // private static $runtime_path    = __DIR__ . '/../runtime';  
    private static $runtime_config_file_name = 'runtime_config_file_name.php'; //合并成新的配置表 
    private static $configs = [ //系统配置表 

   
  
 
 

    'mysql' => [
        'host' => '',
        'port' => 0,
        'database' => '',
        'username' => '',
        'password' => '',
    ],
    'ocr' => [
        'url' => '',
        'token' => '',
        'model' => '',
        'image_path' => '',
        'out_file_path' => '',
        'response_format' => '',
    ],
    'log' => [
        // 'log_path' => __DIR__.'/../temp',
        'log_mode' => 'mysql',
        // 'runtime_path' => __DIR__.'/../runtime',

 
    ],

'modelinfo' => [
    // 
    'table_name' => '',
    'content_name' => '',
    'id_name' => '',
]

    ];
    private static $log_file;
 
 
    private function __construct() {
 
    }

    public static function getInstance(): myConfig {
        if (self::$instance === null) {
            self::init();
            self::$instance = new self();
        }
        return self::$instance;
    }

    private static function init(){

 
        self::$version = myAPP_VERSION;
        self::$app_env = myAPP_ENV;
        self::$app_debug = myAPP_DEBUG;
        self::$app_path = myAPP_PATH;
        self::$runtime_path = myAPP_RUNRIMT_PATH;

        self::save(self::$configs);
        self::setUserConfigFile();
    }

 
    public static function getConfigFile(): string {
        return self::$configs['config_file'];
    }

    public function get($key) {
        return self::$configs[$key] ?? null;
    }
    public static function set($key, $value): void {
        $data = self::getAllConfig ();

        $data[$key] = $value;
        self::save($data);

    }

    public static function setAppPath()    {
        self::$app_path = myAPP_PATH;
    }
  
    public static function getAppPath(): string {
        return  self::$app_path;
       
    }
    // myConfig::getInstance()::setRuntimePath(  RUNTIME_PATH  );
    public static function setRuntimePath(string $path = '') {
        self::$runtime_path = myAPP_RUNRIMT_PATH;
    }
    public static function getRuntimePath(): string {
      return  self::$runtime_path;
    }
    // define("APP_DEBUG" , true  );
    public static function setAppDebug(bool $debug = true) {
        self::$app_debug = myAPP_DEBUG;
    }
    public static function getAppDebug(): bool {
        return self::$app_debug;
    }
//  define("APP_ENV" , "dev"  );
public static function setAppEnv(string $env = '') {
    self::$app_env = myAPP_ENV;
}

public static function getAppEnv(): string {
 
        return  self::$app_env;
}
// myConfig::getInstance()::setUserConfigFile(  APP_PATH.'/config.'.APP_ENV.'.php' );
private static function setUserConfigFile(  ){
    $arr = [];
    $arr[0]  = self::getAllConfig ();
    $file  = self::getAppPath().'/config.'.self::getAppEnv().'.php';
    // 用户配置表  文件是否存在 
    if( file_exists(    $file)  ){ 
        $arr[1] = require  $file;
        $allconfigs = array_merge(   $arr[0] ,   $arr[1]);
        self::$configs =  $allconfigs ;
        self::save(self::$configs);
    }
}


    public static function getRuntimeConfigFile(): string {
        if ( is_dir(self::getRuntimePath()) == false ) {
            die ('Failed to create directory: ' . self::getRuntimePath());
        }
        $file = self::getRuntimePath(). '/'. self::$runtime_config_file_name;
        return  $file  ;
    }
    public static function getAllConfig (): array{
        $data = [];
        $file = self::getRuntimeConfigFile();
        if (  file_exists($file) ) {
             $data =  require self::getRuntimeConfigFile();
        }else{
            echo "$file not exist ".PHP_EOL;
        }
        return $data;
    }
  
    public static function save(array $data) {
        try { 
            $data['update_date'] = date('Y-m-d H:i:s', time()) ;
            $configContent = "<?php\n\nreturn " . var_export( $data, true) . ";\n";
            if ( is_dir(self::getRuntimePath()) == false ) {
                die ('Failed to create directory: ' . self::getRuntimePath());
            }
            file_put_contents(self::getRuntimeConfigFile()  , $configContent);
        } catch (Exception $e) {
            throw new Exception('Failed to save configuration: ' . $e->getMessage().'--'. $e->getLine()  );
        }

    } 
    public  static function getDatabaseConfig($type = 'mysql') {
        $data = self::getAllConfig ();
        return $data[$type] ?? [];
    }
    public  static function getOcrConfig($type = 'ocr') {
        $data = self::getAllConfig ();
        return $data[$type] ?? [];
    }
    public static function getLogConfig($type = 'log') {
        $data = self::getAllConfig ();
        return $data[$type] ?? [];
    }
    // modelinfo
    public static function getModelInfoConfig($type = 'modelinfo') {
        $data = self::getAllConfig ();
        return $data[$type] ?? [];
    }

    public static  function getVersion() {
        return self::$version ?? '';
    }
    public static function setVersion($version) {
        self::$version = myAPP_VERSION;
    }
    public static function getLogFilePath() {
        $data['log_file_path'] =   self::$runtime_path = myAPP_RUNRIMT_PATH;
        return  $data['log_file_path'] ?? '';// $log_file 
    }
 
 
      
}