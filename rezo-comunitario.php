<?php
/**
 * Plugin Name: Rezo Comunitario
 * Description: Plugin para gestionar intenciones de rezo comunitario con contadores de Ave Marías
 * Version: 1.0.1
 * Author: Tu Nombre
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes
define('REZO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('REZO_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Incluir archivos necesarios
require_once REZO_PLUGIN_PATH . 'includes/class-rezo-i18n.php';

// Clase principal del plugin
class RezoComunitario {
    
    private $i18n;
    
    public function __construct() {
        // Inicializar internacionalización
        $this->i18n = Rezo_I18n::get_instance();
        
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Crear tablas personalizadas
        $this->create_tables();
        
        // Agregar menú de administración
        add_action('admin_menu', array($this, 'admin_menu'));
        
        // Registrar scripts y estilos
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Registrar shortcodes
        add_shortcode('rezo_intenciones', array($this, 'display_intenciones'));
        add_shortcode('rezo_intencion', array($this, 'display_intencion'));
        
        // AJAX handlers
        add_action('wp_ajax_agregar_rezos', array($this, 'agregar_rezos'));
        add_action('wp_ajax_nopriv_agregar_rezos', array($this, 'agregar_rezos'));
        
        // Manejar reset de traducciones
        if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'rezo-comunitario-idioma' && isset($_GET['reset'])) {
            $this->reset_translations();
        }
    }
    
    // Modificar la función create_tables para asegurar que la columna activa existe
    public function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rezo_intenciones';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion longtext NOT NULL,
            objetivo_avemarias int(11) NOT NULL DEFAULT 0,
            avemarias_actuales int(11) NOT NULL DEFAULT 0,
            activa tinyint(1) NOT NULL DEFAULT 1,
            page_id bigint(20) DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY page_id (page_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function admin_menu() {
        add_menu_page(
            $this->i18n->get('backend', 'menu_principal', 'Rezo Comunitario'),
            $this->i18n->get('backend', 'menu_principal', 'Rezo Comunitario'),
            'manage_options',
            'rezo-comunitario',
            array($this, 'admin_page_lista'),
            'dashicons-heart',
            30
        );
        
        add_submenu_page(
            'rezo-comunitario',
            $this->i18n->get('backend', 'menu_ver', 'Ver Intenciones'),
            $this->i18n->get('backend', 'menu_ver', 'Ver Intenciones'),
            'manage_options',
            'rezo-comunitario',
            array($this, 'admin_page_lista')
        );
        
        add_submenu_page(
            'rezo-comunitario',
            $this->i18n->get('backend', 'menu_agregar', 'Agregar Intención'),
            $this->i18n->get('backend', 'menu_agregar', 'Agregar Intención'),
            'manage_options',
            'rezo-comunitario-agregar',
            array($this, 'admin_page_formulario')
        );
        
        add_submenu_page(
            'rezo-comunitario',
            $this->i18n->get('backend', 'menu_idioma', 'Configuración de Idioma'),
            $this->i18n->get('backend', 'menu_idioma', 'Configuración de Idioma'),
            'manage_options',
            'rezo-comunitario-idioma',
            array($this, 'admin_page_idioma')
        );
    }
    
    public function enqueue_scripts() {
        // Verificar que estamos en una página que usa nuestros shortcodes
        global $post;
        if (is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'rezo_intenciones') || has_shortcode($post->post_content, 'rezo_intencion'))) {
            wp_enqueue_script('rezo-script', REZO_PLUGIN_URL . 'assets/rezo.js', array('jquery'), '1.0.1', true);
            wp_enqueue_style('rezo-style', REZO_PLUGIN_URL . 'assets/rezo.css', array(), '1.0.1');
            
            // Localizar script para AJAX y traducciones
            wp_localize_script('rezo-script', 'rezo_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('rezo_nonce'),
                'i18n' => $this->i18n->get_section('frontend')
            ));
        }
    }
    
    public function admin_enqueue_scripts() {
        wp_enqueue_script('rezo-admin', REZO_PLUGIN_URL . 'assets/admin.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('rezo-admin-style', REZO_PLUGIN_URL . 'assets/admin.css', array(), '1.0.0');
    }
    
    public function admin_page_lista() {
        // Pasar la instancia de i18n a la vista
        $i18n = $this->i18n;
        include REZO_PLUGIN_PATH . 'admin/admin-lista.php';
    }

    public function admin_page_formulario() {
        // Pasar la instancia de i18n a la vista
        $i18n = $this->i18n;
        include REZO_PLUGIN_PATH . 'admin/admin-formulario.php';
    }
    
    public function admin_page_idioma() {
        include REZO_PLUGIN_PATH . 'admin/admin-idioma.php';
    }
    
    // Modificar la función display_intenciones para mostrar solo intenciones activas
    public function display_intenciones($atts) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rezo_intenciones';
        
        $intenciones = $wpdb->get_results("SELECT * FROM $table_name WHERE activa = 1");
        
        // Pasar la instancia de i18n a la vista
        $i18n = $this->i18n;
        
        ob_start();
        include REZO_PLUGIN_PATH . 'templates/lista-intenciones.php';
        return ob_get_clean();
    }
    
    public function display_intencion($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rezo_intenciones';
        
        $intencion = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d AND activa = 1", $atts['id']));
        
        if (!$intencion) {
            return '<p>' . $this->i18n->get('frontend', 'intencion_no_encontrada', 'Intención no encontrada.') . '</p>';
        }
        
        // Pasar la instancia de i18n a la vista
        $i18n = $this->i18n;
        
        ob_start();
        include REZO_PLUGIN_PATH . 'templates/intencion-detalle.php';
        return ob_get_clean();
    }
    
    public function agregar_rezos() {
        // Verificar nonce con la función de WordPress
        check_ajax_referer('rezo_nonce', 'nonce');
        
        $intencion_id = intval($_POST['intencion_id']);
        $cantidad = intval($_POST['cantidad']);
        $captcha_response = sanitize_text_field($_POST['captcha_response']);
        
        // Verificar captcha simple (suma matemática)
        $captcha_resultado = intval($_POST['captcha_resultado']);
        if ($captcha_response != $captcha_resultado) {
            wp_send_json_error('Captcha incorrecto');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rezo_intenciones';

        // Verificar que la intención exista y esté activa
        $intencion = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d AND activa = 1", $intencion_id));
        if (!$intencion) {
            wp_send_json_error('Intención no válida');
        }

        // Actualizar contador
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET avemarias_actuales = avemarias_actuales + %d WHERE id = %d",
            $cantidad,
            $intencion_id
        ));
        
        if ($result !== false) {
            // Obtener datos actualizados
            $intencion = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $intencion_id));
            $porcentaje = ($intencion->avemarias_actuales / $intencion->objetivo_avemarias) * 100;
            
            wp_send_json_success(array(
                'avemarias_actuales' => $intencion->avemarias_actuales,
                'porcentaje' => min(100, $porcentaje)
            ));
        } else {
            wp_send_json_error('Error al actualizar');
        }
    }

    public function crear_pagina_intencion($intencion_id, $titulo) {
        $page_title = 'Rezo Comunitario: ' . $titulo;
        $page_content = '[rezo_intencion id="' . $intencion_id . '"]';
        $page_slug = 'rezo-comunitario-' . sanitize_title($titulo);
        
        // Verificar si ya existe una página con este slug
        $existing_page = get_page_by_path($page_slug);
        if ($existing_page) {
            return $existing_page->ID;
        }
        
        $page_data = array(
            'post_title'    => $page_title,
            'post_content'  => $page_content,
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_name'     => $page_slug,
            'post_author'   => get_current_user_id(),
        );
        
        $page_id = wp_insert_post($page_data);
        
        if ($page_id && !is_wp_error($page_id)) {
            // Guardar la relación entre intención y página
            global $wpdb;
            $table_name = $wpdb->prefix . 'rezo_intenciones';
            $wpdb->update(
                $table_name,
                array('page_id' => $page_id),
                array('id' => $intencion_id)
            );
        }
        
        return $page_id;
    }

    /**
     * Cargar archivos de traducción estándar de WordPress
     */
    public function load_textdomain() {
        load_plugin_textdomain('rezo-comunitario', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Restaurar traducciones a valores predeterminados
     */
    public function reset_translations() {
        // Copiar el archivo de idioma predeterminado
        $default_file = REZO_PLUGIN_PATH . 'languages/es_default.json';
        $current_file = REZO_PLUGIN_PATH . 'languages/es_default.json';
        
        if (file_exists($default_file)) {
            copy($default_file, $current_file);
            
            // Recargar traducciones
            $this->i18n->load_translations();
            
            // Redirigir para evitar recargas
            wp_redirect(admin_url('admin.php?page=rezo-comunitario-idioma&reset_done=1'));
            exit;
        }
    }
    
    public function activate() {
        $this->create_tables();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Función auxiliar para obtener traducciones
     */
    public function t($section, $key, $default = '') {
        return $this->i18n->get($section, $key, $default);
    }
}

// Inicializar plugin
$rezo_comunitario = new RezoComunitario();

// Función global para acceder a traducciones
function rezo_t($section, $key, $default = '') {
    global $rezo_comunitario;
    return $rezo_comunitario->t($section, $key, $default);
}
