<div style="margin-bottom: 24px;">
    <a href="/dashboard" style="color: var(--text-muted); text-decoration: none; font-size: 13px;">← Back to Dashboard</a>
    <h1 style="font-size: 24px; font-weight: 700; margin: 8px 0 0 0;">
        <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
    </h1>
    <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">
        <?= htmlspecialchars($student['email'] ?? 'No email') ?>
        <?= $student['student_ext_id'] ? ' · ' . htmlspecialchars($student['student_ext_id']) : '' ?>
    </p>
</div>

<!-- Risk Scores per Course -->
<?php foreach ($riskScores as $rs): ?>
<div class="glass-card" style="padding: 24px; margin-bottom: 24px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
        <div>
            <h2 style="font-size: 16px; font-weight: 600; margin: 0;">
                <?= htmlspecialchars($rs['course_name']) ?>
                <span style="color: var(--text-muted); font-family: monospace; font-size: 12px;"><?= htmlspecialchars($rs['course_code']) ?></span>
            </h2>
        </div>
        <div style="text-align: right;">
            <span class="badge-<?= $rs['classification'] ?>" style="font-size: 14px; padding: 6px 16px;">
                <?= strtoupper($rs['classification']) ?>
            </span>
            <p style="font-size: 28px; font-weight: 700; color: var(--text-primary); margin: 4px 0 0 0;"><?= $rs['composite_score'] ?></p>
            <p style="font-size: 12px; color: var(--text-muted);">/ 100</p>
        </div>
    </div>

    <!-- Factor Bars -->
    <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
        <?php
        $factors = [
            ['Attendance', $rs['attendance_score'], 'var(--accent)'],
            ['Assessment', $rs['assessment_score'], '#a855f7'],
            ['Engagement', $rs['engagement_score'], '#06b6d4'],
        ];
        if ($rs['sentiment_score'] !== null) {
            $factors[] = ['AI Sentiment', $rs['sentiment_score'], '#f59e0b'];
        }
        foreach ($factors as [$label, $value, $color]):
        ?>
        <div>
            <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                <span style="color: var(--text-secondary);"><?= $label ?></span>
                <span style="color: var(--text-primary); font-weight: 500;"><?= $value ?>/100</span>
            </div>
            <div style="height: 6px; background: var(--bg-tertiary); border-radius: 3px; overflow: hidden;">
                <div style="width: <?= $value ?>%; height: 100%; background: <?= $color ?>; border-radius: 3px; opacity: 0.7;"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Explanations -->
    <details>
        <summary style="cursor: pointer; font-size: 13px; color: var(--accent-light); margin-bottom: 8px;">View detailed analysis</summary>
        <div style="font-family: monospace; font-size: 12px; color: var(--text-secondary); line-height: 1.8; background: var(--bg-tertiary); padding: 16px; border-radius: 12px;">
            <?php foreach ($rs['explanations_array'] as $line): ?>
            <div style="<?= str_starts_with($line, '  ') ? 'padding-left: 16px; color: var(--text-muted);' : 'margin-top: 8px; font-weight: 500;' ?>">
                <?= htmlspecialchars($line) ?>
            </div>
            <?php endforeach; ?>
        </div>
    </details>
</div>
<?php endforeach; ?>

<?php if (empty($riskScores)): ?>
<div class="glass-card" style="padding: 40px; text-align: center;">
    <p style="color: var(--text-muted);">No risk data yet. Complete attendance sessions to generate risk scores.</p>
</div>
<?php endif; ?>

<!-- Attendance History -->
<?php if (!empty($attendanceHistory)): ?>
<div class="glass-card" style="padding: 24px; margin-bottom: 24px;">
    <h2 style="font-size: 16px; font-weight: 600; margin: 0 0 16px 0;">Attendance History</h2>
    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px;">
        <?php foreach ($attendanceHistory as $a):
            $colors = [
                'attending' => 'var(--risk-low)',
                'late' => 'var(--risk-medium)',
                'not_attending' => 'var(--risk-high)',
                'authorised_absence' => 'var(--accent)',
            ];
            $color = $colors[$a['status']] ?? 'var(--text-muted)';
        ?>
        <div title="Week <?= $a['week_number'] ?> <?= $a['day_of_week'] ?>: <?= $a['status'] ?><?= $a['notes'] ? ' - ' . htmlspecialchars($a['notes']) : '' ?>"
             style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;
                    font-size: 11px; font-weight: 500; cursor: help;
                    background: <?= $color ?>20; color: <?= $color ?>; border: 1px solid <?= $color ?>40;">
            <?= $a['week_number'] ?>
        </div>
        <?php endforeach; ?>
    </div>
    <div style="display: flex; gap: 16px; font-size: 11px; color: var(--text-muted);">
        <span style="display: flex; align-items: center; gap: 4px;"><span style="width: 8px; height: 8px; border-radius: 4px; background: var(--risk-low);"></span> Present</span>
        <span style="display: flex; align-items: center; gap: 4px;"><span style="width: 8px; height: 8px; border-radius: 4px; background: var(--risk-medium);"></span> Late</span>
        <span style="display: flex; align-items: center; gap: 4px;"><span style="width: 8px; height: 8px; border-radius: 4px; background: var(--risk-high);"></span> Absent</span>
        <span style="display: flex; align-items: center; gap: 4px;"><span style="width: 8px; height: 8px; border-radius: 4px; background: var(--accent);"></span> Authorised</span>
    </div>
</div>
<?php endif; ?>

<!-- Sentiment Analysis -->
<?php if (!empty($sentiments)): ?>
<div class="glass-card" style="padding: 24px;">
    <h2 style="font-size: 16px; font-weight: 600; margin: 0 0 16px 0;">AI Sentiment Analysis</h2>
    <?php foreach ($sentiments as $sc): ?>
    <div style="display: flex; align-items: center; gap: 16px; padding: 12px 0; border-bottom: 1px solid var(--border);">
        <div style="font-size: 20px; font-weight: 700; color: <?= $sc['score'] >= 65 ? 'var(--risk-low)' : ($sc['score'] >= 40 ? 'var(--risk-medium)' : 'var(--risk-high)') ?>;">
            <?= $sc['score'] ?>
        </div>
        <div>
            <p style="font-size: 13px; color: var(--text-primary);"><?= htmlspecialchars($sc['course_name']) ?></p>
            <p style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($sc['summary']) ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
