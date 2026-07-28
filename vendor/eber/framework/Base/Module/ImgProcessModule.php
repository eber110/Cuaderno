<?php

namespace Base\Module;

use Base\Builder\Builder;
use Imagick;

/**
 * Módulo de procesamiento de imágenes con compresión inteligente para web.
 * 
 * Características:
 * - Compresión adaptativa basada en dimensiones y orientación
 * - Límite de tamaño en KB configurable
 * - Soporte para múltiples formatos: JPEG, PNG, WebP, GIF, AVIF, BMP, TIFF, HEIC/HEIF
 * - Redimensionamiento automático a dimensiones máximas
 * - Formato de salida configurable (WebP recomendado para web)
 * 
 * @see config.php para opciones de configuración
 */
class ImgProcessModule extends Builder
{

  protected $table;
  protected $valid;
  protected $index;
  protected $name = [];
  protected $type = [];
  protected $format = [];
  protected $tmp = [];
  protected $size_b = [];
  protected $size_mb = [];
  protected $orientation = [];
  protected $gap = []; //dimensión de cada imagen (ARRAY)
  protected $cant; //cantidad de imagenes
  protected $uploadImg;

  /**
   * Formatos que requieren Imagick para procesamiento
   */
  private const IMAGICK_ONLY_FORMATS = ['tiff', 'x-tiff', 'heic', 'heif'];

  /**
   * Mapeo de formatos MIME a extensiones de archivo
   */
  private const FORMAT_EXTENSIONS = [
    'jpeg' => 'jpg',
    'jpg' => 'jpg',
    'png' => 'png',
    'webp' => 'webp',
    'gif' => 'gif',
    'avif' => 'avif',
    'bmp' => 'bmp',
    'x-ms-bmp' => 'bmp',
    'tiff' => 'tiff',
    'x-tiff' => 'tiff',
    'heic' => 'heic',
    'heif' => 'heif'
  ];

  public function __construct($table, $uploadImg = DIR_UPLOAD_MEDIA)
  {

    $this->table = $table;
    $this->uploadImg = $uploadImg;
    $this->ensureDirectoryPermissions();
    parent::__construct();
  }

  /**
   * Asegura que el directorio de subida exista y tenga permisos de escritura (0777).
   * Crea el directorio recursivamente si no existe.
   * Esto evita tener que configurar permisos manualmente en servidores Linux.
   *
   * @return void
   */
  private function ensureDirectoryPermissions(): void
  {

    $dir = $this->uploadImg;

    // Crear el directorio si no existe, con permisos 0777 recursivamente
    if (!is_dir($dir)) {

      // umask temporal en 0 para que mkdir respete los permisos 0777
      $oldUmask = umask(0);
      $created = mkdir($dir, 0777, true);
      umask($oldUmask);

      if (!$created) {
        error_log("ImgProcessModule: No se pudo crear el directorio: {$dir}");
        return;
      }
    }

    // Aplicar permisos 0777 al directorio si aún no los tiene
    $currentPerms = fileperms($dir) & 0777;

    if ($currentPerms !== 0777) {

      if (!chmod($dir, 0777)) {
        error_log("ImgProcessModule: No se pudieron establecer permisos 0777 en: {$dir}");
      }
    }
  }

  /**
   * Verifica estáticamente si se ha subido al menos una imagen en el formulario.
   * No requiere instanciar la clase.
   *
   * @return bool true si hay al menos una imagen subida, false en caso contrario
   */
  public static function imgUploaded(): bool
  {

    if (empty($_FILES)) {
      return false;
    }

    foreach ($_FILES as $fileData) {

      // Subida múltiple
      if (is_array($fileData['name'])) {

        foreach ($fileData['error'] as $key => $error) {

          if ($error === UPLOAD_ERR_OK && !empty($fileData['tmp_name'][$key]) && is_uploaded_file($fileData['tmp_name'][$key])) {
            return true;
          }
        }
      }
      // Subida simple
      elseif (is_string($fileData['name'])) {

        if ($fileData['error'] === UPLOAD_ERR_OK && !empty($fileData['tmp_name']) && is_uploaded_file($fileData['tmp_name'])) {
          return true;
        }
      }
    }

    return false;
  }

