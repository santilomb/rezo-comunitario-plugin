<?php
// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die('Acceso denegado');
}

// Obtener instancia de la clase de traducciones
$i18n = Rezo_I18n::get_instance();

// Procesar formulario
if (isset($_POST['rezo_save_translations']) && wp_verify_nonce($_POST['_wpnonce'], 'rezo_save_translations')) {
    $translations = array(
        'frontend' => isset($_POST['frontend']) ? $_POST['frontend'] : array(),
        'backend' => isset($_POST['backend']) ? $_POST['backend'] : array()
    );
    
    if ($i18n->save_translations($translations)) {
        echo '<div class="notice notice-success"><p>Configuración de idioma guardada exitosamente.</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Error al guardar la configuración de idioma.</p></div>';
    }
    
    // Recargar traducciones
    $i18n->load_translations();
}

// Obtener traducciones actuales
$translations = $i18n->get_all();
$frontend_translations = isset($translations['frontend']) ? $translations['frontend'] : array();
$backend_translations = isset($translations['backend']) ? $translations['backend'] : array();
?>

<div class="wrap">
    <h1>Configuración de Idioma</h1>
    
    <div class="rezo-idioma-container">
        <form method="post" action="">
            <?php wp_nonce_field('rezo_save_translations'); ?>
            <input type="hidden" name="rezo_save_translations" value="1">
            
            <div class="rezo-tabs">
                <button type="button" class="rezo-tab active" data-tab="frontend">Textos del Frontend</button>
                <button type="button" class="rezo-tab" data-tab="backend">Textos del Backend</button>
            </div>
            
            <div class="rezo-tab-content active" id="tab-frontend">
                <div class="rezo-translation-section">
                    <h2>Textos que aparecen en el frontend (parte pública)</h2>
                    <p class="description">Estos textos se muestran a los usuarios cuando visitan las páginas de intenciones.</p>
                    
                    <table class="form-table rezo-translation-table">
                        <thead>
                            <tr>
                                <th>Clave</th>
                                <th>Texto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($frontend_translations as $key => $value): ?>
                                <tr>
                                    <td>
                                        <label for="frontend_<?php echo esc_attr($key); ?>"><?php echo esc_html($key); ?></label>
                                    </td>
                                    <td>
                                        <input type="text" 
                                               name="frontend[<?php echo esc_attr($key); ?>]" 
                                               id="frontend_<?php echo esc_attr($key); ?>" 
                                               value="<?php echo esc_attr($value); ?>" 
                                               class="regular-text">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="rezo-tab-content" id="tab-backend">
                <div class="rezo-translation-section">
                    <h2>Textos que aparecen en el backend (administración)</h2>
                    <p class="description">Estos textos se muestran en el panel de administración.</p>
                    
                    <table class="form-table rezo-translation-table">
                        <thead>
                            <tr>
                                <th>Clave</th>
                                <th>Texto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backend_translations as $key => $value): ?>
                                <tr>
                                    <td>
                                        <label for="backend_<?php echo esc_attr($key); ?>"><?php echo esc_html($key); ?></label>
                                    </td>
                                    <td>
                                        <input type="text" 
                                               name="backend[<?php echo esc_attr($key); ?>]" 
                                               id="backend_<?php echo esc_attr($key); ?>" 
                                               value="<?php echo esc_attr($value); ?>" 
                                               class="regular-text">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="rezo-translation-actions">
                <p class="submit">
                    <input type="submit" class="button-primary" value="Guardar Configuración">
                    <button type="button" class="button" id="rezo-reset-translations">Restaurar Valores Predeterminados</button>
                </p>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Cambiar entre pestañas
    $('.rezo-tab').on('click', function() {
        var tabId = $(this).data('tab');
        
        // Activar pestaña
        $('.rezo-tab').removeClass('active');
        $(this).addClass('active');
        
        // Mostrar contenido
        $('.rezo-tab-content').removeClass('active');
        $('#tab-' + tabId).addClass('active');
    });
    
    // Restaurar valores predeterminados
    $('#rezo-reset-translations').on('click', function(e) {
        e.preventDefault();
        
        if (confirm('¿Estás seguro de que deseas restaurar todos los textos a sus valores predeterminados? Esta acción no se puede deshacer.')) {
            window.location.href = '<?php echo admin_url('admin.php?page=rezo-comunitario-idioma&reset=1'); ?>';
        }
    });
});
</script>

<style>
.rezo-idioma-container {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    margin-top: 20px;
}

.rezo-tabs {
    display: flex;
    border-bottom: 1px solid #ddd;
    margin-bottom: 20px;
}

.rezo-tab {
    padding: 10px 20px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-weight: 500;
    color: #666;
}

.rezo-tab:hover {
    color: #000;
}

.rezo-tab.active {
    border-bottom-color: #17a2b8;
    color: #17a2b8;
}

.rezo-tab-content {
    display: none;
}

.rezo-tab-content.active {
    display: block;
}

.rezo-translation-section {
    margin-bottom: 30px;
}

.rezo-translation-table {
    border-collapse: collapse;
    width: 100%;
}

.rezo-translation-table th {
    text-align: left;
    padding: 10px;
    background: #f8f9fa;
}

.rezo-translation-table td {
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.rezo-translation-table input {
    width: 100%;
}

.rezo-translation-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}
</style>
