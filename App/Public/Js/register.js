document.addEventListener("DOMContentLoaded", () => {
  // Contenedores de pasos
  const stepUsernameContainer = document.getElementById("step-username-container");
  const stepEmailContainer = document.getElementById("step-email-container");
  const stepPasswordContainer = document.getElementById("step-password-container");

  // Formularios
  const formUsername = document.getElementById("form-step-username");
  const formEmail = document.getElementById("form-step-email");
  const formPassword = document.getElementById("form-step-password");

  // Inputs
  const inputUsername = document.getElementById("input-username");
  const inputEmail = document.getElementById("input-email");
  const inputPassword = document.getElementById("input-password");
  const inputRepassword = document.getElementById("input-repassword");

  const hiddenUsername = document.getElementById("hidden-username");
  const hiddenEmail = document.getElementById("hidden-email");

  // Mensajes de error
  const errorUsername = document.getElementById("error-username");
  const errorEmail = document.getElementById("error-email");
  const errorPassword = document.getElementById("error-password");

  // Botones de retroceso
  const btnBackToUsername = document.getElementById("btn-back-to-username");
  const btnBackToEmail = document.getElementById("btn-back-to-email");

  let usernameCountdownInterval = null;
  let emailCountdownInterval = null;
  let isSubmittingFinal = false;

  const RESERVED_USERNAMES = new Set([
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
  ]);

  // Helper para mostrar errores de forma segura (anti-DOM XSS)
  function showError(element, message) {
    if (!element) return;
    element.textContent = message;
    element.style.display = "block";
  }

  // Helper para ocultar errores
  function hideError(element) {
    if (!element) return;
    element.textContent = "";
    element.style.display = "none";
  }

  // Validación sincronizada del nombre de usuario
  function validateUsernameClient(rawValue) {
    const clean = (rawValue || "").trim().toLowerCase();

    if (!clean) {
      return { valid: false, value: clean, message: "El nombre de usuario es obligatorio." };
    }
    if (clean.length < 4) {
      return { valid: false, value: clean, message: "El nombre de usuario debe tener al menos 4 caracteres." };
    }
    if (clean.length > 30) {
      return { valid: false, value: clean, message: "El nombre de usuario no puede superar los 30 caracteres." };
    }
    if (/\s/.test(clean)) {
      return { valid: false, value: clean, message: "El nombre de usuario no debe contener espacios." };
    }
    if (!/^[a-z0-9._-]+$/.test(clean)) {
      return { valid: false, value: clean, message: "Usa solo letras minúsculas, números, puntos, guiones y guiones bajos." };
    }
    if (clean.startsWith(".") || clean.endsWith(".") || clean.includes("..")) {
      return { valid: false, value: clean, message: "No puede comenzar ni terminar con punto ni tener puntos consecutivos." };
    }
    if (RESERVED_USERNAMES.has(clean)) {
      return { valid: false, value: clean, message: "Ese nombre de usuario está reservado por el sistema. Elige otro." };
    }

    return { valid: true, value: clean, message: "" };
  }

  // Helper para obtener el token CSRF con fallback al input oculto
  function getCsrfToken() {
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta && csrfMeta.getAttribute("content")) {
      return csrfMeta.getAttribute("content");
    }
    const csrfInput = document.querySelector('input[name="_token"]');
    return csrfInput ? csrfInput.value : "";
  }

  // Helper para parsear JSON de forma segura evitando SyntaxError en respuestas HTML
  async function parseJsonSafe(response) {
    const contentType = response.headers.get("content-type") || "";
    if (!contentType.includes("application/json")) {
      return null;
    }
    try {
      return await response.json();
    } catch {
      return null;
    }
  }

  // Helper para peticiones POST AJAX
  async function apiPost(url, data) {
    const csrfToken = getCsrfToken();
    const headers = {
      "Content-Type": "application/json",
      "Accept": "application/json",
      "X-Requested-With": "XMLHttpRequest"
    };
    if (csrfToken) {
      headers["X-CSRF-TOKEN"] = csrfToken;
    }
    const response = await fetch(url, {
      method: "POST",
      headers: headers,
      credentials: "same-origin",
      body: JSON.stringify(data)
    });
    return response;
  }

  // Helper para dar formato de minutos y segundos a un número de segundos
  function formatRemainingTime(totalSeconds) {
    const mins = Math.floor(totalSeconds / 60);
    const secs = totalSeconds % 60;
    if (mins > 0) {
      return `${mins} min y ${secs} seg`;
    }
    return `${secs} seg`;
  }

  // Contenedores ordenados de los pasos
  const stepContainers = [
    stepUsernameContainer,
    stepEmailContainer,
    stepPasswordContainer
  ];

  // Inputs principales para auto-enfoque
  const stepInputs = [
    inputUsername,
    inputEmail,
    inputPassword
  ];

  let currentStepIndex = 0;
  let isTransitioning = false;

  // Actualiza el estado visual del indicador/menú de pasos
  function updateStepIndicator(targetIndex) {
    const indicator = document.getElementById("register-steps-indicator");
    if (!indicator) return;

    const items = indicator.querySelectorAll(".step-indicator-item");
    const prog1 = document.getElementById("step-prog-1");
    const prog2 = document.getElementById("step-prog-2");

    items.forEach((item, index) => {
      const badge = item.querySelector(".step-badge");
      item.classList.remove("active", "completed");

      if (index < targetIndex) {
        item.classList.add("completed");
        if (badge) badge.innerHTML = "✓";
      } else if (index === targetIndex) {
        item.classList.add("active");
        if (badge) badge.textContent = (index + 1).toString();
      } else {
        if (badge) badge.textContent = (index + 1).toString();
      }
    });

    if (prog1) {
      prog1.style.width = targetIndex >= 1 ? "100%" : "0%";
    }
    if (prog2) {
      prog2.style.width = targetIndex >= 2 ? "100%" : "0%";
    }
  }

  // Permite retroceder haciendo clic en pasos ya completados del indicador
  const stepsIndicator = document.getElementById("register-steps-indicator");
  if (stepsIndicator) {
    const indicatorItems = stepsIndicator.querySelectorAll(".step-indicator-item");
    indicatorItems.forEach((item, idx) => {
      item.addEventListener("click", () => {
        if (idx < currentStepIndex && !isTransitioning) {
          showStep(stepContainers[idx], -1);
        }
      });
    });
  }

  function focusStepInput(index) {
    const input = stepInputs[index];
    if (input) {
      setTimeout(() => {
        input.focus();
      }, 50);
    }
  }

  // --- TRANSICIONES ANIMADAS ENTRE PASOS ---
  function showStep(containerToShow, explicitDirection = null) {
    const targetIndex = stepContainers.indexOf(containerToShow);
    if (targetIndex === -1 || targetIndex === currentStepIndex || isTransitioning) {
      return;
    }

    const currentContainer = stepContainers[currentStepIndex];
    const targetContainer = stepContainers[targetIndex];
    if (!currentContainer || !targetContainer) return;

    isTransitioning = true;
    const direction = explicitDirection !== null ? explicitDirection : (targetIndex > currentStepIndex ? 1 : -1);
    const prefersReducedMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const hasGsap = typeof gsap !== "undefined";

    // Actualizar indicador visual de pasos
    updateStepIndicator(targetIndex);

    // Fallback: Si no hay GSAP o se prefiere movimiento reducido
    if (prefersReducedMotion || !hasGsap) {
      stepContainers.forEach((container) => {
        if (container) {
          container.style.display = "none";
          container.style.opacity = "";
          container.style.transform = "";
        }
      });

      targetContainer.style.display = "flex";
      targetContainer.style.opacity = "1";
      targetContainer.style.transform = "";

      currentStepIndex = targetIndex;
      isTransitioning = false;
      focusStepInput(targetIndex);
      return;
    }

    // Transición fluida con GSAP
    const stepsWrapper = document.getElementById("register-steps-wrapper");
    const initialHeight = stepsWrapper ? stepsWrapper.offsetHeight : 0;

    // Matar tweens activos previos
    gsap.killTweensOf(currentContainer);
    gsap.killTweensOf(targetContainer);
    if (stepsWrapper) gsap.killTweensOf(stepsWrapper);

    // 1. Desvanecer y desplazar el paso saliente hacia fuera
    gsap.to(currentContainer, {
      opacity: 0,
      x: direction * -35,
      duration: 0.22,
      ease: "power2.in",
      onComplete: () => {
        currentContainer.style.display = "none";
        currentContainer.style.transform = "";

        // 2. Preparar el paso entrante en posición inicial
        targetContainer.style.display = "flex";
        targetContainer.style.opacity = "0";
        gsap.set(targetContainer, { x: direction * 35, opacity: 0 });

        // 3. Suavizar cambio de altura del contenedor si difiere
        if (stepsWrapper && initialHeight > 0) {
          const targetHeight = stepsWrapper.offsetHeight;
          if (targetHeight !== initialHeight) {
            gsap.fromTo(stepsWrapper,
              { height: initialHeight },
              {
                height: targetHeight,
                duration: 0.28,
                ease: "power2.out",
                onComplete: () => {
                  stepsWrapper.style.height = "";
                }
              }
            );
          }
        }

        // 4. Desvanecer y desplazar el nuevo paso a posición visible
        gsap.to(targetContainer, {
          opacity: 1,
          x: 0,
          duration: 0.28,
          ease: "power2.out",
          onComplete: () => {
            currentStepIndex = targetIndex;
            isTransitioning = false;
            focusStepInput(targetIndex);
          }
        });
      }
    });
  }

  // Retroceder de Email a Username
  if (btnBackToUsername) {
    btnBackToUsername.addEventListener("click", () => {
      showStep(stepUsernameContainer, -1);
    });
  }

  // Retroceder de Password a Email
  if (btnBackToEmail) {
    btnBackToEmail.addEventListener("click", () => {
      showStep(stepEmailContainer, -1);
    });
  }

  // Limpiar valores confirmados si el usuario retrocede y edita un paso previo
  if (inputUsername) {
    inputUsername.addEventListener("input", () => {
      if (hiddenUsername) hiddenUsername.value = "";
      hideError(errorUsername);
    });
  }

  if (inputEmail) {
    inputEmail.addEventListener("input", () => {
      if (hiddenEmail) hiddenEmail.value = "";
      hideError(errorEmail);
    });
  }

  // --- PASO 1: USERNAME ---
  if (formUsername) {
    formUsername.addEventListener("submit", async (e) => {
      e.preventDefault();
      hideError(errorUsername);

      const validation = validateUsernameClient(inputUsername ? inputUsername.value : "");
      if (!validation.valid) {
        showError(errorUsername, validation.message);
        return;
      }

      const username = validation.value;
      const submitBtn = formUsername.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = "Verificando...";
      }

      try {
        const response = await apiPost("/registrar/check-username", { username });
        const result = await parseJsonSafe(response);

        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = "Continuar";
        }

        if (response.status === 403) {
          showError(
            errorUsername,
            (result && result.message) || "Tu sesión de seguridad expiró. Recarga la página e inténtalo nuevamente."
          );
          return;
        }

        if (response.status === 429) {
          const seconds = (result && result.seconds) || 300;
          if (submitBtn) submitBtn.disabled = true;

          let remaining = seconds;
          showError(
            errorUsername,
            `Has sobrepasado los intentos para verificar el usuario. Inténtalo nuevamente en ${formatRemainingTime(remaining)}.`
          );

          if (usernameCountdownInterval) clearInterval(usernameCountdownInterval);
          usernameCountdownInterval = setInterval(() => {
            remaining--;
            if (remaining <= 0) {
              clearInterval(usernameCountdownInterval);
              usernameCountdownInterval = null;
              if (submitBtn) submitBtn.disabled = false;
              hideError(errorUsername);
            } else {
              showError(
                errorUsername,
                `Has sobrepasado los intentos para verificar el usuario. Inténtalo nuevamente en ${formatRemainingTime(remaining)}.`
              );
            }
          }, 1000);
          return;
        }

        if (!result) {
          showError(errorUsername, "No se pudo procesar la respuesta del servidor. Recarga la página e inténtalo nuevamente.");
          return;
        }

        if (!response.ok || result.status === "error") {
          showError(errorUsername, result.message || "Error al verificar el usuario.");
          return;
        }

        if (result.status === "taken") {
          showError(errorUsername, "El nombre de usuario ya está en uso.");
          return;
        }

        if (result.status === "available") {
          if (usernameCountdownInterval) {
            clearInterval(usernameCountdownInterval);
            usernameCountdownInterval = null;
          }
          // Guardar valor validado y normalizado en el formulario final
          if (hiddenUsername) hiddenUsername.value = username;
          // Avanzar al paso del correo
          showStep(stepEmailContainer);
        }

      } catch (err) {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = "Continuar";
        }
        showError(errorUsername, "No se pudo conectar con el servidor. Inténtalo más tarde.");
        console.error(err);
      }
    });
  }

  // --- PASO 2: EMAIL ---
  if (formEmail) {
    formEmail.addEventListener("submit", async (e) => {
      e.preventDefault();
      hideError(errorEmail);

      const email = (inputEmail ? inputEmail.value : "").trim().toLowerCase();
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      // Validaciones del cliente
      if (!emailRegex.test(email)) {
        showError(errorEmail, "El formato de correo electrónico no es válido.");
        return;
      }

      if (email.length > 255) {
        showError(errorEmail, "El correo electrónico es demasiado largo.");
        return;
      }

      const submitBtn = formEmail.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = "Verificando...";
      }

      try {
        const response = await apiPost("/registrar/check-email", { email });
        const result = await parseJsonSafe(response);

        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = "Continuar";
        }

        if (response.status === 403) {
          showError(
            errorEmail,
            (result && result.message) || "Tu sesión de seguridad expiró. Recarga la página e inténtalo nuevamente."
          );
          return;
        }

        if (response.status === 429) {
          const seconds = (result && result.seconds) || 300;
          if (submitBtn) submitBtn.disabled = true;

          let remaining = seconds;
          showError(
            errorEmail,
            `Has sobrepasado los intentos para verificar el correo. Inténtalo nuevamente en ${formatRemainingTime(remaining)}.`
          );

          if (emailCountdownInterval) clearInterval(emailCountdownInterval);
          emailCountdownInterval = setInterval(() => {
            remaining--;
            if (remaining <= 0) {
              clearInterval(emailCountdownInterval);
              emailCountdownInterval = null;
              if (submitBtn) submitBtn.disabled = false;
              hideError(errorEmail);
            } else {
              showError(
                errorEmail,
                `Has sobrepasado los intentos para verificar el correo. Inténtalo nuevamente en ${formatRemainingTime(remaining)}.`
              );
            }
          }, 1000);
          return;
        }

        if (!result) {
          showError(errorEmail, "No se pudo procesar la respuesta del servidor. Recarga la página e inténtalo nuevamente.");
          return;
        }

        if (!response.ok || result.status === "error") {
          showError(errorEmail, result.message || "Error al verificar el correo.");
          return;
        }

        if (result.status === "taken") {
          showError(errorEmail, "El correo electrónico ya está registrado con otra cuenta.");
          return;
        }

        if (result.status === "available") {
          if (emailCountdownInterval) {
            clearInterval(emailCountdownInterval);
            emailCountdownInterval = null;
          }
          // Guardar valor normalizado en formulario final
          if (hiddenEmail) hiddenEmail.value = email;
          // Avanzar al paso de contraseña
          showStep(stepPasswordContainer);
        }

      } catch (err) {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = "Continuar";
        }
        showError(errorEmail, "No se pudo conectar con el servidor. Inténtalo más tarde.");
        console.error(err);
      }
    });
  }

  // --- PASO 3: PASSWORD (SUBMIT FINAL) ---
  if (formPassword) {
    formPassword.addEventListener("submit", (e) => {
      if (isSubmittingFinal) {
        e.preventDefault();
        return;
      }

      hideError(errorPassword);

      const pass = inputPassword ? inputPassword.value : "";
      const repass = inputRepassword ? inputRepassword.value : "";

      // Validaciones cliente
      if (pass.length < 8) {
        e.preventDefault();
        showError(errorPassword, "La contraseña debe tener al menos 8 caracteres.");
        return;
      }

      if (pass.length > 128) {
        e.preventDefault();
        showError(errorPassword, "La contraseña no puede superar los 128 caracteres.");
        return;
      }

      if (!/[A-Z]/.test(pass)) {
        e.preventDefault();
        showError(errorPassword, "La contraseña debe contener al menos una letra mayúscula.");
        return;
      }

      if (!/[0-9]/.test(pass)) {
        e.preventDefault();
        showError(errorPassword, "La contraseña debe contener al menos un número.");
        return;
      }

      if (pass !== repass) {
        e.preventDefault();
        showError(errorPassword, "Las contraseñas ingresadas no coinciden.");
        return;
      }

      // Asegurar que los datos ocultos sigan cargados
      if (!hiddenUsername || !hiddenUsername.value || !hiddenEmail || !hiddenEmail.value) {
        e.preventDefault();
        showError(errorPassword, "Faltan datos de los pasos anteriores. Por favor, reinicia el registro.");
        return;
      }

      isSubmittingFinal = true;
      const submitBtn = formPassword.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
      }

      // Se deja que el envío del formulario ocurra de forma nativa a /registrar
    });
  }
});
