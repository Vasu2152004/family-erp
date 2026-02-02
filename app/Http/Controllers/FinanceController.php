<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasFamilyContext;
use App\Models\Family;
use App\Services\FinanceAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class FinanceController extends Controller
{
    use HasFamilyContext;

    public function __construct(
        private FinanceAnalyticsService $analyticsService
    ) {
    }

    /**
     * Display the finance dashboard or family selection.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $accessibleFamilies = $this->getAccessibleFamilies();

        if ($accessibleFamilies->isEmpty()) {
            return redirect()->route('families.index')
                ->with('info', 'Please create or join a family to access finance features.');
        }

        $familyId = $request->input('family_id');
        $activeFamily = $this->getActiveFamily($familyId);

        $user = once(fn () => Auth::user());
        $memberWiseExpenses = null;

        if ($activeFamily) {
            $currentMonth = now()->month;
            $currentYear = now()->year;

            $userRole = \App\Models\FamilyUserRole::where('family_id', $activeFamily->id)
                ->where('user_id', $user->id)
                ->first();
            $isAdminOrOwner = $userRole && in_array($userRole->role, ['OWNER', 'ADMIN']);

            if ($isAdminOrOwner) {
                $memberWiseExpenses = $this->analyticsService->getMemberWiseSpending($activeFamily->id, $currentMonth, $currentYear);
            } else {
                $member = \App\Models\FamilyMember::where('family_id', $activeFamily->id)
                    ->where('user_id', $user->id)
                    ->first();
                
                if ($member) {
                    $startDate = \Carbon\Carbon::create($currentYear, $currentMonth, 1)->startOfMonth();
                    $endDate = \Carbon\Carbon::create($currentYear, $currentMonth, 1)->endOfMonth();
                    
                    $totalExpenses = \App\Models\Transaction::where('family_id', $activeFamily->id)
                        ->where('type', 'EXPENSE')
                        ->where('family_member_id', $member->id)
                        ->whereBetween('transaction_date', [$startDate, $endDate])
                        ->sum('amount');
                    
                    if ($totalExpenses > 0) {
                        $memberWiseExpenses = [[
                            'member_id' => $member->id,
                            'member_name' => $member->first_name . ' ' . $member->last_name,
                            'amount' => (float) $totalExpenses,
                        ]];
                    }
                }
            }
        }

        return view('finance.index', compact('accessibleFamilies', 'activeFamily', 'memberWiseExpenses'));
    }
}

