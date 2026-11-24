<?php
// family/add.php - Add family member
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['_csrf_token'] ?? '';
    if (!validateCsrfToken($submittedToken)) {
        http_response_code(403);
        header('Location: index.php?error=csrf_mismatch');
        exit;
    }

    $fullName = trim($_POST['full_name']);
    $relationship = trim($_POST['relationship']);
    $email = trim($_POST['email']);

    if (empty($fullName)) {
        $error = "Full name is required";
    } else {
        $stmt = $pdo->prepare("INSERT INTO family_members (user_id, full_name, relationship, email, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$userId, $fullName, $relationship, $email]);
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAMFIN - Add Family Member</title>
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
                    <h1>Add Family Member</h1>
                    <p>Add a new family member to your account.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Member Details</h3>
                    </div>
                    <div style="padding: 24px;">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <form action="add.php" method="POST">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" name="full_name" id="full_name" placeholder="Full Name" required>
                            </div>
                            <div class="form-group">
                                <label for="relationship">Relationship</label>
                                <input type="text" name="relationship" id="relationship" placeholder="Relationship (e.g., Spouse, Child, Parent)">
                            </div>
                            <div class="form-group">
                                <label for="email">Email (Optional)</label>
                                <input type="email" name="email" id="email" placeholder="Email">
                            </div>
                            <div style="display: flex; gap: 12px; margin-top: 8px;">
                                <button type="submit" class="btn-primary">Add Member</button>
                                <a href="index.php" class="btn-secondary">Cancel</a>
                            </div>
                        </form>
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