<?php
global $wpdb;
$table_name = $wpdb->prefix . 'rezo_intenciones';

// Procesar formularios
if (isset($_POST['action'])) {
    if ($_POST['action'] == 'crear' && wp_verify_nonce($_POST['_wpnonce'], 'rezo_admin')) {
        $wpdb->insert(
            $table_name,
            array(
                'titulo' => sanitize_text_field($_POST['titulo']),
                'descripcion' => wp_kses_post($_POST['descripcion']),
                'objetivo_avemarias' => intval($_POST['objetivo_avemarias'])
            )
        );
        echo '<div class="notice notice-success"><p>Intención creada exitosamente.</p></div>';
    }
    
    if ($_POST['action'] == 'editar' && wp_verify_nonce($_POST['_wpnonce'], 'rezo_admin')) {
        $wpdb->update(
            $table_name,
            array(
                'titulo' => sanitize_text_field($_POST['titulo']),
                'descripcion' => wp_kses_post($_POST['descripcion']),
                'objetivo_avemarias' => intval($_POST['objetivo_avemarias'])
            ),
            array('id' => intval($_POST['id']))
        );
        echo '<div class="notice notice-success"><p>Intención actualizada exitosamente.</p></div>';
    }
    
    if ($_POST['action'] == 'eliminar' && wp_verify_nonce($_POST['_wpnonce'], 'rezo_admin')) {
        $wpdb->delete(
            $table_name,
            array('id' => intval($_POST['id']))
        );
        echo '<div class="notice notice-success"><p>Intención eliminada exitosamente.</p></div>';
    }
    
    if ($_POST['action'] == 'pausar' && wp_verify_nonce($_POST['_wpnonce'], 'rezo_admin')) {
        $intencion = $wpdb->get_row($wpdb->prepare("SELECT activa FROM $table_name WHERE id = %d", intval($_POST['id'])));
        $nuevo_estado = $intencion->activa ? 0 : 1;
        
        $wpdb->update(
            $table_name,
            array('activa' => $nuevo_estado),
            array('id' => intval($_POST['id']))
        );
        
        $mensaje = $nuevo_estado ? 'activada' : 'pausada';
        echo '<div class="notice notice-success"><p>Intención ' . $mensaje . ' exitosamente.</p></div>';
    }
}

// Obtener intención para editar
$intencion_editar = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $intencion_editar = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['editar'])));
}

// Obtener todas las intenciones (incluyendo pausadas)
$intenciones = $wpdb->get_results("SELECT * FROM $table_name ORDER BY activa DESC, fecha_creacion DESC");
?>

<div class="wrap">
    <h1>Gestión de Intenciones de Rezo</h1>
    
    <div class="rezo-admin-container">
        <!-- Formulario para crear/editar intención -->
        <div class="rezo-form-section">
            <h2><?php echo $intencion_editar ? 'Editar Intención' : 'Crear Nueva Intención'; ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('rezo_admin'); ?>
                <input type="hidden" name="action" value="<?php echo $intencion_editar ? 'editar' : 'crear'; ?>">
                <?php if ($intencion_editar): ?>
                    <input type="hidden" name="id" value="<?php echo $intencion_editar->id; ?>">
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Título</th>
                        <td>
                            <input type="text" name="titulo" class="regular-text" required 
                                value="<?php echo $intencion_editar ? esc_attr($intencion_editar->titulo) : ''; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Descripción</th>
                        <td>
                            <?php 
                            wp_editor(
                                $intencion_editar ? $intencion_editar->descripcion : '', 
                                'descripcion', 
                                array(
                                    'textarea_name' => 'descripcion',
                                    'media_buttons' => true,
                                    'textarea_rows' => 10
                                )
                            ); 
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Objetivo Ave Marías</th>
                        <td>
                            <input type="number" name="objetivo_avemarias" class="regular-text" min="1" required
                                value="<?php echo $intencion_editar ? esc_attr($intencion_editar->objetivo_avemarias) : ''; ?>">
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php echo $intencion_editar ? 'Actualizar Intención' : 'Crear Intención'; ?>">
                    <?php if ($intencion_editar): ?>
                        <a href="?page=rezo-comunitario" class="button">Cancelar</a>
                    <?php endif; ?>
                </p>
            </form>
        </div>
        
        <!-- Lista de intenciones existentes -->
        <div class="rezo-list-section">
            <h2>Intenciones Existentes</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Progreso</th>
                        <th>Ave Marías</th>
                        <th>Estado</th>
                        <th>Shortcode</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($intenciones as $intencion): ?>
                        <?php $porcentaje = ($intencion->avemarias_actuales / $intencion->objetivo_avemarias) * 100; ?>
                        <tr class="<?php echo $intencion->activa ? '' : 'intencion-pausada'; ?>">
                            <td><strong><?php echo esc_html($intencion->titulo); ?></strong></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo min(100, $porcentaje); ?>%"></div>
                                </div>
                                <?php echo number_format($porcentaje, 1); ?>%
                            </td>
                            <td><?php echo number_format($intencion->avemarias_actuales); ?> / <?php echo number_format($intencion->objetivo_avemarias); ?></td>
                            <td>
                                <span class="estado-badge <?php echo $intencion->activa ? 'activa' : 'pausada'; ?>">
                                    <?php echo $intencion->activa ? 'Activa' : 'Pausada'; ?>
                                </span>
                            </td>
                            <td><code>[rezo_intencion id="<?php echo $intencion->id; ?>"]</code></td>
                            <td>
                                <a href="?page=rezo-comunitario&editar=<?php echo $intencion->id; ?>" class="button">Editar</a>
                                
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('rezo_admin'); ?>
                                    <input type="hidden" name="action" value="pausar">
                                    <input type="hidden" name="id" value="<?php echo $intencion->id; ?>">
                                    <input type="submit" class="button" value="<?php echo $intencion->activa ? 'Pausar' : 'Activar'; ?>">
                                </form>
                                
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('rezo_admin'); ?>
                                    <input type="hidden" name="action" value="eliminar">
                                    <input type="hidden" name="id" value="<?php echo $intencion->id; ?>">
                                    <input type="submit" class="button button-secondary" value="Eliminar" 
                                           onclick="return confirm('¿Estás seguro? Esta acción no se puede deshacer.')">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
