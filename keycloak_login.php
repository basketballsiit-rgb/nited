<?php
require_once 'api/keycloak_config.php';

// Generate a random state to prevent CSRF
$state = bin2hex(random_bytes(16));
session_start();
$_SESSION['oauth_state'] = $state;

// Build the Keycloak authorization URL
$auth_url = KEYCLOAK_AUTH_URL . '?' . http_build_query([
    'client_id' => KEYCLOAK_CLIENT_ID,
    'redirect_uri' => KEYCLOAK_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state
]);

// Redirect to Keycloak login page
header('Location: ' . $auth_url);
exit;
?>
