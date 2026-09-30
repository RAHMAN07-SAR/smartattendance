<?php
/**
 * SAMS — Supabase REST API helper (cURL)
 * All database operations go through here using the service-role key.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Make a request to the Supabase REST API.
 */
function db_request(
    string $method,
    string $path,
    ?array $body = null,
    array $queryParams = []
): array {
    $url = rtrim(SUPABASE_URL, '/') . '/rest/v1/' . ltrim($path, '/');

    if (!empty($queryParams)) {
        $url .= '?' . http_build_query($queryParams);
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => [
            'apikey: ' . SUPABASE_SERVICE_KEY,
            'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
            'Content-Type: application/json',
            'Prefer: return=representation',
        ],
        CURLOPT_TIMEOUT => 30,
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['error' => 'Connection failed: ' . $error, 'code' => 0];
    }

    $data = json_decode($response, true);
    if ($httpCode >= 400) {
        $msg = is_array($data) && isset($data['message']) ? $data['message'] : 'Database error';
        return ['error' => $msg, 'code' => $httpCode];
    }

    return ['data' => $data, 'code' => $httpCode];
}

/**
 * Call a Supabase RPC function.
 */
function db_rpc(string $function, array $params = []): array {
    $url = rtrim(SUPABASE_URL, '/') . '/rest/v1/rpc/' . $function;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'apikey: ' . SUPABASE_SERVICE_KEY,
            'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POSTFIELDS => json_encode($params),
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['error' => 'Connection failed: ' . $error, 'code' => 0];
    }

    $data = json_decode($response, true);
    if ($httpCode >= 400) {
        $msg = is_array($data) && isset($data['message']) ? $data['message'] : 'RPC error';
        return ['error' => $msg, 'code' => $httpCode];
    }

    return ['data' => $data, 'code' => $httpCode];
}

/**
 * Call Supabase Auth API (for login/register/token refresh).
 */
function supabase_auth(string $action, array $body): array {
    $url = rtrim(SUPABASE_URL, '/') . '/auth/v1/' . $action;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'apikey: ' . SUPABASE_ANON_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POSTFIELDS => json_encode($body),
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);
    if ($httpCode >= 400) {
        $msg = is_array($data) && isset($data['msg']) ? $data['msg'] : 'Auth error';
        return ['error' => $msg, 'code' => $httpCode];
    }

    return ['data' => $data, 'code' => $httpCode];
}
