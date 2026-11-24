<?php
// financial_intelligence.php - Financial Intelligence Algorithms for FAMFINAPP
// Implements 6 financial analysis algorithms using existing database records

// ============================================================================
// ALGORITHM 1: SPENDING PATTERN ANALYSIS
// ============================================================================

function getSpendingPatternAnalysis($pdo, $userId, $month, $year) {
    $result = [];

    // Current month expenses
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
    $stmt->execute([$userId, $month, $year]);
    $currentMonthTotal = floatval($stmt->fetch()['total']);

    // Previous month expenses
    $prevMonth = $month - 1;
    $prevYear = $year;
    if ($prevMonth < 1) {
        $prevMonth = 12;
        $prevYear = $year - 1;
    }
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
    $stmt->execute([$userId, $prevMonth, $prevYear]);
    $prevMonthTotal = floatval($stmt->fetch()['total']);

    // Expenses by category (current month)
    $stmt = $pdo->prepare("SELECT category, COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ? GROUP BY category ORDER BY total DESC");
    $stmt->execute([$userId, $month, $year]);
    $categories = $stmt->fetchAll();

    // Daily averages
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $today = (int) date('j');
    $elapsedDays = min($today, $daysInMonth);
    $avgDailySpending = $currentMonthTotal > 0 && $elapsedDays > 0 ? $currentMonthTotal / $elapsedDays : 0;

    // Weekday vs Weekend analysis
    $stmt = $pdo->prepare("SELECT DAYOFWEEK(date) as dow, SUM(amount) as total FROM expenses WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ? GROUP BY DAYOFWEEK(date)");
    $stmt->execute([$userId, $month, $year]);
    $weekdayData = $stmt->fetchAll();
    $weekdaySpending = 0;
    $weekendSpending = 0;
    foreach ($weekdayData as $wd) {
        $dow = intval($wd['dow']); // 1=Sunday, 7=Saturday
        if ($dow == 1 || $dow == 7) {
            $weekendSpending += floatval($wd['total']);
        } else {
            $weekdaySpending += floatval($wd['total']);
        }
    }

    // Highest spending category
    $highestCategory = null;
    $highestAmount = 0;
    foreach ($categories as $cat) {
        $amt = floatval($cat['total']);
        if ($amt > $highestAmount) {
            $highestAmount = $amt;
            $highestCategory = $cat['category'];
        }
    }

    // Month-over-month change
    $momChange = 0;
    if ($prevMonthTotal > 0) {
        $momChange = (($currentMonthTotal - $prevMonthTotal) / $prevMonthTotal) * 100;
    } elseif ($currentMonthTotal > 0) {
        $momChange = 100; // New spending
    }

    // Category percentages
    $categoryPercentages = [];
    foreach ($categories as $cat) {
        $catTotal = floatval($cat['total']);
        $pct = $currentMonthTotal > 0 ? ($catTotal / $currentMonthTotal) * 100 : 0;
        $categoryPercentages[] = [
            'category' => $cat['category'],
            'total' => $catTotal,
            'percentage' => round($pct, 1)
        ];
    }

    // Trend determination
    $trend = 'stable';
    if ($momChange > 10) {
        $trend = 'increasing';
    } elseif ($momChange < -10) {
        $trend = 'decreasing';
    }

    $result = [
        'currentMonthTotal' => round($currentMonthTotal, 2),
        'prevMonthTotal' => round($prevMonthTotal, 2),
        'momChange' => round($momChange, 1),
        'trend' => $trend,
        'avgDailySpending' => round($avgDailySpending, 2),
        'weekdaySpending' => round($weekdaySpending, 2),
        'weekendSpending' => round($weekendSpending, 2),
        'highestCategory' => $highestCategory,
        'highestAmount' => round($highestAmount, 2),
        'categories' => $categoryPercentages,
        'totalCategories' => count($categories),
        'hasData' => $currentMonthTotal > 0 || count($categories) > 0
    ];

    return $result;
}

// ============================================================================
// ALGORITHM 2: BUDGET OPTIMIZATION
// ============================================================================

