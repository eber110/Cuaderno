<?php

namespace Base\Module;

/**
 * Módulo de validación de datos.
 * 
 * Validadores disponibles: email, max_length, min_length, min_string, 
 * min_capital, min_char, min_number, space
 * 
 * ============================================================================
 * INSTRUCCIONES DE USO Y EJEMPLOS:
 * ============================================================================
 * 
 * El método principal es `camp($content, ['regla1', 'regla2' => valor])`.
 * Devuelve un array con el resultado:
 *   - Si pasa: `[0 => true, 1 => $valor_validado]`
 *   - Si falla: `[0 => false, 1 => "nombre_regla_que_fallo"]`
 * 
 * ----------------------------------------------------------------------------
 * 1. Formatos aceptados para definir las reglas:
 * ----------------------------------------------------------------------------
 * A. Formato Asociativo (Recomendado):
 *    ValidatorModule::camp($input, ['min_length' => 8, 'space' => false]);
 * 
 * B. Formato de String con Espacio:
 *    ValidatorModule::camp($input, ['min_length 8', 'space false']);
 * 
 * C. Formato Simple (sin parámetros):
 *    ValidatorModule::camp($input, ['email']);
 * 
 * ----------------------------------------------------------------------------
 * 2. Detalle de Reglas de Validación:
 * ----------------------------------------------------------------------------
 * - 'email'       : Verifica si es un correo electrónico válido.
 * - 'max_length'  : Longitud máxima de caracteres (ej: 'max_length' => 50).
 * - 'min_length'  : Longitud mínima de caracteres (ej: 'min_length' => 8).
 * - 'min_string'  : Cantidad mínima de letras del abecedario (ej: 'min_string' => 3).
 * - 'min_capital' : Cantidad mínima de letras mayúsculas (ej: 'min_capital' => 1).
 * - 'min_char'    : Cantidad mínima de caracteres especiales de este grupo: [@#$%&_]
 * - 'min_number'  : Cantidad mínima de dígitos numéricos (ej: 'min_number' => 2).
 * - 'space'       : Si es `false`, valida que NO tenga espacios. Si es `true`,
 *                   valida que SÍ contenga espacios (por defecto es `true`).
 * 
 * ----------------------------------------------------------------------------
 * 3. Ejemplo práctico en un Controlador (Registro de Usuario):
 * ----------------------------------------------------------------------------
 * ```php
 * use Base\Module\ValidatorModule;
 * 
 * $email = $_POST['email'] ?? '';
 * $password = $_POST['pass'] ?? '';
 * 
 * // Validar Email
 * $valEmail = ValidatorModule::camp($email, ['email', 'max_length' => 100]);
 * if (!$valEmail[0]) {
 *     // Falló la validación. $valEmail[1] contiene la regla que falló
 *     echo "El email es inválido o excede el largo permitido (" . $valEmail[1] . ")";
 *     exit;
 * }
 * 
 * // Validar Contraseña (Min 8 caracteres, 1 mayúscula, 1 número, 1 carácter especial, sin espacios)
 * $valPass = ValidatorModule::camp($password, [
 *     'min_length' => 8,
 *     'min_capital' => 1,
 *     'min_number' => 1,
 *     'min_char' => 1,
 *     'space' => false
 * ]);
 * 
 * if ($valPass[0]) {
 *     $passwordValidado = $valPass[1]; // Acceso al valor validado
 *     echo "¡Registro exitoso!";
 * } else {
 *     echo "La contraseña no cumple con los requisitos mínimos de seguridad.";
 * }
 * ```
 */
class ValidatorModule
{

  private static $content;

