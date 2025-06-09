<?php
/**
 * Clase para manejar la internacionalización del plugin
 */
class Rezo_I18n {
    
    private static $instance = null;
    private $translations = array();
    private $language_file = '';
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->language_file = REZO_PLUGIN_PATH . 'languages/es_default.json';
        $this->load_translations();
    }
    
    /**
     * Obtener instancia (Singleton)
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Cargar traducciones desde el archivo
     */
    public function load_translations() {
        if (file_exists($this->language_file)) {
            $json_content = file_get_contents($this->language_file);
            $this->translations = json_decode($json_content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->translations = array();
                error_log('Error al decodificar el archivo de idioma: ' . json_last_error_msg());
            }
        } else {
            // Si no existe el archivo, crear uno por defecto
            $this->create_default_language_file();
        }
    }
    
    /**
     * Crear archivo de idioma por defecto
     */
    private function create_default_language_file() {
        if (!file_exists(dirname($this->language_file))) {
            mkdir(dirname($this->language_file), 0755, true);
        }
        
        // Contenido por defecto (vacío)
        $default_content = json_encode(array(
            'frontend' => array(),
            'backend' => array()
        ), JSON_PRETTY_PRINT);
        
        file_put_contents($this->language_file, $default_content);
        $this->translations = json_decode($default_content, true);
    }
    
    /**
     * Guardar traducciones en el archivo
     */
    public function save_translations($translations) {
        $this->translations = $translations;
        $json_content = json_encode($translations, JSON_PRETTY_PRINT);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            return file_put_contents($this->language_file, $json_content) !== false;
        }
        
        return false;
    }
    
    /**
     * Obtener una traducción
     */
    public function get($section, $key, $default = '') {
        if (isset($this->translations[$section][$key])) {
            return $this->translations[$section][$key];
        }
        
        return $default ?: $key;
    }
    
    /**
     * Obtener todas las traducciones
     */
    public function get_all() {
        return $this->translations;
    }
    
    /**
     * Obtener traducciones de una sección
     */
    public function get_section($section) {
        return isset($this->translations[$section]) ? $this->translations[$section] : array();
    }
}
