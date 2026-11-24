<?php
// family/index.php - Family member management page
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Get family members
$stmt = $pdo->prepare("SELECT * FROM family_members WHERE user_id = ? ORDER BY date_added DESC");
$stmt->execute([$userId]);
$familyMembers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAMFIN - Family Members</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>
<body class="family-page">
    <div class="app-wrapper">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="dashboard-content">
                <div class="welcome-section">
                    <h1>Family Members</h1>
                    <p>Manage your family members and their financial information.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Family Members</h3>
                        <a href="<?= BASE_URL ?>/family/add.php" class="btn-primary" style="padding: 8px 16px; font-size: 13px;">Add Member</a>
                    </div>

                    <?php if (count($familyMembers) > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Relationship</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Date Added</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($familyMembers as $member): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($member['full_name']) ?></td>
                                            <td><?= htmlspecialchars($member['relationship']) ?></td>
                                            <td><?= htmlspecialchars($member['email']) ?></td>
                                            <td>
                                                <span style="font-size: 12px; padding: 4px 10px; border-radius: 20px; background: <?= $member['status'] === 'active' ? 'var(--green-light)' : 'var(--red-light)' ?>; color: <?= $member['status'] === 'active' ? 'var(--primary-green)' : '#C53030' ?>; font-weight: 500;">
                                                    <?= ucfirst($member['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= formatDate($member['date_added']) ?></td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/family/edit.php?id=<?= $member['id'] ?>" class="btn-secondary" style="padding: 6px 12px; font-size: 12px;">Edit</a>
                                                <a href="<?= BASE_URL ?>/family/delete.php?id=<?= $member['id'] ?>" class="btn-danger" style="padding: 6px 12px; font-size: 12px;" onclick="return confirm('Delete this family member?')">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No family members added yet.</p>
                            <a href="<?= BASE_URL ?>/family/add.php" class="btn-primary">Add Family Member</a>
                        </div>
                    <?php endif; ?>
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