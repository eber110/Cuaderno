<?php

namespace Base\Module;

use Core\ErrorHandler;
use DateTime;

/**
 * Módulo para manejo de fechas y tiempo.
 * 
 * Proporciona utilidades para:
 * - Calcular tiempo transcurrido ("hace X minutos")
 * - Cuenta regresiva hasta una fecha futura
 * 
 * @example
 * // Tiempo transcurrido
 * $ago = DateTimeModule::timeAgo("2025-12-10 12:00:00"); // "hace 3 días"
 * 
 * // Cuenta regresiva
 * $countdown = DateTimeModule::countdown("2026-01-01", "00:00:00");
 * // ['year' => 0, 'month' => 0, 'day' => 18, ...]
 */
class DateTimeModule
{
  /**
   * Calcula el tiempo transcurrido desde una fecha.
   * 
   * @param string $dateReg Fecha de registro (formato: Y-m-d H:i:s).
   * @return string Texto descriptivo del tiempo transcurrido.
   */
  public static function timeAgo(string $dateReg): string
  {
    $initDate = date_create(date("Y-m-d H:i:s"));
    $endDate = date_create($dateReg);
    $interval = date_diff($endDate, $initDate);

    // Extraer valores del intervalo
    $timeArray = [];
    foreach ($interval as $key => $range) {
      $timeArray[$key] = $range;
    }

    // Calcular días, horas, minutos y segundos
    $now = strtotime(date("Y-m-d H:i:s"));
    $datePost = strtotime($dateReg);
    $toSec = $now - $datePost;
    $agoMin = floor($toSec / 60);
    $agoHour = floor($agoMin / 60);
    $agoDay = floor($agoHour / 24);

    // Determinar el texto apropiado
    if ($timeArray['m'] >= 1 && $timeArray['y'] == 0) {
      return $timeArray['m'] == 1
        ? 'hace ' . $timeArray['m'] . ' mes'
        : 'hace ' . $timeArray['m'] . ' meses';
    }

    if ($timeArray['y'] >= 1) {
      return $timeArray['y'] == 1
        ? 'hace ' . $timeArray['y'] . ' año'
        : 'hace ' . $timeArray['y'] . ' años';
    }

    if ($agoDay >= 1) {
      return $agoDay == 1
        ? 'hace ' . $agoDay . ' día'
        : 'hace ' . $agoDay . ' días';
    }

    if ($agoHour >= 1) {
      return $agoHour == 1
        ? 'hace ' . $agoHour . ' hora'
        : 'hace ' . $agoHour . ' horas';
    }

    if ($agoMin >= 1) {
      return 'hace ' . $agoMin . ' min';
    }

    return 'hace ' . $toSec . ' seg';
  }

  /**
   * Calcula el tiempo restante hasta una fecha futura.
   * 
   * @param string $date Fecha objetivo (formatos: dd-mm-yyyy o yyyy-mm-dd).
   * @param string|null $time Hora objetivo (formato: H:i:s), opcional.
   * @return array|string Array con componentes de tiempo o mensaje de error.
   */
  public static function countdown(int|string $dateOrSeconds, ?string $time = null): array|string
  {
    if (empty($dateOrSeconds) && $dateOrSeconds !== 0 && $dateOrSeconds !== '0') {
      return "Ingrese una fecha o segundos válidos!";
    }

    if (is_numeric($dateOrSeconds)) {
      $seconds = (int)$dateOrSeconds;
      if ($seconds < 0) {
        ErrorHandler::handle_code(400, 400, "El tiempo especificado ya pasó.");
        return [];
      }

      $now = new DateTime();
      $futureDate = (new DateTime())->modify('+' . $seconds . ' seconds');
      $interval = $now->diff($futureDate);

      return [
        "year" => $interval->y,
        "month" => $interval->m,
        "day" => $interval->d,
        "hour" => $interval->h,
        "min" => $interval->i,
        "sec" => $interval->s,
        "allDay" => $interval->days
      ];
    }

    $dateParts = explode("-", $dateOrSeconds);

    // Validar que el año tenga 4 dígitos
    $hasYear = false;
    foreach ($dateParts as $value) {
      if (mb_strlen($value) == 4) {
        $hasYear = true;
        break;
      }
    }

    if (!$hasYear) {
      ErrorHandler::handle_code(400, 400, "El año debe tener 4 dígitos. Formato: (01-01-2000) o (2000-01-01).");
      return [];
    }

    // Normalizar formato de fecha (yyyy-mm-dd)
    $max = max($dateParts);
    $indexMax = array_search($max, $dateParts);

    if ($indexMax != 0) {
      $dateParts = array_reverse($dateParts);
    }

    $normalizedDate = implode("-", $dateParts);

    // Combinar fecha y hora
    $dateTime = $time !== null
      ? $normalizedDate . " " . $time
      : $normalizedDate;

    $futureDate = new DateTime($dateTime);
    $now = new DateTime();

    if ($now >= $futureDate) {
      ErrorHandler::handle_code(400, 400, "La fecha especificada ya pasó.");
      return [];
    }

    $interval = $now->diff($futureDate);

    return [
      "year" => $interval->y,
      "month" => $interval->m,
      "day" => $interval->d,
      "hour" => $interval->h,
      "min" => $interval->i,
      "sec" => $interval->s,
      "allDay" => $interval->days
    ];
  }

  /**
   * Formatea una fecha en español.
   * 
   * @param string $date Fecha a formatear.
   * @param string $format Formato de salida (default: 'd de F, Y').
   * @return string Fecha formateada.
   */
  public static function formatSpanish(string $date, string $format = 'd de F, Y'): string
  {
    $months = [
      'January' => 'enero',
      'February' => 'febrero',
      'March' => 'marzo',
      'April' => 'abril',
      'May' => 'mayo',
      'June' => 'junio',
      'July' => 'julio',
      'August' => 'agosto',
      'September' => 'septiembre',
      'October' => 'octubre',
      'November' => 'noviembre',
      'December' => 'diciembre'
    ];

    $dateObj = new DateTime($date);
    $formatted = $dateObj->format($format);

    return str_replace(array_keys($months), array_values($months), $formatted);
  }
}
