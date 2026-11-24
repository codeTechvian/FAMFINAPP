<?php
// dashboard/index.php - Dashboard page
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/financial_intelligence.php';

// Set active month (default to current)
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$monthName = getMonthName($month);

// Calculate financial summaries
// Monthly Income
$incomeStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM income WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
$incomeStmt->execute([$userId, $month, $year]);
$incomeData = $incomeStmt->fetch();
$monthlyIncome = floatval($incomeData['total']);

// Monthly Expenses
$expenseStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
$expenseStmt->execute([$userId, $month, $year]);
$expenseData = $expenseStmt->fetch();
$monthlyExpenses = floatval($expenseData['total']);

// Money Left
$moneyLeft = $monthlyIncome - $monthlyExpenses;

// Savings Goals
$goalsStmt = $pdo->prepare("SELECT id, goal_name, target_amount, current_amount, status FROM savings_goals WHERE user_id = ? AND status = 'active'");
$goalsStmt->execute([$userId]);
$goals = $goalsStmt->fetchAll();

// Total saved across all goals
$totalSaved = 0;
$totalGoalAmount = 0;
foreach ($goals as $goal) {
    $totalSaved += floatval($goal['current_amount']);
    $totalGoalAmount += floatval($goal['target_amount']);
}

// Recent Expenses
$recentStmt = $pdo->prepare("SELECT id, category, description, amount, date FROM expenses WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ? ORDER BY date DESC LIMIT 5");
$recentStmt->execute([$userId, $month, $year]);
$recentExpenses = $recentStmt->fetchAll();

// Expenses by Category
$categoryStmt = $pdo->prepare("SELECT category, SUM(amount) as total FROM expenses WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ? GROUP BY category");
$categoryStmt->execute([$userId, $month, $year]);
$categories = $categoryStmt->fetchAll();

