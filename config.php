<?php
/**
 * SAMS — Configuration
 * Supabase credentials. The service-role key is NEVER exposed to the client.
 */

declare(strict_types=1);

const SUPABASE_URL = 'https://nwmlctyythlljrcnintn.supabase.co';
const SUPABASE_SERVICE_KEY = 'sb_secret_YOUR_SERVICE_ROLE_KEY_HERE';
const SUPABASE_ANON_KEY = 'sb_publishable_5lTGafS3JNwSgJjcbs64kA_z7gaeOSX';

const SESSION_NAME = 'sams_session';
const SESSION_LIFETIME = 86400; // 24 hours

const APP_NAME = 'SAMS';
const APP_VERSION = '2.0';
