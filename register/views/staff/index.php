<div style="margin-bottom: 24px;">
    <h1 style="font-size: 24px; font-weight: 700; margin: 0;">Staff Management</h1>
    <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Manage staff members and send invitations</p>
</div>

<?php if ($user['role'] === 'admin'): ?>
<div class="glass-card" style="padding: 24px; margin-bottom: 24px;">
    <h2 style="font-size: 16px; font-weight: 600; margin: 0 0 16px 0;">Invite Staff Member</h2>
    <form method="POST" action="/staff/invite" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 12px; align-items: end;">
        <?= Auth::csrfField() ?>
        <div>
            <label class="form-label">First Name</label>
            <input type="text" name="first_name" class="form-input" placeholder="Jane" required>
        </div>
        <div>
            <label class="form-label">Last Name</label>
            <input type="text" name="last_name" class="form-input" placeholder="Smith" required>
        </div>
        <div>
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-input" placeholder="jane@school.ac.uk" required>
        </div>
        <button type="submit" class="btn btn-primary">Send Invite</button>
    </form>
</div>
<?php endif; ?>

<div class="glass-card" style="padding: 24px;">
    <h2 style="font-size: 16px; font-weight: 600; margin: 0 0 16px 0;">Staff Members</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <?php if ($user['role'] === 'admin'): ?><th></th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($staff as $member): ?>
            <tr>
                <td style="color: var(--text-primary);"><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></td>
                <td style="color: var(--text-secondary);"><?= htmlspecialchars($member['email']) ?></td>
                <td><span class="badge-<?= $member['role'] === 'admin' ? 'low' : 'medium' ?>"><?= ucfirst($member['role']) ?></span></td>
                <td>
                    <?php if ($member['is_active']): ?>
                        <span style="color: var(--risk-low); font-size: 12px;">Active</span>
                    <?php else: ?>
                        <span style="color: var(--text-muted); font-size: 12px;">Pending invite</span>
                    <?php endif; ?>
                </td>
                <?php if ($user['role'] === 'admin'): ?>
                <td>
                    <?php if ((int)$member['id'] !== (int)$user['id']): ?>
                    <form method="POST" action="/staff/<?= $member['id'] ?>/remove" style="display: inline;" onsubmit="return confirm('Remove this staff member?')">
                        <?= Auth::csrfField() ?>
                        <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                    </form>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
