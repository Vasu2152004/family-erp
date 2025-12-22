<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CalendarEvent;
use App\Models\Document;
use App\Models\InventoryItem;
use App\Models\Medicine;
use App\Models\MedicineIntakeReminder;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\DocumentExpiryReminder;
use App\Notifications\EventReminder;
use App\Notifications\LowStockAlert;
use App\Notifications\MedicineExpiryReminder;
use App\Notifications\MedicineIntakeReminder as MedicineIntakeReminderNotification;
use App\Notifications\VehicleExpiryReminder;
use Illuminate\Console\Command;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\HtmlString;
use ReflectionClass;

class TestAllEmailTemplates extends Command
{
    protected $signature = 'mail:test-templates {email : The email address to send test emails to}';
    protected $description = 'Send test emails for all notification templates';

    public function handle(): int
    {
        $recipientEmail = $this->argument('email');

        $validator = Validator::make(
            ['email' => $recipientEmail],
            ['email' => 'required|email']
        );

        if ($validator->fails()) {
            $this->error('✗ Invalid email address: ' . $recipientEmail);
            return Command::FAILURE;
        }

        $this->info('Testing all email templates...');
        $this->newLine();
        $this->line("Recipient: {$recipientEmail}");
        $this->newLine();

        $templates = [
            'Document Expiry Reminder' => fn() => $this->testDocumentExpiryReminder($recipientEmail),
            'Vehicle Expiry Reminder (RC)' => fn() => $this->testVehicleExpiryReminder($recipientEmail, 'rc_expiry'),
            'Vehicle Expiry Reminder (Insurance)' => fn() => $this->testVehicleExpiryReminder($recipientEmail, 'insurance_expiry'),
            'Vehicle Expiry Reminder (PUC)' => fn() => $this->testVehicleExpiryReminder($recipientEmail, 'puc_expiry'),
            'Event Reminder' => fn() => $this->testEventReminder($recipientEmail),
            'Low Stock Alert' => fn() => $this->testLowStockAlert($recipientEmail),
            'Medicine Expiry Reminder' => fn() => $this->testMedicineExpiryReminder($recipientEmail),
            'Medicine Intake Reminder' => fn() => $this->testMedicineIntakeReminder($recipientEmail),
        ];

        $successCount = 0;
        $failCount = 0;

        foreach ($templates as $templateName => $testFunction) {
            $this->line("Testing: {$templateName}...");
            
            try {
                $testFunction();
                $this->info("  ✓ {$templateName} sent successfully");
                $successCount++;
            } catch (\Exception $e) {
                $this->error("  ✗ {$templateName} failed: " . $e->getMessage());
                $failCount++;
            }
            
            $this->newLine();
        }

        $this->info("=== Summary ===");
        $this->line("Total templates: " . count($templates));
        $this->info("Successful: {$successCount}");
        if ($failCount > 0) {
            $this->error("Failed: {$failCount}");
        }
        $this->newLine();
        $this->line("Please check your inbox ({$recipientEmail}) and spam folder for all test emails.");

        return $failCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Get or create user with tenant_id
     */
    private function getOrCreateTestUser(string $email): User
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Create tenant for new user
            $tenant = Tenant::create([
                'name' => 'Test Tenant',
            ]);

            $user = User::create([
                'name' => 'Test User',
                'email' => $email,
                'password' => bcrypt('password'),
                'tenant_id' => $tenant->id,
            ]);
        } elseif (!$user->tenant_id) {
            // Create tenant if user doesn't have one
            $tenant = Tenant::create([
                'name' => 'Test Tenant',
            ]);
            $user->update(['tenant_id' => $tenant->id]);
            $user->refresh();
        }

