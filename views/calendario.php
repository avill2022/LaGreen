<?php
/** @var array $plantData */
?>

<section class="panel">
    <h2>Calendario Anual de Fases</h2>

    <?php if (!$plantData): ?>
        <p class="empty">No hay plantas en germinación.<br>Añada plantas en la pestaña 'Seguimiento'.</p>
    <?php else:
        $px = MIN_PX_PER_DAY;
        $marginLeft = GANTT_MARGIN_LEFT;
        $marginRight = GANTT_MARGIN_RIGHT;

        $start = null;
        $end = null;
        foreach ($plantData as $pd) {
            $endCandidate = (clone $pd['germ'])->modify('+' . $pd['total'] . ' days');
            if ($start === null || $pd['germ'] < $start) {
                $start = $pd['germ'];
            }
            if ($end === null || $endCandidate > $end) {
                $end = $endCandidate;
            }
        }

        $totalDaysSpan = max(dayDiff($start, $end), 366);
        $chartWidth = $totalDaysSpan * $px;
        $ganttWidth = $marginLeft + $chartWidth + $marginRight;
        $today = new DateTime('today');

        // Encabezados de mes
        $monthCursor = new DateTime($start->format('Y-m-01'));
        $endMarker = new DateTime($end->format('Y-m-01'));
        $months = [];
        while ($monthCursor <= $endMarker) {
            $year = (int) $monthCursor->format('Y');
            $monthNum = (int) $monthCursor->format('n');
            $monthDays = daysInMonth($year, $monthNum);
            $offset = dayDiff($start, $monthCursor);
            $label = MONTHS_ABBR[$monthNum - 1];
            if ($monthNum === 1 || $monthCursor->format('Y-m') === $start->format('Y-m')) {
                $label .= ' ' . $year;
            }
            $months[] = [
                'left' => $marginLeft + $offset * $px,
                'width' => $monthDays * $px,
                'label' => $label,
            ];
            $monthCursor = $monthCursor->modify('+1 month');
        }

        $showToday = $start <= $today && $today <= $end;
        $todayX = $marginLeft + dayDiff($start, $today) * $px;
        $rowsTop = GANTT_MONTH_BAR_HEIGHT;
        $rowsHeight = count($plantData) * GANTT_ROW_HEIGHT;
    ?>

    <div class="gantt-scroll">
        <div class="gantt" style="width: <?= $ganttWidth ?>px">
            <div class="gantt-months" style="height: <?= GANTT_MONTH_BAR_HEIGHT ?>px">
                <?php foreach ($months as $m): ?>
                    <div class="gantt-month" style="left: <?= $m['left'] ?>px; width: <?= $m['width'] ?>px">
                        <?= e($m['label']) ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($showToday): ?>
                <div class="gantt-today" style="left: <?= $todayX ?>px; top: <?= $rowsTop ?>px; height: <?= $rowsHeight + GANTT_ROW_HEIGHT - 20 ?>px">
                    <span class="gantt-today-label">Hoy</span>
                </div>
            <?php endif; ?>

            <?php foreach ($plantData as $pd):
                $gp = $pd['gp'];
                $plant = $pd['plant'];
                $phases = $plant['phases'];
                $germOffset = dayDiff($start, $pd['germ']);
            ?>
                <div class="gantt-row" style="height: <?= GANTT_ROW_HEIGHT ?>px">
                    <span class="gantt-label"><?= e($gp['name']) ?></span>

                    <?php if ($phases):
                        $cumulative = 0;
                        foreach ($phases as $i => $ph):
                            $d = (int) ($ph['duration_max_days'] ?? 0);
                            $min = (int) ($ph['duration_min_days'] ?? 0);
                            if ($d === 0) {
                                $d = $min !== 0 ? $min : 30;
                            }
                            $s = $germOffset + $cumulative;
                            $blockLeft = $marginLeft + $s * $px;
                            $blockWidth = $d * $px;
                            $color = phaseColor((string) $ph['name'], $i);
                            $cumulative += $d;
                    ?>
                        <div class="gantt-block" style="left: <?= $blockLeft ?>px; width: <?= $blockWidth ?>px; background-color: <?= $color ?>">
                            <?php if ($blockWidth > 30): ?><?= e($ph['name'] !== '' ? $ph['name'] : 'F' . ($i + 1)) ?><?php endif; ?>
                        </div>
                    <?php endforeach; else:
                        $s = $germOffset;
                        $blockLeft = $marginLeft + $s * $px;
                        $blockWidth = $pd['total'] * $px;
                    ?>
                        <div class="gantt-block" style="left: <?= $blockLeft ?>px; width: <?= $blockWidth ?>px; background-color: #4CAF50">
                            <?= e($gp['name']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php endif; ?>
</section>
