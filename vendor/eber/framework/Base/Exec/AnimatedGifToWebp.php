<?php

namespace Base\Exec;

use Imagick;

/**
 * Conversor especializado de GIFs animados a WebP animado.
 * 
 * Funciona de manera autónoma con GD (en PHP puro) ensamblando el contenedor
 * estándar RIFF/WEBP con chunks VP8X, ANIM y ANMF, y también aprovecha Imagick
 * si está disponible para máxima velocidad.
 */
class AnimatedGifToWebp
{
  /**
   * Convierte un archivo GIF animado a WebP animado.
   * 
   * @param string $sourceGifPath Ruta del GIF animado de origen.
   * @param string $destWebpPath Ruta del archivo WebP de destino.
   * @param int $quality Calidad de compresión WebP (0-100).
   * @param int|null $maxWidth Ancho máximo opcional para redimensionar.
   * @param int|null $maxHeight Alto máximo opcional para redimensionar.
   * @param int $frameStep Salto de fotogramas (1 = todos, 2 = mitad de frames, 3 = 1 de cada 3, etc.).
   * @return bool True si la conversión fue exitosa, false en caso de error.
   */
  public static function convert(
    string $sourceGifPath,
    string $destWebpPath,
    int $quality = 80,
    ?int $maxWidth = null,
    ?int $maxHeight = null,
    int $frameStep = 1
  ): bool {
    if (!file_exists($sourceGifPath) || !is_file($sourceGifPath)) {
      return false;
    }

    $destDir = dirname($destWebpPath);
    if (!is_dir($destDir)) {
      $oldUmask = umask(0);
      @mkdir($destDir, 0777, true);
      umask($oldUmask);
    }

    $frameStep = max(1, $frameStep);

    // 1. Si Imagick está disponible y frameStep es 1, usarlo
    if (class_exists('Imagick') && $frameStep === 1) {
      try {
        $imagick = new Imagick($sourceGifPath);
        $imagick = $imagick->coalesceImages();

        $maxW = $maxWidth ?? (defined('MAX_IMAGE_WIDTH') ? MAX_IMAGE_WIDTH : 0);
        $maxH = $maxHeight ?? (defined('MAX_IMAGE_HEIGHT') ? MAX_IMAGE_HEIGHT : 0);

        foreach ($imagick as $frame) {
          $frame->setImageDepth(8);

          $width = $frame->getImageWidth();
          $height = $frame->getImageHeight();

          if (($maxW > 0 && $width > $maxW) || ($maxH > 0 && $height > $maxH)) {
            $scale = 1.0;
            if ($maxW > 0 && $width > $maxW) {
              $scale = min($scale, $maxW / $width);
            }
            if ($maxH > 0 && $height > $maxH) {
              $scale = min($scale, $maxH / $height);
            }
            $newW = (int) ceil($width * $scale);
            $newH = (int) ceil($height * $scale);
            $frame->thumbnailImage($newW, $newH);
          }

          $frame->setImageFormat('webp');
        }

        $imagick = $imagick->deconstructImages();
        $imagick->setFormat('webp');

        if ($quality > 0) {
          $imagick->setImageCompressionQuality($quality);
        }

        $imagick->stripImage();
        $written = $imagick->writeImages($destWebpPath, true);

        if ($written && file_exists($destWebpPath) && filesize($destWebpPath) > 0) {
          return true;
        }
      } catch (\Exception $e) {
        error_log('AnimatedGifToWebp: Imagick falló, recurriendo al motor GD nativo: ' . $e->getMessage());
      }
    }

    // 2. Motor nativo en PHP puro usando GD + multiplexor de chunks RIFF/WEBP
    return self::convertWithGdNative($sourceGifPath, $destWebpPath, $quality, $maxWidth, $maxHeight, $frameStep);
  }

