<?php defined('ABSPATH') or die;
/*******************************************************************************
 * Plugin Name: Coders Framework
 * Plugin URI: https://coderstheme.org
 * Description: Framework Prototype
 * Version: 1.0.0
 * Author: Jaume Llopis
 * Author URI: 
 * License: GPLv2 or later
 * Text Domain: coders_framework
 * Class: CodersApp
 * 
 * @author Jaume Llopis <jaume@mnkcoder.com>
 ******************************************************************************/
abstract class CodersApp{
    
    const TYPE_INTERFACES = 0;
    const TYPE_CORE = 100;
    const TYPE_PROVIDERS = 200;
    const TYPE_SERVICES = 300;
    const TYPE_MODELS = 400;
    const TYPE_EXTENSIONS = 500;
    
    /**
     * @author Jaume Llopis <jaume@mnkcoder.com>
     * @var \CodersApp[] Singleton of Instances
     */
    private static $_instance = [];
    /**
     * @var string
     */
    private $_key = '';
    /**
     * @var \CODERS\Framework\HookManager
     */
    private $_hookMgr = null;
    /**
     * Componentes cargados
     * @var array
     */
    private $_components = [
        self::TYPE_INTERFACES => [
            'service',
            'plugin',
            'model',
            'template',
            'widget'],
        self::TYPE_CORE => [
            'component',
            'db',           //wpdb helper
            'hook-manager',
            'dictionary',
            'request',      //inputs
            'controller',
            'renderer',
            'service',
        ],
        self::TYPE_PROVIDERS => [
            
        ],
        self::TYPE_SERVICES => [
            
        ],
        self::TYPE_MODELS => [
            
        ],
        self::TYPE_EXTENSIONS => [
            
        ],
    ];
    /**
     * 
     */
    protected function __construct( $key = '' ) {
        //
        $this->_key = strlen($key) > 3 ? $key : self::appKey(strval($this));
        //
        $this->__initializeFramework()->__hook()->__init();
    }
    /**
     * @return string
     */
    public final function __toString() {
        
        $class = get_class($this);
        
        if(substr($class, strlen($class)-3) === 'App'){
            
            $class = substr($class, 0, strlen($class)-3);
        }
        
        return self::nominalize($class);
    }
    /**
     * Ruta local de contenido de la aplicación
     * @return string
     */
    public final function appPath(){
        
        // within either sub or parent class in a static method
        $ref = new ReflectionClass(get_called_class());
        // within either sub or parent class, provided the instance is a sub class
        //$ref = new \ReflectionObject($this);
        // filename
        return dirname( $ref->getFileName() );

        /*return sprintf('%s/../coders-%s/',
                plugin_dir_path(__FILE__) ,
                self::nominalize( self::appName() ) );*/
    }
    /**
     * @return string
     */
    public final function appName(){
        return strval($this);
        //$application = strval($this);
        //$application = self::nominalize( get_called_class() );
        //return $application;
        /*if(substr($application, 0,6) === 'Coders'){
            $to = strrpos($application, 'App');
            if( $to > 6 ){
                return substr($application, 6, $to - 6 ) ;
            }
        }
        return '';*/
    }
 
