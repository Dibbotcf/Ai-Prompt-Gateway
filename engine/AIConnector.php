<?php
/**
 * AI Prompt Security Gateway — AI Connector
 * Connects to external AI models (Gemini, OpenAI, Groq, or Simulated)
 */

require_once __DIR__ . '/../config/database.php';

class AIConnector {
    private $pdo;
    private $settings = [];

    // Provider configurations
    private $providers = [
        'gemini' => [
            'name' => 'Google Gemini',
            'icon' => 'fa-google',
            'color' => '#4285f4',
            'url' => 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent?key={key}',
        ],
        'openai' => [
            'name' => 'OpenAI GPT',
            'icon' => 'fa-robot',
            'color' => '#10a37f',
            'url' => 'https://api.openai.com/v1/chat/completions',
        ],
        'groq' => [
            'name' => 'Groq',
            'icon' => 'fa-bolt',
            'color' => '#f55036',
            'url' => 'https://api.groq.com/openai/v1/chat/completions',
        ],
        'simulated' => [
            'name' => 'Simulated AI',
            'icon' => 'fa-flask',
            'color' => '#8b5cf6',
            'url' => null,
        ],
    ];

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
        $this->loadSettings();
    }

    private function loadSettings() {
        try {
            $stmt = $this->pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'ai_%'");
            foreach ($stmt->fetchAll() as $row) {
                $this->settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            // Use defaults
        }
    }

    /**
     * Get list of available providers with their configuration status
     */
    public function getAvailableProviders() {
        $result = [];
        foreach ($this->providers as $key => $provider) {
            $configured = false;
            if ($key === 'simulated') {
                $configured = true;
            } else {
                $apiKey = $this->settings["ai_{$key}_api_key"] ?? '';
                $configured = !empty($apiKey);
            }
            $result[] = [
                'id' => $key,
                'name' => $provider['name'],
                'icon' => $provider['icon'],
                'color' => $provider['color'],
                'configured' => $configured,
                'model' => $this->settings["ai_{$key}_model"] ?? '',
            ];
        }
        return $result;
    }

    /**
     * Send prompt to the specified AI provider and return the response
     */
    public function sendPrompt($prompt, $provider = 'simulated', $systemPrompt = null) {
        $startTime = microtime(true);

        if ($provider === 'simulated') {
            return $this->simulateResponse($prompt, $startTime);
        }

        if (!isset($this->providers[$provider])) {
            return ['error' => true, 'message' => "Unknown provider: $provider"];
        }

        $apiKey = $this->settings["ai_{$provider}_api_key"] ?? '';
        if (empty($apiKey)) {
            return ['error' => true, 'message' => "No API key configured for $provider. Go to Settings to add one."];
        }

        $model = $this->settings["ai_{$provider}_model"] ?? '';

        try {
            switch ($provider) {
                case 'gemini':
                    return $this->callGemini($prompt, $apiKey, $model, $systemPrompt, $startTime);
                case 'openai':
                    return $this->callOpenAI($prompt, $apiKey, $model, $systemPrompt, $startTime);
                case 'groq':
                    return $this->callGroq($prompt, $apiKey, $model, $systemPrompt, $startTime);
                default:
                    return ['error' => true, 'message' => "Provider $provider not supported"];
            }
        } catch (Exception $e) {
            return [
                'error' => true,
                'message' => "AI call failed: " . $e->getMessage(),
                'response_time_ms' => intval((microtime(true) - $startTime) * 1000),
            ];
        }
    }

    /**
     * Google Gemini API
     */
    private function callGemini($prompt, $apiKey, $model, $systemPrompt, $startTime) {
        $model = $model ?: 'gemini-2.0-flash';
        $url = str_replace(['{model}', '{key}'], [$model, $apiKey], $this->providers['gemini']['url']);

        $body = ['contents' => [['parts' => [['text' => $prompt]]]]];
        if ($systemPrompt) {
            $body['systemInstruction'] = ['parts' => [['text' => $systemPrompt]]];
        }

        $response = $this->httpPost($url, $body);

        if (isset($response['error'])) {
            return ['error' => true, 'message' => $response['error']['message'] ?? 'Gemini API error'];
        }

        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? 'No response generated';
        return [
            'error' => false,
            'response' => $text,
            'model' => $model,
            'provider' => 'gemini',
            'response_time_ms' => intval((microtime(true) - $startTime) * 1000),
        ];
    }

    /**
     * OpenAI API
     */
    private function callOpenAI($prompt, $apiKey, $model, $systemPrompt, $startTime) {
        $model = $model ?: 'gpt-3.5-turbo';
        $url = $this->providers['openai']['url'];

        $messages = [];
        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $body = ['model' => $model, 'messages' => $messages, 'max_tokens' => 1024];
        $response = $this->httpPost($url, $body, ['Authorization: Bearer ' . $apiKey]);

        if (isset($response['error'])) {
            return ['error' => true, 'message' => $response['error']['message'] ?? 'OpenAI API error'];
        }

        $text = $response['choices'][0]['message']['content'] ?? 'No response generated';
        return [
            'error' => false,
            'response' => $text,
            'model' => $model,
            'provider' => 'openai',
            'response_time_ms' => intval((microtime(true) - $startTime) * 1000),
        ];
    }

    /**
     * Groq API (OpenAI-compatible)
     */
    private function callGroq($prompt, $apiKey, $model, $systemPrompt, $startTime) {
        $model = $model ?: 'llama3-8b-8192';
        $url = $this->providers['groq']['url'];

        $messages = [];
        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $body = ['model' => $model, 'messages' => $messages, 'max_tokens' => 1024];
        $response = $this->httpPost($url, $body, ['Authorization: Bearer ' . $apiKey]);

        if (isset($response['error'])) {
            return ['error' => true, 'message' => $response['error']['message'] ?? 'Groq API error'];
        }

        $text = $response['choices'][0]['message']['content'] ?? 'No response generated';
        return [
            'error' => false,
            'response' => $text,
            'model' => $model,
            'provider' => 'groq',
            'response_time_ms' => intval((microtime(true) - $startTime) * 1000),
        ];
    }

    /**
     * Simulated AI response for demo/testing
     */
    private function simulateResponse($prompt, $startTime) {
        $responses = [
            "Thank you for your question! Based on my analysis, I can provide the following insights:\n\nThis is a simulated AI response generated by the PromptGuard test environment. In a production setup, this would be replaced by an actual AI model response from your configured provider (Gemini, OpenAI, or Groq).\n\nThe security gateway has determined that your prompt is safe to process and has forwarded it to this simulated endpoint.",
            "I'd be happy to help with that! Here's my simulated response:\n\nThe PromptGuard Security Gateway has analyzed your prompt and cleared it for processing. This demonstration response shows how a real AI model would respond after the security check passes.\n\nTo use a real AI model, configure your API keys in the Settings page.",
            "Great question! Let me address that:\n\nThis is a demo response from the Simulated AI provider. When you configure a real AI provider (Google Gemini, OpenAI GPT, or Groq), the destination model will generate contextual responses based on your actual prompt.\n\nThe security gateway ensured your prompt was safe before it reached this point.",
        ];

        usleep(rand(200000, 800000)); // Simulate 200-800ms response time

        return [
            'error' => false,
            'response' => $responses[array_rand($responses)],
            'model' => 'simulated-v1',
            'provider' => 'simulated',
            'response_time_ms' => intval((microtime(true) - $startTime) * 1000),
        ];
    }

    /**
     * HTTP POST helper using file_get_contents (no cURL needed)
     */
    private function httpPost($url, $data, $extraHeaders = []) {
        $headers = array_merge([
            'Content-Type: application/json',
        ], $extraHeaders);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => json_encode($data),
                'timeout' => 30,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new Exception("Failed to connect to AI provider. Check your internet connection and API key.");
        }

        $decoded = json_decode($response, true);
        if ($decoded === null) {
            throw new Exception("Invalid response from AI provider");
        }

        return $decoded;
    }
}
