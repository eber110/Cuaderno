<?php

namespace Core\ConfigLoader;

class Load{
  
  /*
  solo carga de un archivo a la ves, para que queden disponible en la aplicación
  sirve para especificar el orden de carga de los ficheros.
  */
  public static function loader($rute){

    if (file_exists($rute . '.php')) {
      
      require_once $rute . '.php';
      
      return;

    }else{

      print $rute.'.php'.'<span style="color:red; font-weight:bold;"> El archivo invocado no existe. Verifique la ruta en load.php</span><br>';

    }

  }

  /*
  Con este método, cargamos los ficheros automáticamente cuando se han
  creando.
  Muy util para cargar los controladores y modelos, en una carga por lote.
  */
  public static function ruteDir($rute){

    $dir = $rute;
    $file = scandir($dir);
    $file = array_values($file);
    
    foreach ($file as $files) {

      if (!is_dir($files)) {
        //var_dump($files);
        $new_file[] = $files;

      }else{

        $new_file = null;

      }

    }

    //si no se encuentra algún archivo, el programa para su funcionamiento aquí.
    if (is_null($new_file)) {

      return;

    }
    
    $file = $new_file;

    foreach ($file as $arch) {

      if (strpos($arch, 'php')) {

        //print $rute.$arch.'<br>';  
        require_once $rute . $arch;

      }

    }

  }

}