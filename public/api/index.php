<?php
require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/AuthService.php';
require_once __DIR__ . '/../../src/ApiController.php';

$config = require __DIR__ . '/../../src/config.php';
$db = new Database($config['db']);
$pdo = $db->pdo();
$authService = new AuthService($pdo, $config['jwt']);
$api = new ApiController($pdo);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

switch ([$method, $path]) {
    case ['POST', '/api/auth/request-otp']:
        $input = json_decode(file_get_contents('php://input'), true);
        $authService->requestOtp($input['phone'] ?? '');
        break;
    case ['POST', '/api/auth/verify-otp']:
        $input = json_decode(file_get_contents('php://input'), true);
        $authService->verifyOtp($input['phone'] ?? '', $input['otp'] ?? '');
        break;
    case ['POST', '/api/auth/admin/login']:
        $input = json_decode(file_get_contents('php://input'), true);
        $authService->adminLogin($input['phone'] ?? '', $input['password'] ?? '');
        break;
    case ['POST', '/api/auth/refresh']:
        $input = json_decode(file_get_contents('php://input'), true);
        $authService->refreshToken((int)($input['user_id'] ?? 0), $input['device_id'] ?? 'web', $input['refresh_token'] ?? '');
        break;
    case ['POST', '/api/auth/logout']:
        $input = json_decode(file_get_contents('php://input'), true);
        $authService->logout((int)($input['user_id'] ?? 0), $input['device_id'] ?? 'web', $input['refresh_token'] ?? '');
        break;
    default:
        routeProtected($path, $method, $authService, $api);
}

function routeProtected(string $path, string $method, AuthService $auth, ApiController $api): void
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $payload = $auth->authenticate($header);
    if (!$payload) {
        Response::json(['error' => 'Unauthorized'], 401);
        return;
    }

    if ($method === 'GET' && $path === '/api/mosques') {
        $api->mosques();
        return;
    }

    if ($method === 'GET' && preg_match('#^/api/mosques/(\d+)$#', $path, $m)) {
        $api->mosque((int)$m[1]);
        return;
    }

    if ($method === 'GET' && preg_match('#^/api/mosques/(\d+)/today$#', $path, $m)) {
        $api->today((int)$m[1]);
        return;
    }

    if ($method === 'GET' && preg_match('#^/api/mosques/(\d+)/next-prayer$#', $path, $m)) {
        $api->nextPrayer((int)$m[1]);
        return;
    }

    if ($method === 'POST' && $path === '/api/admin/prayer-times/update') {
        $body = json_decode(file_get_contents('php://input'), true);
        if ($payload['role'] === 'ADMIN' || $payload['role'] === 'SUPER') {
            $api->updateDaily($body);
            return;
        }
    }

    if ($method === 'POST' && $path === '/api/admin/jummah') {
        $body = json_decode(file_get_contents('php://input'), true);
        if ($payload['role'] === 'ADMIN' || $payload['role'] === 'SUPER') {
            $api->updateJummah($body);
            return;
        }
    }

    Response::json(['error' => 'Not found or forbidden'], 404);
}
