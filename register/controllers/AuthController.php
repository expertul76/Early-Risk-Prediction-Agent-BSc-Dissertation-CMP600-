<?php

require_once __DIR__ . '/../models/School.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {

    public function loginPage(): void {
        if (Auth::check()) {
            Response::redirect('/dashboard');
        }
        $title = 'Login';
        ob_start();
        require __DIR__ . '/../views/auth/login.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    public function login(): void {
        Auth::requireCsrf();

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            Response::flash('error', 'Email and password are required.');
            Response::redirect('/login');
        }

        // Find user across all schools
        $user = Database::fetch(
            'SELECT u.*, s.name as school_name FROM users u JOIN schools s ON u.school_id = s.id WHERE u.email = ? AND u.is_active = 1',
            [$email]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Response::flash('error', 'Invalid email or password.');
            Response::redirect('/login');
        }

        Auth::login($user['id']);
        Response::redirect('/dashboard');
    }

    public function registerPage(): void {
        if (Auth::check()) {
            Response::redirect('/dashboard');
        }
        $title = 'Register School';
        ob_start();
        require __DIR__ . '/../views/auth/register.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    public function register(): void {
        Auth::requireCsrf();

        $v = new Validator($_POST);
        $v->required('school_name', 'School name')
          ->required('first_name', 'First name')
          ->required('last_name', 'Last name')
          ->required('email', 'Email')->email('email', 'Email')
          ->required('password', 'Password')->minLength('password', 8, 'Password')
          ->matches('password', 'password_confirm', 'Password confirmation');

        if (!$v->passes()) {
            Response::flash('error', $v->firstError());
            Response::redirect('/register');
        }

        // Check if email already exists
        $existing = Database::fetch('SELECT id FROM users WHERE email = ?', [$_POST['email']]);
        if ($existing) {
            Response::flash('error', 'An account with this email already exists.');
            Response::redirect('/register');
        }

        // Create school
        $schoolId = School::create($_POST['school_name'], $_POST['email']);

        // Create admin user
        $userId = User::create([
            'school_id' => $schoolId,
            'email' => $_POST['email'],
            'password_hash' => password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]),
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'role' => 'admin',
            'is_active' => 1,
        ]);

        Auth::login($userId);
        Response::flash('success', 'School registered successfully! Welcome to the dashboard.');
        Response::redirect('/dashboard');
    }

    public function logout(): void {
        Auth::logout();
        Response::redirect('/login');
    }

    public function invitePage(array $params): void {
        $token = $params['token'] ?? '';
        $invite = User::findByInviteToken($token);

        if (!$invite) {
            Response::flash('error', 'Invalid or expired invitation link.');
            Response::redirect('/login');
        }

        $title = 'Accept Invitation';
        ob_start();
        require __DIR__ . '/../views/auth/accept-invite.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    public function acceptInvite(array $params): void {
        Auth::requireCsrf();

        $token = $params['token'] ?? '';
        $invite = User::findByInviteToken($token);

        if (!$invite) {
            Response::flash('error', 'Invalid or expired invitation link.');
            Response::redirect('/login');
        }

        $v = new Validator($_POST);
        $v->required('password', 'Password')->minLength('password', 8, 'Password')
          ->matches('password', 'password_confirm', 'Password confirmation');

        if (!$v->passes()) {
            Response::flash('error', $v->firstError());
            Response::redirect('/invite/' . $token);
        }

        User::activate($invite['id'], password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]));
        Auth::login($invite['id']);
        Response::flash('success', 'Welcome! Your account is now active.');
        Response::redirect('/dashboard');
    }
}
