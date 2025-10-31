<?php
$porcentaje = ($intencion->avemarias_actuales / $intencion->objetivo_avemarias) * 100;
$beadRadius = 6;
$beadsMarkup = '';
for ($i = 0; $i < 10; $i++) {
    $angle = deg2rad(-90 + $i * 36);
    $x = 100 + 88 * cos($angle);
    $y = 100 + 88 * sin($angle);
    $beadsMarkup .= '<circle class="rosary-bead" data-bead-index="' . $i . '" cx="' . $x . '" cy="' . $y . '" r="' . $beadRadius . '" role="presentation"></circle>';
}

$porcentaje = min(100, $porcentaje);
?>

<div class="rezo-intencion-detalle" data-intencion-id="<?php echo $intencion->id; ?>">
    <div class="intencion-content">
        <div class="descripcion-section">
            <?php echo wp_kses_post($intencion->descripcion); ?>
        </div>
        
        <div class="progreso-section">
            <h3><?php echo $i18n->get('frontend', 'progreso_rezos', 'Progreso de Rezos'); ?></h3>
            <div class="progress-circle<?php echo $porcentaje >= 100 ? ' completed' : ''; ?>" data-porcentaje="<?php echo $porcentaje; ?>">
                <svg class="progress-ring rosary" width="200" height="240" viewBox="0 0 200 240" aria-hidden="true">
                    <defs>
                        <linearGradient id="rosaryCordGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#d9d9d9" />
                            <stop offset="100%" stop-color="#b3b3b3" />
                        </linearGradient>
                        <linearGradient id="rosaryProgressGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#20c5d8" />
                            <stop offset="100%" stop-color="#138496" />
                        </linearGradient>
                        <radialGradient id="rosaryBeadGradient" cx="50%" cy="35%" r="65%">
                            <stop offset="0%" stop-color="#fdfdfd" />
                            <stop offset="55%" stop-color="#e6e6e6" />
                            <stop offset="100%" stop-color="#cfcfcf" />
                        </radialGradient>
                        <radialGradient id="rosaryBeadFilledGradient" cx="50%" cy="35%" r="65%">
                            <stop offset="0%" stop-color="#7ef5ff" />
                            <stop offset="55%" stop-color="#17a2b8" />
                            <stop offset="100%" stop-color="#0f6674" />
                        </radialGradient>
                        <filter id="beadShadow" x="-50%" y="-50%" width="200%" height="200%">
                            <feDropShadow dx="0" dy="2" stdDeviation="2" flood-color="rgba(0, 0, 0, 0.25)" />
                        </filter>
                    </defs>
                    <circle class="progress-ring-circle" cx="100" cy="100" r="88" stroke-width="8" fill="transparent" />
                    <path class="rosary-tail" d="M100 188 L100 218 M90 218 L110 218 M100 218 L100 238" />
                    <circle class="progress-ring-progress" cx="100" cy="100" r="88" stroke-width="8" fill="transparent" />
                    <?php echo $beadsMarkup; ?>
                </svg>
                <div class="progress-text" aria-live="polite">
                    <span class="porcentaje" data-complete-text="<?php echo esc_attr($i18n->get('frontend', 'progreso_completo', '¡Rosario completado!')); ?>"><?php echo number_format($porcentaje, 1); ?>%</span>
                    <span class="avemarias"><?php echo number_format($intencion->avemarias_actuales); ?> / <?php echo number_format($intencion->objetivo_avemarias); ?></span>
                    <span class="cuentas-completadas" data-label="<?php echo esc_attr($cuentasLabel); ?>"><?php echo $cuentasLabel . ': ' . $cuentasCompletadas . '/10'; ?></span>
                </div>
            </div>
        </div>
        
        <div class="acciones-section">
            <button class="btn-agregar-rezos" id="btn-mostrar-formulario">
                <?php echo $i18n->get('frontend', 'btn_agregar_rezos', '🙏 Agregar mis Rezos'); ?>
            </button>
        </div>
        <a href="javascript:history.back()" class="btn-volver">
            <?php echo $i18n->get('frontend', 'btn_volver', '← Volver'); ?>
        </a>
    </div>
    
    <!-- Modal del formulario -->
    <div id="formulario-rezos" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="window.rezoFunctions.cerrarFormulario()">&times;</span>
            <h2><?php echo $i18n->get('frontend', 'modal_titulo', 'Agregar Ave Marías'); ?></h2>
            
            <form id="form-rezos">
                <div class="cantidad-opciones">
                    <h3><?php echo $i18n->get('frontend', 'modal_pregunta', '¿Cuántas Ave Marías rezaste?'); ?></h3>
                    <div class="opciones-grid">
                        <button type="button" class="opcion-cantidad" data-cantidad="1">
                            <span class="icono">📿</span>
                            <span class="numero">1</span>
                            <span class="texto"><?php echo $i18n->get('frontend', 'opcion_uno', 'Ave María'); ?></span>
                        </button>
                        <button type="button" class="opcion-cantidad" data-cantidad="10">
                            <span class="icono">🙏</span>
                            <span class="numero">10</span>
                            <span class="texto"><?php echo $i18n->get('frontend', 'opcion_varios', 'Ave Marías'); ?></span>
                        </button>
                        <button type="button" class="opcion-cantidad" data-cantidad="50">
                            <span class="icono">✨</span>
                            <span class="numero">50</span>
                            <span class="texto"><?php echo $i18n->get('frontend', 'opcion_varios', 'Ave Marías'); ?></span>
                        </button>
                        <button type="button" class="opcion-cantidad opcion-otro" data-cantidad="otro">
                            <span class="icono">💫</span>
                            <span class="numero">?</span>
                            <span class="texto"><?php echo $i18n->get('frontend', 'opcion_otro', 'Otro'); ?></span>
                        </button>
                    </div>
                    
                    <div id="cantidad-personalizada" style="display: none;">
                        <label for="cantidad-otro"><?php echo $i18n->get('frontend', 'cantidad_personalizada', 'Cantidad personalizada:'); ?></label>
                        <input type="number" id="cantidad-otro" min="1" max="10000">
                    </div>
                </div>
                
                <div class="captcha-section">
                    <h3><?php echo $i18n->get('frontend', 'verificacion', 'Verificación'); ?></h3>
                    <div class="captcha-math">
                        <span id="captcha-pregunta"></span>
                        <input type="number" id="captcha-respuesta" required>
                        <input type="hidden" id="captcha-resultado">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-enviar" disabled><?php echo $i18n->get('frontend', 'btn_enviar', 'Agregar Rezos'); ?></button>
                    <button type="button" class="btn-cancelar" onclick="window.rezoFunctions.cerrarFormulario()"><?php echo $i18n->get('frontend', 'btn_cancelar', 'Cancelar'); ?></button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal de agradecimiento -->
    <div id="modal-gracias" class="modal" style="display: none;">
        <div class="modal-content modal-gracias">
            <h2><?php echo $i18n->get('frontend', 'gracias_titulo', '¡Gracias por tu oración! 🙏'); ?></h2>
            <p><?php echo $i18n->get('frontend', 'gracias_mensaje', 'Tus rezos han sido agregados exitosamente.'); ?></p>
            <button onclick="window.rezoFunctions.cerrarGracias()" class="btn-continuar"><?php echo $i18n->get('frontend', 'btn_continuar', 'Continuar'); ?></button>
        </div>
    </div>
</div>

