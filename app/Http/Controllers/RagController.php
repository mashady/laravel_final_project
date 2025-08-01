<?php

namespace App\Http\Controllers;

use App\Services\RagService;
use Illuminate\Http\Request;
use App\Models\Document;
use Illuminate\Support\Facades\Http;
use App\Models\ChatHistory;
use Illuminate\Support\Facades\Auth;
class RagController extends Controller
{

    public function query(Request $request)
{
    $request->validate([
        'question' => 'required|string',
    ]);

    $question = trim($request->question);
    $userId = Auth::id();

    // Search for related documents
    $documents = Document::searchByContent($question)->limit(3)->get();

    // If no documents found, return early
    if ($documents->isEmpty()) {
        return response()->json([
            'answer' => 'No data provided for this question yet. Please check back later.',
            'source' => null,
        ], 200, ['X-RAG-Source' => 'None']);
    }

    // ✅ FIX: Build context from documents
    $context = $documents->pluck('content')->implode("\n---\n");

    // Prepare RAG-style prompt
    $inputText = <<<TEXT
Based on the following context, answer the user's question.

Context:
$context

Question:
$question
TEXT;

    try {
        $response = Http::post(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . config('services.gemini.key'),
            [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $inputText]
                        ]
                    ]
                ]
            ]
        );

        $data = $response->json();

        $answer = data_get($data, 'candidates.0.content.parts.0.text');

        if (!$answer) {
            return response()->json([
                'answer' => 'Failed to get a valid response from Gemini.',
                'error' => $data,
            ], 500);
        }

        // Save to history if authenticated
        if ($userId) {
            ChatHistory::create([
                'user_id' => $userId,
                'question' => $question,
                'answer' => $answer,
                'sources' => $documents->pluck('title')->implode(', '),
            ]);
        }

        return response()->json([
            'answer' => $answer,
            'source' => 'Gemini',
        ], 200, ['X-RAG-Source' => 'Gemini']);

    } catch (\Exception $e) {
        return response()->json([
            'answer' => 'Failed to process your request.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    

    public function history(Request $request)
    {
        $userId = Auth::id();

        return ChatHistory::where('user_id', $userId)
                          ->latest()
                          ->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        Document::create($request->only('title', 'content'));

        return response()->json(['message' => 'Document uploaded successfully']);
    }
}
