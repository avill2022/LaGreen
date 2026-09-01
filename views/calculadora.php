<?php
/** @var array $plants */
/** @var int $plantId */
/** @var string $calcDate */
/** @var ?array $plantSel */
/** @var ?array $result */
/** @var ?array $horta */
/** @var string $calcError */
/** @var string $slug */
?>

<section class="panel">
    <h2>Calculadora de crecimiento</h2>
    <p class="panel-sub">Selecciona el tipo de planta y la fecha de germinación para generar una guía completa de cultivo.</p>

    <form method="get" class="add-form">
        <input type="hidden" name="tab" value="calculadora">
        <div class="field">
            <label for="plant_id">Tipo de planta</label>
            <select name="plant_id" id="plant_id" required>
                <?php foreach ($plants as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= $plantId === (int) $p['id'] ? 'selected' : '' ?>>
                        <?= e($p['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="calc-date">Fecha de germinación</label>
            <input type="date" name="date" id="calc-date" value="<?= e($calcDate) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Calcular</button>
    </form>

    <?php if ($calcError !== ''): ?>
        <div class="alert alert-error"><?= e($calcError) ?></div>
    <?php endif; ?>
</section>

<?php if ($result): ?>
    <?php
    $total = max($result['totalDays'], 1);
    $today = $result['today'];
    $germ = $result['germDate'];
    $todayOffset = dayDiff($germ, $today);
    $overall = max(0.0, min(1.0, $todayOffset / $total));
    $nPhases = count($result['phases']);
    $done = $result['currentIdx'] !== null && $result['currentIdx'] >= $nPhases;

    $statusText = 'Sin fases definidas';
    $statusClass = 'status-neutral';
    if ($done) {
        $statusText = 'Completada';
        $statusClass = 'status-done';
    } elseif ($result['currentIdx'] !== null) {
        $statusText = 'Fase actual: ' . $result['phases'][$result['currentIdx']]['name'];
        $statusClass = 'status-active';
    } elseif ($nPhases > 0) {
        $statusText = 'Por iniciar';
    }

    $horta = $horta ?? null;
    $sciName = $horta['nombre_cientifico'] ?? '';
    $familia = $horta['familia'] ?? '';
    $dificultad = $horta['dificultad'] ?? '';
    $foto = $horta['foto'] ?? '';
    ?>

    <section class="panel calc-guide" id="calc-export">
        <div class="calc-toolbar">
            <span class="calc-toolbar-hint">Imprime esta guía como PDF o expórtala como imagen.</span>
            <button type="button" class="btn btn-ghost btn-sm" id="calc-print">🖨️ Imprimir / PDF</button>
            <button type="button" class="btn btn-ghost btn-sm" id="calc-img">🖼️ Exportar imagen</button>
        </div>
        <div class="calc-head">
            <div class="foto foto-calc">
                <span class="foto-fallback"><?= e(substr($plantSel['name'], 0, 1)) ?></span>
                <?php if ($foto !== ''): ?>
                    <img src="<?= e($foto) ?>" alt="<?= e($plantSel['name']) ?>" loading="lazy" onerror="this.remove()">
                <?php endif; ?>
            </div>
            <div class="calc-head-info">
                <h2><?= e($plantSel['name']) ?></h2>
                <?php if ($sciName !== ''): ?><em><?= e($sciName) ?></em><?php endif; ?>
                <div class="calc-head-meta">
                    <?php if ($familia !== ''): ?><span>Familia: <?= e($familia) ?></span><?php endif; ?>
                    <?php if ($dificultad !== ''): ?><span class="badge <?= dificultadClass($dificultad) ?>"><?= e($dificultad) ?></span><?php endif; ?>
                    <?php if ($plantSel['plant_group'] !== ''): ?><span class="chip">Grupo: <?= e($plantSel['plant_group']) ?></span><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="calc-stats">
            <div class="stat-box"><span class="stat-label">Germinación</span><span class="stat-value"><?= e($germ->format('d/m/Y')) ?></span></div>
            <div class="stat-box"><span class="stat-label">Cosecha estimada</span><span class="stat-value"><?= $result['harvestDate'] ? e($result['harvestDate']->format('d/m/Y')) : '—' ?></span></div>
            <div class="stat-box"><span class="stat-label">Duración total</span><span class="stat-value"><?= $result['totalDays'] ?> días</span></div>
            <div class="stat-box"><span class="stat-label">Estado</span><span class="stat-value <?= $statusClass ?>"><?= e($statusText) ?></span></div>
        </div>

        <?php if ($nPhases > 0): ?>
            <div class="card-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= (int) round($overall * 100) ?>%"></div>
                </div>
                <div class="phase-labels">
                    <?php foreach ($result['phases'] as $i => $ph): ?>
                        <?php if ($i > 0): ?><span class="phase-arrow">→</span><?php endif; ?>
                        <?php if ($ph['isCurrent']): ?>
                            <strong>[<?= e($ph['name']) ?>]</strong>
                        <?php else: ?>
                            <?= e($ph['name']) ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="calc-timeline">
                <?php $offset = 0; foreach ($result['phases'] as $ph):
                    $left = ($offset / $total) * 100;
                    $width = ($ph['effectiveDays'] / $total) * 100;
                    $offset += $ph['effectiveDays'];
                ?>
                    <div class="calc-phase-block" style="left: <?= $left ?>%; width: <?= $width ?>%; background-color: <?= $ph['color'] ?>;<?= $ph['isCurrent'] ? ' outline: 2px solid #fff;' : '' ?>">
                        <?php if ($width > 8): ?><?= e($ph['name']) ?><?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if ($todayOffset >= 0 && $todayOffset <= $total): ?>
                    <div class="calc-today" style="left: <?= ($todayOffset / $total) * 100 ?>%">
                        <span class="calc-today-label">Hoy</span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <h3 class="calc-phases-title">Guía por fases</h3>

        <?php if ($nPhases === 0): ?>
            <p class="empty">Esta planta no tiene fases de crecimiento definidas.</p>
        <?php else: ?>
            <div class="phase-list">
                <?php foreach ($result['phases'] as $i => $ph): ?>
                    <article class="phase-item">
                        <span class="phase-dot" style="background-color: <?= $ph['color'] ?>"></span>
                        <div class="phase-content">
                            <div class="phase-head">
                                <h4><?= e($ph['name']) ?></h4>
                                <?php if ($ph['isCurrent']): ?><span class="badge badge-current">Actual</span><?php endif; ?>
                                <?php if ($ph['progress'] >= 1.0): ?><span class="badge badge-done">Completada</span><?php endif; ?>
                                <span class="phase-dates"><?= e($ph['startStr']) ?> → <?= e($ph['endStr']) ?></span>
                            </div>
                            <p class="phase-duration">
                                Duración: <?= $ph['durMin'] ?><?= $ph['durMax'] !== $ph['durMin'] ? '-' . $ph['durMax'] : '' ?> días
                                <?php if ($ph['progress'] > 0 && $ph['progress'] < 1): ?>
                                    · <?= (int) round($ph['progress'] * 100) ?>% completada
                                <?php endif; ?>
                            </p>
                            <?php if ($ph['chips']): ?>
                                <div class="chips">
                                    <?php foreach ($ph['chips'] as [$icon, $text]): ?>
                                        <span class="chip"><?= $icon ?> <?= e($text) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($ph['notes'] !== ''): ?>
                                <p class="phase-notes">📝 <?= e($ph['notes']) ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($horta): ?>
        <section class="panel">
            <details class="calc-extra">
                <summary>Ficha de cultivo adicional</summary>
                <div class="ficha-grid">
                    <div><dt>Meses siembra</dt><dd><?= e(implode(', ', $horta['ficha']['meses_siembra'] ?? [])) ?></dd></div>
                    <div><dt>Meses cosecha</dt><dd><?= e(implode(', ', $horta['ficha']['meses_cosecha'] ?? [])) ?></dd></div>
                    <div><dt>Días a cosecha</dt><dd><?= e($horta['ficha']['dias_cosecha'] ?? '') ?></dd></div>
                    <div><dt>Temp. suelo mín.</dt><dd><?= e($horta['ficha']['temperatura_suelo_minima'] ?? '') ?></dd></div>
                    <div><dt>Temp. óptima</dt><dd><?= e($horta['ficha']['temperatura_optima'] ?? '') ?></dd></div>
                    <div><dt>Fase lunar</dt><dd><?= e($horta['ficha']['fase_lunar'] ?? '') ?></dd></div>
                    <div><dt>Profundidad siembra</dt><dd><?= e($horta['ficha']['profundidad_siembra'] ?? '') ?></dd></div>
                    <div><dt>Marco plantación</dt><dd><?= e($horta['ficha']['marco_plantacion'] ?? '') ?></dd></div>
                    <div><dt>Método siembra</dt><dd><?= e($horta['metodo_siembra'] ?? '') ?></dd></div>
                </div>

                <?php if (!empty($horta['ficha']['asociaciones_buenas'])): ?>
                    <h4>Asociaciones favorables</h4>
                    <div class="chips">
                        <?php foreach ($horta['ficha']['asociaciones_buenas'] as $a): ?>
                            <span class="chip chip-good"><?= e($a) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($horta['ficha']['asociaciones_malas'])): ?>
                    <h4>Asociaciones desfavorables</h4>
                    <div class="chips">
                        <?php foreach ($horta['ficha']['asociaciones_malas'] as $a): ?>
                            <span class="chip chip-bad"><?= e($a) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($horta['plagas'])): ?>
                    <h4>Plagas</h4>
                    <div class="chips">
                        <?php foreach ($horta['plagas'] as $a): ?>
                            <span class="chip"><?= e($a) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($horta['enfermedades'])): ?>
                    <h4>Enfermedades</h4>
                    <div class="chips">
                        <?php foreach ($horta['enfermedades'] as $a): ?>
                            <span class="chip"><?= e($a) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($horta['ficha']['riego_clave'])): ?>
                    <h4>Riego</h4>
                    <p class="ficha-text">💧 <?= e($horta['ficha']['riego_clave']) ?></p>
                <?php endif; ?>

                <?php if (!empty($horta['ficha']['abono_recomendado'])): ?>
                    <h4>Abonado</h4>
                    <p class="ficha-text">🌱 <?= e($horta['ficha']['abono_recomendado']) ?></p>
                <?php endif; ?>

                <?php if (!empty($horta['ficha']['observaciones'])): ?>
                    <h4>Observaciones</h4>
                    <p class="ficha-text">📝 <?= e($horta['ficha']['observaciones']) ?></p>
                <?php endif; ?>
            </details>
        </section>
    <?php endif; ?>

    <script src="assets/vendor/html2canvas.min.js"></script>
    <script>
    (function () {
        var el = document.getElementById('calc-export');
        if (!el) {
            return;
        }

        var printBtn = document.getElementById('calc-print');
        if (printBtn) {
            printBtn.addEventListener('click', function () {
                document.body.classList.add('print-mode');
                window.print();
                document.body.classList.remove('print-mode');
            });
        }

        var imgBtn = document.getElementById('calc-img');
        if (imgBtn && window.html2canvas) {
            imgBtn.addEventListener('click', function () {
                imgBtn.disabled = true;
                var original = imgBtn.textContent;
                imgBtn.textContent = 'Generando…';
                html2canvas(el, {
                    backgroundColor: '#2b2b2b',
                    scale: 2,
                    useCORS: true,
                    imageTimeout: 3000,
                    ignoreElements: function (node) {
                        return node.classList && node.classList.contains('calc-toolbar');
                    }
                }).then(function (canvas) {
                    var a = document.createElement('a');
                    a.download = 'calculadora-<?= e($slug) ?>.png';
                    a.href = canvas.toDataURL('image/png');
                    a.click();
                }).catch(function () {
                    alert('No se pudo generar la imagen. Prueba con "Imprimir / PDF".');
                }).finally(function () {
                    imgBtn.disabled = false;
                    imgBtn.textContent = original;
                });
            });
        }
    })();
    </script>
<?php endif; ?>
