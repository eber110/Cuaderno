<?php

namespace App\Controllers;

use App\Models\DesignModels;
use App\Models\LemonSqueezyModels;
use App\Models\LoginModels;
use App\Models\SubscriptionModels;
use App\Models\UserModels;
use App\Rsc\Helper\AuthMailHelper;
use Base\Control\Control;
use Base\Module\DateTimeModule;
use Base\Module\LogModule;
use Base\Module\ResponseModule;
use Base\Module\Session;
use Base\Module\ValidatorModule;

/**
 * Clase LoginControllers
 * 
 * Controlador encargado de la autenticación de usuarios, registro, verificación de correo,
 * recuperación de contraseña y cierre de sesión.
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
    "verificar-pendiente",
    "verificar-reenviar",
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
   * Asegura que la sesión PHP esté iniciada para almacenar marcas anti-bot o avisos temporales.
   *
   * @return void
   */
  private function ensureSessionStarted(): void {
    if (session_status() === PHP_SESSION_NONE) {
      @session_start();
    }
  }

  /**
   * Construye una URL absoluta a partir de una ruta relativa del sitio.
   *
   * @param string $path Ruta relativa (ej. "verificar-cuenta?token=...").
   * @return string URL absoluta.
   */
  private function buildAbsoluteUrl(string $path): string {
    $base = defined("DOMAIN") ? (string)DOMAIN : "/";
    return rtrim($base, "/") . "/" . ltrim($path, "/");
  }

  /**
   * Inicializa el diseño base, suscripción Free y log de usuario cuando la cuenta queda activa.
   *
   * @param int|string $userId ID numérico del usuario.
   * @param string $username Nombre de usuario normalizado.
   * @return void
   */
  private function initializeVerifiedUserResources(int|string $userId, string $username): void {
    LogModule::simpleLog([
      "dir"     => ROOT_PATH . "/Cache/Users",
      "name"    => "userList",
      "content" => $username
    ]);
    DesignModels::createInitialDesign($username);

    $indexUser = UserModels::getIndexUserByUsername($username) ?? (string)$userId;
    SubscriptionModels::initializeFreeSubscription($indexUser, (string)$userId, $username);
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
    if ($userStatus === "pending") {
      $pendingEmail = (string)(Session::session_data("email") ?? "");
      Session::session_end_all();
      $this->ensureSessionStarted();
      $_SESSION["pending_verify_email"] = $pendingEmail;
      return ResponseModule::redirect(
        "/verificar-pendiente",
        "Debes verificar tu correo electrónico antes de iniciar sesión. Revisa tu bandeja o solicita un nuevo enlace.",
        2
      );
    }

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
    $this->ensureSessionStarted();
    $_SESSION["register_form_ts"] = time();
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
   * Procesa el registro de un nuevo usuario en estado pendiente y envía el enlace de verificación.
   *
   * @param array|string $requestData Datos recibidos del formulario de registro.
   * @return mixed Redirección a la pantalla de verificación pendiente o error.
   */
  public function processRegister(array|string $requestData) {
    if (!is_array($requestData)) {
      return ResponseModule::redirect("/registrar", "Solicitud inválida.", 2);
    }

    $this->ensureSessionStarted();

    // Protección anti-bots: honeypot y tiempo mínimo de llenado
    $honeypot = trim((string)($requestData["website"] ?? ""));
    if ($honeypot !== "") {
      return ResponseModule::redirect("/registrar", "No se pudo completar el registro. Inténtalo nuevamente.", 2);
    }

    if (isset($_SESSION["register_form_ts"]) && (time() - (int)$_SESSION["register_form_ts"]) < 2) {
      return ResponseModule::redirect("/registrar", "No se pudo completar el registro. Inténtalo nuevamente.", 2);
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

    // Purgar cuentas pendientes abandonadas (> 24h) para liberar nombres acaparados por bots
    $model->purgeExpiredPendingUsers(24);

    // Verificación de duplicados en la base de datos vía modelo
    if ($model->existsByUsername($usernameClean)) {
      return ResponseModule::redirect("/registrar", "El nombre de usuario ya está en uso", 2);
    }

    if ($model->existsByEmail($emailClean)) {
      return ResponseModule::redirect("/registrar", "El correo electrónico ya está registrado", 2);
    }

    try {
      $rawToken  = bin2hex(random_bytes(32));
      $tokenHash = hash("sha256", $rawToken);
      $expiresAt = date("Y-m-d H:i:s", time() + 3600);

      $insertId = $model->create([
        "username"                            => $usernameClean,
        "email"                               => $emailClean,
        "password_hash"                       => password_hash($pass, PASSWORD_DEFAULT),
        "user_status"                         => "pending",
        "email_verification_token"            => $tokenHash,
        "email_verification_token_expires_at" => $expiresAt
      ], "index_user");

      if ($insertId) {
        $model->clearRegistrationRateLimits();
        unset($_SESSION["register_form_ts"]);
        $_SESSION["pending_verify_email"] = $emailClean;

        $verifyUrl  = $this->buildAbsoluteUrl("verificar-cuenta?token=" . urlencode($rawToken));
        $mailResult = AuthMailHelper::sendVerificationEmail($emailClean, $usernameClean, $verifyUrl);

        if ($mailResult["success"]) {
          return ResponseModule::redirect(
            "/verificar-pendiente",
            "¡Casi listo! Te enviamos un enlace de verificación a tu correo. Haz clic en él para activar tu cuenta.",
            0
          );
        }

        error_log("Error enviando correo de verificación a {$emailClean}: " . ($mailResult["error"] ?? "desconocido"));
        return ResponseModule::redirect(
          "/verificar-pendiente",
          "Tu cuenta fue creada, pero no pudimos enviar el correo ahora. Intenta reenviar el enlace.",
          2
        );
      } else {
        return ResponseModule::redirect("/registrar", "Hubo un error al registrar al usuario. Inténtelo más tarde.", 2);
      }
    } catch (\Exception $e) {
      error_log("Error en registro de usuario: " . $e->getMessage());
      return ResponseModule::redirect("/registrar", "Ocurrió un error inesperado al procesar el registro. Por favor, inténtelo de nuevo más tarde.", 2);
    }
  }

  /**
   * Muestra la pantalla informativa de verificación pendiente con opción de reenvío.
   *
   * @return mixed Renderizado de la vista Register.verifyPending.
   */
  public function verifyPending() {
    $this->ensureSessionStarted();
    $pendingEmail = (string)($_SESSION["pending_verify_email"] ?? "");
    return $this->view("Register.verifyPending", [
      "pendingEmail" => $pendingEmail
    ]);
  }

  /**
   * Procesa la solicitud de reenvío del enlace de verificación de cuenta.
   *
   * @param array|string $requestData Datos enviados vía POST.
   * @return mixed Redirección a /verificar-pendiente con mensaje neutro o de rate limit.
   */
  public function resendVerification(array|string $requestData) {
    if (!is_array($requestData)) {
      return ResponseModule::redirect("/verificar-pendiente", "Solicitud inválida.", 2);
    }

    $emailClean = mb_strtolower(trim((string)($requestData["email"] ?? "")), "UTF-8");
    $emailVal   = ValidatorModule::camp($emailClean, [
      "max_length" => [255, "El correo electrónico es demasiado largo"],
      "email"      => [true, "El formato de correo electrónico no es válido"]
    ]);

    if (!$emailVal[0]) {
      return ResponseModule::redirect("/verificar-pendiente", $emailVal[1], 2);
    }

    $this->ensureSessionStarted();
    $_SESSION["pending_verify_email"] = $emailClean;

    $model     = new LoginModels();
    $rateCheck = $model->checkResendVerificationRate($emailClean);
    if ($rateCheck !== true) {
      $timeMsg = $this->formatCountdown((int)$rateCheck);
      return ResponseModule::redirect(
        "/verificar-pendiente",
        "Debes esperar {$timeMsg} antes de solicitar otro enlace de verificación.",
        2
      );
    }

    $user = $model->findUserByEmail($emailClean);
    if ($user && ($user["user_status"] ?? "") === "pending" && empty($user["deleted_at_user"])) {
      $rawToken  = bin2hex(random_bytes(32));
      $tokenHash = hash("sha256", $rawToken);
      $expiresAt = date("Y-m-d H:i:s", time() + 3600);

      $model->saveEmailVerificationToken($user["user_id"], $tokenHash, $expiresAt);

      $verifyUrl  = $this->buildAbsoluteUrl("verificar-cuenta?token=" . urlencode($rawToken));
      $mailResult = AuthMailHelper::sendVerificationEmail($emailClean, (string)$user["username"], $verifyUrl);

      if (!$mailResult["success"]) {
        error_log("Error reenviando verificación a {$emailClean}: " . ($mailResult["error"] ?? "desconocido"));
      }
    }

    return ResponseModule::redirect(
      "/verificar-pendiente",
      "Si el correo tiene una verificación pendiente, enviamos un nuevo enlace. Revisa tu bandeja de entrada o spam.",
      0
    );
  }

  /**
   * Valida el token de verificación de 1 clic, activa la cuenta e inicializa sus recursos.
   *
   * @return mixed Redirección a /ingresar si fue exitoso o a /verificar-pendiente si falló.
   */
  public function verifyEmail() {
    $rawToken = trim((string)($_GET["token"] ?? ""));

    if (preg_match("/^[a-f0-9]{64}$/", $rawToken) !== 1) {
      return ResponseModule::redirect(
        "/verificar-pendiente",
        "El enlace de verificación no es válido o ha expirado. Puedes solicitar uno nuevo.",
        2
      );
    }

    $model     = new LoginModels();
    $tokenHash = hash("sha256", $rawToken);
    $user      = $model->findByVerificationTokenHash($tokenHash);

    if (!$user || ($user["user_status"] ?? "") !== "pending" || !empty($user["deleted_at_user"])) {
      return ResponseModule::redirect(
        "/verificar-pendiente",
        "El enlace de verificación no es válido o ha expirado. Puedes solicitar uno nuevo.",
        2
      );
    }

    $expiresRaw = (string)($user["email_verification_token_expires_at"] ?? "");
    $expiresTs  = $expiresRaw !== "" ? strtotime($expiresRaw) : false;
    if ($expiresTs === false || $expiresTs < time()) {
      return ResponseModule::redirect(
        "/verificar-pendiente",
        "El enlace de verificación no es válido o ha expirado. Puedes solicitar uno nuevo.",
        2
      );
    }

    $model->activateVerifiedUser($user["user_id"]);
    $this->initializeVerifiedUserResources($user["user_id"], (string)$user["username"]);

    $this->ensureSessionStarted();
    unset($_SESSION["pending_verify_email"]);

    return ResponseModule::redirect("/ingresar", "¡Correo verificado con éxito! Ya puedes iniciar sesión.", 0);
  }

  /**
   * Muestra el formulario para solicitar la recuperación de contraseña.
   *
   * @return mixed Renderizado de la vista Login.forgotPassword.
   */
  public function forgotPassword() {
    return $this->view("Login.forgotPassword");
  }

  /**
   * Procesa la solicitud de recuperación de contraseña aplicando rate limit y respuesta neutra.
   *
   * @param array|string $requestData Datos enviados vía POST.
   * @return mixed Redirección a /recuperar.
   */
  public function sendPasswordReset(array|string $requestData) {
    if (!is_array($requestData)) {
      return ResponseModule::redirect("/recuperar", "Solicitud inválida.", 2);
    }

    $emailClean = mb_strtolower(trim((string)($requestData["email"] ?? "")), "UTF-8");
    $emailVal   = ValidatorModule::camp($emailClean, [
      "max_length" => [255, "El correo electrónico es demasiado largo"],
      "email"      => [true, "El formato de correo electrónico no es válido"]
    ]);

    if (!$emailVal[0]) {
      return ResponseModule::redirect("/recuperar", $emailVal[1], 2);
    }

    $model     = new LoginModels();
    $rateCheck = $model->checkPasswordResetRequestRate($emailClean);
    if ($rateCheck !== true) {
      $timeMsg = $this->formatCountdown((int)$rateCheck);
      return ResponseModule::redirect(
        "/recuperar",
        "Has superado el límite de solicitudes. Inténtalo nuevamente en {$timeMsg}.",
        2
      );
    }

    $user = $model->findUserByEmail($emailClean);
    if ($user && empty($user["deleted_at_user"])) {
      $status = (string)($user["user_status"] ?? "");

      if ($status === "active") {
        $rawToken  = bin2hex(random_bytes(32));
        $tokenHash = hash("sha256", $rawToken);
        $expiresAt = date("Y-m-d H:i:s", time() + 1800);

        $model->savePasswordResetToken($user["user_id"], $tokenHash, $expiresAt);

        $resetUrl   = $this->buildAbsoluteUrl("restablecer?token=" . urlencode($rawToken));
        $mailResult = AuthMailHelper::sendPasswordResetEmail($emailClean, (string)$user["username"], $resetUrl);
        if (!$mailResult["success"]) {
          error_log("Error enviando recuperación a {$emailClean}: " . ($mailResult["error"] ?? "desconocido"));
        }
      } elseif ($status === "pending") {
        $rawToken  = bin2hex(random_bytes(32));
        $tokenHash = hash("sha256", $rawToken);
        $expiresAt = date("Y-m-d H:i:s", time() + 3600);

        $model->saveEmailVerificationToken($user["user_id"], $tokenHash, $expiresAt);

        $verifyUrl  = $this->buildAbsoluteUrl("verificar-cuenta?token=" . urlencode($rawToken));
        $mailResult = AuthMailHelper::sendVerificationEmail($emailClean, (string)$user["username"], $verifyUrl);
        if (!$mailResult["success"]) {
          error_log("Error enviando verificación desde /recuperar a {$emailClean}: " . ($mailResult["error"] ?? "desconocido"));
        }
      }
    }

    return ResponseModule::redirect(
      "/recuperar",
      "Si el correo está registrado, recibirás un enlace en los próximos minutos. Revisa tu bandeja de entrada o spam.",
      0
    );
  }

  /**
   * Valida el token de recuperación por GET y muestra el formulario de nueva contraseña.
   *
   * @return mixed Renderizado de Login.resetPassword o redirección si el token es inválido.
   */
  public function resetPasswordForm() {
    $rawToken = trim((string)($_GET["token"] ?? ""));

    if (preg_match("/^[a-f0-9]{64}$/", $rawToken) !== 1) {
      return ResponseModule::redirect(
        "/recuperar",
        "El enlace de recuperación es inválido o ha expirado. Solicita uno nuevo.",
        2
      );
    }

    $model = new LoginModels();
    $user  = $model->findByPasswordResetTokenHash(hash("sha256", $rawToken));
    if (!$user || ($user["user_status"] ?? "") !== "active" || !empty($user["deleted_at_user"])) {
      return ResponseModule::redirect(
        "/recuperar",
        "El enlace de recuperación es inválido o ha expirado. Solicita uno nuevo.",
        2
      );
    }

    $expiresRaw = (string)($user["password_reset_token_expires_at"] ?? "");
    $expiresTs  = $expiresRaw !== "" ? strtotime($expiresRaw) : false;
    if ($expiresTs === false || $expiresTs < time()) {
      return ResponseModule::redirect(
        "/recuperar",
        "El enlace de recuperación es inválido o ha expirado. Solicita uno nuevo.",
        2
      );
    }

    return $this->view("Login.resetPassword", [
      "token" => $rawToken
    ]);
  }

  /**
   * Procesa el cambio de contraseña validando el token de un solo uso y las reglas de seguridad.
   *
   * @param array|string $requestData Datos enviados vía POST.
   * @return mixed Redirección a /ingresar si fue exitoso o al formulario con mensaje de error.
   */
  public function processResetPassword(array|string $requestData) {
    if (!is_array($requestData)) {
      return ResponseModule::redirect("/recuperar", "Solicitud inválida.", 2);
    }

    $rawToken = trim((string)($requestData["token"] ?? ""));
    $pass     = (string)($requestData["pass"] ?? "");
    $repass   = (string)($requestData["repass"] ?? "");

    if (preg_match("/^[a-f0-9]{64}$/", $rawToken) !== 1) {
      return ResponseModule::redirect(
        "/recuperar",
        "El enlace de recuperación es inválido o ha expirado. Solicita uno nuevo.",
        2
      );
    }

    $model = new LoginModels();
    $user  = $model->findByPasswordResetTokenHash(hash("sha256", $rawToken));
    if (!$user || ($user["user_status"] ?? "") !== "active" || !empty($user["deleted_at_user"])) {
      return ResponseModule::redirect(
        "/recuperar",
        "El enlace de recuperación es inválido o ha expirado. Solicita uno nuevo.",
        2
      );
    }

    $expiresRaw = (string)($user["password_reset_token_expires_at"] ?? "");
    $expiresTs  = $expiresRaw !== "" ? strtotime($expiresRaw) : false;
    if ($expiresTs === false || $expiresTs < time()) {
      return ResponseModule::redirect(
        "/recuperar",
        "El enlace de recuperación es inválido o ha expirado. Solicita uno nuevo.",
        2
      );
    }

    if ($pass === "" || $repass === "") {
      return ResponseModule::redirect("/restablecer?token=" . urlencode($rawToken), "Debes rellenar todos los datos.", 2);
    }

    $passVal = ValidatorModule::camp($pass, [
      "min_length"  => [8, "La contraseña debe tener al menos 8 caracteres"],
      "max_length"  => [128, "La contraseña no puede superar los 128 caracteres"],
      "min_capital" => [1, "La contraseña debe tener al menos 1 mayúscula"],
      "min_number"  => [1, "La contraseña debe tener al menos 1 número"]
    ]);
    if (!$passVal[0]) {
      return ResponseModule::redirect("/restablecer?token=" . urlencode($rawToken), $passVal[1], 2);
    }

    if ($pass !== $repass) {
      return ResponseModule::redirect("/restablecer?token=" . urlencode($rawToken), "Las contraseñas no coinciden.", 2);
    }

    $model->updatePasswordAndClearResetToken(
      $user["user_id"],
      password_hash($pass, PASSWORD_DEFAULT),
      (string)$user["username"],
      (string)$user["email"]
    );

    return ResponseModule::redirect("/ingresar", "¡Contraseña actualizada con éxito! Ya puedes iniciar sesión.", 0);
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