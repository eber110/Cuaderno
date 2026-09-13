<?php

namespace Base\Module;

/**
 * Cliente universal para la API de Google Gemini AI.
 * 
 * Permite generación de texto libre, resúmenes, análisis y chat
 * con modelos configurables y opciones personalizables.
 */
class GeminiModule
{
  private string $apiKey;
  private string $defaultModel;

  /**
   * Constructor del cliente Gemini.
   *
   * @param string|null $apiKey Clave de API (opcional, lee de GEMINI_API_KEY por defecto)
   * @param string $defaultModel Modelo predeterminado (default: gemini-2.5-flash)
   */
  public function __construct(?string $apiKey = null, string $defaultModel = 'gemini-2.5-flash')
  {
    $this->apiKey = $apiKey ?? ($_ENV['GEMINI_API_KEY'] ?? (defined('GEMINI_API_KEY') ? GEMINI_API_KEY : ''));
    $this->defaultModel = $defaultModel;
  }

  /**
   * Genera contenido usando la API de Gemini.
   *
   * @param string $prompt Prompt de entrada para el modelo
   * @param array $options Opciones adicionales:
   *   - model: string (default: gemini-2.5-flash)
   *   - temperature: float (0.0 a 2.0, default: 0.7)
   *   - maxOutputTokens: int (opcional)
   *   - systemInstruction: string (instrucción de sistema opcional)
   * @return string Texto generado o string vacío en caso de fallo
   */
  public function generate(string $prompt, array $options = []): string
  {
    if (empty($this->apiKey)) {
      error_log("GeminiModule: No se ha configurado GEMINI_API_KEY");
      return '';
    }

    $model = $options['model'] ?? $this->defaultModel;
    $temperature = $options['temperature'] ?? 0.7;

    $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $this->apiKey;

    $generationConfig = [
      "temperature" => $temperature
    ];

    if (isset($options['maxOutputTokens'])) {
      $generationConfig["maxOutputTokens"] = (int)$options['maxOutputTokens'];
    }

    $payload = [
      "contents" => [
        [
          "parts" => [
            ["text" => $prompt]
          ]
        ]
      ],
      "generationConfig" => $generationConfig
    ];

    if (!empty($options['systemInstruction'])) {
      $payload["systemInstruction"] = [
        "parts" => [
          ["text" => $options['systemInstruction']]
        ]
      ];
    }

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    // En desarrollo local en Windows, relajar verificación SSL si es necesario
    $isDev = defined('ENVIRONMENT') && in_array(strtolower((string)ENVIRONMENT), ['dev', 'development', 'local'], true);
    if ($isDev) {
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
      error_log("GeminiModule cURL Error: " . $curlError);
      return '';
    }

    if ($httpCode === 200 && $response) {
      $responseData = json_decode($response, true);
      if (!empty($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        return trim((string)$responseData['candidates'][0]['content']['parts'][0]['text']);
      }
    } else {
      error_log("GeminiModule API Error (HTTP {$httpCode}): " . $response);
    }

    return '';
  }

  /**
   * Método estático de conveniencia para generar contenido directamente.
   *
   * @param string $prompt
   * @param array $options
   * @return string
   */
  public static function prompt(string $prompt, array $options = []): string
  {
    $client = new self();
    return $client->generate($prompt, $options);
  }

  /**
   * Genera un resumen conciso optimizado para redes sociales.
   *
   * @param string $text Texto original a resumir
   * @param int $maxWords Cantidad máxima aproximada de palabras
   * @return string
   */
  public function generateSummary(string $text, int $maxWords = 50): string
  {
    if (empty($this->apiKey)) {
      $words = explode(' ', strip_tags($text));
      return implode(' ', array_slice($words, 0, $maxWords)) . (count($words) > $maxWords ? '...' : '');
    }

    $prompt = "Escribe un resumen con gancho magnético diseñado para redes sociales. Debe tener aproximadamente {$maxWords} palabras. No añadas introducciones ni saludos, ve directo al texto:\n\n" . strip_tags($text);

    $result = $this->generate($prompt, [
      'temperature' => 0.5,
      'model' => $this->defaultModel
    ]);

    if (!empty($result)) {
      return $result;
    }

    // Fallback si la llamada falla
    $words = explode(' ', strip_tags($text));
    return implode(' ', array_slice($words, 0, $maxWords)) . (count($words) > $maxWords ? '...' : '');
  }
}
