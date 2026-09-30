<?php
/**
 * SAMS — Authentication & Session Management
 * PHP sessions store the Supabase user ID and access token.
 * All API calls verify the session server-side.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

session_name(SESSION_NAME);
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
]);
session_start();

/**
 * Check if a user is logged in.
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['access_token']);
}

/**
 * Check if the current user is an admin.
 */
function is_admin(): bool {
    return ($_SESSION['role'] ?? '') === 'admin';
}

/**
 * Get the current user's ID.
 */
function current_user_id(): ?string {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get the current user's access token.
 */
function current_access_token(): ?string {
    return $_SESSION['access_token'] ?? null;
}

/**
 * Require authentication. Returns 401 if not logged in.
 */
function require_auth(): void {
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
}

/**
 * Require admin role. Returns 403 if not admin.
 */
function require_admin(): void {
    require_auth();
    if (!is_admin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit;
    }
}

/**
 * Log in a user with email and password.
 */
function login_user(string $email, string $password): array {
    $result = supabase_auth('token?grant_type=password', [
        'email' => $email,
        'password' => $password,
    ]);

    if (isset($result['error'])) {
        return ['error' => $result['error']];
    }

    $data = $result['data'];
    $userId = $data['user']['id'] ?? null;
    $accessToken = $data['access_token'] ?? null;

    if (!$userId || !$accessToken) {
        return ['error' => 'Login failed'];
    }

    // Fetch profile to get role and status
    $profileResult = db_request('GET', 'profiles', null, [
        'id' => 'eq.' . $userId,
        'select' => 'id,role,status,full_name,email',
    ]);

    if (empty($profileResult['data'][0])) {
        return ['error' => 'Profile not found'];
    }

    $profile = $profileResult['data'][0];

    // Check if account is approved
    if ($profile['role'] !== 'admin' && $profile['status'] !== 'approved') {
        return ['error' => 'Account is not approved. Status: ' . $profile['status']];
    }

    // Set session
    $_SESSION['user_id'] = $userId;
    $_SESSION['access_token'] = $accessToken;
    $_SESSION['role'] = $profile['role'];
    $_SESSION['full_name'] = $profile['full_name'];
    $_SESSION['email'] = $profile['email'];

    return ['success' => true, 'user' => $profile];
}

/**
 * Register a new student account.
 */
function register_user(array $data): array {
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $fullName = trim($data['full_name'] ?? '');
    $studentId = trim($data['student_id'] ?? '');
    $department = trim($data['department'] ?? '');
    $year = trim($data['year'] ?? '');
    $section = trim($data['section'] ?? '');

    // Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['error' => 'Please enter a valid email address.'];
    }
    if (strlen($password) < 6) {
        return ['error' => 'Password must be at least 6 characters.'];
    }
    if ($fullName === '') {
        return ['error' => 'Full name is required.'];
    }

    // Check if email already exists
    $existing = db_request('GET', 'profiles', null, [
        'email' => 'eq.' . $email,
        'select' => 'id',
    ]);
    if (!empty($existing['data'])) {
        return ['error' => 'An account with this email already exists.'];
    }

    // Check student ID uniqueness
    if ($studentId !== '') {
        $existingSid = db_request('GET', 'profiles', null, [
            'student_id' => 'eq.' . $studentId,
            'select' => 'id',
        ]);
        if (!empty($existingSid['data'])) {
            return ['error' => 'That Student ID is already registered.'];
        }
    }

    // Create auth user via Supabase Auth API
    $result = supabase_auth('signup', [
        'email' => $email,
        'password' => $password,
        'data' => [
            'full_name' => $fullName,
            'student_id' => $studentId,
            'department' => $department,
            'year' => $year,
            'section' => $section,
        ],
    ]);

    if (isset($result['error'])) {
        return ['error' => $result['error']];
    }

    // The trigger creates the profile and attendance_settings
    // But we need to ensure the profile exists (in case trigger didn't fire)
    $userId = $result['data']['user']['id'] ?? null;
    if ($userId) {
        // Ensure profile exists
        $profileCheck = db_request('GET', 'profiles', null, [
            'id' => 'eq.' . $userId,
            'select' => 'id',
        ]);
        if (empty($profileCheck['data'])) {
            db_request('POST', 'profiles', [
                'id' => $userId,
                'full_name' => $fullName,
                'email' => $email,
                'student_id' => $studentId,
                'department' => $department,
                'year' => $year,
                'section' => $section,
                'role' => 'student',
                'status' => 'pending',
            ]);
        }

        // Ensure attendance_settings exists
        db_request('POST', 'attendance_settings', [
            'user_id' => $userId,
        ]);
    }

    return ['success' => true, 'message' => 'Registration successful. Your account is pending admin approval.'];
}

/**
 * Log out the current user.
 */
function logout_user(): void {
    // Optionally revoke the token via Supabase
    if (isset($_SESSION['access_token'])) {
        supabase_auth('logout', []);
    }
    session_destroy();
}

/**
 * Get the current user's profile.
 */
function get_current_profile(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    $result = db_request('GET', 'profiles', null, [
        'id' => 'eq.' . current_user_id(),
        'select' => '*',
    ]);
    return $result['data'][0] ?? null;
}
