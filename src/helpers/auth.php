<?php

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function requireAuth()
{
    if (!isLoggedIn()) {
        header('Location: /login');
        exit;
    }
}

function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireAdmin()
{
    requireAuth();

    if (!isAdmin()) {
        // Show access denied page
        include __DIR__ . '/../views/errors/access_denied.php';
        exit;
    }
}

function isCustomer()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}