  /**
   * Valida y extrae la información de las imágenes subidas,
   * funcionando tanto para subidas de uno como de múltiples archivos.
   */
  private function img_exists(): void
  {
    // 1. Reiniciar propiedades para cada ejecución
    $this->valid = false;
    $properties = ['index', 'name', 'type', 'format', 'tmp', 'size_b', 'size_mb', 'orientation', 'gap', 'cant'];
    foreach ($properties as $prop) {
      $this->$prop = is_array($this->$prop) ? [] : null;
    }

    if (empty($_FILES)) {
      return;
    }

    // 2. Normalizar la estructura de $_FILES para que siempre sea un array de archivos
    $normalized_files = [];

    $input_name = null;
    if (!empty($this->table) && isset($_FILES[$this->table])) {
      $input_name = $this->table;
    } else {
      // Si no se especificó $this->table o no existe esa clave, buscar la primera clave en $_FILES que contenga un archivo válido
      foreach ($_FILES as $key => $fileData) {
        if (is_array($fileData['name'])) {
          foreach ($fileData['error'] as $err) {
            if ($err === UPLOAD_ERR_OK) {
              $input_name = $key;
              break 2;
            }
          }
        } elseif (is_string($fileData['name']) && $fileData['error'] === UPLOAD_ERR_OK) {
          $input_name = $key;
          break;
        }
      }
      if ($input_name === null) {
        $input_name = array_keys($_FILES)[0];
      }
    }

    $files_data = $_FILES[$input_name];

    // Si 'name' es un string, es una subida de un solo archivo. Lo convertimos a un array.
    if (is_string($files_data['name'])) {
      if ($files_data['error'] !== UPLOAD_ERR_NO_FILE) {
        $normalized_files[] = $files_data;
      }
    }
    // Si 'name' es un array, es una subida múltiple.
    elseif (is_array($files_data['name'])) {
      $count = count($files_data['name']);
      for ($i = 0; $i < $count; $i++) {
        if ($files_data['error'][$i] !== UPLOAD_ERR_NO_FILE) {
          $normalized_files[] = [
            'name' => $files_data['name'][$i],
            'type' => $files_data['type'][$i],
            'tmp_name' => $files_data['tmp_name'][$i],
            'error' => $files_data['error'][$i],
            'size' => $files_data['size'][$i],
          ];
        }
      }
    }

    if (empty($normalized_files)) {
      return;
    }

    // 3. Procesar el array normalizado de archivos
    $this->cant = count($normalized_files);
    $allow = defined('IMG_ADMITTED') ? IMG_ADMITTED : ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $mb_size = 1024 * 1024;

    foreach ($normalized_files as $file) {
      if ($file['error'] !== UPLOAD_ERR_OK) {
        continue;
      }

      $current_type = $file['type'];
      $current_format = explode('/', $current_type)[1] ?? '';

      if (!in_array($current_type, $allow)) {
        $this->valid = false;
        return;
      }

      $this->type[] = $current_type;
      $this->format[] = $current_format;

      // Determinar extensión de salida basada en formato configurado
      $output_ext = $this->getOutputExtension($current_format);
      $this->name[] = bin2hex(random_bytes(16)) . '.' . $output_ext;

      $this->tmp[] = $file['tmp_name'];
      $this->size_b[] = $file['size'];
      $this->size_mb[] = (int) ceil($file['size'] / $mb_size);

      // Obtener dimensiones de la imagen
      $dimensions = $this->getImageDimensions($file['tmp_name'], $current_format);
      $this->gap[] = $dimensions;

      if ($dimensions['width'] > $dimensions['height']) {
        $this->orientation[] = 'landscape';
      } elseif ($dimensions['width'] === $dimensions['height']) {
        $this->orientation[] = 'square';
      } else {
        $this->orientation[] = 'portrait';
      }
    }

    if (!empty($this->name)) {
      $this->valid = true;
      $this->index = $input_name;
    }
  }

  /**
   * Obtiene las dimensiones de una imagen, soportando formatos especiales con Imagick
   */
  private function getImageDimensions(string $tmpPath, string $format): array
  {
    // Para formatos que requieren Imagick
    if (in_array($format, self::IMAGICK_ONLY_FORMATS)) {
      if (class_exists('Imagick')) {
        try {
          $imagick = new Imagick($tmpPath);
          return [
            'width' => $imagick->getImageWidth(),
            'height' => $imagick->getImageHeight()
          ];
        } catch (\Exception $e) {
          error_log('Error al obtener dimensiones con Imagick: ' . $e->getMessage());
        }
      }
    }

    // Método estándar para formatos soportados por GD
    $info = @getimagesize($tmpPath);
    if ($info !== false) {
      return ['width' => $info[0], 'height' => $info[1]];
    }

    return ['width' => 0, 'height' => 0];
  }

  /**
   * Determina la extensión de archivo de salida basada en la configuración
   */
  private function getOutputExtension(string $inputFormat): string
  {
    // Usar formato de salida configurado
    $outputFormat = (defined('OUTPUT_FORMAT') && OUTPUT_FORMAT !== '') ? OUTPUT_FORMAT : 'webp';

    // GIFs animados mantienen su formato si se solicita gif explícitamente,
    // pero si el destino es webp o avif, permitimos la conversión
    if ($inputFormat === 'gif') {
      return ($outputFormat === 'gif' || $outputFormat === 'webp' || $outputFormat === 'avif') ? $outputFormat : 'gif';
    }

    // Usar formato de salida configurado
    $outputFormat = (defined('OUTPUT_FORMAT') && OUTPUT_FORMAT !== '') ? OUTPUT_FORMAT : 'webp';

    // Verificar soporte para AVIF
    if ($outputFormat === 'avif' && !function_exists('imagecreatefromavif')) {
      $outputFormat = 'webp'; // Fallback a WebP si AVIF no está disponible
    }

    return $outputFormat;
  }