function getBudgetOptimization($pdo, $userId, $month, $year) {
    $result = [];

    // Get monthly income
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM income WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
    $stmt->execute([$userId, $month, $year]);
    $monthlyIncome = floatval($stmt->fetch()['total']);

    // Get budgets for current month
    $stmt = $pdo->prepare("SELECT category, budget_amount FROM budgets WHERE user_id = ? AND month = ? AND year = ?");
    $stmt->execute([$userId, $month, $year]);
    $budgets = $stmt->fetchAll();

    // Get actual expenses by category
    $stmt = $pdo->prepare("SELECT category, COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ? GROUP BY category");
    $stmt->execute([$userId, $month, $year]);
    $actualExpenses = [];
    while ($row = $stmt->fetch()) {
        $actualExpenses[$row['category']] = floatval($row['total']);
    }

    $budgetItems = [];
    $totalBudgeted = 0;
    $totalActual = 0;
    $categoriesNeedingAdjustment = [];

    foreach ($budgets as $budget) {
        $category = $budget['category'];
        $budgetAmount = floatval($budget['budget_amount']);
        $actualAmount = isset($actualExpenses[$category]) ? $actualExpenses[$category] : 0;
        $utilization = $budgetAmount > 0 ? ($actualAmount / $budgetAmount) * 100 : 0;
        $variance = $budgetAmount - $actualAmount;
        $status = 'normal';
        if ($utilization >= 100) {
            $status = 'exceeded';
        } elseif ($utilization >= 80) {
            $status = 'warning';
        }

        $totalBudgeted += $budgetAmount;
        $totalActual += $actualAmount;

        if ($status === 'exceeded') {
            $categoriesNeedingAdjustment[] = [
                'category' => $category,
                'budget' => round($budgetAmount, 2),
                'actual' => round($actualAmount, 2),
                'variance' => round($variance, 2),
                'utilization' => round($utilization, 1),
                'status' => $status,
                'suggested' => round($actualAmount * 1.05, 2) // 5% buffer over actual
            ];
        }

        $budgetItems[] = [
            'category' => $category,
            'budget' => round($budgetAmount, 2),
            'actual' => round($actualAmount, 2),
            'variance' => round($variance, 2),
            'utilization' => round($utilization, 1),
            'status' => $status,
            'suggested' => round(max($actualAmount, $budgetAmount * 0.8), 2)
        ];
    }

    // Check for categories with spending but no budget
    foreach ($actualExpenses as $category => $amount) {
        $hasBudget = false;
        foreach ($budgets as $budget) {
            if ($budget['category'] === $category) {
                $hasBudget = true;
                break;
            }
        }
        if (!$hasBudget && $amount > 0) {
            $categoriesNeedingAdjustment[] = [
                'category' => $category,
                'budget' => 0,
                'actual' => round($amount, 2),
                'variance' => round(-$amount, 2),
                'utilization' => 0,
                'status' => 'no_budget',
                'suggested' => round($amount * 1.1, 2)
            ];
        }
    }

    $overallUtilization = $totalBudgeted > 0 ? ($totalActual / $totalBudgeted) * 100 : 0;
    $incomeRemaining = $monthlyIncome - $totalBudgeted;

    $result = [
        'monthlyIncome' => round($monthlyIncome, 2),
        'totalBudgeted' => round($totalBudgeted, 2),
        'totalActual' => round($totalActual, 2),
        'overallUtilization' => round($overallUtilization, 1),
        'incomeRemaining' => round($incomeRemaining, 2),
        'budgetItems' => $budgetItems,
        'categoriesNeedingAdjustment' => $categoriesNeedingAdjustment,
        'hasBudgets' => count($budgets) > 0,
        'hasData' => $monthlyIncome > 0 || count($budgets) > 0 || count($actualExpenses) > 0
    ];

    return $result;
}

// ============================================================================
// ALGORITHM 3: EXPENSE REDUCTION RECOMMENDATIONS
// ============================================================================

