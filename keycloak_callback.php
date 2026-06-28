<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once('config/db.php');
require_once 'api/keycloak_config.php';

// Check if there's an error from Keycloak
if (isset($_GET['error'])) {
    header('Location: index.php?error=' . urlencode('Keycloak Error: ' . $_GET['error_description']));
    exit;
}

// Verify state to prevent CSRF
if (empty($_GET['state']) || empty($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    header('Location: index.php?error=' . urlencode('Invalid OAuth state. Please try logging in again.'));
    exit;
}

// Unset the state so it can't be reused
unset($_SESSION['oauth_state']);

$code = $_GET['code'] ?? '';

if (empty($code)) {
    header('Location: index.php?error=' . urlencode('No authorization code received.'));
    exit;
}

// 1. Exchange the authorization code for an access token
$token_params = [
    'grant_type' => 'authorization_code',
    'client_id' => KEYCLOAK_CLIENT_ID,
    'client_secret' => KEYCLOAK_CLIENT_SECRET,
    'code' => $code,
    'redirect_uri' => KEYCLOAK_REDIRECT_URI
];

$ch = curl_init(KEYCLOAK_TOKEN_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_params));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded'
]);
// Ignore SSL verification if testing locally
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
$token_response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || !$token_response) {
    echo "Error fetching token. HTTP Code: $http_code <br> Response: " . $token_response;
    exit;
}

$token_data = json_decode($token_response, true);
$access_token = $token_data['access_token'] ?? '';
$id_token = $token_data['id_token'] ?? ''; // useful for logout

if (empty($access_token)) {
     header('Location: index.php?error=' . urlencode('Failed to obtain access token.'));
     exit;
}

// Store id_token in session for logout procedure later
$_SESSION['keycloak_id_token'] = $id_token;

// 2. Fetch User Profile Data from Keycloak UserInfo Endpoint
$ch = curl_init(KEYCLOAK_USERINFO_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token
]);
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$userinfo_response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || !$userinfo_response) {
     header('Location: index.php?error=' . urlencode('Failed to obtain user info.'));
     exit;
}

$userinfo = json_decode($userinfo_response, true);

// Extract info. (Adjust keys based on what Keycloak returns for your scope)
$kc_username = $userinfo['preferred_username'] ?? $userinfo['email'] ?? '';
$kc_email = $userinfo['email'] ?? '';
$kc_name = $userinfo['name'] ?? trim(($userinfo['given_name'] ?? '') . ' ' . ($userinfo['family_name'] ?? ''));

if (empty($kc_username)) {
    header('Location: index.php?error=' . urlencode('Could not identify user from Keycloak.'));
    exit;
}

// 3. User Auto-Provisioning & Local Login
try {
    // Check if user exists locally
       $stmt = $pdo->prepare("SELECT id, username, name, department, position, role FROM users WHERE username = ?");
       $stmt->execute([$kc_username]);    
    //$stmt = $pdo->prepare("SELECT id, username, name, department, role FROM users WHERE username = ? OR email = ?");
    //$stmt->execute([$kc_username, $kc_email]);
    $local_user = $stmt->fetch();

    if ($local_user) {
        // User exists, just log them in
        $_SESSION['user_id'] = $local_user['id'];
        $_SESSION['username'] = $local_user['username'];
        $_SESSION['name'] = $local_user['name'];
        $_SESSION['department'] = $local_user['department'];
        $_SESSION['position'] = $local_user['position'];
        $_SESSION['role'] = $local_user['role'];
        //$_SESSION['profile_picture'] = $local_user['profile_picture'];
        
        // Check if onboarding is needed (position is required in onboarding)
        if (empty($local_user['position'])) {
            $_SESSION['requires_onboarding'] = true;
        }
    } else {
        // Auto-provisioning: Create new user
        // Generate a random password since they use SSO
        $random_password = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT);
        
        $insertStmt = $pdo->prepare("
            INSERT INTO users (username, password, name, email, role, created_at) 
            VALUES (?, ?, ?, ?, 'teacher', NOW())
        ");
        $insertStmt->execute([
            $kc_username, 
            $random_password, 
            $kc_name,
            $kc_email
        ]);
        
        $new_user_id = $pdo->lastInsertId();
        
        // Log the newly created user in
        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['username'] = $kc_username;
        $_SESSION['name'] = $kc_name;
        $_SESSION['department'] = ''; // Empty initially, can be updated later
        $_SESSION['role'] = 'teacher';
        $_SESSION['profile_picture'] = '';
        $_SESSION['requires_onboarding'] = true; // Flag for first-time profile completion
    }

    // Login successful
    header('Location: index.php');
    exit;

} catch (PDOException $e) {
    error_log("SSO Auto-provisioning error: " . $e->getMessage());
     // header('Location: index.php?error=' . urlencode('Database error during SSO login.'));
        die("Database Error: " . $e->getMessage());    
exit;
}
?>
