<?php
// Run with: php tests/acme-http-authorization.php
require_once __DIR__ . '/../https-redirection/vendor/autoload.php';
require_once __DIR__ . '/../https-redirection/classes/ehssl-acme-client.php';

set_error_handler(function ($severity, $message, $file, $line) {
    throw new \ErrorException($message, 0, $severity, $file, $line);
}, E_WARNING | E_NOTICE);

class EHSSL_Test_ACME_Client extends EHSSL_ACME_Client
{
    public $response_data;
    public function __construct() {}
    protected function getDigest(): string { return 'test-digest'; }
    protected function signPayloadKid($payload, $url): array { return array(); }
    protected function request($url, $payload = array(), $method = 'POST'): \Psr\Http\Message\ResponseInterface
    {
        return new \GuzzleHttp\Psr7\Response(200, array(), json_encode($this->response_data));
    }
}

$order = new \Afosto\Acme\Data\Order(
    array('example.org'), 'https://acme.invalid/order/1', 'pending',
    '2026-11-01T00:00:00Z', array(), array('https://acme.invalid/authz/1'),
    'https://acme.invalid/finalize/1', null
);
$http = array('type' => 'http-01', 'status' => 'pending', 'url' => 'https://acme.invalid/chall/1', 'token' => 'test-token');
$persist = array('type' => 'dns-persist-01', 'status' => 'pending', 'url' => 'https://acme.invalid/chall/2');
$client = new EHSSL_Test_ACME_Client();
$base = array('identifier' => array('value' => 'example.org'), 'expires' => '2026-11-01T00:00:00Z');
foreach (array(array($persist, $http), array($http, $persist), array($http)) as $challenges) {
    $client->response_data = $base + array('challenges' => $challenges);
    $authorizations = $client->authorize($order);
    if (count($authorizations) !== 1 || count($authorizations[0]->getChallenges()) !== 1
        || $authorizations[0]->getFile()->getContents() !== 'test-token.test-digest') {
        throw new \RuntimeException('HTTP challenge selection or key authorization changed.');
    }
}
$missing_token = $http;
unset($missing_token['token']);
foreach (array(
    array(array($persist), 'did not offer an HTTP-01'),
    array(array($missing_token), 'missing or invalid token'),
) as $case) {
    $client->response_data = $base + array('challenges' => $case[0]);
    try {
        $client->authorize($order);
    } catch (\RuntimeException $error) {
        if (strpos($error->getMessage(), $case[1]) !== false) {
            continue;
        }
        throw $error;
    }
    throw new \RuntimeException('Expected a controlled authorization error.');
}
echo "PASS: HTTP selection, tokenless unsupported challenges, missing HTTP challenge, and malformed HTTP token.\n";
