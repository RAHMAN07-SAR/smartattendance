<?php
/**
 * SAMS — API Router
 * Single entry point for all client requests.
 * Every request is authenticated and authorized server-side.
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    switch ($action) {
        // ===== AUTH =====
        case 'login':
            require_method('POST', $method);
            $result = login_user($input['email'] ?? '', $input['password'] ?? '');
            json_response($result);
            break;

        case 'register':
            require_method('POST', $method);
            $result = register_user($input);
            json_response($result);
            break;

        case 'logout':
            require_auth();
            logout_user();
            json_response(['success' => true]);
            break;

        case 'me':
            require_auth();
            $profile = get_current_profile();
            json_response(['user' => $profile]);
            break;

        case 'forgot_password':
            require_method('POST', $method);
            $email = trim($input['email'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                json_response(['error' => 'Invalid email address']);
            }
            $result = supabase_auth('recover', ['email' => $email]);
            json_response(['success' => true, 'message' => 'If the email exists, a recovery code has been sent.']);
            break;

        case 'reset_password':
            require_method('POST', $method);
            $token = $input['token'] ?? '';
            $password = $input['password'] ?? '';
            if (strlen($password) < 6) {
                json_response(['error' => 'Password must be at least 6 characters.']);
            }
            $result = supabase_auth('verify', [
                'type' => 'recovery',
                'token' => $token,
                'password' => $password,
            ]);
            if (isset($result['error'])) {
                json_response(['error' => $result['error']]);
            }
            json_response(['success' => true, 'message' => 'Password reset successful. Please sign in.']);
            break;

        // ===== PROFILE =====
        case 'update_profile':
            require_auth();
            $allowed = ['full_name', 'student_id', 'department', 'year', 'section'];
            $updates = array_intersect_key($input, array_flip($allowed));
            if (empty($updates)) {
                json_response(['error' => 'No valid fields to update']);
            }
            $updates['updated_at'] = date('c');
            $result = db_request('PATCH', 'profiles', $updates, [
                'id' => 'eq.' . current_user_id(),
            ]);
            json_response(['success' => true, 'data' => $result['data'] ?? null]);
            break;

        // ===== SETTINGS =====
        case 'get_settings':
            require_auth();
            $result = db_request('GET', 'attendance_settings', null, [
                'user_id' => 'eq.' . current_user_id(),
            ]);
            json_response(['data' => $result['data'][0] ?? null]);
            break;

        case 'update_settings':
            require_auth();
            $allowed = ['target_percent', 'warning_percent', 'critical_percent', 'periods_per_day', 'semester', 'academic_year', 'college_start_time', 'college_end_time', 'working_days'];
            $updates = array_intersect_key($input, array_flip($allowed));
            if (empty($updates)) {
                json_response(['error' => 'No valid fields to update']);
            }
            $updates['updated_at'] = date('c');
            $result = db_request('PATCH', 'attendance_settings', $updates, [
                'user_id' => 'eq.' . current_user_id(),
            ]);
            json_response(['success' => true, 'data' => $result['data'] ?? null]);
            break;

        // ===== SEMESTERS =====
        case 'get_semesters':
            require_auth();
            $result = db_request('GET', 'semesters', null, [
                'user_id' => 'eq.' . current_user_id(),
                'order' => 'started_at.desc',
            ]);
            json_response(['data' => $result['data'] ?? []]);
            break;

        case 'start_semester':
            require_auth();
            $name = trim($input['name'] ?? '');
            $year = trim($input['academic_year'] ?? '');
            if ($name === '') {
                json_response(['error' => 'Semester name is required']);
            }
            $result = db_rpc('start_new_semester', [
                'p_name' => $name,
                'p_academic_year' => $year,
            ]);
            if (isset($result['error'])) {
                json_response(['error' => $result['error']]);
            }
            json_response(['success' => true, 'data' => $result['data'] ?? null]);
            break;

        // ===== SUBJECTS =====
        case 'get_subjects':
            require_auth();
            $semesterId = $input['semester_id'] ?? null;
            $query = ['user_id' => 'eq.' . current_user_id(), 'order' => 'created_at.asc'];
            if ($semesterId) {
                $query['semester_id'] = 'eq.' . $semesterId;
            }
            $result = db_request('GET', 'subjects', null, $query);
            json_response(['data' => $result['data'] ?? []]);
            break;

        case 'add_subject':
            require_auth();
            $name = trim($input['name'] ?? '');
            $code = trim($input['code'] ?? '');
            $color = $input['color'] ?? '#E53935';
            $semesterId = $input['semester_id'] ?? null;
            if ($name === '' || $code === '') {
                json_response(['error' => 'Subject name and code are required']);
            }
            $body = [
                'user_id' => current_user_id(),
                'name' => $name,
                'code' => $code,
                'color' => $color,
            ];
            if ($semesterId) {
                $body['semester_id'] = $semesterId;
            }
            $result = db_request('POST', 'subjects', $body);
            json_response(['success' => true, 'data' => $result['data'] ?? null]);
            break;

        case 'update_subject':
            require_auth();
            $id = $input['id'] ?? '';
            if ($id === '') json_response(['error' => 'Subject ID required']);
            $allowed = ['name', 'code', 'color'];
            $updates = array_intersect_key($input, array_flip($allowed));
            if (empty($updates)) json_response(['error' => 'No valid fields']);
            $result = db_request('PATCH', 'subjects', $updates, ['id' => 'eq.' . $id]);
            json_response(['success' => true, 'data' => $result['data'] ?? null]);
            break;

        case 'delete_subject':
            require_auth();
            $id = $input['id'] ?? '';
            if ($id === '') json_response(['error' => 'Subject ID required']);
            db_request('DELETE', 'subjects', null, ['id' => 'eq.' . $id]);
            json_response(['success' => true]);
            break;

        // ===== TIMETABLE =====
        case 'get_timetable':
            require_auth();
            $semesterId = $input['semester_id'] ?? null;
            $query = ['user_id' => 'eq.' . current_user_id(), 'order' => 'day_of_week.asc,period_number.asc'];
            if ($semesterId) {
                $query['semester_id'] = 'eq.' . $semesterId;
            }
            $result = db_request('GET', 'timetable', null, $query);
            json_response(['data' => $result['data'] ?? []]);
            break;

        case 'add_timetable_slot':
            require_auth();
            $day = $input['day_of_week'] ?? '';
            $period = (int)($input['period_number'] ?? 0);
            $subjectId = $input['subject_id'] ?? null;
            $startTime = $input['period_start_time'] ?? null;
            $endTime = $input['period_end_time'] ?? null;
            $time = $input['period_time'] ?? '';
            $semesterId = $input['semester_id'] ?? null;
            $validDays = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
            if (!in_array($day, $validDays)) json_response(['error' => 'Invalid day']);
            if ($period < 1) json_response(['error' => 'Invalid period']);
            $body = [
                'user_id' => current_user_id(),
                'day_of_week' => $day,
                'period_number' => $period,
            ];
            if ($subjectId) $body['subject_id'] = $subjectId;
            if ($startTime) $body['period_start_time'] = $startTime;
            if ($endTime) $body['period_end_time'] = $endTime;
            if ($time) $body['period_time'] = $time;
            if ($semesterId) $body['semester_id'] = $semesterId;
            $result = db_request('POST', 'timetable', $body);
            json_response(['success' => true, 'data' => $result['data'] ?? null]);
            break;

        case 'update_timetable_slot':
            require_auth();
            $id = $input['id'] ?? '';
            if ($id === '') json_response(['error' => 'Slot ID required']);
            $allowed = ['subject_id', 'period_time', 'period_start_time', 'period_end_time'];
            $updates = array_intersect_key($input, array_flip($allowed));
            if (empty($updates)) json_response(['error' => 'No valid fields']);
            $result = db_request('PATCH', 'timetable', $updates, ['id' => 'eq.' . $id]);
            json_response(['success' => true, 'data' => $result['data'] ?? null]);
            break;

        case 'delete_timetable_slot':
            require_auth();
            $id = $input['id'] ?? '';
            if ($id === '') json_response(['error' => 'Slot ID required']);
            db_request('DELETE', 'timetable', null, ['id' => 'eq.' . $id]);
            json_response(['success' => true]);
            break;

        // ===== ATTENDANCE =====
        case 'get_attendance':
            require_auth();
            $semesterId = $input['semester_id'] ?? null;
            $subjectId = $input['subject_id'] ?? null;
            $date = $input['date'] ?? null;
            $query = ['user_id' => 'eq.' . current_user_id(), 'order' => 'att_date.desc'];
            if ($semesterId) $query['semester_id'] = 'eq.' . $semesterId;
            if ($subjectId) $query['subject_id'] = 'eq.' . $subjectId;
            if ($date) $query['att_date'] = 'eq.' . $date;
            $result = db_request('GET', 'attendance', null, $query);
            json_response(['data' => $result['data'] ?? []]);
            break;

        case 'mark_attendance':
            require_auth();
            $records = $input['records'] ?? [];
            if (!is_array($records) || empty($records)) {
                json_response(['error' => 'No attendance records provided']);
            }
            $results = [];
            foreach ($records as $rec) {
                $subjectId = $rec['subject_id'] ?? '';
                $date = $rec['att_date'] ?? '';
                $period = (int)($rec['period_number'] ?? 0);
                $status = $rec['status'] ?? '';
                $semesterId = $rec['semester_id'] ?? null;
                if (!$subjectId || !$date || $period < 1 || !in_array($status, ['present','absent','not-marked'])) {
                    $results[] = ['error' => 'Invalid record'];
                    continue;
                }
                $body = [
                    'user_id' => current_user_id(),
                    'subject_id' => $subjectId,
                    'att_date' => $date,
                    'period_number' => $period,
                    'status' => $status,
                ];
                if ($semesterId) $body['semester_id'] = $semesterId;
                // Upsert: delete existing then insert
                db_request('DELETE', 'attendance', null, [
                    'user_id' => 'eq.' . current_user_id(),
                    'subject_id' => 'eq.' . $subjectId,
                    'att_date' => 'eq.' . $date,
                    'period_number' => 'eq.' . $period,
                ]);
                $res = db_request('POST', 'attendance', $body);
                $results[] = $res['data'] ?? ['error' => $res['error'] ?? 'Unknown'];
            }
            json_response(['success' => true, 'results' => $results]);
            break;

        // ===== EXTRA ATTENDANCE =====
        case 'get_extra_attendance':
            require_auth();
            $semesterId = $input['semester_id'] ?? null;
            $subjectId = $input['subject_id'] ?? null;
            $query = ['user_id' => 'eq.' . current_user_id(), 'order' => 'extra_date.desc'];
            if ($semesterId) $query['semester_id'] = 'eq.' . $semesterId;
            if ($subjectId) $query['subject_id'] = 'eq.' . $subjectId;
            $result = db_request('GET', 'extra_attendance', null, $query);
            json_response(['data' => $result['data'] ?? []]);
            break;

        case 'add_extra_attendance':
            require_auth();
            $subjectId = $input['subject_id'] ?? '';
            $date = $input['extra_date'] ?? '';
            $count = (int)($input['count'] ?? 1);
            $reason = $input['reason'] ?? '';
            $semesterId = $input['semester_id'] ?? null;
            if (!$subjectId || !$date || $count < 1) {
                json_response(['error' => 'Subject, date, and count are required']);
            }
            $body = [
                'user_id' => current_user_id(),
                'subject_id' => $subjectId,
                'extra_date' => $date,
                'count' => $count,
                'reason' => $reason,
            ];
            if ($semesterId) $body['semester_id'] = $semesterId;
            $result = db_request('POST', 'extra_attendance', $body);
            json_response(['success' => true, 'data' => $result['data'] ?? null]);
            break;

        case 'delete_extra_attendance':
            require_auth();
            $id = $input['id'] ?? '';
            if ($id === '') json_response(['error' => 'Extra attendance ID required']);
            db_request('DELETE', 'extra_attendance', null, ['id' => 'eq.' . $id]);
            json_response(['success' => true]);
            break;

        // ===== ATTENDANCE STATS (via views) =====
        case 'get_subject_attendance':
            require_auth();
            $semesterId = $input['semester_id'] ?? null;
            $query = ['user_id' => 'eq.' . current_user_id()];
            if ($semesterId) $query['semester_id'] = 'eq.' . $semesterId;
            $result = db_request('GET', 'v_subject_attendance', null, $query);
            json_response(['data' => $result['data'] ?? []]);
            break;

        case 'get_semester_attendance':
            require_auth();
            $semesterId = $input['semester_id'] ?? null;
            $query = ['user_id' => 'eq.' . current_user_id()];
            if ($semesterId) $query['semester_id'] = 'eq.' . $semesterId;
            $result = db_request('GET', 'v_semester_attendance', null, $query);
            json_response(['data' => $result['data'] ?? []]);
            break;

        // ===== LEAVE PLANS =====
        case 'get_leave_plans':
            require_auth();
            $semesterId = $input['semester_id'] ?? null;
            $query = ['user_id' => 'eq.' . current_user_id(), 'order' => 'plan_date.desc'];
            if ($semesterId) $query['semester_id'] = 'eq.' . $semesterId;
            $result = db_request('GET', 'leave_plans', null, $query);
            json_response(['data' => $result['data'] ?? []]);
            break;

        case 'add_leave_plan':
            require_auth();
            $date = $input['plan_date'] ?? '';
            $periods = $input['periods'] ?? [];
            $subjectIds = $input['subject_ids'] ?? [];
            $semesterId = $input['semester_id'] ?? null;
            if ($date === '') json_response(['error' => 'Date required']);
            $body = [
                'user_id' => current_user_id(),
                'plan_date' => $date,
                'periods' => $periods,
                'subject_ids' => $subjectIds,
                'status' => 'planned',
            ];
            if ($semesterId) $body['semester_id'] = $semesterId;
            $result = db_request('POST', 'leave_plans', $body);
            json_response(['success' => true, 'data' => $result['data'] ?? null]);
            break;

        case 'update_leave_plan':
            require_auth();
            $id = $input['id'] ?? '';
            if ($id === '') json_response(['error' => 'Plan ID required']);
            $allowed = ['status', 'periods', 'subject_ids'];
            $updates = array_intersect_key($input, array_flip($allowed));
            if (empty($updates)) json_response(['error' => 'No valid fields']);
            $result = db_request('PATCH', 'leave_plans', $updates, ['id' => 'eq.' . $id]);
            json_response(['success' => true, 'data' => $result['data'] ?? null]);
            break;

        case 'delete_leave_plan':
            require_auth();
            $id = $input['id'] ?? '';
            if ($id === '') json_response(['error' => 'Plan ID required']);
            db_request('DELETE', 'leave_plans', null, ['id' => 'eq.' . $id]);
            json_response(['success' => true]);
            break;

        // ===== NOTIFICATIONS =====
        case 'get_notifications':
            require_auth();
            $result = db_request('GET', 'notifications', null, [
                'user_id' => 'eq.' . current_user_id(),
                'order' => 'created_at.desc',
                'limit' => '50',
            ]);
            json_response(['data' => $result['data'] ?? []]);
            break;

        case 'mark_notification_read':
            require_auth();
            $id = $input['id'] ?? '';
            if ($id === '') json_response(['error' => 'Notification ID required']);
            db_request('PATCH', 'notifications', ['is_read' => true], ['id' => 'eq.' . $id]);
            json_response(['success' => true]);
            break;

        case 'mark_all_notifications_read':
            require_auth();
            db_request('PATCH', 'notifications', ['is_read' => true], [
                'user_id' => 'eq.' . current_user_id(),
            ]);
            json_response(['success' => true]);
            break;

        // ===== ADMIN =====
        case 'admin_get_students':
            require_admin();
            $result = db_rpc('admin_student_overview', []);
            if (isset($result['error'])) {
                json_response(['error' => $result['error']]);
            }
            json_response(['data' => $result['data'] ?? []]);
            break;

        case 'admin_set_status':
            require_admin();
            $studentId = $input['student_id'] ?? '';
            $status = $input['status'] ?? '';
            $note = $input['note'] ?? null;
            if ($studentId === '' || !in_array($status, ['pending','approved','rejected','deactivated','cancelled'])) {
                json_response(['error' => 'Invalid parameters']);
            }
            $result = db_rpc('set_student_status', [
                'p_student' => $studentId,
                'p_status' => $status,
                'p_note' => $note,
            ]);
            if (isset($result['error'])) {
                json_response(['error' => $result['error']]);
            }
            json_response(['success' => true]);
            break;

        case 'admin_get_student_detail':
            require_admin();
            $studentId = $input['student_id'] ?? '';
            if ($studentId === '') json_response(['error' => 'Student ID required']);
            $profile = db_request('GET', 'profiles', null, ['id' => 'eq.' . $studentId]);
            $semesters = db_request('GET', 'semesters', null, [
                'user_id' => 'eq.' . $studentId,
                'order' => 'started_at.desc',
            ]);
            $subjects = db_request('GET', 'subjects', null, [
                'user_id' => 'eq.' . $studentId,
                'order' => 'created_at.asc',
            ]);
            $attendance = db_request('GET', 'v_subject_attendance', null, [
                'user_id' => 'eq.' . $studentId,
            ]);
            $extraAttendance = db_request('GET', 'extra_attendance', null, [
                'user_id' => 'eq.' . $studentId,
                'order' => 'extra_date.desc',
            ]);
            json_response([
                'profile' => $profile['data'][0] ?? null,
                'semesters' => $semesters['data'] ?? [],
                'subjects' => $subjects['data'] ?? [],
                'attendance' => $attendance['data'] ?? [],
                'extra_attendance' => $extraAttendance['data'] ?? [],
            ]);
            break;

        default:
            http_response_code(404);
            json_response(['error' => 'Unknown action: ' . $action]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    json_response(['error' => 'Server error: ' . $e->getMessage()]);
}

// ===== Helpers =====

function require_method(string $expected, string $actual): void {
    if ($actual !== $expected) {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed. Expected ' . $expected]);
        exit;
    }
}

function json_response(array $data): void {
    echo json_encode($data);
    exit;
}
