<?php

namespace App\Console\Commands;

use FPDF;
use Illuminate\Console\Command;

/**
 * Renders the full "Voice Mode" command reference (every trigger phrase
 * handleVoiceCommand() in welcome.blade.php recognizes) into a PDF via
 * FPDF, illustrated with the real dashboard screenshots captured by
 * tests/Browser/VoiceCommandScreenshotsTest.php (Laravel Dusk).
 *
 * Run `php artisan dusk --filter=VoiceCommandScreenshotsTest` first so the
 * screenshots exist — this command embeds whichever ones it finds and
 * silently skips a section's image if its file is missing.
 */
class GenerateVoiceCommandsPdf extends Command
{
    protected $signature = 'voice:commands-pdf {--output= : Output path for the generated PDF}';

    protected $description = 'Generate a PDF reference of every Voice Mode command, illustrated with Dusk screenshots';

    private const SCREENSHOTS_DIR = 'tests/Browser/screenshots';

    public function handle(): int
    {
        $outputPath = $this->option('output') ?: storage_path('app/voice-commands-reference.pdf');

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->SetTitle('Solo Leveling - Voice Command Reference');
        $pdf->SetAuthor('The System');

        $this->renderCoverPage($pdf);

        foreach ($this->sections() as $section) {
            $this->renderSection($pdf, $section);
        }

        $pdf->Output('F', $outputPath);

        $this->info("PDF written to {$outputPath}");

        return self::SUCCESS;
    }

    private function renderCoverPage(FPDF $pdf): void
    {
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', 'B', 26);
        $pdf->Ln(60);
        $pdf->Cell(0, 12, $this->txt('SOLO LEVELING'), 0, 1, 'C');
        $pdf->SetFont('Helvetica', 'B', 16);
        $pdf->Cell(0, 10, $this->txt('Voice Mode - Command Reference'), 0, 1, 'C');

        $pdf->Ln(10);
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->SetTextColor(90, 90, 90);
        $pdf->Cell(0, 8, $this->txt('Generated ' . now()->format('F j, Y, H:i')), 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);

        $pdf->Ln(20);
        $pdf->SetFont('Helvetica', 'I', 10);
        $pdf->SetLeftMargin(30);
        $pdf->SetRightMargin(30);
        $pdf->MultiCell(0, 6, $this->txt(
            'Every phrase Nova (the Voice Mode assistant) recognizes, grouped by the screen it applies to. '
            . 'Each section shows the real dashboard screen alongside the exact trigger words handled by '
            . 'handleVoiceCommand() in resources/views/welcome.blade.php.'
        ), 0, 'C');
        $pdf->SetLeftMargin(15);
        $pdf->SetRightMargin(15);
    }

    private function renderSection(FPDF $pdf, array $section): void
    {
        $pdf->AddPage();

        $pdf->SetFont('Helvetica', 'B', 17);
        $pdf->Cell(0, 10, $this->txt($section['title']), 0, 1);

        if (! empty($section['intro'])) {
            $pdf->SetFont('Helvetica', 'I', 10);
            $pdf->SetTextColor(90, 90, 90);
            $pdf->MultiCell(0, 5.5, $this->txt($section['intro']));
            $pdf->SetTextColor(0, 0, 0);
        }

        $pdf->Ln(2);

        if (! empty($section['screenshot'])) {
            $this->renderScreenshot($pdf, $section['screenshot']);
        }

        $this->renderCommandTable($pdf, $section['commands']);
    }

