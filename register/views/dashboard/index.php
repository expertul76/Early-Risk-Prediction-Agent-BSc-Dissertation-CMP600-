<div style="margin-bottom: 24px;">
    <h1 style="font-size: 24px; font-weight: 700; margin: 0;">Dashboard</h1>
    <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Welcome back, <?= htmlspecialchars($user['first_name']) ?></p>
</div>

<!-- Stat Cards -->
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 32px;">
    <div class="stat-card">
        <div class="stat-label">Active Courses</div>
        <div class="stat-value" style="color: var(--accent-light);"><?= $courseCount ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Students</div>
        <div class="stat-value" style="color: var(--text-primary);"><?= $studentCount ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Staff Members</div>
        <div class="stat-value" style="color: var(--text-primary);"><?= $staffCount ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">High Risk Students</div>
        <div class="stat-value" style="color: var(--risk-high);"><?= $highRiskCount ?></div>
    </div>
</div>

<!-- Courses Overview -->
<div class="glass-card" style="padding: 24px; margin-bottom: 24px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
        <h2 style="font-size: 16px; font-weight: 600; margin: 0;">Courses</h2>
        <?php if ($user['role'] === 'admin'): ?>
        <a href="/courses/create" class="btn btn-primary btn-sm">+ New Course</a>
        <?php endif; ?>
    </div>

    <?php if (empty($courses)): ?>
    <div style="text-align: center; padding: 40px 0;">
        <svg style="width: 48px; height: 48px; color: var(--text-muted); margin: 0 auto 12px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
        <p style="color: var(--text-muted); font-size: 14px;">No courses yet.</p>
        <?php if ($user['role'] === 'admin'): ?>
        <a href="/courses/create" class="btn btn-secondary btn-sm" style="margin-top: 12px;">Create your first course</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Course</th>
                <th>Students</th>
                <th>Progress</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($courses as $course): ?>
            <tr>
                <td>
                    <a href="/courses/<?= $course['id'] ?>" style="color: var(--text-primary); text-decoration: none; font-weight: 500;">
                        <?= htmlspecialchars($course['name']) ?>
                    </a>
                    <br><span style="font-size: 12px; color: var(--text-muted); font-family: monospace;"><?= htmlspecialchars($course['code']) ?></span>
                </td>
                <td style="color: var(--text-secondary);"><?= $course['student_count'] ?></td>
                <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="flex: 1; height: 4px; background: var(--bg-tertiary); border-radius: 2px; overflow: hidden;">
                            <?php $pct = $course['total_sessions'] > 0 ? round($course['completed_sessions'] / $course['total_sessions'] * 100) : 0; ?>
                            <div style="width: <?= $pct ?>%; height: 100%; background: var(--accent); border-radius: 2px;"></div>
                        </div>
                        <span style="font-size: 12px; color: var(--text-muted);"><?= $course['completed_sessions'] ?>/<?= $course['total_sessions'] ?></span>
                    </div>
                </td>
                <td>
                    <?php if ($course['is_active']): ?>
                    <span class="badge-low">Active</span>
                    <?php else: ?>
                    <span style="color: var(--text-muted); font-size: 12px;">Inactive</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="/courses/<?= $course['id'] ?>" class="btn btn-secondary btn-sm">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Top Risk Students -->
<?php if (!empty($topRiskStudents)): ?>
<div class="glass-card" style="padding: 24px;">
    <h2 style="font-size: 16px; font-weight: 600; margin: 0 0 16px 0;">Highest Risk Students</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Course</th>
                <th>Score</th>
                <th>Risk</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($topRiskStudents as $rs): ?>
            <tr>
                <td>
                    <a href="/students/<?= $rs['student_id'] ?>" style="color: var(--text-primary); text-decoration: none;">
                        <?= htmlspecialchars($rs['first_name'] . ' ' . $rs['last_name']) ?>
                    </a>
                </td>
                <td style="color: var(--text-muted); font-family: monospace; font-size: 12px;"><?= htmlspecialchars($rs['course_code']) ?></td>
                <td style="font-weight: 600;"><?= $rs['composite_score'] ?></td>
                <td><span class="badge-<?= $rs['classification'] ?>"><?= strtoupper($rs['classification']) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
