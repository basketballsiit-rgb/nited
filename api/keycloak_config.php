<?php
// api/keycloak_config.php
// Configuration for Keycloak SSO

define('KEYCLOAK_URL', 'http://service.npc.ac.th:8080'); // URL ของวิทยาลัย
define('KEYCLOAK_REALM', 'NPC-SSO'); // Realm ของวิทยาลัย
define('KEYCLOAK_CLIENT_ID', 'nited-app'); // รบกวนเปลี่ยนให้ตรงกับใน Keycloak
define('KEYCLOAK_CLIENT_SECRET', 'your-client-secret'); // รบกวนนำ Secret จาก Keycloak มาใส่ตรงนี้ครับ

// Endpoints
define('KEYCLOAK_AUTH_URL', KEYCLOAK_URL . '/realms/' . KEYCLOAK_REALM . '/protocol/openid-connect/auth');
define('KEYCLOAK_TOKEN_URL', KEYCLOAK_URL . '/realms/' . KEYCLOAK_REALM . '/protocol/openid-connect/token');
define('KEYCLOAK_USERINFO_URL', KEYCLOAK_URL . '/realms/' . KEYCLOAK_REALM . '/protocol/openid-connect/userinfo');
define('KEYCLOAK_LOGOUT_URL', KEYCLOAK_URL . '/realms/' . KEYCLOAK_REALM . '/protocol/openid-connect/logout');

// Redirect URI (Must match what is configured in Keycloak Client exactly)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
define('KEYCLOAK_REDIRECT_URI', $protocol . '://' . $host . '/nited/keycloak_callback.php');
?>
