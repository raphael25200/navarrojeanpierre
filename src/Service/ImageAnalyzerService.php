<?php
// src/Service/ImageAnalyzerService.php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ImageAnalyzerService
{
    private string $openaiToken;
    private HttpClientInterface $client;

    public function __construct(string $openaiToken, HttpClientInterface $client)
    {
        $this->openaiToken = $openaiToken;
        $this->client = $client;
    }

    /**
     * Analyse une image et renvoie description + keywords + aria_label
     */
    public function analyzeImage(string $publicImageUrl): array
    {
        $data = [
            "model" => "gpt-4o-mini",
            "messages" => [
                [
                    "role" => "system",
                    "content" => "Tu es un assistant qui analyse des peintures et répond uniquement en français.
                        Fournis toujours ta réponse au format JSON strict :
                        {
                        \"description\": \"une phrase descriptive\",
                        \"keywords\": [\"mot1\", \"mot2\", \"mot3\"],
                        \"aria_label\": \"une phrase concise pour aria-label\"
                        }"
                ],
                [
                    "role" => "user",
                    "content" => [
                        [
                            "type" => "text",
                            "text" => "Décris cette peinture, propose 15 mots-clés et une phrase concise pour aria-label."
                        ],
                        [
                            "type" => "image_url",
                            "image_url" => ["url" => $publicImageUrl]
                        ]
                    ]
                ]
            ],
            "max_tokens" => 1000
        ];

        try {
            $response = $this->client->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->openaiToken
                ],
                'json' => $data
            ]);

            $result = $response->toArray();

            if (isset($result['choices'][0]['message']['content'])) {
                $content = $result['choices'][0]['message']['content'];

                // Extraire le JSON même si l'IA ajoute des backticks ```json ou du texte
                if (preg_match('/\{.*\}/s', $content, $matches)) {
                    $jsonString = $matches[0];
                    $parsed = json_decode($jsonString, true);

                    if (json_last_error() === JSON_ERROR_NONE) {
                        return [
                            'description' => $parsed['description'] ?? '',
                            'keywords'   => $parsed['keywords'] ?? [],
                            'aria_label' => $parsed['aria_label'] ?? ''
                        ];
                    }
                }

                // fallback si JSON invalide
                // fallback si le JSON est invalide
                return [
                    'description' => $content,
                    'keywords' => [],
                    'aria_label' => ''
                ];
            }
        } catch (\Exception $e) {
            return [
                'description' => '',
                'keywords' => [],
                'aria_label' => '',
                'error' => $e->getMessage()
            ];
        }

        return [
            'description' => '',
            'keywords' => [],
            'aria_label' => ''
        ];
    }
}
