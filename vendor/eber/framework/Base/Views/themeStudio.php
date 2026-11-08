<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Frame Theme Studio</title>
  <?php if (class_exists('\\Base\\Module\\SecurityModule')): ?>
    <?= \Base\Module\SecurityModule::csrfMeta() ?>
  <?php endif; ?>
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
      padding: 0 18px;
      height: 54px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      z-index: 100;
      flex-shrink: 0;
    }
    .ts-brand {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 1.05rem;
      font-weight: 700;
      color: #fff;
      white-space: nowrap;
      flex-shrink: 0;
    }
    .ts-badge {
      font-size: 0.7rem;
      background: rgba(56, 189, 248, 0.15);
      color: var(--ts-primary);
      padding: 2px 7px;
      border-radius: 999px;
      border: 1px solid rgba(56, 189, 248, 0.3);
      font-weight: 600;
    }
    .ts-header-center {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-shrink: 1;
      min-width: 0;
    }
    .ts-theme-bar {
      display: flex;
      align-items: center;
      gap: 6px;
      background: var(--ts-bg);
      border: 1px solid var(--ts-border);
      border-radius: 8px;
      padding: 4px 8px;
    }
    .ts-theme-label {
      font-size: 0.75rem;
      color: var(--ts-muted);
      font-weight: 600;
      white-space: nowrap;
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
    .ts-mode-segmented {
      display: flex;
      align-items: center;
      background: var(--ts-bg);
      border: 1px solid var(--ts-border);
      border-radius: 8px;
      padding: 3px;
      gap: 3px;
    }
    .ts-mode-btn {
      background: transparent;
      border: none;
      color: var(--ts-muted);
      cursor: pointer;
      padding: 5px 11px;
      border-radius: 6px;
      font-size: 0.82rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 5px;
      transition: all 0.15s ease;
      white-space: nowrap;
    }
    .ts-mode-btn.active {
      background: var(--ts-surface-hover);
      color: #fff;
      box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    }
    .ts-actions {
      display: flex;
      align-items: center;
      gap: 8px;
      white-space: nowrap;
      flex-shrink: 0;
    }
    .ts-btn {
      border: none;
      cursor: pointer;
      font-family: inherit;
      font-weight: 600;
      font-size: 0.86rem;
      padding: 7px 15px;
      border-radius: 8px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.15s ease;
      white-space: nowrap;
      flex-shrink: 0;
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

    /* Dedicated Canvas Navigation & Viewport Bar */
    .ts-canvas-bar {
      width: 100%;
      background: var(--ts-surface);
      border: 1px solid var(--ts-border);
      border-bottom: none;
      border-radius: 10px 10px 0 0;
      padding: 6px 12px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-shrink: 0;
    }
    .ts-canvas-nav {
      display: flex;
      align-items: center;
      gap: 8px;
      flex: 1;
      min-width: 0;
    }
    .ts-sidebar-toggle-btn {
      background: var(--ts-bg);
      border: 1px solid var(--ts-border);
      color: var(--ts-muted);
      cursor: pointer;
      padding: 5px 10px;
      border-radius: 6px;
      font-size: 0.78rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      white-space: nowrap;
      transition: all 0.15s ease;
      flex-shrink: 0;
    }
    .ts-sidebar-toggle-btn:hover {
      background: var(--ts-surface-hover);
      color: #fff;
      border-color: var(--ts-primary);
    }
    .ts-route-bar {
      display: flex;
      align-items: center;
      gap: 6px;
      flex: 1;
      min-width: 0;
    }
    .ts-canvas-label {
      font-size: 0.76rem;
      color: var(--ts-muted);
      font-weight: 600;
      white-space: nowrap;
    }
    .ts-route-select {
      background: var(--ts-bg);
      border: 1px solid var(--ts-border);
      color: #fff;
      padding: 5px 8px;
      border-radius: 6px;
      font-size: 0.8rem;
      font-weight: 600;
      outline: none;
      cursor: pointer;
      max-width: 220px;
      flex-shrink: 1;
    }
    .ts-route-select:focus {
      border-color: var(--ts-primary);
    }
    .ts-route-input {
      background: var(--ts-bg);
      border: 1px solid var(--ts-border);
      color: var(--ts-primary);
      padding: 5px 8px;
      border-radius: 6px;
      font-size: 0.8rem;
      font-family: monospace;
      width: 120px;
      outline: none;
      flex-shrink: 1;
    }
    .ts-route-input:focus {
      border-color: var(--ts-primary);
    }
    .ts-icon-btn {
      background: var(--ts-bg);
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
      flex-shrink: 0;
    }
    .ts-icon-btn:hover {
      background: var(--ts-surface-hover);
      color: #fff;
      border-color: var(--ts-primary);
    }
    .ts-viewport-bar {
      display: flex;
      align-items: center;
      gap: 3px;
      background: var(--ts-bg);
      border: 1px solid var(--ts-border);
      border-radius: 8px;
      padding: 3px 4px;
      flex-shrink: 0;
    }
    .ts-view-btn {
      background: transparent;
      border: none;
      color: var(--ts-muted);
      cursor: pointer;
      padding: 5px 9px;
      border-radius: 6px;
      font-size: 0.8rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      transition: all 0.15s ease;
      white-space: nowrap;
    }
    .ts-view-btn.active {
      background: var(--ts-surface-hover);
      color: var(--ts-primary);
      font-weight: 700;
    }

    /* Responsive Breakpoints */
    @media (max-width: 1200px) {
      .ts-view-btn span { display: none; }
      .ts-view-btn { padding: 5px 7px; }
      .ts-route-select { max-width: 150px; }
      .ts-route-input { width: 90px; }
      .ts-theme-select { max-width: 130px; }
    }
    @media (max-width: 1024px) {
      .ts-brand-text { display: none; }
      .ts-badge { display: none; }
      .ts-theme-label { display: none; }
      .ts-mode-btn span { display: none; }
      .ts-mode-btn { padding: 5px 8px; }
      .ts-sidebar { width: 380px; }
    }
    @media (max-width: 800px) {
      .ts-canvas-label { display: none; }
      .ts-route-input { display: none; }
      .ts-btn span { display: none; }
      .ts-btn { padding: 6px 10px; }
    }

    /* Main Container (Sidebar + Real Project View) */
    .ts-main {
      display: flex;
      flex: 1;
      height: calc(100vh - 54px);
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
      transition: width 0.25s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.2s ease;
    }
    .ts-sidebar.collapsed {
      width: 0 !important;
      min-width: 0 !important;
      border-right: none !important;
      visibility: hidden;
      opacity: 0;
      pointer-events: none;
    }
    .ts-tabs {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      background: var(--ts-bg);
      border-bottom: 1px solid var(--ts-border);
      padding: 6px;
      gap: 5px;
    }
    .ts-tab-btn {
      background: rgba(255, 255, 255, 0.02);
      border: 1px solid rgba(255, 255, 255, 0.07);
      border-radius: 6px;
      color: var(--ts-muted);
      padding: 8px 4px;
      font-size: 0.74rem;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 4px;
      transition: all 0.15s ease;
      white-space: nowrap;
    }
    .ts-tab-btn:hover {
      background: var(--ts-surface-hover);
      color: #fff;
      border-color: rgba(255, 255, 255, 0.16);
    }
    .ts-tab-btn.active {
      background: rgba(56, 189, 248, 0.14);
      color: var(--ts-primary);
      border-color: rgba(56, 189, 248, 0.45);
      font-weight: 700;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
    }
    .ts-tab-btn:nth-child(7) {
      grid-column: span 2;
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

    /* Date Input & Selector de Fechas Popover */
    .ts-date-input-group {
      display: flex;
      align-items: center;
      background: var(--ts-bg);
      border: 1px solid var(--ts-border);
      border-radius: 6px;
      overflow: hidden;
      transition: border-color 0.15s ease;
    }
    .ts-date-input-group:focus-within {
      border-color: var(--ts-primary);
    }
    .ts-date-input {
      flex: 1;
      min-width: 0;
      background: transparent;
      border: none;
      color: #fff;
      padding: 7px 10px;
      font-size: 0.82rem;
      font-family: monospace;
      outline: none;
    }
    .ts-date-picker-trigger {
      background: rgba(255, 255, 255, 0.05);
      border: none;
      border-left: 1px solid var(--ts-border);
      color: var(--ts-text);
      padding: 7px 11px;
      cursor: pointer;
      font-size: 0.85rem;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.15s ease, color 0.15s ease;
    }
    .ts-date-picker-trigger:hover {
      background: var(--ts-surface-hover);
      color: var(--ts-primary);
    }
    .ts-chip-btn {
      background: rgba(15, 23, 42, 0.8);
      border: 1px solid var(--ts-border);
      color: #94a3b8;
      font-size: 0.7rem;
      padding: 3px 8px;
      border-radius: 999px;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.15s ease;
      white-space: nowrap;
    }
    .ts-chip-btn:hover {
      background: rgba(56, 189, 248, 0.12);
      border-color: var(--ts-primary);
      color: #f8fafc;
      transform: translateY(-1px);
    }

    /* Popover Calendario Flotante */
    .ts-datepicker-popover {
      position: fixed;
      z-index: 100010;
      background: #1e293b;
      border: 1px solid #334155;
      border-radius: 10px;
      box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(255, 255, 255, 0.05);
      padding: 12px;
      width: 250px;
      font-family: var(--ts-font);
      user-select: none;
      animation: tsFadeInScale 0.15s ease-out;
    }
    @keyframes tsFadeInScale {
      from { opacity: 0; transform: scale(0.96) translateY(-4px); }
      to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .ts-dp-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 8px;
    }
    .ts-dp-title {
      font-size: 0.82rem;
      font-weight: 700;
      color: #f8fafc;
    }
    .ts-dp-nav-btn {
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #94a3b8;
      width: 24px;
      height: 24px;
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 0.9rem;
      line-height: 1;
      transition: all 0.15s ease;
    }
    .ts-dp-nav-btn:hover {
      background: var(--ts-primary);
      color: #0f172a;
      border-color: var(--ts-primary);
    }
    .ts-dp-weekdays {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      text-align: center;
      font-size: 0.68rem;
      font-weight: 600;
      color: #64748b;
      margin-bottom: 4px;
    }
    .ts-dp-days {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 2px;
    }
    .ts-dp-day {
      height: 28px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.75rem;
      border-radius: 4px;
      cursor: pointer;
      color: #cbd5e1;
      transition: all 0.12s ease;
    }
    .ts-dp-day:hover {
      background: rgba(56, 189, 248, 0.2);
      color: #fff;
    }
    .ts-dp-day.empty {
      cursor: default;
      background: transparent !important;
      color: transparent;
    }
    .ts-dp-day.today {
      border: 1px solid var(--ts-primary);
      font-weight: 700;
    }
    .ts-dp-day.selected {
      background: var(--ts-primary) !important;
      color: #0f172a !important;
      font-weight: 700;
    }
    .ts-dp-day.in-range {
      background: rgba(56, 189, 248, 0.15);
      color: #38bdf8;
    }
    .ts-dp-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 10px;
      padding-top: 8px;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
    }
    .ts-dp-footer-btn {
      background: none;
      border: none;
      color: var(--ts-primary);
      font-size: 0.72rem;
      cursor: pointer;
      padding: 2px 4px;
      border-radius: 3px;
    }
    .ts-dp-footer-btn:hover {
      text-decoration: underline;
    }
    .ts-dp-mode-badge {
      font-size: 0.66rem;
      color: var(--ts-muted);
      font-family: monospace;
    }
  </style>
</head>
<body>

  <!-- Top App Bar -->
  <header class="ts-header">
    <div class="ts-brand">
      <span class="ts-brand-icon">🎨</span>
      <span class="ts-brand-text">Frame Theme Studio</span>
      <span class="ts-badge">dev mode</span>
    </div>

    <!-- Center: Theme Selector & Color Mode Switcher -->
    <div class="ts-header-center">
      <!-- Selector de Tema / Paleta Guardada -->
      <div class="ts-theme-bar">
        <span class="ts-theme-label">🏷️ Tema:</span>
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

      <!-- Segmented Pill Claro / Oscuro -->
      <div class="ts-mode-segmented">
        <button type="button" class="ts-mode-btn active" id="btnModeLight" onclick="setPreviewMode('light')" title="Previsualizar Modo Claro">
          ☀️ <span>Claro</span>
        </button>
        <button type="button" class="ts-mode-btn" id="btnModeDark" onclick="setPreviewMode('dark')" title="Previsualizar Modo Oscuro">
          🌙 <span>Oscuro</span>
        </button>
      </div>
    </div>

    <!-- Right: Actions -->
    <div class="ts-actions">
      <button type="button" class="ts-btn ts-btn-secondary" onclick="resetDefaults()" title="Restaurar a los colores definidos en _configTheme.css">
        🔄 <span>Reset</span>
      </button>
      <button type="button" class="ts-btn ts-btn-primary" id="btnSave" onclick="saveTheme()" title="Guardar cambios del tema en theme.json (sin compilar al sitio)">
        💾 <span>Guardar</span>
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
        <button type="button" class="ts-tab-btn" onclick="switchTab('layout', this)">🔤 Layout</button>
        <button type="button" class="ts-tab-btn" onclick="switchTab('wcag', this)">♿ WCAG</button>
        <button type="button" class="ts-tab-btn" onclick="switchTab('gradients', this)">🌈 Gradientes</button>
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
              <!-- Presets Rápidos de Eventos -->
              <div style="margin-bottom:12px;">
                <span style="display:block; font-size:0.68rem; color:var(--ts-muted); margin-bottom:6px; font-weight:700; letter-spacing:0.04em;">PRESETS DE EVENTOS RÁPIDOS:</span>
                <div style="display:flex; flex-wrap:wrap; gap:6px;">
                  <button type="button" class="ts-chip-btn" onclick="applySchedulePreset('navidad')" title="20 Dic a 26 Dic (Navidad)">🎄 Navidad</button>
                  <button type="button" class="ts-chip-btn" onclick="applySchedulePreset('ano-nuevo')" title="28 Dic a 06 Ene (Año Nuevo)">🎆 Año Nuevo</button>
                  <button type="button" class="ts-chip-btn" onclick="applySchedulePreset('black-friday')" title="20 Nov a 30 Nov (Black Friday)">🏷️ Black Friday</button>
                  <button type="button" class="ts-chip-btn" onclick="applySchedulePreset('halloween')" title="25 Oct a 02 Nov (Halloween)">🎃 Halloween</button>
                  <button type="button" class="ts-chip-btn" onclick="applySchedulePreset('san-valentin')" title="10 Feb a 16 Feb (San Valentín)">💘 San Valentín</button>
                </div>
              </div>

              <!-- Input Fecha Inicio -->
              <div class="ts-field" style="margin-bottom:8px;">
                <label for="schedStart" style="font-size:0.75rem; display:flex; justify-content:space-between; align-items:center;">
                  <span>Fecha Inicio (YYYY-MM-DD o MM-DD)</span>
                  <span id="schedStartHint" style="font-size:0.68rem; color:var(--ts-primary);"></span>
                </label>
                <div class="ts-date-input-group">
                  <input type="text" id="schedStart" class="ts-date-input" placeholder="ej. 12-28 o 2026-11-20" oninput="onScheduleDateChange()">
                  <button type="button" class="ts-date-picker-trigger" onclick="openDatePicker('start', event)" title="Abrir selector de calendario para fecha inicio">
                    📅
                  </button>
                </div>
              </div>

              <!-- Input Fecha Fin -->
              <div class="ts-field" style="margin-bottom:8px;">
                <label for="schedEnd" style="font-size:0.75rem; display:flex; justify-content:space-between; align-items:center;">
                  <span>Fecha Fin (YYYY-MM-DD o MM-DD)</span>
                  <span id="schedEndHint" style="font-size:0.68rem; color:var(--ts-primary);"></span>
                </label>
                <div class="ts-date-input-group">
                  <input type="text" id="schedEnd" class="ts-date-input" placeholder="ej. 01-05 o 2026-11-30" oninput="onScheduleDateChange()">
                  <button type="button" class="ts-date-picker-trigger" onclick="openDatePicker('end', event)" title="Abrir selector de calendario para fecha fin">
                    📅
                  </button>
                </div>
              </div>

              <!-- Estado Dinámico del Rango -->
              <div id="schedStatusBanner" style="display:none; padding:8px 10px; border-radius:6px; font-size:0.74rem; margin-bottom:10px; line-height:1.4;"></div>

              <div style="margin-bottom:12px;">
                <label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-size:0.78rem; color:var(--ts-muted);">
                  <input type="checkbox" id="schedRecurring" checked onchange="onScheduleRecurringChange()">
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
      
      <!-- Barra Superior de Navegación del Canvas & Resoluciones Viewport -->
      <div class="ts-canvas-bar">
        <!-- Izquierda: Toggle de Sidebar y Barra de Direcciones del Proyecto -->
        <div class="ts-canvas-nav">
          <button type="button" class="ts-sidebar-toggle-btn" id="btnToggleSidebar" onclick="toggleSidebar()" title="Ocultar / Mostrar panel lateral">
            ◀ Panel
          </button>
          
          <div class="ts-route-bar">
            <span class="ts-canvas-label">🌐 Vista:</span>
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
        </div>

        <!-- Derecha: Selector de Resolución y Dispositivos (100%, 1200px, 768px, 380px) -->
        <div class="ts-viewport-bar">
          <button type="button" class="ts-view-btn active" onclick="setViewport('100%', this)" title="Escritorio Completo">
            🖥️ <span>100%</span>
          </button>
          <button type="button" class="ts-view-btn" onclick="setViewport('1200px', this)" title="Laptop / Contenedor 1200px">
            💻 <span>1200px</span>
          </button>
          <button type="button" class="ts-view-btn" onclick="setViewport('768px', this)" title="Tablet 768px">
            📱 <span>768px</span>
          </button>
          <button type="button" class="ts-view-btn" onclick="setViewport('380px', this)" title="Móvil 380px">
            📲 <span>380px</span>
          </button>
        </div>
      </div>

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

  <!-- Modal de Confirmación Genérico y Profesional -->
  <div id="modalConfirm" class="ts-modal-overlay">
    <div class="ts-modal-box" style="max-width: 420px; text-align: center;">
      <div style="font-size: 2.2rem; margin-bottom: 8px;" id="modalConfirmIcon">🔄</div>
      <h3 style="font-size: 1.05rem; font-weight: 700; color: #fff; margin-bottom: 10px;" id="modalConfirmTitle">
        ¿Restaurar valores por defecto?
      </h3>
      <p style="font-size: 0.84rem; color: var(--ts-muted); margin-bottom: 20px; line-height: 1.45;" id="modalConfirmMessage">
        ¿Deseas restaurar los colores y la configuración a los definidos en _configTheme.css del proyecto?
      </p>
      <div style="display: flex; justify-content: center; gap: 10px;">
        <button type="button" class="ts-btn ts-btn-secondary" onclick="closeConfirmModal()">
          Cancelar
        </button>
        <button type="button" class="ts-btn ts-btn-primary" id="btnConfirmAccept" onclick="onConfirmModalAccept()">
          Sí, Restaurar
        </button>
      </div>
    </div>
  </div>

  <!-- Popover Selector de Fechas (Date Picker) -->
  <div id="tsDatePickerPopover" class="ts-datepicker-popover" style="display: none;">
    <div class="ts-dp-header">
      <button type="button" class="ts-dp-nav-btn" onclick="dpPrevMonth()" title="Mes anterior">&lsaquo;</button>
      <div class="ts-dp-title" id="dpCurrentMonthYear">Noviembre 2026</div>
      <button type="button" class="ts-dp-nav-btn" onclick="dpNextMonth()" title="Mes siguiente">&rsaquo;</button>
    </div>
    <div class="ts-dp-weekdays">
      <span>Lu</span><span>Ma</span><span>Mi</span><span>Ju</span><span>Vi</span><span>Sá</span><span>Do</span>
    </div>
    <div class="ts-dp-days" id="dpDaysGrid"></div>
    <div class="ts-dp-footer">
      <button type="button" class="ts-dp-footer-btn" onclick="dpSelectToday()">Hoy</button>
      <span class="ts-dp-mode-badge" id="dpModeBadge">Modo: MM-DD</span>
      <button type="button" class="ts-dp-footer-btn" onclick="closeDatePicker()">✕ Cerrar</button>
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

    // ==============================================================
    // FLOATING SCROLLBAR ENGINE PARA LA PREVIEW (Resources/Js/Components/floatingScroll.js)
    // ==============================================================

    class PreviewFloatingScroll {
      constructor(width = 8, color = "color2", responsive = "", options = {}) {
        const parsedWidth = parseFloat(width);
        this.width = Number.isFinite(parsedWidth) && parsedWidth > 0 ? parsedWidth : 8;

        const rawOpts = options && typeof options === "object" ? options : {};
        this.win = rawOpts.targetWindow || (typeof window !== "undefined" ? window : null);
        this.doc = rawOpts.targetDocument || (this.win ? this.win.document : document);

        this.options = {
          hoverExpand: rawOpts.hoverExpand !== undefined ? Number(rawOpts.hoverExpand) : 4,
          minThumbHeight: rawOpts.minThumbHeight !== undefined ? Number(rawOpts.minThumbHeight) : 36,
          offsetRight: rawOpts.offsetRight !== undefined ? Number(rawOpts.offsetRight) : 3,
          offsetY: rawOpts.offsetY !== undefined ? Number(rawOpts.offsetY) : 4,
          opacity: rawOpts.opacity !== undefined ? Number(rawOpts.opacity) : 0.65,
          autoHide: Boolean(rawOpts.autoHide),
          zIndex: rawOpts.zIndex !== undefined ? rawOpts.zIndex : 2147483647
        };

        this.color = color || "var(--ts-primary, #38bdf8)";
        this.isDragging = false;
        this.isHovered = false;
        this.dragStartY = 0;
        this.dragStartScrollTop = 0;
        this.thumbHeight = 0;
        this.thumbTop = 0;
        this.maxScrollTop = 0;
        this.maxThumbTop = 0;
        this.rafId = null;
        this.hideTimer = null;
        this.isDestroyed = false;

        this._initDOM();
        this._bindEvents();
        this.refresh();
      }

      _initDOM() {
        if (!this.doc || !this.doc.documentElement) return;
        this.doc.documentElement.classList.add("has-floating-scroll");

        // Reglas mínimas esenciales para ocultar scrollbars nativos en el iframe
        if (!this.doc.getElementById("floating-scroll-base-styles")) {
          const style = this.doc.createElement("style");
          style.id = "floating-scroll-base-styles";
          style.textContent = `
            html.has-floating-scroll,
            html.has-floating-scroll body {
              scrollbar-width: none !important;
              -ms-overflow-style: none !important;
              scrollbar-gutter: auto !important;
            }
            html.has-floating-scroll::-webkit-scrollbar,
            html.has-floating-scroll body::-webkit-scrollbar {
              display: none !important;
              width: 0 !important;
              height: 0 !important;
              background: transparent !important;
            }
            .floating-scroll-track {
              position: fixed;
              right: 0;
              z-index: 2147483647;
              background: transparent;
              user-select: none;
              -webkit-user-select: none;
              touch-action: none;
            }
            .floating-scroll-thumb {
              background-color: currentColor;
              border-radius: 999px;
              touch-action: none;
            }
          `;
          (this.doc.head || this.doc.documentElement).appendChild(style);
        }

        // Eliminar track previo si ya existía
        const oldTrack = this.doc.getElementById("floating-scroll-track");
        if (oldTrack && oldTrack.parentNode) {
          oldTrack.parentNode.removeChild(oldTrack);
        }

        this.track = this.doc.createElement("div");
        this.track.id = "floating-scroll-track";
        this.track.setAttribute("role", "scrollbar");
        this.track.setAttribute("aria-controls", "body");
        this.track.setAttribute("aria-orientation", "vertical");

        this._applyClassesAndStyles();
        this._ensureThumbElement();

        if (this.doc.body) {
          this.doc.body.appendChild(this.track);
        } else {
          this.doc.documentElement.appendChild(this.track);
        }
      }

      _ensureThumbElement() {
        if (!this.track) return;
        let thumb = this.track.querySelector(".floating-scroll-thumb");
        if (!thumb) {
          thumb = this.doc.createElement("div");
          thumb.className = "floating-scroll-thumb";
          this.track.appendChild(thumb);
        }
        this.thumb = thumb;
        this._styleThumb();
      }

      _applyClassesAndStyles() {
        if (!this.track) return;
        this.track.className = "floating-scroll-track";

        const hitAreaWidth = Math.max(14, this.width + this.options.hoverExpand + this.options.offsetRight * 2);
        const s = this.track.style;
        s.position = "fixed";
        s.top = `${this.options.offsetY}px`;
        s.right = "0px";
        s.bottom = `${this.options.offsetY}px`;
        s.width = `${hitAreaWidth}px`;
        s.zIndex = String(this.options.zIndex);
        s.pointerEvents = "auto";
        s.userSelect = "none";
        s.webkitUserSelect = "none";
        s.background = "transparent";
        s.color = this.color;
      }

      _styleThumb() {
        if (!this.thumb) return;
        const activeWidth = (this.isHovered || this.isDragging)
          ? this.width + this.options.hoverExpand
          : this.width;

        const ts = this.thumb.style;
        ts.position = "absolute";
        ts.top = "0px";
        ts.right = `${this.options.offsetRight}px`;
        ts.width = `${activeWidth}px`;
        ts.height = `${Math.max(this.options.minThumbHeight, this.thumbHeight)}px`;
        ts.borderRadius = "999px";
        ts.backgroundColor = "currentColor";
        ts.opacity = (this.isHovered || this.isDragging) ? "1" : String(this.options.opacity);
        ts.transform = `translate3d(0, ${this.thumbTop}px, 0)`;
        ts.transition = this.isDragging
          ? "width 0.15s ease, opacity 0.15s ease"
          : "width 0.15s ease, opacity 0.2s ease";
        ts.cursor = this.isDragging ? "grabbing" : "grab";
        ts.willChange = "transform, width, opacity";
      }

      _bindEvents() {
        if (!this.win || !this.doc) return;

        this._onScroll = () => {
          if (this.isDestroyed) return;
          if (this.options.autoHide && this.thumb && !this.isDragging && !this.isHovered) {
            this.thumb.style.opacity = String(this.options.opacity);
            clearTimeout(this.hideTimer);
            this.hideTimer = setTimeout(() => {
              if (!this.isDragging && !this.isHovered && this.thumb) {
                this.thumb.style.opacity = "0";
              }
            }, 1200);
          }
          this._scheduleUpdate();
        };
        this.win.addEventListener("scroll", this._onScroll, { passive: true });

        this._onResize = () => {
          if (this.isDestroyed) return;
          this._scheduleUpdate();
        };
        this.win.addEventListener("resize", this._onResize, { passive: true });

        if (typeof ResizeObserver !== "undefined") {
          this._resizeObserver = new ResizeObserver(this._onResize);
          this._resizeObserver.observe(this.doc.documentElement);
          if (this.doc.body) {
            this._resizeObserver.observe(this.doc.body);
          }
        }

        this.track.addEventListener("mouseenter", () => {
          this.isHovered = true;
          clearTimeout(this.hideTimer);
          this._styleThumb();
        });

        this.track.addEventListener("mouseleave", () => {
          this.isHovered = false;
          if (!this.isDragging) {
            this._styleThumb();
          }
        });

        this._onPointerDown = (e) => {
          if (e.button !== undefined && e.button !== 0) return;
          this._ensureThumbElement();

          const clickedThumb = e.target.closest(".floating-scroll-thumb");

          if (!clickedThumb) {
            const trackRect = this.track.getBoundingClientRect();
            const clickY = e.clientY - trackRect.top;
            const targetThumbTop = Math.max(
              0,
              Math.min(this.maxThumbTop, clickY - this.thumbHeight * 0.5)
            );
            const ratio = this.maxThumbTop > 0 ? targetThumbTop / this.maxThumbTop : 0;
            this.win.scrollTo({
              top: ratio * this.maxScrollTop,
              behavior: "smooth"
            });
            return;
          }

          e.preventDefault();
          e.stopPropagation();

          this.isDragging = true;
          this.dragStartY = e.clientY;
          this.dragStartScrollTop = this.win.scrollY || this.doc.documentElement.scrollTop || 0;

          this._prevHtmlScrollBehavior = this.doc.documentElement.style.scrollBehavior;
          this._prevBodyScrollBehavior = this.doc.body ? this.doc.body.style.scrollBehavior : "";
          this.doc.documentElement.style.scrollBehavior = "auto";
          if (this.doc.body) {
            this.doc.body.style.scrollBehavior = "auto";
            this.doc.body.classList.add("floating-scroll-dragging");
          }

          if (this.track.setPointerCapture && e.pointerId !== undefined) {
            try {
              this.track.setPointerCapture(e.pointerId);
            } catch (_) {}
          }

          if (typeof window !== "undefined" && window !== this.win) {
            window.addEventListener("pointermove", this._onPointerMove, { passive: false });
            window.addEventListener("pointerup", this._onPointerUp);
            window.addEventListener("pointercancel", this._onPointerUp);
          }

          this._styleThumb();
        };

        this._onPointerMove = (e) => {
          if (!this.isDragging) return;
          e.preventDefault();

          const deltaY = e.clientY - this.dragStartY;
          const scrollRatio = this.maxThumbTop > 0 ? this.maxScrollTop / this.maxThumbTop : 0;
          const nextScrollTop = Math.max(
            0,
            Math.min(this.maxScrollTop, this.dragStartScrollTop + deltaY * scrollRatio)
          );

          this.win.scrollTo(0, nextScrollTop);
          this._updateMetricsAndPosition();
        };

        this._onPointerUp = (e) => {
          if (!this.isDragging) return;
          this.isDragging = false;

          this.doc.documentElement.style.scrollBehavior = this._prevHtmlScrollBehavior || "";
          if (this.doc.body) {
            this.doc.body.style.scrollBehavior = this._prevBodyScrollBehavior || "";
            this.doc.body.classList.remove("floating-scroll-dragging");
          }

          if (this.track && this.track.releasePointerCapture && e && e.pointerId !== undefined) {
            try {
              this.track.releasePointerCapture(e.pointerId);
            } catch (_) {}
          }

          if (typeof window !== "undefined" && window !== this.win) {
            window.removeEventListener("pointermove", this._onPointerMove);
            window.removeEventListener("pointerup", this._onPointerUp);
            window.removeEventListener("pointercancel", this._onPointerUp);
          }

          this._styleThumb();
        };

        this.track.addEventListener("pointerdown", this._onPointerDown);
        this.win.addEventListener("pointermove", this._onPointerMove, { passive: false });
        this.win.addEventListener("pointerup", this._onPointerUp);
        this.win.addEventListener("pointercancel", this._onPointerUp);
      }

      _scheduleUpdate() {
        if (this.rafId) return;
        const reqAnim = (this.win && this.win.requestAnimationFrame) ? this.win.requestAnimationFrame : requestAnimationFrame;
        this.rafId = reqAnim(() => {
          this.rafId = null;
          this._updateMetricsAndPosition();
        });
      }

      _updateMetricsAndPosition() {
        if (this.isDestroyed || !this.track || !this.win || !this.doc) return;

        this._ensureThumbElement();
        if (!this.thumb) return;

        const docEl = this.doc.documentElement;
        const body = this.doc.body;

        const viewportHeight = this.win.innerHeight || docEl.clientHeight;
        const scrollHeight = Math.max(
          docEl.scrollHeight,
          body ? body.scrollHeight : 0,
          docEl.offsetHeight,
          body ? body.offsetHeight : 0
        );

        this.maxScrollTop = Math.max(0, scrollHeight - viewportHeight);

        if (this.maxScrollTop <= 1) {
          this.track.style.visibility = "hidden";
          this.track.style.pointerEvents = "none";
          return;
        }

        this.track.style.visibility = "visible";
        this.track.style.pointerEvents = "auto";

        const trackHeight = Math.max(1, viewportHeight - this.options.offsetY * 2);
        const proportionalHeight = (viewportHeight / scrollHeight) * trackHeight;
        this.thumbHeight = Math.max(
          this.options.minThumbHeight,
          Math.min(trackHeight, Math.round(proportionalHeight))
        );

        this.maxThumbTop = Math.max(0, trackHeight - this.thumbHeight);

        const currentScrollTop = Math.max(
          0,
          Math.min(this.maxScrollTop, this.win.scrollY || docEl.scrollTop || 0)
        );
        const scrollProgress = this.maxScrollTop > 0 ? currentScrollTop / this.maxScrollTop : 0;
        this.thumbTop = Math.round(scrollProgress * this.maxThumbTop);

        this.thumb.style.height = `${this.thumbHeight}px`;
        this.thumb.style.transform = `translate3d(0, ${this.thumbTop}px, 0)`;

        const progressPct = Math.round(scrollProgress * 100);
        this.track.setAttribute("aria-valuenow", String(progressPct));
      }

      setColor(color) {
        if (color) {
          this.color = color;
          this._applyClassesAndStyles();
          this._styleThumb();
        }
        return this;
      }

      refresh() {
        this._updateMetricsAndPosition();
        return this;
      }

      destroy() {
        this.isDestroyed = true;
        clearTimeout(this.hideTimer);

        if (this.rafId) {
          const cancelAnim = (this.win && this.win.cancelAnimationFrame) ? this.win.cancelAnimationFrame : cancelAnimationFrame;
          cancelAnim(this.rafId);
          this.rafId = null;
        }

        if (this.win) {
          this.win.removeEventListener("scroll", this._onScroll);
          this.win.removeEventListener("resize", this._onResize);
          this.win.removeEventListener("pointermove", this._onPointerMove);
          this.win.removeEventListener("pointerup", this._onPointerUp);
          this.win.removeEventListener("pointercancel", this._onPointerUp);
        }

        if (typeof window !== "undefined" && window !== this.win) {
          window.removeEventListener("pointermove", this._onPointerMove);
          window.removeEventListener("pointerup", this._onPointerUp);
          window.removeEventListener("pointercancel", this._onPointerUp);
        }

        if (this._resizeObserver) {
          this._resizeObserver.disconnect();
          this._resizeObserver = null;
        }

        if (this.track && this.track.parentNode) {
          this.track.parentNode.removeChild(this.track);
        }

        if (this.doc && this.doc.documentElement) {
          this.doc.documentElement.classList.remove("has-floating-scroll");
        }
        if (this.doc && this.doc.body) {
          this.doc.body.classList.remove("floating-scroll-dragging");
        }
      }
    }

    let iframeScrollInstance = null;

    function setupIframeFloatingScroll(frame) {
      if (!frame) return;
      try {
        const doc = frame.contentDocument || (frame.contentWindow && frame.contentWindow.document);
        const win = frame.contentWindow;
        if (!doc || !win || !doc.body) return;

        if (iframeScrollInstance) {
          iframeScrollInstance.destroy();
          iframeScrollInstance = null;
        }

        // Asegurar que la clase has-floating-scroll esté en html
        doc.documentElement.classList.add("has-floating-scroll");

        const isDark = (currentPreviewMode === 'dark');
        const activeColors = isDark 
          ? (themeConfig.dark.colors.length ? themeConfig.dark.colors : themeConfig.light.colors)
          : themeConfig.light.colors;
        const scrollColor = activeColors[1] || activeColors[0] || 'var(--ts-primary, #38bdf8)';

        iframeScrollInstance = new PreviewFloatingScroll(8, scrollColor, '', {
          targetWindow: win,
          targetDocument: doc,
          hoverExpand: 4,
          minThumbHeight: 36,
          offsetRight: 3,
          offsetY: 4,
          opacity: 0.65,
          autoHide: false
        });
      } catch (e) {
        // Ignorar posibles restricciones de sandbox
      }
    }

    function updateIframeFloatingScrollColor() {
      if (!iframeScrollInstance) return;
      try {
        const isDark = (currentPreviewMode === 'dark');
        const activeColors = isDark 
          ? (themeConfig.dark.colors.length ? themeConfig.dark.colors : themeConfig.light.colors)
          : themeConfig.light.colors;
        const scrollColor = activeColors[1] || activeColors[0] || 'var(--ts-primary, #38bdf8)';
        iframeScrollInstance.setColor(scrollColor);
        iframeScrollInstance.refresh();
      } catch (e) {}
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

          // Inicializar barra de scroll flotante en la preview (elimina barra nativa y gutter)
          setupIframeFloatingScroll(frame);

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
        css += `.back${i}-hover { background-color: var(${varName}) !important; transition: var(--transition, all 0.15s ease-in-out) !important; }\n`;
        css += `.back${i}-hover:hover, .back${i}-hover:focus { background-color: var(${hoverVarName}) !important; }\n`;
        css += `.color${i}, .color${i} a { color: var(${varName}) !important; }\n`;
        css += `.color${i}-hover, .color${i}-hover a { color: var(${varName}); transition: var(--transition, all 0.15s ease-in-out) !important; }\n`;
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

      // Eliminar barra de scroll nativa en el iframe de previsualización para usar floatingScroll
      css += `\n/* Ocultar barra nativa en el iframe para floatingScroll (sin reserva de gutter ni distorsión de ancho) */\n`;
      css += `html, body, html.has-floating-scroll, html.has-floating-scroll body {\n`;
      css += `  scrollbar-width: none !important;\n`;
      css += `  -ms-overflow-style: none !important;\n`;
      css += `  scrollbar-gutter: auto !important;\n`;
      css += `}\n`;
      css += `html::-webkit-scrollbar, body::-webkit-scrollbar,\n`;
      css += `html.has-floating-scroll::-webkit-scrollbar, html.has-floating-scroll body::-webkit-scrollbar {\n`;
      css += `  display: none !important;\n`;
      css += `  width: 0 !important;\n`;
      css += `  height: 0 !important;\n`;
      css += `  background: transparent !important;\n`;
      css += `}\n`;

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
        doc.documentElement.classList.add('has-floating-scroll');
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

      // Sincronizar el color de la barra de scroll flotante del iframe
      updateIframeFloatingScrollColor();

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

    function toggleSidebar() {
      const sidebar = document.querySelector('.ts-sidebar');
      const btn = document.getElementById('btnToggleSidebar');
      if (!sidebar) return;
      const isCollapsed = sidebar.classList.toggle('collapsed');
      if (btn) {
        btn.innerHTML = isCollapsed ? '▶ Panel' : '◀ Panel';
        btn.title = isCollapsed ? 'Mostrar panel lateral de configuración' : 'Ocultar panel lateral';
      }
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
      if (iframeScrollInstance) {
        setTimeout(() => iframeScrollInstance.refresh(), 260);
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

    let __confirmCallback = null;

    function openConfirmModal({ icon = '⚠️', title = '¿Estás seguro?', message = '', acceptText = 'Aceptar', acceptClass = 'ts-btn-primary', onAccept = null }) {
      const icoEl = document.getElementById('modalConfirmIcon');
      const titleEl = document.getElementById('modalConfirmTitle');
      const msgEl = document.getElementById('modalConfirmMessage');
      const acceptBtn = document.getElementById('btnConfirmAccept');

      if (icoEl) icoEl.textContent = icon;
      if (titleEl) titleEl.textContent = title;
      if (msgEl) msgEl.textContent = message;

      if (acceptBtn) {
        acceptBtn.textContent = acceptText;
        acceptBtn.className = `ts-btn ${acceptClass}`;
      }

      __confirmCallback = onAccept;

      const modal = document.getElementById('modalConfirm');
      if (modal) modal.classList.add('open');
    }

    function closeConfirmModal() {
      const modal = document.getElementById('modalConfirm');
      if (modal) modal.classList.remove('open');
      __confirmCallback = null;
    }

    function onConfirmModalAccept() {
      const cb = __confirmCallback;
      closeConfirmModal();
      if (typeof cb === 'function') {
        cb();
      }
    }

    function resetDefaults() {
      openConfirmModal({
        icon: '🔄',
        title: '¿Restaurar Configuración?',
        message: '¿Deseas restaurar los colores, tipografías y variables a los definidos en _configTheme.css del proyecto?',
        acceptText: 'Sí, Restaurar',
        acceptClass: 'ts-btn-primary',
        onAccept: () => {
          themeConfig = JSON.parse(JSON.stringify(defaultCssConfig));
          themeConfig.slug = currentThemeSlug;
          themeConfig.name = fullThemeConfig.themes[currentThemeSlug]?.name || 'Predeterminado';
          fullThemeConfig.themes[currentThemeSlug] = JSON.parse(JSON.stringify(themeConfig));
          initStudio();
          showToast('🔄 Colores restaurados a los de _configTheme.css');
        }
      });
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
      onScheduleDateChange();

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
            <button type="button" class="ts-btn ts-btn-secondary" style="padding:4px 10px; font-size:0.75rem; font-weight:700; color:#38bdf8; border-color:rgba(56,189,248,0.35);" onclick="loadThemeToSite('${s}')" title="Cargar y aplicar este tema al sitio web real">
              🚀 Cargar
            </button>
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
      if (show) onScheduleDateChange();
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

    // ==============================================================
    // MOTOR SELECTOR DE FECHAS (DATE PICKER) Y PROGRAMACIÓN
    // ==============================================================

    const DP_MONTH_NAMES = [
      'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
      'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];

    let dpTarget = null; // 'start' o 'end'
    let dpYear = new Date().getFullYear();
    let dpMonth = new Date().getMonth(); // 0 - 11

    function openDatePicker(target, evt) {
      if (evt) {
        evt.stopPropagation();
      }
      dpTarget = target;

      const input = document.getElementById(target === 'start' ? 'schedStart' : 'schedEnd');
      const val = input ? input.value.trim() : '';

      // Si el input ya tiene fecha, parsearla para mostrar ese mes
      if (val) {
        let parsedYear = new Date().getFullYear();
        let parsedMonth = new Date().getMonth();
        if (/^\d{4}-\d{2}-\d{2}$/.test(val)) {
          const parts = val.split('-');
          parsedYear = parseInt(parts[0], 10);
          parsedMonth = parseInt(parts[1], 10) - 1;
        } else if (/^\d{2}-\d{2}$/.test(val)) {
          const parts = val.split('-');
          parsedMonth = parseInt(parts[0], 10) - 1;
        }
        if (parsedMonth >= 0 && parsedMonth <= 11) {
          dpMonth = parsedMonth;
          dpYear = parsedYear;
        }
      }

      renderDatePicker();

      const popover = document.getElementById('tsDatePickerPopover');
      if (!popover) return;

      const triggerBtn = (evt && evt.currentTarget) ? evt.currentTarget : input;
      const rect = triggerBtn.getBoundingClientRect();
      const popWidth = 250;
      let left = rect.right - popWidth;
      if (left < 10) left = 10;
      let top = rect.bottom + 6;

      // Si sobrepasa la parte inferior de la ventana, mostrar hacia arriba
      if (top + 280 > window.innerHeight) {
        top = Math.max(10, rect.top - 280);
      }

      popover.style.left = `${left}px`;
      popover.style.top = `${top}px`;
      popover.style.display = 'block';

      setTimeout(() => {
        document.addEventListener('click', onDatePickerDocClick);
        document.addEventListener('keydown', onDatePickerKeyDown);
      }, 10);
    }

    function closeDatePicker() {
      const popover = document.getElementById('tsDatePickerPopover');
      if (popover) popover.style.display = 'none';
      document.removeEventListener('click', onDatePickerDocClick);
      document.removeEventListener('keydown', onDatePickerKeyDown);
      dpTarget = null;
    }

    function onDatePickerDocClick(e) {
      const popover = document.getElementById('tsDatePickerPopover');
      if (popover && !popover.contains(e.target) && !e.target.closest('.ts-date-picker-trigger')) {
        closeDatePicker();
      }
    }

    function onDatePickerKeyDown(e) {
      if (e.key === 'Escape') closeDatePicker();
    }

    function dpPrevMonth() {
      dpMonth--;
      if (dpMonth < 0) {
        dpMonth = 11;
        dpYear--;
      }
      renderDatePicker();
    }

    function dpNextMonth() {
      dpMonth++;
      if (dpMonth > 11) {
        dpMonth = 0;
        dpYear++;
      }
      renderDatePicker();
    }

    function renderDatePicker() {
      const titleEl = document.getElementById('dpCurrentMonthYear');
      const gridEl = document.getElementById('dpDaysGrid');
      const badgeEl = document.getElementById('dpModeBadge');
      if (!gridEl) return;

      if (titleEl) {
        titleEl.textContent = `${DP_MONTH_NAMES[dpMonth]} ${dpYear}`;
      }

      const isRecurring = document.getElementById('schedRecurring') ? document.getElementById('schedRecurring').checked : true;
      if (badgeEl) {
        badgeEl.textContent = isRecurring ? 'Formato: MM-DD (Anual)' : 'Formato: YYYY-MM-DD';
      }

      const inStart = document.getElementById('schedStart');
      const inEnd = document.getElementById('schedEnd');
      const startVal = inStart ? inStart.value.trim() : '';
      const endVal = inEnd ? inEnd.value.trim() : '';

      // Primer día del mes (0 = domingo, 1 = lunes, ..., 6 = sábado)
      const firstDayDate = new Date(dpYear, dpMonth, 1);
      let startingDay = firstDayDate.getDay();
      // Semana inicia en Lunes: Lu=0, Ma=1, Mi=2, Ju=3, Vi=4, Sá=5, Do=6
      startingDay = (startingDay === 0) ? 6 : startingDay - 1;

      const daysInMonth = new Date(dpYear, dpMonth + 1, 0).getDate();
      const today = new Date();
      const isCurrentMonthYear = (today.getFullYear() === dpYear && today.getMonth() === dpMonth);

      gridEl.innerHTML = '';

      // Celdas vacías iniciales
      for (let i = 0; i < startingDay; i++) {
        const empty = document.createElement('div');
        empty.className = 'ts-dp-day empty';
        gridEl.appendChild(empty);
      }

      for (let d = 1; d <= daysInMonth; d++) {
        const dayEl = document.createElement('div');
        dayEl.className = 'ts-dp-day';
        dayEl.textContent = String(d);

        const mStr = String(dpMonth + 1).padStart(2, '0');
        const dStr = String(d).padStart(2, '0');
        const ymdStr = `${dpYear}-${mStr}-${dStr}`;
        const mdStr = `${mStr}-${dStr}`;

        if (isCurrentMonthYear && today.getDate() === d) {
          dayEl.classList.add('today');
          dayEl.title = 'Hoy';
        }

        const isSelectedStart = (startVal === ymdStr || startVal === mdStr);
        const isSelectedEnd = (endVal === ymdStr || endVal === mdStr);
        const isCurrentTargetSelected = (dpTarget === 'start' && isSelectedStart) || (dpTarget === 'end' && isSelectedEnd);

        if (isCurrentTargetSelected || isSelectedStart || isSelectedEnd) {
          dayEl.classList.add('selected');
        } else if (startVal && endVal) {
          const sMd = startVal.slice(-5);
          const eMd = endVal.slice(-5);
          if (sMd <= eMd) {
            if (mdStr >= sMd && mdStr <= eMd) dayEl.classList.add('in-range');
          } else {
            if (mdStr >= sMd || mdStr <= eMd) dayEl.classList.add('in-range');
          }
        }

        dayEl.onclick = (e) => {
          e.stopPropagation();
          dpSelectDay(d);
        };

        gridEl.appendChild(dayEl);
      }
    }

    function dpSelectDay(day) {
      const isRecurring = document.getElementById('schedRecurring') ? document.getElementById('schedRecurring').checked : true;
      const mStr = String(dpMonth + 1).padStart(2, '0');
      const dStr = String(day).padStart(2, '0');
      const formatted = isRecurring ? `${mStr}-${dStr}` : `${dpYear}-${mStr}-${dStr}`;

      const targetInput = document.getElementById(dpTarget === 'start' ? 'schedStart' : 'schedEnd');
      if (targetInput) {
        targetInput.value = formatted;
      }

      const justChosen = dpTarget;
      closeDatePicker();
      onScheduleDateChange();

      // Si seleccionó fecha inicio y fin está vacía, abrir automáticamente el selector para fecha fin
      if (justChosen === 'start') {
        const inEnd = document.getElementById('schedEnd');
        if (inEnd && !inEnd.value.trim()) {
          setTimeout(() => {
            openDatePicker('end');
          }, 150);
        }
      }
    }

    function dpSelectToday() {
      const today = new Date();
      dpYear = today.getFullYear();
      dpMonth = today.getMonth();
      dpSelectDay(today.getDate());
    }

    function applySchedulePreset(presetKey) {
      const presets = {
        'navidad': { start: '12-20', end: '12-26', name: 'Navidad' },
        'ano-nuevo': { start: '12-28', end: '01-06', name: 'Año Nuevo' },
        'black-friday': { start: '11-20', end: '11-30', name: 'Black Friday / Cyber Monday' },
        'halloween': { start: '10-25', end: '11-02', name: 'Halloween' },
        'san-valentin': { start: '02-10', end: '02-16', name: 'San Valentín' }
      };

      const p = presets[presetKey];
      if (!p) return;

      const inStart = document.getElementById('schedStart');
      const inEnd = document.getElementById('schedEnd');
      const chkRec = document.getElementById('schedRecurring');

      if (inStart) inStart.value = p.start;
      if (inEnd) inEnd.value = p.end;
      if (chkRec) chkRec.checked = true;

      onScheduleDateChange();
      showToast(`📅 Preset aplicado: ${p.name} (${p.start} ➔ ${p.end})`);
    }

    function onScheduleRecurringChange() {
      const isRecurring = document.getElementById('schedRecurring').checked;
      const inStart = document.getElementById('schedStart');
      const inEnd = document.getElementById('schedEnd');

      if (isRecurring) {
        if (inStart && /^\d{4}-\d{2}-\d{2}$/.test(inStart.value)) inStart.value = inStart.value.slice(-5);
        if (inEnd && /^\d{4}-\d{2}-\d{2}$/.test(inEnd.value)) inEnd.value = inEnd.value.slice(-5);
      } else {
        const curY = new Date().getFullYear();
        if (inStart && /^\d{2}-\d{2}$/.test(inStart.value)) inStart.value = `${curY}-${inStart.value}`;
        if (inEnd && /^\d{2}-\d{2}$/.test(inEnd.value)) inEnd.value = `${curY}-${inEnd.value}`;
      }
      onScheduleDateChange();
    }

    function isCurrentScheduleActive(start, end, recurring) {
      if (!start || !end) return false;
      const now = new Date();
      if (recurring) {
        const sMd = start.slice(-5);
        const eMd = end.slice(-5);
        const nowM = String(now.getMonth() + 1).padStart(2, '0');
        const nowD = String(now.getDate()).padStart(2, '0');
        const todayMd = `${nowM}-${nowD}`;
        if (sMd <= eMd) {
          return todayMd >= sMd && todayMd <= eMd;
        } else {
          return todayMd >= sMd || todayMd <= eMd;
        }
      } else {
        const nowY = now.getFullYear();
        const nowM = String(now.getMonth() + 1).padStart(2, '0');
        const nowD = String(now.getDate()).padStart(2, '0');
        const todayYmd = `${nowY}-${nowM}-${nowD}`;
        return todayYmd >= start && todayYmd <= end;
      }
    }

    function formatFriendlyDateText(val) {
      if (!val) return '';
      const md = val.slice(-5);
      const parts = md.split('-');
      if (parts.length !== 2) return val;
      const mIdx = parseInt(parts[0], 10) - 1;
      const d = parseInt(parts[1], 10);
      if (mIdx >= 0 && mIdx < DP_MONTH_NAMES.length) {
        const shortMonths = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        return `${d} ${shortMonths[mIdx]}`;
      }
      return val;
    }

    function onScheduleDateChange() {
      const inStart = document.getElementById('schedStart');
      const inEnd = document.getElementById('schedEnd');
      const chkRec = document.getElementById('schedRecurring');
      const banner = document.getElementById('schedStatusBanner');
      const hintStart = document.getElementById('schedStartHint');
      const hintEnd = document.getElementById('schedEndHint');
      if (!inStart || !inEnd || !banner) return;

      const start = inStart.value.trim();
      const end = inEnd.value.trim();
      const rec = chkRec ? chkRec.checked : true;

      if (hintStart) hintStart.textContent = start ? formatFriendlyDateText(start) : '';
      if (hintEnd) hintEnd.textContent = end ? formatFriendlyDateText(end) : '';

      if (!start && !end) {
        banner.style.display = 'none';
        return;
      }

      if (!start || !end) {
        banner.style.display = 'block';
        banner.style.background = 'rgba(234, 179, 8, 0.15)';
        banner.style.border = '1px solid rgba(234, 179, 8, 0.35)';
        banner.style.color = '#fde047';
        banner.innerHTML = '⚠️ Completa ambas fechas (Inicio y Fin) para activar el rango.';
        return;
      }

      const activeNow = isCurrentScheduleActive(start, end, rec);
      banner.style.display = 'block';
      if (activeNow) {
        banner.style.background = 'rgba(34, 197, 94, 0.15)';
        banner.style.border = '1px solid rgba(34, 197, 94, 0.4)';
        banner.style.color = '#86efac';
        banner.innerHTML = `🟢 <strong>ACTIVO AHORA:</strong> El tema se aplica automáticamente hoy (${start} ➔ ${end}${rec ? ', anual' : ''}).`;
      } else {
        banner.style.background = 'rgba(56, 189, 248, 0.12)';
        banner.style.border = '1px solid rgba(56, 189, 248, 0.35)';
        banner.style.color = '#bae6fd';
        banner.innerHTML = `📅 <strong>PROGRAMADO:</strong> Se activará del ${start} al ${end} ${rec ? '(todos los años)' : ''}.`;
      }
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

    function getStudioHeaders() {
      const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
      const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      };
      if (token) {
        headers['X-CSRF-TOKEN'] = token;
        headers['X-XSRF-TOKEN'] = token;
      }
      return headers;
    }

    async function loadThemeToSite(slug) {
      if (!slug) return;

      // Si el tema que se va a cargar es el que se está editando actualmente,
      // sincronizamos los cambios locales al fullThemeConfig
      if (slug === currentThemeSlug) {
        themeConfig.slug = slug;
        if (!themeConfig.name && fullThemeConfig.themes[slug]) {
          themeConfig.name = fullThemeConfig.themes[slug].name;
        }
        fullThemeConfig.themes[slug] = JSON.parse(JSON.stringify(themeConfig));
      }

      showToast(`⏳ Cargando tema "${slug}" y aplicando al sitio web...`);

      try {
        const response = await fetch('/_frame/theme-studio/load-to-site', {
          method: 'POST',
          headers: getStudioHeaders(),
          body: JSON.stringify({
            slug: slug,
            fullConfig: fullThemeConfig
          })
        });

        if (!response.ok) {
          const errText = await response.text();
          console.error('Error response:', errText);
          showToast('❌ Error del servidor al cargar tema en el sitio', 'error');
          return;
        }

        const res = await response.json();
        if (res.success) {
          activeThemeSlug = slug;
          if (res.fullConfig) {
            fullThemeConfig = res.fullConfig;
          }
          renderThemesTab();
          applySandboxStyles();
          showToast(`🚀 ¡Tema "${slug}" cargado y aplicado con éxito al sitio web!`, 'success');
        } else {
          showToast('❌ ' + (res.message || 'Error al cargar tema en el sitio'), 'error');
        }
      } catch (e) {
        console.error('Connection error:', e);
        showToast('❌ Error al procesar la respuesta del servidor', 'error');
      }
    }

    async function activateTheme(slug) {
      try {
        const response = await fetch('/_frame/theme-studio/set-active', {
          method: 'POST',
          headers: getStudioHeaders(),
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
      openConfirmModal({
        icon: '🗑️',
        title: `¿Eliminar tema "${slug}"?`,
        message: 'Esta acción eliminará permanentemente la paleta y configuración de este tema guardado.',
        acceptText: 'Sí, Eliminar',
        acceptClass: 'ts-btn-secondary',
        onAccept: async () => {
          try {
            const response = await fetch('/_frame/theme-studio/delete-theme', {
              method: 'POST',
              headers: getStudioHeaders(),
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
      });
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
          headers: getStudioHeaders(),
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
          showToast('💾 ' + (data.message || 'Tema guardado en theme.json (modo aislado)'), 'success');
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

      // Limpiar prefijo de emoji si viene en el texto para no duplicar icono
      const cleanMsg = message.replace(/^[\p{Emoji}\u200d\uFE0F]+\s*/u, '');
      msg.textContent = cleanMsg;

      let ico = '✅';
      if (type === 'error') {
        ico = '❌';
      } else if (message.includes('⚡')) {
        ico = '⚡';
      } else if (message.includes('🚀')) {
        ico = '🚀';
      } else if (message.includes('💾')) {
        ico = '💾';
      } else if (message.includes('⏳')) {
        ico = '⏳';
      } else if (message.includes('🗑️')) {
        ico = '🗑️';
      }
      icon.textContent = ico;
      toast.className = `ts-toast show ${type}`;

      clearTimeout(window.__toastTimer);
      window.__toastTimer = setTimeout(() => {
        toast.className = 'ts-toast';
      }, 4000);
    }

    // Iniciar estudio al cargar el DOM
    document.addEventListener('DOMContentLoaded', initStudio);

    // Actualizar barra de scroll flotante del iframe al redimensionar la ventana principal
    window.addEventListener('resize', () => {
      if (iframeScrollInstance) {
        iframeScrollInstance.refresh();
      }
    });
  </script>
</body>
</html>
