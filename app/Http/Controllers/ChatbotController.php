<?php

namespace App\Http\Controllers;

use App\Models\ClinicSetting;
use App\Services\ChatbotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatbotController extends Controller
{
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array',
            'history.*.role' => 'required_with:history|string|in:user,model',
            'history.*.parts' => 'required_with:history|array',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = auth()->user();
        $systemPrompt = ChatbotService::buildSystemPrompt($user);

        $result = ChatbotService::send(
            $request->message,
            $request->history ?? [],
            $systemPrompt
        );

        if (!$result['success']) {
            $clinicPhone = ClinicSetting::get('clinic_phone', '0917-123-4567');
            return response()->json([
                'reply' => "I'm having trouble connecting right now. Please try again in a moment, or call the clinic directly at {$clinicPhone}.",
                'error' => $result['error'],
            ], 200);
        }

        return response()->json([
            'reply' => $result['reply'],
        ]);
    }
}