function getExpenseReductionRecommendations($pdo, $userId, $month, $year) {
    $result = [];

    // Get current month expenses by category
    $stmt = $pdo->prepare("SELECT category, COALESCE(SUM(amount), 0) as total, COUNT(*) as count FROM expenses WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ? GROUP BY category ORDER BY total DESC");
    $stmt->execute([$userId, $month, $year]);
    $categories = $stmt->fetchAll();

    // Get previous month for trend comparison
    $prevMonth = $month - 1;
    $prevYear = $year;
    if ($prevMonth < 1) {
        $prevMonth = 12;
        $prevYear = $year - 1;
    }

    $previousCategoryTotals = [];
    $stmt = $pdo->prepare("SELECT category, COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ? GROUP BY category");
    $stmt->execute([$userId, $prevMonth, $prevYear]);
    while ($row = $stmt->fetch()) {
        $previousCategoryTotals[$row['category']] = floatval($row['total']);
    }

    $totalCurrentMonth = 0;
    foreach ($categories as $cat) {
        $totalCurrentMonth += floatval($cat['total']);
    }

    // Pre-compute max category total (categories already ordered DESC by SQL)
    $maxCategoryTotal = count($categories) > 0 ? floatval($categories[0]['total']) : 0;

    $recommendations = [];
    $totalPotentialSavings = 0;

    $categoryDescriptions = [
        'Food' => 'Consider planning weekly meals and reducing unnecessary food delivery purchases.',
        'Transportation' => 'Consider combining errands and using public transport where possible.',
        'Bills' => 'Review service providers and consider switching to more affordable plans.',
        'Education' => 'Look for free or lower-cost learning resources.',
        'Health' => 'Consider preventive care to reduce future medical costs.',
        'Shopping' => 'Set a monthly spending limit for non-essential purchases.',
        'Other' => 'Review miscellaneous spending and categorize properly.'
    ];

    foreach ($categories as $cat) {
        $category = $cat['category'];
        $currentTotal = floatval($cat['total']);
        $prevTotal = isset($previousCategoryTotals[$category]) ? $previousCategoryTotals[$category] : 0;
        $trendChange = $prevTotal > 0 ? (($currentTotal - $prevTotal) / $prevTotal) * 100 : 0;

        $recommendation = null;
        $potentialSavings = 0;
        $severity = 'low';

        // High spending category (>30% of total)
        if ($totalCurrentMonth > 0 && ($currentTotal / $totalCurrentMonth) > 0.30) {
            $recommendation = $categoryDescriptions[$category] ?? "Review {$category} expenses for optimization opportunities.";
            $potentialSavings = $currentTotal * 0.10; // 10% reduction
            $severity = 'high';
        }
        // Increasing trend (>15% increase)
        elseif ($trendChange > 15 && $prevTotal > 0) {
            $recommendation = "{$category} spending increased by " . number_format($trendChange, 1) . "% last month. " . ($categoryDescriptions[$category] ?? "Review this category for savings.");
            $potentialSavings = ($currentTotal - $prevTotal) * 0.5;
            $severity = 'medium';
        }
        // Consistent high spending (top category)
        elseif ($currentTotal === $maxCategoryTotal && $currentTotal > 0) {
            $recommendation = $categoryDescriptions[$category] ?? "Consider reducing {$category} expenses.";
            $potentialSavings = $currentTotal * 0.05;
            $severity = 'medium';
        }

        if ($recommendation) {
            $recommendations[] = [
                'category' => $category,
                'currentTotal' => round($currentTotal, 2),
                'prevTotal' => round($prevTotal, 2),
                'trendChange' => round($trendChange, 1),
                'recommendation' => $recommendation,
                'potentialSavings' => round(max(0, $potentialSavings), 2),
                'severity' => $severity
            ];
            $totalPotentialSavings += max(0, $potentialSavings);
        }
    }

    // Sort by severity (high first) then by potential savings
    usort($recommendations, function($a, $b) {
        $sevOrder = ['high' => 3, 'medium' => 2, 'low' => 1];
        if ($sevOrder[$a['severity']] !== $sevOrder[$b['severity']]) {
            return $sevOrder[$b['severity']] - $sevOrder[$a['severity']];
        }
        return $b['potentialSavings'] <=> $a['potentialSavings'];
    });

    $result = [
        'recommendations' => array_slice($recommendations, 0, 5),
        'totalPotentialSavings' => round($totalPotentialSavings, 2),
        'totalCurrentMonth' => round($totalCurrentMonth, 2),
        'hasRecommendations' => count($recommendations) > 0,
        'hasData' => $totalCurrentMonth > 0
    ];

    return $result;
}

