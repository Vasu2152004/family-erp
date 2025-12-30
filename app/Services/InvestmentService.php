<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Investment;
use App\Models\InvestmentAccessLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class InvestmentService
{
    /**
     * Calculate current value based on start date and interest rate.
     */
    private function calculateCurrentValue(
        float $amount,
        ?string $startDate,
        ?float $interestRate,
        ?string $interestPeriod,
        ?float $monthlyPremium,
        string $investmentType
    ): ?float {
        // If no start_date or interest_rate, return null (manual entry)
        if (!$startDate || !$interestRate) {
            return null;
        }

        $start = Carbon::parse($startDate);
        $now = Carbon::now();
        
        // For investments that haven't started yet
        if ($now->lt($start)) {
            return $amount;
        }

        $years = $start->diffInYears($now, true);
        $months = $start->diffInMonths($now, true);
        $days = $start->diffInDays($now, true);

        switch ($investmentType) {
            case 'FD': // Fixed Deposit - compound interest
                // Calculate compound interest: A = P(1 + r/n)^(nt)
                // For yearly compounding
                $n = 1; // Compounding frequency (yearly)
                $t = $years;
                $r = $interestRate / 100;
                return $amount * pow(1 + ($r / $n), $n * $t);

            case 'RD': // Recurring Deposit
                // RD formula: M = R × [(1 + i)^n - 1] / (1 - (1 + i)^(-1/3))
                // Simplified: monthly contributions with interest
                if ($monthlyPremium) {
                    $monthlyRate = $interestRate / 12 / 100;
                    $n = (int) $months;
                    if ($n > 0) {
                        $futureValue = $monthlyPremium * ((pow(1 + $monthlyRate, $n) - 1) / $monthlyRate);
                        return $futureValue;
                    }
                }
                return $amount;

            case 'SIP': // Systematic Investment Plan
                // SIP only uses monthly premium and interest rate, NOT amount
                if ($monthlyPremium && $interestRate) {
                    $monthlyRate = $interestRate / 12 / 100;
                    $n = (int) $months;
                    if ($n > 0) {
                        $futureValue = $monthlyPremium * ((pow(1 + $monthlyRate, $n) - 1) / $monthlyRate);
                        return $futureValue;
                    }
                }
                // If no monthly premium or interest rate, return 0 (shouldn't happen for valid SIP)
                return 0;

            default:
                // For other types, use simple interest if we have the data
                if ($interestPeriod === 'YEARLY') {
                    return $amount * (1 + ($interestRate / 100) * $years);
                } elseif ($interestPeriod === 'MONTHLY') {
                    return $amount * (1 + ($interestRate / 12 / 100) * $months);
                } elseif ($interestPeriod === 'QUARTERLY') {
                    $quarters = $months / 3;
                    return $amount * (1 + ($interestRate / 4 / 100) * $quarters);
                }
                // Default to simple yearly interest
                return $amount * (1 + ($interestRate / 100) * $years);
        }
    }
    /**
     * Create a new investment.
     */
    public function createInvestment(array $data, int $tenantId, int $familyId): Investment
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId) {
            // Family Investments (no owner) cannot be hidden
            $isHidden = ($data['is_hidden'] ?? false) && !empty($data['family_member_id']);
            
            // Calculate current_value if start_date and interest_rate are provided
            $calculatedValue = null;
            $investmentType = $data['investment_type'] ?? 'OTHER';
            if (isset($data['start_date']) && isset($data['interest_rate']) && !isset($data['current_value'])) {
                // For SIP, use 0 as amount since it's not used in calculation
                $amountForCalculation = ($investmentType === 'SIP') ? 0 : (float) ($data['amount'] ?? 0);
                $calculatedValue = $this->calculateCurrentValue(
                    $amountForCalculation,
                    $data['start_date'] ?? null,
                    $data['interest_rate'] !== null ? (float) $data['interest_rate'] : null,
                    $data['interest_period'] ?? null,
                    $data['monthly_premium'] !== null ? (float) $data['monthly_premium'] : null,
                    $investmentType
                );
            }

            // Handle amount: for SIP, it's not required, so use 0 or empty value
            $amount = 0;
            if ($investmentType !== 'SIP') {
                $amount = isset($data['amount']) && $data['amount'] !== '' ? (float) $data['amount'] : 0;
            }
            
            $investment = Investment::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'family_member_id' => $data['family_member_id'] ?? null,
                'investment_type' => $investmentType,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'amount' => $amount,
                'start_date' => $data['start_date'] ?? null,
                'interest_rate' => $data['interest_rate'] ?? null,
                'interest_period' => $data['interest_period'] ?? null,
                'monthly_premium' => $data['monthly_premium'] ?? null,
                'current_value' => $data['current_value'] ?? $calculatedValue,
                'is_hidden' => $isHidden,
                'details' => $data['details'] ?? null,
                'created_by' => $data['created_by'] ?? auth()->id(),
            ]);

            // If hidden, encrypt details and set PIN
            if ($investment->is_hidden) {
                if (!empty($data['pin'])) {
                    $investment->setPinHash($data['pin']);
                }
                if (!empty($data['details'])) {
                    $investment->encryptDetails();
                    $investment->save();
                }
            }

            return $investment->fresh();
        });
    }

    /**
     * Update an existing investment.
     */
    public function updateInvestment(int $investmentId, array $data): Investment
    {
        return DB::transaction(function () use ($investmentId, $data) {
            $investment = Investment::findOrFail($investmentId);

            if (!$investment) {
                throw new \RuntimeException('Investment not found.');
            }

            // Calculate current_value if start_date and interest_rate are provided and current_value not explicitly set
            $calculatedValue = null;
            $startDate = isset($data['start_date']) ? $data['start_date'] : ($investment->start_date ?? null);
            $interestRate = isset($data['interest_rate']) ? $data['interest_rate'] : $investment->interest_rate;
            
            // Handle interest_period: validate enum value and convert empty strings to null
            $validPeriods = ['YEARLY', 'MONTHLY', 'QUARTERLY'];
            if (isset($data['interest_period'])) {
                $interestPeriod = $data['interest_period'] !== '' && in_array($data['interest_period'], $validPeriods, true)
                    ? (string) $data['interest_period']
                    : null;
            } else {
                $interestPeriod = $investment->interest_period;
            }
            
            $monthlyPremium = isset($data['monthly_premium']) ? $data['monthly_premium'] : $investment->monthly_premium;
            $amount = $data['amount'] ?? $investment->amount;
            $investmentType = $data['investment_type'] ?? $investment->investment_type;

            if ($startDate && $interestRate && !isset($data['current_value'])) {
                // For SIP, use 0 as amount since it's not used in calculation
                $amountForCalculation = ($investmentType === 'SIP') ? 0 : (float) $amount;
                $calculatedValue = $this->calculateCurrentValue(
                    $amountForCalculation,
                    $startDate instanceof \Carbon\Carbon ? $startDate->format('Y-m-d') : (string) $startDate,
                    $interestRate !== null ? (float) $interestRate : null,
                    $interestPeriod,
                    $monthlyPremium !== null ? (float) $monthlyPremium : null,
                    $investmentType
                );
            }

            // Build update array - ensure enum values are properly handled
            $updateData = [
                'family_member_id' => $data['family_member_id'] ?? $investment->family_member_id,
                'investment_type' => $investmentType,
                'name' => $data['name'] ?? $investment->name,
                'description' => $data['description'] ?? $investment->description,
                'amount' => ($investmentType === 'SIP') ? 0 : $amount,
                'updated_by' => auth()->id(),
            ];

            // Only update start_date if provided
            if (isset($data['start_date'])) {
                $updateData['start_date'] = $startDate instanceof \Carbon\Carbon ? $startDate->format('Y-m-d') : $startDate;
            }

            // Only update interest_rate if provided
            if (isset($data['interest_rate'])) {
                $updateData['interest_rate'] = $interestRate;
            }

            // Only update interest_period if provided
            // CRITICAL: Always use the value directly from $data, never from $investment model
            // This ensures Laravel treats it as a new parameter that needs binding
            if (array_key_exists('interest_period', $data)) {
                // Convert empty string to null
                if ($data['interest_period'] === '' || $data['interest_period'] === null) {
                    $updateData['interest_period'] = null;
                } else {
                    // Ensure it's a valid enum value - use value directly from request
                    $validPeriods = ['YEARLY', 'MONTHLY', 'QUARTERLY'];
                    $periodValue = trim((string) $data['interest_period']);
                    if (in_array($periodValue, $validPeriods, true)) {
                        // Create a fresh string to ensure it's treated as a new value
                        // This is critical for proper parameter binding
                        $updateData['interest_period'] = $periodValue;
                    } else {
                        // Invalid value, skip update
                        unset($updateData['interest_period']);
                    }
                }
            }

            // Only update monthly_premium if provided
            if (isset($data['monthly_premium'])) {
                $updateData['monthly_premium'] = $monthlyPremium;
            }

            // Update current_value if calculated or explicitly provided
            if (isset($data['current_value']) || $calculatedValue !== null) {
                $updateData['current_value'] = $data['current_value'] ?? $calculatedValue;
            }

            // Family Investments (no owner) cannot be hidden
            // If family_member_id is being set to null or is already null, ensure is_hidden is false
            $newFamilyMemberId = $data['family_member_id'] ?? $investment->family_member_id;
            if (empty($newFamilyMemberId) && isset($data['is_hidden']) && $data['is_hidden']) {
                // Don't allow hiding if no owner
                unset($data['is_hidden']);
            }
            
            // Only update is_hidden if provided and valid
            if (isset($data['is_hidden'])) {
                // Only allow hiding if there's an owner
                if (!empty($newFamilyMemberId)) {
                    $updateData['is_hidden'] = (bool) $data['is_hidden'];
                } else {
                    // Force to false if no owner
                    $updateData['is_hidden'] = false;
                }
            }

            // Handle interest_period separately
            // NOTE: If you get "Data truncated" error, run the migration:
            // php artisan migrate
            // This ensures the enum column has the correct values: YEARLY, MONTHLY, QUARTERLY
            if (isset($updateData['interest_period'])) {
                $interestPeriodValue = $updateData['interest_period'];
                unset($updateData['interest_period']);
                
                // Update other fields first
                if (!empty($updateData)) {
                    $investment->update($updateData);
                }
                
                // Validate and update interest_period
                if ($interestPeriodValue === null || $interestPeriodValue === '') {
                    // Set to NULL
                    DB::table('investments')
                        ->where('id', $investment->id)
                        ->update([
                            'interest_period' => null,
                            'updated_at' => now(),
                        ]);
                } else {
                    // Validate the enum value against whitelist
                    $validPeriods = ['YEARLY', 'MONTHLY', 'QUARTERLY'];
                    $periodValue = trim((string) $interestPeriodValue);
                    
                    if (in_array($periodValue, $validPeriods, true)) {
                        // Use raw SQL with proper quoting to ensure the value is treated as a string
                        $pdo = DB::connection()->getPdo();
                        $quotedValue = $pdo->quote($periodValue);
                        
                        try {
                            DB::statement(
                                "UPDATE investments SET interest_period = {$quotedValue}, updated_at = ? WHERE id = ?",
                                [now(), $investment->id]
                            );
                        } catch (\Illuminate\Database\QueryException $e) {
                            // If we get "Data truncated" error, the enum column definition doesn't match
                            // Run the migration: php artisan migrate
                            // Or manually fix the column: ALTER TABLE investments MODIFY COLUMN interest_period ENUM('YEARLY', 'MONTHLY', 'QUARTERLY') NULL
                            throw new \RuntimeException(
                                "Failed to update interest_period. The enum column definition may not match. " .
                                "Please run: php artisan migrate to fix the column definition. " .
                                "Original error: " . $e->getMessage()
                            );
                        }
                    }
                }
                
                $investment->refresh();
            } else {
                // No interest_period to update, use normal update
                $investment->update($updateData);
            }

            // Update details if provided
            if (isset($data['details'])) {
                if ($investment->is_hidden) {
                    $investment->encrypted_details = Crypt::encryptString($data['details']);
                    $investment->details = null;
                } else {
                    $investment->details = $data['details'];
                    $investment->encrypted_details = null;
                }
                $investment->save();
            }

            return $investment->fresh();
        });
    }

    /**
     * Delete an investment.
     */
    public function deleteInvestment(int $investmentId): void
    {
        DB::transaction(function () use ($investmentId) {
            $investment = Investment::findOrFail($investmentId);
            $investment->delete();
        });
    }

    /**
     * Toggle hidden status of investment.
     */
    public function toggleHidden(int $investmentId, bool $isHidden, ?string $pin = null): Investment
    {
        return DB::transaction(function () use ($investmentId, $isHidden, $pin) {
            $investment = Investment::findOrFail($investmentId);

            // Family Investments (no owner) cannot be hidden
            if ($isHidden && empty($investment->family_member_id)) {
                throw new \InvalidArgumentException('Family Investments cannot be hidden. Please assign an owner first.');
            }

            if ($isHidden) {
                // Hiding: encrypt details, set PIN
                if (!empty($investment->details)) {
                    $investment->encryptDetails();
                }
                if ($pin) {
                    $investment->setPinHash($pin);
                }
                $investment->is_hidden = true;
            } else {
                // Unhiding: decrypt details, clear PIN
                if (!empty($investment->encrypted_details)) {
                    $investment->decryptDetails();
                    $investment->encrypted_details = null;
                }
                $investment->pin_hash = null;
                $investment->is_hidden = false;
            }

            $investment->save();
            return $investment->fresh();
        });
    }

    /**
     * Unlock investment with PIN verification.
     */
    public function unlockInvestment(int $investmentId, User $user, ?string $pin = null): Investment
    {
        return DB::transaction(function () use ($investmentId, $user, $pin) {
            $investment = Investment::findOrFail($investmentId);

            if (!$investment->is_hidden) {
                return $investment;
            }

            // Verify PIN if provided
            if ($pin && !$investment->verifyPin($pin)) {
                throw new \InvalidArgumentException('Invalid PIN provided.');
            }

            // Log successful unlock
            $this->logAccess($investmentId, $user, 'unlock_success');

            return $investment->fresh();
        });
    }

    /**
     * Log access to investment.
     */
    public function logAccess(int $investmentId, User $user, string $action, array $metadata = []): InvestmentAccessLog
    {
        $investment = Investment::findOrFail($investmentId);

        return InvestmentAccessLog::create([
            'tenant_id' => $investment->tenant_id,
            'family_id' => $investment->family_id,
            'investment_id' => $investmentId,
            'user_id' => $user->id,
            'action' => $action,
            'ip_address' => $metadata['ip_address'] ?? request()->ip(),
            'user_agent' => $metadata['user_agent'] ?? request()->userAgent(),
            'notes' => $metadata['notes'] ?? null,
        ]);
    }

    /**
     * Check if user can access investment.
     */
    public function canUserAccess(Investment $investment, User $user): bool
    {
        // If not hidden, anyone with view permission can access
        if (!$investment->is_hidden) {
            return true;
        }

        // Check if user has permanent unlock access
        if ($investment->isUnlockedFor($user)) {
            return true;
        }

        // Check if user is the owner
        if ($investment->familyMember && $investment->familyMember->user_id === $user->id) {
            return true;
        }

        // Check if user is ADMIN/OWNER and owner is deceased
        if ($investment->canBeRequestedForUnlock($user)) {
            return false; // Can request but doesn't have direct access yet
        }

        return false;
    }
}

