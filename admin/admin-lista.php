<?php
global $wpdb;
$table_name = $wpdb->prefix . 'rezo_intenciones';

// Procesar acciones
if (isset($_POST['action'])) {
    if ($_POST['action'] == 'eliminar' && wp_verify_nonce($_POST['_wpnonce'], 'rezo_admin')) {
        $intencion_id = intval($_POST['id']);
        
        // Obtener page_id antes de eliminar
        $intencion = $wpdb->get_row($wpdb->prepare("SELECT page_id FROM $table_name WHERE id = %d", $intencion_id));
        
        // Eliminar la página asociada
        if ($intencion && $intencion->page_id) {
            wp_delete_post($intencion->page_id, true);
        }
        
        // Eliminar la intención
        $wpdb->delete($table_name, array('id' => $intencion_id));
        echo '<div class="notice notice-success"><p>' . $i18n->get('backend', 'exito_eliminar', 'Intención eliminada exitosamente.') . '</p></div>';
    }
    
    if ($_POST['action'] == 'pausar' && wp_verify_nonce($_POST['_wpnonce'], 'rezo_admin')) {
        $intencion = $wpdb->get_row($wpdb->prepare("SELECT activa FROM $table_name WHERE id = %d", intval($_POST['id'])));
        $nuevo_estado = $intencion->activa ? 0 : 1;
        
        $wpdb->update(
            $table_name,
            array('activa' => $nuevo_estado),
            array('id' => intval($_POST['id']))
        );
        
        $mensaje = $nuevo_estado ? 
            $i18n->get('backend', 'exito_activar', 'Intención activada exitosamente.') : 
            $i18n->get('backend', 'exito_pausar', 'Intención pausada exitosamente.');
        
        echo '<div class="notice notice-success"><p>' . $mensaje . '</p></div>';
    }
}

// Obtener todas las intenciones
$intenciones = $wpdb->get_results("SELECT * FROM $table_name ORDER BY activa DESC, fecha_creacion DESC");
?>

<div class="wrap">
    <h1>
        <?php echo $i18n->get('backend', 'titulo_lista', 'Intenciones de Rezo'); ?>
        <a href="<?php echo admin_url('admin.php?page=rezo-comunitario-agregar'); ?>" class="page-title-action">
            <span class="dashicons dashicons-plus-alt"></span> <?php echo $i18n->get('backend', 'btn_nueva', 'Nueva Intención'); ?>
        </a>
    </h1>
    
    <?php if (empty($intenciones)): ?>
        <div class="rezo-empty-state">
            <div class="rezo-empty-content">
                <span class="dashicons dashicons-heart" style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></span>
                <h2><?php echo $i18n->get('backend', 'no_intenciones', 'No hay intenciones creadas'); ?></h2>
                <p><?php echo $i18n->get('backend', 'no_intenciones_desc', 'Comienza creando tu primera intención de rezo comunitario.'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=rezo-comunitario-agregar'); ?>" class="button button-primary button-hero">
                    <span class="dashicons dashicons-plus-alt"></span> <?php echo $i18n->get('backend', 'btn_crear_primera', 'Crear Primera Intención'); ?>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="rezo-intenciones-grid">
            <?php foreach ($intenciones as $intencion): ?>
                <?php $porcentaje = ($intencion->avemarias_actuales / $intencion->objetivo_avemarias) * 100; ?>
                <div class="rezo-intencion-card <?php echo $intencion->activa ? '' : 'pausada'; ?>">
                    <div class="card-header">
                        <h3><?php echo esc_html($intencion->titulo); ?></h3>
                        <span class="estado-badge <?php echo $intencion->activa ? 'activa' : 'pausada'; ?>">
                            <?php echo $intencion->activa ? 
                                $i18n->get('backend', 'estado_activa', 'Activa') : 
                                $i18n->get('backend', 'estado_pausada', 'Pausada'); ?>
                        </span>
                    </div>
                    
                    <div class="card-content">
                        <div class="progress-container">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo min(100, $porcentaje); ?>%"></div>
                            </div>
                            <div class="progress-stats">
                                <span class="porcentaje"><?php echo number_format($porcentaje, 1); ?>%</span>
                                <span class="avemarias"><?php echo number_format($intencion->avemarias_actuales); ?> / <?php echo number_format($intencion->objetivo_avemarias); ?></span>
                            </div>
                        </div>
                        
                        <div class="shortcode-info">
                            <label><?php echo $i18n->get('backend', 'shortcode', 'Shortcode'); ?>:</label>
                            <code>[rezo_intencion id="<?php echo $intencion->id; ?>"]</code>
                        </div>
                    </div>
                    
                    <div class="card-actions">
                        <?php if ($intencion->page_id): ?>
                            <a href="<?php echo get_permalink($intencion->page_id); ?>" class="button button-small" target="_blank" title="<?php echo $i18n->get('backend', 'btn_ver_pagina', 'Ver Página'); ?>">
                                <span class="dashicons dashicons-external"></span>
                            </a>
                        <?php endif; ?>
                        
                        <a href="<?php echo admin_url('admin.php?page=rezo-comunitario-agregar&editar=' . $intencion->id); ?>" class="button button-small" title="<?php echo $i18n->get('backend', 'btn_editar', 'Editar'); ?>">
                            <span class="dashicons dashicons-edit"></span>
                        </a>
                        
                        <form method="post" style="display: inline;">
                            <?php wp_nonce_field('rezo_admin'); ?>
                            <input type="hidden" name="action" value="pausar">
                            <input type="hidden" name="id" value="<?php echo $intencion->id; ?>">
                            <button type="submit" class="button button-small" title="<?php echo $intencion->activa ? 
                                $i18n->get('backend', 'btn_pausar', 'Pausar') : 
                                $i18n->get('backend', 'btn_activar', 'Activar'); ?>">
                                <span class="dashicons dashicons-<?php echo $intencion->activa ? 'pause' : 'controls-play'; ?>"></span>
                            </button>
                        </form>
                        
                        <form method="post" style="display: inline;">
                            <?php wp_nonce_field('rezo_admin'); ?>
                            <input type="hidden" name="action" value="eliminar">
                            <input type="hidden" name="id" value="<?php echo $intencion->id; ?>">
                            <button type="submit" class="button button-small button-link-delete" 
                                    onclick="return confirm('<?php echo $i18n->get('backend', 'confirmar_eliminar', '¿Estás seguro? Esta acción eliminará también la página asociada y no se puede deshacer.'); ?>')"
                                    title="<?php echo $i18n->get('backend', 'btn_eliminar', 'Eliminar'); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
