<?php

namespace App\Controllers;

use App\Models\DesignModels;
use App\Models\LemonSqueezyModels;
use App\Models\LoginModels;
use App\Models\SubscriptionModels;
use App\Models\UserModels;
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
   * Nombres de usuario reservados por el sistema para evitar colisiones con rutas públicas.
   */
  private const RESERVED_USERNAMES = [
    "panel",
    "ingresar",
    "registrar",
    "recuperar",
    "restablecer",
    "verificar",
    "verificar-cuenta",
    "salir",
    "planes",
    "suscripcion",
    "lemon-squeezy",
    "proxy",
    "op",
    "admin",
    "administrador",
    "root",
    "api",
    "auth",
    "login",
    "register",
    "logout",
    "soporte",
    "support",
    "ayuda",
    "help",
    "clikhub",
    "clickhub",
    "cuaderno",
    "terminos",
    "privacidad",
    "robots.txt",
    "sitemap.xml",
    "llms.txt",
    "app",
    "uploads",
    "cache",
    "logs",
    "database",
    "vendor",
    "public",
    "assets",
    "static",
    "config",
    "settings",
    "dashboard"
  ];

  /**
   * Valida formato, longitud, caracteres permitidos y slugs reservados del nombre de usuario.
   *
   * @param string $username Nombre de usuario ingresado.
   * @return array{0: bool, 1: string} [true, usuario limpio] o [false, mensaje de error].
   */
  private function validateUsername(string $username): array {
    $clean = mb_strtolower(trim($username), "UTF-8");

    if ($clean === "") {
      return [false, "El nombre de usuario es obligatorio."];
    }

    if (strlen($clean) < 4) {
      return [false, "El nombre de usuario debe tener al menos 4 caracteres."];
    }

    if (strlen($clean) > 30) {
      return [false, "El nombre de usuario no puede superar los 30 caracteres."];
    }

    if (preg_match("/\s/", $clean)) {
      return [false, "El nombre de usuario no debe contener espacios."];
    }

    if (!preg_match("/^[a-z0-9._-]+$/", $clean)) {
      return [false, "El nombre de usuario solo puede contener letras minúsculas, números, puntos, guiones y guiones bajos."];
    }

    if (str_starts_with($clean, ".") || str_ends_with($clean, ".") || str_contains($clean, "..")) {
      return [false, "El nombre de usuario no puede comenzar ni terminar con punto ni contener puntos consecutivos."];
    }

    if (in_array($clean, self::RESERVED_USERNAMES, true)) {
      return [false, "Ese nombre de usuario está reservado por el sistema. Elige otro."];
    }

    return [true, $clean];
  }

  /**
   * Formatea un tiempo de espera en segundos a texto legible sin etiquetas HTML.
   *
   * @param int $seconds Segundos restantes de bloqueo.
   * @return string Texto formateado.
   */
  private function formatCountdown(int $seconds): string {
    $timeAgo = DateTimeModule::countdown(max(0, $seconds));
    $min = (int)($timeAgo["min"] ?? 0);
    $sec = (int)($timeAgo["sec"] ?? 0);

    if ($min > 0 && $sec > 0) {
      return "{$min} minutos y {$sec} segundos";
    }

    if ($min > 0) {
      return "{$min} minutos";
    }

    return "{$sec} segundos";
  }

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
    if (!is_array($requestData)) {
      return ResponseModule::redirect("/ingresar", "Solicitud inválida.", 2);
    }

    $identifier = trim((string)($requestData["username"] ?? ""));
    $pass       = (string)($requestData["pass"] ?? "");

    if ($identifier === "" || $pass === "") {
      return ResponseModule::redirect("/ingresar", "Debes rellenar todos los datos", 2);
    }

    if (strlen($pass) > 128) {
      return ResponseModule::redirect("/ingresar", "El usuario o la contraseña ingresada no es válida.", 2);
    }

    $identifierClean = mb_strtolower($identifier, "UTF-8");

    $userExists = new LoginModels();
    $userTrue   = $userExists->loginApp($identifierClean, $pass);

    // Límite de intentos de login
    if (!$userTrue[0] && ($userTrue[1] ?? "") === "rate_limited") {
      $timeMsg = $this->formatCountdown((int)($userTrue[2] ?? 0));
      return ResponseModule::redirect("/ingresar", "Has sobrepasado los intentos para ingresar a tu cuenta. Inténtalo nuevamente en {$timeMsg}.", 2);
    }

    if (!$userTrue[0]) {
      return ResponseModule::redirect("/ingresar", "El usuario o la contraseña ingresada no es válida.", 2);
    }

    if (!Session::session_active()) {
      return ResponseModule::redirect("/ingresar", "No se pudo iniciar la sesión. Inténtalo nuevamente.", 2);
    }

    $userStatus = (string)(Session::session_data("user_status") ?? "active");
    if ($userStatus !== "" && $userStatus !== "active") {
      Session::session_end_all();
      return ResponseModule::redirect("/ingresar", "Tu cuenta no está activa. Verifica tu correo electrónico o contacta soporte.", 2);
    }

    $userClean = trim((string)Session::session_data("username"));
    if ($userClean === "") {
      Session::session_end_all();
      return ResponseModule::redirect("/ingresar", "No se pudo validar tu perfil de usuario. Inténtalo nuevamente.", 2);
    }

    $userId = Session::session_data("user_id");
    $sub    = LemonSqueezyModels::getSubscriptionByUser($userId);
    if (!empty($sub)) {
      LemonSqueezyModels::updateUserPremiumCache($userClean, $sub);
    } else {
      LemonSqueezyModels::updateUserPremiumCache($userClean, ["status" => "inactive"]);
    }

    return ResponseModule::redirect("/panel/" . rawurlencode($userClean));
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
    if (!is_array($requestData)) {
      return $this->json(["status" => "error", "message" => "Solicitud inválida."], 400);
    }

    $userNameVal = $this->validateUsername((string)($requestData["username"] ?? ""));
    if (!$userNameVal[0]) {
      return $this->json(["status" => "error", "message" => $userNameVal[1]], 400);
    }

    $usernameClean = $userNameVal[1];
    $model = new LoginModels();

    $exists = $model->checkUserExists($usernameClean);

    if (is_array($exists) && isset($exists["rate_limited"])) {
      $seconds = (int)($exists["seconds"] ?? 300);
      return $this->json([
        "status"  => "rate_limited",
        "message" => "Límite de intentos superado. Inténtalo de nuevo en " . $seconds . " segundos.",
        "seconds" => $seconds
      ], 429);
    }

    if ($exists) {
      return $this->json(["status" => "taken", "message" => "El nombre de usuario ya está en uso."]);
    } else {
      return $this->json(["status" => "available", "message" => "El nombre de usuario está disponible."]);
    }
  }

  /**
   * Procesa la comprobación de disponibilidad de un correo electrónico vía AJAX.
   *
   * @param array|string $requestData Petición AJAX con el correo.
   * @return mixed Respuesta en formato JSON.
   */
  public function checkEmail(array|string $requestData) {
    if (!is_array($requestData)) {
      return $this->json(["status" => "error", "message" => "Solicitud inválida."], 400);
    }

    $email = mb_strtolower(trim((string)($requestData["email"] ?? "")), "UTF-8");

    if ($email === "") {
      return $this->json(["status" => "error", "message" => "El correo electrónico es obligatorio."], 400);
    }

    $emailVal = ValidatorModule::camp($email, [
      "max_length" => [255, "El correo electrónico es demasiado largo"],
      "email"      => [true, "El formato de correo electrónico no es válido"]
    ]);

    if (!$emailVal[0]) {
      return $this->json(["status" => "error", "message" => $emailVal[1]], 400);
    }

    $model = new LoginModels();
    $exists = $model->checkEmailExists($email);

    if (is_array($exists) && isset($exists["rate_limited"])) {
      $seconds = (int)($exists["seconds"] ?? 300);
      return $this->json([
        "status"  => "rate_limited",
        "message" => "Límite de intentos superado. Inténtalo de nuevo en " . $seconds . " segundos.",
        "seconds" => $seconds
      ], 429);
    }

    if ($exists) {
      return $this->json(["status" => "taken", "message" => "El correo electrónico ya está registrado."]);
    } else {
      return $this->json(["status" => "available", "message" => "El correo electrónico está disponible."]);
    }
  }

  /**
   * Procesa el registro de un nuevo usuario en la base de datos y caché.
   *
   * @param array|string $requestData Datos recibidos del formulario de registro.
   * @return mixed Redirección al login con mensaje o vista de error.
   */
  public function processRegister(array|string $requestData) {
    if (!is_array($requestData)) {
      return ResponseModule::redirect("/registrar", "Solicitud inválida.", 2);
    }

    $username = (string)($requestData["username"] ?? "");
    $email    = (string)($requestData["email"] ?? "");
    $pass     = (string)($requestData["pass"] ?? "");
    $repass   = (string)($requestData["repass"] ?? "");

    if (trim($username) === "" || trim($email) === "" || $pass === "" || $repass === "") {
      return ResponseModule::redirect("/registrar", "Debes rellenar todos los datos", 2);
    }

    $model = new LoginModels();

    // Verificación de límite de tasa para el registro
    $rateCheck = $model->checkSubmitRegisterRate(10, 300);
    if ($rateCheck !== true) {
      $timeMsg = $this->formatCountdown((int)$rateCheck);
      return ResponseModule::redirect("/registrar", "Has sobrepasado los intentos para registrarte. Inténtalo nuevamente en {$timeMsg}.", 2);
    }

    // Validación de usuario
    $userNameVal = $this->validateUsername($username);
    if (!$userNameVal[0]) {
      return ResponseModule::redirect("/registrar", $userNameVal[1], 2);
    }
    $usernameClean = $userNameVal[1];

    // Validación de email
    $emailClean = mb_strtolower(trim($email), "UTF-8");
    $emailVal = ValidatorModule::camp($emailClean, [
      "max_length" => [255, "El correo electrónico es demasiado largo"],
      "email"      => [true, "El formato de correo electrónico no es válido"]
    ]);
    if (!$emailVal[0]) {
      return ResponseModule::redirect("/registrar", $emailVal[1], 2);
    }

    // Validación de contraseña
    $passVal = ValidatorModule::camp($pass, [
      "min_length"  => [8, "La contraseña debe tener al menos 8 caracteres"],
      "max_length"  => [128, "La contraseña no puede superar los 128 caracteres"],
      "min_capital" => [1, "La contraseña debe tener al menos 1 mayúscula"],
      "min_number"  => [1, "La contraseña debe tener al menos 1 número"]
    ]);
    if (!$passVal[0]) {
      return ResponseModule::redirect("/registrar", $passVal[1], 2);
    }

    if ($pass !== $repass) {
      return ResponseModule::redirect("/registrar", "Las contraseñas no coinciden", 2);
    }

    // Verificación de duplicados en la base de datos vía modelo
    if ($model->existsByUsername($usernameClean)) {
      return ResponseModule::redirect("/registrar", "El nombre de usuario ya está en uso", 2);
    }

    if ($model->existsByEmail($emailClean)) {
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
        $model->clearRegistrationRateLimits();
        LogModule::simpleLog([
          "dir"     => ROOT_PATH . "/Cache/Users",
          "name"    => "userList",
          "content" => $usernameClean
        ]);
        DesignModels::createInitialDesign($usernameClean);

        // Inicializar suscripción base Free (1) para el nuevo usuario
        $indexUser = UserModels::getIndexUserByUsername($usernameClean) ?? (string)$insertId;
        SubscriptionModels::initializeFreeSubscription($indexUser, (string)$insertId, $usernameClean);

        return ResponseModule::redirect("/ingresar", "¡Registro exitoso! Ya puedes iniciar sesión.", 0);
      } else {
        return ResponseModule::redirect("/registrar", "Hubo un error al registrar al usuario. Inténtelo más tarde.", 2);
      }
    } catch (\Exception $e) {
      error_log("Error en registro de usuario: " . $e->getMessage());
      return ResponseModule::redirect("/registrar", "Ocurrió un error inesperado al procesar el registro. Por favor, inténtelo de nuevo más tarde.", 2);
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