// ============================================================================
// ALGORITHM 4: ANOMALY DETECTION
// ============================================================================

function getAnomalyDetection($pdo, $userId, $month, $year) {
    $result = [];

    // Get historical category averages (past 3 months excluding current)
    $threeMonthsAgo = date('Y-m-d', mktime(0, 0, 0, $month - 3, 1, $year));
    $stmt = $pdo->prepare("SELECT category, AVG(amount) as avg_amount, MAX(amount) as max_amount, COUNT(*) as txn_count FROM expenses WHERE user_id = ? AND date >= ? AND date < ? GROUP BY category");
    $stmt->execute([$userId, $threeMonthsAgo, date('Y-m-d', mktime(0, 0, 0, $month + 1, 0, $year))]);
    $historicalStats = [];
    while ($row = $stmt->fetch()) {
        $historicalStats[$row['category']] = [
            'avg' => floatval($row['avg_amount']),
            'max' => floatval($row['max_amount']),
            'count' => intval($row['txn_count'])
        ];
    }

    // Get current month individual transactions
    $stmt = $pdo->prepare("SELECT id, category, description, amount, date FROM expenses WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ? ORDER BY amount DESC");
    $stmt->execute([$userId, $month, $year]);
    $transactions = $stmt->fetchAll();

    $anomalies = [];

    foreach ($transactions as $txn) {
        $category = $txn['category'];
        $amount = floatval($txn['amount']);

        if (!isset($historicalStats[$category]) || $historicalStats[$category]['count'] < 2) {
            continue; // Not enough historical data
        }

        $avg = $historicalStats[$category]['avg'];
        $max = $historicalStats[$category]['max'];

        // Calculate standard deviation roughly (using max as proxy for high variance)
        $stdDev = $avg > 0 ? ($max - $avg) / 2 : 0;

        $isAnomaly = false;
        $reason = '';
        $deviationPercent = 0;

        if ($stdDev > 0 && $amount > $avg + (2 * $stdDev)) {
            $isAnomaly = true;
            $deviationPercent = $avg > 0 ? (($amount - $avg) / $avg) * 100 : 0;
            $reason = "Transaction exceeds historical average by " . number_format($deviationPercent, 1) . "%";
        } elseif ($amount > $max * 1.5 && $max > 0) {
            $isAnomaly = true;
            $deviationPercent = $max > 0 ? (($amount - $max) / $max) * 100 : 0;
            $reason = "Transaction exceeds previous maximum by " . number_format($deviationPercent, 1) . "%";
        }

        if ($isAnomaly) {
            $anomalies[] = [
                'id' => $txn['id'],
                'category' => $category,
                'description' => htmlspecialchars($txn['description'] ?? ''),
                'amount' => round($amount, 2),
                'date' => $txn['date'],
                'historicalAvg' => round($avg, 2),
                'deviationPercent' => round($deviationPercent, 1),
                'reason' => $reason
            ];
        }
    }

    // Sort by deviation percent (highest first)
    usort($anomalies, function($a, $b) {
        return $b['deviationPercent'] <=> $a['deviationPercent'];
    });

    $result = [
        'anomalies' => array_slice($anomalies, 0, 5),
        'totalAnomalies' => count($anomalies),
        'hasAnomalies' => count($anomalies) > 0,
        'hasData' => count($transactions) > 0
    ];

    return $result;
}

// ============================================================================
// ALGORITHM 5: SAVINGS OPTIMIZATION
// ============================================================================