  /**
   * Graba las imágenes en la BD y en disco con compresión optimizada
   * 
   * @param string $name_img_bd Nombre de la columna para el nombre de imagen
   * @param array $col_bd Array con las demás columnas de la tabla
   * @param int|null $maxKb Límite de tamaño en KB (null = usar config, 0 = sin límite)
   * @return array|bool Array con IDs insertados o false si falla
   */
  public function record_img_disk(string $name_img_bd, array $col_bd = [], ?int $maxKb = null): array|bool
  {
    $this->img_exists();

    if (empty($name_img_bd) || !$this->valid) {
      return false;
    }

    $img_data_to_insert = [];
    for ($i = 0; $i < $this->cant; $i++) {
      $row = [$name_img_bd => $this->name[$i]];
      foreach ($col_bd as $key => $value) {
        $row[$key] = $value;
      }
      $img_data_to_insert[] = $row;
    }

    $all_keys = array_keys($img_data_to_insert[0]);
    $key_data_bd = implode(', ', $all_keys);
    $placeholders = implode(', ', array_fill(0, count($all_keys), '?'));

    $inserted_ids = [];

    foreach ($img_data_to_insert as $row) {
      $sql = "INSERT INTO {$this->table} ( {$key_data_bd} ) VALUES ( {$placeholders} )";
      $this->query_foreign($sql, array_values($row));

      if ($this->query_error) {
        return false;
      }

      $last_id = $this->last_id();
      if ($last_id) {
        $inserted_ids[] = $last_id;
      }
    }

    if ($this->img_create($maxKb)) {
      return $inserted_ids;
    } else {
      return false;
    }
  }

  /**
   * Procesa y graba las imágenes únicamente en disco (sin guardar registros en la base de datos)
   * 
   * @param int|null $maxKb Límite de tamaño en KB (null = usar config, 0 = sin límite)
   * @return array|bool Array con los nombres de archivo generados o false si falla
   */
  public function save_img_disk(?int $maxKb = null): array|bool
  {
    $this->img_exists();

    if (!$this->valid) {
      return false;
    }

    if ($this->img_create($maxKb)) {
      return $this->name;
    }

    return false;
  }

  /**
   * Crea un recurso de imagen GD desde cualquier formato soportado
   * 
   * @param string $tmpPath Ruta temporal de la imagen
   * @param string $format Formato de la imagen
   * @return \GdImage|false Recurso de imagen o false si falla
   */
  private function createImageFromAny(string $tmpPath, string $format): \GdImage|false
  {
    switch ($format) {
      case 'jpeg':
      case 'jpg':
        return @imagecreatefromjpeg($tmpPath);

      case 'png':
        return @imagecreatefrompng($tmpPath);

      case 'webp':
        return @imagecreatefromwebp($tmpPath);

      case 'gif':
        return @imagecreatefromgif($tmpPath);

      case 'avif':
        if (function_exists('imagecreatefromavif')) {
          return @imagecreatefromavif($tmpPath);
        }
        break;

      case 'bmp':
      case 'x-ms-bmp':
        if (function_exists('imagecreatefrombmp')) {
          return @imagecreatefrombmp($tmpPath);
        }
        break;

      case 'tiff':
      case 'x-tiff':
      case 'heic':
      case 'heif':
        // Estos formatos requieren Imagick, convertir a formato intermedio
        return $this->convertWithImagick($tmpPath);
    }

    return false;
  }

