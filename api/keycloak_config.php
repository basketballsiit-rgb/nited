<?php
// api/keycloak_config.php
// Configuration for Keycloak SSO

define('KEYCLOAK_URL', 'https://sso.yourdomain.ac.th/auth');
define('KEYCLOAK_REALM', 'your-realm-name');
define('KEYCLOAK_CLIENT_ID', 'your-client-id');
define('KEYCLOAK_CLIENT_SECRET', 'your-client-secret');

// Endpoints
define('KEYCLOAK_AUTH_URL', KEYCLOAK_URL . '/realms/' . KEYCLOAK_REALM . '/protocol/openid-connect/auth');
define('KEYCLOAK_TOKEN_URL', KEYCLOAK_URL . '/realms/' . KEYCLOAK_REALM . '/protocol/openid-connect/token');
define('KEYCLOAK_USERINFO_URL', KEYCLOAK_URL . '/realms/' . KEYCLOAK_REALM . '/protocol/openid-connect/userinfo');

// Redirect URI (Must match what is configured in Keycloak Client exactly)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
define('KEYCLOAK_REDIRECT_URI', $protocol . '://' . $host . '/nited/keycloak_callback.php');
?>
