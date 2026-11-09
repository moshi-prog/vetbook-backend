<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    /**
     * Build the system prompt based on the pet owner's context.
     */
    public static function buildSystemPrompt($user): string
    {
        $prompt = "You are VetBook Assistant, an AI assistant for BK Petaholic Animal Clinic, NOT a licensed veterinarian. "
            . "BK Petaholic is a veterinary clinic in Batangas, Philippines with two branches: "
            . "Mabini Branch (142 Mabini St., Poblacion) and Batangas City Branch "
            . "(N.F. Almario Building, Kumintang Ilaya).\n\n"
            . "Clinic hours: Monday to Saturday 9:00 AM - 6:00 PM, Sunday 10:00 AM - 4:00 PM.\n\n"
            . "STRICT CLINICAL SAFETY RULES:\n"
            . "- You are NOT a licensed veterinarian. NEVER give medical diagnoses, drug dosages, or specific treatment instructions.\n"
            . "- NEVER prescribe, recommend, or suggest medications, dosages, or home remedies involving drugs or chemicals.\n"
            . "- If asked about illness or symptoms, provide only general comfort observations and brief wellness tips (e.g., keep the pet comfortable, hydrated, quiet, and do not feed if vomiting). Keep responses concise and easy to read (2-3 short paragraphs maximum).\n"
            . "- For severe signs (persistent vomiting, lethargy, difficulty breathing, trauma, bleeding, poisoning), urge the owner to seek immediate veterinary care.\n\n"
            . "MANDATORY CLOSING (MUST BE INCLUDED AT THE END OF EVERY ANSWER):\n"
            . "- At the end of EVERY answer, warmly advise the owner to bring their pet to BK Petaholic Animal Clinic (Mabini Branch or Batangas City Branch) for a professional examination, and remind them they can book an appointment right here in the VetBook app.\n\n"
            . "GENERAL CAPABILITIES:\n"
            . "- Answer questions about clinic services, branch locations, and operating hours.\n"
            . "- Assist pet owners with general care knowledge while maintaining a warm, empathetic, and professional tone.\n";

        if ($user) {
            $prompt .= "\nYou are speaking with {$user->name}.";

            $pets = $user->pets ?? collect();
            if ($pets->isNotEmpty()) {
                $petList = $pets->map(function ($p) {
                    return "{$p->name} ({$p->species}" . ($p->breed ? ", {$p->breed}" : "") . ")";
                })->join(", ");
                $prompt .= " Their registered pets are: {$petList}. "
                    . "Reference these pets when relevant, but do not pretend to know their "
                    . "medical history in detail.";
            }
        }

        return $prompt;
    }

    /**
     * Send a message to Gemini and get a response.
     *
     * @param string $message  The user's message
     * @param array  $history  Previous messages in Gemini format:
     *                         [['role' => 'user', 'parts' => [['text' => '...']]], ...]
     * @param string $systemPrompt
     * @return array ['success' => bool, 'reply' => string|null, 'error' => string|null]
     */
    public static function send(string $message, array $history = [], string $systemPrompt = ''): array
    {
        $apiKey = config('services.gemini.api_key');
        $configuredModel = config('services.gemini.model');
        $baseUrl = config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta/models');
        $timeout = config('services.gemini.timeout', 20);

        if (empty($apiKey)) {
            return ['success' => false, 'reply' => null, 'error' => 'Chatbot not configured'];
        }

        // Build the contents array
        $contents = $history;
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]],
        ];

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1200,
                'topP' => 0.95,
            ],
        ];

        if (!empty($systemPrompt)) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $systemPrompt]],
            ];
        }

        // Active models to try in order
        $modelsToTry = array_values(array_unique(array_filter([
            'gemini-flash-lite-latest',
            'gemini-3.5-flash',
            $configuredModel,
            'gemini-3.5-flash-lite',
            'gemini-3.8-flash',
        ])));

        $lastError = null;

        foreach ($modelsToTry as $m) {
            try {
                $url = "{$baseUrl}/{$m}:generateContent?key={$apiKey}";

                $response = Http::timeout($timeout)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, $payload);

                if (!$response->successful()) {
                    Log::warning("Gemini API error with model {$m}", [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    $lastError = "Model {$m} returned HTTP " . $response->status();
                    continue;
                }

                $body = $response->json();
                $reply = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if (!$reply) {
                    $lastError = "Model {$m} returned empty response";
                    continue;
                }

                return ['success' => true, 'reply' => trim($reply), 'error' => null];
            } catch (\Throwable $e) {
                Log::warning("Gemini exception with model {$m}", ['message' => $e->getMessage()]);
                $lastError = $e->getMessage();
            }
        }

        return [
            'success' => false,
            'reply' => null,
            'error' => 'AI service unavailable: ' . ($lastError ?? 'unknown error'),
        ];
    }
}
