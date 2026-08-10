<?php

namespace Tests\Browser;

use App\Models\Conversation;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Not a correctness test — this drives the real dashboard to capture one
 * screenshot per screen referenced in the Voice Command PDF reference
 * (see App\Console\Commands\GenerateVoiceCommandsPdf). Runs against the
 * real local dev database (no DatabaseMigrations/RefreshDatabase), so it
 * cleans up any rows it creates itself.
 *
 * Popovers render in the browser's "top layer", which trips up Selenium's
 * own isDisplayed()-based waitFor() — so every wait here checks state
 * through JS (matches(':popover-open') / textContent) instead.
 */
class VoiceCommandScreenshotsTest extends DuskTestCase
{
    public function test_capture_dashboard_screenshots(): void
    {
        $user = User::first();
        $this->assertNotNull($user, 'Need at least one existing user in the local dev DB to log in as.');

        $seededConversations = [
            Conversation::create([
                'user_id' => $user->id,
                'question' => 'What is the strongest way to train agility as a beginner hunter?',
                'answer' => 'Focus on short, explosive sprint intervals combined with agility ladder drills three times a week, and always pair them with proper rest — agility grows fastest when the nervous system recovers between sessions, not from grinding every day.',
                'mode' => 'suggestion',
                'source_title' => null,
                'source_url' => null,
            ]),
            Conversation::create([
                'user_id' => $user->id,
                'question' => 'What is the latest stable version of Laravel?',
                'answer' => 'Laravel 11 is the current long-term-support release as of this year, bringing a slimmer application skeleton and a simplified default structure compared to Laravel 10.',
                'mode' => 'reference',
                'source_title' => 'Laravel Release Notes',
                'source_url' => 'https://laravel.com/docs/releases',
            ]),
        ];

        $this->browse(function (Browser $browser) {
            $popoverOpen = fn (string $selector) => "document.querySelector('{$selector}') && document.querySelector('{$selector}').matches(':popover-open')";

            $browser->loginAs(User::first())
                ->visit('/')
                ->pause(500)
                ->screenshot('01-dashboard');

            $browser->click('#daily-quest');
            $browser->waitUntil($popoverOpen('#upgrade'), 15);
            $browser->pause(600)->screenshot('02-daily-quest');

            $browser->click('#main-quest');
            $browser->waitUntil($popoverOpen('#upgrade'), 15);
            $browser->pause(600)->screenshot('03-main-quest');

            $browser->click('#task-btn');
            $browser->waitUntil($popoverOpen('#upgrade'), 15);
            $browser->pause(600)->screenshot('04-task');

            $browser->click('.history-btn');
            $browser->waitUntil($popoverOpen('#history-modal'), 15);
            $browser->pause(600)->screenshot('05-task-history');

            $browser->click('#history-cancel');
            $browser->pause(400);

            $browser->click('#shop-btn');
            $browser->waitUntil($popoverOpen('#upgrade'), 15);
            $browser->pause(600)->screenshot('06-shop');

            $browser->click('#status-btn');
            $browser->waitUntil($popoverOpen('#status-modal'), 15);
            $browser->pause(600)->screenshot('07-status');

            $browser->script("document.querySelector('#status-modal').hidePopover();");
            $browser->pause(300);

            // Ask-modal and Conversation History are voice-only screens (no
            // dashboard button opens them) — invoke the same top-level JS
            // functions handleVoiceCommand() would call, so the screenshot
            // reflects the exact real UI instead of a mockup.
            $browser->script("openConversationHistory();");
            $browser->waitUntil("document.querySelectorAll('#conversation-history-body .conversation-row').length > 0", 15);
            $browser->pause(400)->screenshot('08-conversation-history');

            $browser->script("document.querySelectorAll('#conversation-history-body .conversation-row')[0].click();");
            $browser->waitUntil("document.querySelector('#ask-answer').textContent.trim().length > 0", 15);
            $browser->pause(400)->screenshot('09-ask-answer');
        });

        Conversation::whereIn('id', collect($seededConversations)->pluck('id'))->delete();
    }
}
