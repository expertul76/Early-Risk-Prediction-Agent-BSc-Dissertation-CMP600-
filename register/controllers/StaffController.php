<?php
require_once __DIR__ . '/../models/User.php';

class StaffController {
    public function index(): void {
        $user = Auth::requireAuth();
        $staff = User::listBySchool(Auth::schoolId());

        $title = 'Staff';
        ob_start();
        require __DIR__ . '/../views/staff/index.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    public function sendInvite(): void {
        $user = Auth::requireAdmin();
        Auth::requireCsrf();

        $v = new Validator($_POST);
        $v->required('first_name', 'First name')
          ->required('last_name', 'Last name')
          ->required('email', 'Email')->email('email', 'Email');

        if (!$v->passes()) {
            Response::flash('error', $v->firstError());
            Response::redirect('/staff');
        }

        // Check if already exists
        $existing = User::findByEmail(Auth::schoolId(), $_POST['email']);
        if ($existing) {
            Response::flash('error', 'A staff member with this email already exists.');
            Response::redirect('/staff');
        }

        $token = bin2hex(random_bytes(32));
        User::create([
            'school_id' => Auth::schoolId(),
            'email' => $_POST['email'],
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'role' => $_POST['role'] ?? 'staff',
            'invite_token' => $token,
            'invite_expires' => date('Y-m-d H:i:s', strtotime('+7 days')),
            'is_active' => 0,
        ]);

        $schoolName = $user['school_name'];
        Mailer::sendInvite($_POST['email'], $_POST['first_name'], $schoolName, $token);

        Response::flash('success', 'Invitation sent to ' . $_POST['email']);
        Response::redirect('/staff');
    }

    public function remove(array $params): void {
        Auth::requireAdmin();
        Auth::requireCsrf();

        $id = (int)($params['id'] ?? 0);
        $user = Auth::user();

        if ($id === (int)$user['id']) {
            Response::flash('error', 'You cannot remove yourself.');
            Response::redirect('/staff');
        }

        User::delete($id, Auth::schoolId());
        Response::flash('success', 'Staff member removed.');
        Response::redirect('/staff');
    }
}