function getSavingsOptimization($pdo, $userId, $month, $year) {
    $result = [];

    // Get monthly income and expenses
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM income WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
    $stmt->execute([$userId, $month, $year]);
    $monthlyIncome = floatval($stmt->fetch()['total']);

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
    $stmt->execute([$userId, $month, $year]);
    $monthlyExpenses = floatval($stmt->fetch()['total']);

    $availableBalance = $monthlyIncome - $monthlyExpenses;

    // Get active savings goals
    $stmt = $pdo->prepare("SELECT id, goal_name, target_amount, current_amount, target_date, status FROM savings_goals WHERE user_id = ? AND status = 'active'");
    $stmt->execute([$userId]);
    $goals = $stmt->fetchAll();

    $goalsOptimization = [];
    $totalRemainingNeeded = 0;
    $totalCurrentSaved = 0;
    $totalTargetAmount = 0;

    foreach ($goals as $goal) {
        $target = floatval($goal['target_amount']);
        $current = floatval($goal['current_amount']);
        $remaining = $target - $current;
        $progress = $target > 0 ? min(100, max(0, ($current / $target) * 100)) : 0;

        $totalCurrentSaved += $current;
        $totalTargetAmount += $target;
        $totalRemainingNeeded += max(0, $remaining);

        // Calculate recommended monthly contribution
        $recommendedMonthly = 0;
        $monthsToGoal = null;
        $completionDate = null;

        if ($remaining > 0 && $goal['target_date']) {
            $targetDate = new DateTime($goal['target_date']);
            $currentDate = new DateTime();
            $monthsToGoal = max(1, (int) $targetDate->diff($currentDate)->format('%m') + ($targetDate->diff($currentDate)->format('%y') * 12));
            $recommendedMonthly = $remaining / $monthsToGoal;
            $completionDate = $goal['target_date'];
        } elseif ($remaining > 0 && $availableBalance > 0) {
            // No target date, suggest spreading over 12 months
            $monthsToGoal = 12;
            $recommendedMonthly = min($remaining / 12, $availableBalance * 0.5);
        }

        $goalsOptimization[] = [
            'id' => $goal['id'],
            'goalName' => htmlspecialchars($goal['goal_name']),
            'targetAmount' => round($target, 2),
            'currentAmount' => round($current, 2),
            'remainingAmount' => round(max(0, $remaining), 2),
            'progress' => round($progress, 1),
            'targetDate' => $goal['target_date'] ?? null,
            'recommendedMonthly' => round(max(0, $recommendedMonthly), 2),
            'monthsToGoal' => $monthsToGoal,
            'completionDate' => $completionDate,
            'canAfford' => $recommendedMonthly <= $availableBalance
        ];
    }

    // Sort by progress (lowest first - needs most help)
    usort($goalsOptimization, function($a, $b) {
        return $a['progress'] <=> $b['progress'];
    });

    // Calculate total recommended monthly contribution
    $totalRecommendedMonthly = 0;
    foreach ($goalsOptimization as $g) {
        if ($g['canAfford']) {
            $totalRecommendedMonthly += $g['recommendedMonthly'];
        }
    }

    // Cap at available balance
    if ($totalRecommendedMonthly > $availableBalance && $availableBalance > 0) {
        $totalRecommendedMonthly = $availableBalance;
    }

    $savingsRate = $monthlyIncome > 0 ? ($totalCurrentSaved / $monthlyIncome) * 100 : 0;

    $result = [
        'monthlyIncome' => round($monthlyIncome, 2),
        'monthlyExpenses' => round($monthlyExpenses, 2),
        'availableBalance' => round(max(0, $availableBalance), 2),
        'totalCurrentSaved' => round($totalCurrentSaved, 2),
        'totalTargetAmount' => round($totalTargetAmount, 2),
        'totalRemainingNeeded' => round(max(0, $totalRemainingNeeded), 2),
        'savingsRate' => round(max(0, $savingsRate), 1),
        'goals' => array_slice($goalsOptimization, 0, 5),
        'totalRecommendedMonthly' => round(max(0, $totalRecommendedMonthly), 2),
        'hasGoals' => count($goals) > 0,
        'hasData' => $monthlyIncome > 0 || count($goals) > 0
    ];

    return $result;
}

// ============================================================================
// ALGORITHM 6: FINANCIAL HEALTH SCORE
// ============================================================================

