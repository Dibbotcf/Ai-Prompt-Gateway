<?php
/**
 * AI Prompt Security Gateway — Header / Layout Top
 */
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AI Prompt Security Gateway — Preventing LLM Prompt Injection Attacks">
    <title>AI Prompt Security Gateway<?php echo isset($pageTitle) ? " — $pageTitle" : ''; ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css?v=3.0" rel="stylesheet">
</head>
<body>
    <!-- Sidebar Navigation -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <div class="brand-icon">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <div class="brand-text">
                    <span class="brand-name">PromptGuard</span>
                    <span class="brand-version">v1.0.0</span>
                </div>
            </div>
        </div>


        <ul class="sidebar-nav">
            <li class="nav-section-title">MAIN</li>
            <li class="nav-item <?php echo $currentPage === 'index' ? 'active' : ''; ?>">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item <?php echo $currentPage === 'testbench' ? 'active' : ''; ?>">
                <a href="testbench.php" class="nav-link">
                    <i class="fas fa-flask"></i>
                    <span>Test Bench</span>
                </a>
            </li>
            
            <li class="nav-section-title">MANAGEMENT</li>
            <li class="nav-item <?php echo $currentPage === 'rules' ? 'active' : ''; ?>">
                <a href="rules.php" class="nav-link">
                    <i class="fas fa-gavel"></i>
                    <span>Rules Engine</span>
                </a>
            </li>
            <li class="nav-item <?php echo $currentPage === 'logs' ? 'active' : ''; ?>">
                <a href="logs.php" class="nav-link">
                    <i class="fas fa-scroll"></i>
                    <span>Logs & Analytics</span>
                </a>
            </li>
            <li class="nav-item <?php echo $currentPage === 'activities' ? 'active' : ''; ?>">
                <a href="activities.php" class="nav-link">
                    <i class="fas fa-folder-open"></i>
                    <span>Activities</span>
                </a>
            </li>

            <li class="nav-section-title">CONFIGURATION</li>
            <li class="nav-item <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>

            <li class="nav-section-title">DOCUMENTATION</li>
            <li class="nav-item <?php echo $currentPage === 'architecture' ? 'active' : ''; ?>">
                <a href="architecture.php" class="nav-link">
                    <i class="fas fa-sitemap"></i>
                    <span>Architecture</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <div class="system-status">
                <div class="status-dot online"></div>
                <span>System Online</span>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content" id="main-content">
        <!-- Top Bar -->
        <header class="top-bar">
            <div class="top-bar-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
            </div>
            <div class="top-bar-right">
                <div class="top-bar-stat">
                    <i class="fas fa-shield-check"></i>
                    <span id="topbar-total-scans">—</span> Scans
                </div>
                <div class="top-bar-stat threat">
                    <i class="fas fa-ban"></i>
                    <span id="topbar-blocked">—</span> Blocked
                </div>
                <div class="top-bar-stat safe">
                    <i class="fas fa-check-circle"></i>
                    <span id="topbar-safe">—</span> Safe
                </div>
            </div>
        </header>

        <!-- Page Content Container -->
        <div class="content-wrapper">
