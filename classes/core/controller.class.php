<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * 
 */
abstract class Controller extends Component{
    
    const MAX_REDIRECTIONS = 3;
    
    /**
     * @var int
     */
    private $_redirections = 0;
    
    //private $_appName;
    
    /**
     * @param \CodersApp $app
     */
    protected function __construct( ) {
        
        //$this->_appName = $app->endPointName();

    }
    /**
     * @return boolean
     */
    private final function importRenderer(){
        
        $renderer = '\CODERS\Framework\Views\Renderer';
        
        if( class_exists($renderer)){
            
            return TRUE;
        }
        else{

            require_once(sprintf('%s/classes/core/renderer.class.php',CODERS_FRAMEWORK_BASE));
            
            return class_exists($renderer);
        }
    }
    /**
     * @param string $view
     * @return \CODERS\Framework\Views\Renderer | boolean
     */
    protected function renderer( $view = 'main' ){
       
        $app = \CodersApp::current();

        if ($app !== FALSE) {

            return $app->createDocument();
        }

        return FALSE;
    }
    /**
     * @param \CODERS\Framework\IModel $content
     */
    protected function json( IModel $content ){
        
        json_encode( $content->toArray() );
    }
    
    /**
     * Ejecuta el controlador
     * @param \CODERS\Framework\Request $request
     * @return bool
     */
    public function __execute( Request $request ){
        
        $action = sprintf('%s_action', $request->action());
        
        if(method_exists($this, $action)){
            
            return $this->$action( $request );
        }
        
        TripManLogProvider::error(
                TripManStringProvider::__('Opci&oacute;n inv&aacute;lida'),
                $this);
        
        return $this->error_action($request);
    }
    /**
     * @return string
     */
    public function __toString() {
        return \CodersApp::nominalize(parent::__toString());
    }
    /**
     * Genera un error (visual o redirigido a un log)
     * @param \CODERS\Framework\Request $request
     * @return boolean
     */
    protected function error_action( Request $request ){
        
        var_dump($request);
        
        return FALSE;
    }
    /**
     * Acción por defecto del controlador
     */
    abstract protected function default_action( Request $request );
    /**
     * Carga un controlador. Retorna un controlador de error si no se ha encontrado el deseado
     * @param string $app Controlador a cargar
     * @param string $context 
     * @param boolean $admin
     * @return \CODERS\Framework\Controller | boolean
     */
    public static final function create( $app , $context , $admin = FALSE ){
        
        $instance = \CodersApp::instance($app);
        
        if( $instance !== FALSE ){

            $path = sprintf('%s/%s/controllers/%s.controller.php', $instance->appPath(),
                    //select administrator or public module
                    $admin ? 'admin' : 'public' , $context);
            
            $class = sprintf('\CODERS\Framework\Controllers\%sController',$context);
            
            if(file_exists($path)){
                
                require_once $path;
                
                if(class_exists($class) && is_subclass_of($class, \CODERS\Framework\Controller::class, TRUE ) ){
                    return new $class( $instance );
                }
            }
        }
        
        return FALSE;
    }
    /**
     * Redirige un controlador a otro (mucho ojo a las redirecciones, máximo 3)
     * @param \\CODERS\Framework\Request $request
     * @return \TripManController
     */
    public function redirect( Request  $request ){
        if( $this->_redirections < self::MAX_REDIRECTIONS ){
            return self::loadController($request->getContext());
        }
        return $this;
    }
}


