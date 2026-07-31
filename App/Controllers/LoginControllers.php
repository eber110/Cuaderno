<?php

namespace App\Controllers;

use App\Models\LoginModels;
use Base\Control\Control;
use Base\Module\DateTimeModule;
use Base\Module\LogModule;
use Base\Module\ResponseModule;
use Base\Module\Session;
use Base\Module\ValidatorModule;

/**
 * Clase LoginControllers
 * 
 * Controlador encargado de la autenticación de usuarios, registro y cierre de sesión.
 * Cumple con SRP realizando validaciones y orquestación HTTP.
 */
class LoginControllers extends Control {

  /**
   * Muestra la vista principal del formulario de inicio de sesión.
   *
   * @return mixed Renderizado de la vista Login.index.
   */
  public function login() {
    return $this->view("Login.index");
  }

  /**
   * Procesa los datos recibidos del formulario de inicio de sesión.
   *
   * @param array|string $requestData Datos enviados vía POST.
   * @return mixed Redirección al panel del usuario o mensaje de error.
   */
  public function processLogin(array|string $requestData) {
    if (is_array($requestData)) {
      foreach ($requestData as $value) {
        if (!$value) {
          return ResponseModule::redirect("/ingresar", "Debes rellenar todos los datos", 2);
        }
      }
    }

    $username = mb_strtolower($requestData["username"] ?? "", "UTF-8");
    $pass     = $requestData["pass"] ?? "";

    $userExists = new LoginModels();
    $userTrue   = $userExists->loginApp($username, $pass);

    // Límite de intentos de login
    if (!$userTrue[0] && ($userTrue[1] ?? "") === "rate_limited") {
      $timeAgo    = DateTimeModule::countdown($userTrue[2] ?? 0);
      $timeAgoSec = ($timeAgo["sec"] != 0) ? "{$timeAgo['sec']} segundos" : "";
      $timeAgoMin = ($timeAgo["min"] != 0) ? "{$timeAgo['min']} minutos con " : "";
      $timeMsg    = "inténtalo nuevamente en {$timeAgoMin}{$timeAgoSec}";
      return ResponseModule::redirect("/ingresar", "Has sobrepasado los intentos para ingresar a tu cuenta<br>{$timeMsg}");
    }

    if (!$userTrue[0]) {
      if (($userTrue[1] ?? null) === 1) return ResponseModule::redirect("/ingresar", "El usuario no es valido");
      if (($userTrue[1] ?? null) === 0) return ResponseModule::redirect("/ingresar", "La contraseña no es valida");
    } else {
      if (!($userTrue["encrypted"] ?? true)) return ResponseModule::redirect("/", "Tú contraseña no esta encriptada", 1);
    }

    return ResponseModule::redirect("/panel/" . $username);
  }

  /**
   * Muestra la vista principal del formulario de registro de usuarios.
   *
   * @return mixed Renderizado de la vista Register.index.
   */
  public function register() {
    return $this->view("Register.index");
  }

  /**
   * Procesa la comprobación de disponibilidad de un nombre de usuario vía AJAX.
   *
   * @param array|string $requestData Petición AJAX con el nombre de usuario.
   * @return mixed Respuesta en formato JSON.
   */
  public function checkUsername(array|string $requestData) {
    $username = $requestData["username"] ?? "";

    if ($username === "") {
      return $this->json(["status" => "error", "message" => "El nombre de usuario es obligatorio."], 400);
    }

    $userNameVal = ValidatorModule::camp($username, [
      "min_length" => [4, "El nombre de usuario debe tener al menos 4 letras"],
      "space"      => [false, "El nombre de usuario no debe contener espacios"]
    ]);

    if (!$userNameVal[0]) {
      return $this->json(["status" => "error", "message" => $userNameVal[1]], 400);
    }

    $usernameClean = mb_strtolower($username, "UTF-8");
    $model = new LoginModels();

    try {
      $exists = $model->checkUserExists($usernameClean);
      if ($exists) {
        return $this->json(["status" => "taken", "message" => "El nombre de usuario ya está en uso."]);
      } else {
        return $this->json(["status" => "available", "message" => "El nombre de usuario está disponible."]);
      }
    } catch (\Exception $e) {
      $matches = [];
      if (strpos($e->getMessage(), "Rate limit exceeded") !== false) {
        preg_match('/Try again in (\d+) seconds/', $e->getMessage(), $matches);
        $seconds = isset($matches[1]) ? intval($matches[1]) : 300;
        return $this->json([
          "status"  => "rate_limited",
          "message" => "Límite de intentos superado. Inténtalo de nuevo en " . $seconds . " segundos.",
          "seconds" => $seconds
        ], 429);
      }
      return $this->json(["status" => "error", "message" => "Error interno del servidor: " . $e->getMessage()], 500);
    }
  }