function getFinancialHealthScore($pdo, $userId, $month, $year) {
    $result = [];

    // Get income and expenses
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM income WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
    $stmt->execute([$userId, $month, $year]);
    $monthlyIncome = floatval($stmt->fetch()['total']);

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
    $stmt->execute([$userId, $month, $year]);
    $monthlyExpenses = floatval($stmt->fetch()['total']);

    // Get savings goals
    $stmt = $pdo->prepare("SELECT target_amount, current_amount FROM savings_goals WHERE user_id = ? AND status = 'active'");
    $stmt->execute([$userId]);
    $goals = $stmt->fetchAll();

    // Get budgets
    $stmt = $pdo->prepare("SELECT category, budget_amount FROM budgets WHERE user_id = ? AND month = ? AND year = ?");
    $stmt->execute([$userId, $month, $year]);
    $budgets = $stmt->fetchAll();

    $score = 0;
    $components = [];
    $dataAvailable = false;

    // --- Component 1: Savings Rate (25 points) ---
    $savingsRate = 0;
    if ($monthlyIncome > 0) {
        $totalSaved = 0;
        foreach ($goals as $goal) {
            $totalSaved += floatval($goal['current_amount']);
        }
        $savingsRate = ($totalSaved / $monthlyIncome) * 100;
        $dataAvailable = true;
    }
    $savingsScore = min(25, max(0, round($savingsRate / 100 * 25, 1)));
    $score += $savingsScore;
    $components[] = [
        'name' => 'Savings Rate',
        'weight' => '25 pts',
        'score' => round($savingsScore, 1),
        'maxScore' => 25,
        'value' => round($savingsRate, 1),
        'description' => $monthlyIncome > 0 ? "You have saved " . round($savingsRate, 1) . "% of your monthly income." : "No income data available.",
        'status' => $savingsScore >= 20 ? 'good' : ($savingsScore >= 10 ? 'fair' : 'poor')
    ];

    // --- Component 2: Budget Adherence (25 points) ---
    $budgetScore = 0;
    $budgetAdherence = 100;
    if (count($budgets) > 0) {
        $totalBudgeted = 0;
        $totalActual = 0;
        $stmt = $pdo->prepare("SELECT category, COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ? GROUP BY category");
        $stmt->execute([$userId, $month, $year]);
        $actualByCategory = [];
        while ($row = $stmt->fetch()) {
            $actualByCategory[$row['category']] = floatval($row['total']);
        }
        foreach ($budgets as $budget) {
            $totalBudgeted += floatval($budget['budget_amount']);
            $category = $budget['category'];
            $actual = isset($actualByCategory[$category]) ? $actualByCategory[$category] : 0;
            $totalActual += $actual;
        }
        if ($totalBudgeted > 0) {
            $budgetAdherence = ($totalActual / $totalBudgeted) * 100;
            $dataAvailable = true;
        }
        // Lower adherence % means better (under budget = good)
        $budgetScore = min(25, max(0, round((200 - $budgetAdherence) / 200 * 25, 1)));
        if ($budgetAdherence <= 100) {
            $budgetScore = min(25, round((100 - $budgetAdherence) / 100 * 25, 1) + 12.5);
        }
    }
    $score += $budgetScore;
    $components[] = [
        'name' => 'Budget Adherence',
        'weight' => '25 pts',
        'score' => round($budgetScore, 1),
        'maxScore' => 25,
        'value' => round($budgetAdherence, 1),
        'description' => count($budgets) > 0 ? "Your spending is " . round($budgetAdherence, 1) . "% of your budgeted amount." : "No budgets set for this month.",
        'status' => $budgetScore >= 20 ? 'good' : ($budgetScore >= 10 ? 'fair' : 'poor')
    ];

    // --- Component 3: Expense-to-Income Ratio (25 points) ---
    $expenseRatioScore = 0;
    $expenseToIncomeRatio = 0;
    if ($monthlyIncome > 0) {
        $expenseToIncomeRatio = ($monthlyExpenses / $monthlyIncome) * 100;
        $dataAvailable = true;
        // Lower ratio = better
        if ($expenseToIncomeRatio <= 50) {
            $expenseRatioScore = 25;
        } elseif ($expenseToIncomeRatio <= 70) {
            $expenseRatioScore = 20;
        } elseif ($expenseToIncomeRatio <= 85) {
            $expenseRatioScore = 15;
        } elseif ($expenseToIncomeRatio <= 100) {
            $expenseRatioScore = 10;
        } else {
            $expenseRatioScore = 5;
        }
    }
    $score += $expenseRatioScore;
    $components[] = [
        'name' => 'Expense-to-Income Ratio',
        'weight' => '25 pts',
        'score' => round($expenseRatioScore, 1),
        'maxScore' => 25,
        'value' => round($expenseToIncomeRatio, 1),
        'description' => $monthlyIncome > 0 ? "Expenses are " . round($expenseToIncomeRatio, 1) . "% of your income." : "No income data available.",
        'status' => $expenseRatioScore >= 20 ? 'good' : ($expenseRatioScore >= 10 ? 'fair' : 'poor')
    ];

    // --- Component 4: Financial Consistency (15 points) ---
    $consistencyScore = 0;
    // Based on having regular income and expenses recorded
    if ($monthlyIncome > 0) {
        $consistencyScore = 10;
        $dataAvailable = true;
    }
    if ($monthlyExpenses > 0) {
        $consistencyScore += 5;
    }
    $score += $consistencyScore;
    $components[] = [
        'name' => 'Financial Consistency',
        'weight' => '15 pts',
        'score' => round($consistencyScore, 1),
        'maxScore' => 15,
        'value' => $monthlyIncome > 0 ? 100 : 0,
        'description' => $monthlyIncome > 0 ? "Regular income and expense tracking detected." : "Insufficient transaction data for consistency assessment.",
        'status' => $consistencyScore >= 10 ? 'good' : ($consistencyScore >= 5 ? 'fair' : 'poor')
    ];

    // --- Component 5: Savings Goal Progress (10 points) ---
    $goalProgressScore = 0;
    $goalProgress = 0;
    if (count($goals) > 0) {
        $totalTarget = 0;
        $totalCurrent = 0;
        foreach ($goals as $goal) {
            $totalTarget += floatval($goal['target_amount']);
            $totalCurrent += floatval($goal['current_amount']);
        }
        if ($totalTarget > 0) {
            $goalProgress = ($totalCurrent / $totalTarget) * 100;
            $dataAvailable = true;
        }
        if ($goalProgress >= 75) {
            $goalProgressScore = 10;
        } elseif ($goalProgress >= 50) {
            $goalProgressScore = 7;
        } elseif ($goalProgress >= 25) {
            $goalProgressScore = 5;
        } elseif ($goalProgress > 0) {
            $goalProgressScore = 3;
        }
    }
    $score += $goalProgressScore;
    $components[] = [
        'name' => 'Savings Goal Progress',
        'weight' => '10 pts',
        'score' => round($goalProgressScore, 1),
        'maxScore' => 10,
        'value' => round($goalProgress, 1),
        'description' => count($goals) > 0 ? "Goals are " . round($goalProgress, 1) . "% funded on average." : "No active savings goals.",
        'status' => $goalProgressScore >= 8 ? 'good' : ($goalProgressScore >= 5 ? 'fair' : 'poor')
    ];

    // Determine classification
    $classification = 'Critical';
    $classColor = '#E87575';
    if ($score >= 85) {
        $classification = 'Excellent';
        $classColor = '#2D805F';
    } elseif ($score >= 70) {
        $classification = 'Good';
        $classColor = '#347F61';
    } elseif ($score >= 50) {
        $classification = 'Fair';
        $classColor = '#F5C94F';
    } elseif ($score >= 30) {
        $classification = 'Needs Improvement';
        $classColor = '#E87575';
    }

    $result = [
        'totalScore' => round(min(100, max(0, $score)), 1),
        'classification' => $classification,
        'classColor' => $classColor,
        'components' => $components,
        'dataAvailable' => $dataAvailable,
        'monthlyIncome' => round($monthlyIncome, 2),
        'monthlyExpenses' => round($monthlyExpenses, 2),
        'hasData' => $dataAvailable
    ];

    return $result;
}