  /**
   * Convierte imagen con Imagick a formato que GD pueda procesar
   */
  private function convertWithImagick(string $tmpPath): \GdImage|false
  {
    if (!class_exists('Imagick')) {
      error_log('Imagick no está disponible para convertir el formato');
      return false;
    }

    try {
      $imagick = new Imagick($tmpPath);
      $imagick->setImageFormat('png');

      // Crear archivo temporal PNG
      $tempFile = sys_get_temp_dir() . '/' . uniqid('img_convert_') . '.png';
      $imagick->writeImage($tempFile);
      $imagick->clear();
      $imagick->destroy();

      $gdImage = @imagecreatefrompng($tempFile);
      unlink($tempFile);

      return $gdImage;
    } catch (\Exception $e) {
      error_log('Error al convertir con Imagick: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Guarda la imagen en el formato de salida especificado
   * 
   * @param \GdImage $image Recurso de imagen GD
   * @param string $path Ruta de destino
   * @param int $quality Calidad de compresión (0-100)
   * @param string $outputFormat Formato de salida
   * @return bool Éxito de la operación
   */
  private function saveImageAs(\GdImage $image, string $path, int $quality, string $outputFormat): bool
  {
    switch ($outputFormat) {
      case 'webp':
        return imagewebp($image, $path, $quality);

      case 'avif':
        if (function_exists('imageavif')) {
          return imageavif($image, $path, $quality);
        }
        return imagewebp($image, $path, $quality); // Fallback

      case 'jpeg':
      case 'jpg':
        return imagejpeg($image, $path, $quality);

      case 'png':
        // PNG quality es 0-9 (inverso), convertir desde 0-100
        $pngQuality = (int) floor((100 - $quality) / 11.11);
        return imagepng($image, $path, $pngQuality);

      case 'gif':
        return imagegif($image, $path);

      default:
        return imagewebp($image, $path, $quality);
    }
  }

  /**
   * Calcula las dimensiones óptimas respetando los límites máximos
   * 
   * @param int $width Ancho original
   * @param int $height Alto original
   * @return array ['width' => int, 'height' => int, 'scale' => float]
   */
  private function calculateOptimalDimensions(int $width, int $height): array
  {
    $maxWidth = defined('MAX_IMAGE_WIDTH') ? MAX_IMAGE_WIDTH : 0;
    $maxHeight = defined('MAX_IMAGE_HEIGHT') ? MAX_IMAGE_HEIGHT : 0;

    $scale = 1.0;

    if ($maxWidth > 0 && $width > $maxWidth) {
      $scale = min($scale, $maxWidth / $width);
    }

    if ($maxHeight > 0 && $height > $maxHeight) {
      $scale = min($scale, $maxHeight / $height);
    }

    return [
      'width' => (int) ceil($width * $scale),
      'height' => (int) ceil($height * $scale),
      'scale' => $scale
    ];
  }

  /**
   * Calcula la calidad óptima basada en dimensiones y orientación
   */
  private function calculateOptimalQuality(int $width, string $orientation): int
  {
    if (!defined('SMART_COMPRESSION') || !SMART_COMPRESSION) {
      return 80; // Calidad por defecto
    }

    if ($orientation === 'landscape' || $orientation === 'square') {
      if ($width < 712) {
        return defined('SML_QUALITY') ? SML_QUALITY : 75;
      } else if ($width >= 712 && $width < 1024) {
        return defined('MID_QUALITY') ? MID_QUALITY : 75;
      } else if ($width >= 1024 && $width < 2000) {
        return defined('QUALITY') ? QUALITY : 55;
      } else {
        return defined('XL_QUALITY') ? XL_QUALITY : 50;
      }
    } else { // portrait
      if ($width < 512) {
        return defined('P_SML_QUALITY') ? P_SML_QUALITY : 75;
      } else if ($width >= 512 && $width < 1024) {
        return defined('P_MID_QUALITY') ? P_MID_QUALITY : 75;
      } else if ($width >= 1024 && $width < 2000) {
        return defined('P_QUALITY') ? P_QUALITY : 65;
      } else {
        return defined('P_XL_QUALITY') ? P_XL_QUALITY : 50;
      }
    }
  }

  /**
   * Comprime la imagen iterativamente hasta alcanzar el límite de KB
   * 
   * @param \GdImage $image Recurso de imagen
   * @param string $outputPath Ruta de salida
   * @param int $maxKb Límite en KB
   * @param int $initialQuality Calidad inicial
   * @param string $outputFormat Formato de salida
   * @return bool Éxito de la operación
   */
  private function compressToMaxSize(
    \GdImage $image,
    string $outputPath,
    int $maxKb,
    int $initialQuality,
    string $outputFormat
  ): bool {
    $quality = $initialQuality;
    $minQuality = defined('MIN_QUALITY') ? MIN_QUALITY : 20;
    $qualityStep = defined('QUALITY_STEP') ? QUALITY_STEP : 5;
    $maxBytes = $maxKb * 1024;

    // Crear archivo temporal para medir tamaño
    $tempPath = sys_get_temp_dir() . '/' . uniqid('img_compress_') . '.tmp';

    while ($quality >= $minQuality) {
      $this->saveImageAs($image, $tempPath, $quality, $outputFormat);
      $fileSize = filesize($tempPath);

      if ($fileSize <= $maxBytes) {
        // Tamaño aceptable, copiar al destino final
        copy($tempPath, $outputPath);
        unlink($tempPath);
        return true;
      }

      $quality -= $qualityStep;
    }

    // Si llegamos aquí, necesitamos reducir dimensiones
    $origWidth = imagesx($image);
    $origHeight = imagesy($image);
    $width = $origWidth;
    $height = $origHeight;
    $scaleFactor = 0.9; // Reducir 10% cada iteración

    while ($width > 100 && $height > 100) {
      $newWidth = (int) ceil($width * $scaleFactor);
      $newHeight = (int) ceil($height * $scaleFactor);

      $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

      // Preservar transparencia
      if ($outputFormat === 'png' || $outputFormat === 'webp') {
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
        $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
        imagefill($resizedImage, 0, 0, $transparent);
      }

      imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

      // Reintentar con calidad inicial
      $quality = $initialQuality;

      while ($quality >= $minQuality) {
        $this->saveImageAs($resizedImage, $tempPath, $quality, $outputFormat);
        $fileSize = filesize($tempPath);

        if ($fileSize <= $maxBytes) {
          copy($tempPath, $outputPath);
          unlink($tempPath);
          imagedestroy($resizedImage);
          return true;
        }

        $quality -= $qualityStep;
      }

      imagedestroy($resizedImage);
      $width = $newWidth;
      $height = $newHeight;
    }

    // Último recurso: guardar con la configuración más agresiva
    unlink($tempPath);
    return $this->saveImageAs($image, $outputPath, $minQuality, $outputFormat);
  }

  /**
   * Procesa y guarda las imágenes con compresión optimizada para web
   * 
   * @param int|null $maxKb Límite de tamaño en KB (null = usar config)
   * @return bool Éxito de la operación
   */
  private function img_create(?int $maxKb = null): bool
  {
    if ($this->valid !== true) {
      return false;
    }

    // Determinar límite de KB
    if ($maxKb === null) {
      $maxKb = defined('MAX_IMAGE_KB') ? MAX_IMAGE_KB : 0;
    }

    $outputFormat = (defined('OUTPUT_FORMAT') && OUTPUT_FORMAT !== '') ? OUTPUT_FORMAT : 'webp';
    $compressEnabled = defined('COMPRESS_IMAGE') ? COMPRESS_IMAGE : true;

    for ($i = 0; $i < $this->cant; $i++) {
      $width_img = $this->gap[$i]['width'];
      $height_img = $this->gap[$i]['height'];
      $format = $this->format[$i];

      // Manejar GIFs animados con Imagick
      if ($format === 'gif') {
        if (!$this->processAnimatedGif($i)) {
          return false;
        }
        continue;
      }

      // Crear imagen desde el archivo original
      $sourceImage = $this->createImageFromAny($this->tmp[$i], $format);
      if (!$sourceImage) {
        error_log("No se pudo crear imagen desde formato: {$format}");
        return false;
      }

      // Calcular dimensiones óptimas
      $optimalDims = $this->calculateOptimalDimensions($width_img, $height_img);
      $newWidth = $optimalDims['width'];
      $newHeight = $optimalDims['height'];

      // Calcular calidad óptima
      $quality = $compressEnabled
        ? $this->calculateOptimalQuality($newWidth, $this->orientation[$i])
        : 95;

      // Crear nueva imagen redimensionada
      $newImage = imagecreatetruecolor($newWidth, $newHeight);

      // Preservar transparencia para formatos que la soportan
      if (in_array($format, ['png', 'webp', 'avif']) || in_array($outputFormat, ['png', 'webp', 'avif'])) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
        imagefill($newImage, 0, 0, $transparent);
      }

      // Redimensionar
      imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width_img, $height_img);

      $outputPath = $this->uploadImg . $this->name[$i];

      // Aplicar compresión con límite de KB si está especificado
      if ($maxKb > 0) {
        $success = $this->compressToMaxSize($newImage, $outputPath, $maxKb, $quality, $outputFormat);
      } else {
        $success = $this->saveImageAs($newImage, $outputPath, $quality, $outputFormat);
      }

      imagedestroy($newImage);
      imagedestroy($sourceImage);

      if (!$success) {
        return false;
      }
    }

    return true;
  }

  /**
   * Procesa GIFs animados usando Imagick
   */
  private function processAnimatedGif(int $index): bool
  {
    if (!class_exists('Imagick')) {
      error_log('Imagick no está disponible para procesar GIF animado');
      return false;
    }

    try {
      $gif = new Imagick($this->tmp[$index]);
      $gif = $gif->coalesceImages();

      $outputExt = pathinfo($this->name[$index], PATHINFO_EXTENSION);

      // Obtener dimensiones máximas
      $maxWidth = defined('MAX_IMAGE_WIDTH') ? MAX_IMAGE_WIDTH : 0;
      $maxHeight = defined('MAX_IMAGE_HEIGHT') ? MAX_IMAGE_HEIGHT : 0;

      foreach ($gif as $frame) {
        $frame->setImageDepth(8);
        
        // Redimensionar proporcionalmente
        $width = $frame->getImageWidth();
        $height = $frame->getImageHeight();
        
        if (($maxWidth > 0 && $width > $maxWidth) || ($maxHeight > 0 && $height > $maxHeight)) {
          $scale = 1.0;
          if ($maxWidth > 0 && $width > $maxWidth) {
            $scale = min($scale, $maxWidth / $width);
          }
          if ($maxHeight > 0 && $height > $maxHeight) {
            $scale = min($scale, $maxHeight / $height);
          }
          $newW = (int) ceil($width * $scale);
          $newH = (int) ceil($height * $scale);
          $frame->thumbnailImage($newW, $newH);
        }

        // Si el destino es diferente (ej: webp), cambiar el formato del frame
        if ($outputExt !== 'gif') {
          $frame->setImageFormat($outputExt);
        }
      }

      $gif = $gif->deconstructImages();
      
      if ($outputExt !== 'gif') {
        $gif->setFormat($outputExt);
      }
      
      $gif->stripImage();
      $gif->writeImages($this->uploadImg . $this->name[$index], true);

      return true;
    } catch (\Exception $e) {
      error_log('Error al procesar GIF animado: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Método alternativo basado en GD (sin Imagick) para formatos básicos
   */
  private function img_create_gd(): bool
  {
    if (!$this->valid) return false;

    try {
      for ($i = 0; $i < $this->cant; $i++) {
        $width_img = $this->gap[$i]['width'];
        $height_img = $this->gap[$i]['height'];

        $percent = 1.0;
        $quality = 80;

        $new_width = (int) ceil($width_img * $percent);
        $new_height = (int) ceil($height_img * $percent);
        $new_image = imagecreatetruecolor($new_width, $new_height);

        $source_image = $this->createImageFromAny($this->tmp[$i], $this->format[$i]);
        if (!$source_image) continue;

        imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $width_img, $height_img);

        $outputFormat = (defined('OUTPUT_FORMAT') && OUTPUT_FORMAT !== '') ? OUTPUT_FORMAT : 'webp';
        $this->saveImageAs($new_image, $this->uploadImg . $this->name[$i], $quality, $outputFormat);

        imagedestroy($new_image);
        imagedestroy($source_image);
      }
      return true;
    } catch (\Exception $e) {
      error_log('Error al crear imagen con GD: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Elimina una o varias imágenes guardadas en disco
   * 
   * @param string $route_img Ruta relativa donde se guardan las imágenes
   * @param array|string $image Nombre(s) de archivo(s) a eliminar
   * @return bool Éxito de la operación
   */
  public function delete_img_disk(string $route_img, array|string $image): bool
  {

    if (is_array($image)) {

      if (!empty($image) && !is_null($image[0])) {

        foreach ($image as $value) {

          $file_path = $route_img . $value;
          if (file_exists($file_path) && is_file($file_path)) {
            unlink($file_path);
          }
        }

        return true;
      } else {

        return false;
      }
    } else {

      if (!empty($image)) {

        $file_path = $route_img . $image;

        if (file_exists($file_path) && is_file($file_path)) {

          unlink($file_path);
          return true;
        } else {

          return false;
        }
      } else {

        return false;
      }
    }
  }

  /**
   * Muestra la información de las imágenes procesadas
   * 
   * @return array Datos de las imágenes
   */
  public function print_data_img(): array
  {
    $this->img_exists();
    if (!$this->valid) {
      return ['valid' => $this->valid];
    }
    return [
      'valid' => $this->valid,
      'index' => $this->index,
      'name' => $this->name,
      'type' => $this->type,
      'format' => $this->format,
      'tmp' => $this->tmp,
      'size_b' => $this->size_b,
      'size_mb' => $this->size_mb,
      'orientation' => $this->orientation,
      'gap' => $this->gap,
      'cant' => $this->cant
    ];
  }

  /**
   * Obtiene los formatos de imagen soportados
   * 
   * @return array Lista de formatos soportados con información de disponibilidad
   */
  public static function getSupportedFormats(): array
  {
    $formats = [
      'jpeg' => ['supported' => true, 'library' => 'GD'],
      'png' => ['supported' => true, 'library' => 'GD'],
      'gif' => ['supported' => true, 'library' => 'GD'],
      'webp' => ['supported' => function_exists('imagecreatefromwebp'), 'library' => 'GD'],
      'avif' => ['supported' => function_exists('imagecreatefromavif'), 'library' => 'GD'],
      'bmp' => ['supported' => function_exists('imagecreatefrombmp'), 'library' => 'GD'],
      'tiff' => ['supported' => class_exists('Imagick'), 'library' => 'Imagick'],
      'heic' => ['supported' => class_exists('Imagick'), 'library' => 'Imagick'],
      'heif' => ['supported' => class_exists('Imagick'), 'library' => 'Imagick'],
    ];

    return $formats;
  }

  /**
   * Comprime una imagen existente en disco a un tamaño máximo en KB
   * 
   * NOTA: Los GIFs son excluidos automáticamente ya que no son compatibles
   * con la compresión WebP debido a su paleta de colores.
   * 
   * @param string $sourcePath Ruta de la imagen original
   * @param string $destPath Ruta de destino (puede ser la misma)
   * @param int $maxKb Tamaño máximo en KB
   * @param string|null $outputFormat Formato de salida (null = detectar del destino)
   * @return bool|null Éxito de la operación, null si el formato fue excluido (GIF)
   */
  public static function compressExistingImage(
    string $sourcePath,
    string $destPath,
    int $maxKb,
    ?string $outputFormat = null
  ): bool|null {
    if (!file_exists($sourcePath)) {
      return false;
    }

    $info = getimagesize($sourcePath);
    if ($info === false) {
      return false;
    }

    $mimeType = $info['mime'];
    $format = explode('/', $mimeType)[1] ?? '';

    // Excluir GIFs - no son compatibles con compresión WebP/AVIF
    // debido a su paleta de colores indexada
    if ($format === 'gif') {
      return null; // Indica que fue excluido, no es un error
    }

    // Determinar formato de salida
    if ($outputFormat === null) {
      $ext = strtolower(pathinfo($destPath, PATHINFO_EXTENSION));
      $outputFormat = $ext ?: 'webp';
    }

    // Crear instancia temporal para usar métodos privados
    $instance = new self('');

    $sourceImage = $instance->createImageFromAny($sourcePath, $format);
    if (!$sourceImage) {
      return false;
    }

    // Convertir imagen de paleta a truecolor si es necesario
    if (!imageistruecolor($sourceImage)) {
      $width = imagesx($sourceImage);
      $height = imagesy($sourceImage);
      $trueColorImage = imagecreatetruecolor($width, $height);

      // Preservar transparencia
      imagealphablending($trueColorImage, false);
      imagesavealpha($trueColorImage, true);
      $transparent = imagecolorallocatealpha($trueColorImage, 0, 0, 0, 127);
      imagefill($trueColorImage, 0, 0, $transparent);
      imagealphablending($trueColorImage, true);

      imagecopy($trueColorImage, $sourceImage, 0, 0, 0, 0, $width, $height);
      imagedestroy($sourceImage);
      $sourceImage = $trueColorImage;
    }

    $result = $instance->compressToMaxSize(
      $sourceImage,
      $destPath,
      $maxKb,
      80,
      $outputFormat
    );

    imagedestroy($sourceImage);

    return $result;
  }

  /**
   * Optimiza todas las imágenes de un directorio por lote.
   * Busca en el directorio especificado y procesa las imágenes
   * redimensionándolas y comprimiéndolas según los límites definidos.
   * 
   * @param string $directory Ruta del directorio a procesar.
   * @param string|null $destDirectory Ruta del directorio de destino (si es null se optimiza en el mismo directorio).
   * @param int|null $maxKb Límite de tamaño en KB para cada imagen (null = usar config).
   * @param string|null $outputFormat Formato de salida (null = usar config o webp).
   * @param bool $recursive Si se debe buscar en subdirectorios de forma recursiva.
   * @return array Resumen de la operación ['processed' => int, 'errors' => int, 'details' => array]
   */
  public static function optimizeDirectoryImages(
    string $directory,
    ?string $destDirectory = null,
    ?int $maxKb = null,
    ?string $outputFormat = null,
    bool $recursive = true
  ): array {
    $summary = ['processed' => 0, 'errors' => 0, 'details' => []];

    if (!is_dir($directory)) {
      return $summary;
    }

    if ($maxKb === null) {
      $maxKb = defined('MAX_IMAGE_KB') ? MAX_IMAGE_KB : 0;
    }

    if ($outputFormat === null || $outputFormat === '') {
      $outputFormat = (defined('OUTPUT_FORMAT') && OUTPUT_FORMAT !== '') ? OUTPUT_FORMAT : 'webp';
    }
    $outputFormat = strtolower($outputFormat);

    $inPlace = true;
    if ($destDirectory !== null) {
      $destDirectory = rtrim(str_replace('\\', '/', $destDirectory), '/');
      $srcDirNormalized = rtrim(str_replace('\\', '/', $directory), '/');
      if ($destDirectory !== $srcDirNormalized) {
        $inPlace = false;
        if (!is_dir($destDirectory)) {
          $oldUmask = umask(0);
          @mkdir($destDirectory, 0777, true);
          umask($oldUmask);
        }
      }
    }

    $flags = $recursive ? (\RecursiveDirectoryIterator::SKIP_DOTS) : null;
    
    $iterator = $recursive 
      ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, $flags))
      : new \DirectoryIterator($directory);

    $allowedExtensions = [];
    $admitted = defined('IMG_ADMITTED') ? IMG_ADMITTED : [];
    foreach ($admitted as $mime) {
      $ext = explode('/', $mime)[1] ?? '';
      if ($ext) {
        $allowedExtensions[] = strtolower($ext);
        if ($ext === 'jpeg') $allowedExtensions[] = 'jpg';
      }
    }
    if (empty($allowedExtensions)) {
      $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'avif', 'bmp', 'tiff', 'heic', 'heif'];
    }

    // Instancia temporal para acceder a helpers
    $processor = new self('');

    foreach ($iterator as $file) {
      if ($recursive && !$file->isFile()) continue;
      if (!$recursive && ($file->isDot() || !$file->isFile())) continue;

      $filePath = $file->getRealPath();
      $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

      if (in_array($ext, $allowedExtensions)) {
        try {
          if ($ext === 'gif') {
            if (class_exists('Imagick')) {
              $relativeSubDir = '';
              if ($recursive) {
                $relativeSubDir = str_replace(
                  rtrim(str_replace('\\', '/', $directory), '/'),
                  '',
                  rtrim(str_replace('\\', '/', pathinfo($filePath, PATHINFO_DIRNAME)), '/')
                );
              }

              $destPath = $inPlace 
                ? pathinfo($filePath, PATHINFO_DIRNAME) . '/' . pathinfo($filePath, PATHINFO_FILENAME) . '.' . $outputFormat
                : ($recursive 
                    ? $destDirectory . $relativeSubDir . '/' . pathinfo($filePath, PATHINFO_FILENAME) . '.' . $outputFormat
                    : $destDirectory . '/' . pathinfo($filePath, PATHINFO_FILENAME) . '.' . $outputFormat
                  );

              $destDirOnly = pathinfo($destPath, PATHINFO_DIRNAME) . '/';
              $tempModule = new self('', $destDirOnly);
              
              $tempModule->cant = 1;
              $tempModule->tmp = [$filePath];
              $tempModule->format = ['gif'];
              $tempModule->name = [pathinfo($destPath, PATHINFO_BASENAME)];
              
              if ($tempModule->processAnimatedGif(0)) {
                if ($inPlace && $filePath !== $destPath) {
                  unlink($filePath);
                }
                $summary['processed']++;
                $summary['details'][] = ['file' => $filePath, 'status' => 'success', 'new_path' => $destPath];
              } else {
                $summary['errors']++;
                $summary['details'][] = ['file' => $filePath, 'status' => 'error', 'message' => 'Error al procesar GIF animado con Imagick'];
              }
            } else {
              continue; // Saltamos si no hay Imagick
            }
            continue;
          }

          $info = @getimagesize($filePath);
          if (!$info) continue;

          $format = explode('/', $info['mime'])[1] ?? $ext;
          $sourceImage = $processor->createImageFromAny($filePath, $format);

          if (!$sourceImage) {
            $summary['errors']++;
            $summary['details'][] = ['file' => $filePath, 'status' => 'error', 'message' => 'No se pudo crear imagen'];
            continue;
          }

          $width = imagesx($sourceImage);
          $height = imagesy($sourceImage);

          // Calcular dimensiones y orientación óptimas
          $optimalDims = $processor->calculateOptimalDimensions($width, $height);
          $newWidth = $optimalDims['width'];
          $newHeight = $optimalDims['height'];

          $orientation = ($newWidth > $newHeight) ? 'landscape' : (($newWidth === $newHeight) ? 'square' : 'portrait');
          $quality = $processor->calculateOptimalQuality($newWidth, $orientation);

          $newImage = imagecreatetruecolor($newWidth, $newHeight);

          // Preservar transparencia
          if (in_array($ext, ['png', 'webp', 'avif']) || in_array($outputFormat, ['png', 'webp', 'avif'])) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
            imagefill($newImage, 0, 0, $transparent);
          }

          imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

          // Definir ruta de guardado
          if ($inPlace) {
            $destPath = pathinfo($filePath, PATHINFO_DIRNAME) . '/' . pathinfo($filePath, PATHINFO_FILENAME) . '.' . $outputFormat;
          } else {
            if ($recursive) {
              $relativeSubDir = str_replace(
                rtrim(str_replace('\\', '/', $directory), '/'),
                '',
                rtrim(str_replace('\\', '/', pathinfo($filePath, PATHINFO_DIRNAME)), '/')
              );
              $targetDir = $destDirectory . $relativeSubDir;
              if (!is_dir($targetDir)) {
                $oldUmask = umask(0);
                @mkdir($targetDir, 0777, true);
                umask($oldUmask);
              }
              $destPath = $targetDir . '/' . pathinfo($filePath, PATHINFO_FILENAME) . '.' . $outputFormat;
            } else {
              $destPath = $destDirectory . '/' . pathinfo($filePath, PATHINFO_FILENAME) . '.' . $outputFormat;
            }
          }

          if ($maxKb > 0) {
            $success = $processor->compressToMaxSize($newImage, $destPath, $maxKb, $quality, $outputFormat);
          } else {
            $success = $processor->saveImageAs($newImage, $destPath, $quality, $outputFormat);
          }

          imagedestroy($newImage);
          imagedestroy($sourceImage);

          if ($success) {
            if ($inPlace && $filePath !== $destPath) {
              unlink($filePath);
            }
            $summary['processed']++;
            $summary['details'][] = ['file' => $filePath, 'status' => 'success', 'new_path' => $destPath];
          } else {
            $summary['errors']++;
            $summary['details'][] = ['file' => $filePath, 'status' => 'error', 'message' => 'Fallo al guardar o comprimir'];
          }

        } catch (\Exception $e) {
          $summary['errors']++;
          $summary['details'][] = ['file' => $filePath, 'status' => 'error', 'message' => $e->getMessage()];
        }
      }
    }

    return $summary;
  }

  /**
   * Crea un thumbnail (miniatura) a partir de una imagen de origen, con opción de recortar (crop)
   * para ajustarla exactamente al ancho y alto requeridos (centrada).
   * 
   * @param string $sourcePath Ruta de la imagen original en disco
   * @param string $destPath Ruta donde se guardará la miniatura
   * @param int $thumbWidth Ancho de la miniatura
   * @param int $thumbHeight Alto de la miniatura
   * @param bool $crop Si es true, recorta la imagen desde el centro. Si es false, la escala proporcionalmente.
   * @param int $quality Calidad de compresión (0-100)
   * @param string|null $outputFormat Formato de salida (null = detectar del destino o usar webp)
   * @return bool Éxito de la operación
   */
  public static function createThumbnail(
    string $sourcePath,
    string $destPath,
    int $thumbWidth,
    int $thumbHeight,
    bool $crop = true,
    int $quality = 80,
    ?string $outputFormat = null
  ): bool {
    if (!file_exists($sourcePath)) {
      return false;
    }

    $info = @getimagesize($sourcePath);
    if ($info === false) {
      return false;
    }

    $mimeType = $info['mime'];
    $format = explode('/', $mimeType)[1] ?? '';
    $width = $info[0];
    $height = $info[1];

    if ($outputFormat === null) {
      $ext = strtolower(pathinfo($destPath, PATHINFO_EXTENSION));
      $outputFormat = $ext ?: 'webp';
    }

    $processor = new self('');
    $sourceImage = $processor->createImageFromAny($sourcePath, $format);
    if (!$sourceImage) {
      return false;
    }

    $thumbImage = imagecreatetruecolor($thumbWidth, $thumbHeight);

    // Preservar transparencia
    if (in_array($format, ['png', 'webp', 'avif']) || in_array($outputFormat, ['png', 'webp', 'avif'])) {
      imagealphablending($thumbImage, false);
      imagesavealpha($thumbImage, true);
      $transparent = imagecolorallocatealpha($thumbImage, 0, 0, 0, 127);
      imagefill($thumbImage, 0, 0, $transparent);
      imagealphablending($thumbImage, true);
    }

    if ($crop) {
      $sourceRatio = $width / $height;
      $thumbRatio = $thumbWidth / $thumbHeight;

      if ($sourceRatio > $thumbRatio) {
        $srcHeight = $height;
        $srcWidth = (int) ($height * $thumbRatio);
        $srcX = (int) (($width - $srcWidth) / 2);
        $srcY = 0;
      } else {
        $srcWidth = $width;
        $srcHeight = (int) ($width / $thumbRatio);
        $srcX = 0;
        $srcY = (int) (($height - $srcHeight) / 2);
      }

      $success = imagecopyresampled(
        $thumbImage,
        $sourceImage,
        0, 0,
        $srcX, $srcY,
        $thumbWidth, $thumbHeight,
        $srcWidth, $srcHeight
      );
    } else {
      $ratio = min($thumbWidth / $width, $thumbHeight / $height);
      $newWidth = (int) ($width * $ratio);
      $newHeight = (int) ($height * $ratio);

      $dstX = (int) (($thumbWidth - $newWidth) / 2);
      $dstY = (int) (($thumbHeight - $newHeight) / 2);

      $success = imagecopyresampled(
        $thumbImage,
        $sourceImage,
        $dstX, $dstY,
        0, 0,
        $newWidth, $newHeight,
        $width, $height
      );
    }

    if ($success) {
      $success = $processor->saveImageAs($thumbImage, $destPath, $quality, $outputFormat);
    }

    imagedestroy($thumbImage);
    imagedestroy($sourceImage);

    return $success;
  }
}
