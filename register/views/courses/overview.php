<div style="margin-bottom: 24px;">
    <a href="/courses" style="color: var(--text-muted); text-decoration: none; font-size: 13px;">&larr; Back to Courses</a>
    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 8px;">
        <div>
            <h1 style="font-size: 24px; font-weight: 700; margin: 0;"><?= htmlspecialchars($course['name']) ?></h1>
            <span style="font-family: monospace; font-size: 13px; color: var(--text-muted);"><?= htmlspecialchars($course['code']) ?> &middot; <?= $course['num_weeks'] ?> weeks &middot; Started <?= date('j M Y', strtotime($course['start_date'])) ?></span>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="/attendance/<?= $course['id'] ?>" class="btn btn-primary btn-sm">Attendance</a>
            <a href="/assessments/<?= $course['id'] ?>" class="btn btn-secondary btn-sm">Assessments</a>
            <?php if ($user['role'] === 'admin'): ?>
            <a href="/courses/<?= $course['id'] ?>/edit" class="btn btn-secondary btn-sm">Edit</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Stats -->
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;">
    <div class="stat-card">
        <div class="stat-label">Students</div>
        <div class="stat-value" style="color: var(--text-primary);"><?= count($students) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Sessions</div>
        <div class="stat-value" style="color: var(--accent-light);"><?= $completedSessions ?>/<?= $totalSessions ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Assessments</div>
        <div class="stat-value" style="color: var(--text-primary);"><?= count($assessments) ?></div>
    </div>
    <div class="stat-card">
        <?php
        $highRisk = 0; $medRisk = 0; $lowRisk = 0;
        foreach ($riskMap as $rs) {
            if ($rs['classification'] === 'high') $highRisk++;
            elseif ($rs['classification'] === 'medium') $medRisk++;
            else $lowRisk++;
        }
        ?>
        <div class="stat-label">High Risk</div>
        <div class="stat-value" style="color: var(--risk-high);"><?= $highRisk ?></div>
    </div>
</div>

<!-- Student Risk Table -->
<div class="glass-card" style="padding: 24px;">
    <h2 style="font-size: 16px; font-weight: 600; margin: 0 0 16px 0;">Students</h2>
    <?php if (empty($students)): ?>
    <p style="color: var(--text-muted); font-size: 14px; text-align: center; padding: 40px 0;">
        No students enrolled. <a href="/groups" style="color: var(--accent-light);">Assign a student group</a> to this course.
    </p>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Email</th>
                <th>Attendance</th>
                <th>Assessment</th>
                <th>Engagement</th>
                <th>Score</th>
                <th>Risk</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $s):
                $rs = $riskMap[$s['id']] ?? null;
            ?>
            <tr>
                <td>
                    <a href="/students/<?= $s['id'] ?>" style="color: var(--text-primary); text-decoration: none; font-weight: 500;">
                        <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?>
                    </a>
                </td>
                <td style="color: var(--text-muted); font-size: 12px;"><?= htmlspecialchars($s['email'] ?? '-') ?></td>
                <td style="color: var(--text-secondary);"><?= $rs ? $rs['attendance_score'] : '-' ?></td>
                <td style="color: var(--text-secondary);"><?= $rs ? $rs['assessment_score'] : '-' ?></td>
                <td style="color: var(--text-secondary);"><?= $rs ? $rs['engagement_score'] : '-' ?></td>
                <td style="font-weight: 600; color: var(--text-primary);"><?= $rs ? $rs['composite_score'] : '-' ?></td>
                <td>
                    <?php if ($rs): ?>
                    <span class="badge-<?= $rs['classification'] ?>"><?= strtoupper($rs['classification']) ?></span>
                    <?php else: ?>
                    <span style="color: var(--text-muted); font-size: 12px;">No data</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
