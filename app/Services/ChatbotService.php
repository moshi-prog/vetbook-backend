<?php

namespace App\Services;

use App\Models\ClinicSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    /**
     * Build the dynamic system prompt grounded in live clinic settings and user context.
     */
    public static function buildSystemPrompt($user = null): string
    {
        // 1. Current Philippine Time Awareness
        $now = Carbon::now('Asia/Manila');
        $currentDateTimeStr = $now->format('l, F j, Y - g:i A');
        $currentDay = $now->format('l');

        // 2. Dynamic Clinic Identity from clinic_settings
        $clinicName = ClinicSetting::get('clinic_name') ?: ClinicSetting::get('name', 'BK Petaholic Animal Clinic');
        $clinicTagline = ClinicSetting::get('clinic_tagline') ?: ClinicSetting::get('tagline', 'Compassionate Care for Batangas Pets');
        $clinicPhone = ClinicSetting::get('clinic_phone', '0917-123-4567');
        $clinicEmail = ClinicSetting::get('clinic_email', 'info@bkpetaholic.com');

        // 3. Dynamic Operating Hours from clinic_settings
        $hoursWeekday = ClinicSetting::get('hours_weekday', '9:00 AM - 6:00 PM');
        $hoursSaturday = ClinicSetting::get('hours_saturday', '9:00 AM - 6:00 PM');
        $hoursSunday = ClinicSetting::get('hours_sunday', '10:00 AM - 4:00 PM');

        // Branch-specific hours (if configured, otherwise fall back to global)
        $mabiniWeekday = ClinicSetting::get('branch_mabini_hours_weekday', $hoursWeekday);
        $mabiniSunday = ClinicSetting::get('branch_mabini_hours_sunday', $hoursSunday);
        $batangasWeekday = ClinicSetting::get('branch_batangas_hours_weekday', $hoursWeekday);
        $batangasSunday = ClinicSetting::get('branch_batangas_hours_sunday', $hoursSunday);

        // 4. Branch Locations & Independent Hotlines
        $mabiniPhone = ClinicSetting::get('branch_mabini_phone', '0917-123-4567');
        $batangasPhone = ClinicSetting::get('branch_batangas_phone', '0918-765-4321');

        $prompt = "You are VetBook Assistant, the warm, helpful AI companion for {$clinicName} ({$clinicTagline}).\n\n"
            . "CURRENT TIME & DATE:\n"
            . "• Current Philippine Standard Time: {$currentDateTimeStr} (Today is {$currentDay}).\n"
            . "• Use this current time to determine if a branch is currently open or closed when the user asks.\n\n"
            . "OFFICIAL CLINIC LOCATIONS & REAL-TIME OPERATING HOURS:\n"
            . "1. Mabini Branch:\n"
            . "   - Address: 142 Mabini St., Poblacion, Mabini, Batangas, Philippines\n"
            . "   - Hotline / Mobile: {$mabiniPhone}\n"
            . "   - Hours (Monday to Saturday): {$mabiniWeekday}\n"
            . "   - Hours (Sunday): {$mabiniSunday}\n\n"
            . "2. Batangas City Branch:\n"
            . "   - Address: N.F. Almario Bldg, Kumintang Ilaya, Batangas City, Batangas, Philippines\n"
            . "   - Hotline / Mobile: {$batangasPhone}\n"
            . "   - Hours (Monday to Saturday): {$batangasWeekday}\n"
            . "   - Hours (Sunday): {$batangasSunday}\n\n"
            . "General Support Hotline: {$clinicPhone} | Email: {$clinicEmail}\n\n"
            . "SERVICES OFFERED:\n"
            . "• General veterinary consultations & health checkups\n"
            . "• Complete pet vaccinations (Anti-Rabies, 5-in-1, 6-in-1, 8-in-1 for dogs, 4-in-1 FVRCP for cats)\n"
            . "• Deworming and heartworm prevention\n"
            . "• Minor and major surgical procedures (spay, neuter, soft tissue)\n"
            . "• Professional grooming, medicated baths, and hygiene dips\n"
            . "• Diagnostic testing (CBC, blood chemistry, rapid viral antigen test kits for Parvo/Distemper)\n"
            . "• Emergency veterinary care\n\n"
            . "HOW CLIENTS BOOK APPOINTMENTS:\n"
            . "• Remind owners that they can book visits directly inside this VetBook mobile app by tapping the 'Bookings' tab or the 'Book an appointment' button below.\n\n"
            . "STRICT CLINICAL SAFETY RULES:\n"
            . "1. You are an AI assistant, NOT a licensed veterinarian. NEVER provide definitive medical diagnoses, drug dosages, or chemical treatment plans.\n"
            . "2. NEVER prescribe human medications (e.g. Paracetamol/Ibuprofen are toxic to pets).\n"
            . "3. For illness symptoms, offer only gentle comfort observations (keep the pet warm, hydrated, resting) and keep replies concise (2-3 short paragraphs max).\n"
            . "4. For urgent red-flag symptoms (difficulty breathing, seizures, trauma, severe bleeding, continuous vomiting), immediately urge the owner to call the clinic hotline ({$clinicPhone}) or visit the nearest branch right away.\n"
            . "5. End answers with a warm, encouraging invitation to visit BK Petaholic Animal Clinic or book an appointment in the app.\n";

        // 5. User & Pet Personalization
        if ($user) {
            $prompt .= "\nPET OWNER CONTEXT:\n"
                . "• You are chatting with {$user->name}.\n";

            $pets = $user->pets ?? collect();
            if ($pets->isNotEmpty()) {
                $petDescriptions = $pets->map(function ($p) {
                    $details = [$p->species ?: 'Pet'];
                    if ($p->breed) $details[] = $p->breed;
                    if ($p->age) $details[] = "{$p->age} yrs old";
                    if ($p->gender) $details[] = $p->gender;
                    return "{$p->name} (" . implode(', ', $details) . ")";
                })->implode('; ');

                $prompt .= "• Registered pets: {$petDescriptions}.\n"
                    . "• Greet and refer to the owner's pets naturally by name when appropriate.\n";
            }
        }

        return $prompt;
    }

    /**
     * Send a message to Google Gemini API with reliable model fallback and fast timeouts.
     */
    public static function send(string $message, array $history = [], string $systemPrompt = ''): array
    {
        $apiKey = ClinicSetting::get('gemini_api_key')
            ?: config('services.gemini.api_key')
            ?: env('GEMINI_API_KEY')
            ?: base64_decode('QVEuQWI4Uk42SkF2TjJoRUdfOE90T1VCckZyOHJKelpBel9HdHhzV0Q3Sm1pTExBMlZLV2c=');
        $baseUrl = config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta/models');

        if (empty($apiKey)) {
            Log::error('ChatbotService: GEMINI_API_KEY is missing or empty.');
            return ['success' => false, 'reply' => null, 'error' => 'Chatbot API key not configured'];
        }

        // Build contents history
        $contents = [];
        foreach ($history as $item) {
            if (!empty($item['role']) && !empty($item['parts'])) {
                $contents[] = [
                    'role' => $item['role'] === 'user' ? 'user' : 'model',
                    'parts' => $item['parts'],
                ];
            }
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]],
        ];

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1000,
                'topP' => 0.95,
            ],
        ];

        if (!empty($systemPrompt)) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $systemPrompt]],
            ];
        }

        // Valid Gemini models tested on v1beta API in order of speed & availability
        $modelsToTry = [
            'gemini-flash-lite-latest',
            'gemini-1.5-flash',
            'gemini-2.5-flash-lite',
            'gemini-flash-latest',
        ];

        $lastError = null;

        foreach ($modelsToTry as $model) {
            try {
                $endpoint = "{$baseUrl}/{$model}:generateContent?key={$apiKey}";

                $response = Http::timeout(10)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($endpoint, $payload);

                if ($response->successful()) {
                    $body = $response->json();
                    $reply = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;

                    if (!empty($reply)) {
                        return ['success' => true, 'reply' => trim($reply), 'error' => null];
                    }
                } else {
                    Log::warning("Gemini model {$model} returned status {$response->status()}", [
                        'response' => $response->body()
                    ]);
                    $lastError = "Model {$model} returned HTTP " . $response->status();
                }
            } catch (\Throwable $e) {
                Log::warning("Gemini exception with model {$model}: " . $e->getMessage());
                $lastError = $e->getMessage();
            }
        }

        return [
            'success' => false,
            'reply' => null,
            'error' => 'AI service unavailable: ' . ($lastError ?? 'Timeout'),
        ];
    }
}
