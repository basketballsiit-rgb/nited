<?php
// includes/auth.php
session_start();

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: /nited/index.php');
        exit;
    }
    
    // Enforce onboarding
    if (isset($_SESSION['requires_onboarding']) && $_SESSION['requires_onboarding'] === true) {
        $current_script = basename($_SERVER['PHP_SELF']);
        if ($current_script !== 'onboarding.php' && $current_script !== 'onboarding_action.php' && $current_script !== 'logout.php') {
            header('Location: /nited/onboarding.php');
            exit;
        }
    }
}

function requireRole($allowed_roles)
{
    if (!isLoggedIn()) {
        header('Location: /nited/index.php');
        exit;
    }

    // Enforce onboarding
    if (isset($_SESSION['requires_onboarding']) && $_SESSION['requires_onboarding'] === true) {
        $current_script = basename($_SERVER['PHP_SELF']);
        if ($current_script !== 'onboarding.php' && $current_script !== 'onboarding_action.php' && $current_script !== 'logout.php') {
            header('Location: /nited/onboarding.php');
            exit;
        }
    }

    if (!in_array($_SESSION['role'], (array) $allowed_roles)) {
        // Redirect to their respective dashboard instead of dying if possible
        redirectBasedOnRole();
    }
}

function redirectBasedOnRole()
{
    if (!isLoggedIn()) {
        header('Location: /nited/index.php');
        exit;
    }

    // Enforce onboarding
    if (isset($_SESSION['requires_onboarding']) && $_SESSION['requires_onboarding'] === true) {
        header('Location: /nited/onboarding.php');
        exit;
    }

    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: /nited/admin/dashboard.php');
            break;
        case 'teacher':
            header('Location: /nited/teacher/dashboard.php');
            break;
        case 'supervisor':
            header('Location: /nited/supervisor/dashboard.php');
            break;
        case 'executive':
            header('Location: /nited/executive/dashboard.php');
            break;
        default:
            header('Location: /nited/index.php');
            break;
    }
    exit;
}
?>