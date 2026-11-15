<?php

namespace App\Services;

use Anthropic\Client;
use Anthropic\Messages\JSONOutputFormat;
use Anthropic\Messages\Message;
use Anthropic\Messages\OutputConfig;

/**
 * Thin wrapper around the Anthropic SDK so every feature (quest generation,
 * quest review, voice replies, translation, ...) talks to Claude the same way.
 */
class ClaudeService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client(apiKey: (string) config('services.anthropic.key'));
    }

    public function model(): string
    {
        return config('services.anthropic.model');
    }

    /**
     * Plain text completion. Returns the trimmed text of the reply ('' if the
     * model produced no text, e.g. on a refusal).
     */
    public function text(string $system, string $prompt, int $maxTokens = 4000): string
    {
        return $this->extractText($this->message($system, $prompt, $maxTokens));
    }

    /**
     * Completion constrained to a JSON schema (structured outputs), decoded
     * to an array. Returns null if the reply is missing or not valid JSON.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>|null
     */
    public function json(string $system, string $prompt, array $schema, int $maxTokens = 8000): ?array
    {
        $message = $this->message(
            $system,
            $prompt,
            $maxTokens,
            outputConfig: OutputConfig::with(
                format: JSONOutputFormat::with(schema: $schema),
                effort: 'low',
            ),
        );

        $decoded = json_decode($this->extractText($message), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Runs a prompt with Claude's server-side web search tool. Returns the
     * answer text plus the first web citation (title/url), if any.
     *
     * @return array{answer: string, source_title: ?string, source_url: ?string}
     */
    public function searchWeb(string $system, string $prompt, int $maxTokens = 4000): array
    {
        $message = $this->message(
            $system,
            $prompt,
            $maxTokens,
            // Basic variant on purpose: the newer web_search_20260209 isn't supported
            // on Haiku models (calls fail with `invalid_tool_input`).
            tools: [['type' => 'web_search_20250305', 'name' => 'web_search', 'maxUses' => 2]],
        );

        // Text blocks the model writes before/around the search ("Let me look
        // that up...") aren't the answer: only keep the text that comes after
        // the last search result.
        $answerBlocks = [];
        foreach ($message->content as $block) {
            if ($block->type === 'web_search_tool_result') {
                $answerBlocks = [];
            } elseif ($block->type === 'text') {
                $answerBlocks[] = $block;
            }
        }

        $answer = '';
        $citation = null;
        foreach ($answerBlocks as $block) {
            $answer .= $block->text;
            foreach ($block->citations ?? [] as $candidate) {
                if ($citation === null && $candidate->type === 'web_search_result_location') {
                    $citation = $candidate;
                }
            }
        }

        return [
            'answer' => trim($answer),
            'source_title' => $citation?->title,
            'source_url' => $citation?->url,
        ];
    }

    private function message(string $system, string $prompt, int $maxTokens, ?OutputConfig $outputConfig = null, ?array $tools = null): Message
    {
        return $this->client->messages->create(
            maxTokens: $maxTokens,
            messages: [['role' => 'user', 'content' => $prompt]],
            model: $this->model(),
            // Claude Opus 5.5 always thinks and defaults to `medium` effort; these
            // are short, simple tasks so keep it light unless told otherwise.
            outputConfig: $outputConfig ?? OutputConfig::with(effort: 'low'),
            system: $system,
            tools: $tools,
        );
    }

    private function extractText(Message $message): string
    {
        $text = '';

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        return trim($text);
    }
}
