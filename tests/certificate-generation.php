<?php
// Run with: php tests/certificate-generation.php
require __DIR__ . '/../https-redirection/vendor/autoload.php';
$root = sys_get_temp_dir() . '/ehssl-generation-' . bin2hex(random_bytes(8));
mkdir($root . '/classes', 0700, true);
define('ABSPATH', $root . '/site/');
define('EASY_HTTPS_SSL_PATH', $root);
file_put_contents($root . '/classes/ehssl-certificate-storage.php', '<?php');
file_put_contents($root . '/classes/ehssl-acme-client.php', '<?php');
require __DIR__ . '/../https-redirection/classes/ehssl-certificate-storage.php';
require __DIR__ . '/../https-redirection/classes/ehssl-ssl-certificate.php';
$options = array();
$httpsrdrctn_options = array();
class WP_Error { public function __construct($code, $message) {} }
class EHSSL_Logger { public static function log($message) {} }
class EHSSL_Utils {
    public static function get_domain() { return 'example.org'; }
    public static function get_domain_variant($domain) { return 'www.example.org'; }
    public static function is_domain_accessible($domain) { return false; }
}
function get_option($name, $default = false) { global $options; return $options[$name] ?? $default; }
function add_option($name, $value, $unused = '', $autoload = false) { global $options; $options[$name] = $value; }
function update_option($name, $value) { global $options; $options[$name] = $value; }
function wp_mkdir_p($directory) { return is_dir($directory) || mkdir($directory, 0755, true); }
function is_wp_error($value) { return $value instanceof WP_Error; }
function site_url($path) { return 'https://example.org/' . $path; }
function set_url_scheme($url, $scheme) { return preg_replace('/^https?/', $scheme, $url); }
function wp_remote_get($url, $args) { return 403; }
function wp_remote_retrieve_response_code($response) { return $response; }
function __($text, $domain) { return $text; }
class EHSSL_Test_Certificate {
    public function getCertificate($as_chain = true) { return $as_chain ? 'leaf-and-chain' : 'leaf-only'; }
    public function getIntermediate() { return 'intermediate-chain'; }
    public function getPrivateKey() { return 'test-private-key'; }
    public function getExpiryDate() { return new DateTime('2027-01-01'); }
}
class EHSSL_ACME_Client {
    public static $scenario = 'success';
    public function __construct($config) {
        $config['fs']->write('le/test/account.pem', 'test-account-key');
    }
    public function createOrder($domains) { return new stdClass(); }
    public function authorize($order) {
        $auth = new Afosto\Acme\Data\Authorization('example.org', '2027-01-01', 'digest');
        $auth->addChallenge(new Afosto\Acme\Data\Challenge('https://acme.invalid/authz', 'http-01', 'pending', 'https://acme.invalid/challenge', 'test-token'));
        return array($auth);
    }
    public function selfTest($authorization, $type) {
        if (!is_file(ABSPATH . '.well-known/acme-challenge/test-token')) { throw new RuntimeException('Challenge was not written.'); }
        return self::$scenario !== 'self-test-failure';
    }
    public function validate($challenge, $attempts) { return self::$scenario !== 'validation-failure'; }
    public function isReady($order) { return true; }
    public function getCertificate($order) {
        if (self::$scenario === 'issuance-error') { throw new TypeError('Simulated issuance error'); }
        return new EHSSL_Test_Certificate();
    }
}
try {
    foreach (array('success', 'self-test-failure', 'validation-failure', 'issuance-error') as $scenario) {
        EHSSL_ACME_Client::$scenario = $scenario;
        $mask = umask();
        $result = (new EHSSL_SSL_Certificate())->handle_ssl_installation('test@example.org');
        if (is_wp_error($result) !== ($scenario !== 'success')) { throw new RuntimeException('Unexpected result for ' . $scenario); }
        if (glob(ABSPATH . '.well-known/acme-challenge/*')) { throw new RuntimeException('Challenge left after ' . $scenario); }
        if (umask() !== $mask) { throw new RuntimeException('Process umask was not restored.'); }
    }
    $storage = EHSSL_Certificate_Storage::directory();
    foreach (array('/certificate/private.pem', '/account/le/test/account.pem') as $path) {
        if ((fileperms($storage . $path) & 0777) !== 0600) { throw new RuntimeException('Incorrect key permissions: ' . $path); }
    }
    if (file_get_contents($storage . '/certificate/certificate.crt') !== 'leaf-only') { throw new RuntimeException('CRT includes the chain.'); }
    foreach (EHSSL_SSL_Certificate::get_certificate_urls() as $file) {
        if (isset($file['url'])) { throw new RuntimeException('Public download URL present.'); }
    }
    echo "PASS: generation success/failures, challenge cleanup, umask restoration, real Flysystem account permissions, leaf-only CRT, and no public download URLs.\n";
} finally {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $file) {
        if ($file->isDir() && !$file->isLink()) { rmdir($file->getPathname()); } else { unlink($file->getPathname()); }
    }
    rmdir($root);
}
