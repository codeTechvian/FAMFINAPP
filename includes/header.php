<!-- Top Header -->
<header class="top-header">
    <div class="header-left">
        <button class="hamburger" id="hamburger" aria-label="Toggle navigation">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <img src="<?= BASE_URL ?>/assets/images/famfin-logo.svg" alt="FAMFIN" class="header-logo">
        <span class="header-label">Family Finance</span>
    </div>
    <div class="header-right">
        <span class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></span>
        <div class="user-profile">
            <img src="<?= BASE_URL ?>/assets/images/user-avatar.svg" alt="Profile" class="user-avatar">
        </div>
    </div>
</header>