  /**
   * Procesa la comprobación de disponibilidad de un correo electrónico vía AJAX.
   *
   * @param array|string $requestData Petición AJAX con el correo.
   * @return mixed Respuesta en formato JSON.
   */
  public function checkEmail(array|string $requestData) {
    $email = $requestData["email"] ?? "";

    if ($email === "") {
      return $this->json(["status" => "error", "message" => "El correo electrónico es obligatorio."], 400);
    }

    $emailVal = ValidatorModule::camp($email, [
      "email" => [true, "El formato de correo electrónico no es válido"]
    ]);

    if (!$emailVal[0]) {
      return $this->json(["status" => "error", "message" => $emailVal[1]], 400);
    }

    $model = new LoginModels();
    try {
      $exists = $model->checkEmailExists($email);
      if ($exists) {
        return $this->json(["status" => "taken", "message" => "El correo electrónico ya está registrado."]);
      } else {
        return $this->json(["status" => "available", "message" => "El correo electrónico está disponible."]);
      }
    } catch (\Exception $e) {
      return $this->json(["status" => "error", "message" => "Error interno del servidor: " . $e->getMessage()], 500);
    }
  }

  /**
   * Procesa el registro de un nuevo usuario en la base de datos y caché.
   *
   * @param array|string $requestData Datos recibidos del formulario de registro.
   * @return mixed Redirección al login con mensaje o vista de error.
   */
  public function processRegister(array|string $requestData) {
    $username = $requestData["username"] ?? "";
    $email    = $requestData["email"] ?? "";
    $pass     = $requestData["pass"] ?? "";
    $repass   = $requestData["repass"] ?? "";

    if ($username === "" || $email === "" || $pass === "" || $repass === "") {
      return ResponseModule::redirect("/registrar", "Debes rellenar todos los datos", 2);
    }

    // Validación de usuario
    $userNameVal = ValidatorModule::camp($username, [
      "min_length" => [4, "El nombre de usuario debe tener al menos 4 letras"],
      "space"      => [false, "El nombre de usuario no debe contener espacios"]
    ]);
    if (!$userNameVal[0]) {
      return ResponseModule::redirect("/registrar", $userNameVal[1], 2);
    }

    // Validación de email
    $emailVal = ValidatorModule::camp($email, [
      "email" => [true, "El formato de correo electrónico no es válido"]
    ]);
    if (!$emailVal[0]) {
      return ResponseModule::redirect("/registrar", $emailVal[1], 2);
    }

    // Validación de contraseña
    $passVal = ValidatorModule::camp($pass, [
      "min_length"  => [8, "La contraseña debe tener al menos 8 caracteres"],
      "min_capital" => [1, "La contraseña debe tener al menos 1 mayúscula"],
      "min_number"  => [1, "La contraseña debe tener al menos 1 número"]
    ]);
    if (!$passVal[0]) {
      return ResponseModule::redirect("/registrar", $passVal[1], 2);
    }

    if ($pass !== $repass) {
      return ResponseModule::redirect("/registrar", "Las contraseñas no coinciden", 2);
    }

    $usernameClean = mb_strtolower($username, "UTF-8");
    $emailClean    = mb_strtolower($email, "UTF-8");

    $model = new LoginModels();
    
    // Verificación de duplicados en la base de datos
    $userExists = $model->where("username", $usernameClean)->get_one();
    if ($userExists) {
      return ResponseModule::redirect("/registrar", "El nombre de usuario ya está en uso", 2);
    }

    $emailExists = $model->where("email", $emailClean)->get_one();
    if ($emailExists) {
      return ResponseModule::redirect("/registrar", "El correo electrónico ya está registrado", 2);
    }

    try {
      $insertId = $model->create([
        "username"      => $usernameClean,
        "email"         => $emailClean,
        "password_hash" => password_hash($pass, PASSWORD_DEFAULT),
        "user_status"   => "active"
      ], "index_user");

      if ($insertId) {
        LogModule::simpleLog([
          "dir"     => ROOT_PATH . "/Cache/Users",
          "name"    => "userList",
          "content" => $usernameClean
        ]);
        return ResponseModule::redirect("/ingresar", "¡Registro exitoso! Ya puedes iniciar sesión.", 0);
      } else {
        return ResponseModule::redirect("/registrar", "Hubo un error al registrar al usuario. Inténtelo más tarde.", 2);
      }
    } catch (\Exception $e) {
      return ResponseModule::redirect("/registrar", "Error en el registro: " . $e->getMessage(), 2);
    }
  }

  /**
   * Cierra la sesión activa del usuario y redirige al inicio.
   *
   * @return void
   */
  public function exitApp(): void {
    Session::session_end_all();
    ResponseModule::redirect("/");
  }

}