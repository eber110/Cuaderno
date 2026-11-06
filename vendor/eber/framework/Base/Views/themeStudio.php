<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Frame Theme Studio</title>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎨</text></svg>">
  <style>
    :root {
      --ts-bg: #0f172a;
      --ts-surface: #1e293b;
      --ts-surface-hover: #334155;
      --ts-border: #334155;
      --ts-text: #f8fafc;
      --ts-muted: #94a3b8;
      --ts-primary: #38bdf8;
      --ts-primary-hover: #0ea5e9;
      --ts-success: #22c55e;
      --ts-success-hover: #16a34a;
      --ts-danger: #ef4444;
      --ts-warning: #f59e0b;
      --ts-font: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: var(--ts-font);
      background: var(--ts-bg);
      color: var(--ts-text);
      display: flex;
      flex-direction: column;
      height: 100vh;
      overflow: hidden;
    }

    /* Top Navigation Bar */
    .ts-header {
      background: var(--ts-surface);
      border-bottom: 1px solid var(--ts-border);
      padding: 10px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 15px;
      z-index: 100;
      flex-shrink: 0;
    }
    .ts-brand {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 1.1rem;
      font-weight: 700;
      color: #fff;
      white-space: nowrap;
    }
    .ts-badge {
      font-size: 0.72rem;
      background: rgba(56, 189, 248, 0.15);
      color: var(--ts-primary);
      padding: 3px 8px;
      border-radius: 999px;
      border: 1px solid rgba(56, 189, 248, 0.3);
      font-weight: 600;
    }
    .ts-center-controls {
      display: flex;
      align-items: center;
      gap: 10px;
      background: var(--ts-bg);
      padding: 4px 8px;
      border-radius: 10px;
      border: 1px solid var(--ts-border);
      flex-wrap: wrap;
    }
    .ts-route-bar {
      display: flex;
      align-items: center;
      gap: 6px;
      border-right: 1px solid var(--ts-border);
      padding-right: 10px;
    }
    .ts-route-select {
      background: var(--ts-surface);
      border: 1px solid var(--ts-border);
      color: #fff;
      padding: 5px 8px;
      border-radius: 6px;
      font-size: 0.8rem;
      font-weight: 600;
      outline: none;
      cursor: pointer;
      max-width: 200px;
    }
    .ts-route-select:focus {
      border-color: var(--ts-primary);
    }
    .ts-route-input {
      background: var(--ts-surface);
      border: 1px solid var(--ts-border);
      color: var(--ts-primary);
      padding: 5px 8px;
      border-radius: 6px;
      font-size: 0.8rem;
      font-family: monospace;
      width: 130px;
      outline: none;
    }
    .ts-route-input:focus {
      border-color: var(--ts-primary);
    }
    .ts-icon-btn {
      background: var(--ts-surface);
      border: 1px solid var(--ts-border);
      color: var(--ts-muted);
      cursor: pointer;
      padding: 5px 8px;
      border-radius: 6px;
      font-size: 0.8rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: all 0.15s ease;
    }
    .ts-icon-btn:hover {
      background: var(--ts-surface-hover);
      color: #fff;
      border-color: var(--ts-primary);
    }
    .ts-mode-btn {
      background: transparent;
      border: none;
      color: var(--ts-muted);
      cursor: pointer;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 0.85rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.15s ease;
    }
    .ts-mode-btn.active {
      background: var(--ts-surface-hover);
      color: #fff;
      box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    }
    .ts-viewport-btns {
      display: flex;
      align-items: center;
      gap: 4px;
      border-left: 1px solid var(--ts-border);
      padding-left: 8px;
    }
    .ts-view-btn {
      background: transparent;
      border: none;
      color: var(--ts-muted);
      cursor: pointer;
      padding: 6px 10px;
      border-radius: 6px;
      font-size: 0.82rem;
      transition: all 0.15s ease;
    }
    .ts-view-btn.active {
      background: var(--ts-surface-hover);
      color: var(--ts-primary);
    }
    .ts-actions {
      display: flex;
      align-items: center;
      gap: 10px;
      white-space: nowrap;
    }
    .ts-btn {
      border: none;
      cursor: pointer;
      font-family: inherit;
      font-weight: 600;
      font-size: 0.88rem;
      padding: 8px 16px;
      border-radius: 8px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.15s ease;
    }
    .ts-btn-primary {
      background: var(--ts-success);
      color: #fff;
    }
    .ts-btn-primary:hover {
      background: var(--ts-success-hover);
      transform: translateY(-1px);
    }
    .ts-btn-secondary {
      background: var(--ts-surface-hover);
      color: var(--ts-text);
    }
    .ts-btn-secondary:hover {
      background: #475569;
    }

    /* Main Container (Sidebar + Real Project View) */
    .ts-main {
      display: flex;
      flex: 1;
      height: calc(100vh - 61px);
      overflow: hidden;
    }

    /* Left Sidebar */
    .ts-sidebar {
      width: 440px;
      background: var(--ts-surface);
      border-right: 1px solid var(--ts-border);
      display: flex;
      flex-direction: column;
      flex-shrink: 0;
      overflow: hidden;
    }
    .ts-tabs {
      display: flex;
      background: var(--ts-bg);
      border-bottom: 1px solid var(--ts-border);
      overflow-x: auto;
      scrollbar-width: none !important;
    }
    .ts-tabs::-webkit-scrollbar { display: none !important; }
    .ts-tab-btn {
      flex: 1;
      min-width: 68px;
      background: transparent;
      border: none;
      color: var(--ts-muted);
      padding: 12px 6px;
      font-size: 0.78rem;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 4px;
      border-bottom: 2px solid transparent;
      transition: all 0.15s;
      white-space: nowrap;
    }
    .ts-tab-btn.active {
      color: var(--ts-primary);
      border-bottom-color: var(--ts-primary);
      background: rgba(56, 189, 248, 0.05);
    }
    .ts-tab-content {
      flex: 1;
      overflow-y: auto;
      padding: 18px;
      display: none;
    }
    .ts-tab-content.active {
      display: block;
    }

    /* Barra de desplazamiento invisible pero funcional en Sidebar y Tabs */
    .ts-sidebar, .ts-tab-content {
      scrollbar-width: none !important;
      -ms-overflow-style: none !important;
    }
    .ts-sidebar::-webkit-scrollbar,
    .ts-tab-content::-webkit-scrollbar {
      display: none !important;
      width: 0 !important;
      height: 0 !important;
    }

    /* Form Fields & Sections */
    .ts-section {
      margin-bottom: 22px;
    }
    .ts-section-title {
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--ts-muted);
      margin-bottom: 10px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .ts-field {
      margin-bottom: 12px;
    }
    .ts-field label {
      display: block;
      font-size: 0.82rem;
      font-weight: 600;
      color: #cbd5e1;
      margin-bottom: 4px;
    }
    .ts-input-text, .ts-select {
      width: 100%;
      background: var(--ts-bg);
      border: 1px solid var(--ts-border);
      color: #fff;
      padding: 8px 10px;
      border-radius: 6px;
      font-size: 0.85rem;
      font-family: inherit;
      outline: none;
      transition: border 0.15s;
    }
    .ts-input-text:focus, .ts-select:focus {
      border-color: var(--ts-primary);
    }

    /* Color Grid Rows */
    .ts-color-row {
      display: grid;
      grid-template-columns: 34px 1fr 70px 32px 28px;
      align-items: center;
      gap: 6px;
      margin-bottom: 8px;
      background: rgba(15, 23, 42, 0.5);
      padding: 6px;
      border-radius: 8px;
      border: 1px solid var(--ts-border);
    }
    .ts-color-row-fixed {
      display: grid;
      grid-template-columns: 34px 1fr 70px 32px;
      align-items: center;
      gap: 6px;
      margin-bottom: 8px;
      background: rgba(15, 23, 42, 0.5);
      padding: 6px;
      border-radius: 8px;
      border: 1px solid var(--ts-border);
    }
    .ts-color-swatch-input {
      -webkit-appearance: none;
      border: none;
      width: 32px;
      height: 32px;
      border-radius: 6px;
      cursor: pointer;
      background: transparent;
    }
    .ts-color-swatch-input::-webkit-color-swatch-wrapper { padding: 0; }
    .ts-color-swatch-input::-webkit-color-swatch {
      border: 1px solid rgba(255,255,255,0.2);
      border-radius: 6px;
    }
    .ts-color-info {
      font-size: 0.82rem;
      font-weight: 600;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .ts-color-info span {
      display: block;
      font-size: 0.72rem;
      color: var(--ts-muted);
      font-weight: 400;
    }
    .ts-hex-input {
      background: var(--ts-bg);
      border: 1px solid var(--ts-border);
      color: #fff;
      padding: 5px 6px;
      border-radius: 4px;
      font-size: 0.8rem;
      font-family: monospace;
      text-transform: uppercase;
      text-align: center;
      outline: none;
    }
    .ts-hover-chip {
      width: 30px;
      height: 30px;
      border-radius: 6px;
      border: 1px dashed rgba(255,255,255,0.3);
      position: relative;
    }
    .ts-hover-chip::after {
      content: 'H';
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.65rem;
      font-weight: 700;
      color: rgba(255,255,255,0.8);
      text-shadow: 0 1px 2px rgba(0,0,0,0.8);
    }

    /* Botones de acción para slots de color */
    .ts-btn-del-slot {
      background: transparent;
      border: 1px solid transparent;
      color: var(--ts-muted);
      cursor: pointer;
      width: 28px;
      height: 28px;
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.85rem;
      transition: all 0.15s ease;
    }
    .ts-btn-del-slot:hover:not(:disabled) {
      background: rgba(239, 68, 68, 0.15);
      color: var(--ts-danger);
      border-color: rgba(239, 68, 68, 0.3);
    }
    .ts-btn-del-slot:disabled {
      opacity: 0.18;
      cursor: not-allowed;
    }
    .ts-btn-add-color {
      width: 100%;
      background: rgba(56, 189, 248, 0.08);
      border: 1px dashed rgba(56, 189, 248, 0.4);
      color: var(--ts-primary);
      padding: 9px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      font-size: 0.82rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: all 0.15s ease;
      margin-top: 6px;
    }
    .ts-btn-add-color:hover:not(:disabled) {
      background: rgba(56, 189, 248, 0.18);
      border-color: var(--ts-primary);
      transform: translateY(-1px);
    }
    .ts-btn-add-color:disabled {
      opacity: 0.35;
      cursor: not-allowed;
      border-color: var(--ts-border);
      color: var(--ts-muted);
    }

    /* Range Sliders */
    .ts-range-box {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .ts-range-input {
      flex: 1;
      accent-color: var(--ts-primary);
    }
    .ts-range-val {
      font-size: 0.85rem;
      font-weight: 700;
      font-family: monospace;
      color: var(--ts-primary);
      width: 55px;
      text-align: right;
    }

    /* Batch Paste Area */
    .ts-batch-area {
      background: var(--ts-bg);
      border: 1px dashed var(--ts-border);
      border-radius: 8px;
      padding: 10px;
      margin-bottom: 14px;
    }
    .ts-batch-textarea {
      width: 100%;
      height: 50px;
      background: transparent;
      border: none;
      color: #cbd5e1;
      font-family: monospace;
      font-size: 0.78rem;
      outline: none;
      resize: none;
      margin-bottom: 6px;
    }

    /* Right Live Project Preview Stage */
    .ts-sandbox-stage {
      flex: 1;
      background: #020617;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 12px;
      transition: all 0.2s ease;
      position: relative;
    }

    .ts-sandbox-window {
      width: 100%;
      max-width: 100%;
      height: 100%;
      flex: 1;
      background: #ffffff;
      border-radius: 10px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: max-width 0.25s ease, background-color 0.2s ease;
      position: relative;
      border: 1px solid var(--ts-border);
    }

    #sandbox {
      width: 100%;
      height: 100%;
      flex: 1;
      display: flex;
      flex-direction: column;
      background-color: var(--body, #ffffff);
    }

    #projectPreviewFrame {
      width: 100%;
      height: 100%;
      flex: 1;
      border: none;
      display: block;
      background: transparent;
    }

    /* WCAG Badges & Table in Sidebar */
    .ts-wcag-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 2px 7px;
      border-radius: 999px;
      font-size: 0.68rem;
      font-weight: 700;
      font-family: monospace;
      letter-spacing: 0.02em;
    }
    .ts-wcag-aaa {
      background: #14532d;
      color: #86efac;
      border: 1px solid #16a34a;
    }
    .ts-wcag-aa {
      background: #1e3a8a;
      color: #93c5fd;
      border: 1px solid #3b82f6;
    }
    .ts-wcag-aa-lg {
      background: #713f12;
      color: #fde047;
      border: 1px solid #ca8a04;
    }
    .ts-wcag-fail {
      background: #7f1d1d;
      color: #fca5a5;
      border: 1px solid #dc2626;
    }
    .ts-wcag-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.78rem;
      margin-top: 10px;
    }
    .ts-wcag-table th {
      padding: 8px 6px;
      color: var(--ts-muted);
      border-bottom: 1px solid var(--ts-border);
      text-align: left;
    }
    .ts-wcag-table td {
      padding: 8px 6px;
      border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .sb-gradient-strip {
      height: 52px;
      border-radius: 8px;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 12px;
      box-shadow: 0 3px 6px rgba(0,0,0,0.15);
      color: #fff;
      font-weight: 700;
      font-size: 0.8rem;
      text-shadow: 0 1px 3px rgba(0,0,0,0.7);
    }

    /* Toast Notification */
    .ts-toast {
      position: fixed;
      bottom: 24px;
      right: 24px;
      background: #1e293b;
      color: #fff;
      padding: 14px 22px;
      border-radius: 10px;
      box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5);
      border: 1px solid var(--ts-border);
      font-size: 0.9rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
      z-index: 9999;
      transform: translateY(120%);
      opacity: 0;
      transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .ts-toast.show {
      transform: translateY(0);
      opacity: 1;
    }
    .ts-toast.success { border-left: 4px solid var(--ts-success); }
    .ts-toast.error { border-left: 4px solid var(--ts-danger); }

    /* Selector de Tema en Header */
    .ts-theme-bar {
      display: flex;
      align-items: center;
      gap: 6px;
      background: var(--ts-panel);
      border: 1px solid var(--ts-border);
      border-radius: 8px;
      padding: 3px 8px;
    }
    .ts-theme-select {
      background: transparent;
      border: none;
      color: #f1f5f9;
      font-size: 0.82rem;
      font-weight: 600;
      outline: none;
      cursor: pointer;
      max-width: 175px;
    }
    .ts-theme-select option {
      background: #0f172a;
      color: #f1f5f9;
    }

    /* Tarjetas de Tema en Pestaña Temas */
    .ts-theme-card {
      background: var(--ts-bg);
      border: 1px solid var(--ts-border);
      border-radius: 8px;
      padding: 12px;
      margin-bottom: 12px;
      transition: all 0.2s ease;
    }
    .ts-theme-card.active-theme {
      border-color: var(--ts-primary);
      box-shadow: 0 0 12px rgba(56, 189, 248, 0.18);
    }
    .ts-theme-card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
    }
    .ts-theme-card-title {
      font-weight: 700;
      font-size: 0.88rem;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .ts-theme-slug-badge {
      font-size: 0.7rem;
      font-family: monospace;
      color: var(--ts-muted);
      background: rgba(255,255,255,0.06);
      padding: 2px 6px;
      border-radius: 4px;
    }
    .ts-theme-swatches {
      display: flex;
      gap: 4px;
      margin: 8px 0;
      overflow-x: auto;
      padding-bottom: 4px;
    }
    .ts-theme-mini-chip {
      width: 18px;
      height: 18px;
      border-radius: 4px;
      flex-shrink: 0;
      border: 1px solid rgba(255,255,255,0.15);
    }
    .ts-theme-actions {
      display: flex;
      align-items: center;
      gap: 6px;
      margin-top: 10px;
    }

    /* Modal Flotante */
    .ts-modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(2, 6, 23, 0.75);
      backdrop-filter: blur(4px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 99999;
    }
    .ts-modal-overlay.open {
      display: flex;
    }
    .ts-modal-box {
      background: #0f172a;
      border: 1px solid var(--ts-border);
      border-radius: 12px;
      padding: 20px;
      width: 90%;
      max-width: 440px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.5);
    }
    .ts-modal-title {
      font-size: 1.05rem;
      font-weight: 700;
      margin-bottom: 12px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
  </style>
</head>
<body>

  <!-- Top App Bar -->
  <header class="ts-header">
    <div class="ts-brand">
      <span>🎨</span> Frame Theme Studio
      <span class="ts-badge">dev mode</span>
    </div>

    <div class="ts-center-controls">
      <!-- Selector de Vista Real del Proyecto (App/Route) -->
      <div class="ts-route-bar">
        <span style="font-size:0.75rem; color:var(--ts-muted); font-weight:600;">Vista:</span>
        <select id="projectRouteSelect" class="ts-route-select" onchange="navigateProjectRoute(this.value)" title="Rutas GET detectadas en App/Route">
          <?php foreach (($projectRoutes ?? [['uri' => '/', 'label' => '/ (Vista Principal)']]) as $routeItem): ?>
            <option value="<?= htmlspecialchars($routeItem['uri']) ?>"><?= htmlspecialchars($routeItem['label']) ?></option>
          <?php endforeach; ?>
        </select>
        <input type="text" id="projectRouteInput" class="ts-route-input" value="/" onkeydown="if(event.key==='Enter') navigateProjectRoute(this.value)" title="Ruta activa del proyecto (presiona Enter para ir)">
        <button type="button" class="ts-icon-btn" onclick="reloadProjectView()" title="Recargar vista del proyecto">
          🔄
        </button>
        <button type="button" class="ts-icon-btn" onclick="openProjectInNewTab()" title="Abrir vista actual en nueva pestaña">
          ↗️
        </button>
      </div>

      <!-- Alternador Claro / Oscuro en Vivo -->
      <button type="button" class="ts-mode-btn active" id="btnModeLight" onclick="setPreviewMode('light')">
        ☀️ Claro
      </button>
      <button type="button" class="ts-mode-btn" id="btnModeDark" onclick="setPreviewMode('dark')">
        🌙 Oscuro
      </button>

      <!-- Selector de Tema / Paleta Guardada -->
      <div class="ts-theme-bar">
        <span style="font-size:0.75rem; color:var(--ts-muted); font-weight:600;">Tema:</span>
        <select id="themeSelector" class="ts-theme-select" onchange="switchThemeTo(this.value)" title="Paletas guardadas">
          <?php foreach (($allThemes ?? ['default' => $config]) as $s => $thm): ?>
            <?php 
              $isAct = ($s === ($activeThemeSlug ?? 'default'));
              $isSched = !empty($thm['schedule']['enabled']);
              $b = $isAct ? ' ⭐' : ($isSched ? ' 📅' : '');
            ?>
            <option value="<?= htmlspecialchars($s) ?>" <?= $s === ($config['themeSlug'] ?? 'default') ? 'selected' : '' ?>>
              <?= htmlspecialchars($thm['name'] ?? $s) ?><?= $b ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="ts-icon-btn" onclick="openSaveThemeModal()" title="Guardar paleta actual como nuevo tema...">
          ➕
        </button>
      </div>

      <!-- Controles de Viewport Responsivo Real -->
      <div class="ts-viewport-btns">
        <button type="button" class="ts-view-btn active" onclick="setViewport('100%', this)" title="Escritorio Completo">🖥️ 100%</button>
        <button type="button" class="ts-view-btn" onclick="setViewport('1200px', this)" title="Laptop / Contenedor 1200px">💻 1200px</button>
        <button type="button" class="ts-view-btn" onclick="setViewport('768px', this)" title="Tablet 768px">📱 768px</button>
        <button type="button" class="ts-view-btn" onclick="setViewport('380px', this)" title="Móvil 380px">📱 380px</button>
      </div>
    </div>

    <div class="ts-actions">
      <button type="button" class="ts-btn ts-btn-secondary" onclick="resetDefaults()" title="Restaurar a los colores definidos en _configTheme.css">
        🔄 Reset
      </button>
      <button type="button" class="ts-btn ts-btn-primary" id="btnSave" onclick="saveTheme()">
        💾 Guardar y Aplicar
      </button>
    </div>
  </header>

  <!-- Main View (Sidebar + Live Project View) -->
  <div class="ts-main">
    
    <!-- Left Configuration Sidebar -->
    <aside class="ts-sidebar">
      <div class="ts-tabs">
        <button type="button" class="ts-tab-btn active" onclick="switchTab('themes', this)">🏷️ Temas</button>
        <button type="button" class="ts-tab-btn" onclick="switchTab('colors', this)">🎨 Colores</button>
        <button type="button" class="ts-tab-btn" onclick="switchTab('dark', this)">🌙 Oscuro</button>
        <button type="button" class="ts-tab-btn" onclick="switchTab('wcag', this)">♿ WCAG</button>
        <button type="button" class="ts-tab-btn" onclick="switchTab('gradients', this)">🌈 Gradientes</button>
        <button type="button" class="ts-tab-btn" onclick="switchTab('layout', this)">🔤 Layout</button>
        <button type="button" class="ts-tab-btn" onclick="switchTab('json', this)">📋 JSON</button>
      </div>

      <!-- TAB 0: TEMAS Y EVENTOS ESPECIALES -->
      <div id="tab-themes" class="ts-tab-content active">
        
        <div style="margin-bottom: 14px;">
          <button type="button" class="ts-btn ts-btn-primary" style="width:100%; justify-content:center; padding:9px;" onclick="openSaveThemeModal()">
            ➕ Guardar Paleta Actual como Nuevo Tema
          </button>
        </div>

        <!-- Lista de Temas Guardados -->
        <div class="ts-section">
          <div class="ts-section-title">
            <span>Paletas y Diseños Guardados</span>
            <span id="themesCountBadge" style="font-size:0.75rem; color:var(--ts-muted);"></span>
          </div>
          <div id="themesListContainer">
            <!-- Renderizado dinámico vía JS -->
          </div>
        </div>

        <!-- Programación por Calendario / Fechas Especiales -->
        <div class="ts-section">
          <div class="ts-section-title">
            <span>📅 Programación de Calendario (<span id="schedThemeName">Tema Actual</span>)</span>
          </div>
          <p style="font-size:0.75rem; color:var(--ts-muted); margin-bottom:10px; line-height:1.4;">
            Activa este tema automáticamente en una fecha o evento especial (Navidad, Cyber Monday, Año Nuevo). Al terminar el rango, el sistema volverá automáticamente al tema <strong>Predeterminado</strong>.
          </p>

          <div style="background: rgba(15, 23, 42, 0.6); padding: 12px; border-radius: 8px; border: 1px solid var(--ts-border);">
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.85rem; font-weight:600; margin-bottom:10px;">
              <input type="checkbox" id="schedEnabled" onchange="toggleScheduleForm(this.checked)">
              Activar automáticamente por fecha
            </label>

            <div id="schedFormFields" style="display:none;">
              <div class="ts-field" style="margin-bottom:8px;">
                <label for="schedStart" style="font-size:0.75rem;">Fecha Inicio (YYYY-MM-DD o MM-DD)</label>
                <input type="text" id="schedStart" class="ts-route-input" style="width:100%;" placeholder="ej. 12-28 o 2026-11-20">
              </div>

              <div class="ts-field" style="margin-bottom:8px;">
                <label for="schedEnd" style="font-size:0.75rem;">Fecha Fin (YYYY-MM-DD o MM-DD)</label>
                <input type="text" id="schedEnd" class="ts-route-input" style="width:100%;" placeholder="ej. 01-05 o 2026-11-30">
              </div>

              <div style="margin-bottom:12px;">
                <label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-size:0.78rem; color:var(--ts-muted);">
                  <input type="checkbox" id="schedRecurring" checked>
                  🔁 Recurrente anualmente (todos los años)
                </label>
              </div>

              <button type="button" class="ts-btn ts-btn-secondary" style="width:100%; padding:7px; font-size:0.8rem;" onclick="saveCurrentThemeSchedule()">
                💾 Guardar Programación de Fecha
              </button>
            </div>
          </div>
        </div>

      </div>

      <!-- TAB 1: COLORES MODO CLARO -->
      <div id="tab-colors" class="ts-tab-content">
        
        <!-- Pegado Rápido Batch (Coloristia / CSS / HEX) -->
        <div class="ts-batch-area">
          <div class="ts-section-title">
            <span>Pegado Rápido (Coloristia / CSS / HEX)</span>
            <span style="font-size:0.7rem; color:var(--ts-muted);">3 a 20 colores</span>
          </div>
          <textarea id="batchInput" class="ts-batch-textarea" placeholder="Pega aquí código CSS de Coloristia (:root { --color-step-1: ... }), variables CSS o códigos HEX..."></textarea>
          <button type="button" class="ts-btn ts-btn-secondary" style="width:100%; padding:6px; font-size:0.78rem;" onclick="applyBatchPaste()">
            📥 Aplicar Colores al Modo Claro
          </button>
        </div>

        <!-- Paleta Base Dinámica (3 a 20 Colores) -->
        <div class="ts-section">
          <div class="ts-section-title">
            <span>Paleta Base (<span id="paletteRangeLabel">--back-color1..N</span>)</span>
            <span id="paletteCountBadge" style="font-size:0.72rem; color:var(--ts-primary); font-weight:700;"></span>
          </div>
          <div id="lightColorsList">
            <!-- Renderizado dinámico vía JS -->
          </div>
          <button type="button" class="ts-btn-add-color" id="btnAddColor" onclick="addColorSlot()">
            ➕ Agregar Color (<span id="btnAddColorCount"></span>/20)
          </button>
        </div>

        <!-- Fondo y Textos -->
        <div class="ts-section">
          <div class="ts-section-title">Fondo y Textos (Modo Claro)</div>
          
          <div class="ts-color-row-fixed">
            <input type="color" id="lightBodySwatch" class="ts-color-swatch-input" oninput="syncColorInput('lightBody', this.value)">
            <div class="ts-color-info">--body <span>Fondo principal</span></div>
            <input type="text" id="lightBodyHex" class="ts-hex-input" oninput="syncHexInput('lightBody', this.value)">
            <div></div>
          </div>

          <div class="ts-color-row-fixed">
            <input type="color" id="lightTextPrimarySwatch" class="ts-color-swatch-input" oninput="syncColorInput('lightTextPrimary', this.value)">
            <div class="ts-color-info">--text-primary <span>Texto títulos y párrafos</span></div>
            <input type="text" id="lightTextPrimaryHex" class="ts-hex-input" oninput="syncHexInput('lightTextPrimary', this.value)">
            <div></div>
          </div>

          <div class="ts-color-row-fixed">
            <input type="color" id="lightTextSecondarySwatch" class="ts-color-swatch-input" oninput="syncColorInput('lightTextSecondary', this.value)">
            <div class="ts-color-info">--text-secondary <span>Texto sobre fondos oscuros</span></div>
            <input type="text" id="lightTextSecondaryHex" class="ts-hex-input" oninput="syncHexInput('lightTextSecondary', this.value)">
            <div></div>
          </div>

          <div class="ts-color-row-fixed">
            <input type="color" id="lightTextInactiveSwatch" class="ts-color-swatch-input" oninput="syncColorInput('lightTextInactive', this.value)">
            <div class="ts-color-info">--text-inactive <span>Texto secundario o atenuado</span></div>
            <input type="text" id="lightTextInactiveHex" class="ts-hex-input" oninput="syncHexInput('lightTextInactive', this.value)">
            <div></div>
          </div>
        </div>

        <!-- Estados del Sistema -->
        <div class="ts-section">
          <div class="ts-section-title">Colores de Estado</div>
          
          <div class="ts-color-row-fixed">
            <input type="color" id="statusSuccessSwatch" class="ts-color-swatch-input" oninput="syncColorInput('statusSuccess', this.value)">
            <div class="ts-color-info">--success <span>Éxito</span></div>
            <input type="text" id="statusSuccessHex" class="ts-hex-input" oninput="syncHexInput('statusSuccess', this.value)">
            <div></div>
          </div>

          <div class="ts-color-row-fixed">
            <input type="color" id="statusCautionSwatch" class="ts-color-swatch-input" oninput="syncColorInput('statusCaution', this.value)">
            <div class="ts-color-info">--caution <span>Precaución</span></div>
            <input type="text" id="statusCautionHex" class="ts-hex-input" oninput="syncHexInput('statusCaution', this.value)">
            <div></div>
          </div>

          <div class="ts-color-row-fixed">
            <input type="color" id="statusDangerSwatch" class="ts-color-swatch-input" oninput="syncColorInput('statusDanger', this.value)">
            <div class="ts-color-info">--danger <span>Peligro</span></div>
            <input type="text" id="statusDangerHex" class="ts-hex-input" oninput="syncHexInput('statusDanger', this.value)">
            <div></div>
          </div>
        </div>

      </div>

      <!-- TAB 2: MODO OSCURO -->
      <div id="tab-dark" class="ts-tab-content">
        <!-- Pegado Rápido Modo Oscuro (Coloristia / CSS / HEX) -->
        <div class="ts-batch-area">
          <div class="ts-section-title">
            <span>Pegado Rápido Modo Oscuro (Coloristia / CSS / HEX)</span>
            <span style="font-size:0.7rem; color:var(--ts-muted);">3 a 20 colores</span>
          </div>
          <textarea id="darkBatchInput" class="ts-batch-textarea" placeholder="Pega aquí código CSS de Coloristia (:root { --color-step-1: ... }), variables CSS o códigos HEX para modo oscuro..."></textarea>
          <button type="button" class="ts-btn ts-btn-secondary" style="width:100%; padding:6px; font-size:0.78rem;" onclick="applyDarkBatchPaste()">
            📥 Aplicar Colores al Modo Oscuro
          </button>
        </div>

        <div class="ts-section">
          <div style="background: rgba(15, 23, 42, 0.6); padding: 12px; border-radius: 8px; border: 1px solid var(--ts-border); margin-bottom: 16px;">
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.85rem; font-weight:600;">
              <input type="checkbox" id="darkAuto" checked onchange="toggleDarkAuto(this.checked)">
              Modo Oscuro Automático (dark.auto: true)
            </label>
            <p style="font-size:0.75rem; color:var(--ts-muted); margin-top:6px; line-height:1.4;">
              Invierte inteligentemente la luminosidad de la paleta clara conservando tonos y aplicando desaturación ergonómica WCAG.
            </p>
            <button type="button" class="ts-btn ts-btn-secondary" style="width:100%; margin-top:10px; padding:6px; font-size:0.8rem;" onclick="generateAutoDarkMode()">
              ⚡ Calcular y Aplicar Modo Oscuro Ahora
            </button>
          </div>

          <div class="ts-section-title">Fondo y Textos (Modo Oscuro)</div>
          
          <div class="ts-color-row-fixed">
            <input type="color" id="darkBodySwatch" class="ts-color-swatch-input" oninput="syncColorInput('darkBody', this.value)">
            <div class="ts-color-info">--body (Dark) <span>Fondo oscuro</span></div>
            <input type="text" id="darkBodyHex" class="ts-hex-input" oninput="syncHexInput('darkBody', this.value)">
            <div></div>
          </div>

          <div class="ts-color-row-fixed">
            <input type="color" id="darkTextPrimarySwatch" class="ts-color-swatch-input" oninput="syncColorInput('darkTextPrimary', this.value)">
            <div class="ts-color-info">--text-primary (Dark) <span>Texto principal</span></div>
            <input type="text" id="darkTextPrimaryHex" class="ts-hex-input" oninput="syncHexInput('darkTextPrimary', this.value)">
            <div></div>
          </div>

          <div class="ts-color-row-fixed">
            <input type="color" id="darkTextSecondarySwatch" class="ts-color-swatch-input" oninput="syncColorInput('darkTextSecondary', this.value)">
            <div class="ts-color-info">--text-secondary (Dark) <span>Texto secundario</span></div>
            <input type="text" id="darkTextSecondaryHex" class="ts-hex-input" oninput="syncHexInput('darkTextSecondary', this.value)">
            <div></div>
          </div>

          <div class="ts-color-row-fixed">
            <input type="color" id="darkTextInactiveSwatch" class="ts-color-swatch-input" oninput="syncColorInput('darkTextInactive', this.value)">
            <div class="ts-color-info">--text-inactive (Dark) <span>Texto atenuado</span></div>
            <input type="text" id="darkTextInactiveHex" class="ts-hex-input" oninput="syncHexInput('darkTextInactive', this.value)">
            <div></div>
          </div>
        </div>

        <div class="ts-section">
          <div class="ts-section-title">
            <span>Paleta Oscura (<span id="darkPaletteRangeLabel">--back-color1..N</span>)</span>
            <span id="darkPaletteCountBadge" style="font-size:0.72rem; color:var(--ts-muted);"></span>
          </div>
          <div id="darkColorsList">
            <!-- Renderizado dinámico vía JS -->
          </div>
        </div>
      </div>

      <!-- TAB 3: ACCESIBILIDAD Y MATRIZ WCAG EN VIVO -->
      <div id="tab-wcag" class="ts-tab-content">
        <div class="ts-section">
          <div class="ts-section-title">
            <span>Diagnóstico de Contraste WCAG 2.1</span>
            <span id="wcagOverallBadge" class="ts-wcag-badge ts-wcag-aa">Calculando...</span>
          </div>
          <p style="font-size:0.76rem; color:var(--ts-muted); margin-bottom:12px; line-height:1.4;">
            Verifica el ratio de contraste de cada fondo respecto a los colores de texto configurados. 
            <strong>AA:</strong> ratio &ge; 4.5:1 (texto normal). <strong>AAA:</strong> ratio &ge; 7:1.
          </p>
          <div id="wcagMatrixContainer">
            <!-- Generado dinámicamente -->
          </div>
        </div>
      </div>

      <!-- TAB 4: GRADIENTES Y COMBINACIONES -->
      <div id="tab-gradients" class="ts-tab-content">
        <div class="ts-section">
          <div class="ts-section-title">
            <span>Gradientes Coloristia</span>
            <span style="font-size:0.72rem; color:var(--ts-primary);">CSS Puro</span>
          </div>
          <p style="font-size:0.76rem; color:var(--ts-muted); margin-bottom:14px; line-height:1.4;">
            Combinaciones dinámicas calculadas automáticamente a partir de tu paleta activa. Haz clic en "Copiar" para usar en tus componentes.
          </p>
          <div id="sidebarGradientsContainer">
            <!-- Generado dinámicamente -->
          </div>
        </div>
      </div>

      <!-- TAB 5: LAYOUT Y TIPOGRAFÍA -->
      <div id="tab-layout" class="ts-tab-content">
        <div class="ts-section">
          <div class="ts-section-title">Tipografía Global (--font)</div>
          
          <div class="ts-field">
            <label for="fontSelect">Seleccionar Pila Tipográfica</label>
            <select id="fontSelect" class="ts-select" onchange="onFontSelectChange(this.value)">
              <?php foreach ($fonts as $font): ?>
                <option value="<?= htmlspecialchars($font['value']) ?>"><?= htmlspecialchars($font['name']) ?></option>
              <?php endforeach; ?>
              <option value="custom">✏️ Personalizada (Escribir abajo)</option>
            </select>
          </div>

          <div class="ts-field">
            <label for="fontCustom">Familia de Fuente Personalizada</label>
            <input type="text" id="fontCustom" class="ts-input-text" oninput="updateLayoutProperty('font', this.value)">
          </div>
        </div>

        <div class="ts-section">
          <div class="ts-section-title">Dimensiones de Pantalla y Texto</div>
          
          <div class="ts-field">
            <label for="fontSizeRange">Tamaño Base (--font-size)</label>
            <div class="ts-range-box">
              <input type="range" id="fontSizeRange" min="14" max="26" step="1" class="ts-range-input" oninput="updateFontSize(this.value)">
              <span id="fontSizeVal" class="ts-range-val">20px</span>
            </div>
          </div>

          <div class="ts-field">
            <label for="widthWindowRange">Ancho Máximo de Ventana (--width-window)</label>
            <div class="ts-range-box">
              <input type="range" id="widthWindowRange" min="960" max="1800" step="10" class="ts-range-input" oninput="updateWidthWindow(this.value)">
              <span id="widthWindowVal" class="ts-range-val">1500px</span>
            </div>
          </div>

          <div class="ts-field">
            <label for="scrollbarWidthSelect">Ancho de Barra de Desplazamiento (--scrollbar-width)</label>
            <select id="scrollbarWidthSelect" class="ts-select" onchange="updateLayoutProperty('scrollbarWidth', this.value)">
              <option value="thin">thin (Delgada)</option>
              <option value="auto">auto (Estándar del sistema)</option>
              <option value="none">none (Oculta)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- TAB 6: JSON CRUDO -->
      <div id="tab-json" class="ts-tab-content">
        <div class="ts-section">
          <div class="ts-section-title">
            <span>theme.json en Vivo</span>
            <button type="button" class="ts-btn ts-btn-secondary" style="padding:4px 8px; font-size:0.75rem;" onclick="copyJsonToClipboard()">
              📋 Copiar
            </button>
          </div>
          <textarea id="jsonViewer" class="ts-batch-textarea" style="height:480px; font-size:0.8rem; background:var(--ts-bg);" readonly></textarea>
        </div>
      </div>

    </aside>

    <!-- Right Live Real Project View Stage -->
    <main class="ts-sandbox-stage">
      <div class="ts-sandbox-window" id="sandboxWindow">
        <div id="sandbox">
          <!-- Renderizado en vivo de la vista real del proyecto (App/Route -> "/") con inyección dinámica de _configTheme.css -->
          <iframe id="projectPreviewFrame" src="/" title="Vista en vivo del proyecto"></iframe>
        </div>
      </div>
    </main>

  </div>

  <!-- Modal Guardar Nuevo Tema -->
  <div id="modalSaveTheme" class="ts-modal-overlay">
    <div class="ts-modal-box">
      <div class="ts-modal-title">
        <span>➕ Guardar como Nuevo Tema</span>
        <button type="button" class="ts-icon-btn" onclick="closeSaveThemeModal()">✕</button>
      </div>
      <p style="font-size:0.8rem; color:var(--ts-muted); margin-bottom:14px; line-height:1.4;">
        Guarda la paleta cromática y estilos actuales con un identificador único para activarlo manualmente o programarlo en fechas especiales (Navidad, Año Nuevo, Black Friday, etc.).
      </p>
      <div class="ts-field" style="margin-bottom:12px;">
        <label for="newThemeName">Nombre del Tema</label>
        <input type="text" id="newThemeName" class="ts-route-input" style="width:100%;" placeholder="ej. Año Nuevo" oninput="autoGenerateThemeSlug(this.value)">
      </div>
      <div class="ts-field" style="margin-bottom:14px;">
        <label for="newThemeSlug">Identificador / Clase CSS (slug)</label>
        <input type="text" id="newThemeSlug" class="ts-route-input" style="width:100%; font-family:monospace;" placeholder="ej. new-year">
        <span style="font-size:0.72rem; color:var(--ts-muted); margin-top:4px;">
          Permitirá clases: <code style="color:var(--ts-primary);">.only-theme-slug</code> y <code style="color:var(--ts-primary);">.no-theme-slug</code>
        </span>
      </div>
      <div style="margin-bottom:16px;">
        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.82rem;">
          <input type="checkbox" id="newThemeSetActive" checked>
          Establecer inmediatamente como tema principal
        </label>
      </div>
      <div style="display:flex; justify-content:flex-end; gap:8px;">
        <button type="button" class="ts-btn ts-btn-secondary" onclick="closeSaveThemeModal()">Cancelar</button>
        <button type="button" class="ts-btn ts-btn-primary" onclick="submitSaveNewTheme()">Guardar Tema</button>
      </div>
    </div>
  </div>

  <!-- Toast Notification Container -->
  <div id="toast" class="ts-toast">
    <span id="toastIcon">✅</span>
    <span id="toastMsg">Mensaje de notificación</span>
  </div>

  <!-- Pure Vanilla Client-Side JavaScript Engine -->
  <script>
    // Configuración base leída desde App/Public/Css/_configTheme.css del proyecto
    const defaultCssConfig = <?= $rawDefaultJson ?>;
    // Configuración activa (cargada desde theme.json o defaultCssConfig)
    const initialConfig = <?= $rawJson ?>;
    const initialFullConfig = <?= $rawFullJson ?? json_encode(['activeTheme' => 'default', 'themes' => ['default' => $config]]) ?>;

    let fullThemeConfig = JSON.parse(JSON.stringify(initialFullConfig));
    let activeThemeSlug = '<?= $activeThemeSlug ?? "default" ?>';
    let currentThemeSlug = '<?= $config["themeSlug"] ?? "default" ?>';
    let themeConfig = JSON.parse(JSON.stringify(initialConfig));
    let currentPreviewMode = 'light'; // 'light' o 'dark'
    let iframeThemeObserver = null;

    // ==============================================================
    // UTILIDADES DE COLOR (RGB, HSL, HOVER, WCAG 2.1)
    // ==============================================================

    function hexToRgb(hex) {
      if (!hex || typeof hex !== 'string') return [0, 0, 0];
      hex = hex.replace(/^#/, '');
      if (hex.length === 3) {
        hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
      }
      if (hex.length !== 6) return [0, 0, 0];
      return [
        parseInt(hex.substring(0, 2), 16) || 0,
        parseInt(hex.substring(2, 4), 16) || 0,
        parseInt(hex.substring(4, 6), 16) || 0
      ];
    }

    function rgbToHex(r, g, b) {
      const clamp = (v) => Math.max(0, Math.min(255, Math.round(v)));
      const toHex = (v) => clamp(v).toString(16).padStart(2, '0').toUpperCase();
      return '#' + toHex(r) + toHex(g) + toHex(b);
    }

    function rgbToHsl(r, g, b) {
      r /= 255; g /= 255; b /= 255;
      const max = Math.max(r, g, b), min = Math.min(r, g, b);
      let h, s, l = (max + min) / 2;

      if (max === min) {
        h = s = 0;
      } else {
        const d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        switch (max) {
          case r: h = (g - b) / d + (g < b ? 6 : 0); break;
          case g: h = (b - r) / d + 2; break;
          case b: h = (r - g) / d + 4; break;
        }
        h /= 6;
      }
      return [h * 360, s, l];
    }

    function hslToRgb(h, s, l) {
      h = (h % 360 + 360) % 360 / 360;
      if (s === 0) {
        const v = Math.round(l * 255);
        return [v, v, v];
      }
      const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
      const p = 2 * l - q;
      const hue2rgb = (p, q, t) => {
        if (t < 0) t += 1;
        if (t > 1) t -= 1;
        if (t < 1/6) return p + (q - p) * 6 * t;
        if (t < 1/2) return q;
        if (t < 2/3) return p + (q - p) * (2/3 - t) * 6;
        return p;
      };
      return [
        Math.round(hue2rgb(p, q, h + 1/3) * 255),
        Math.round(hue2rgb(p, q, h) * 255),
        Math.round(hue2rgb(p, q, h - 1/3) * 255)
      ];
    }

    function sRgbToLinear(c) {
      c = c / 255;
      return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
    }

    function getRelativeLuminance(hex) {
      const [r, g, b] = hexToRgb(hex);
      const rLin = sRgbToLinear(r);
      const gLin = sRgbToLinear(g);
      const bLin = sRgbToLinear(b);
      return 0.2126 * rLin + 0.7152 * gLin + 0.0722 * bLin;
    }

    function getContrastRatio(hex1, hex2) {
      const lum1 = getRelativeLuminance(hex1);
      const lum2 = getRelativeLuminance(hex2);
      const lMax = Math.max(lum1, lum2);
      const lMin = Math.min(lum1, lum2);
      return (lMax + 0.05) / (lMin + 0.05);
    }

    function getWcagRating(ratio) {
      if (ratio >= 7.0) {
        return { level: 'AAA', badge: 'ts-wcag-aaa', pass: true, desc: 'Excelente (AAA normal y grande)' };
      }
      if (ratio >= 4.5) {
        return { level: 'AA', badge: 'ts-wcag-aa', pass: true, desc: 'Aprobado (AA texto normal)' };
      }
      if (ratio >= 3.0) {
        return { level: 'AA Lg', badge: 'ts-wcag-aa-lg', pass: true, desc: 'Aprobado solo para texto grande' };
      }
      return { level: 'Falla', badge: 'ts-wcag-fail', pass: false, desc: 'Contraste insuficiente (<3.0:1)' };
    }

    function calculateHoverHex(hex, factor = 1.15) {
      const [r, g, b] = hexToRgb(hex);
      const [h, s, l] = rgbToHsl(r, g, b);
      const newL = l < 0.75 ? Math.min(0.95, l * factor) : Math.max(0.1, l / factor);
      const [nr, ng, nb] = hslToRgb(h, s, newL);
      return rgbToHex(nr, ng, nb);
    }

    function invertToDarkHex(hex) {
      const [r, g, b] = hexToRgb(hex);
      const [h, s, l] = rgbToHsl(r, g, b);
      const newL = Math.max(0.18, Math.min(0.82, 1.0 - l));
      const newS = Math.max(0.0, Math.min(1.0, s * 0.88));
      const [nr, ng, nb] = hslToRgb(h, newS, newL);
      return rgbToHex(nr, ng, nb);
    }

    // ==============================================================
    // RENDERIZADO Y SINCRONIZACIÓN DE LA UI
    // ==============================================================

    function initStudio() {
      // 0. Renderizar lista de temas y selector
      renderThemesTab();
      // 1. Renderizar inputs de paleta clara
      renderColorSlots('light');
      // 2. Renderizar inputs de paleta oscura
      renderColorSlots('dark');
      // 3. Inicializar valores de formulario
      syncFormValues();
      // 4. Configurar iframe de vista real del proyecto
      setupProjectIframe();
      // 5. Inyectar variables en vivo
      applySandboxStyles();
      // 6. Actualizar visor JSON
      updateJsonViewer();
    }

    function setupProjectIframe() {
      const frame = document.getElementById('projectPreviewFrame');
      if (!frame || frame.dataset.listenerAttached === '1') return;
      frame.dataset.listenerAttached = '1';

      frame.addEventListener('load', () => {
        try {
          const doc = frame.contentDocument || (frame.contentWindow && frame.contentWindow.document);
          if (!doc) return;

          // Detectar en la primera carga si theme.css del proyecto define un --width-window específico (ej. 1200px)
          if (!window.__initialProjectWidthDetected && !doc.getElementById('frame-theme-studio-live-vars')) {
            window.__initialProjectWidthDetected = true;
            const computedWidth = frame.contentWindow.getComputedStyle(doc.documentElement).getPropertyValue('--width-window').trim();
            if (computedWidth && /^\d+px$/i.test(computedWidth)) {
              themeConfig.layout.widthWindow = computedWidth;
              defaultCssConfig.layout.widthWindow = computedWidth;
              syncFormValues();
              updateJsonViewer();
            }
          }

          // Actualizar la barra de ruta con la ruta actual del iframe
          const currentPath = frame.contentWindow.location.pathname + frame.contentWindow.location.search + frame.contentWindow.location.hash;
          const routeInput = document.getElementById('projectRouteInput');
          if (routeInput && currentPath) {
            routeInput.value = currentPath;
          }
          const routeSelect = document.getElementById('projectRouteSelect');
          if (routeSelect) {
            const matchOpt = Array.from(routeSelect.options).find(opt => opt.value === frame.contentWindow.location.pathname);
            if (matchOpt) routeSelect.value = matchOpt.value;
          }

          // Aplicar modo claro/oscuro actual e inyectar variables CSS en vivo
          syncIframeThemeAttribute(doc, frame.contentWindow);
          injectLiveThemeIntoIframe();

          // Observar si el usuario cambia el tema desde el botón propio dentro de la vista del proyecto
          if (iframeThemeObserver) {
            iframeThemeObserver.disconnect();
          }
          iframeThemeObserver = new MutationObserver((mutations) => {
            for (const m of mutations) {
              if (m.type === 'attributes' && m.attributeName === 'data-theme') {
                const isDarkNow = doc.documentElement.getAttribute('data-theme') === 'dark';
                const newMode = isDarkNow ? 'dark' : 'light';
                if (newMode !== currentPreviewMode) {
                  currentPreviewMode = newMode;
                  document.getElementById('btnModeLight').classList.toggle('active', newMode === 'light');
                  document.getElementById('btnModeDark').classList.toggle('active', newMode === 'dark');
                  applySandboxStyles();
                }
              }
            }
          });
          iframeThemeObserver.observe(doc.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
        } catch (e) {
          // Ignorar errores cross-origin si el usuario navega a un enlace externo
        }
      });
    }

    function syncIframeThemeAttribute(doc, win) {
      if (!doc || !doc.documentElement) return;
      try {
        if (currentPreviewMode === 'dark') {
          doc.documentElement.setAttribute('data-theme', 'dark');
          if (win && win.localStorage) win.localStorage.setItem('fme-theme', 'dark');
        } else {
          doc.documentElement.removeAttribute('data-theme');
          if (win && win.localStorage) win.localStorage.setItem('fme-theme', 'light');
        }
      } catch (e) {}
    }

    function navigateProjectRoute(routeUri) {
      if (!routeUri || typeof routeUri !== 'string') return;
      routeUri = routeUri.trim();
      if (!routeUri.startsWith('/') && !routeUri.startsWith('http')) {
        routeUri = '/' + routeUri;
      }
      const frame = document.getElementById('projectPreviewFrame');
      const routeInput = document.getElementById('projectRouteInput');
      if (routeInput) routeInput.value = routeUri;
      if (frame) {
        frame.src = routeUri;
      }
    }

    function reloadProjectView() {
      const frame = document.getElementById('projectPreviewFrame');
      if (frame && frame.contentWindow) {
        frame.contentWindow.location.reload();
        showToast('🔄 Vista del proyecto recargada');
      }
    }

    function openProjectInNewTab() {
      const routeInput = document.getElementById('projectRouteInput');
      const uri = (routeInput && routeInput.value) ? routeInput.value : '/';
      window.open(uri, '_blank');
    }

    function renderColorSlots(mode) {
      const container = document.getElementById(mode === 'light' ? 'lightColorsList' : 'darkColorsList');
      if (!container) return;
      container.innerHTML = '';
      
      const colors = (mode === 'light') ? themeConfig.light.colors : themeConfig.dark.colors;
      const count = colors.length;

      // Actualizar contadores y badges
      if (mode === 'light') {
        const rangeLabel = document.getElementById('paletteRangeLabel');
        const countBadge = document.getElementById('paletteCountBadge');
        const btnAddCount = document.getElementById('btnAddColorCount');
        const btnAdd = document.getElementById('btnAddColor');

        if (rangeLabel) rangeLabel.textContent = (count === 1) ? '--back-color' : `--back-color1..${count}`;
        if (countBadge) countBadge.textContent = `${count} de 20`;
        if (btnAddCount) btnAddCount.textContent = count;
        if (btnAdd) {
          btnAdd.disabled = count >= 20;
          btnAdd.title = count >= 20 ? 'Límite máximo de 20 colores alcanzado' : 'Agregar nuevo color a la paleta';
        }
      } else {
        const darkRangeLabel = document.getElementById('darkPaletteRangeLabel');
        const darkCountBadge = document.getElementById('darkPaletteCountBadge');
        if (darkRangeLabel) darkRangeLabel.textContent = (count === 1) ? '--back-color' : `--back-color1..${count}`;
        if (darkCountBadge) darkCountBadge.textContent = `${count} colores`;
      }

      for (let i = 0; i < count; i++) {
        const hex = colors[i] || (mode === 'light' ? '#B3C4FF' : '#1869B4');
        const varName = (i === 0) ? '--back-color' : '--back-color' + (i + 1);
        const hoverHex = calculateHoverHex(hex);
        const canDelete = count > 3;

        const row = document.createElement('div');
        row.className = 'ts-color-row';
        row.innerHTML = `
          <input type="color" value="${hex}" class="ts-color-swatch-input" oninput="onColorSlotChange('${mode}', ${i}, this.value)" title="Seleccionar color">
          <div class="ts-color-info">${varName} <span>Color ${i + 1}</span></div>
          <input type="text" value="${hex}" class="ts-hex-input" id="${mode}Hex_${i}" oninput="onColorSlotHexInput('${mode}', ${i}, this.value)" maxlength="7">
          <div class="ts-hover-chip" id="${mode}Hover_${i}" style="background-color: ${hoverHex};" title="Hover: ${hoverHex}"></div>
          ${mode === 'light' ? `
            <button type="button" class="ts-btn-del-slot" onclick="removeColorSlot(${i})" ${canDelete ? '' : 'disabled'} title="${canDelete ? 'Eliminar este color' : 'Mínimo 3 colores requeridos'}">
              ✕
            </button>
          ` : `<div></div>`}
        `;
        container.appendChild(row);
      }
    }

    function addColorSlot() {
      if (themeConfig.light.colors.length >= 20) {
        showToast('⚠️ Máximo alcanzado: la paleta admite entre 3 y 20 colores.', 'error');
        return;
      }

      const lastColor = themeConfig.light.colors[themeConfig.light.colors.length - 1] || '#38BDF8';
      const [r, g, b] = hexToRgb(lastColor);
      const [h, s, l] = rgbToHsl(r, g, b);
      const newH = (h + 35) % 360;
      const [nr, ng, nb] = hslToRgb(newH, Math.max(0.4, s), Math.max(0.45, Math.min(0.85, l)));
      const newHex = rgbToHex(nr, ng, nb);

      themeConfig.light.colors.push(newHex);

      if (themeConfig.dark.auto) {
        themeConfig.dark.colors = themeConfig.light.colors.map(c => invertToDarkHex(c));
      } else if (themeConfig.dark.colors && themeConfig.dark.colors.length > 0) {
        themeConfig.dark.colors.push(invertToDarkHex(newHex));
      }

      renderColorSlots('light');
      renderColorSlots('dark');
      applySandboxStyles();
      updateJsonViewer();
      showToast(`➕ Color agregado (${themeConfig.light.colors.length}/20)`);
    }

    function removeColorSlot(index) {
      if (themeConfig.light.colors.length <= 3) {
        showToast('⚠️ Mínimo requerido: la paleta debe contener al menos 3 colores.', 'error');
        return;
      }

      themeConfig.light.colors.splice(index, 1);

      if (themeConfig.dark.auto) {
        themeConfig.dark.colors = themeConfig.light.colors.map(c => invertToDarkHex(c));
      } else if (themeConfig.dark.colors && themeConfig.dark.colors.length > index) {
        themeConfig.dark.colors.splice(index, 1);
      }

      renderColorSlots('light');
      renderColorSlots('dark');
      applySandboxStyles();
      updateJsonViewer();
      showToast(`🗑️ Color eliminado (${themeConfig.light.colors.length}/20)`);
    }

    function syncFormValues() {
      // Light
      document.getElementById('lightBodySwatch').value = themeConfig.light.body;
      document.getElementById('lightBodyHex').value = themeConfig.light.body;
      document.getElementById('lightTextPrimarySwatch').value = themeConfig.light.text.primary;
      document.getElementById('lightTextPrimaryHex').value = themeConfig.light.text.primary;
      document.getElementById('lightTextSecondarySwatch').value = themeConfig.light.text.secondary;
      document.getElementById('lightTextSecondaryHex').value = themeConfig.light.text.secondary;
      document.getElementById('lightTextInactiveSwatch').value = themeConfig.light.text.inactive;
      document.getElementById('lightTextInactiveHex').value = themeConfig.light.text.inactive;

      // Status
      document.getElementById('statusSuccessSwatch').value = themeConfig.status.success;
      document.getElementById('statusSuccessHex').value = themeConfig.status.success;
      document.getElementById('statusCautionSwatch').value = themeConfig.status.caution;
      document.getElementById('statusCautionHex').value = themeConfig.status.caution;
      document.getElementById('statusDangerSwatch').value = themeConfig.status.danger;
      document.getElementById('statusDangerHex').value = themeConfig.status.danger;

      // Dark
      document.getElementById('darkAuto').checked = !!themeConfig.dark.auto;
      document.getElementById('darkBodySwatch').value = themeConfig.dark.body;
      document.getElementById('darkBodyHex').value = themeConfig.dark.body;
      document.getElementById('darkTextPrimarySwatch').value = themeConfig.dark.text.primary;
      document.getElementById('darkTextPrimaryHex').value = themeConfig.dark.text.primary;
      document.getElementById('darkTextSecondarySwatch').value = themeConfig.dark.text.secondary;
      document.getElementById('darkTextSecondaryHex').value = themeConfig.dark.text.secondary;
      document.getElementById('darkTextInactiveSwatch').value = themeConfig.dark.text.inactive;
      document.getElementById('darkTextInactiveHex').value = themeConfig.dark.text.inactive;

      // Layout
      const fs = parseInt(themeConfig.layout.fontSize) || 20;
      document.getElementById('fontSizeRange').value = fs;
      document.getElementById('fontSizeVal').textContent = fs + 'px';

      const ww = parseInt(themeConfig.layout.widthWindow) || 1500;
      document.getElementById('widthWindowRange').value = ww;
      document.getElementById('widthWindowVal').textContent = ww + 'px';

      document.getElementById('fontCustom').value = themeConfig.layout.font;
      document.getElementById('scrollbarWidthSelect').value = themeConfig.layout.scrollbarWidth || 'thin';
    }

    function onColorSlotChange(mode, index, hex) {
      hex = hex.toUpperCase();
      if (mode === 'light') {
        themeConfig.light.colors[index] = hex;
        if (themeConfig.dark.auto) {
          themeConfig.dark.colors[index] = invertToDarkHex(hex);
          const darkHexInput = document.getElementById(`darkHex_${index}`);
          if (darkHexInput) darkHexInput.value = themeConfig.dark.colors[index];
          const darkSwatch = document.querySelectorAll('#darkColorsList .ts-color-swatch-input')[index];
          if (darkSwatch) darkSwatch.value = themeConfig.dark.colors[index];
        }
      } else {
        themeConfig.dark.colors[index] = hex;
      }

      const hexInput = document.getElementById(`${mode}Hex_${index}`);
      if (hexInput) hexInput.value = hex;

      const hoverChip = document.getElementById(`${mode}Hover_${index}`);
      if (hoverChip) {
        const hover = calculateHoverHex(hex);
        hoverChip.style.backgroundColor = hover;
        hoverChip.title = `Hover: ${hover}`;
      }

      applySandboxStyles();
      updateJsonViewer();
    }

    function onColorSlotHexInput(mode, index, hex) {
      if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
        onColorSlotChange(mode, index, hex);
        const swatch = document.querySelectorAll(`#${mode}ColorsList .ts-color-swatch-input`)[index];
        if (swatch) swatch.value = hex;
      }
    }

    function syncColorInput(key, hex) {
      hex = hex.toUpperCase();
      const hexInput = document.getElementById(key + 'Hex');
      if (hexInput) hexInput.value = hex;
      applyIndividualField(key, hex);
    }

    function syncHexInput(key, hex) {
      if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
        const swatch = document.getElementById(key + 'Swatch');
        if (swatch) swatch.value = hex;
        applyIndividualField(key, hex);
      }
    }

    function applyIndividualField(key, hex) {
      switch (key) {
        case 'lightBody': themeConfig.light.body = hex; break;
        case 'lightTextPrimary': themeConfig.light.text.primary = hex; break;
        case 'lightTextSecondary': themeConfig.light.text.secondary = hex; break;
        case 'lightTextInactive': themeConfig.light.text.inactive = hex; break;
        case 'statusSuccess': themeConfig.status.success = hex; break;
        case 'statusCaution': themeConfig.status.caution = hex; break;
        case 'statusDanger': themeConfig.status.danger = hex; break;
        case 'darkBody': themeConfig.dark.body = hex; break;
        case 'darkTextPrimary': themeConfig.dark.text.primary = hex; break;
        case 'darkTextSecondary': themeConfig.dark.text.secondary = hex; break;
        case 'darkTextInactive': themeConfig.dark.text.inactive = hex; break;
      }
      applySandboxStyles();
      updateJsonViewer();
    }

    function updateFontSize(val) {
      themeConfig.layout.fontSize = val + 'px';
      document.getElementById('fontSizeVal').textContent = val + 'px';
      applySandboxStyles();
      updateJsonViewer();
    }

    function updateWidthWindow(val) {
      themeConfig.layout.widthWindow = val + 'px';
      document.getElementById('widthWindowVal').textContent = val + 'px';
      applySandboxStyles();
      updateJsonViewer();
    }

    function updateLayoutProperty(prop, val) {
      themeConfig.layout[prop] = val;
      applySandboxStyles();
      updateJsonViewer();
    }

    function onFontSelectChange(val) {
      if (val !== 'custom') {
        document.getElementById('fontCustom').value = val;
        updateLayoutProperty('font', val);
      }
    }

    function toggleDarkAuto(checked) {
      themeConfig.dark.auto = checked;
      if (checked) {
        generateAutoDarkMode();
      }
      updateJsonViewer();
    }

    // ==============================================================
    // PARSER UNIVERSAL DE COLORES (COLORISTIA, CSS VARIABLES, HEX)
    // ==============================================================

    function parseColorsFromInput(text) {
      if (!text || typeof text !== 'string') return [];

      // 1. Detectar si contiene steps de Coloristia (--color-step-X: #HEX)
      const stepRegex = /--color-step-(\d+)\s*:\s*(#[0-9A-Fa-f]{3,8})/gi;
      const steps = {};
      let match;
      let hasSteps = false;

      while ((match = stepRegex.exec(text)) !== null) {
        hasSteps = true;
        const idx = parseInt(match[1], 10);
        let hex = match[2].toUpperCase();
        if (hex.length === 4) {
          hex = '#' + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3];
        } else if (hex.length > 7) {
          hex = hex.substring(0, 7);
        }
        steps[idx] = hex;
      }

      if (hasSteps) {
        const sortedIndices = Object.keys(steps).map(k => parseInt(k, 10)).sort((a, b) => a - b);
        const sortedColors = sortedIndices.map(k => steps[k]);
        if (sortedColors.length >= 3) {
          return sortedColors.slice(0, 20);
        }
      }

      // 2. Limpiar comentarios CSS (/* ... */)
      const clean = text.replace(/\/\*[\s\S]*?\*\//g, '');

      // 3. Buscar cualquier variable CSS que declare un código HEX (--var: #HEX)
      const varRegex = /--[\w-]+\s*:\s*(#[0-9A-Fa-f]{3,8})/gi;
      const varColors = [];
      while ((match = varRegex.exec(clean)) !== null) {
        let hex = match[1].toUpperCase();
        if (hex.length === 4) {
          hex = '#' + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3];
        } else if (hex.length > 7) {
          hex = hex.substring(0, 7);
        }
        varColors.push(hex);
      }
      if (varColors.length >= 3) {
        return varColors.slice(0, 20);
      }

      // 4. Buscar códigos HEX directos en el texto limpio
      const directMatches = clean.match(/#[0-9A-Fa-f]{6}|#[0-9A-Fa-f]{3}/g);
      if (directMatches && directMatches.length >= 3) {
        const hexList = [];
        for (let i = 0; i < Math.min(20, directMatches.length); i++) {
          let hex = directMatches[i].toUpperCase();
          if (hex.length === 4) {
            hex = '#' + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3];
          }
          hexList.push(hex);
        }
        return hexList;
      }

      return [];
    }

    // ==============================================================
    // GENERADORES Y APLICACIÓN DE PALETAS (MODO CLARO Y OSCURO)
    // ==============================================================

    function generateAutoDarkMode() {
      themeConfig.dark.colors = themeConfig.light.colors.map(c => invertToDarkHex(c));
      renderColorSlots('dark');
      applySandboxStyles();
      updateJsonViewer();
      showToast('⚡ Paleta oscura calculada con contrastes WCAG');
    }

    function applyBatchPaste() {
      const text = document.getElementById('batchInput').value;
      const parsedColors = parseColorsFromInput(text);

      if (parsedColors.length < 3) {
        showToast('⚠️ Debes ingresar al menos 3 colores válidos (soporta CSS de Coloristia, variables o HEX)', 'error');
        return;
      }

      themeConfig.light.colors = parsedColors;

      if (themeConfig.dark.auto) {
        themeConfig.dark.colors = parsedColors.map(c => invertToDarkHex(c));
      } else if (themeConfig.dark.colors && themeConfig.dark.colors.length > 0) {
        if (themeConfig.dark.colors.length < parsedColors.length) {
          const diff = parsedColors.length - themeConfig.dark.colors.length;
          for (let i = 0; i < diff; i++) {
            themeConfig.dark.colors.push(invertToDarkHex(parsedColors[themeConfig.dark.colors.length]));
          }
        } else if (themeConfig.dark.colors.length > parsedColors.length) {
          themeConfig.dark.colors = themeConfig.dark.colors.slice(0, parsedColors.length);
        }
      }

      renderColorSlots('light');
      renderColorSlots('dark');
      applySandboxStyles();
      updateJsonViewer();
      showToast(`🎨 ${parsedColors.length} colores de Coloristia aplicados al Modo Claro`);
    }

    function applyDarkBatchPaste() {
      const text = document.getElementById('darkBatchInput').value;
      const parsedColors = parseColorsFromInput(text);

      if (parsedColors.length < 3) {
        showToast('⚠️ Debes ingresar al menos 3 colores válidos para modo oscuro (soporta CSS de Coloristia o HEX)', 'error');
        return;
      }

      themeConfig.dark.auto = false;
      const darkAutoEl = document.getElementById('darkAuto');
      if (darkAutoEl) darkAutoEl.checked = false;

      themeConfig.dark.colors = parsedColors;

      if (themeConfig.light.colors.length < parsedColors.length) {
        const diff = parsedColors.length - themeConfig.light.colors.length;
        for (let i = 0; i < diff; i++) {
          themeConfig.light.colors.push(invertToDarkHex(parsedColors[themeConfig.light.colors.length]));
        }
      } else if (themeConfig.light.colors.length > parsedColors.length) {
        themeConfig.light.colors = themeConfig.light.colors.slice(0, parsedColors.length);
      }

      renderColorSlots('light');
      renderColorSlots('dark');
      applySandboxStyles();
      updateJsonViewer();
      showToast(`🌙 ${parsedColors.length} colores de Coloristia aplicados al Modo Oscuro (dark.auto desactivado)`);
    }

    // ==============================================================
    // SOBRESCRITURA EN VIVO DE VARIABLES DE _configTheme.css EN LA VISTA REAL DEL PROYECTO
    // ==============================================================

    function buildLiveThemeCssString() {
      const lightColors = themeConfig.light.colors || [];
      const darkColors = (themeConfig.dark.colors && themeConfig.dark.colors.length)
        ? themeConfig.dark.colors
        : lightColors.map(c => invertToDarkHex(c));

      const hoverSuccess = calculateHoverHex(themeConfig.status.success);
      const hoverCaution = calculateHoverHex(themeConfig.status.caution);
      const hoverDanger = calculateHoverHex(themeConfig.status.danger);

      let css = `/* Inyectado en vivo por Frame Theme Studio */\n`;
      css += `:root, html:not([data-theme="dark"]) {\n`;
      css += `  --theme-active: "${currentThemeSlug}" !important;\n`;
      css += `  --font: ${themeConfig.layout.font} !important;\n`;
      css += `  --font-size: ${themeConfig.layout.fontSize} !important;\n`;
      css += `  --width-window: ${themeConfig.layout.widthWindow} !important;\n`;
      css += `  --scrollbar-width: ${themeConfig.layout.scrollbarWidth || 'thin'} !important;\n`;
      css += `  --body: ${themeConfig.light.body} !important;\n`;
      css += `  --text-primary: ${themeConfig.light.text.primary} !important;\n`;
      css += `  --text-secondary: ${themeConfig.light.text.secondary} !important;\n`;
      css += `  --text-inactive: ${themeConfig.light.text.inactive} !important;\n`;
      css += `  --place-primary: ${themeConfig.light.text.primary} !important;\n`;
      css += `  --place-secondary: ${themeConfig.light.text.secondary} !important;\n`;
      css += `  --success: ${themeConfig.status.success} !important;\n`;
      css += `  --hover-success: ${hoverSuccess} !important;\n`;
      css += `  --caution: ${themeConfig.status.caution} !important;\n`;
      css += `  --hover-caution: ${hoverCaution} !important;\n`;
      css += `  --danger: ${themeConfig.status.danger} !important;\n`;
      css += `  --hover-danger: ${hoverDanger} !important;\n`;

      for (let i = 0; i < lightColors.length; i++) {
        const hex = lightColors[i] || '#000000';
        const varName = (i === 0) ? '--back-color' : `--back-color${i + 1}`;
        const hoverVarName = (i === 0) ? '--back-color-hover' : `--back-color${i + 1}-hover`;
        css += `  ${varName}: ${hex} !important;\n`;
        css += `  ${hoverVarName}: oklch(from var(${varName}) calc(l * 1.2) calc(c - 0.02) h) !important;\n`;
        if (i === 0) {
          css += `  --back-color1: ${hex} !important;\n`;
          css += `  --back-color1-hover: oklch(from var(--back-color) calc(l * 1.2) calc(c - 0.02) h) !important;\n`;
        }
      }
      css += `}\n\n`;

      css += `html[data-theme="dark"], [data-theme="dark"] {\n`;
      css += `  --font: ${themeConfig.layout.font} !important;\n`;
      css += `  --font-size: ${themeConfig.layout.fontSize} !important;\n`;
      css += `  --width-window: ${themeConfig.layout.widthWindow} !important;\n`;
      css += `  --scrollbar-width: ${themeConfig.layout.scrollbarWidth || 'thin'} !important;\n`;
      css += `  --body: ${themeConfig.dark.body} !important;\n`;
      css += `  --text-primary: ${themeConfig.dark.text.primary} !important;\n`;
      css += `  --text-secondary: ${themeConfig.dark.text.secondary} !important;\n`;
      css += `  --text-inactive: ${themeConfig.dark.text.inactive} !important;\n`;
      css += `  --place-primary: ${themeConfig.dark.text.primary} !important;\n`;
      css += `  --place-secondary: ${themeConfig.dark.text.secondary} !important;\n`;
      css += `  --success: ${themeConfig.status.success} !important;\n`;
      css += `  --hover-success: ${hoverSuccess} !important;\n`;
      css += `  --caution: ${themeConfig.status.caution} !important;\n`;
      css += `  --hover-caution: ${hoverCaution} !important;\n`;
      css += `  --danger: ${themeConfig.status.danger} !important;\n`;
      css += `  --hover-danger: ${hoverDanger} !important;\n`;

      for (let i = 0; i < darkColors.length; i++) {
        const hex = darkColors[i] || '#000000';
        const varName = (i === 0) ? '--back-color' : `--back-color${i + 1}`;
        const hoverVarName = (i === 0) ? '--back-color-hover' : `--back-color${i + 1}-hover`;
        css += `  ${varName}: ${hex} !important;\n`;
        css += `  ${hoverVarName}: oklch(from var(${varName}) calc(l * 1.2) calc(c - 0.02) h) !important;\n`;
        if (i === 0) {
          css += `  --back-color1: ${hex} !important;\n`;
          css += `  --back-color1-hover: oklch(from var(--back-color) calc(l * 1.2) calc(c - 0.02) h) !important;\n`;
        }
      }
      css += `}\n\n`;

      // Reglas utilitarias dinámicas por si el usuario agrega nuevos colores (ej. .back10, .color10) aún no compilados en jit-compiled.css
      const maxSlots = Math.max(lightColors.length, darkColors.length);
      for (let i = 1; i <= maxSlots; i++) {
        const varName = (i === 1) ? '--back-color' : `--back-color${i}`;
        const hoverVarName = (i === 1) ? '--back-color-hover' : `--back-color${i}-hover`;
        css += `.back${i} { background-color: var(${varName}) !important; }\n`;
        css += `.back${i}-hover { background-color: var(${varName}) !important; transition: var(--transition, all 0.15s ease-in-out); }\n`;
        css += `.back${i}-hover:hover, .back${i}-hover:focus { background-color: var(${hoverVarName}) !important; }\n`;
        css += `.color${i}, .color${i} a { color: var(${varName}) !important; }\n`;
        css += `.color${i}-hover, .color${i}-hover a { color: var(${varName}); transition: var(--transition, all 0.15s ease-in-out); }\n`;
        css += `.color${i}-hover:hover, .color${i}-hover:hover a { color: var(${hoverVarName}) !important; }\n`;
        css += `.border${i} { border: 1px solid var(${varName}); }\n`;
        css += `.border-b-${i} { border-bottom: 1px solid var(${varName}); }\n`;
      }

      // Utilidades condicionales estacionales para todos los temas registrados
      css += `\n/* Utilidades condicionales por tema en vivo */\n`;
      const allRegisteredSlugs = Object.keys(fullThemeConfig.themes || { default: true });
      for (const s of allRegisteredSlugs) {
        if (s === currentThemeSlug) {
          css += `.only-theme-${s} { display: revert !important; }\n`;
          css += `[data-theme-name="${s}"] .only-theme-${s}, body.theme-${s} .only-theme-${s} { display: revert !important; }\n`;
          css += `.no-theme-${s} { display: none !important; }\n`;
          css += `[data-theme-name="${s}"] .no-theme-${s}, body.theme-${s} .no-theme-${s} { display: none !important; }\n`;
        } else {
          css += `.only-theme-${s} { display: none !important; }\n`;
          css += `[data-theme-name="${s}"] .only-theme-${s}, body.theme-${s} .only-theme-${s} { display: revert !important; }\n`;
          css += `.no-theme-${s} { display: revert !important; }\n`;
          css += `[data-theme-name="${s}"] .no-theme-${s}, body.theme-${s} .no-theme-${s} { display: none !important; }\n`;
        }
      }

      return css;
    }

    function injectLiveThemeIntoIframe() {
      const frame = document.getElementById('projectPreviewFrame');
      if (!frame) return;

      try {
        const doc = frame.contentDocument || (frame.contentWindow && frame.contentWindow.document);
        if (!doc || !doc.documentElement) return;

        // Inyectar atributo data-theme-name en html y body, y clase theme-{slug} en body
        doc.documentElement.setAttribute('data-theme-name', currentThemeSlug);
        if (doc.body) {
          doc.body.setAttribute('data-theme-name', currentThemeSlug);
          const cleanBodyClass = doc.body.className.replace(/\btheme-[a-z0-9_-]+\b/g, '').trim();
          doc.body.className = (cleanBodyClass ? cleanBodyClass + ' ' : '') + 'theme-' + currentThemeSlug;
        }

        let styleEl = doc.getElementById('frame-theme-studio-live-vars');
        if (!styleEl) {
          styleEl = doc.createElement('style');
          styleEl.id = 'frame-theme-studio-live-vars';
          (doc.head || doc.documentElement).appendChild(styleEl);
        } else if (doc.head && styleEl.parentNode !== doc.head) {
          doc.head.appendChild(styleEl);
        }

        styleEl.textContent = buildLiveThemeCssString();
      } catch (e) {
        // El iframe aún está cargando o pertenece a otro origen
      }
    }

    function applySandboxStyles() {
      const sb = document.getElementById('sandbox');
      const isDark = (currentPreviewMode === 'dark');
      const activeColors = isDark 
        ? (themeConfig.dark.colors.length ? themeConfig.dark.colors : themeConfig.light.colors.map(c => invertToDarkHex(c)))
        : themeConfig.light.colors;

      const activeBody = isDark ? themeConfig.dark.body : themeConfig.light.body;
      const activeTextPrimary = isDark ? themeConfig.dark.text.primary : themeConfig.light.text.primary;
      const activeTextSecondary = isDark ? themeConfig.dark.text.secondary : themeConfig.light.text.secondary;

      if (sb) {
        sb.style.setProperty('--body', activeBody);
      }

      // Sobrescribir en tiempo real las variables de _configTheme.css dentro de la vista real del proyecto
      injectLiveThemeIntoIframe();

      // Actualizar paneles de diagnóstico en el Sidebar (WCAG y Gradientes)
      renderWcagTab(activeColors, activeTextPrimary, activeTextSecondary, activeBody);
      renderGradientsShowcase(activeColors);
    }

    // ==============================================================
    // PESTAÑAS DE DIAGNÓSTICO EN SIDEBAR: WCAG 2.1 Y GRADIENTES
    // ==============================================================

    function renderWcagTab(colors, textPrimary, textSecondary, bodyHex) {
      const container = document.getElementById('wcagMatrixContainer');
      const badge = document.getElementById('wcagOverallBadge');
      if (!container) return;
      container.innerHTML = '';

      let passCount = 0;
      const totalEvaluated = colors.length + 1;

      const bodyRatio = getContrastRatio(bodyHex, textPrimary);
      const bodyRating = getWcagRating(bodyRatio);
      if (bodyRating.pass) passCount++;

      let tableHtml = `
        <table class="ts-wcag-table">
          <thead>
            <tr>
              <th>Variable</th>
              <th>Fondo</th>
              <th>Texto Primario</th>
              <th>Texto Secundario</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><strong>--body</strong></td>
              <td><span style="display:inline-block; width:14px; height:14px; border-radius:3px; background:${bodyHex}; vertical-align:middle; border:1px solid #666;"></span> ${bodyHex}</td>
              <td>
                <span class="ts-wcag-badge ${bodyRating.badge}">${bodyRating.level} ${bodyRatio.toFixed(1)}:1</span>
              </td>
              <td>
                ${(() => {
                  const r = getContrastRatio(bodyHex, textSecondary);
                  const rt = getWcagRating(r);
                  return `<span class="ts-wcag-badge ${rt.badge}">${rt.level} ${r.toFixed(1)}:1</span>`;
                })()}
              </td>
            </tr>
      `;

      for (let i = 0; i < colors.length; i++) {
        const hex = colors[i];
        const varName = (i === 0) ? '--back-color' : '--back-color' + (i + 1);
        const r1 = getContrastRatio(hex, textPrimary);
        const rt1 = getWcagRating(r1);
        const r2 = getContrastRatio(hex, textSecondary);
        const rt2 = getWcagRating(r2);

        if (rt1.pass || rt2.pass) passCount++;

        tableHtml += `
          <tr>
            <td><strong>${varName}</strong></td>
            <td><span style="display:inline-block; width:14px; height:14px; border-radius:3px; background:${hex}; vertical-align:middle; border:1px solid #666;"></span> ${hex}</td>
            <td>
              <span class="ts-wcag-badge ${rt1.badge}">${rt1.level} ${r1.toFixed(1)}:1</span>
            </td>
            <td>
              <span class="ts-wcag-badge ${rt2.badge}">${rt2.level} ${r2.toFixed(1)}:1</span>
            </td>
          </tr>
        `;
      }

      tableHtml += `</tbody></table>`;
      container.innerHTML = tableHtml;

      if (badge) {
        const allPass = passCount === totalEvaluated;
        badge.className = `ts-wcag-badge ${allPass ? 'ts-wcag-aaa' : 'ts-wcag-aa'}`;
        badge.textContent = `${passCount} de ${totalEvaluated} accesibles`;
      }
    }

    function renderGradientsShowcase(colors) {
      const container = document.getElementById('sidebarGradientsContainer');
      if (!container) return;
      container.innerHTML = '';

      if (colors.length < 2) {
        container.innerHTML = '<p style="font-size:0.8rem; color:var(--ts-muted);">Se requieren al menos 2 colores para generar gradientes.</p>';
        return;
      }

      const fullVarList = colors.map((c, i) => (i === 0) ? 'var(--back-color)' : `var(--back-color${i + 1})`).join(', ');
      const fullGradientCss = `linear-gradient(135deg, ${fullVarList})`;
      const fullHexGradient = `linear-gradient(135deg, ${colors.join(', ')})`;

      const fullStrip = document.createElement('div');
      fullStrip.className = 'sb-gradient-strip';
      fullStrip.style.background = fullHexGradient;
      fullStrip.innerHTML = `
        <span>Paleta Completa (${colors.length} Pasos)</span>
        <button type="button" class="ts-btn ts-btn-secondary" style="padding:4px 10px; font-size:0.75rem;" onclick="copyGradientCss('${fullGradientCss}')">
          📋 Copiar CSS
        </button>
      `;
      container.appendChild(fullStrip);

      for (let i = 0; i < colors.length - 1; i++) {
        const var1 = (i === 0) ? 'var(--back-color)' : `var(--back-color${i + 1})`;
        const var2 = `var(--back-color${i + 2})`;
        const gradCss = `linear-gradient(135deg, ${var1}, ${var2})`;
        const hexGrad = `linear-gradient(135deg, ${colors[i]}, ${colors[i + 1]})`;

        const pairStrip = document.createElement('div');
        pairStrip.className = 'sb-gradient-strip';
        pairStrip.style.background = hexGrad;
        pairStrip.innerHTML = `
          <span>Paso ${i + 1} ➔ ${i + 2}</span>
          <button type="button" class="ts-btn ts-btn-secondary" style="padding:3px 8px; font-size:0.72rem;" onclick="copyGradientCss('${gradCss}')">
            📋 Copiar
          </button>
        `;
        container.appendChild(pairStrip);
      }
    }

    function copyGradientCss(css) {
      const textToCopy = `background: ${css};`;
      navigator.clipboard.writeText(textToCopy).then(() => {
        showToast('📋 Código de gradiente copiado al portapapeles');
      });
    }

    // ==============================================================
    // CONTROLES DE VISTA PREVIA Y ACCIONES DEL HEADER
    // ==============================================================

    function setPreviewMode(mode) {
      currentPreviewMode = mode;
      document.getElementById('btnModeLight').classList.toggle('active', mode === 'light');
      document.getElementById('btnModeDark').classList.toggle('active', mode === 'dark');

      const frame = document.getElementById('projectPreviewFrame');
      if (frame) {
        try {
          const doc = frame.contentDocument || (frame.contentWindow && frame.contentWindow.document);
          syncIframeThemeAttribute(doc, frame.contentWindow);
        } catch (e) {}
      }

      applySandboxStyles();
    }

    function setViewport(width, btnEl) {
      const windowEl = document.getElementById('sandboxWindow');
      windowEl.style.maxWidth = width;
      document.querySelectorAll('.ts-view-btn').forEach(btn => btn.classList.remove('active'));
      if (btnEl) {
        btnEl.classList.add('active');
      } else if (typeof event !== 'undefined' && event.target) {
        event.target.classList.add('active');
      }
    }

    function switchTab(tabId, btnEl) {
      document.querySelectorAll('.ts-tab-btn').forEach(btn => btn.classList.remove('active'));
      document.querySelectorAll('.ts-tab-content').forEach(c => c.classList.remove('active'));
      
      if (btnEl) {
        btnEl.classList.add('active');
      } else if (typeof event !== 'undefined' && event.target) {
        event.target.classList.add('active');
      }
      const target = document.getElementById('tab-' + tabId);
      if (target) target.classList.add('active');

      if (tabId === 'json') {
        updateJsonViewer();
      }
    }

    function updateJsonViewer() {
      const viewer = document.getElementById('jsonViewer');
      if (viewer) {
        viewer.value = JSON.stringify(themeConfig, null, 2);
      }
    }

    function copyJsonToClipboard() {
      const viewer = document.getElementById('jsonViewer');
      viewer.select();
      navigator.clipboard.writeText(viewer.value).then(() => {
        showToast('📋 JSON copiado al portapapeles');
      });
    }

    function resetDefaults() {
      if (confirm('¿Restaurar los colores y configuración a los definidos en _configTheme.css del proyecto?')) {
        themeConfig = JSON.parse(JSON.stringify(defaultCssConfig));
        themeConfig.slug = currentThemeSlug;
        themeConfig.name = fullThemeConfig.themes[currentThemeSlug]?.name || 'Predeterminado';
        fullThemeConfig.themes[currentThemeSlug] = JSON.parse(JSON.stringify(themeConfig));
        initStudio();
        showToast('🔄 Colores restaurados a los de _configTheme.css');
      }
    }

    // ==============================================================
    // GESTIÓN DE TEMAS, EVENTOS ESPECIALES Y CALENDARIO
    // ==============================================================

    function renderThemesTab() {
      const container = document.getElementById('themesListContainer');
      const countBadge = document.getElementById('themesCountBadge');
      const themeSelect = document.getElementById('themeSelector');
      const schedThemeName = document.getElementById('schedThemeName');

      const themes = fullThemeConfig.themes || { default: themeConfig };
      const slugs = Object.keys(themes);

      if (countBadge) countBadge.textContent = `${slugs.length} temas guardados`;
      if (schedThemeName) schedThemeName.textContent = themeConfig.name || currentThemeSlug;

      // Actualizar select del header
      if (themeSelect) {
        themeSelect.innerHTML = '';
        slugs.forEach(s => {
          const t = themes[s];
          const isAct = (s === activeThemeSlug);
          const isSched = t.schedule && t.schedule.enabled;
          const badge = isAct ? ' ⭐' : (isSched ? ' 📅' : '');
          const opt = document.createElement('option');
          opt.value = s;
          opt.textContent = (t.name || s) + badge;
          if (s === currentThemeSlug) opt.selected = true;
          themeSelect.appendChild(opt);
        });
      }

      // Actualizar formulario de programación de calendario
      const curSched = themeConfig.schedule || {};
      const chkEnabled = document.getElementById('schedEnabled');
      const inStart = document.getElementById('schedStart');
      const inEnd = document.getElementById('schedEnd');
      const chkRecurring = document.getElementById('schedRecurring');

      if (chkEnabled) chkEnabled.checked = !!curSched.enabled;
      if (inStart) inStart.value = curSched.start || '';
      if (inEnd) inEnd.value = curSched.end || '';
      if (chkRecurring) chkRecurring.checked = curSched.recurring !== false;
      toggleScheduleForm(!!curSched.enabled);

      if (!container) return;
      container.innerHTML = '';

      slugs.forEach(s => {
        const t = themes[s];
        const isCurrent = (s === currentThemeSlug);
        const isActive = (s === activeThemeSlug);
        const hasSched = t.schedule && t.schedule.enabled;
        const colors = (t.light && t.light.colors) ? t.light.colors : [];

        const card = document.createElement('div');
        card.className = `ts-theme-card ${isCurrent ? 'active-theme' : ''}`;

        let statusBadge = '';
        if (isActive) {
          statusBadge = '<span class="ts-wcag-badge ts-wcag-aaa" style="background:#059669; color:#fff;">⭐ Activo</span>';
        } else if (hasSched) {
          statusBadge = `<span class="ts-wcag-badge ts-wcag-aa" style="background:#0284c7; color:#fff;" title="${t.schedule.start} a ${t.schedule.end}">📅 ${t.schedule.start}➔${t.schedule.end}</span>`;
        }

        let swatchesHtml = '';
        colors.slice(0, 9).forEach(c => {
          swatchesHtml += `<span class="ts-theme-mini-chip" style="background:${c};" title="${c}"></span>`;
        });

        card.innerHTML = `
          <div class="ts-theme-card-header">
            <div class="ts-theme-card-title">
              <span>${t.name || s}</span>
              <span class="ts-theme-slug-badge">.${s}</span>
            </div>
            <div>${statusBadge}</div>
          </div>
          <div class="ts-theme-swatches">${swatchesHtml}</div>
          <div class="ts-theme-actions">
            <button type="button" class="ts-btn ${isCurrent ? 'ts-btn-primary' : 'ts-btn-secondary'}" style="padding:4px 10px; font-size:0.75rem;" onclick="switchThemeTo('${s}')">
              ${isCurrent ? '✏️ Editando' : '👁️ Ver y Editar'}
            </button>
            ${!isActive ? `
              <button type="button" class="ts-btn ts-btn-secondary" style="padding:4px 8px; font-size:0.75rem;" onclick="activateTheme('${s}')" title="Establecer como tema principal">
                ⭐ Activar
              </button>
            ` : ''}
            ${s !== 'default' ? `
              <button type="button" class="ts-btn ts-btn-secondary" style="padding:4px 8px; font-size:0.75rem; color:#ef4444;" onclick="deleteTheme('${s}')" title="Eliminar tema">
                🗑️
              </button>
            ` : ''}
          </div>
        `;
        container.appendChild(card);
      });
    }

    function switchThemeTo(slug) {
      if (!slug || !fullThemeConfig.themes[slug]) return;

      // Guardar cambios en memoria del tema previo antes de cambiar
      fullThemeConfig.themes[currentThemeSlug] = JSON.parse(JSON.stringify(themeConfig));

      currentThemeSlug = slug;
      themeConfig = JSON.parse(JSON.stringify(fullThemeConfig.themes[slug]));
      if (!themeConfig.slug) themeConfig.slug = slug;

      renderColorSlots('light');
      renderColorSlots('dark');
      syncFormValues();
      renderThemesTab();
      applySandboxStyles();
      updateJsonViewer();

      showToast(`🎨 Tema cargado: ${themeConfig.name || slug}`);
    }

    function toggleScheduleForm(show) {
      const el = document.getElementById('schedFormFields');
      if (el) el.style.display = show ? 'block' : 'none';
    }

    function saveCurrentThemeSchedule() {
      const enabled = document.getElementById('schedEnabled').checked;
      const start = document.getElementById('schedStart').value.trim();
      const end = document.getElementById('schedEnd').value.trim();
      const recurring = document.getElementById('schedRecurring').checked;

      if (enabled && (!start || !end)) {
        showToast('❌ Debes ingresar fecha de inicio y fin para la programación.', 'error');
        return;
      }

      themeConfig.schedule = { enabled, start, end, recurring };
      if (!fullThemeConfig.themes[currentThemeSlug]) {
        fullThemeConfig.themes[currentThemeSlug] = themeConfig;
      }
      fullThemeConfig.themes[currentThemeSlug].schedule = themeConfig.schedule;

      saveTheme();
    }

    function openSaveThemeModal() {
      document.getElementById('newThemeName').value = '';
      document.getElementById('newThemeSlug').value = '';
      document.getElementById('newThemeSetActive').checked = true;
      document.getElementById('modalSaveTheme').classList.add('open');
      setTimeout(() => document.getElementById('newThemeName').focus(), 50);
    }

    function closeSaveThemeModal() {
      document.getElementById('modalSaveTheme').classList.remove('open');
    }

    function autoGenerateThemeSlug(name) {
      const slugInput = document.getElementById('newThemeSlug');
      if (!slugInput) return;
      const clean = name.toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9_-]+/g, '-')
        .replace(/^-+|-+$/g, '');
      slugInput.value = clean;
    }

    async function submitSaveNewTheme() {
      const name = document.getElementById('newThemeName').value.trim();
      const slug = document.getElementById('newThemeSlug').value.trim();
      const setActive = document.getElementById('newThemeSetActive').checked;

      if (!name || !slug) {
        showToast('❌ Por favor ingresa el nombre y el slug del tema.', 'error');
        return;
      }

      // Crear copia del tema actual con el nuevo nombre y slug
      const newTheme = JSON.parse(JSON.stringify(themeConfig));
      newTheme.name = name;
      newTheme.slug = slug;
      newTheme.schedule = null;

      fullThemeConfig.themes[slug] = newTheme;
      if (setActive) {
        fullThemeConfig.activeTheme = slug;
        activeThemeSlug = slug;
      }
      currentThemeSlug = slug;
      themeConfig = newTheme;

      closeSaveThemeModal();
      await saveTheme();
      renderThemesTab();
    }

    async function activateTheme(slug) {
      try {
        const response = await fetch('/_frame/theme-studio/set-active', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ slug })
        });
        const res = await response.json();
        if (res.success) {
          activeThemeSlug = slug;
          if (res.fullConfig) fullThemeConfig = res.fullConfig;
          renderThemesTab();
          applySandboxStyles();
          showToast(`⭐ Tema activo establecido en: ${slug}`);
        } else {
          showToast('❌ ' + (res.message || 'Error al activar tema'), 'error');
        }
      } catch (e) {
        showToast('❌ Error de conexión al activar tema', 'error');
      }
    }

    async function deleteTheme(slug) {
      if (!confirm(`¿Eliminar permanentemente el tema "${slug}"?`)) return;

      try {
        const response = await fetch('/_frame/theme-studio/delete-theme', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ slug })
        });
        const res = await response.json();
        if (res.success) {
          if (res.fullConfig) {
            fullThemeConfig = res.fullConfig;
          } else {
            delete fullThemeConfig.themes[slug];
          }
          if (currentThemeSlug === slug) {
            switchThemeTo('default');
          } else {
            renderThemesTab();
          }
          showToast(`🗑️ Tema "${slug}" eliminado`);
        } else {
          showToast('❌ ' + (res.message || 'Error al eliminar'), 'error');
        }
      } catch (e) {
        showToast('❌ Error de conexión al eliminar tema', 'error');
      }
    }

    // ==============================================================
    // PERSISTENCIA Y GUARDADO VIA POST
    // ==============================================================

    async function saveTheme() {
      const btn = document.getElementById('btnSave');
      const origText = btn.innerHTML;
      btn.innerHTML = '⏳ Guardando...';
      btn.disabled = true;

      // Asegurar que el tema actual esté reflejado en la colección completa
      themeConfig.slug = currentThemeSlug;
      if (!themeConfig.name && fullThemeConfig.themes[currentThemeSlug]) {
        themeConfig.name = fullThemeConfig.themes[currentThemeSlug].name;
      }
      fullThemeConfig.themes[currentThemeSlug] = JSON.parse(JSON.stringify(themeConfig));

      try {
        const response = await fetch('/_frame/theme-studio/save', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify(fullThemeConfig)
        });

        const data = await response.json();

        if (data.success) {
          if (data.fullConfig) {
            fullThemeConfig = data.fullConfig;
          }
          if (data.activeThemeSlug) {
            activeThemeSlug = data.activeThemeSlug;
          }
          renderThemesTab();
          applySandboxStyles();
          showToast('✅ ' + (data.message || '¡Tema guardado y compilado con éxito en _configTheme.css!'), 'success');
        } else {
          showToast('❌ ' + (data.message || 'Error al guardar'), 'error');
        }
      } catch (err) {
        showToast('❌ Error de conexión al guardar el tema', 'error');
      } finally {
        btn.innerHTML = origText;
        btn.disabled = false;
      }
    }

    function showToast(message, type = 'success') {
      const toast = document.getElementById('toast');
      const msg = document.getElementById('toastMsg');
      const icon = document.getElementById('toastIcon');

      msg.textContent = message;
      icon.textContent = (type === 'error') ? '❌' : (message.includes('⚡') ? '⚡' : '✅');
      toast.className = `ts-toast show ${type}`;

      clearTimeout(window.__toastTimer);
      window.__toastTimer = setTimeout(() => {
        toast.className = 'ts-toast';
      }, 4000);
    }

    // Iniciar estudio al cargar el DOM
    document.addEventListener('DOMContentLoaded', initStudio);
  </script>
</body>
</html>
