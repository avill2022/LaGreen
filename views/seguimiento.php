<?php
/** @var Database $db */
/** @var array $plants */
/** @var array $gps */
/** @var string $error */
/** @var array $submitted */
?>

<section class="panel">
    <h2>Añadir Planta en Germinación</h2>
    <form method="post" class="add-form">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="tab" value="seguimiento">
        <div class="field">
            <label for="plant_id">Tipo</label>
            <select name="plant_id" id="plant_id" required>
                <?php foreach ($plants as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"
                        <?= $submitted['plant_id'] === (int) $p['id'] ? 'selected' : '' ?>>
                        <?= e($p['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="name">Nombre</label>
            <input type="text" name="name" id="name" value="<?= e($submitted['name']) ?>"
                   placeholder="Opcional (usa el tipo por defecto)">
        </div>
        <div class="field">
            <label for="germination_date">Fecha germinación</label>
            <input type="date" name="germination_date" id="germination_date"
                   value="<?= e($submitted['germination_date']) ?>" required>
        </div>
        <div class="field">
            <label for="notes">Notas</label>
            <input type="text" name="notes" id="notes" value="<?= e($submitted['notes']) ?>">
        </div>
        <button type="submit" class="btn btn-primary">Añadir</button>
    </form>
</section>

<section class="panel">
    <h2>Plantas en Seguimiento</h2>

    <?php if (!$gps): ?>
        <p class="empty">No hay plantas en germinación.</p>
    <?php else: ?>
        <?php foreach ($gps as $gp):
            $plant = $db->getPlant((int) $gp['plant_id']);
            if (!$plant) {
                continue;
            }
            [$phaseIdx, $phaseName, $progress, $currentPhase] = currentPhase($plant, $gp['germination_date']);
            $statusColor = $phaseName !== '' ? '#4CAF50' : '#FF9800';
            $phaseStatus = $phaseName !== '' ? 'Fase actual: ' . $phaseName : 'Completada';
        ?>
            <article class="card">
                <div class="card-header">
                    <div class="foto foto-small">
                        <span class="foto-fallback"><?= e(substr($plant['name'], 0, 1)) ?></span>
                        <?php if (($plant['image'] ?? '') !== ''): ?>
                            <img src="<?= e($plant['image']) ?>" alt="<?= e($plant['name']) ?>" loading="lazy" onerror="this.remove()">
                        <?php endif; ?>
                    </div>
                    <div class="card-title">
                        <strong><?= e($gp['name']) ?></strong>
                        <span class="card-subtitle">(<?= e($plant['name']) ?>)</span>
                    </div>
                    <div class="card-actions">
                        <span class="badge" style="color: <?= $statusColor ?>; border-color: <?= $statusColor ?>">
                            <?= e($phaseStatus) ?>
                        </span>
                        <form method="post" class="inline-form" onsubmit="return confirm('¿Eliminar <?= e($gp['name']) ?>?');">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="tab" value="seguimiento">
                            <input type="hidden" name="id" value="<?= (int) $gp['id'] ?>">
                            <button type="submit" class="btn btn-danger">Eliminar</button>
                        </form>
                    </div>
                </div>

                <div class="card-info">
                    <span>Germinación: <?= e($gp['germination_date']) ?></span>
                    <?php if ($gp['notes'] !== ''): ?>
                        <span>Notas: <?= e($gp['notes']) ?></span>
                    <?php endif; ?>
                    <?php $harvest = estimateHarvestDate($plant, $gp['germination_date']); ?>
                    <?php if ($harvest): ?>
                        <?php
                        $remaining = dayDiff(new DateTime('today'), $harvest);
                        $remText = $remaining >= 0
                            ? '(' . $remaining . ' días restantes)'
                            : '(¡tiempo de cosecha!)';
                        ?>
                        <span>Cosecha estimada: <?= e($harvest->format('Y-m-d')) ?> <?= e($remText) ?></span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($plant['phases']) && $phaseIdx !== null && $phaseIdx < count($plant['phases'])): ?>
                    <div class="card-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= (int) round($progress * 100) ?>%"></div>
                        </div>
                        <div class="phase-labels">
                            <?php foreach ($plant['phases'] as $i => $ph): ?>
                                <?php
                                $label = $ph['name'] !== '' ? $ph['name'] : 'Fase ' . ($i + 1);
                                if ($i > 0) {
                                    echo ' <span class="phase-arrow">→</span> ';
                                }
                                if ($i === $phaseIdx) {
                                    echo '<strong>[' . e($label) . ']</strong>';
                                } else {
                                    echo e($label);
                                }
                                ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($currentPhase): ?>
                    <?php $chips = phaseChips($currentPhase); ?>
                    <?php if ($chips): ?>
                        <div class="chips">
                            <?php foreach ($chips as [$icon, $text]): ?>
                                <span class="chip"><?= $icon ?> <?= e($text) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (hasValue($currentPhase, 'notes')): ?>
                        <p class="phase-notes">📝 <?= e($currentPhase['notes']) ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
