<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentReminder;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\DocumentExpiryReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('families.documents.index'));
        $this->assertTrue(Route::has('families.documents.create'));
        $this->assertTrue(Route::has('families.documents.store'));
        $this->assertTrue(Route::has('families.documents.download'));
        $this->assertTrue(Route::has('families.documents.verify-password'));
    }

    public function test_admin_can_upload_sensitive_document_and_download_after_password_verification(): void
    {
        Storage::fake('local');
        [$tenant, $family, $admin, $member] = $this->createFamilyContext();

        $file = UploadedFile::fake()->create('passport.pdf', 120, 'application/pdf');

        $response = $this->actingAs($admin)->post(route('families.documents.store', ['family' => $family->id]), [
            'title' => 'Family Passport',
            'document_type' => 'PASSPORT',
            'file' => $file,
            'family_member_id' => $member->id,
            'is_sensitive' => true,
            'password' => 'safe-pass',
            'expires_at' => now()->addDays(60)->toDateString(),
        ]);

        $response->assertRedirect(route('families.documents.index', ['family' => $family->id]));

        $document = Document::first();
        $this->assertNotNull($document);
        Storage::disk('local')->assertExists($document->file_path);

        $download = $this->actingAs($admin)->postJson(route('families.documents.download', ['family' => $family->id, 'document' => $document]));
        $download->assertStatus(403);

        $verify = $this->actingAs($admin)->postJson(route('families.documents.verify-password', ['family' => $family->id, 'document' => $document]), [
            'password' => 'safe-pass',
        ]);
        $verify->assertOk();

        $downloadAfterVerification = $this->actingAs($admin)->post(route('families.documents.download', ['family' => $family->id, 'document' => $document]));
        $downloadAfterVerification->assertOk();
    }

    public function test_expiry_reminder_command_notifies_recipients(): void
    {
        Notification::fake();
        [$tenant, $family, $admin, $member, $linkedUser] = $this->createFamilyContext(includeLinkedUser: true);

        $document = Document::create([
            'tenant_id' => $tenant->id,
            'family_id' => $family->id,
            'family_member_id' => $member->id,
            'uploaded_by' => $admin->id,
            'title' => 'Driving License',
            'document_type' => 'DRIVING_LICENSE',
            'is_sensitive' => true,
            'password_hash' => 'secret',
            'original_name' => 'license.pdf',
            'file_path' => 'documents/test/license.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'expires_at' => now()->addDays(5),
        ]);

        DocumentReminder::create([
            'document_id' => $document->id,
            'remind_at' => now()->toDateString(),
        ]);

        $this->artisan('documents:send-expiry-reminders')->assertExitCode(0);

        Notification::assertSentTo(
            [$admin, $linkedUser],
            DocumentExpiryReminder::class
        );
    }

    private function createFamilyContext(bool $includeLinkedUser = false): array
    {
        $tenant = Tenant::create(['name' => 'Test Tenant']);
        $family = Family::create(['tenant_id' => $tenant->id, 'name' => 'Test Family']);

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        FamilyUserRole::create([
            'tenant_id' => $tenant->id,
            'family_id' => $family->id,
            'user_id' => $admin->id,
            'role' => 'OWNER',
            'is_backup_admin' => false,
        ]);

        $linkedUser = $includeLinkedUser ? User::factory()->create(['tenant_id' => $tenant->id]) : null;

        $member = FamilyMember::create([
            'tenant_id' => $tenant->id,
            'family_id' => $family->id,
            'user_id' => $linkedUser?->id,
            'first_name' => 'Test',
            'last_name' => 'Member',
            'gender' => 'male',
            'relation' => 'Sibling',
            'phone' => '1234567890',
            'email' => 'member@example.com',
            'is_deceased' => false,
        ]);

        return [$tenant, $family, $admin, $member, $linkedUser];
    }
}