    /**
     * Ruta URL de contenido de la aplicación
     * @return string
     */
    public final function appURL( ){
        
        return preg_replace( '/coders-framework/',
                $this->appName(),
                plugin_dir_url(__FILE__) );
    }
    /**
     * @return CodersApp
     */
    private final function __initializeFramework(){
        foreach( $this->_components as $type => $list ){
            foreach( $list as $member ){
                
                $path = self::componentPath($member, $type);

                if( $path !== FALSE && file_exists($path)){
                    
                    require_once $path;
                        
                    $class = self::componentClass($member, $type);
                    
                    if( $class !== FALSE ){
                        //
                    }
                }
            }
        }
        return $this;
    }
    /**
     * Initializer
     */
    abstract protected function __init();
    /**
     * @param string $view
     * @param boolean $getLocale
     * @return string
     */
    public final function endPoint( $view , $getLocale = FALSE ){
        
        if( $getLocale ){

            return $view;
        }

        $locale = get_locale();

        $translations = array(
            'es-ES' => 'intranet',
            'en-GB' => 'network',
            'en-US' => 'network',
        );

        return array_key_exists($locale, $translations) ? $translations[$locale] : $view;
    }
    /**
     * Esto irá mejor en el renderizador del sistema
     * @param string $view
     */
    public static final function redirect_template( $view = 'default' ){
        
        $path = sprintf('%s/html/%s.template.php',__DIR__,$view);

        if(file_exists($path)){
            require $path;
        }
        else{
            printf('<!-- TEMPLATE_NOT_FOUND[%s] -->',$view);
        }
    }
    /**
     * Cargar gestor de hooks
     * @return \CodersApp
     */
    private final function __hook(){

        if(class_exists('\CODERS\Framework\HookManager')){
    
            $this->_hookMgr = new \CODERS\Framework\HookManager( $this );
        }
        
        return $this;
    }
    /**
     * @return boolean
     */
    public final function hasHooks(){
        
        return !is_null($this->_hookMgr);
    }
    /**
     * @return \CODERS\Framework\HookManager
     */
    public final function hooks(){
        
        return $this->_hookMgr;
    }
    /**
     * @return \CODERS\Framework\Request|boolean
     */
    public function request(){
        
        if(class_exists('\CODERS\Framework\Request')){
            
            return new \CODERS\Framework\Request( strval($this) );
        }
        
        return FALSE;
    }
    /**
     * @return \CODERS\Framework\DB|boolean
     */
    public function db(){
        
        if(class_exists('\CODERS\Framework\DB')){
            return new \CODERS\Framework\DB( $this->_key );
        }
        
        return FALSE;
    }
    /**
     * 
     * @return \CodersApp
     */
    public function response( ){

        if( class_exists('CODERS\Framework\Controller') ){

            $request = $this->request();

            if( $request !== FALSE ){
                try{
                    $context = \CODERS\Framework\Controller::create( $request->context( ) );

                    if( !is_null($context)){

                        if( !$context->__execute( $request ) ){

                            //
                        }
                    }
                }
                catch (Exception $ex) {
                    die( $ex->getMessage());
                }
            }
        }
        
        return $this;
    }
    /**
     * @param string $option
     * @param mixed $default
     * @return mixed
     */
    public final function getOption( $option ,  $default = null ){
        
        $option_key = sprintf('%s_%s', strval($this),$option);
        
        return get_option($option_key, $default);
    }
    /**
     * @param string $option
     * @param mixed $value
     * @param bool $autoload
     * @return boolean
     */
    protected final function setOption( $option , $value ,$autoload = FALSE ){
        
        $option_key = sprintf('%s_%s', strval($this),$option);
        
        return update_option($option_key, $value, $autoload );
    }
    /**
     * Registra un componente del framework
     * @param string $component
     * @param int $type
     * @return \CodersApp
     */
    protected function register( $component , $type = self::TYPE_MODELS ){
        
        if( $type > self::TYPE_CORE ){
            if(array_key_exists($type, $this->_components)
                    && !in_array( $component ,$this->_components[$type]) ){
                $this->_components[ $type ][] = $component;
            }
        }
        
        return $this;
    }
    /**
     * 
     * @param mixed $element
     * @return string
     */
    public static final function nominalize( $element ){
        $class_name =  is_object($element) ? get_class( $element ) : $element;
        if( !is_null($class_name)){
            if(is_string($class_name)){
                $name = explode('\\', $class_name );
                return strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-',  $name[ count($name) - 1 ] ) );
            }
        }
        return $class_name;
    }
    /**
     * 
     * @param mixed $element
     * @return string
     */
    public static final function classify( $element ){
        $chunks = explode('-', $element);
        $output = array();
        foreach( $chunks  as $string ){
            $output[] = strtoupper( substr($string, 0,1) ) . substr($string, 1, strlen($string)-1);
        }
        return implode('', $output);
    }
    /**
     * @author Jaume Llopis <jaume@mnkcoder.com>
     * @param string $application
     * @return \CodersApp
     */
    private static final function __instance( $application , $key ){

        //$path = sprintf('%s/modules/%s/%s.module.php',CODERS_FRAMEWORK_BASE ,$name,$name);
        $path = sprintf('%s/../%s/application.php',__DIR__,$application);
        
        $class = sprintf('%sApp',self::classify($application) );
        
        if(file_exists($path)){
            
            require_once $path;
            
            if(class_exists($class) && is_subclass_of( $class , self::class , TRUE ) ){

                return new $class( $key );
            }
            else{
                throw new Exception(sprintf('INVALID APPLICATION [%s]',$class) );
            }
        }
        else{
            throw new Exception(sprintf('INVALID PATH [%s]',$path) );
            //die(sprintf('INVALID PATH [%s]',$path) );
        }
        
        return NULL;
    }
    /**
     * @param String $component
     * @param int $type
     * @return String|boolean
     */
    protected static final function componentClass( $component , $type = self::TYPE_MODELS ){
        
        switch( $type ){
            case self::TYPE_INTERFACES:
                return sprintf('\CODERS\Framework\I%s', self::classify($component));
            case self::TYPE_CORE:
                return sprintf('\CODERS\Framework\%s', self::classify($component));
            case self::TYPE_PROVIDERS:
                return sprintf('\CODERS\Framework\Providers\%s', self::classify($component));
            case self::TYPE_SERVICES:
                return sprintf('\CODERS\Framework\Services\%s', self::classify($component));
            case self::TYPE_MODELS:
                return sprintf('\CODERS\Framework\Models\%sModel', self::classify($component));
            case self::TYPE_EXTENSIONS:
                return sprintf('\CODERS\Framework\Plugins\%sPlugin', self::classify($component));
        }
        
        return FALSE;
    }
    /**
     * 
     * @param String $component
     * @param int $type
     * @return String | boolean
     */
    protected static final function componentPath( $component , $type = self::TYPE_MODELS ){

        switch( $type ){
            case self::TYPE_INTERFACES:
                return sprintf('%s/classes/interfaces/%s.interface.php',
                        CODERS_FRAMEWORK_BASE,
                        self::nominalize($component));
            case self::TYPE_CORE:
                return sprintf('%s/classes/core/%s.class.php',
                        CODERS_FRAMEWORK_BASE,
                        self::nominalize($component));
            case self::TYPE_PROVIDERS:
                return sprintf('%s/classes/providers/%s.provider.php',
                        CODERS_FRAMEWORK_BASE,
                        self::nominalize($component));
            case self::TYPE_SERVICES:
                return sprintf('%s/classes/services/%s.interface.php',
                        CODERS_FRAMEWORK_BASE,
                        self::nominalize($component));
            case self::TYPE_MODELS:
                return sprintf('%s/classes/models/%s.model.php',
                        CODERS_FRAMEWORK_BASE,
                        self::nominalize($component));
            case self::TYPE_EXTENSIONS:
                return sprintf('%s/classes/plugins/%s.plugin.php',
                        CODERS_FRAMEWORK_BASE,
                        self::nominalize($component));
        }
            
        return FALSE;
    }
    /**
     * Inicialización
     * Cada llamada a esta instancia se realiza solo en el contexto de la
     * petición del usuario sobre una única aplicacion. No es necesario
     * trabajar con diferentes instancias a la vez si tenemos varias aplicaciones
     * sobre este framework. Simplemente, se cargará la aplicación adecuada
     * dentro de su espacio a cada llamada requerida desde el plugin activo.
     * 
     * @author Jaume Llopis <jaume@mnkcoder.com>
     * @return \CodersApp|Boolean
     */
    public static final function instance( $app ){
        
        return strlen($app) && isset(self::$_instance[$app]) ? self::$_instance[ $app ] : FALSE;;
    }
    /**
     * @return array
     */
    public static final function listInstances(){
        return array_keys(self::$_instance);
    }

