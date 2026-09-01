<?php
/** @var ?array $hortaliza */
/** @var string $foto */
?>

<?php if (!$hortaliza): ?>
    <div class="panel">
        <h2>Ficha no encontrada</h2>
        <p>No existe ninguna hortaliza que coincida con la búsqueda.</p>
        <a class="btn btn-ghost" href="index.php?tab=siembra">← Volver al calendario de siembra</a>
    </div>
<?php else:
    $ficha = $hortaliza['ficha'] ?? []; ?>
    <nav class="detail-back">
        <a href="index.php?tab=siembra">← Volver al calendario de siembra</a>
    </nav>

    <section class="panel">
        <div class="detail-hero">
            <div class="foto">
                <span class="foto-fallback"><?= e(substr($hortaliza['nombre'], 0, 1)) ?></span>
                <?php if ($foto !== ''): ?>
                    <img src="<?= e($foto) ?>" alt="<?= e($hortaliza['nombre']) ?>" loading="lazy"
                         onerror="this.remove()">
                <?php endif; ?>
            </div>
            <div>
                <h2><?= e($hortaliza['nombre']) ?></h2>
                <em><?= e($hortaliza['nombre_cientifico'] ?? '') ?></em>
                <p class="familia"><?= e($hortaliza['familia'] ?? '') ?></p>
                <span class="badge <?= dificultadClass($hortaliza['dificultad'] ?? '') ?>"><?= e($hortaliza['dificultad'] ?? '') ?></span>
                <div class="chips">
                    <?php foreach ($ficha['meses_siembra'] ?? [] as $m): ?>
                        <span class="chip"><?= e($m) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="ficha-grid">
            <div><dt>Meses cosecha</dt><dd><?= e(implode(', ', $ficha['meses_cosecha'] ?? [])) ?></dd></div>
            <div><dt>Días a cosecha</dt><dd><?= e($ficha['dias_cosecha'] ?? '') ?></dd></div>
            <div><dt>Temp. suelo mín.</dt><dd><?= e($ficha['temperatura_suelo_minima'] ?? '') ?></dd></div>
            <div><dt>Temp. óptima</dt><dd><?= e($ficha['temperatura_optima'] ?? '') ?></dd></div>
            <div><dt>Fase lunar</dt><dd><?= e($ficha['fase_lunar'] ?? '') ?></dd></div>
            <div><dt>Profundidad siembra</dt><dd><?= e($ficha['profundidad_siembra'] ?? '') ?></dd></div>
            <div><dt>Marco plantación</dt><dd><?= e($ficha['marco_plantacion'] ?? '') ?></dd></div>
            <div><dt>Método siembra</dt><dd><?= e($hortaliza['metodo_siembra'] ?? '') ?></dd></div>
        </div>

        <?php if (!empty($ficha['asociaciones_buenas'])): ?>
            <h4>Asociaciones favorables</h4>
            <div class="chips">
                <?php foreach ($ficha['asociaciones_buenas'] as $a): ?>
                    <span class="chip chip-good"><?= e($a) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($ficha['asociaciones_malas'])): ?>
            <h4>Asociaciones desfavorables</h4>
            <div class="chips">
                <?php foreach ($ficha['asociaciones_malas'] as $a): ?>
                    <span class="chip chip-bad"><?= e($a) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($hortaliza['plagas'])): ?>
            <h4>Plagas</h4>
            <div class="chips">
                <?php foreach ($hortaliza['plagas'] as $a): ?>
                    <span class="chip"><?= e($a) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($hortaliza['enfermedades'])): ?>
            <h4>Enfermedades</h4>
            <div class="chips">
                <?php foreach ($hortaliza['enfermedades'] as $a): ?>
                    <span class="chip"><?= e($a) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($ficha['riego_clave'])): ?>
            <h4>Riego</h4>
            <p class="ficha-text">💧 <?= e($ficha['riego_clave']) ?></p>
        <?php endif; ?>

        <?php if (!empty($ficha['abono_recomendado'])): ?>
            <h4>Abonado</h4>
            <p class="ficha-text">🌱 <?= e($ficha['abono_recomendado']) ?></p>
        <?php endif; ?>

        <?php if (!empty($ficha['observaciones'])): ?>
            <h4>Observaciones</h4>
            <p class="ficha-text">📝 <?= e($ficha['observaciones']) ?></p>
        <?php endif; ?>
    </section>
<?php endif; ?>