        return $user;
    }

    private function testDocumentExpiryReminder(string $email): void
    {
        // Get or create user in database with tenant_id
        $user = $this->getOrCreateTestUser($email);

        $document = new Document();
        $document->id = 999999; // Set ID first
        $document->title = 'Passport - Test Document';
        $document->document_type = 'passport';
        $document->expires_at = now()->addDays(30);
        $document->family_id = 1;

        // Send email directly to bypass database notification storage
        $notification = new DocumentExpiryReminder($document);
        $mailMessage = $notification->toMail($user);
        $this->sendMailMessage($user, $mailMessage);
    }

    private function testVehicleExpiryReminder(string $email, string $reminderType): void
    {
        // Get or create user in database with tenant_id
        $user = $this->getOrCreateTestUser($email);

        $vehicle = new Vehicle();
        $vehicle->id = 999999; // Set ID first
        $vehicle->make = 'Honda';
        $vehicle->model = 'City';
        $vehicle->registration_number = 'GJ-01-AB-1234';
        $vehicle->rc_expiry_date = $reminderType === 'rc_expiry' ? now()->addDays(30) : null;
        $vehicle->insurance_expiry_date = $reminderType === 'insurance_expiry' ? now()->addDays(30) : null;
        $vehicle->puc_expiry_date = $reminderType === 'puc_expiry' ? now()->addDays(30) : null;
        $vehicle->family_id = 1;

        // Send email directly to bypass database notification storage
        $notification = new VehicleExpiryReminder($vehicle, $reminderType);
        $mailMessage = $notification->toMail($user);
        $this->sendMailMessage($user, $mailMessage);
    }

    private function testEventReminder(string $email): void
    {
        // Get or create user in database with tenant_id
        $user = $this->getOrCreateTestUser($email);

        $event = new CalendarEvent();
        $event->id = 999999; // Set ID first
        $event->title = 'Family Meeting - Test Event';
        $event->start_at = now()->addHours(2);
        $event->reminder_before_minutes = 120;
        $event->family_id = 1;

        // Send email directly to bypass database notification storage
        $notification = new EventReminder($event);
        $mailMessage = $notification->toMail($user);
        $this->sendMailMessage($user, $mailMessage);
    }

    private function testLowStockAlert(string $email): void
    {
        // Get or create user in database with tenant_id
        $user = $this->getOrCreateTestUser($email);

        $family = new \App\Models\Family([
            'id' => 1,
            'name' => 'Test Family',
        ]);

        $item = new InventoryItem();
        $item->id = 999999; // Set ID first
        $item->name = 'Rice - Test Item';
        $item->qty = 2;
        $item->min_qty = 10;
        $item->unit = 'kg';
        $item->family_id = 1;
        $item->setRelation('family', $family);
        $item->setRelation('category', null);

        // Send email directly to bypass database notification storage
        $notification = new LowStockAlert($item);
        $mailMessage = $notification->toMail($user);
        $this->sendMailMessage($user, $mailMessage);
    }

    private function testMedicineExpiryReminder(string $email): void
    {
        // Get or create user in database with tenant_id
        $user = $this->getOrCreateTestUser($email);

        $medicine = new Medicine();
        $medicine->id = 999999; // Set ID first
        $medicine->name = 'Paracetamol 500mg - Test Medicine';
        $medicine->manufacturer = 'Cipla';
        $medicine->batch_number = 'BATCH123456';
        $medicine->quantity = 50;
        $medicine->unit = 'tablets';
        $medicine->expiry_date = now()->addDays(30);
        $medicine->family_id = 1;

        // Send email directly to bypass database notification storage
        $notification = new MedicineExpiryReminder($medicine);
        $mailMessage = $notification->toMail($user);
        $this->sendMailMessage($user, $mailMessage);
    }

    private function testMedicineIntakeReminder(string $email): void
    {
        // Get or create user in database with tenant_id
        $user = $this->getOrCreateTestUser($email);

        $medicine = new Medicine();
        $medicine->id = 999999; // Set ID first
        $medicine->name = 'Paracetamol 500mg - Test Medicine';
        $medicine->quantity = 50;
        $medicine->unit = 'tablets';
        $medicine->family_id = 1;

        $reminder = new MedicineIntakeReminder();
        $reminder->id = 999999;
        $reminder->reminder_time = now()->setTime(9, 0);
        $reminder->frequency = 'daily';
        $reminder->days_of_week = null;
        $reminder->selected_dates = null;

        // Send email directly to bypass database notification storage
        $notification = new MedicineIntakeReminderNotification($medicine, $reminder);
        $mailMessage = $notification->toMail($user);
        $this->sendMailMessage($user, $mailMessage);
    }

    /**
     * Send MailMessage directly using Mail facade to bypass database notifications
     */
    private function sendMailMessage(User $user, MailMessage $mailMessage): void
    {
        $emailSubject = $mailMessage->subject ?? 'Notification';
        
        // Use reflection to access protected view properties
        $reflection = new ReflectionClass($mailMessage);
        $viewProperty = $reflection->getProperty('view');
        $viewProperty->setAccessible(true);
        $view = $viewProperty->getValue($mailMessage);
        
        $viewDataProperty = $reflection->getProperty('viewData');
        $viewDataProperty->setAccessible(true);
        $viewData = $viewDataProperty->getValue($mailMessage) ?? [];
        
        if ($view) {
            // Render the custom view
            $html = view($view, $viewData)->render();
        } else {
            // Build HTML email content from MailMessage (fallback for default format)
            $html = '<!DOCTYPE html><html><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">';
            
            if ($mailMessage->greeting) {
                $html .= '<p style="font-size: 16px; margin-bottom: 20px;">' . htmlspecialchars($mailMessage->greeting) . '</p>';
            }
            
            foreach ($mailMessage->introLines ?? [] as $line) {
                $html .= '<p style="margin-bottom: 10px;">' . nl2br(htmlspecialchars($line)) . '</p>';
            }
            
            if ($mailMessage->actionText && $mailMessage->actionUrl) {
                $html .= '<p style="margin: 20px 0;">';
                $html .= '<a href="' . htmlspecialchars($mailMessage->actionUrl) . '" style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 5px;">';
                $html .= htmlspecialchars($mailMessage->actionText);
                $html .= '</a>';
                $html .= '</p>';
            }
            
            foreach ($mailMessage->outroLines ?? [] as $line) {
                $html .= '<p style="margin-bottom: 10px;">' . nl2br(htmlspecialchars($line)) . '</p>';
            }
            
            if ($mailMessage->salutation) {
                $html .= '<p style="margin-top: 20px;">' . htmlspecialchars($mailMessage->salutation) . '</p>';
            }
            
            $html .= '</body></html>';
        }
        
        // Send email directly using Mail::send() with HTML Mailable (old style)
        Mail::to($user->email, $user->name)->send(
            new class($html, $emailSubject) extends Mailable
            {
                protected string $emailContent;
                protected string $emailSubject;

                public function __construct(string $html, string $emailSubject)
                {
                    $this->emailContent = $html;
                    $this->emailSubject = $emailSubject;
                }

                public function build()
                {
                    return $this->subject($this->emailSubject)
                        ->html($this->emailContent);
                }
            }
        );
    }
}