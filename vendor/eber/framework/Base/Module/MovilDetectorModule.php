<?php

namespace Base\Module;

class MovilDetectorModule{

  private static function movilDetector() {

    // Obtenemos el User-Agent enviado por el navegador
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
  
    // Expresión regular que cubre la mayoría de dispositivos móviles
    $patron = "/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i";

    return preg_match($patron, $userAgent);

  }
  
  /**
   * Esta función devuelve dos parámetros para verificar si se usa el sitio web 
   * en un navegador o un dispositivo movil
   * @return 1 -> es un móvil.
   * @return 2 -> es un navegador
   */
  public static function is_movil(){
    
    if (self::movilDetector()) {

      return 1;

    }else{

      return 2;

    }
  
  }

}