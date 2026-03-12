<?php
$targetDir = __DIR__ . '/vendor/ralouphie/getallheaders/src';
$targetFile = $targetDir . '/getallheaders.php';

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$polyfill = <<<'PHP'
<?php
if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$key] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
        }
        return $headers;
    }
}
PHP;

file_put_contents($targetFile, $polyfill);

header('Content-Type: application/json');
echo json_encode([
    'ok' => file_exists($targetFile),
    'path' => $targetFile,
]);
