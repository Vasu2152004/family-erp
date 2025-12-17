<?php

declare(strict_types=1);

namespace Tests\Unit\Assets;

use App\Models\Asset;
use App\Models\FamilyMember;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class EffectiveOwnerTest extends TestCase
{
    public function test_family_member_remains_primary_owner(): void
    {
        $ownerUser = new User(['id' => 2, 'name' => 'Owner']);
        $familyMember = new FamilyMember([
            'family_id' => 1,
            'user_id' => 2,
            'first_name' => 'Owner',
            'last_name' => 'User',
            'is_deceased' => false,
        ]);

        $asset = new Asset([
            'family_id' => 1,
            'family_member_id' => 10,
            'created_by' => 99,
            'is_locked' => true,
        ]);
        $asset->setRelation('familyMember', $familyMember);
        $asset->setRelation('createdBy', new User(['id' => 99, 'name' => 'Creator']));

        $this->assertTrue($asset->isEffectiveOwner($ownerUser));
        $this->assertFalse($asset->isOwnerDeceased());
        $this->assertSame('Owner User', $asset->owner_name);
        $this->assertSame(2, $asset->effectiveOwnerUserId());
    }

    public function test_creator_is_effective_owner_when_no_family_member(): void
    {
        $creator = new User(['id' => 7, 'name' => 'Creator']);
        $creatorFamilyMember = new FamilyMember([
            'family_id' => 3,
            'user_id' => 7,
            'first_name' => 'Creator',
            'last_name' => 'Owner',
            'is_deceased' => true,
        ]);
        $creator->setRelation('familyMember', $creatorFamilyMember);

        $asset = new Asset([
            'family_id' => 3,
            'created_by' => 7,
            'is_locked' => true,
        ]);
        $asset->setRelation('createdBy', $creator);

        $this->assertTrue($asset->isEffectiveOwner($creator));
        $this->assertTrue($asset->isOwnerDeceased());
        $this->assertSame(7, $asset->effectiveOwnerUserId());
        $this->assertSame('Creator', $asset->owner_name);
    }
}
