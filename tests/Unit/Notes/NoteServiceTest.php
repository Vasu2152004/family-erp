<?php

declare(strict_types=1);

namespace Tests\Unit\Notes;

use App\Models\Family;
use App\Models\Note;
use App\Models\Tenant;
use App\Models\User;
use App\Services\NoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class NoteServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_unlock_sets_session_when_pin_matches(): void
    {
        $service = app(NoteService::class);

        $tenant = Tenant::create(['name' => 'Test Tenant']);
        $family = Family::create(['tenant_id' => $tenant->id, 'name' => 'Test Family']);
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
        ]);

        $note = Note::create([
            'tenant_id' => $tenant->id,
            'family_id' => $family->id,
            'title' => 'Locked',
            'visibility' => 'locked',
            'pin_hash' => bcrypt('1234'),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->assertFalse($service->isUnlocked($note));

        $result = $service->unlock($note, '1234');

        $this->assertTrue($result);
        $this->assertTrue($service->isUnlocked($note));
        $this->assertTrue((bool) Session::get("note_unlocked_{$note->id}"));
    }

    public function test_unlock_fails_with_wrong_pin(): void
    {
        $service = app(NoteService::class);

        $tenant = Tenant::create(['name' => 'Test Tenant']);
        $family = Family::create(['tenant_id' => $tenant->id, 'name' => 'Test Family']);
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test2@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
        ]);

        $note = Note::create([
            'tenant_id' => $tenant->id,
            'family_id' => $family->id,
            'title' => 'Locked',
            'visibility' => 'locked',
            'pin_hash' => bcrypt('1234'),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $result = $service->unlock($note, '9999');

        $this->assertFalse($result);
        $this->assertFalse($service->isUnlocked($note));
    }
}