// Financial Intelligence Algorithms
$spendingPattern = getSpendingPatternAnalysis($pdo, $userId, $month, $year);
$budgetOptimization = getBudgetOptimization($pdo, $userId, $month, $year);
$expenseReduction = getExpenseReductionRecommendations($pdo, $userId, $month, $year);
$anomalyDetection = getAnomalyDetection($pdo, $userId, $month, $year);
$savingsOptimization = getSavingsOptimization($pdo, $userId, $month, $year);
$healthScore = getFinancialHealthScore($pdo, $userId, $month, $year);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAMFIN - Dashboard</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Financial Intelligence Section */
        .financial-intelligence-section {
            margin-top: 32px;
            padding: 0 24px;
        }

        .fi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .fi-header h2 {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-text);
            margin: 0;
        }

        .fi-header p {
            color: var(--secondary-text);
            margin: 0;
            font-size: 14px;
        }

        .fi-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        .fi-card {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .fi-card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
        }

        .fi-card-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary-text);
            margin: 0;
        }

        .fi-card-body {
            padding: 24px;
        }

        /* Overview Section */
        .fi-overview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }

        .fi-metric {
            background: var(--light-green);
            border-radius: 12px;
            padding: 16px;
        }

        .fi-metric-label {
            font-size: 12px;
            color: var(--secondary-text);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .fi-metric-value {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-text);
        }

        .fi-score-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 8px;
            color: #FFFFFF;
            font-weight: 700;
            font-size: 14px;
        }

        /* Spending Chart */
        .fi-chart-container {
            position: relative;
            height: 280px;
            width: 100%;
            margin-bottom: 20px;
        }

        .fi-spending-insights {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
        }

        .fi-insight-item {
            background: var(--light-gray);
            border-radius: 8px;
            padding: 12px;
            font-size: 13px;
        }

        .fi-insight-item strong {
            color: var(--primary-text);
        }

        /* Category Breakdown */
        .fi-category-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .fi-category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
        }

        .fi-category-item:last-child {
            border-bottom: none;
        }

        .fi-cat-name {
            font-size: 14px;
            color: var(--primary-text);
        }

        .fi-cat-amount {
            font-size: 14px;
            font-weight: 600;
            color: var(--primary-text);
        }

        .fi-cat-pct {
            font-size: 12px;
            color: var(--secondary-text);
            min-width: 35px;
            text-align: right;
        }

        .fi-cat-bar {
            width: 100px;
            height: 6px;
            background: var(--border);
            border-radius: 3px;
            overflow: hidden;
        }

        .fi-cat-bar-fill {
            height: 100%;
            border-radius: 3px;
        }

        /* Budget Optimization */
        .fi-budget-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .fi-budget-metric {
            background: var(--light-green);
            border-radius: 10px;
            padding: 16px;
        }

        .fi-budget-label {
            font-size: 12px;
            color: var(--secondary-text);
            margin-bottom: 6px;
        }

        .fi-budget-value {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary-text);
        }

        .fi-budget-adjustments {
            margin-top: 20px;
        }

        .fi-adjustment-list {
            list-style: none;
            padding: 0;
            margin: 10px 0 0 0;
        }

        .fi-adjustment-list li {
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }

        .fi-adjustment-list li:last-child {
            border-bottom: none;
        }

        /* Recommendations */
        .fi-recommendation-item {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            background: var(--card-bg);
        }

        .fi-rec-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .fi-rec-category {
            font-weight: 600;
            color: var(--primary-text);
            font-size: 15px;
        }

        .fi-rec-severity {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .fi-rec-severity.high {
            background: #FDEDEC;
            color: #C53030;
        }

        .fi-rec-severity.medium {
            background: #FEF9E7;
            color: #B56E2E;
        }

        .fi-rec-severity.low {
            background: #E8F5F0;
            color: #2D805F;
        }

        .fi-rec-message {
            font-size: 14px;
            color: var(--primary-text);
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .fi-rec-details {
            display: flex;
            gap: 16px;
            font-size: 13px;
            color: var(--secondary-text);
        }

        .fi-total-savings {
            margin-top: 20px;
            padding: 16px;
            background: #E8F5F0;
            border-radius: 12px;
            font-weight: 600;
            color: #2D805F;
        }

        /* Anomaly Detection */
        .fi-anomaly-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px solid var(--border);
        }

        .fi-anomaly-item:last-child {
            border-bottom: none;
        }

        .fi-anomaly-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .fi-anomaly-category {
            font-weight: 600;
            color: var(--primary-text);
        }

        .fi-anomaly-date {
            font-size: 12px;
            color: var(--secondary-text);
        }

        .fi-anomaly-amount {
            font-weight: 700;
            color: #E87575;
        }

        .fi-anomaly-reason {
            font-size: 13px;
            color: var(--secondary-text);
            margin-top: 4px;
        }

        .fi-anomaly-compare {
            color: #B9C4C0;
        }

        /* Savings Optimization */
        .fi-savings-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .fi-savings-metric {
            background: var(--light-green);
            border-radius: 10px;
            padding: 16px;
        }

        .fi-savings-label {
            font-size: 12px;
            color: var(--secondary-text);
            margin-bottom: 6px;
        }

        .fi-savings-value {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary-text);
        }

        .fi-goals-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .fi-goal-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .fi-goal-info {
            flex: 1;
        }

        .fi-goal-info strong {
            display: block;
            font-size: 15px;
            color: var(--primary-text);
            margin-bottom: 4px;
        }

        .fi-goal-progress {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .fi-goal-progress-bar {
            flex: 1;
            height: 8px;
            background: var(--border);
            border-radius: 4px;
            overflow: hidden;
        }

        .fi-goal-progress-fill {
            height: 100%;
            background: #2D805F;
            border-radius: 4px;
        }

        .fi-goal-actions {
            display: flex;
            flex-direction: column;
            gap: 6px;
            align-items: flex-end;
        }

        .fi-goal-remaining {
            font-size: 13px;
            color: var(--secondary-text);
        }

        .fi-goal-recommend {
            font-size: 13px;
            font-weight: 600;
            color: #2D805F;
        }

        .fi-goal-cannot-afford {
            color: #E87575;
        }

        /* Health Assessment */
        .fi-health-main {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .fi-health-score-circle {
            text-align: center;
        }

        .fi-health-score-number {
            font-size: 48px;
            font-weight: 700;
            line-height: 1;
        }

        .fi-health-score-label {
            font-size: 14px;
            color: var(--secondary-text);
            margin-top: 4px;
        }

        .fi-health-components {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .fi-health-component {
            background: var(--light-gray);
            border-radius: 10px;
            padding: 16px;
        }

        .fi-health-comp-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .fi-health-comp-name {
            font-size: 14px;
            color: var(--primary-text);
            font-weight: 600;
        }

        .fi-health-comp-score {
            font-size: 14px;
            font-weight: 700;
            color: var(--secondary-text);
        }

        .fi-health-comp-bar {
            height: 8px;
            background: var(--border);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .fi-health-comp-bar-fill {
            height: 100%;
            border-radius: 4px;
        }

        .fi-health-comp-note {
            font-size: 13px;
            color: var(--secondary-text);
        }

        .fi-empty-state {
            text-align: center;
            padding: 40px 24px;
            color: var(--secondary-text);
        }

        .fi-empty-state p {
            margin-bottom: 8px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .fi-row {
                grid-template-columns: 1fr;
            }

            .fi-header {
                flex-direction: column;
                gap: 8px;
                align-items: flex-start;
            }

            .fi-health-main {
                flex-direction: column;
                gap: 20px;
                align-items: flex-start;
            }

            .fi-health-score-circle {
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <div class="app-wrapper">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="dashboard-content">
                <!-- Welcome Section -->
                <section class="welcome-section">
                    <h1>Good morning, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>!</h1>
                    <p>Here's your family's financial overview for <?= $monthName ?> <?= $year ?>.</p>
                </section>

                <!-- Financial Summary Cards -->
                <section class="summary-section">
                    <div class="dashboard-grid">
                        <div class="summary-card income">
                            <div class="label">Monthly Income</div>
                            <div class="value"><?= formatCurrency($monthlyIncome) ?></div>
                            <div class="small"><?= $monthName ?> <?= $year ?></div>
                        </div>

                        <div class="summary-card expenses">
                            <div class="label">Monthly Expenses</div>
                            <div class="value"><?= formatCurrency($monthlyExpenses) ?></div>
                            <div class="small"><?= $monthName ?> <?= $year ?></div>
                        </div>

                        <div class="summary-card left">
                            <div class="label">Money Left</div>
                            <div class="value"><?= formatCurrency($moneyLeft) ?></div>
                            <div class="small">Income minus Expenses</div>
                        </div>

                        <div class="summary-card goals">
                            <div class="label">Savings Goals</div>
                            <div class="value"><?= formatCurrency($totalSaved) ?> / <?= formatCurrency($totalGoalAmount) ?></div>
                            <div class="small"><?= count($goals) ?> goal<?= count($goals) !== 1 ? 's' : '' ?></div>
                        </div>
                    </div>
                </section>

                <!-- Lower Dashboard: Two Column Layout -->
                <section class="dashboard-lower">
                    <!-- Primary Column: Recent Expenses -->
                    <div class="dashboard-primary">
                        <div class="card recent-expenses">
                            <div class="card-header">
                                <h3>Recent Expenses</h3>
                                <a href="<?= BASE_URL ?>/expenses/index.php" class="view-all-link">View All</a>
                            </div>
                            <?php if (count($recentExpenses) > 0): ?>
                                <?php foreach ($recentExpenses as $exp): ?>
                                    <div class="expense-item">
                                        <div class="expense-info">
                                            <span class="expense-category"><?= htmlspecialchars($exp['category']) ?></span>
                                            <span class="expense-description"><?= htmlspecialchars($exp['description']) ?></span>
                                            <span class="expense-date"><?= formatDate($exp['date']) ?></span>
                                        </div>
                                        <span class="expense-amount"><?= formatCurrency($exp['amount']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <p>No expenses recorded for <?= $monthName ?> <?= $year ?>.</p>
                                    <a href="<?= BASE_URL ?>/expenses/index.php" class="btn-primary">Add Expense</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Secondary Column: Expenses by Category -->
                    <div class="dashboard-secondary">
                        <div class="card expenses-by-category">
                            <div class="card-header">
                                <h3>Expenses by Category</h3>
                            </div>
                            <?php if (count($categories) > 0 && $monthlyExpenses > 0): ?>
                                <div class="category-list">
                                    <?php foreach ($categories as $cat): ?>
                                        <?php
                                        $percentage = $monthlyExpenses > 0 ? min(100, max(0, ($cat['total'] / $monthlyExpenses) * 100)) : 0;
                                        ?>
                                        <div class="category-item">
                                            <div class="category-header">
                                                <span class="category-name"><?= htmlspecialchars($cat['category']) ?></span>
                                                <span class="category-amount"><?= formatCurrency($cat['total']) ?></span>
                                            </div>
                                            <div class="category-progress">
                                                <div class="progress">
                                                    <div class="progress-bar" style="width: <?= $percentage ?>%;"></div>
                                                </div>
                                                <span class="category-percentage"><?= number_format($percentage, 1) ?>%</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <p>No expense categories for <?= $monthName ?> <?= $year ?>.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Financial Intelligence Section -->
            <section class="financial-intelligence-section">
                <div class="fi-header">
                    <h2>Financial Intelligence</h2>
                    <p>AI-powered insights into your family's financial health.</p>
                </div>

                <!-- Section A: Financial Overview -->
                <div class="fi-row">
                    <div class="fi-card fi-overview">
                        <div class="fi-card-header">
                            <h3>Financial Health Score</h3>
                            <span class="fi-score-badge" style="background: <?= $healthScore['classColor'] ?>"><?= round($healthScore['totalScore']) ?></span>
                        </div>
                        <div class="fi-overview-grid">
                            <div class="fi-metric">
                                <div class="fi-metric-label">Classification</div>
                                <div class="fi-metric-value" style="color: <?= $healthScore['classColor'] ?>"><?= $healthScore['classification'] ?></div>
                            </div>
                            <div class="fi-metric">
                                <div class="fi-metric-label">Monthly Income</div>
                                <div class="fi-metric-value"><?= formatCurrency($healthScore['monthlyIncome']) ?></div>
                            </div>
                            <div class="fi-metric">
                                <div class="fi-metric-label">Monthly Expenses</div>
                                <div class="fi-metric-value"><?= formatCurrency($healthScore['monthlyExpenses']) ?></div>
                            </div>
                            <div class="fi-metric">
                                <div class="fi-metric-label">Available Balance</div>
                                <div class="fi-metric-value"><?= formatCurrency(max(0, $savingsOptimization['availableBalance'])) ?></div>
                            </div>
                            <div class="fi-metric">
                                <div class="fi-metric-label">Est. Savings Opportunity</div>
                                <div class="fi-metric-value"><?= formatCurrency($expenseReduction['totalPotentialSavings']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section B: Spending Analysis -->
                <div class="fi-row">
                    <div class="fi-card fi-spending-chart">
                        <div class="fi-card-header">
                            <h3>Spending Analysis</h3>
                        </div>
                        <div class="fi-card-body">
                            <?php if ($spendingPattern['hasData']): ?>
                                <div class="fi-chart-container">
                                    <canvas id="spendingChart"></canvas>
                                </div>
                                <div class="fi-spending-insights">
                                    <div class="fi-insight-item">
                                        <strong>Highest Category:</strong> <?= htmlspecialchars($spendingPattern['highestCategory'] ?? 'N/A') ?>
                                    </div>
                                    <div class="fi-insight-item">
                                        <strong>Trend:</strong> <?= ucfirst($spendingPattern['trend']) ?> (<?= $spendingPattern['momChange'] ?>% vs last month)
                                    </div>
                                    <div class="fi-insight-item">
                                        <strong>Avg Daily:</strong> <?= formatCurrency($spendingPattern['avgDailySpending']) ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="fi-empty-state">
                                    <p>No spending data available for <?= $monthName ?> <?= $year ?>.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="fi-card fi-category-breakdown">
                        <div class="fi-card-header">
                            <h3>Expense Categories</h3>
                        </div>
                        <div class="fi-card-body">
                            <?php if ($spendingPattern['hasData'] && count($spendingPattern['categories']) > 0): ?>
                                <div class="fi-category-list">
                                    <?php foreach (array_slice($spendingPattern['categories'], 0, 5) as $cat): ?>
                                        <div class="fi-category-item">
                                            <span class="fi-cat-name"><?= htmlspecialchars($cat['category']) ?></span>
                                            <span class="fi-cat-amount"><?= formatCurrency($cat['total']) ?></span>
                                            <span class="fi-cat-pct"><?= $cat['percentage'] ?>%</span>
                                            <div class="fi-cat-bar">
                                                <div class="fi-cat-bar-fill" style="width: <?= $cat['percentage'] ?>%; background: <?= $cat['percentage'] > 50 ? '#E87575' : '#2D805F' ?>;"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="fi-empty-state">
                                    <p>No expense categories recorded.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Section C: Budget Optimization -->
                <div class="fi-row">
                    <div class="fi-card fi-budget-opt">
                        <div class="fi-card-header">
                            <h3>Budget Optimization</h3>
                        </div>
                        <div class="fi-card-body">
                            <?php if ($budgetOptimization['hasBudgets']): ?>
                                <div class="fi-budget-summary">
                                    <div class="fi-budget-metric">
                                        <div class="fi-budget-label">Total Budgeted</div>
                                        <div class="fi-budget-value"><?= formatCurrency($budgetOptimization['totalBudgeted']) ?></div>
                                    </div>
                                    <div class="fi-budget-metric">
                                        <div class="fi-budget-label">Actual Spent</div>
                                        <div class="fi-budget-value"><?= formatCurrency($budgetOptimization['totalActual']) ?></div>
                                    </div>
                                    <div class="fi-budget-metric">
                                        <div class="fi-budget-label">Utilization</div>
                                        <div class="fi-budget-value"><?= $budgetOptimization['overallUtilization'] ?>%</div>
                                    </div>
                                    <div class="fi-budget-metric">
                                        <div class="fi-budget-label">Income Remaining</div>
                                        <div class="fi-budget-value"><?= formatCurrency(max(0, $budgetOptimization['incomeRemaining'])) ?></div>
                                    </div>
                                </div>
                                <?php if (count($budgetOptimization['categoriesNeedingAdjustment']) > 0): ?>
                                    <div class="fi-budget-adjustments">
                                        <strong>Categories Needing Attention:</strong>
                                        <ul class="fi-adjustment-list">
                                            <?php foreach (array_slice($budgetOptimization['categoriesNeedingAdjustment'], 0, 3) as $adj): ?>
                                                <li>
                                                    <?= htmlspecialchars($adj['category']) ?>: Budgeted <?= formatCurrency($adj['budget']) ?>, Spent <?= formatCurrency($adj['actual']) ?>
                                                    <?php if ($adj['status'] === 'no_budget'): ?> (No budget set)
                                                    <?php elseif ($adj['status'] === 'exceeded'): ?> (Over budget by <?= formatCurrency(abs($adj['variance'])) ?>)
                                                    <?php endif ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="fi-empty-state">
                                    <p>No budgets set for <?= $monthName ?> <?= $year ?>.</p>
                                    <p>Set budgets in the Budget module to see optimization recommendations.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Section D: Expense Reduction Recommendations -->
                <div class="fi-row">
                    <div class="fi-card fi-recommendations">
                        <div class="fi-card-header">
                            <h3>Expense Reduction Recommendations</h3>
                        </div>
                        <div class="fi-card-body">
                            <?php if ($expenseReduction['hasRecommendations']): ?>
                                <div class="fi-recommendation-list">
                                    <?php foreach ($expenseReduction['recommendations'] as $rec): ?>
                                        <div class="fi-recommendation-item fi-severity-<?= $rec['severity'] ?>">
                                            <div class="fi-rec-header">
                                                <span class="fi-rec-category"><?= htmlspecialchars($rec['category']) ?></span>
                                                <span class="fi-rec-severity"><?= ucfirst($rec['severity']) ?></span>
                                            </div>
                                            <div class="fi-rec-message"><?= htmlspecialchars($rec['recommendation']) ?></div>
                                            <div class="fi-rec-details">
                                                <span class="fi-rec-current">Current: <?= formatCurrency($rec['currentTotal']) ?></span>
                                                <span class="fi-rec-potential">Potential Savings: <?= formatCurrency($rec['potentialSavings']) ?></span>
                                                <?php if ($rec['trendChange'] != 0): ?>
                                                    <span class="fi-rec-trend"><?= ($rec['trendChange'] > 0 ? '+' : '') . number_format($rec['trendChange'], 1) ?>% vs last month</span>
                                                <?php endif ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="fi-total-savings">
                                    <strong>Total Potential Monthly Savings: <?= formatCurrency($expenseReduction['totalPotentialSavings']) ?></strong>
                                </div>
                            <?php else: ?>
                                <div class="fi-empty-state">
                                    <p>No reduction recommendations at this time.</p>
                                    <p>Your spending appears well-balanced or insufficient data is available.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Section E: Anomaly Detection -->
                <div class="fi-row">
                    <div class="fi-card fi-anomalies">
                        <div class="fi-card-header">
                            <h3>Anomaly Detection</h3>
                        </div>
                        <div class="fi-card-body">
                            <?php if ($anomalyDetection['hasAnomalies']): ?>
                                <div class="fi-anomaly-list">
                                    <?php foreach ($anomalyDetection['anomalies'] as $anomaly): ?>
                                        <div class="fi-anomaly-item">
                                            <div class="fi-anomaly-info">
                                                <span class="fi-anomaly-category"><?= htmlspecialchars($anomaly['category']) ?></span>
                                                <span class="fi-anomaly-date"><?= formatDate($anomaly['date']) ?></span>
                                            </div>
                                            <div class="fi-anomaly-amount"><?= formatCurrency($anomaly['amount']) ?></span>
                                            <div class="fi-anomaly-reason">
                                                <small><?= htmlspecialchars($anomaly['reason']) ?></small>
                                                <br><small class="fi-anomaly-compare">Historical avg: <?= formatCurrency($anomaly['historicalAvg']) ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="fi-empty-state">
                                    <p>No unusual spending detected.</p>
                                    <p>Your expenses are within normal historical patterns.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Section F: Savings Optimization -->
                <div class="fi-row">
                    <div class="fi-card fi-savings-opt">
                        <div class="fi-card-header">
                            <h3>Savings Optimization</h3>
                        </div>
                        <div class="fi-card-body">
                            <?php if ($savingsOptimization['hasGoals']): ?>
                                <div class="fi-savings-summary">
                                    <div class="fi-savings-metric">
                                        <div class="fi-savings-label">Total Saved</div>
                                        <div class="fi-savings-value"><?= formatCurrency($savingsOptimization['totalCurrentSaved']) ?></div>
                                    </div>
                                    <div class="fi-savings-metric">
                                        <div class="fi-savings-label">Target Amount</div>
                                        <div class="fi-savings-value"><?= formatCurrency($savingsOptimization['totalTargetAmount']) ?></div>
                                    </div>
                                    <div class="fi-savings-metric">
                                        <div class="fi-savings-label">Remaining Needed</div>
                                        <div class="fi-savings-value"><?= formatCurrency($savingsOptimization['totalRemainingNeeded']) ?></div>
                                    </div>
                                    <div class="fi-savings-metric">
                                        <div class="fi-savings-label">Recommended Monthly</div>
                                        <div class="fi-savings-value"><?= formatCurrency($savingsOptimization['totalRecommendedMonthly']) ?></div>
                                    </div>
                                </div>
                                <div class="fi-goals-list">
                                    <?php foreach ($savingsOptimization['goals'] as $goal): ?>
                                        <div class="fi-goal-item">
                                            <div class="fi-goal-info">
                                                <strong><?= htmlspecialchars($goal['goalName']) ?></strong>
                                                <div class="fi-goal-progress">
                                                    <div class="fi-goal-progress-bar">
                                                        <div class="fi-goal-progress-fill" style="width: <?= $goal['progress'] ?>%;"></div>
                                                    </div>
                                                    <span><?= $goal['progress'] ?>% complete</span>
                                                </div>
                                            </div>
                                            <div class="fi-goal-actions">
                                                <span class="fi-goal-remaining"><?= formatCurrency($goal['remainingAmount']) ?> remaining</span>
                                                <?php if ($goal['canAfford']): ?>
                                                    <span class="fi-goal-recommend">Suggested: <?= formatCurrency($goal['recommendedMonthly']) ?>/month</span>
                                                <?php else: ?>
                                                    <span class="fi-goal-recommend fi-goal-cannot-afford">Insufficient balance</span>
                                                <?php endif ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="fi-empty-state">
                                    <p>No active savings goals.</p>
                                    <p>Create goals in the Savings Goals module to see optimization recommendations.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Section G: Financial Health Assessment -->
                <div class="fi-row">
                    <div class="fi-card fi-health-assessment">
                        <div class="fi-card-header">
                            <h3>Financial Health Assessment</h3>
                        </div>
                        <div class="fi-card-body">
                            <div class="fi-health-main">
                                <div class="fi-health-score-circle">
                                    <div class="fi-health-score-number" style="color: <?= $healthScore['classColor'] ?>"><?= round($healthScore['totalScore']) ?></span>
                                    <div class="fi-health-score-label"><?= $healthScore['classification'] ?></div>
                                </div>
                            </div>
                            <div class="fi-health-components">
                                <?php foreach ($healthScore['components'] as $component): ?>
                                    <div class="fi-health-component">
                                        <div class="fi-health-comp-header">
                                            <span class="fi-health-comp-name"><?= htmlspecialchars($component['name']) ?></span>
                                            <span class="fi-health-comp-score"><?= $component['score'] ?>/<?= $component['maxScore'] ?></span>
                                        </div>
                                        <div class="fi-health-comp-bar">
                                            <div class="fi-health-comp-bar-fill" style="width: <?= ($component['score'] / $component['maxScore']) * 100 ?>%; background: <?= $component['status'] === 'good' ? '#2D805F' : ($component['status'] === 'fair' ? '#F5C94F' : '#E87575') ?>;"></div>
                                        </div>
                                        <div class="fi-health-comp-note"><?= htmlspecialchars($component['description']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <?php include __DIR__ . '/../includes/footer.php'; ?>
        </main>
    </div>

    <script>
        // Mobile sidebar toggle
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

        // Close sidebar on nav link click (mobile)
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    toggleSidebar();
                }
            });
        });
    </script>

    <!-- Spending Chart -->
    <?php if ($spendingPattern['hasData'] && count($spendingPattern['categories']) > 0): ?>
    <script>
        const spendingCtx = document.getElementById('spendingChart').getContext('2d');
        new Chart(spendingCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($spendingPattern['categories'], 'category')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($spendingPattern['categories'], 'total')) ?>,
                    backgroundColor: [
                        '#2D805F', '#3478E5', '#F5C94F', '#E87575', '#246A4D',
                        '#6C757D', '#17A2B8', '#6F42C1', '#FD7E14', '#20C997'
                    ],
                    borderWidth: 0,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.chart._metasets[context.datasetIndex].total;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return label + ': ₱' + value.toLocaleString(undefined, {minimumFractionDigits: 2}) + ' (' + percentage + '%)';
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>