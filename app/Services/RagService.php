<?php

namespace App\Services;

use OpenAI;
use Illuminate\Support\Facades\Log;

class RagService
{
    protected $openai;
    protected $embeddingModel = 'text-embedding-ada-002';
    protected $llmModel = 'gpt-4o-mini';

    public function __construct()
    {
        $this->openai = OpenAI::client(config('services.openai.key'));
    }

    public function getRelevantContext(string $query, int $topK = 3)
    {
        // Generate embedding for the query
        $embedding = $this->generateEmbedding($query);
        
        // Vector similarity search (pseudo-code - replace with your vector DB implementation)
        $context = $this->vectorSearch($embedding, $topK);
        
        return $context;
    }

    public function generateResponse(string $query, array $context)
    {
        $prompt = $this->buildPrompt($query, $context);
        
        $response = $this->openai->chat()->create([
            'model' => $this->llmModel,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => $prompt]
            ]
        ]);
        
        return $response->choices[0]->message->content;
    }

    protected function generateEmbedding(string $text)
    {
        $response = $this->openai->embeddings()->create([
            'model' => $this->embeddingModel,
            'input' => $text
        ]);
        
        return $response->embeddings[0]->embedding;
    }

    protected function buildPrompt(string $query, array $context)
    {
        $contextText = implode("\n\n", $context);
        
        return "Answer the question based on the following context:\n\n" .
               $contextText . "\n\nQuestion: " . $query;
    }
}