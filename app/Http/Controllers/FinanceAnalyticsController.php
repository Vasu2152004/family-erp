<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasFamilyContext;
use App\Models\Family;
use App\Services\FinanceAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class FinanceAnalyticsController extends Controller
{
    use HasFamilyContext;

    public function __construct(
        private FinanceAnalyticsService $analyticsService
    ) {
    }

    /**
     * Display the finance analytics dashboard.
     */
    public function dashboard(Request $request): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return redirect()->route('finance.index')
                ->with('info', 'Please select a family to view finance analytics.');
        }

        $this->authorize('viewAny', [\App\Models\Transaction::class, $family]);

        $currentYear = now()->year;
        $currentMonth = now()->month;

        // Get account balances
        $accountBalances = $this->analyticsService->getAccountBalances($family->id);

        // Get monthly expenses for current year
        $monthlyData = $this->analyticsService->getMonthlyExpenses($family->id, $currentYear);

        // Get member-wise spending for current month
        $memberWiseData = $this->analyticsService->getMemberWiseSpending($family->id, $currentMonth, $currentYear);

        // Get category-wise expenses for current month
        $categoryWiseData = $this->analyticsService->getCategoryWiseExpenses($family->id, $currentMonth, $currentYear);

        // Get savings trend data
        $savingsTrendData = $this->analyticsService->getSavingsTrend($family->id, $currentYear);

        // Get account balance trends
        $accountBalanceTrends = $this->analyticsService->getAccountBalanceTrends($family->id, $currentYear);

        // Get income sources breakdown
        $incomeSourcesData = $this->analyticsService->getIncomeSources($family->id, $currentMonth, $currentYear);

        // Get expense patterns by day of week
        $expensePatternsData = $this->analyticsService->getExpensePatternsByDay($family->id, $currentMonth, $currentYear);

        return view('finance-analytics.dashboard', compact(
            'family',
            'accountBalances',
            'monthlyData',
            'memberWiseData',
            'categoryWiseData',
            'savingsTrendData',
            'accountBalanceTrends',
            'incomeSourcesData',
            'expensePatternsData',
            'currentYear',
            'currentMonth'
        ));
    }

    /**
     * Get monthly data for charts (API endpoint).
     */
    public function getMonthlyData(Request $request): JsonResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return response()->json(['error' => 'Family not found'], 404);
        }

        $this->authorize('viewAny', [\App\Models\Transaction::class, $family]);

        $year = $request->input('year', now()->year);
        $data = $this->analyticsService->getMonthlyExpenses($family->id, (int) $year);

        return response()->json($data);
    }

    /**
     * Get member-wise data for charts (API endpoint).
     */
    public function getMemberWiseData(Request $request): JsonResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        
        if (!$family) {
            return response()->json(['error' => 'Family not found'], 404);
        }

        $this->authorize('viewAny', [\App\Models\Transaction::class, $family]);

        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $data = $this->analyticsService->getMemberWiseSpending($family->id, (int) $month, (int) $year);

        return response()->json($data);
    }
}
