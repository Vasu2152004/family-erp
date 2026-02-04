<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\GenericNotificationMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

class CronController extends Controller
{
    /**
     * Run scheduled tasks and process the queue.
     * Called by Vercel cron or manually with ?token=CRON_SECRET
     */
    public function run(): JsonResponse
    {
        if (!$this->authorizeCron()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        Artisan::call('schedule:run');
        Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--max-time' => 50,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Cron completed',
        ]);
    }

    /**
     * Send a test email to verify Brevo SMTP.
     * Usage: GET /cron/test-mail?token=CRON_SECRET&to=your@email.com
     */
    public function testMail(): JsonResponse|Response
    {
        if (!$this->authorizeCron()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $email = request()->query('to');
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response('Missing or invalid "to" parameter (valid email required)', 400);
        }

        Mail::to($email)->send(new GenericNotificationMail(
            'Brevo Test Email',
            'This is a test email from Family ERP. If you received this, Brevo SMTP is working correctly.'
        ));

        return response()->json([
            'ok' => true,
            'sent_to' => $email,
        ]);
    }

    /**
     * Verify cron request: Authorization Bearer or ?token=CRON_SECRET
     */
    private function authorizeCron(): bool
    {
        $secret = env('CRON_SECRET');
        if (empty($secret)) {
            return false;
        }

        $authHeader = request()->header('Authorization');
        if ($authHeader === 'Bearer ' . $secret) {
            return true;
        }

        $token = request()->query('token');
        return $token === $secret;
    }
}
