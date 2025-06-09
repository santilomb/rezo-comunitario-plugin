<?php
global $wpdb;
$table_name = $wpdb->prefix . 'rezo_intenciones';

// Obtener intención para editar
$intencion_editar = null;
$es_edicion = false;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $intencion_editar = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['editar'])));
    $es_edicion = true;
}

// Procesar formulario
if (isset($_POST['action'])) {
    if ($_POST['action'] == 'crear' && wp_verify_nonce($_POST['_wpnonce'], 'rezo_admin')) {
        $titulo = sanitize_text_field($_POST['titulo']);
        $descripcion = wp_kses_post($_POST['descripcion']);
        $objetivo = intval($_POST['objetivo_avemarias']);
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'objetivo_avemarias' => $objetivo
            )
        );
        
        if ($result) {
            $intencion_id = $wpdb->insert_id;
            
            // Crear página automáticamente
            $rezo_plugin = new RezoComunitario();
            $reflection = new ReflectionClass($rezo_plugin);
            $method = $reflection->getMethod('crear_pagina_intencion');
            $method->setAccessible(true);
            $page_id = $method->invoke($rezo_plugin, $intencion_id, $titulo);
            
            echo '<div class="notice notice-success"><p>' . $i18n->get('backend', 'exito_crear', 'Intención creada exitosamente.') . ' <a href="' . get_permalink($page_id) . '" target="_blank">' . $i18n->get('backend', 'btn_ver_pagina', 'Ver página') . '</a></p></div>';
            
            // Limpiar formulario
            unset($_POST);
        } else {
            echo '<div class="notice notice-error"><p>' . $i18n->get('backend', 'error_crear', 'Error al crear la intención.') . '</p></div>';
        }
    }
    
    if ($_POST['action'] == 'editar' && wp_verify_nonce($_POST['_wpnonce'], 'rezo_admin')) {
        $titulo = sanitize_text_field($_POST['titulo']);
        $descripcion = wp_kses_post($_POST['descripcion']);
        $objetivo = intval($_POST['objetivo_avemarias']);
        $intencion_id = intval($_POST['id']);
        
        $result = $wpdb->update(
            $table_name,
            array(
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'objetivo_avemarias' => $objetivo
            ),
            array('id' => $intencion_id)
        );
        
        if ($result !== false) {
            // Actualizar página asociada
            $intencion = $wpdb->get_row($wpdb->prepare("SELECT page_id FROM $table_name WHERE id = %d", $intencion_id));
            if ($intencion && $intencion->page_id) {
                wp_update_post(array(
                    'ID' => $intencion->page_id,
                    'post_title' => 'Rezo Comunitario: ' . $titulo
                ));
            }
            
            echo '<div class="notice notice-success"><p>' . $i18n->get('backend', 'exito_actualizar', 'Intención actualizada exitosamente.') . '</p></div>';
            
            // Recargar datos
            $intencion_editar = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $intencion_id));
        } else {
            echo '<div class="notice notice-error"><p>' . $i18n->get('backend', 'error_actualizar', 'Error al actualizar la intención.') . '</p></div>';
        }
    }
}
?>

<div class="wrap">
    <h1>
        <?php echo $es_edicion ? 
            $i18n->get('backend', 'titulo_editar', 'Editar Intención') : 
            $i18n->get('backend', 'titulo_agregar', 'Agregar Nueva Intención'); ?>
        <a href="<?php echo admin_url('admin.php?page=rezo-comunitario'); ?>" class="page-title-action">
            <span class="dashicons dashicons-arrow-left-alt"></span> <?php echo $i18n->get('backend', 'btn_volver_lista', 'Volver a la Lista'); ?>
        </a>
    </h1>
    
    <div class="rezo-form-container">
        <form method="post" action="" class="rezo-form">
            <?php wp_nonce_field('rezo_admin'); ?>
            <input type="hidden" name="action" value="<?php echo $es_edicion ? 'editar' : 'crear'; ?>">
            <?php if ($es_edicion): ?>
                <input type="hidden" name="id" value="<?php echo $intencion_editar->id; ?>">
            <?php endif; ?>
            
            <div class="form-section">
                <h2><span class="dashicons dashicons-edit"></span> <?php echo $i18n->get('backend', 'seccion_info_basica', 'Información Básica'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="titulo"><?php echo $i18n->get('backend', 'label_titulo', 'Título de la Intención'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="titulo" 
                                   name="titulo" 
                                   class="regular-text" 
                                   required 
                                   value="<?php echo $intencion_editar ? esc_attr($intencion_editar->titulo) : ''; ?>"
                                   placeholder="<?php echo $i18n->get('backend', 'placeholder_titulo', 'Ej: Por la paz mundial'); ?>">
                            <p class="description"><?php echo $i18n->get('backend', 'desc_titulo', 'Este título aparecerá en la página y en el listado de intenciones.'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="objetivo_avemarias"><?php echo $i18n->get('backend', 'label_objetivo', 'Objetivo de Ave Marías'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="objetivo_avemarias" 
                                   name="objetivo_avemarias" 
                                   class="regular-text" 
                                   min="1" 
                                   required 
                                   value="<?php echo $intencion_editar ? esc_attr($intencion_editar->objetivo_avemarias) : ''; ?>"
                                   placeholder="1000">
                            <p class="description"><?php echo $i18n->get('backend', 'desc_objetivo', 'Cantidad total de Ave Marías que se desea alcanzar para esta intención.'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="form-section">
                <h2><span class="dashicons dashicons-admin-page"></span> <?php echo $i18n->get('backend', 'seccion_
