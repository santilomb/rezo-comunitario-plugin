<div class="rezo-intenciones-lista">
    <h2>Intenciones de Rezo Comunitario</h2>
    <div class="intenciones-grid">
        <?php foreach ($intenciones as $intencion): ?>
            <?php $porcentaje = ($intencion->avemarias_actuales / $intencion->objetivo_avemarias) * 100; ?>
            <div class="intencion-card">
                <h3><?php echo esc_html($intencion->titulo); ?></h3>
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo min(100, $porcentaje); ?>%"></div>
                    </div>
                    <span class="progress-text"><?php echo number_format($porcentaje, 1); ?>%</span>
                </div>
                <p class="avemarias-count">
                    <?php echo number_format($intencion->avemarias_actuales); ?> / <?php echo number_format($intencion->objetivo_avemarias); ?> Ave Marías
                </p>
                <a href="?intencion=<?php echo $intencion->id; ?>" class="btn-ver-intencion">Ver Intención</a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
