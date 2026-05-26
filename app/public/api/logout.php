<?php
require_once __DIR__ . '/auth_helper.php';

startSession();
$_SESSION = [];
session_destroy();

jsonResponse(['success' => true]);
