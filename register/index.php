<?php
// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Load config
require_once __DIR__ . '/config.php';

// Autoload core
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Request.php';
require_once __DIR__ . '/core/Response.php';
require_once __DIR__ . '/core/Validator.php';
require_once __DIR__ . '/core/Mailer.php';

// Initialize session and auth
Auth::init();

// Define routes
// Auth
Router::get('/', 'AuthController', 'loginPage');
Router::get('/login', 'AuthController', 'loginPage');
Router::post('/login', 'AuthController', 'login');
Router::get('/register', 'AuthController', 'registerPage');
Router::post('/register', 'AuthController', 'register');
Router::get('/logout', 'AuthController', 'logout');
Router::get('/invite/{token}', 'AuthController', 'invitePage');
Router::post('/invite/{token}', 'AuthController', 'acceptInvite');

// Dashboard
Router::get('/dashboard', 'DashboardController', 'index');

// Courses
Router::get('/courses', 'CourseController', 'index');
Router::get('/courses/create', 'CourseController', 'createPage');
Router::post('/courses/create', 'CourseController', 'create');
Router::get('/courses/{id}/edit', 'CourseController', 'editPage');
Router::post('/courses/{id}/edit', 'CourseController', 'edit');
Router::get('/courses/{id}', 'CourseController', 'overview');

// Staff
Router::get('/staff', 'StaffController', 'index');
Router::post('/staff/invite', 'StaffController', 'sendInvite');
Router::post('/staff/{id}/remove', 'StaffController', 'remove');

// Student Groups
Router::get('/groups', 'GroupController', 'index');
Router::get('/groups/create', 'GroupController', 'createPage');
Router::post('/groups/create', 'GroupController', 'create');
Router::get('/groups/{id}', 'GroupController', 'students');
Router::post('/groups/{id}/add-student', 'GroupController', 'addStudent');
Router::post('/groups/{id}/import-csv', 'GroupController', 'importCsv');
Router::get('/groups/{id}/assign-course', 'GroupController', 'assignCoursePage');
Router::post('/groups/{id}/assign-course', 'GroupController', 'assignCourse');

// Attendance
Router::get('/attendance/{courseId}', 'AttendanceController', 'sessions');
Router::get('/attendance/session/{id}', 'AttendanceController', 'registerPage');
Router::post('/attendance/session/{id}', 'AttendanceController', 'saveRegister');
Router::post('/attendance/session/{id}/complete', 'AttendanceController', 'complete');

// Assessments
Router::get('/assessments/{courseId}', 'AssessmentController', 'resultsPage');
Router::post('/assessments/{courseId}', 'AssessmentController', 'saveResults');

// Students
Router::get('/students/{id}', 'StudentController', 'detail');

// API
Router::post('/api/risk/calculate/{courseId}', 'ApiController', 'calculateRisk');
Router::post('/api/sentiment/{courseId}', 'ApiController', 'runSentiment');

// Dispatch
$uri = Request::uri();
$method = Request::method();
Router::dispatch($uri, $method);
