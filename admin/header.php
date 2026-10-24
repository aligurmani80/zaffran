<?php
/**
 * Zaffran Admin Panel — Shared Header & Sidebar
 */
if (!defined('ZAFFRAN_APP')) {
    define('ZAFFRAN_APP', true);
}
require_once __DIR__ . '/auth.php';

$currentAdmin = requireAdminAuth();
$pendingOrdersCount = getPendingOrdersCount();
$currentPage = basename($_SERVER['PHP_SELF']);
$pageTitle = $pageTitle ?? 'Zaffran Admin Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?> — Zaffran Admin</title>
  <link rel="stylesheet" href="admin.css?v=<?php echo (string)(@filemtime(__DIR__ . '/admin.css') ?: '1'); ?>">
  <link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
</head>
<body class="admin-body">

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="admin-shell">
  <!-- LEFT SIDEBAR -->
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
      <div class="brand-emblem">Z</div>
      <div class="brand-meta">
        <h2>ZAFFRAN</h2>
        <span>Botanical Admin</span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <span class="nav-section-title">Core Operations</span>
      
      <a href="index.php" class="nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        Overview
      </a>

      <a href="orders.php" class="nav-link <?php echo in_array($currentPage, ['orders.php', 'order-details.php']) ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
        Orders
        <?php if ($pendingOrdersCount > 0): ?>
          <span class="nav-badge"><?php echo $pendingOrdersCount; ?></span>
        <?php endif; ?>
      </a>

      <a href="reports.php" class="nav-link <?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line><path d="M3 20h18"></path></svg>
        Reports
      </a>

      <a href="products.php" class="nav-link <?php echo in_array($currentPage, ['products.php', 'product-create.php', 'product-edit.php']) ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
        Products
      </a>

      <a href="categories.php" class="nav-link <?php echo $currentPage === 'categories.php' ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
        Categories
      </a>

      <a href="customers.php" class="nav-link <?php echo in_array($currentPage, ['customers.php', 'customer-details.php']) ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        Customers
      </a>

      <a href="reviews.php" class="nav-link <?php echo $currentPage === 'reviews.php' ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
        Customer Reviews / WhatsApp Proof
      </a>

      <span class="nav-section-title">Storefront Content</span>

      <a href="announcements.php" class="nav-link <?php echo $currentPage === 'announcements.php' ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
        Announcements
      </a>

      <a href="hero-slider.php" class="nav-link <?php echo $currentPage === 'hero-slider.php' ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
        Hero Slider
      </a>

      <a href="perfume-supply.php" class="nav-link <?php echo $currentPage === 'perfume-supply.php' ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"></path><line x1="16" y1="8" x2="2" y2="22"></line><line x1="17.5" y1="15" x2="9" y2="15"></line></svg>
        Perfume Oil Supply
      </a>

      <span class="nav-section-title">System & Controls</span>

      <a href="settings.php" class="nav-link <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
        Website Settings
      </a>

      <?php if (isSuperAdmin()): ?>
        <a href="admins.php" class="nav-link <?php echo $currentPage === 'admins.php' ? 'active' : ''; ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
          Admin Accounts
        </a>
      <?php endif; ?>

      <a href="audit-logs.php" class="nav-link <?php echo $currentPage === 'audit-logs.php' ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
        Audit Logs
      </a>

      <a href="../index.php" target="_blank" class="nav-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
        View Website
      </a>
    </nav>

    <div class="sidebar-footer">
      <div class="admin-user-pill">
        <div class="admin-avatar"><?php echo strtoupper(substr($currentAdmin['username'], 0, 1)); ?></div>
        <div class="admin-info-text">
          <div class="admin-info-name"><?php echo htmlspecialchars($currentAdmin['username']); ?></div>
          <div class="admin-info-role"><?php echo htmlspecialchars(str_replace('_', ' ', $currentAdmin['role'])); ?></div>
        </div>
      </div>
      <a href="logout.php" class="btn btn-secondary btn-sm" style="width: 100%; border-color: rgba(255,255,255,0.15); color: #FFF; background: transparent;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        Log Out
      </a>
    </div>
  </aside>

  <!-- MAIN WRAPPER -->
  <div class="admin-main">
    <header class="admin-topbar">
      <div class="topbar-left">
        <button type="button" class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Toggle navigation">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>
        <h1 class="page-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
      </div>

      <div class="topbar-right">
        <?php if ($pendingOrdersCount > 0): ?>
          <a href="orders.php?status=pending" class="topbar-btn" style="background: var(--admin-warning-light); color: var(--admin-warning); border-color: #FCD34D;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            <span><?php echo $pendingOrdersCount; ?><span class="topbar-btn-label"> Pending Orders</span></span>
          </a>
        <?php endif; ?>
        <a href="../index.php" target="_blank" class="topbar-btn">
          <span class="topbar-btn-label">Visit Shop</span> &rarr;
        </a>
      </div>
    </header>

    <main class="admin-content">
