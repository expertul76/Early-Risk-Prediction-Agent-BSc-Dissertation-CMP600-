<?php
require_once __DIR__ . '/auth_helper.php';

$user = getCurrentUser();

if (!$user) {
    jsonResponse(['user' => null]);
}

jsonResponse(['user' => $user]);
