<?php
// Run with: php tests/certificate-storage.php
$root = sys_get_temp_dir() . '/ehssl-test-' . bin2hex(random_bytes(8));
mkdir($root);
define('ABSPATH', $root . '/');
$options = array();
$http_status = 403;
$capability = false;
$nonce_valid = false;
$http_requests = array();
$http_responses = array();
$control_mode = '';
class WP_Error {
    public function get_error_message() { return 'cURL error 60: SSL certificate problem: unable to get local issuer certificate'; }
}
function get_option($key, $default = false) { global $options; return $options[$key] ?? $default; }
function add_option($key, $value, $unused = '', $autoload = null) { global $options; if (!isset($options[$key])) { $options[$key] = $value; } }
function wp_delete_file($path) { unlink($path); }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function wp_mkdir_p($path) { return is_dir($path) || mkdir($path, 0755, true); }
function site_url($path) { return 'https://example.org/' . $path; }
function set_url_scheme($url, $scheme) { return preg_replace('/^https?/', $scheme, $url); }
function wp_remote_get($url, $args) {
    global $http_status, $http_requests, $http_responses, $control_mode;
    $http_requests[] = array($url, $args);
    if ($control_mode && strpos($url, '/ehssl-control-') !== false) {
        $body = $control_mode === 'valid' ? file_get_contents(ABSPATH . ltrim(parse_url($url, PHP_URL_PATH), '/')) : 'unrelated error page';
        return array('status' => 200, 'body' => $body);
    }
    return $http_responses ? array_shift($http_responses) : $http_status;
}
function wp_remote_retrieve_header($response, $header) { return $response['location'] ?? ''; }
function wp_remote_retrieve_body($response) { return $response['body'] ?? ''; }
function wp_remote_retrieve_response_code($response) { return is_array($response) ? $response['status'] : $response; }
function is_wp_error($value) { return $value instanceof WP_Error; }
function current_user_can($cap) { global $capability; return $capability; }
function check_admin_referer($action) { global $nonce_valid; if (!$nonce_valid) { throw new RuntimeException('nonce rejected'); } }
function wp_die($message, $title = '', $args = array()) { throw new RuntimeException($message); }
function wp_unslash($value) { return stripslashes($value); }
function esc_html($value) { return $value; }
require __DIR__ . '/../https-redirection/classes/ehssl-certificate-storage.php';
function expect_error($call, $text) {
    try { $call(); } catch (RuntimeException $error) {
        if (strpos($error->getMessage(), $text) !== false) { return; }
        throw $error;
    }
    throw new RuntimeException('Expected error: ' . $text);
}
function check($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
try {
    expect_error(function () { EHSSL_Certificate_Storage::directory(); }, 'not initialized');
    $directory = EHSSL_Certificate_Storage::prepare_storage();
    check($directory === EHSSL_Certificate_Storage::directory(true), 'Storage name changed.');
    check(strpos(file_get_contents($directory . '/.htaccess'), 'Require all denied') !== false, 'Missing deny rule.');
    check(!glob($directory . '/access-test-*'), 'Access probe left behind.');
    foreach (array(200, 301, 302, 404, 500, new WP_Error()) as $status) {
        $http_status = $status;
        expect_error(function () { EHSSL_Certificate_Storage::prepare_storage(); }, 'could not be verified');
        check(!glob($directory . '/access-test-*'), 'Failed probe left behind.');
    }
    $http_status = 403;
    $http_status = 404;
    $control_mode = 'valid';
    EHSSL_Certificate_Storage::prepare_storage();
    check(!glob(ABSPATH . '.well-known/ehssl-control-*'), 'Public control left behind.');
    $control_mode = 'wrong-body';
    expect_error(function () { EHSSL_Certificate_Storage::prepare_storage(); }, 'public control file');
    check(!glob(ABSPATH . '.well-known/ehssl-control-*'), 'Failed public control left behind.');
    $control_mode = '';
    $http_status = 403;
    $http_responses = array(new WP_Error());
    expect_error(function () { EHSSL_Certificate_Storage::prepare_storage(); }, 'cURL error 60');
    $http_responses = array(404);
    expect_error(function () { EHSSL_Certificate_Storage::prepare_storage(); }, 'HTTP returned HTTP 404');
    $http_responses = array(array('status' => 302, 'location' => 'https://example.org/login'), 403);
    expect_error(function () { EHSSL_Certificate_Storage::prepare_storage(); }, 'could not be verified');
    $http_responses = array();
    check((fileperms($directory) & 0777) === 0700, 'Wrong storage directory permissions.');
    foreach ($http_requests as $request) {
        check($request[1]['redirection'] === 0, 'Unrestricted redirects were enabled.');
    }
    EHSSL_Certificate_Storage::write($directory . '/private.pem', 'test-key');
    check((fileperms($directory . '/private.pem') & 0777) === 0600, 'Wrong key permissions.');
    symlink($directory . '/private.pem', $directory . '/linked.pem');
    expect_error(function () use ($directory) { EHSSL_Certificate_Storage::write($directory . '/linked.pem', 'replacement'); }, 'symbolic link');
    expect_error(array('EHSSL_Certificate_Storage', 'download'), 'not allowed');
    $capability = true;
    expect_error(array('EHSSL_Certificate_Storage', 'download'), 'nonce rejected');
    $nonce_valid = true;
    foreach (array('../private.pem', 'account.pem', array('private.pem')) as $name) {
        $_GET['file'] = $name;
        expect_error(array('EHSSL_Certificate_Storage', 'download'), 'Invalid certificate filename');
    }
    mkdir($directory . '/certificate');
    symlink($directory . '/private.pem', $directory . '/certificate/private.pem');
    $_GET['file'] = 'private.pem';
    expect_error(array('EHSSL_Certificate_Storage', 'download'), 'not found');
    echo "PASS: persistent random storage, denied/unverified HTTP access, probe cleanup, key permissions, symlinks, capabilities, nonce, and download allowlist.\n";
} finally {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $file) {
        if ($file->isDir() && !$file->isLink()) { rmdir($file->getPathname()); } else { unlink($file->getPathname()); }
    }
    rmdir($root);
}
