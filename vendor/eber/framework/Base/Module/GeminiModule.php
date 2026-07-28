<?php

namespace Base\Module;

class GeminiModule
{

  private string $apiKey;
  private string $apiUrl;

  public function __construct()
  {

    // Intentamos obtener la api key de las variables de entorno o constantes
    $this->apiKey = $_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY') ?: (defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '');
    $this->apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $this->apiKey;
  }

  public function generateSummary(string $text, int $maxWords = 50): string
  {

    if (empty($this->apiKey)) {
      $words = explode(' ', strip_tags($text));
      return implode(' ', array_slice($words, 0, $maxWords)) . (count($words) > $maxWords ? '...' : '');
    }

    $prompt = "Escribe un resumen con gancho magnético diseñado para redes sociales. Debe tener aproximadamente {$maxWords} palabras. No añadas introducciones ni saludos, ve directo al texto:\n\n" . strip_tags($text);

    $data = [
      "contents" => [
        [
          "parts" => [
            ["text" => $prompt]
          ]
        ]
      ],
      "generationConfig" => [
        "temperature" => 0.5
      ]
    ];

    $ch = curl_init($this->apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    // Si estás en entorno de desarrollo local (Windows/XAMPP/Laragon), a veces los certificados SSL fallan
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'DEV') {
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
      
      $responseData = json_decode($response, true);
      
      if (!empty($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        
        $summary = $responseData['candidates'][0]['content']['parts'][0]['text'];
        
        return trim($summary);
      }
    }

    // Fallback si la API falla
    $words = explode(' ', strip_tags($text));
    return implode(' ', array_slice($words, 0, $maxWords)) . (count($words) > $maxWords ? '...' : '');
  }

}
