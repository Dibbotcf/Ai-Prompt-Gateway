<?php
/**
 * AI Prompt Security Gateway — Sanitizer
 * Strips, masks, or transforms unsafe content in prompts
 */

class Sanitizer {

    /**
     * Sanitization rules: pattern => replacement description
     */
    private $piiPatterns = [
        // SSN
        [
            'pattern' => '/\b\d{3}-\d{2}-\d{4}\b/',
            'replacement' => '[REDACTED-SSN]',
            'type' => 'ssn',
            'description' => 'Social Security Number'
        ],
        // Credit card (Visa, MC, Amex, Discover)
        [
            'pattern' => '/\b(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|3[47][0-9]{13}|6(?:011|5[0-9]{2})[0-9]{12})\b/',
            'replacement' => '[REDACTED-CC]',
            'type' => 'credit_card',
            'description' => 'Credit Card Number'
        ],
        // Email
        [
            'pattern' => '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/',
            'replacement' => '[REDACTED-EMAIL]',
            'type' => 'email',
            'description' => 'Email Address'
        ],
        // Phone (US formats)
        [
            'pattern' => '/\b(?:\+?1?[-.\s]?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4})\b/',
            'replacement' => '[REDACTED-PHONE]',
            'type' => 'phone',
            'description' => 'Phone Number'
        ],
        // IP Address
        [
            'pattern' => '/\b(?:\d{1,3}\.){3}\d{1,3}\b/',
            'replacement' => '[REDACTED-IP]',
            'type' => 'ip_address',
            'description' => 'IP Address'
        ],
    ];

    /**
     * Injection patterns to strip
     */
    private $injectionPatterns = [
        [
            'pattern' => '/\[SYSTEM\].*?(?:\n|$)/i',
            'replacement' => '[REMOVED-INJECTION]',
            'type' => 'system_injection',
            'description' => 'System tag injection'
        ],
        [
            'pattern' => '/\[ADMIN\].*?(?:\n|$)/i',
            'replacement' => '[REMOVED-INJECTION]',
            'type' => 'admin_injection',
            'description' => 'Admin tag injection'
        ],
        [
            'pattern' => '/\[DEVELOPER\].*?(?:\n|$)/i',
            'replacement' => '[REMOVED-INJECTION]',
            'type' => 'dev_injection',
            'description' => 'Developer tag injection'
        ],
        [
            'pattern' => '/\[OVERRIDE\].*?(?:\n|$)/i',
            'replacement' => '[REMOVED-INJECTION]',
            'type' => 'override_injection',
            'description' => 'Override tag injection'
        ],
        [
            'pattern' => '/<<SYS>>.*?<<\/SYS>>/is',
            'replacement' => '[REMOVED-INJECTION]',
            'type' => 'llama_injection',
            'description' => 'LLaMA system tag injection'
        ],
        [
            'pattern' => '/<\|im_start\|>.*?<\|im_end\|>/is',
            'replacement' => '[REMOVED-INJECTION]',
            'type' => 'chatml_injection',
            'description' => 'ChatML tag injection'
        ],
        [
            'pattern' => '/<\|endoftext\|>/i',
            'replacement' => '',
            'type' => 'token_injection',
            'description' => 'End-of-text token injection'
        ],
    ];

    /**
     * Sanitize a prompt by removing PII and injection patterns
     * 
     * @param string $prompt The raw prompt text
     * @return array Sanitized text and list of transformations
     */
    public function sanitize($prompt) {
        $sanitized = $prompt;
        $transformations = [];

        // Apply PII sanitization
        foreach ($this->piiPatterns as $rule) {
            if (preg_match($rule['pattern'], $sanitized, $matches)) {
                $transformations[] = [
                    'type' => $rule['type'],
                    'description' => $rule['description'],
                    'original' => $matches[0],
                    'replacement' => $rule['replacement'],
                ];
                $sanitized = preg_replace($rule['pattern'], $rule['replacement'], $sanitized);
            }
        }

        // Apply injection pattern removal
        foreach ($this->injectionPatterns as $rule) {
            if (preg_match($rule['pattern'], $sanitized, $matches)) {
                $transformations[] = [
                    'type' => $rule['type'],
                    'description' => $rule['description'],
                    'original' => $matches[0],
                    'replacement' => $rule['replacement'],
                ];
                $sanitized = preg_replace($rule['pattern'], $rule['replacement'], $sanitized);
            }
        }

        // Trim excessive whitespace
        $sanitized = preg_replace('/\s{3,}/', '  ', $sanitized);
        $sanitized = trim($sanitized);

        return [
            'sanitized_text' => $sanitized,
            'transformations' => $transformations,
            'was_modified' => $sanitized !== $prompt,
            'transformations_count' => count($transformations),
        ];
    }
}
