# SAMS — Smart Attendance Management System (Full-Stack Rebuild)

A secure full-stack web application built with **HTML + CSS + JavaScript + PHP + Supabase**.

## Architecture

```
┌─────────────────────────────────────────────────────┐
│  Browser (HTML + CSS + JS)                          │
│  - Single-page app with 10 views                     │
│  - All API calls go through api.php                  │
│  - No Supabase credentials exposed                   │
└──────────────────────┬──────────────────────────────┘
                       │ HTTP (same-origin)
┌──────────────────────▼──────────────────────────────┐
│  PHP Backend (api.php)                              │
│  - Session-based authentication                     │
│  - Server-side authorization (admin/student)        │
│  - Input validation                                 │
│  - Holds Supabase service-role key securely         │
└──────────────────────┬──────────────────────────────┘
                       │ cURL (server-side only)
┌──────────────────────▼──────────────────────────────┐
│  Supabase (PostgreSQL + Auth)                       │
│  - Row Level Security on all tables                 │
│  - Triggers for auto-provisioning                   │
│  - Views for attendance calculations                │
│  - RPC functions for admin operations               │
└─────────────────────────────────────────────────────┘
```

## Project Structure

```
index.php              ← Entry point (loads the SPA shell)
config.php             ← Supabase credentials (server-side only)
db.php                 ← Supabase REST API helper (cURL)
auth.php               ← Authentication & session management
api.php                ← API router (all client requests)
assets/
  css/
    styles.css         ← All styles
  js/
    app.js             ← All frontend logic
supabase/
  schema.sql           ← Database schema (unchanged)
```

## Security Features

1. **Service-role key never exposed** — held only in `config.php` on the server
2. **Session-based auth** — PHP sessions with httponly cookies
3. **Server-side authorization** — every API call checks auth/role
4. **Input validation** — all inputs validated server-side
5. **RLS on all tables** — database-level protection
6. **No client-side Supabase** — client never sees Supabase credentials

## Setup

1. **Database** — Run `supabase/schema.sql` in Supabase SQL Editor (same as before)
2. **Configure** — Update `config.php` with your Supabase service-role key:
   ```php
   const SUPABASE_SERVICE_KEY = 'sb_secret_YOUR_ACTUAL_SERVICE_ROLE_KEY';
   ```
3. **Deploy** — Upload all files to a PHP-enabled web server (Apache/Nginx with PHP 7.4+)
4. **Sign in** — `admin@sams.edu` / `Admin@12345`

## Requirements

- PHP 7.4+ with cURL extension
- Supabase project (same as before)
- Web server (Apache, Nginx, or PHP built-in server for testing)

## Quick Test

```bash
php -S localhost:8000
# Then open http://localhost:8000
```

## API Endpoints

All endpoints are via `api.php?action={action}`:

| Action | Method | Auth | Description |
|---|---|---|---|
| `login` | POST | No | Sign in |
| `register` | POST | No | Create account |
| `logout` | POST | Yes | Sign out |
| `me` | GET | Yes | Get current user |
| `forgot_password` | POST | No | Send reset code |
| `reset_password` | POST | No | Reset password |
| `update_profile` | POST | Yes | Update profile |
| `get_settings` | GET | Yes | Get attendance settings |
| `update_settings` | POST | Yes | Update settings |
| `get_semesters` | GET | Yes | List semesters |
| `start_semester` | POST | Yes | Start new semester |
| `get_subjects` | GET | Yes | List subjects |
| `add_subject` | POST | Yes | Add subject |
| `update_subject` | POST | Yes | Update subject |
| `delete_subject` | POST | Yes | Delete subject |
| `get_timetable` | GET | Yes | Get timetable |
| `add_timetable_slot` | POST | Yes | Add slot |
| `update_timetable_slot` | POST | Yes | Update slot |
| `delete_timetable_slot` | POST | Yes | Delete slot |
| `get_attendance` | GET | Yes | Get attendance |
| `mark_attendance` | POST | Yes | Mark attendance |
| `get_subject_attendance` | GET | Yes | Subject stats |
| `get_semester_attendance` | GET | Yes | Semester stats |
| `get_leave_plans` | GET | Yes | List leave plans |
| `add_leave_plan` | POST | Yes | Add leave plan |
| `update_leave_plan` | POST | Yes | Update leave plan |
| `delete_leave_plan` | POST | Yes | Delete leave plan |
| `get_notifications` | GET | Yes | List notifications |
| `mark_notification_read` | POST | Yes | Mark read |
| `mark_all_notifications_read` | POST | Yes | Mark all read |
| `admin_get_students` | GET | Admin | List all students |
| `admin_set_status` | POST | Admin | Change student status |
| `admin_get_student_detail` | GET | Admin | Student details |
