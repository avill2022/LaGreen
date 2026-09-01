<?php
/** @var array $hortalizas */
/** @var array $plants */

$hortalizas = loadHortalizas();
$currentMonthNum = (int) date('n');
$currentYear = (int) date('Y');
$currentMonthName = MONTHS_ES[$currentMonthNum];

$plantImages = [];
foreach (($plants ?? []) as $p) {
    $img = (string) ($p['image'] ?? '');
    if ($img !== '') {
        $plantImages[normalizeLower((string) $p['name'])] = $img;
    }
}

$thisMonth = [];
foreach ($hortalizas as $h) {
    foreach ($h['ficha']['meses_siembra'] ?? [] as $m) {
        if (monthNumber($m) === $currentMonthNum) {
            $thisMonth[] = $h;
            break;
        }
    }
}

?>

<section class="panel">
    <h2>Calendario de siembra</h2>
    <p class="panel-sub">¿Qué sembrar? Estos son los cultivos recomendados para <strong><?= e($currentMonthName) ?></strong>.</p>

    <?php if (!$hortalizas): ?>
        <p class="empty">No se encontró el archivo <code>hortalizas.json</code>.</p>
    <?php elseif (!$thisMonth): ?>
        <p class="empty">No hay cultivos programados para <?= e($currentMonthName) ?>.</p>
    <?php else: ?>
        <h3 class="reel-title"><?= e($currentMonthName) ?> de <?= $currentYear ?> · <?= count($thisMonth) ?> cultivos</h3>
        <div class="reel-wrap">
            <button type="button" class="reel-btn reel-prev" aria-label="Anterior">‹</button>
            <div class="reel" id="reel">
                <?php foreach ($thisMonth as $h): ?>
                    <?php $ficha = $h['ficha']; ?>
                    <article class="reel-card" data-href="<?= e(hortDetailUrl($h)) ?>" tabindex="0" role="link"
                             aria-label="Ver ficha de <?= e($h['nombre']) ?>">
                        <?php $foto = $plantImages[normalizeLower((string) ($h['nombre'] ?? ''))] ?? ($h['foto'] ?? ''); ?>
                        <div class="foto">
                            <span class="foto-fallback"><?= e(substr($h['nombre'], 0, 1)) ?></span>
                            <?php if ($foto !== ''): ?>
                                <img src="<?= e($foto) ?>" alt="<?= e($h['nombre']) ?>" loading="lazy"
                                     onerror="this.remove()">
                            <?php endif; ?>
                        </div>
                        <div class="reel-body">
                            <div class="reel-head">
                                <h4><?= e($h['nombre']) ?></h4>
                                <span class="badge <?= dificultadClass($h['dificultad'] ?? '') ?>"><?= e($h['dificultad'] ?? '') ?></span>
                            </div>
                            <em class="reel-sci"><?= e($h['nombre_cientifico'] ?? '') ?></em>
                            <div class="chips">
                                <?php foreach ($ficha['meses_siembra'] ?? [] as $m): ?>
                                    <span class="chip <?= monthNumber($m) === $currentMonthNum ? 'chip-current' : '' ?>"><?= e($m) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <p class="reel-meta">🌡 <?= e($ficha['temperatura_optima'] ?? '') ?></p>
                            <p class="reel-meta"><?= e($ficha['temperatura_suelo_minima'] ?? '') ?> mín. de suelo</p>
                            <p class="reel-metodo"><?= e($h['metodo_siembra'] ?? '') ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <button type="button" class="reel-btn reel-next" aria-label="Siguiente">›</button>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>Consulta de hortalizas</h2>
    <input type="search" id="hort-search" class="search" placeholder="Buscar por nombre, familia o nombre científico…">
    <p id="hort-empty" class="empty" hidden>No se encontraron resultados.</p>

    <div class="hort-grid">
        <?php foreach ($hortalizas as $h): ?>
            <?php $ficha = $h['ficha']; ?>
            <article class="hort-consult"
                     data-search="<?= e(strtolower($h['nombre'] . ' ' . ($h['nombre_cientifico'] ?? '') . ' ' . ($h['familia'] ?? ''))) ?>">
                <div class="hort-consult-head">
                    <?php $foto = $plantImages[normalizeLower((string) ($h['nombre'] ?? ''))] ?? ($h['foto'] ?? ''); ?>
                    <div class="foto foto-small">
                        <span class="foto-fallback"><?= e(substr($h['nombre'], 0, 1)) ?></span>
                        <?php if ($foto !== ''): ?>
                            <img src="<?= e($foto) ?>" alt="<?= e($h['nombre']) ?>" loading="lazy"
                                 onerror="this.remove()">
                        <?php endif; ?>
                    </div>
                    <div>
                        <h3><?= e($h['nombre']) ?></h3>
                        <em><?= e($h['nombre_cientifico'] ?? '') ?></em>
                        <p class="familia"><?= e($h['familia'] ?? '') ?></p>
                    </div>
                    <span class="badge <?= dificultadClass($h['dificultad'] ?? '') ?>"><?= e($h['dificultad'] ?? '') ?></span>
                </div>

                <div class="chips">
                    <?php foreach ($ficha['meses_siembra'] ?? [] as $m): ?>
                        <span class="chip <?= monthNumber($m) === $currentMonthNum ? 'chip-current' : '' ?>"><?= e($m) ?></span>
                    <?php endforeach; ?>
                </div>

                <a class="btn btn-ghost" href="<?= e(hortDetailUrl($h)) ?>">Ver ficha completa</a>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<script>
    (function () {
        var reel = document.getElementById('reel');
        if (reel) {
            document.querySelectorAll('.reel-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var dir = btn.classList.contains('reel-prev') ? -1 : 1;
                    reel.scrollBy({ left: dir * reel.clientWidth * 0.8, behavior: 'smooth' });
                });
            });

            document.querySelectorAll('.reel-card').forEach(function (card) {
                var go = function () {
                    if (card.dataset.href) {
                        window.location.href = card.dataset.href;
                    }
                };
                card.addEventListener('click', go);
                card.addEventListener('keydown', function (ev) {
                    if (ev.key === 'Enter' || ev.key === ' ') {
                        ev.preventDefault();
                        go();
                    }
                });
            });
        }

        var search = document.getElementById('hort-search');
        if (search) {
            search.addEventListener('input', function () {
                var q = search.value.trim().toLowerCase();
                var visible = 0;
                document.querySelectorAll('.hort-consult').forEach(function (card) {
                    var match = q === '' || (card.dataset.search || '').indexOf(q) !== -1;
                    card.style.display = match ? '' : 'none';
                    if (match) visible++;
                });
                document.getElementById('hort-empty').hidden = visible !== 0;
            });
        }
    })();
</script>
