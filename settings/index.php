<?php
// settings/index.php - Settings page
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../settings.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAMFIN - Settings</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>
<body>
    <div class="app-wrapper">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="dashboard-content">
                <div class="welcome-section">
                    <h1>Settings</h1>
                    <p>Manage your account settings and preferences.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Account Information</h3>
                    </div>
                    <div style="padding: 24px;">
                        <div style="margin-bottom: 32px;">
                            <h4 style="margin-bottom: 16px;">Current Profile</h4>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                                <div style="background: var(--light-gray); padding: 16px; border-radius: 8px;">
                                    <div style="font-size: 12px; color: var(--secondary-text); margin-bottom: 4px;">Full Name</div>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Not set') ?></div>
                                </div>
                                <div style="background: var(--light-gray); padding: 16px; border-radius: 8px;">
                                    <div style="font-size: 12px; color: var(--secondary-text); margin-bottom: 4px;">Username</div>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($_SESSION['user_username'] ?? 'Not set') ?></div>
                                </div>
                                <div style="background: var(--light-gray); padding: 16px; border-radius: 8px;">
                                    <div style="font-size: 12px; color: var(--secondary-text); margin-bottom: 4px;">Email</div>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($_SESSION['user_email'] ?? 'Not set') ?></div>
                                </div>
                            </div>
                        </div>

                        <div style="margin-bottom: 32px; padding-bottom: 32px; border-bottom: 1px solid var(--border);">
                            <h4 style="margin-bottom: 16px;">Edit Full Name</h4>
                            <form method="POST" style="background: var(--card-bg); padding: 20px; border-radius: 12px; border: 1px solid var(--border);">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="action" value="update_name">
                                <div class="form-group">
                                    <label for="full_name">New Full Name</label>
                                    <input type="text" name="full_name" id="full_name" placeholder="Enter new full name" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>" required>
                                </div>
                                <button type="submit" class="btn-primary">Update Name</button>
                            </form>
                        </div>

                        <div style="margin-bottom: 32px; padding-bottom: 32px; border-bottom: 1px solid var(--border);">
                            <h4 style="margin-bottom: 16px;">Change Password</h4>
                            <form method="POST" style="background: var(--card-bg); padding: 20px; border-radius: 12px; border: 1px solid var(--border);">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="action" value="change_password">
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <input type="password" name="current_password" id="current_password" placeholder="Current Password" required>
                                </div>
                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="password" name="new_password" id="new_password" placeholder="New Password" required minlength="8">
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm New Password" required minlength="8">
                                </div>
                                <button type="submit" class="btn-primary">Change Password</button>
                            </form>
                        </div>

                        <div>
                            <h4 style="margin-bottom: 16px;">Danger Zone</h4>
                            <form method="POST" style="background: var(--red-light); padding: 20px; border-radius: 12px; border: 1px solid #F1A1A1;">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="action" value="logout">
                                <p style="color: #C53030; margin-bottom: 16px;">Sign out of your account. You'll need to log in again to access your dashboard.</p>
                                <button type="submit" class="btn-danger">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php include __DIR__ . '/../includes/footer.php'; ?>
        </main>
    </div>

    <script>
        const hamburger = document.getElementById('hamburger');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('open');
            hamburger.classList.toggle('active');
        }

        hamburger.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);

        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    toggleSidebar();
                }
            });
        });
    </script>
</body>
</html>