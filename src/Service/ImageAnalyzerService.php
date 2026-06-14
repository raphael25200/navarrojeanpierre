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
    public function analyzeImage(string $publicImageUrl, string $title = ''): array
    {
        $contextTitle = '';
        if ($title && !preg_match('/^sans\s+titre/i', trim($title))) {
            $contextTitle = "Le titre de cette œuvre est « {$title} ». Utilise-le uniquement comme aide à l'identification. Ne l'intègre dans la description ou l'aria_label que s'il désigne un lieu ou un sujet clairement visible.";
        }

        $data = [
            "model" => "gpt-4o-mini",
            "messages" => [
                [
                    "role" => "system",
                    "content" => "Tu es un assistant spécialisé dans l'analyse de tableaux. Tu génères systématiquement une description factuelle, une liste de mots-clés et un aria-label. Les réponses sont rédigées en français.

Réponds uniquement au format JSON strict :
{
\"description\": \"description concise, 1-2 phrases maximum\",
\"keywords\": [\"mot1\", \"mot2\", \"mot3\"],
\"aria_label\": \"phrase concise, maximum 120 caractères\"
}

DESCRIPTION — Règles :
- Décrire uniquement ce qui est visible.
- Commencer directement par le sujet observé.
- Ne jamais écrire \"ce tableau représente\", \"cette peinture représente\" ou \"image représentant\".
- Ne jamais interpréter les intentions du peintre ni commenter la qualité de l'œuvre.
- Ne jamais utiliser : \"met en valeur\", \"suggère\", \"évoque\", \"invite à\", \"crée une atmosphère\", \"dégage une impression\", \"symbolise\".
- Ne jamais mentionner Rembrandt, clair-obscur, réalisme, école hollandaise, expressionnisme, abstraction lyrique, picturalité, plasticité.
- Ne jamais mentionner \"Jean-Pierre Navarro\" ou \"Navarro\" SAUF si le titre contient \"autoportrait\" ou \"auto-portrait\". Dans ce cas, utiliser directement \"Jean-Pierre Navarro\" comme sujet, sans le décrire comme \"un homme\" ou \"un personnage\".

PAYSAGES :
- Décrire : relief, végétation, cours d'eau, bâtiments, ciel, éléments remarquables.
- Employer les termes précis : une tour surmontée d'une croix = clocher d'église, église comtoise si identifiable.

PORTRAITS :
- Décrire : position du personnage, vêtements, accessoires, expression visible, orientation du regard.
- Ne pas mentionner l'âge sauf si demandé.
- Ne pas inventer l'identité.
- Si le titre fournit une identité (Margie, Raphaël, etc.), l'utiliser uniquement dans les mots-clés.

ANIMAUX :
- Identifier l'espèce quand elle est reconnaissable : rouge-gorge, vache, chèvre, cheval plutôt que oiseau ou animal.

NATURES MORTES :
- Lister les objets réellement visibles. Ne pas commenter la technique ni le style.

MOTS-CLÉS :
- Produire entre 10 et 15 mots-clés classés du plus important au moins important.
- Utiliser : sujets visibles, lieux connus fournis dans le titre, éléments du paysage, espèces animales.
- Privilégier des mots individuels, éviter les phrases complètes.

ARIA-LABEL :
- Maximum 120 caractères, une seule phrase.
- Décrire brièvement le sujet principal."
                ],
                [
                    "role" => "user",
                    "content" => [
                        [
                            "type" => "text",
                            "text" => "Analyse cette peinture. {$contextTitle}"
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