  /**
   * Este método valida cada datos que se administre en los controladores y devuelve un array. En caso caso de ser aprobado 
   * el array devuelve [0 => true, 1 => el campo evaluado] y si se rechaza devuelve [0 => false, 1 => el validador que emitió el rechazo] 
   * .
   * estos validadores están aceptado: 
   * 'email',
   * 'max_length int',
   * 'min_length int',
   * 'min_string int',
   * 'min_capital int',
   * 'min_char int',
   * 'min_number int',
   * 'space'
   * @var content -- el valor a validar
   * @var validate -- el validador o los validadores de $content.
   * @property caracteres especiales soportados [ @ # $ % & _ ]
   */
  public static function camp($content, ...$validate)
  {

    self::$content = $content;

    // Validar que se hayan proporcionado validadores
    if (empty($validate[0])) {
      return '<p class="mt5 mb5">Agregue un validador para el campo:<span class="bold800 color6"> "' . $content . '"</span>.</p>';
    }

    $function_available = [
      'email',
      'max_length',
      'min_length',
      'min_string',
      'min_capital',
      'min_char',
      'min_number',
      'space'
    ];

    // Ejecutar cada validador
    foreach ($validate[0] as $key => $value) {

      $validator_name = null;
      $validator_param = null;
      $custom_message = null;

      if (is_numeric($key)) {
        // Formato con llave numérica: el valor contiene toda la definición
        if (is_string($value) && strpos($value, ',') !== false) {
          $parts = explode(',', $value);
          $partsCount = count($parts);
          
          if ($partsCount >= 3) {
            $validator_name = trim($parts[0]);
            $validator_param = trim($parts[1]);
            $custom_message = trim($parts[2]);
          } elseif ($partsCount === 2) {
            $validator_name = trim($parts[0]);
            $part2 = trim($parts[1]);
            
            // Si es 'email' (sin parámetros), el segundo elemento es el mensaje
            if ($validator_name === 'email') {
              $validator_param = null;
              $custom_message = $part2;
            } else {
              // Si el segundo elemento es un parámetro numérico o booleano, es el parámetro
              if ($part2 === 'true' || $part2 === 'false' || is_numeric($part2)) {
                $validator_param = $part2;
                $custom_message = null;
              } else {
                // De lo contrario, es el mensaje personalizado
                $validator_param = null;
                $custom_message = $part2;
              }
            }
          } else {
            $validator_name = trim($parts[0]);
            $validator_param = null;
            $custom_message = null;
          }
        } elseif (is_string($value) && strpos($value, ' ') !== false) {
          // Formato: "max_length 15"
          $parts = explode(' ', $value, 2);
          $validator_name = trim($parts[0]);
          $validator_param = trim($parts[1]);
          $custom_message = null;
        } else {
          // Formato: "email"
          $validator_name = $value;
          $validator_param = null;
          $custom_message = null;
        }

        // Convertir tipos de parámetros de string a boolean/int si aplica
        if ($validator_param !== null) {
          if ($validator_param === 'true') {
            $validator_param = true;
          } elseif ($validator_param === 'false') {
            $validator_param = false;
          } elseif (is_numeric($validator_param)) {
            $validator_param = (int)$validator_param;
          }
        }
      } else {
        // Formato Asociativo: 'max_length' => 100 o 'max_length' => [8, 'Mensaje']
        $validator_name = $key;
        if (is_array($value)) {
          $validator_param = $value[0] ?? null;
          $custom_message = $value[1] ?? null;
        } else {
          // Si el validador es 'email', el valor de la clave podría ser el mensaje de error directamente
          if ($validator_name === 'email') {
            $validator_param = null;
            $custom_message = $value;
          } else {
            $validator_param = $value;
            $custom_message = null;
          }
        }
      }

      // Establecer valor por defecto para 'space' si no se especificó
      if ($validator_name === 'space' && $validator_param === null) {
        $validator_param = true;
      }

      // Verificar si el validador existe
      if (!in_array($validator_name, $function_available)) {
        return '<p>Método <span class="bold800 color6">"' . $validator_name . '"</span> no aceptado.</p>';
      }

      // Ejecutar el validador correspondiente
      $result = match ($validator_name) {
        'email' => self::email(),
        'max_length' => self::max_length($validator_param),
        'min_length' => self::min_length($validator_param),
        'min_string' => self::min_string($validator_param),
        'min_capital' => self::min_capital($validator_param),
        'min_char' => self::min_char($validator_param),
        'min_number' => self::min_number($validator_param),
        'space' => self::no_space($validator_param),
        default => false
      };

      // Si falla la validación, retornar el mensaje personalizado o el nombre del validador que falló
      if (!$result) {
        return [false, $custom_message ?? $validator_name];
      }
    }

    // Todos los validadores pasaron
    return [true, self::$content];
  }


  //Métodos validadores -- aquí esta toda la lógica del validador
  private static function email()
  {
    return filter_var(self::$content, FILTER_VALIDATE_EMAIL) !== false;
  }

  private static function max_length($int)
  {
    return strlen(self::$content) <= $int;
  }

  private static function min_length($int)
  {
    return strlen(self::$content) >= $int;
  }

  private static function min_string($string)
  {
    $count = preg_match_all('/[a-zA-Z]/', self::$content);
    return $count >= $string;
  }

  private static function min_capital($string)
  {
    $count = preg_match_all('/[A-Z]/', self::$content);
    return $count >= $string;
  }

  private static function min_char($char)
  {
    $count = preg_match_all('/[@#$%&_]/', self::$content);
    return $count >= $char;
  }

  private static function min_number($int)
  {
    $count = preg_match_all('/\d/', self::$content);
    return $count >= $int;
  }

  private static function no_space($bool)
  {
    $has_space = preg_match('/\s/', self::$content) === 1;
    return $has_space === $bool;
  }
}
