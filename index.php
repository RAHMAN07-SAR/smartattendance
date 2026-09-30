<?php
/**
 * SAMS — Entry Point
 * Loads the SPA shell. All data operations go through api.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

// If not logged in and not on auth page, the JS will handle redirect
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>SAMS — Smart Attendance Management System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <div id="preloader">
    <div class="pre-logo">SAMS</div>
    <div class="pre-sub">Loading your attendance workspace…</div>
    <div class="pre-bar"><span></span></div>
  </div>

  <div id="auth-screen">
    <div class="auth-wrap">
      <div class="card clay auth-card" id="auth-card"></div>
    </div>
  </div>

  <div id="app" class="hidden">
    <div class="sidebar-scrim" id="sidebar-scrim"></div>
    <div class="app-shell">
      <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand brand">
          <div class="logo-icon">S</div>
          <div>
            <div class="brand-name">SAMS</div>
            <div class="brand-sub">Smart Attendance</div>
          </div>
        </div>
        <nav id="nav-list"></nav>
        <div class="sidebar-foot">
          <div class="nav-item" id="nav-logout">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Sign out
          </div>
        </div>
      </aside>
      <div class="main-col">
        <header class="topheader">
          <div class="topheader-left">
            <button class="icon-btn menu-btn" id="menu-btn" aria-label="Open menu">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div style="min-width:0;">
              <div class="page-title" id="page-title">Dashboard</div>
              <div class="page-sub" id="page-sub">Welcome back</div>
            </div>
          </div>
          <div class="topheader-right">
            <button class="icon-btn" id="notif-btn" aria-label="Notifications">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
              <span class="dot" id="notif-dot"></span>
            </button>
            <button class="theme-toggle" id="theme-toggle" aria-label="Toggle dark mode">
              <span class="knob" id="theme-knob"></span>
            </button>
            <div class="profile-chip" id="profile-chip">
              <div class="avatar" id="avatar-init">--</div>
              <div>
                <div class="pname" id="chip-name">—</div>
                <div class="prole" id="chip-role">Student</div>
              </div>
            </div>
          </div>
        </header>
        <main class="content" id="content">
          <section class="view" id="view-dashboard"></section>
          <section class="view" id="view-attendance"></section>
          <section class="view" id="view-timetable"></section>
          <section class="view" id="view-subjects"></section>
          <section class="view" id="view-calculator"></section>
          <section class="view" id="view-leave"></section>
          <section class="view" id="view-reports"></section>
          <section class="view" id="view-notifications"></section>
          <section class="view" id="view-settings"></section>
          <section class="view" id="view-admin"></section>
        </main>
      </div>
    </div>
    <nav class="bottom-nav" id="bottom-nav"></nav>
  </div>

  <div class="modal-overlay" id="modal-overlay">
    <div class="modal" id="modal-inner"></div>
  </div>
  <div id="toast-region"></div>

  <script src="assets/js/app.js"></script>
</body>
</html>