  /**
   * Decodifica los fotogramas del GIF y los empaqueta en un WebP animado (VP8X + ANIM + ANMF).
   * 
   * @param string $sourceGifPath Ruta origen del archivo GIF.
   * @param string $destWebpPath Ruta destino del archivo WebP.
   * @param int $quality Calidad WebP (0-100).
   * @param int|null $maxWidth Ancho máximo opcional.
   * @param int|null $maxHeight Alto máximo opcional.
   * @param int $frameStep Salto de fotogramas para optimización.
   * @return bool True si se generó el archivo WebP correctamente.
   */
  private static function convertWithGdNative(
    string $sourceGifPath,
    string $destWebpPath,
    int $quality,
    ?int $maxWidth,
    ?int $maxHeight,
    int $frameStep = 1
  ): bool {
    $gifData = @file_get_contents($sourceGifPath);
    if (!$gifData) {
      return false;
    }

    $len = strlen($gifData);
    if ($len < 13 || substr($gifData, 0, 3) !== 'GIF') {
      return false;
    }

    $screenDesc = substr($gifData, 6, 7);
    $canvasW = ord($screenDesc[0]) | (ord($screenDesc[1]) << 8);
    $canvasH = ord($screenDesc[2]) | (ord($screenDesc[3]) << 8);

    if ($canvasW <= 0 || $canvasH <= 0) {
      return false;
    }

    $packed = ord($screenDesc[4]);
    $hasGct = ($packed & 0x80) !== 0;
    $gctSize = $hasGct ? (3 * (1 << (($packed & 0x07) + 1))) : 0;
    $pos = 13;
    $gct = $hasGct ? substr($gifData, $pos, $gctSize) : '';
    $pos += $gctSize;

    $frames = [];
    $gce = '';
    $delay = 100;
    $disposal = 0;
    $transparent = -1;
    $loopCount = 0;

    // Escanear bloques del GIF
    while ($pos < $len) {
      $char = $gifData[$pos];

      if ($char === "\x3B") { // Trailer (Fin de archivo GIF)
        break;
      } elseif ($char === "\x21") { // Bloque de extensión
        $label = $gifData[$pos + 1] ?? '';
        $pos += 2;

        if ($label === "\xF9") { // Graphic Control Extension (GCE)
          $blockSize = ord($gifData[$pos]);
          $gceData = substr($gifData, $pos + 1, $blockSize);
          $gcePacked = ord($gceData[0]);
          $disposal = ($gcePacked >> 2) & 0x07;
          $hasTransparent = ($gcePacked & 0x01) !== 0;
          $delayUnits = ord($gceData[1]) | (ord($gceData[2]) << 8);
          $delay = ($delayUnits > 0 ? $delayUnits : 10) * 10; // Convertir 1/100s a milisegundos
          $transparent = $hasTransparent ? ord($gceData[3]) : -1;
          $gce = "\x21\xF9" . chr($blockSize) . $gceData . "\x00";
          $pos += $blockSize + 2;
        } elseif ($label === "\xFF") { // Application Extension (NETSCAPE2.0 para loops)
          $blockSize = ord($gifData[$pos]);
          $appData = substr($gifData, $pos + 1, $blockSize);
          $pos += $blockSize + 1;

          while ($pos < $len) {
            $subBlockSize = ord($gifData[$pos]);
            $pos++;
            if ($subBlockSize === 0) break;
            $subData = substr($gifData, $pos, $subBlockSize);
            if (str_starts_with($appData, 'NETSCAPE2.0') && $subBlockSize >= 3 && ord($subData[0]) === 1) {
              $loopCount = ord($subData[1]) | (ord($subData[2]) << 8);
            }
            $pos += $subBlockSize;
          }
        } else {
          // Saltar otras extensiones
          while ($pos < $len) {
            $subBlockSize = ord($gifData[$pos]);
            $pos++;
            if ($subBlockSize === 0) break;
            $pos += $subBlockSize;
          }
        }
      } elseif ($char === "\x2C") { // Image Descriptor
        $startPos = $pos;
        $imgDesc = substr($gifData, $pos, 10);
        $imgLeft = ord($imgDesc[1]) | (ord($imgDesc[2]) << 8);
        $imgTop = ord($imgDesc[3]) | (ord($imgDesc[4]) << 8);
        $imgWidth = ord($imgDesc[5]) | (ord($imgDesc[6]) << 8);
        $imgHeight = ord($imgDesc[7]) | (ord($imgDesc[8]) << 8);

        $imgPacked = ord($imgDesc[9]);
        $hasLct = ($imgPacked & 0x80) !== 0;
        $lctSize = $hasLct ? (3 * (1 << (($imgPacked & 0x07) + 1))) : 0;
        $pos += 10 + $lctSize;
        $pos++; // LZW Minimum Code Size

        while ($pos < $len) {
          $subBlockSize = ord($gifData[$pos]);
          $pos++;
          if ($subBlockSize === 0) break;
          $pos += $subBlockSize;
        }

        $imgData = substr($gifData, $startPos, $pos - $startPos);

        $frameSd = $screenDesc;
        if ($hasLct) {
          // Si tiene tabla de color local, desmarcar el bit de GCT en el Screen Descriptor
          $frameSd[4] = chr(ord($frameSd[4]) & 0x7F);
          $frameGct = '';
        } else {
          $frameGct = $gct;
        }

        $singleGif = 'GIF89a' . $frameSd . $frameGct . $gce . $imgData . "\x3B";

        $frames[] = [
          'data' => $singleGif,
          'left' => $imgLeft,
          'top' => $imgTop,
          'width' => $imgWidth,
          'height' => $imgHeight,
          'delay' => max(20, $delay), // Mínimo 20ms para evitar frames congelados
          'disposal' => $disposal,
          'transparent' => $transparent
        ];

        $gce = '';
        $delay = 100;
        $disposal = 0;
        $transparent = -1;
      } else {
        $pos++;
      }
    }

    if (empty($frames)) {
      return false;
    }

    // Calcular escala si se definieron dimensiones máximas
    $targetCanvasW = $canvasW;
    $targetCanvasH = $canvasH;
    $maxW = $maxWidth ?? (defined('MAX_IMAGE_WIDTH') ? MAX_IMAGE_WIDTH : 0);
    $maxH = $maxHeight ?? (defined('MAX_IMAGE_HEIGHT') ? MAX_IMAGE_HEIGHT : 0);

    if (($maxW > 0 && $canvasW > $maxW) || ($maxH > 0 && $canvasH > $maxH)) {
      $scale = 1.0;
      if ($maxW > 0 && $canvasW > $maxW) {
        $scale = min($scale, $maxW / $canvasW);
      }
      if ($maxH > 0 && $canvasH > $maxH) {
        $scale = min($scale, $maxH / $canvasH);
      }
      $targetCanvasW = (int) ceil($canvasW * $scale);
      $targetCanvasH = (int) ceil($canvasH * $scale);
    }

    // Crear lienzo maestro con transparencia
    $canvas = imagecreatetruecolor($canvasW, $canvasH);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    $transparentColor = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefill($canvas, 0, 0, $transparentColor);

    $anmfChunks = '';
    $previousCanvas = null;
    $accumDelay = 0;
    $frameCount = count($frames);

    for ($i = 0; $i < $frameCount; $i++) {
      $frameInfo = $frames[$i];
      $frameImg = @imagecreatefromstring($frameInfo['data']);
      if ($frameImg) {
        if ($frameInfo['disposal'] === 3) {
          // Guardar estado previo del lienzo para restaurar luego
          $previousCanvas = imagecreatetruecolor($canvasW, $canvasH);
          imagealphablending($previousCanvas, false);
          imagesavealpha($previousCanvas, true);
          imagecopy($previousCanvas, $canvas, 0, 0, 0, 0, $canvasW, $canvasH);
        }

        // Dibujar fotograma sobre el lienzo respetando su posición y canal alfa
        imagealphablending($canvas, true);
        imagecopy(
          $canvas,
          $frameImg,
          $frameInfo['left'],
          $frameInfo['top'],
          0,
          0,
          imagesx($frameImg),
          imagesy($frameImg)
        );
        imagedestroy($frameImg);
      }

      $accumDelay += $frameInfo['delay'];

      // Determinar si conservamos este fotograma según el paso de optimización
      $isLastFrame = ($i === $frameCount - 1);
      $shouldKeep = (($i + 1) % $frameStep === 0) || $isLastFrame;

      if ($shouldKeep) {
        // Si se redimensionó el lienzo, generar versión escalada para la salida
        $exportImg = $canvas;
        if ($targetCanvasW !== $canvasW || $targetCanvasH !== $canvasH) {
          $exportImg = imagecreatetruecolor($targetCanvasW, $targetCanvasH);
          imagealphablending($exportImg, false);
          imagesavealpha($exportImg, true);
          imagefill($exportImg, 0, 0, $transparentColor);
          imagecopyresampled(
            $exportImg,
            $canvas,
            0, 0, 0, 0,
            $targetCanvasW, $targetCanvasH,
            $canvasW, $canvasH
          );
        }

        // Codificar fotograma a WebP en memoria
        ob_start();
        imagewebp($exportImg, null, $quality);
        $webpFrame = ob_get_clean();

        if ($exportImg !== $canvas) {
          imagedestroy($exportImg);
        }

        if (strlen($webpFrame) >= 12 && substr($webpFrame, 0, 4) === 'RIFF' && substr($webpFrame, 8, 4) === 'WEBP') {
          // Extraer payload de imagen individual (chunk VP8 / VP8L)
          $payload = substr($webpFrame, 12);
          $payloadLen = strlen($payload);
          $pad = ($payloadLen % 2 === 1) ? "\x00" : '';

          $fx = substr(pack('V', 0), 0, 3);
          $fy = substr(pack('V', 0), 0, 3);
          $fw = substr(pack('V', $targetCanvasW - 1), 0, 3);
          $fh = substr(pack('V', $targetCanvasH - 1), 0, 3);
          $dur = substr(pack('V', max(20, $accumDelay)), 0, 3);
          $flags = chr(0x02); // 0x02 = NO_BLENDING (reemplaza rectángulo completo)

          $anmfData = $fx . $fy . $fw . $fh . $dur . $flags . $payload . $pad;
          $anmfChunks .= 'ANMF' . pack('V', 16 + $payloadLen) . $anmfData;
        }

        $accumDelay = 0; // Reiniciar retardo acumulado
      }

      // Aplicar método de disposición de fotograma (Disposal Method)
      if ($frameInfo['disposal'] === 2) {
        // Restaurar área al fondo transparente
        imagealphablending($canvas, false);
        imagefilledrectangle(
          $canvas,
          $frameInfo['left'],
          $frameInfo['top'],
          $frameInfo['left'] + $frameInfo['width'] - 1,
          $frameInfo['top'] + $frameInfo['height'] - 1,
          $transparentColor
        );
      } elseif ($frameInfo['disposal'] === 3 && $previousCanvas !== null) {
        // Restaurar estado previo
        imagealphablending($canvas, false);
        imagecopy($canvas, $previousCanvas, 0, 0, 0, 0, $canvasW, $canvasH);
        imagedestroy($previousCanvas);
        $previousCanvas = null;
      }
    }

    imagedestroy($canvas);

    if (empty($anmfChunks)) {
      return false;
    }

    // Construcción del contenedor RIFF/WEBP con VP8X, ANIM y ANMF
    $vp8xFlags = 0x02 | 0x10; // Bit 1: Animation | Bit 4: Alpha
    $vw = substr(pack('V', $targetCanvasW - 1), 0, 3);
    $vh = substr(pack('V', $targetCanvasH - 1), 0, 3);
    $vp8xChunk = 'VP8X' . pack('V', 10) . pack('V', $vp8xFlags) . $vw . $vh;
    $animChunk = 'ANIM' . pack('V', 6) . "\x00\x00\x00\x00" . pack('v', $loopCount);

    $body = $vp8xChunk . $animChunk . $anmfChunks;
    $riffHeader = 'RIFF' . pack('V', strlen($body) + 4) . 'WEBP';

    $finalWebp = $riffHeader . $body;
    return (bool) @file_put_contents($destWebpPath, $finalWebp);
  }
}
