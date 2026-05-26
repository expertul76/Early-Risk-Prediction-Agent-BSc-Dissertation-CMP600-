<div style="margin-bottom: 24px;">
    <a href="/courses" style="color: var(--text-muted); text-decoration: none; font-size: 13px;">&larr; Back to Courses</a>
    <h1 style="font-size: 24px; font-weight: 700; margin: 8px 0 0 0;"><?= $course ? 'Edit ' . htmlspecialchars($course['name']) : 'Create Course' ?></h1>
</div>

<form method="POST" action="<?= $course ? '/courses/' . $course['id'] . '/edit' : '/courses/create' ?>">
    <?= Auth::csrfField() ?>

    <div class="glass-card" style="padding: 24px; margin-bottom: 24px;">
        <h2 style="font-size: 16px; font-weight: 600; margin: 0 0 16px 0;">Course Details</h2>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label class="form-label">Course Name</label>
                <input type="text" name="name" class="form-input" placeholder="Introduction to Computing" required value="<?= htmlspecialchars($course['name'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label">Course Code</label>
                <input type="text" name="code" class="form-input" placeholder="COMP101" required value="<?= htmlspecialchars($course['code'] ?? '') ?>" style="text-transform: uppercase;">
            </div>
        </div>

        <div style="margin-bottom: 16px;">
            <label class="form-label">Description (optional)</label>
            <textarea name="description" class="form-input" rows="3" placeholder="Course description..."><?= htmlspecialchars($course['description'] ?? '') ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div>
                <label class="form-label">Number of Weeks</label>
                <input type="number" name="num_weeks" class="form-input" min="1" max="52" required value="<?= $course['num_weeks'] ?? '12' ?>">
            </div>
            <div>
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-input" required value="<?= $course['start_date'] ?? date('Y-m-d') ?>">
            </div>
        </div>
    </div>

    <!-- Assessments Section -->
    <div class="glass-card" style="padding: 24px; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
            <h2 style="font-size: 16px; font-weight: 600; margin: 0;">Assessments</h2>
            <button type="button" id="add-assessment" class="btn btn-secondary btn-sm">+ Add Assessment</button>
        </div>

        <div id="assessments-container">
            <?php if (!empty($assessments)): ?>
                <?php foreach ($assessments as $i => $a): ?>
                <div class="assessment-row" style="display: grid; grid-template-columns: 2fr 1fr 80px 80px 40px; gap: 12px; align-items: end; margin-bottom: 12px;">
                    <div>
                        <?php if ($i === 0): ?><label class="form-label">Assessment Name</label><?php endif; ?>
                        <input type="text" name="assessment_name[]" class="form-input" value="<?= htmlspecialchars($a['name']) ?>" placeholder="Midterm Essay">
                    </div>
                    <div>
                        <?php if ($i === 0): ?><label class="form-label">Type</label><?php endif; ?>
                        <select name="assessment_type[]" class="form-select">
                            <option value="formative" <?= $a['type'] === 'formative' ? 'selected' : '' ?>>Formative</option>
                            <option value="summative" <?= $a['type'] === 'summative' ? 'selected' : '' ?>>Summative</option>
                        </select>
                    </div>
                    <div>
                        <?php if ($i === 0): ?><label class="form-label">Week</label><?php endif; ?>
                        <input type="number" name="assessment_week[]" class="form-input" min="1" value="<?= $a['week_due'] ?>">
                    </div>
                    <div>
                        <?php if ($i === 0): ?><label class="form-label">Weight</label><?php endif; ?>
                        <input type="number" name="assessment_weight[]" class="form-input" min="0.1" step="0.1" value="<?= $a['weight'] ?>">
                    </div>
                    <button type="button" onclick="this.closest('.assessment-row').remove()" class="btn btn-danger btn-sm" style="padding: 10px;">&times;</button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <p style="color: var(--text-muted); font-size: 12px; margin-top: 8px;">Summative assessments count double in risk scoring. Weight is relative (e.g., 2.0 = twice as important).</p>
    </div>

    <button type="submit" class="btn btn-primary"><?= $course ? 'Update Course' : 'Create Course' ?></button>
</form>

<script>
document.getElementById('add-assessment').addEventListener('click', function() {
    const container = document.getElementById('assessments-container');
    const row = document.createElement('div');
    row.className = 'assessment-row';
    row.style.cssText = 'display: grid; grid-template-columns: 2fr 1fr 80px 80px 40px; gap: 12px; align-items: end; margin-bottom: 12px;';
    row.innerHTML = `
        <div><input type="text" name="assessment_name[]" class="form-input" placeholder="Assessment name"></div>
        <div><select name="assessment_type[]" class="form-select"><option value="formative">Formative</option><option value="summative">Summative</option></select></div>
        <div><input type="number" name="assessment_week[]" class="form-input" min="1" value="1"></div>
        <div><input type="number" name="assessment_weight[]" class="form-input" min="0.1" step="0.1" value="1"></div>
        <button type="button" onclick="this.closest('.assessment-row').remove()" class="btn btn-danger btn-sm" style="padding: 10px;">&times;</button>
    `;
    container.appendChild(row);
});
</script>