    private function renderScreenshot(FPDF $pdf, string $filename): void
    {
        $path = base_path(self::SCREENSHOTS_DIR . '/' . $filename);

        if (! is_file($path)) {
            $pdf->SetFont('Helvetica', 'I', 9);
            $pdf->SetTextColor(180, 60, 60);
            $pdf->Cell(0, 6, $this->txt("[screenshot missing: {$filename} - run the Dusk test first]"), 0, 1);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Ln(2);

            return;
        }

        [$pixelWidth, $pixelHeight] = getimagesize($path);
        $usableWidth = $pdf->GetPageWidth() - 30; // 15mm margins each side
        $height = $usableWidth * ($pixelHeight / $pixelWidth);

        $pdf->Image($path, 15, null, $usableWidth, $height);
        $pdf->Ln($height + 4);
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $commands  [trigger, description] pairs
     */
    private function renderCommandTable(FPDF $pdf, array $commands): void
    {
        $triggerWidth = 78;
        $descWidth = $pdf->GetPageWidth() - 30 - $triggerWidth;
        $lineHeight = 5.5;
        $pageBottom = $pdf->GetPageHeight() - 18; // matches SetAutoPageBreak's 18mm margin

        // FPDF's automatic page break can fire mid-MultiCell, which would
        // split a single table row's two cells across two pages — checked
        // manually per row instead, with the header redrawn after each
        // manual break.
        $pdf->SetAutoPageBreak(false);
        $this->renderTableHeader($pdf, $triggerWidth, $descWidth);

        foreach ($commands as [$trigger, $description]) {
            $rowHeight = $lineHeight * max(
                $this->wrappedLineCount($pdf, $trigger, $triggerWidth),
                $this->wrappedLineCount($pdf, $description, $descWidth)
            );

            if ($pdf->GetY() + $rowHeight > $pageBottom) {
                $pdf->AddPage();
                $this->renderTableHeader($pdf, $triggerWidth, $descWidth);
            }

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->MultiCell($triggerWidth, $lineHeight, $this->txt($trigger), 1, 'L');
            $pdf->SetXY($x + $triggerWidth, $y);
            $pdf->MultiCell($descWidth, $lineHeight, $this->txt($description), 1, 'L');
            $pdf->SetXY($x, $y + $rowHeight);
        }

        $pdf->Ln(4);
        $pdf->SetAutoPageBreak(true, 18);
    }

    private function renderTableHeader(FPDF $pdf, float $triggerWidth, float $descWidth): void
    {
        $pdf->SetFont('Helvetica', 'B', 9.5);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell($triggerWidth, 7, $this->txt('Trigger phrase'), 1, 0, 'L', true);
        $pdf->Cell($descWidth, 7, $this->txt('What it does'), 1, 1, 'L', true);
        $pdf->SetFont('Helvetica', '', 9.5);
    }

    private function wrappedLineCount(FPDF $pdf, string $text, float $width): int
    {
        $words = explode(' ', $text);
        $lines = 1;
        $lineWidth = 0;
        $spaceWidth = $pdf->GetStringWidth(' ');

        foreach ($words as $word) {
            $wordWidth = $pdf->GetStringWidth($word);

            if ($lineWidth + $wordWidth > $width - 2 && $lineWidth > 0) {
                $lines++;
                $lineWidth = 0;
            }

            $lineWidth += $wordWidth + $spaceWidth;
        }

        return $lines;
    }

    private function txt(string $text): string
    {
        return mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
    }

    private function sections(): array
    {
        return [
            [
                'title' => 'Global Commands',
                'screenshot' => '01-dashboard.png',
                'intro' => 'Recognized from anywhere, regardless of which popup (if any) is currently open.',
                'commands' => [
                    ['"you up"', 'Easter egg - Nova replies "For you sir, always".'],
                    ['"hi friday"', 'Greeting - Nova replies with a hello and asks how she can help.'],
                    ["\"let's begin friday\"", 'Greeting - Nova asks how she can help this time.'],
                    ['"Friday, may I ask?"', 'Starts the open-ended Q&A flow (see "Ask The System" section).'],
                    ['"what is my status" / "my status"', 'Only while the Status popup is open - Nova speaks a summary of your stats and one piece of advice.'],
                    ['"open daily quest"', 'Opens the Daily Quest list.'],
                    ['"open main quest"', 'Opens the Main Quest list.'],
                    ['"open status"', 'Opens the Hunter Status popup.'],
                    ['"open shop"', 'Opens the Shop.'],
                    ['"open task"', 'Opens the Task list.'],
                    ['"open history"', 'Opens Task History.'],
                    ['"open last conversation"', 'Opens Conversation History. Nova only replies "Open Last Conversation." - the list itself is not read aloud.'],
                ],
            ],
            [
                'title' => 'Daily Quest',
                'screenshot' => '02-daily-quest.png',
                'intro' => 'Commands available while the Daily Quest list is open.',
                'commands' => [
                    ['"quest one" .. "quest five" / "first quest" .. "fifth quest"', 'Selects the Nth quest in the list and opens its detail popup.'],
                    ['"generate"', 'Generates a new quest (shown when the list is empty).'],
                    ['"proceed" / "confirm"', 'In the quest detail popup, marks the quest as completed.'],
                    ['"cancel" / "back"', 'Closes the current popup / returns to the previous screen.'],
                ],
            ],
            [
                'title' => 'Main Quest',
                'screenshot' => '03-main-quest.png',
                'intro' => 'Main Quest generation uses a stat picker instead of spoken stat names (STR/AGI/PER/VIT/INT proved unreliable to recognize by voice).',
                'commands' => [
                    ['"one" .. "five" / "first" .. "fifth"', 'Picks which stat (STR, AGI, PER, VIT, INT in that order) the next generated Main Quest should train.'],
                    ['"generate new quest"', 'Reopens the stat picker even while a Main Quest is already active, to generate another one.'],
                    ['"proceed" / "confirm"', 'Completes the selected quest.'],
                    ['"cancel" / "back"', 'Closes the current popup / returns to the previous screen.'],
                ],
            ],
            [
                'title' => 'Task',
                'screenshot' => '04-task.png',
                'intro' => 'Commands available while the Task list is open.',
                'commands' => [
                    ['"create new task" / "new task" / "create task"', 'Opens the new-task form.'],
                    ['"task one" .. "task five" / "first task" .. "fifth task"', 'Selects the Nth task and opens its detail popup.'],
                    ['"proceed" / "confirm"', 'In the task detail popup, marks the task as completed.'],
                    ['"cancel" / "back"', 'Closes the current popup / returns to the previous screen.'],
                ],
            ],
            [
                'title' => 'Task History',
                'screenshot' => '05-task-history.png',
                'intro' => 'Completed tasks move here. Same point-and-select pattern as the Task list, plus paging.',
                'commands' => [
                    ['"next"', 'Goes to the next page of history.'],
                    ['"previous"', 'Goes to the previous page of history.'],
                    ['"task one" .. "task five"', 'Opens the Nth completed task on the current page.'],
                ],
            ],
            [
                'title' => 'Shop',
                'screenshot' => '06-shop.png',
                'intro' => 'Items are bought by clicking Buy (no voice item-picker) - voice only confirms the purchase.',
                'commands' => [
                    ['(click "Buy" on an item)', 'Nova asks "Are you sure to buy this item, sir?"'],
                    ['"yes"', 'Confirms and completes the purchase.'],
                    ['"no"', 'Cancels the purchase.'],
                ],
            ],
            [
                'title' => 'Hunter Status',
                'screenshot' => '07-status.png',
                'commands' => [
                    ['"what is my status" / "my status"', 'Nova speaks a summary of your stats plus one piece of advice.'],
                    ['"save"', 'Saves the Weight / Height / Age fields.'],
                    ['"cancel" / "back"', 'Closes the popup.'],
                ],
            ],
            [
                'title' => 'Ask The System (Q&A flow)',
                'intro' => 'Triggered globally by "Friday, may I ask?" - a multi-step spoken conversation before an answer is shown.',
                'commands' => [
                    ['"reference"', 'Nova will search the web for a real, cited answer.'],
                    ['"suggestion"', "Nova gives her own opinion directly, without searching."],
                    ['(speak the question)', 'Whatever is said next is captured as the question text.'],
                    ['"yes" (after "Is that all you need, sir?")', 'Confirms the question and starts the search / suggestion.'],
                    ['"no"', 'Nova asks "What else, sir?" so more can be added to the question.'],
                    ['"cancel"', 'Cancels the question entirely.'],
                ],
            ],
            [
                'title' => 'System Answer Popup',
                'screenshot' => '09-ask-answer.png',
                'intro' => 'Shown after "Ask The System" finds an answer, or when reopening one from Conversation History.',
                'commands' => [
                    ['"search the reference" / "open reference" / "buka referensi"', 'Opens the cited source in a new browser tab (if one exists).'],
                    ['"change bahasa"', 'Translates the shown answer into Bahasa Indonesia.'],
                    ['"change english"', 'Translates the shown answer into English.'],
                    ['"cancel" / "back"', 'Closes the popup (returns to Conversation History if that is where it was opened from).'],
                ],
            ],
            [
                'title' => 'Conversation History',
                'screenshot' => '08-conversation-history.png',
                'intro' => 'Every "Ask The System" question and answer is saved automatically and can be revisited here.',
                'commands' => [
                    ['"open last conversation"', 'Opens this list (global command - see above).'],
                    ['"next" / "previous"', 'Pages through past conversations.'],
                    ['"conversation one" .. "conversation five"', 'Reopens the Nth conversation on the current page - Nova reads the stored answer aloud again.'],
                ],
            ],
            [
                'title' => 'General Commands (most popups)',
                'commands' => [
                    ['"proceed" / "confirm"', 'Clicks whichever button is marked as the Proceed action in the current popup.'],
                    ['"cancel" / "back"', 'Clicks whichever button is marked as the Cancel action in the current popup.'],
                    ['"save"', 'Status popup only - saves Weight / Height / Age.'],
                ],
            ],
        ];
    }
}
