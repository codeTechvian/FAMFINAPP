<!-- Sidebar Navigation -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="<?= BASE_URL ?>/assets/images/famfin-logo.svg" alt="FAMFIN" class="sidebar-logo">
        <h4>FAMFIN</h4>
    </div>
    <nav>
        <ul>
            <li>
                <a href="<?= BASE_URL ?>/dashboard/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false ? 'active' : ''; ?>">
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/income/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/income/') !== false ? 'active' : ''; ?>">
                    <span>Income</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/expenses/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/expenses/') !== false ? 'active' : ''; ?>">
                    <span>Expenses</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/budget/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/budget/') !== false ? 'active' : ''; ?>">
                    <span>Budget</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/goals/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/goals/') !== false ? 'active' : ''; ?>">
                    <span>Savings Goals</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/family/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/family/') !== false ? 'active' : ''; ?>">
                    <span>Family Member</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/reports/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/reports/') !== false ? 'active' : ''; ?>">
                    <span>Reports</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/settings/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/settings/') !== false ? 'active' : ''; ?>">
                    <span>Settings</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>

<!-- Mobile Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>