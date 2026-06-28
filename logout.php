<?php
// logout.php
session_start();
require_once 'api/keycloak_config.php';

$id_token = $_SESSION['keycloak_id_token'] ?? '';

session_unset();
session_destroy();

if (!empty($id_token) && defined('KEYCLOAK_URL')) {
    // Redirect to Keycloak logout
    $realm = 'YOUR_REALM_HERE'; // It might be defined in config
    // The standard Keycloak logout URL logic:
    // Need post_logout_redirect_uri and id_token_hint
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $redirect_uri = urlencode($protocol . "://" . $host . "/nited/index.php");
    
    // Instead of hardcoding, try to get from config or construct
    $logout_url = KEYCLOAK_URL . "/realms/" . (defined('KEYCLOAK_REALM') ? KEYCLOAK_REALM : 'master') . "/protocol/openid-connect/logout";
    $logout_url .= "?id_token_hint=" . urlencode($id_token);
    $logout_url .= "&post_logout_redirect_uri=" . $redirect_uri;
    
    header('Location: ' . $logout_url);
    exit;
} else {
    header('Location: index.php');
    exit;
}
?>