    /**
     * @param string $app
     * @return string
     */
    private static final function appKey( $app ){
        
        $key = explode('-', $app);
        
        $output = [];

        switch( count($key)){
            case 0:
                return FALSE;
            case 1:
                return substr($key, 0,4);
            case 2:
                for( $k = 0 ; $k < count( $key ) ; $k++ ){
                    $output[] = strtolower( substr($key[$k], 0, 2) );
                }
                break;
            case 3:
                for( $k = 0 ; $k < count( $key ) ; $k++ ){
                    $output[] = strtolower( substr($key[$k],0,$k > 1 ? 2 : 1 ) );
                }
                break;
            default:
                for( $k = 0 ; $k < count( $key ) && $k < 4 ; $k++ ){
                    $output[] = strtolower( substr($key[$k], 0, 1) );
                }
                break;
        }

        
        return implode('', $output);
    }
    /**
     * Inicialización
     * @author Jaume Llopis <jaume@mnkcoder.com>
     * @param string $app
     * @param string $key
     * @return \CodersApp|NULL
     */
    public static function init( $app = '' ){
        
        if( !defined('CODERS_FRAMEWORK_BASE')){

            //first instance to call
            define('CODERS_FRAMEWORK_BASE',__DIR__);
        }
        
        if( strlen($app) && !isset( self::$_instance[$app] ) ){

            $key = self::appKey($app);
            //die($key);
            
            try{
                
                $instance = self::__instance( $app , $key );
                
                if( !is_null($instance)){

                    self::$_instance[ $app ] = $instance;
                    
                    var_dump($instance);
                    die;

                    //define('CODERS_APP', strval( $instance ) );
                }
            }
            catch (Exception $ex) {
                die($ex->getMessage());
            }
        }
        
        return strlen($app) ? self::instance( $app ) : null;
    }
}

/**
 * Inicializar aplicación
 */
CodersApp::init();




