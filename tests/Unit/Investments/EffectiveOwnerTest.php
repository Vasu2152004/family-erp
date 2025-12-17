<?php

declare(strict_types=1);

namespace Tests\Unit\Investments;

use App\Models\FamilyMember;
use App\Models\Investment;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class EffectiveOwnerTest extends TestCase
{
    public function test_family_member_is_primary_owner(): void
    {
        $ownerUser = new User(['id' => 5, 'name' => 'Owner']);
        $familyMember = new FamilyMember([
            'family_id' => 11,
            'user_id' => 5,
            'first_name' => 'Owner',
            'last_name' => 'Investor',
            'is_deceased' => false,
        ]);

        $investment = new Investment([
            'family_id' => 11,
            'family_member_id' => 50,
            'created_by' => 42,
            'is_hidden' => true,
        ]);
        $investment->setRelation('familyMember', $familyMember);
        $investment->setRelation('createdBy', new User(['id' => 42, 'name' => 'Creator']));

        $this->assertTrue($investment->isEffectiveOwner($ownerUser));
        $this->assertFalse($investment->isOwnerDeceased());
        $this->assertSame('Owner Investor', $investment->owner_name);
        $this->assertSame(5, $investment->effectiveOwnerUserId());
    }

    public function test_creator_becomes_effective_owner_when_unassigned(): void
    {
        $creator = new User(['id' => 9, 'name' => 'Creator']);
        $creatorFamilyMember = new FamilyMember([
            'family_id' => 21,
            'user_id' => 9,
            'first_name' => 'Creator',
            'last_name' => 'Investor',
            'is_deceased' => true,
        ]);
        $creator->setRelation('familyMember', $creatorFamilyMember);

        $investment = new Investment([
            'family_id' => 21,
            'created_by' => 9,
            'is_hidden' => true,
        ]);
        $investment->setRelation('createdBy', $creator);

        $this->assertTrue($investment->isEffectiveOwner($creator));
        $this->assertTrue($investment->isOwnerDeceased());
        $this->assertSame(9, $investment->effectiveOwnerUserId());
        $this->assertSame('Creator', $investment->owner_name);
    }
}
