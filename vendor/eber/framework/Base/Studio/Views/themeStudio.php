<?php
if (!defined('ROOT_PATH') || !class_exists(\Base\Studio\StudioKernel::class) || !\Base\Studio\StudioKernel::isDevEnvironment()) {
  http_response_code(404);
  exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Frame Studio</title>
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
      flex-wrap: wrap;
      row-gap: 6px;
      gap: 10px;
      flex-shrink: 0;
    }
    .ts-canvas-nav {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 6px;
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
      grid-template-columns: repeat(3, 1fr);
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
    .ts-tab-content {
      flex: 1;
      overflow-y: auto;
      padding: 18px;
      display: none;
    }
    .ts-tab-content.active {
      display: block;
    }

    /* Barra de desplazamiento nativa oculta en todo Studio (se usa Resources/Js/Components/floatingScroll.js) */
    .ts-sidebar,
    .ts-tab-content,
    #jsModulesSandboxView,
    #configSandboxView,
    #chartSandboxStage,
    #iconSandboxStage,
    #inputSandboxStage,
    #isFoldersContainer,
    #jsonViewer {
      scrollbar-width: none !important;
      -ms-overflow-style: none !important;
    }
    .ts-sidebar::-webkit-scrollbar,
    .ts-tab-content::-webkit-scrollbar,
    #jsModulesSandboxView::-webkit-scrollbar,
    #configSandboxView::-webkit-scrollbar,
    #chartSandboxStage::-webkit-scrollbar,
    #iconSandboxStage::-webkit-scrollbar,
    #inputSandboxStage::-webkit-scrollbar,
    #isFoldersContainer::-webkit-scrollbar,
    #jsonViewer::-webkit-scrollbar {
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

    /* Media Queries Cards */
    .ts-mq-card {
      background: rgba(15, 23, 42, 0.6);
      border: 1px solid var(--ts-border);
      border-radius: 8px;
      padding: 10px;
      margin-bottom: 10px;
    }
    .ts-mq-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      margin-bottom: 8px;
    }
    .ts-mq-badges {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 4px;
    }
    .ts-mq-class-badge {
      font-size: 0.7rem;
      font-family: monospace;
      color: var(--ts-primary);
      background: rgba(56, 189, 248, 0.12);
      border: 1px solid rgba(56, 189, 248, 0.28);
      padding: 2px 6px;
      border-radius: 4px;
    }
    .ts-mq-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
      margin-bottom: 8px;
    }
    .ts-mq-field label {
      display: block;
      font-size: 0.68rem;
      color: var(--ts-muted);
      margin-bottom: 3px;
      font-weight: 600;
    }
    .ts-mq-input {
      width: 100%;
      background: var(--ts-bg);
      border: 1px solid var(--ts-border);
      color: #fff;
      padding: 5px 8px;
      border-radius: 5px;
      font-size: 0.78rem;
      font-family: inherit;
      outline: none;
    }
    .ts-mq-input:focus {
      border-color: var(--ts-primary);
    }
    .ts-mq-input[readonly] {
      opacity: 0.65;
      cursor: not-allowed;
    }
    .ts-mq-preview {
      font-size: 0.7rem;
      font-family: monospace;
      color: #94a3b8;
      background: rgba(2, 6, 23, 0.6);
      padding: 4px 8px;
      border-radius: 4px;
      overflow-x: auto;
      white-space: nowrap;
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
      min-height: 0;
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
      min-height: 0;
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
      min-height: 0;
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

    /* ==============================================================
       CHART STUDIO (CREADOR Y CONFIGURADOR DE GRÁFICOS)
       ============================================================== */
    .ts-stage-switcher {
      display: flex;
      align-items: center;
      background: var(--ts-bg);
      border: 1px solid var(--ts-border);
      border-radius: 7px;
      padding: 2px;
      gap: 2px;
      flex-shrink: 0;
    }
    .ts-stage-btn {
      background: transparent;
      border: none;
      color: var(--ts-muted);
      cursor: pointer;
      padding: 4px 10px;
      border-radius: 5px;
      font-size: 0.78rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: all 0.15s ease;
      white-space: nowrap;
    }
    .ts-stage-btn:hover {
      color: #fff;
      background: rgba(255, 255, 255, 0.06);
    }
    .ts-stage-btn.active {
      background: rgba(56, 189, 248, 0.18);
      color: var(--ts-primary);
      font-weight: 700;
    }

    /* Controles en Sidebar #tab-charts */
    .cs-scope-bar {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 6px;
      background: var(--ts-bg);
      padding: 4px;
      border-radius: 8px;
      border: 1px solid var(--ts-border);
      margin-bottom: 10px;
    }
    .cs-scope-btn {
      background: transparent;
      border: 1px solid transparent;
      color: var(--ts-muted);
      padding: 7px 6px;
      border-radius: 6px;
      font-size: 0.75rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.15s ease;
      text-align: center;
    }
    .cs-scope-btn.active {
      background: rgba(56, 189, 248, 0.16);
      color: var(--ts-primary);
      border-color: rgba(56, 189, 248, 0.45);
    }
    .cs-chart-type-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 6px;
    }
    .cs-type-btn {
      background: rgba(15, 23, 42, 0.65);
      border: 1px solid var(--ts-border);
      color: #cbd5e1;
      border-radius: 7px;
      padding: 7px 8px;
      font-size: 0.73rem;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      text-align: left;
      transition: all 0.15s ease;
      min-width: 0;
    }
    .cs-type-btn:hover {
      background: var(--ts-surface-hover);
      color: #fff;
      border-color: rgba(255, 255, 255, 0.2);
    }
    .cs-type-btn.active {
      background: rgba(56, 189, 248, 0.14);
      border-color: var(--ts-primary);
      color: #fff;
      box-shadow: 0 0 0 1px rgba(56, 189, 248, 0.25);
    }
    .cs-type-btn-text {
      display: flex;
      flex-direction: column;
      min-width: 0;
    }
    .cs-type-btn-name {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      line-height: 1.2;
    }
    .cs-type-btn-method {
      font-size: 0.64rem;
      font-family: monospace;
      color: var(--ts-muted);
      font-weight: 400;
    }

    .cs-color-control {
      background: rgba(15, 23, 42, 0.55);
      border: 1px solid var(--ts-border);
      border-radius: 8px;
      padding: 9px 10px;
      margin-bottom: 10px;
    }
    .cs-color-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 6px;
      font-size: 0.78rem;
      font-weight: 600;
      color: #e2e8f0;
    }
    .cs-color-chips {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
      margin-bottom: 7px;
    }
    .cs-color-chip {
      border: 1px solid var(--ts-border);
      background: var(--ts-bg);
      color: #cbd5e1;
      border-radius: 999px;
      padding: 2px 7px;
      font-size: 0.67rem;
      font-family: monospace;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      transition: all 0.12s ease;
    }
    .cs-color-chip:hover {
      border-color: var(--ts-primary);
      color: #fff;
    }
    .cs-color-chip.active {
      background: rgba(56, 189, 248, 0.2);
      border-color: var(--ts-primary);
      color: #fff;
      font-weight: 700;
    }
    .cs-chip-dot,
    .cs-color-dot {
      width: 9px;
      height: 9px;
      border-radius: 50%;
      border: 1px solid rgba(255, 255, 255, 0.35);
      flex-shrink: 0;
    }
    .cs-color-input-row {
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .cs-color-input-row .ts-input-text {
      padding: 5px 8px;
      font-size: 0.78rem;
      font-family: monospace;
    }

    .cs-measure-row {
      background: rgba(15, 23, 42, 0.55);
      border: 1px solid var(--ts-border);
      border-radius: 8px;
      padding: 9px 10px;
      margin-bottom: 9px;
    }
    .cs-measure-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      margin-bottom: 6px;
      font-size: 0.78rem;
      font-weight: 600;
      color: #e2e8f0;
    }
    .cs-measure-controls {
      display: flex;
      align-items: center;
      gap: 7px;
    }
    .cs-num-input {
      width: 62px;
      background: var(--ts-bg);
      border: 1px solid var(--ts-border);
      color: var(--ts-primary);
      padding: 4px 6px;
      border-radius: 5px;
      font-size: 0.78rem;
      font-family: monospace;
      font-weight: 700;
      text-align: center;
      outline: none;
    }
    .cs-num-input:focus {
      border-color: var(--ts-primary);
    }
    .cs-auto-btn {
      background: var(--ts-bg);
      border: 1px solid var(--ts-border);
      color: var(--ts-muted);
      padding: 4px 8px;
      border-radius: 5px;
      font-size: 0.7rem;
      font-weight: 600;
      cursor: pointer;
      white-space: nowrap;
      transition: all 0.12s ease;
    }
    .cs-auto-btn:hover,
    .cs-auto-btn.active {
      border-color: var(--ts-primary);
      color: var(--ts-primary);
      background: rgba(56, 189, 248, 0.12);
    }

    .cs-seg-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 5px;
    }
    .cs-seg-btn {
      background: var(--ts-bg);
      border: 1px solid var(--ts-border);
      color: var(--ts-muted);
      padding: 6px 4px;
      border-radius: 6px;
      font-size: 0.72rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.12s ease;
      text-align: center;
    }
    .cs-seg-btn:hover {
      color: #fff;
      border-color: rgba(255, 255, 255, 0.25);
    }
    .cs-seg-btn.active {
      background: rgba(56, 189, 248, 0.18);
      border-color: var(--ts-primary);
      color: var(--ts-primary);
    }
    .cs-split-bar-preview {
      display: flex;
      height: 22px;
      border-radius: 6px;
      overflow: hidden;
      border: 1px solid var(--ts-border);
      background: var(--ts-bg);
      margin-top: 8px;
      font-size: 0.65rem;
      font-weight: 700;
      font-family: monospace;
    }
    .cs-split-seg-label {
      background: rgba(56, 189, 248, 0.28);
      color: #e0f2fe;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      white-space: nowrap;
      transition: width 0.15s ease;
    }
    .cs-split-seg-gap {
      background: rgba(148, 163, 184, 0.1);
      color: var(--ts-muted);
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      white-space: nowrap;
      transition: width 0.15s ease;
    }
    .cs-split-seg-chart {
      background: rgba(34, 197, 94, 0.28);
      color: #dcfce7;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      white-space: nowrap;
      transition: width 0.15s ease;
    }
    .cs-syntax-badge {
      display: block;
      margin-top: 8px;
      background: #020617;
      border: 1px solid var(--ts-border);
      border-radius: 6px;
      padding: 7px 9px;
      font-family: monospace;
      font-size: 0.74rem;
      color: var(--ts-primary);
      word-break: break-all;
    }
    .cs-local-badge {
      font-size: 0.64rem;
      padding: 1px 6px;
      border-radius: 999px;
      font-family: monospace;
      font-weight: 700;
    }
    .cs-local-badge.is-local {
      background: rgba(245, 158, 11, 0.2);
      color: #fde68a;
      border: 1px solid rgba(245, 158, 11, 0.45);
    }
    .cs-local-badge.is-global {
      background: rgba(56, 189, 248, 0.14);
      color: #bae6fd;
      border: 1px solid rgba(56, 189, 248, 0.3);
    }
    .cs-check-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 7px;
      margin-top: 8px;
    }
    .cs-check-item {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 0.75rem;
      color: #cbd5e1;
      cursor: pointer;
    }

    /* Escenario Derecho: Chart Sandbox View */
    #chartSandboxView {
      display: none;
      flex: 1;
      min-height: 0;
      flex-direction: column;
      overflow: hidden;
      background-color: var(--body, #ffffff);
      color: var(--text-primary, #0f172a);
      font-family: var(--font, system-ui, sans-serif);
    }
    .cs-toolbar {
      background: var(--ts-surface);
      border-bottom: 1px solid var(--ts-border);
      padding: 8px 14px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 8px;
      flex-shrink: 0;
      font-family: var(--ts-font);
      color: var(--ts-text);
    }
    .cs-toolbar-group {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }
    .cs-toolbar-select {
      background: var(--ts-bg);
      border: 1px solid var(--ts-border);
      color: #f8fafc;
      padding: 5px 8px;
      border-radius: 6px;
      font-size: 0.78rem;
      font-weight: 600;
      outline: none;
      cursor: pointer;
    }
    .cs-sandbox-stage {
      flex: 1;
      min-height: 0;
      overflow-y: auto;
      padding: 18px;
      background-color: var(--body, #ffffff);
      color: var(--text-primary, #0f172a);
      transition: background-color 0.2s ease, color 0.2s ease;
    }
    .cs-gallery-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 16px;
      align-items: stretch;
    }
    .cs-chart-card {
      border-radius: 14px;
      padding: 16px;
      border: 1px solid rgba(128, 128, 128, 0.22);
      display: flex;
      flex-direction: column;
      gap: 10px;
      transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.2s ease;
      cursor: pointer;
      min-width: 0;
      position: relative;
    }
    .cs-chart-card:hover {
      border-color: rgba(56, 189, 248, 0.65);
    }
    .cs-chart-card.active {
      border-color: var(--ts-primary);
      box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.35);
    }
    .cs-chart-card-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      font-size: 0.78rem;
      font-weight: 700;
    }
    .cs-chart-card-title {
      display: flex;
      align-items: center;
      gap: 6px;
      min-width: 0;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .cs-chart-card-method {
      font-family: monospace;
      font-size: 0.68rem;
      opacity: 0.72;
      font-weight: 600;
      flex-shrink: 0;
    }
    .cs-chart-card-body {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 150px;
      width: 100%;
      min-width: 0;
    }
    .cs-chart-card-body > svg,
    .cs-chart-card-body > .mono-chart-svg {
      width: 100%;
      max-width: 100%;
    }
    .cs-focused-wrap {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 14px;
      width: 100%;
    }
    .cs-focused-card {
      width: 100%;
      border-radius: 16px;
      padding: 20px;
      border: 1px solid rgba(128, 128, 128, 0.25);
      transition: max-width 0.25s ease, background-color 0.2s ease;
      box-shadow: 0 10px 28px rgba(0, 0, 0, 0.14);
    }
    .cs-focused-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      font-size: 0.73rem;
      font-family: monospace;
      opacity: 0.85;
      margin-top: 12px;
      padding-top: 10px;
      border-top: 1px dashed rgba(128, 128, 128, 0.25);
    }

    /* Dock de Código PHP Copy & Paste */
    .cs-code-dock {
      background: #0b1120;
      border-top: 1px solid var(--ts-border);
      color: var(--ts-text);
      font-family: var(--ts-font);
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
    }
    .cs-code-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 8px;
      padding: 8px 14px;
      background: var(--ts-surface);
      border-bottom: 1px solid var(--ts-border);
    }
    .cs-code-tabs {
      display: flex;
      align-items: center;
      gap: 4px;
    }
    .cs-code-tab {
      background: var(--ts-bg);
      border: 1px solid var(--ts-border);
      color: var(--ts-muted);
      padding: 4px 10px;
      border-radius: 5px;
      font-size: 0.74rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.12s ease;
    }
    .cs-code-tab.active {
      background: rgba(56, 189, 248, 0.16);
      border-color: var(--ts-primary);
      color: var(--ts-primary);
    }
    .cs-code-options {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 10px;
      font-size: 0.74rem;
      color: #cbd5e1;
    }
    .cs-code-options label {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      cursor: pointer;
      white-space: nowrap;
    }
    .cs-code-body {
      max-height: 205px;
      overflow: auto;
      padding: 12px 14px;
      transition: max-height 0.2s ease, padding 0.2s ease;
    }
    .cs-code-dock.collapsed .cs-code-body {
      max-height: 0;
      padding-top: 0;
      padding-bottom: 0;
      overflow: hidden;
    }
    .cs-code-pre {
      margin: 0;
      font-family: 'Cascadia Code', 'Fira Code', Consolas, monospace;
      font-size: 0.78rem;
      line-height: 1.48;
      color: #e2e8f0;
      white-space: pre;
      tab-size: 2;
    }

    /* ==============================================================
       ICON STUDIO (GALERÍA VISUAL DE SVG POR CARPETAS)
       ============================================================== */
    #iconSandboxView {
      display: none;
      flex: 1;
      min-height: 0;
      flex-direction: column;
      overflow: hidden;
      background-color: var(--body, #ffffff);
      color: var(--text-primary, #0f172a);
      font-family: var(--font, system-ui, sans-serif);
    }
    .is-search-wrap {
      position: relative;
      display: flex;
      align-items: center;
      flex: 1;
      min-width: 170px;
    }
    .is-search-input {
      width: 100%;
      background: var(--ts-bg);
      border: 1px solid var(--ts-border);
      color: #f8fafc;
      padding: 6px 28px 6px 10px;
      border-radius: 6px;
      font-size: 0.78rem;
      outline: none;
      transition: border-color 0.15s ease;
    }
    .is-search-input:focus {
      border-color: var(--ts-primary);
    }
    .is-search-clear {
      position: absolute;
      right: 6px;
      background: transparent;
      border: none;
      color: var(--ts-muted);
      cursor: pointer;
      font-size: 0.78rem;
      padding: 2px 4px;
      display: none;
      line-height: 1;
    }
    .is-search-clear.visible {
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .is-search-clear:hover {
      color: #fff;
    }
    .is-size-segmented {
      display: inline-flex;
      align-items: center;
      background: var(--ts-bg);
      border: 1px solid var(--ts-border);
      border-radius: 6px;
      padding: 2px;
      gap: 2px;
    }
    .is-size-btn {
      background: transparent;
      border: none;
      color: var(--ts-muted);
      cursor: pointer;
      padding: 3px 8px;
      border-radius: 4px;
      font-size: 0.72rem;
      font-weight: 700;
      transition: all 0.12s ease;
    }
    .is-size-btn.active {
      background: var(--ts-surface-hover);
      color: var(--ts-primary);
    }
    .is-sandbox-stage {
      flex: 1;
      min-height: 0;
      overflow-y: auto;
      padding: 18px;
      background-color: var(--body, #ffffff);
      color: var(--text-primary, #0f172a);
      transition: background-color 0.2s ease, color 0.2s ease;
      font-family: var(--ts-font);
    }
    .is-folders-stack {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
    .is-folder-section {
      border-radius: 14px;
      padding: 14px 16px;
      border: 1px solid rgba(128, 128, 128, 0.22);
      background: rgba(128, 128, 128, 0.04);
    }
    .is-folder-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 12px;
      padding-bottom: 8px;
      border-bottom: 1px dashed rgba(128, 128, 128, 0.22);
    }
    .is-folder-title-wrap {
      display: flex;
      align-items: center;
      gap: 8px;
      min-width: 0;
    }
    .is-folder-title {
      font-size: 0.92rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .is-folder-path {
      font-family: monospace;
      font-size: 0.72rem;
      opacity: 0.7;
    }
    .is-folder-count {
      font-family: monospace;
      font-size: 0.72rem;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 999px;
      background: rgba(56, 189, 248, 0.14);
      border: 1px solid rgba(56, 189, 248, 0.35);
      color: #0284c7;
    }
    #iconSandboxView[data-surface-dark="1"] .is-folder-count {
      color: #7dd3fc;
    }
    .is-icon-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(112px, 1fr));
      gap: 10px;
    }
    .is-icon-card {
      position: relative;
      border-radius: 10px;
      padding: 12px 8px 9px;
      border: 1px solid rgba(128, 128, 128, 0.22);
      background: rgba(128, 128, 128, 0.05);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 8px;
      cursor: pointer;
      transition: transform 0.14s ease, border-color 0.14s ease, background-color 0.14s ease, box-shadow 0.14s ease;
      min-width: 0;
      user-select: none;
    }
    .is-icon-card:hover {
      border-color: var(--ts-primary);
      background: rgba(56, 189, 248, 0.1);
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
    }
    .is-icon-card.selected {
      border-color: var(--ts-primary);
      box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.35);
    }
    .is-icon-card.copied {
      border-color: var(--ts-success);
      box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.35);
    }
    .is-icon-glyph {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 44px;
      height: 44px;
      font-size: var(--is-preview-size, 28px);
      line-height: 1;
      transition: transform 0.14s ease, color 0.14s ease;
    }
    .is-icon-card:hover .is-icon-glyph {
      transform: scale(1.08);
    }
    .is-icon-name {
      width: 100%;
      font-size: 0.7rem;
      font-family: monospace;
      font-weight: 600;
      text-align: center;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      opacity: 0.9;
    }
    .is-icon-copy-pill {
      position: absolute;
      top: 5px;
      right: 5px;
      font-size: 0.6rem;
      font-weight: 700;
      padding: 1px 5px;
      border-radius: 4px;
      background: var(--ts-surface);
      color: var(--ts-primary);
      border: 1px solid var(--ts-border);
      opacity: 0;
      transition: opacity 0.12s ease;
      pointer-events: none;
    }
    .is-icon-card:hover .is-icon-copy-pill {
      opacity: 1;
    }
    .is-icon-card.copied .is-icon-copy-pill {
      opacity: 1;
      background: var(--ts-success);
      border-color: var(--ts-success);
      color: #fff;
    }
    .is-empty-folder {
      padding: 12px 14px;
      border-radius: 8px;
      border: 1px dashed rgba(128, 128, 128, 0.32);
      font-size: 0.78rem;
      opacity: 0.82;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 8px;
    }
    .is-empty-search {
      text-align: center;
      padding: 40px 20px;
      border-radius: 12px;
      border: 1px dashed rgba(128, 128, 128, 0.32);
      font-size: 0.88rem;
      opacity: 0.85;
    }

    /* Sidebar Icon Studio */
    .is-inspector-card {
      background: rgba(15, 23, 42, 0.65);
      border: 1px solid var(--ts-border);
      border-radius: 10px;
      padding: 12px;
      margin-bottom: 12px;
    }
    .is-inspector-top {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 10px;
    }
    .is-inspector-preview {
      width: 64px;
      height: 64px;
      border-radius: 10px;
      border: 1px solid rgba(128, 128, 128, 0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 34px;
      flex-shrink: 0;
    }
    .is-inspector-meta {
      min-width: 0;
      flex: 1;
    }
    .is-inspector-name {
      font-size: 0.86rem;
      font-weight: 700;
      font-family: monospace;
      color: #fff;
      word-break: break-all;
      margin-bottom: 3px;
    }
    .is-inspector-sub {
      font-size: 0.71rem;
      color: var(--ts-muted);
      line-height: 1.35;
    }
    .is-folder-pills {
      display: flex;
      flex-wrap: wrap;
      gap: 5px;
    }
    .is-folder-pill {
      background: var(--ts-bg);
      border: 1px solid var(--ts-border);
      color: #cbd5e1;
      border-radius: 999px;
      padding: 4px 9px;
      font-size: 0.71rem;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: all 0.12s ease;
    }
    .is-folder-pill:hover {
      border-color: var(--ts-primary);
      color: #fff;
    }
    .is-folder-pill.active {
      background: rgba(56, 189, 248, 0.18);
      border-color: var(--ts-primary);
      color: var(--ts-primary);
    }
    .is-folder-pill-count {
      font-family: monospace;
      font-size: 0.66rem;
      opacity: 0.78;
    }

    /* ==============================================================
       UI / INPUT STUDIO (FORMUIMODULE SANDBOX & DESIGNER)
       ============================================================== */
    #inputSandboxView {
      display: none;
      flex: 1;
      min-height: 0;
      flex-direction: column;
      height: 100%;
      width: 100%;
      background-color: var(--body, #f8fafc);
      color: var(--text-primary, #0f172a);
      overflow: hidden;
      transition: background-color 0.2s ease, color 0.2s ease;
    }
    .iu-sandbox-stage {
      flex: 1;
      min-height: 0;
      overflow-y: auto;
      padding: 20px;
      background-color: var(--iu-surface, var(--body, #f8fafc));
      transition: background-color 0.2s ease;
    }
    .iu-gallery-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 16px;
      align-items: start;
    }
    .iu-input-card {
      border-radius: 14px;
      padding: 14px;
      border: 1px solid rgba(128, 128, 128, 0.22);
      background-color: var(--iu-card-bg, rgba(128, 128, 128, 0.04));
      display: flex;
      flex-direction: column;
      gap: 12px;
      min-width: 0;
      transition: border-color 0.16s ease, box-shadow 0.16s ease;
    }
    .iu-input-card.active {
      border-color: var(--ts-primary, #38bdf8);
      box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.25);
    }
    .iu-card-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      cursor: pointer;
    }
    .iu-card-title {
      font-size: 0.84rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .iu-card-meta {
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .iu-card-actions {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
      align-items: center;
    }
    .iu-card-pill-btn {
      border: 1px solid rgba(128, 128, 128, 0.35);
      background: rgba(128, 128, 128, 0.1);
      color: inherit;
      font-size: 0.7rem;
      font-weight: 600;
      padding: 4px 9px;
      border-radius: 999px;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .iu-card-pill-btn:hover {
      border-color: var(--ts-primary);
      color: var(--ts-primary);
    }
    .iu-card-body {
      min-width: 0;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .iu-focused-wrap {
      display: none;
      flex-direction: column;
      align-items: center;
      gap: 14px;
      width: 100%;
    }
    .iu-focused-card {
      width: 100%;
      max-width: 100%;
      border-radius: 16px;
      padding: 20px;
      border: 1px solid rgba(128, 128, 128, 0.24);
      background-color: var(--iu-card-bg, rgba(128, 128, 128, 0.04));
      display: flex;
      flex-direction: column;
      gap: 14px;
      transition: max-width 0.22s ease;
    }
    .iu-inline-preview-panel {
      width: 100%;
      max-width: 100%;
      border-radius: 16px;
      padding: 16px 20px 22px;
      border: 1px dashed rgba(56, 189, 248, 0.45);
      background: rgba(128, 128, 128, 0.04);
      display: flex;
      flex-direction: column;
      gap: 14px;
      transition: max-width 0.22s ease;
      box-sizing: border-box;
    }
    .iu-inline-preview-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      font-size: 0.78rem;
      font-weight: 700;
      opacity: 0.86;
    }
    .iu-inline-preview-badge {
      font-size: 0.68rem;
      font-weight: 600;
      padding: 2px 8px;
      border-radius: 999px;
      background: rgba(56, 189, 248, 0.14);
      border: 1px solid rgba(56, 189, 248, 0.35);
      color: var(--ts-primary, #38bdf8);
    }
    .iu-inline-preview-host {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      width: 100%;
      min-width: 0;
    }
    #inputSandboxView.is-touch-simulated .iu-focused-card,
    #inputSandboxView.is-touch-simulated .iu-gallery-grid {
      --ui-touch-size: 48px;
    }
    .iu-touch-btn.active {
      background: rgba(56, 189, 248, 0.22) !important;
      border-color: var(--ts-primary) !important;
      color: var(--ts-primary) !important;
    }
    .iu-wcag-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      padding: 7px 10px;
      border-radius: 8px;
      background: rgba(15, 23, 42, 0.55);
      border: 1px solid var(--ts-border);
      font-size: 0.74rem;
      margin-bottom: 8px;
    }
    .iu-wcag-badge {
      font-family: monospace;
      font-weight: 700;
      padding: 2px 7px;
      border-radius: 999px;
      font-size: 0.7rem;
    }
    .iu-wcag-badge.pass {
      background: rgba(34, 197, 94, 0.2);
      color: #4ade80;
    }
    .iu-wcag-badge.fail {
      background: rgba(239, 68, 68, 0.2);
      color: #f87171;
    }
    .iu-color-pair {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
    }
    .iu-group-box {
      background: rgba(15, 23, 42, 0.36);
      border: 1px solid var(--ts-border);
      border-radius: 10px;
      padding: 9px 10px;
      margin-bottom: 9px;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .iu-group-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 6px;
      font-size: 0.72rem;
      font-weight: 700;
      color: var(--ts-text);
      letter-spacing: 0.01em;
    }
    .iu-scope-banner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      padding: 7px 10px;
      border-radius: 9px;
      background: rgba(56, 189, 248, 0.12);
      border: 1px solid rgba(56, 189, 248, 0.32);
      font-size: 0.73rem;
      margin-bottom: 8px;
    }
    .iu-type-cloud {
      display: flex;
      flex-wrap: wrap;
      gap: 5px;
    }
    .iu-type-pill {
      border: 1px solid var(--ts-border);
      background: var(--ts-bg);
      color: #cbd5e1;
      border-radius: 999px;
      padding: 3px 9px;
      font-size: 0.71rem;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      transition: all 0.12s ease;
      white-space: nowrap;
    }
    .iu-type-pill:hover {
      border-color: var(--ts-primary);
      color: #fff;
    }
    .iu-type-pill.active {
      background: rgba(56, 189, 248, 0.2);
      border-color: var(--ts-primary);
      color: #fff;
      font-weight: 700;
      box-shadow: 0 0 0 1px rgba(56, 189, 248, 0.25);
    }
    .iu-type-pill-dot {
      color: var(--ts-primary);
      font-size: 0.68rem;
      line-height: 1;
    }
    .iu-floating-color-dock {
      position: sticky;
      top: -18px;
      z-index: 25;
      margin: 0 -6px 12px -6px;
      padding: 8px 10px;
      border-radius: 10px;
      background: rgba(15, 23, 42, 0.95);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 1px solid rgba(56, 189, 248, 0.32);
      box-shadow: 0 10px 24px rgba(0, 0, 0, 0.42);
    }
    .iu-floating-color-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 6px;
      font-size: 0.71rem;
      color: var(--ts-muted);
    }
    .iu-floating-color-title {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      min-width: 0;
      flex: 1;
      cursor: pointer;
      user-select: none;
    }
    .iu-floating-color-actions {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      flex-shrink: 0;
    }
    .iu-floating-color-body {
      margin-top: 8px;
      max-height: 220px;
      opacity: 1;
      overflow-y: auto;
      transition: max-height 0.2s ease, opacity 0.16s ease, margin-top 0.16s ease;
    }
    .iu-floating-color-dock.is-collapsed .iu-floating-color-body {
      max-height: 0;
      opacity: 0;
      margin-top: 0;
      overflow: hidden;
      pointer-events: none;
    }
    .iu-target-banner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 6px;
      font-size: 0.71rem;
      color: var(--ts-muted);
      margin-bottom: 6px;
      padding: 4px 8px;
      border-radius: 7px;
      background: rgba(15, 23, 42, 0.48);
      border: 1px solid var(--ts-border);
    }
    .iu-token-field {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .iu-token-field label {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 4px;
      font-size: 0.7rem;
      color: var(--ts-muted);
    }
    .iu-local-reset-btn {
      display: none;
      border: none;
      background: rgba(56, 189, 248, 0.16);
      color: var(--ts-primary, #38bdf8);
      border-radius: 4px;
      padding: 0 5px;
      font-size: 0.65rem;
      font-weight: 700;
      cursor: pointer;
      line-height: 1.4;
    }
    .iu-token-field.has-local-override .iu-local-reset-btn,
    .ts-field.has-local-override .iu-local-reset-btn {
      display: inline-block;
    }
    .iu-token-field.has-local-override .iu-token-control input[type="text"],
    .ts-field.has-local-override input,
    .ts-field.has-local-override select {
      border-color: rgba(56, 189, 248, 0.55);
    }
    .iu-token-control {
      display: flex;
      align-items: center;
      gap: 4px;
    }
    .iu-token-control input[type="text"],
    .iu-token-control select {
      flex: 1;
      min-width: 0;
    }
    .iu-token-control input.iu-token-focused {
      border-color: var(--ts-primary, #38bdf8) !important;
      box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.25);
    }
    .iu-quick-chip-btn {
      border: 1px solid var(--ts-border);
      background: rgba(15, 23, 42, 0.65);
      color: var(--ts-muted);
      border-radius: 5px;
      padding: 2px 6px;
      font-size: 0.66rem;
      cursor: pointer;
      white-space: nowrap;
    }
    .iu-quick-chip-btn:hover {
      border-color: var(--ts-primary);
      color: var(--ts-text);
    }
    .iu-token-picker {
      width: 28px;
      height: 28px;
      padding: 1px;
      border: 1px solid var(--ts-border);
      border-radius: 6px;
      background: transparent;
      cursor: pointer;
      flex-shrink: 0;
    }
    .iu-preset-row {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 5px;
      margin-bottom: 10px;
    }
    .iu-preset-btn {
      padding: 6px 4px;
      font-size: 0.7rem;
      font-weight: 600;
      border-radius: 7px;
      border: 1px solid var(--ts-border);
      background: rgba(15, 23, 42, 0.55);
      color: var(--ts-text);
      cursor: pointer;
      text-align: center;
      transition: all 0.14s ease;
    }
    .iu-preset-btn:hover {
      border-color: var(--ts-primary);
    }
    .iu-preset-btn.active {
      background: rgba(56, 189, 248, 0.2);
      border-color: var(--ts-primary);
      color: var(--ts-primary);
    }
    .iu-code-options {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      font-size: 0.72rem;
      color: var(--ts-muted);
    }
    .iu-code-options label {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      cursor: pointer;
      user-select: none;
    }

    /* Visual JIT Class Assistant */
    .iu-jit-input-wrap {
      display: flex;
      align-items: center;
      gap: 5px;
    }
    .iu-jit-input-wrap input {
      flex: 1;
      min-width: 0;
    }
    .iu-jit-trigger-btn {
      border: 1px solid var(--ts-border);
      background: rgba(56, 189, 248, 0.14);
      color: var(--ts-primary);
      border-radius: 6px;
      padding: 5px 8px;
      font-size: 0.7rem;
      font-weight: 700;
      cursor: pointer;
      white-space: nowrap;
      flex-shrink: 0;
    }
    .iu-jit-trigger-btn:hover {
      background: rgba(56, 189, 248, 0.25);
      border-color: var(--ts-primary);
    }
    .iu-jit-popover {
      margin-top: 6px;
      padding: 8px;
      border-radius: 8px;
      border: 1px solid var(--ts-border);
      background: rgba(15, 23, 42, 0.96);
      display: flex;
      flex-direction: column;
      gap: 8px;
      max-height: 240px;
      overflow-y: auto;
    }
    .iu-jit-cat-title {
      font-size: 0.66rem;
      font-weight: 700;
      color: var(--ts-muted);
      text-transform: uppercase;
      letter-spacing: 0.04em;
      margin-bottom: 4px;
    }
    .iu-jit-chip-list {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
    }
    .iu-jit-chip {
      border: 1px solid var(--ts-border);
      background: rgba(30, 41, 59, 0.7);
      color: var(--ts-text);
      border-radius: 5px;
      padding: 2px 6px;
      font-size: 0.68rem;
      font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
      cursor: pointer;
    }
    .iu-jit-chip:hover {
      border-color: var(--ts-primary);
    }
    .iu-jit-chip.active {
      background: var(--ts-primary);
      color: #0f172a;
      border-color: var(--ts-primary);
      font-weight: 700;
    }

    /* Visual Form Builder */
    .fb-stage-wrap {
      width: 100%;
      max-width: 980px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      gap: 14px;
    }
    .fb-templates-bar,
    .fb-palette-bar {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 6px;
      padding: 10px 12px;
      border-radius: 12px;
      background: rgba(15, 23, 42, 0.72);
      border: 1px solid var(--ts-border);
    }
    .fb-bar-label {
      font-size: 0.72rem;
      font-weight: 700;
      color: var(--ts-muted);
      margin-right: 4px;
    }
    .fb-template-btn,
    .fb-palette-btn {
      border: 1px solid var(--ts-border);
      background: rgba(30, 41, 59, 0.78);
      color: var(--ts-text);
      border-radius: 7px;
      padding: 4px 9px;
      font-size: 0.72rem;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      transition: border-color 0.14s ease, background 0.14s ease;
    }
    .fb-template-btn:hover,
    .fb-palette-btn:hover {
      border-color: var(--ts-primary);
      background: rgba(56, 189, 248, 0.15);
    }
    .fb-palette-btn.is-step {
      border-color: rgba(56, 189, 248, 0.5);
      color: var(--ts-primary);
      font-weight: 700;
    }
    .fb-stage-grid {
      display: grid;
      grid-template-columns: 280px 1fr;
      gap: 14px;
      align-items: start;
    }
    @media (max-width: 920px) {
      .fb-stage-grid {
        grid-template-columns: 1fr;
      }
    }
    .fb-structure-card,
    .fb-stage-card {
      border-radius: 14px;
      border: 1px solid var(--ts-border);
      background: rgba(15, 23, 42, 0.55);
      padding: 14px;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .fb-stage-card-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      font-size: 0.78rem;
      font-weight: 700;
      color: var(--ts-text);
      border-bottom: 1px solid var(--ts-border);
      padding-bottom: 8px;
    }
    .fb-step-tabs {
      display: flex;
      flex-wrap: wrap;
      gap: 5px;
    }
    .fb-step-tab {
      border: 1px solid var(--ts-border);
      background: rgba(30, 41, 59, 0.7);
      color: var(--ts-muted);
      border-radius: 6px;
      padding: 4px 8px;
      font-size: 0.71rem;
      cursor: pointer;
    }
    .fb-step-tab.active {
      background: rgba(56, 189, 248, 0.22);
      border-color: var(--ts-primary);
      color: var(--ts-primary);
      font-weight: 700;
    }
    .fb-fields-list {
      display: flex;
      flex-direction: column;
      gap: 6px;
      max-height: 460px;
      overflow-y: auto;
    }
    .fb-field-item {
      border: 1px solid var(--ts-border);
      border-radius: 9px;
      background: rgba(30, 41, 59, 0.6);
      padding: 8px 10px;
      cursor: pointer;
      display: flex;
      flex-direction: column;
      gap: 5px;
      transition: border-color 0.14s ease, background 0.14s ease;
    }
    .fb-field-item:hover {
      border-color: rgba(56, 189, 248, 0.5);
    }
    .fb-field-item.is-selected {
      border-color: var(--ts-primary);
      background: rgba(56, 189, 248, 0.14);
      box-shadow: 0 0 0 1px rgba(56, 189, 248, 0.35);
    }
    .fb-field-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 6px;
      font-size: 0.74rem;
      font-weight: 600;
      color: var(--ts-text);
    }
    .fb-field-actions-row {
      display: flex;
      align-items: center;
      gap: 3px;
    }
    .fb-mini-act {
      border: 1px solid var(--ts-border);
      background: rgba(15, 23, 42, 0.75);
      color: var(--ts-muted);
      border-radius: 4px;
      padding: 1px 5px;
      font-size: 0.66rem;
      cursor: pointer;
    }
    .fb-mini-act:hover {
      color: var(--ts-text);
      border-color: var(--ts-primary);
    }
    .fb-mini-act.danger:hover {
      color: #f87171;
      border-color: #f87171;
    }
    .fb-inspector-box {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .fb-empty-state {
      padding: 24px;
      border-radius: 10px;
      border: 1px dashed var(--ts-border);
      text-align: center;
      font-size: 0.78rem;
      color: var(--ts-muted);
    }
    .fb-preview-host {
      padding: 8px;
      border-radius: 10px;
      background: var(--body);
    }
  </style>
  <style id="ts-charts-core-css"><?= $chartsCss ?? '' ?></style>
  <style id="ts-inputs-core-css"><?= $inputsCss ?? '' ?></style>
  <style id="tsJitLivePreviewCss"><?= $initialJitPreviewCss ?? '' ?></style>
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
        <button type="button" class="ts-tab-btn active" data-tab-id="themes" onclick="switchTab('themes', this)">🏷️ Temas</button>
        <button type="button" class="ts-tab-btn" data-tab-id="colors" onclick="switchTab('colors', this)">🎨 Colores</button>
        <button type="button" class="ts-tab-btn" data-tab-id="dark" onclick="switchTab('dark', this)">🌙 Oscuro</button>
        <button type="button" class="ts-tab-btn" data-tab-id="layout" onclick="switchTab('layout', this)">🔤 Layout</button>
        <button type="button" class="ts-tab-btn" id="tabBtnCharts" data-tab-id="charts" onclick="switchTab('charts', this)">📊 Gráficos</button>
        <button type="button" class="ts-tab-btn" id="tabBtnInputs" data-tab-id="inputs" onclick="switchTab('inputs', this)">🎛️ Inputs</button>
        <button type="button" class="ts-tab-btn" id="tabBtnIcons" data-tab-id="icons" onclick="switchTab('icons', this)">🖼️ Iconos</button>
        <button type="button" class="ts-tab-btn" data-tab-id="wcag" onclick="switchTab('wcag', this)">♿ WCAG</button>
        <button type="button" class="ts-tab-btn" data-tab-id="gradients" onclick="switchTab('gradients', this)">🌈 Gradientes</button>
        <button type="button" class="ts-tab-btn" id="tabBtnJsModules" data-tab-id="jsmodules" onclick="switchTab('jsmodules', this)">📦 Módulos JS</button>
        <button type="button" class="ts-tab-btn" id="tabBtnConfig" data-tab-id="config" onclick="switchTab('config', this)">⚙️ Config</button>
        <button type="button" class="ts-tab-btn" data-tab-id="json" onclick="switchTab('json', this)">📋 JSON</button>
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

        <div class="ts-section">
          <div class="ts-section-title">
            <span>📱 Media Queries y Pantallas (querie.js)</span>
          </div>
          <p style="font-size:0.74rem; color:var(--ts-muted); margin-bottom:10px; line-height:1.4;">
            Centraliza los puntos de quiebre del proyecto. Cada pantalla habilita automáticamente <code>.no-{slug}</code> (controlada por <strong>querie.js</strong>) y <code>.no-{slug}-css</code> (solo CSS).
          </p>
          <div id="mediaQueriesList">
            <!-- Renderizado dinámico vía JS -->
          </div>
          <button type="button" class="ts-btn-add-color" onclick="addMediaQuerySlot()">
            ➕ Agregar Pantalla / Media Query
          </button>
          <button type="button" class="ts-btn ts-btn-secondary" style="width:100%; justify-content:center; margin-top:8px; padding:7px; font-size:0.78rem;" onclick="resetDefaultMediaQueries()">
            🔄 Restaurar Media Queries por defecto
          </button>
        </div>
      </div>

      <!-- TAB 5.5: CREADOR Y CONFIGURADOR DE GRÁFICOS (CHART STUDIO) -->
      <div id="tab-charts" class="ts-tab-content">
        <!-- Bloque 1: Alcance y Selector de Gráfico -->
        <div class="ts-section">
          <div class="ts-section-title">
            <span>1. Alcance y Tipo de Gráfico</span>
            <span id="csScopeInfo" class="cs-local-badge is-global">Global</span>
          </div>
          <div class="cs-scope-bar">
            <button type="button" class="cs-scope-btn active" id="btnScopeGlobal" onclick="setChartScope('global')">
              🌍 Global (configStyle)
            </button>
            <button type="button" class="cs-scope-btn" id="btnScopeLocal" onclick="setChartScope('local')">
              🎯 Individual (Local)
            </button>
          </div>
          <div id="csLocalActions" style="display:none; margin-bottom:10px;">
            <button type="button" class="ts-btn ts-btn-secondary" style="width:100%; justify-content:center; padding:5px 8px; font-size:0.74rem;" onclick="clearActiveChartLocalOverrides()">
              ↺ Restablecer overrides locales de este gráfico
            </button>
          </div>
          <div id="csChartTypeGrid" class="cs-chart-type-grid">
            <!-- Generado dinámicamente vía JS -->
          </div>
        </div>

        <!-- Bloque 2: Colores del Gráfico (Vinculados a la Paleta del Tema) -->
        <div class="ts-section">
          <div class="ts-section-title">
            <span>2. Colores del Gráfico</span>
            <span style="font-size:0.7rem; color:var(--ts-muted);">Paleta viva</span>
          </div>

          <div class="cs-color-control">
            <div class="cs-color-header">
              <span>Color Base (<code>color</code>)</span>
              <span id="csColorBadge_color" class="cs-local-badge is-global">Global</span>
            </div>
            <div class="cs-color-chips" id="csColorChips_color"></div>
            <div class="cs-color-input-row">
              <input type="color" id="csColorPicker_color" class="ts-color-swatch-input" oninput="onChartColorInput('color', this.value)" title="Elegir HEX">
              <input type="text" id="csColorInput_color" class="ts-input-text" placeholder="texto, color1, var(--back-color1), #38bdf8" oninput="onChartColorInput('color', this.value)">
            </div>
          </div>

          <div class="cs-color-control">
            <div class="cs-color-header">
              <span>Etiquetas y Centro (<code>colorLabel</code>)</span>
              <span id="csColorBadge_colorLabel" class="cs-local-badge is-global">Global</span>
            </div>
            <div class="cs-color-chips" id="csColorChips_colorLabel"></div>
            <div class="cs-color-input-row">
              <input type="color" id="csColorPicker_colorLabel" class="ts-color-swatch-input" oninput="onChartColorInput('colorLabel', this.value)" title="Elegir HEX">
              <input type="text" id="csColorInput_colorLabel" class="ts-input-text" placeholder="textw, texto, color2, #ffffff" oninput="onChartColorInput('colorLabel', this.value)">
            </div>
          </div>

          <div class="cs-color-control">
            <div class="cs-color-header">
              <span>Ejes X / Y (<code>axisLabel</code>)</span>
              <span id="csColorBadge_axisLabel" class="cs-local-badge is-global">Global</span>
            </div>
            <div class="cs-color-chips" id="csColorChips_axisLabel"></div>
            <div class="cs-color-input-row">
              <input type="color" id="csColorPicker_axisLabel" class="ts-color-swatch-input" oninput="onChartColorInput('axisLabel', this.value)" title="Elegir HEX">
              <input type="text" id="csColorInput_axisLabel" class="ts-input-text" placeholder="texto, textc, color1, #94a3b8" oninput="onChartColorInput('axisLabel', this.value)">
            </div>
          </div>

          <div class="cs-color-control" id="csTargetColorGroup" style="display:none;">
            <div class="cs-color-header">
              <span>Marcador Meta (<code>targetColor</code>)</span>
              <span class="cs-local-badge is-local">Local</span>
            </div>
            <div class="cs-color-chips" id="csColorChips_targetColor"></div>
            <div class="cs-color-input-row">
              <input type="color" id="csColorPicker_targetColor" class="ts-color-swatch-input" oninput="onChartLocalColorInput('targetColor', this.value)" title="Elegir HEX">
              <input type="text" id="csColorInput_targetColor" class="ts-input-text" placeholder="#10b981" oninput="onChartLocalColorInput('targetColor', this.value)">
            </div>
          </div>
        </div>

        <!-- Bloque 3: Medidas Exactas (Pixel-Perfect) y Contextuales -->
        <div class="ts-section">
          <div class="ts-section-title">
            <span>3. Medidas Exactas (Pixel-Perfect)</span>
            <span style="font-size:0.7rem; color:var(--ts-muted);">px exactos</span>
          </div>

          <div class="cs-measure-row">
            <div class="cs-measure-top">
              <span>Grosor de Barra (<code>barWidth</code>)</span>
              <span id="csBarWidthBadge" class="cs-local-badge is-global">Global</span>
            </div>
            <div class="cs-measure-controls">
              <input type="range" id="csBarWidthRange" min="4" max="60" step="1" value="18" class="ts-range-input" oninput="onChartScopedMeasure('barWidth', this.value)">
              <input type="number" id="csBarWidthNum" min="4" max="60" placeholder="Auto" class="cs-num-input" oninput="onChartScopedMeasure('barWidth', this.value)">
              <button type="button" id="csBarWidthAuto" class="cs-auto-btn active" onclick="onChartScopedMeasure('barWidth', null)">Auto</button>
            </div>
          </div>

          <div class="cs-measure-row">
            <div class="cs-measure-top">
              <span>Tamaño de Etiquetas (<code>axisLabelSize</code>)</span>
              <span id="csAxisSizeBadge" class="cs-local-badge is-global">Global</span>
            </div>
            <div class="cs-measure-controls">
              <input type="range" id="csAxisSizeRange" min="8" max="24" step="1" value="12" class="ts-range-input" oninput="onChartScopedMeasure('axisLabelSize', this.value)">
              <input type="number" id="csAxisSizeNum" min="8" max="24" placeholder="Auto" class="cs-num-input" oninput="onChartScopedMeasure('axisLabelSize', this.value)">
              <button type="button" id="csAxisSizeAuto" class="cs-auto-btn active" onclick="onChartScopedMeasure('axisLabelSize', null)">Auto</button>
            </div>
          </div>

          <div class="cs-measure-row" id="csRxWrapper">
            <div class="cs-measure-top">
              <span>Radio Esquinas (<code>rx</code>)</span>
              <span class="cs-local-badge is-local">Local</span>
            </div>
            <div class="cs-measure-controls">
              <input type="range" id="csRxRange" min="0" max="30" step="1" value="9" class="ts-range-input" oninput="onChartLocalParam('rx', Number(this.value))">
              <input type="number" id="csRxNum" min="0" max="30" placeholder="Auto" class="cs-num-input" oninput="onChartLocalParam('rx', this.value === '' ? null : Number(this.value))">
              <button type="button" id="csRxAuto" class="cs-auto-btn active" onclick="onChartLocalParam('rx', null)">Auto</button>
            </div>
          </div>

          <div class="cs-measure-row" id="csDimensionsWrapper">
            <div class="cs-measure-top">
              <span>Dimensiones ViewBox (<code>width</code> / <code>height</code>)</span>
              <span id="csDimBadge" class="cs-local-badge is-global">Global</span>
            </div>
            <div class="ts-mq-grid" style="margin-bottom:0;">
              <div class="ts-mq-field">
                <label>Ancho (width)</label>
                <div style="display:flex; gap:4px;">
                  <input type="number" id="csWidthNum" min="100" max="1200" placeholder="Auto" class="cs-num-input" style="flex:1;" oninput="onChartScopedMeasure('width', this.value)">
                  <button type="button" id="csWidthAuto" class="cs-auto-btn active" onclick="onChartScopedMeasure('width', null)">Auto</button>
                </div>
              </div>
              <div class="ts-mq-field">
                <label>Alto (height)</label>
                <div style="display:flex; gap:4px;">
                  <input type="number" id="csHeightNum" min="60" max="900" placeholder="Auto" class="cs-num-input" style="flex:1;" oninput="onChartScopedMeasure('height', this.value)">
                  <button type="button" id="csHeightAuto" class="cs-auto-btn active" onclick="onChartScopedMeasure('height', null)">Auto</button>
                </div>
              </div>
            </div>
          </div>

          <!-- Controles Contextuales por Tipo de Gráfico -->
          <div id="csContextualFields">
            <!-- arcMeter -->
            <div id="csCtxArcMeter" class="cs-measure-row" style="display:none;">
              <div class="cs-measure-top">
                <span>⚙️ Arc Meter</span>
                <span class="cs-local-badge is-local">Local</span>
              </div>
              <div class="ts-field" style="margin-bottom:8px;">
                <label>Valor (<code>value</code> %)</label>
                <div class="cs-measure-controls">
                  <input type="range" id="csArcValueRange" min="0" max="100" step="1" class="ts-range-input" oninput="onChartLocalParam('value', Number(this.value))">
                  <input type="number" id="csArcValueNum" min="0" max="100" class="cs-num-input" oninput="onChartLocalParam('value', Number(this.value))">
                </div>
              </div>
              <div class="ts-field" style="margin-bottom:6px;">
                <label>Etiqueta Central (<code>label</code>)</label>
                <input type="text" id="csArcLabel" class="ts-input-text" placeholder="Optimal Load" oninput="onChartLocalParam('label', this.value)">
              </div>
              <div class="cs-check-grid">
                <label class="cs-check-item"><input type="checkbox" id="csArcShowNumber" checked onchange="onChartLocalParam('showNumber', this.checked)"> showNumber</label>
                <label class="cs-check-item"><input type="checkbox" id="csArcShowLabel" checked onchange="onChartLocalParam('showLabel', this.checked)"> showLabel</label>
              </div>
            </div>

            <!-- pillPillars -->
            <div id="csCtxPillPillars" class="cs-measure-row" style="display:none;">
              <div class="cs-measure-top">
                <span>⚙️ Pill Pillars (<code>pillarGap</code>)</span>
                <span class="cs-local-badge is-local">Local</span>
              </div>
              <div class="cs-measure-controls">
                <input type="range" id="csPillarGapRange" min="1" max="20" step="1" value="4" class="ts-range-input" oninput="onChartLocalParam('pillarGap', Number(this.value))">
                <input type="number" id="csPillarGapNum" min="1" max="20" placeholder="Auto" class="cs-num-input" oninput="onChartLocalParam('pillarGap', this.value === '' ? null : Number(this.value))">
                <button type="button" id="csPillarGapAuto" class="cs-auto-btn active" onclick="onChartLocalParam('pillarGap', null)">Auto</button>
              </div>
            </div>

            <!-- pyramidStack -->
            <div id="csCtxPyramidStack" class="cs-measure-row" style="display:none;">
              <div class="cs-measure-top">
                <span>⚙️ Pyramid Stack</span>
                <span class="cs-local-badge is-local">Local</span>
              </div>
              <div class="ts-field" style="margin-bottom:8px;">
                <label>Espacio Vertical (<code>gapY</code>)</label>
                <div class="cs-measure-controls">
                  <input type="range" id="csPyramidGapYRange" min="0" max="24" step="1" value="9" class="ts-range-input" oninput="onChartLocalParam('gapY', Number(this.value))">
                  <input type="number" id="csPyramidGapYNum" min="0" max="24" class="cs-num-input" oninput="onChartLocalParam('gapY', this.value === '' ? null : Number(this.value))">
                </div>
              </div>
              <div class="ts-mq-grid" style="margin-bottom:0;">
                <div class="ts-mq-field">
                  <label>minWidth (px)</label>
                  <input type="number" id="csPyramidMinW" min="30" max="220" placeholder="84" class="ts-mq-input" oninput="onChartLocalParam('minWidth', this.value === '' ? null : Number(this.value))">
                </div>
                <div class="ts-mq-field">
                  <label>maxWidth (px)</label>
                  <input type="number" id="csPyramidMaxW" min="100" max="310" placeholder="254" class="ts-mq-input" oninput="onChartLocalParam('maxWidth', this.value === '' ? null : Number(this.value))">
                </div>
              </div>
            </div>

            <!-- matrixHeatmap -->
            <div id="csCtxMatrixHeatmap" class="cs-measure-row" style="display:none;">
              <div class="cs-measure-top">
                <span>⚙️ Matrix Heatmap</span>
                <span class="cs-local-badge is-local">Local</span>
              </div>
              <div class="ts-field" style="margin-bottom:8px;">
                <label>Tamaño Celda (<code>cellSize</code>)</label>
                <div class="cs-measure-controls">
                  <input type="range" id="csMatrixCellSizeRange" min="10" max="42" step="1" value="24" class="ts-range-input" oninput="onChartLocalParam('cellSize', Number(this.value))">
                  <input type="number" id="csMatrixCellSizeNum" min="10" max="42" placeholder="Auto" class="cs-num-input" oninput="onChartLocalParam('cellSize', this.value === '' ? null : Number(this.value))">
                  <button type="button" id="csMatrixCellSizeAuto" class="cs-auto-btn active" onclick="onChartLocalParam('cellSize', null)">Auto</button>
                </div>
              </div>
              <div class="ts-field" style="margin-bottom:8px;">
                <label>Separación Celdas (<code>cellGap</code>)</label>
                <div class="cs-measure-controls">
                  <input type="range" id="csMatrixCellGapRange" min="1" max="16" step="1" value="6" class="ts-range-input" oninput="onChartLocalParam('cellGap', Number(this.value))">
                  <input type="number" id="csMatrixCellGapNum" min="1" max="16" placeholder="Auto" class="cs-num-input" oninput="onChartLocalParam('cellGap', this.value === '' ? null : Number(this.value))">
                  <button type="button" id="csMatrixCellGapAuto" class="cs-auto-btn active" onclick="onChartLocalParam('cellGap', null)">Auto</button>
                </div>
              </div>
              <div class="ts-mq-grid" style="margin-bottom:0; align-items:end;">
                <div class="ts-mq-field">
                  <label>Posición Eje X (<code>colLabelPosition</code>)</label>
                  <select id="csMatrixColPos" class="ts-select" style="padding:5px 7px; font-size:0.78rem;" onchange="onChartLocalParam('colLabelPosition', this.value)">
                    <option value="bottom">bottom (Inferior)</option>
                    <option value="top">top (Superior)</option>
                  </select>
                </div>
                <label class="cs-check-item" style="padding-bottom:6px;">
                  <input type="checkbox" id="csMatrixInterpolate" onchange="onChartLocalParam('interpolate', this.checked)"> Suavizado continuo
                </label>
              </div>
            </div>

            <!-- hybridSpline / splineDynamics tension -->
            <div id="csCtxTension" class="cs-measure-row" style="display:none;">
              <div class="cs-measure-top">
                <span>⚙️ Curvatura Spline (<code>tension</code>)</span>
                <span class="cs-local-badge is-local">Local</span>
              </div>
              <div class="cs-measure-controls">
                <input type="range" id="csTensionRange" min="0" max="0.5" step="0.05" value="0.25" class="ts-range-input" oninput="onChartLocalParam('tension', Number(this.value))">
                <input type="number" id="csTensionNum" min="0" max="0.5" step="0.05" class="cs-num-input" oninput="onChartLocalParam('tension', this.value === '' ? null : Number(this.value))">
              </div>
            </div>

            <!-- splineDynamics toggles -->
            <div id="csCtxSplineDyn" class="cs-measure-row" style="display:none;">
              <div class="cs-measure-top">
                <span>⚙️ Elementos Spline Dynamics</span>
                <span class="cs-local-badge is-local">Local</span>
              </div>
              <div class="cs-check-grid" style="margin-top:0;">
                <label class="cs-check-item"><input type="checkbox" id="csSplineShowSecondary" checked onchange="onChartLocalParam('showSecondary', this.checked)"> Serie secundaria</label>
                <label class="cs-check-item"><input type="checkbox" id="csSplineShowPoints" checked onchange="onChartLocalParam('showPoints', this.checked)"> Puntos / Nodos</label>
              </div>
            </div>

            <!-- roundedDonut -->
            <div id="csCtxDonut" class="cs-measure-row" style="display:none;">
              <div class="cs-measure-top">
                <span>⚙️ Rounded Donut</span>
                <span class="cs-local-badge is-local">Local</span>
              </div>
              <div class="ts-field" style="margin-bottom:8px;">
                <label>Ángulo Inicial (<code>startAngle</code>°)</label>
                <div class="cs-measure-controls">
                  <input type="range" id="csDonutStartAngleRange" min="-180" max="180" step="5" value="-135" class="ts-range-input" oninput="onChartLocalParam('startAngle', Number(this.value))">
                  <input type="number" id="csDonutStartAngleNum" min="-180" max="180" class="cs-num-input" oninput="onChartLocalParam('startAngle', this.value === '' ? null : Number(this.value))">
                </div>
              </div>
              <div class="ts-mq-grid" style="margin-bottom:6px;">
                <div class="ts-mq-field">
                  <label>centerValue</label>
                  <input type="text" id="csDonutCenterValue" placeholder="Auto (100%)" class="ts-mq-input" oninput="onChartLocalParam('centerValue', this.value)">
                </div>
                <div class="ts-mq-field">
                  <label>centerLabel</label>
                  <input type="text" id="csDonutCenterLabel" placeholder="Mono Arc" class="ts-mq-input" oninput="onChartLocalParam('centerLabel', this.value)">
                </div>
              </div>
              <div class="cs-check-grid">
                <label class="cs-check-item"><input type="checkbox" id="csDonutShowCenter" checked onchange="onChartLocalParam('showCenter', this.checked)"> showCenter</label>
                <label class="cs-check-item"><input type="checkbox" id="csDonutShowLegend" checked onchange="onChartLocalParam('showLegend', this.checked)"> showLegend</label>
              </div>
            </div>

            <!-- tileTreemap -->
            <div id="csCtxTreemap" class="cs-measure-row" style="display:none;">
              <div class="cs-measure-top">
                <span>⚙️ Tile Treemap (<code>gap</code>)</span>
                <span class="cs-local-badge is-local">Local</span>
              </div>
              <div class="cs-measure-controls">
                <input type="range" id="csTreemapGapRange" min="2" max="20" step="1" value="8" class="ts-range-input" oninput="onChartLocalParam('gap', Number(this.value))">
                <input type="number" id="csTreemapGapNum" min="2" max="20" class="cs-num-input" oninput="onChartLocalParam('gap', this.value === '' ? null : Number(this.value))">
              </div>
            </div>

            <!-- Ejes Y / X y Sufijo -->
            <div id="csCtxAxes" class="cs-measure-row" style="display:none;">
              <div class="cs-measure-top">
                <span>⚙️ Ejes y Formato</span>
                <span class="cs-local-badge is-local">Local</span>
              </div>
              <div class="cs-check-grid" style="margin-top:0; margin-bottom:8px;">
                <label class="cs-check-item" id="csShowAxisYWrap"><input type="checkbox" id="csShowAxisY" checked onchange="onChartLocalParam('showAxisY', this.checked)"> showAxisY</label>
                <label class="cs-check-item" id="csShowAxisXWrap"><input type="checkbox" id="csShowAxisX" checked onchange="onChartLocalParam('showAxisX', this.checked)"> showAxisX</label>
              </div>
              <div id="csValueSuffixWrap" class="ts-field" style="margin-bottom:0;">
                <label>Sufijo de valor (<code>valueSuffix</code>)</label>
                <input type="text" id="csValueSuffix" class="ts-input-text" placeholder="ej. %, h, hrs, ms" oninput="onChartLocalParam('valueSuffix', this.value)">
              </div>
            </div>
          </div>
        </div>

        <!-- Bloque 4: Constructor Visual yLabelPosition -->
        <div class="ts-section">
          <div class="ts-section-title">
            <span>4. Disposición y Eje Y (<code>yLabelPosition</code>)</span>
            <span id="csYPosBadge" class="cs-local-badge is-global">Global</span>
          </div>

          <div class="cs-measure-row">
            <div class="ts-field" style="margin-bottom:10px;">
              <label>Alineación de Fila (<code>row</code>)</label>
              <div class="cs-seg-grid" id="csYPosRowGrid">
                <button type="button" class="cs-seg-btn active" data-row="default" onclick="setYPosRow('default')">default</button>
                <button type="button" class="cs-seg-btn" data-row="between" onclick="setYPosRow('between')">between</button>
                <button type="button" class="cs-seg-btn" data-row="around" onclick="setYPosRow('around')">around</button>
                <button type="button" class="cs-seg-btn" data-row="start" onclick="setYPosRow('start')">start</button>
                <button type="button" class="cs-seg-btn" data-row="center" onclick="setYPosRow('center')">center</button>
                <button type="button" class="cs-seg-btn" data-row="end" onclick="setYPosRow('end')">end</button>
              </div>
            </div>

            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.8rem; font-weight:600; margin-bottom:8px;">
              <input type="checkbox" id="csYPosHasSplit" onchange="setYPosHasSplit(this.checked)">
              Activar reparto porcentual (<code>labelPct / chartPct</code>)
            </label>

            <div id="csYPosSplitControls" style="display:none; margin-bottom:10px;">
              <div class="ts-field" style="margin-bottom:7px;">
                <label>% Nombres (<code>labelPct</code>)</label>
                <div class="cs-measure-controls">
                  <input type="range" id="csYPosLabelPctRange" min="10" max="85" step="1" value="50" class="ts-range-input" oninput="setYPosSplitPct('label', this.value)">
                  <input type="number" id="csYPosLabelPctNum" min="10" max="85" value="50" class="cs-num-input" oninput="setYPosSplitPct('label', this.value)">
                </div>
              </div>
              <div class="ts-field" style="margin-bottom:6px;">
                <label>% Barras / Gráfico (<code>chartPct</code>)</label>
                <div class="cs-measure-controls">
                  <input type="range" id="csYPosChartPctRange" min="15" max="90" step="1" value="40" class="ts-range-input" oninput="setYPosSplitPct('chart', this.value)">
                  <input type="number" id="csYPosChartPctNum" min="15" max="90" value="40" class="cs-num-input" oninput="setYPosSplitPct('chart', this.value)">
                </div>
              </div>
              <div class="cs-split-bar-preview" title="Distribución porcentual en vivo">
                <div id="csSplitSegLabel" class="cs-split-seg-label" style="width:50%;">50%</div>
                <div id="csSplitSegGap" class="cs-split-seg-gap" style="width:10%;">10%</div>
                <div id="csSplitSegChart" class="cs-split-seg-chart" style="width:40%;">40%</div>
              </div>
            </div>

            <div class="ts-field" style="margin-bottom:6px;">
              <label>Alineación del Texto (<code>align</code>)</label>
              <div class="cs-seg-grid" id="csYPosAlignGrid">
                <button type="button" class="cs-seg-btn active" data-align="start" onclick="setYPosAlign('start')">⬅️ start</button>
                <button type="button" class="cs-seg-btn" data-align="center" onclick="setYPosAlign('center')">↔️ center</button>
                <button type="button" class="cs-seg-btn" data-align="end" onclick="setYPosAlign('end')">➡️ end</button>
              </div>
            </div>

            <code id="csYPosSyntaxPreview" class="cs-syntax-badge">default</code>
          </div>
        </div>

        <!-- Bloque 5: Animación, Tooltips y Simulador de Datos -->
        <div class="ts-section">
          <div class="ts-section-title">
            <span>5. Animación, Tooltips y Datos</span>
          </div>

          <div class="cs-measure-row">
            <div class="cs-measure-top">
              <span>Transición (<code>transition</code> ms)</span>
              <span id="csTransitionBadge" class="cs-local-badge is-global">Global</span>
            </div>
            <div class="cs-measure-controls" style="margin-bottom:8px;">
              <input type="range" id="csTransitionRange" min="0" max="2000" step="50" value="400" class="ts-range-input" oninput="onChartScopedMeasure('transition', this.value, true)">
              <input type="number" id="csTransitionNum" min="0" max="2000" step="50" value="400" class="cs-num-input" oninput="onChartScopedMeasure('transition', this.value, true)">
            </div>
            <button type="button" class="ts-btn ts-btn-primary" style="width:100%; justify-content:center; padding:6px; font-size:0.78rem;" onclick="replayChartsAnimation()">
              ▶️ Probar Animación en Vivo
            </button>
          </div>

          <div class="cs-measure-row">
            <div class="cs-measure-top" style="margin-bottom:6px;">
              <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.8rem;">
                <input type="checkbox" id="csTooltipCheck" checked onchange="onChartScopedBool('tooltip', this.checked)">
                Habilitar Tooltips flotantes (<code>tooltip</code>)
              </label>
              <span id="csTooltipBadge" class="cs-local-badge is-global">Global</span>
            </div>
            <div class="ts-field" style="margin-bottom:0;">
              <label for="csDatasetPresetSelect">Simulador de Datos (<code>datasetPreset</code>)</label>
              <select id="csDatasetPresetSelect" class="ts-select" onchange="setChartDatasetPreset(this.value)">
                <option value="standard">Estándar (Vista por defecto)</option>
                <option value="compact">Pocos ítems (3 elementos)</option>
                <option value="dense">Muchos ítems (8-10 elementos)</option>
                <option value="long-labels">Etiquetas largas (Prueba de saltos)</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 5.5: UI / INPUT STUDIO (FormUiModule) -->
      <div id="tab-inputs" class="ts-tab-content">
        <div id="iuFormBuilderSidebarPanel" style="display:none;">
          <div class="ts-section">
            <div class="ts-section-title">
              <span>🧱 Diseñador de Formularios</span>
              <button type="button" class="ts-btn ts-btn-secondary" style="padding:3px 8px; font-size:0.7rem;" onclick="setInputSandboxViewMode('gallery')">
                ← Inputs (19)
              </button>
            </div>
            <div class="cs-mini-tabs">
              <button type="button" class="cs-mini-tab active" id="fbSubTab_form" onclick="switchFormBuilderInspectorTab('form')">📋 Configuración del Form</button>
              <button type="button" class="cs-mini-tab" id="fbSubTab_field" onclick="switchFormBuilderInspectorTab('field')">🎯 Campo Seleccionado</button>
            </div>
            <div id="fbSidebarInspectorHost" class="fb-inspector-box">
              <!-- Rellenado dinámicamente según el sub-tab activo del Form Builder -->
            </div>
          </div>
        </div>

        <div id="iuSingleInputSidebarWrap">
        <div class="ts-section">
          <div class="ts-section-title">
            <span>1. Presets de Diseño UI</span>
            <button type="button" class="ts-btn ts-btn-secondary" style="padding:3px 8px; font-size:0.72rem;" onclick="resetAllInputStyles()" title="Restaurar configuración por defecto de FormUiModule">
              ↺ Reset
            </button>
          </div>
          <div class="iu-preset-row" id="iuPresetRow">
            <button type="button" class="iu-preset-btn active" data-preset="soft" onclick="applyInputPreset('soft')">Suave</button>
            <button type="button" class="iu-preset-btn" data-preset="minimal" onclick="applyInputPreset('minimal')">Minimal</button>
            <button type="button" class="iu-preset-btn" data-preset="glass" onclick="applyInputPreset('glass')">Glass</button>
            <button type="button" class="iu-preset-btn" data-preset="pill" onclick="applyInputPreset('pill')">Pill</button>
            <button type="button" class="iu-preset-btn" data-preset="elevated" onclick="applyInputPreset('elevated')">Elevado</button>
          </div>
        </div>

        <div class="ts-section">
          <div class="ts-section-title">
            <span>2. Selecciona un Input (<span id="iuActiveTypeBadge">cropImage</span>)</span>
          </div>
          <div class="iu-type-cloud" id="iuTypeGrid">
            <!-- Rellenado dinámicamente por JS -->
          </div>
        </div>

        <div class="ts-section">
          <div class="cs-mini-tabs">
            <button type="button" class="cs-mini-tab active" id="iuSubTab_global" onclick="switchInputSubTab('global')">🌐 Global</button>
            <button type="button" class="cs-mini-tab" id="iuSubTab_local" onclick="switchInputSubTab('local')">🎯 Local (<span id="iuSubTabLocalBadge">cropImage</span>)</button>
            <button type="button" class="cs-mini-tab" id="iuSubTab_attrs" onclick="switchInputSubTab('attrs')">⚙️ Atributos</button>
          </div>

          <!-- Sub-panel compartido: Diseño y Colores (Global para todos o Local para el input activo) -->
          <div id="iuPanel_design" style="display:block;">
            <div id="iuScopeBanner" class="iu-scope-banner" style="display:none;">
              <span>🎯 Editando solo: <strong id="iuLocalTypeLabel">cropImage</strong></span>
              <button type="button" class="ts-btn ts-btn-secondary" style="padding:2px 8px; font-size:0.68rem;" onclick="clearActiveInputLocalOverrides()">↺ Limpiar local</button>
            </div>

            <div id="iuFloatingColorDock" class="iu-floating-color-dock">
              <div class="iu-floating-color-head">
                <div class="iu-floating-color-title" onclick="toggleIuColorChipsAccordion()" title="Mostrar u ocultar paleta de clases de color">
                  <span>🎨</span>
                  <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">Color a: <strong id="iuFocusedTokenLabel" style="color:var(--ts-primary);">Fondo Campo</strong></span>
                </div>
                <div class="iu-floating-color-actions">
                  <button type="button" class="iu-quick-chip-btn" onclick="applySystemColorToFocusedToken('transparent')" title="Quitar color o borde al campo activo">🚫 Sin color</button>
                  <button type="button" class="iu-quick-chip-btn" id="btnToggleIuColorDock" onclick="toggleIuColorChipsAccordion()" aria-expanded="true" title="Mostrar u ocultar clases de color">▾ Ocultar</button>
                </div>
              </div>
              <div class="iu-floating-color-body" id="iuColorChipsBody">
                <div class="cs-color-chips" id="iuColorChips" style="margin-bottom:0;"></div>
              </div>
            </div>

            <!-- Bloque 1: Campo / Disparador (Trigger) -->
            <div class="iu-group-box">
              <div class="iu-group-head"><span>1. Campo / Disparador</span></div>
              <div class="iu-color-pair">
                <div class="iu-token-field" data-iu-field-wrap="triggerBg">
                  <label for="iu_triggerBg"><span>Fondo Campo</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('triggerBg')" title="Volver al valor Global">↺</button></label>
                  <div class="iu-token-control">
                    <input type="text" id="iu_triggerBg" class="ts-input" data-iu-prop="triggerBg" placeholder="back1">
                    <input type="color" class="iu-token-picker" data-iu-picker-prop="triggerBg" title="Elegir color exacto">
                  </div>
                </div>
                <div class="iu-token-field" data-iu-field-wrap="triggerText">
                  <label for="iu_triggerText"><span>Texto Campo</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('triggerText')" title="Volver al valor Global">↺</button></label>
                  <div class="iu-token-control">
                    <input type="text" id="iu_triggerText" class="ts-input" data-iu-prop="triggerText" placeholder="texto">
                    <input type="color" class="iu-token-picker" data-iu-picker-prop="triggerText" title="Elegir color exacto">
                  </div>
                </div>
                <div class="iu-token-field" data-iu-field-wrap="surfaceMuted">
                  <label for="iu_surfaceMuted"><span>Texto Guía / Muted</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('surfaceMuted')" title="Volver al valor Global">↺</button></label>
                  <div class="iu-token-control">
                    <input type="text" id="iu_surfaceMuted" class="ts-input" data-iu-prop="surfaceMuted" placeholder="text-inactive">
                    <input type="color" class="iu-token-picker" data-iu-picker-prop="surfaceMuted" title="Elegir color exacto">
                  </div>
                </div>
                <div class="iu-token-field" data-iu-field-wrap="triggerBorder">
                  <label for="iu_triggerBorder"><span>Borde Campo</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('triggerBorder')" title="Volver al valor Global">↺</button></label>
                  <div class="iu-token-control">
                    <input type="text" id="iu_triggerBorder" class="ts-input" data-iu-prop="triggerBorder" placeholder="textc">
                    <input type="color" class="iu-token-picker" data-iu-picker-prop="triggerBorder" title="Elegir color exacto">
                  </div>
                </div>
              </div>
            </div>

            <!-- Bloque 1b: Placeholder y Textos Secundarios -->
            <div class="iu-group-box">
              <div class="iu-group-head"><span>1b. Placeholder y Textos Secundarios</span></div>
              <div class="iu-color-pair">
                <div class="iu-token-field" data-iu-field-wrap="placeholderColor">
                  <label for="iu_placeholderColor"><span>Color Placeholder</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('placeholderColor')" title="Volver al valor Global">↺</button></label>
                  <div class="iu-token-control">
                    <input type="text" id="iu_placeholderColor" class="ts-input" data-iu-prop="placeholderColor" placeholder="text-inactive">
                    <input type="color" class="iu-token-picker" data-iu-picker-prop="placeholderColor" title="Elegir color exacto">
                  </div>
                </div>
                <div class="iu-token-field" data-iu-field-wrap="secondaryText">
                  <label for="iu_secondaryText"><span>Texto Secundario</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('secondaryText')" title="Volver al valor Global">↺</button></label>
                  <div class="iu-token-control">
                    <input type="text" id="iu_secondaryText" class="ts-input" data-iu-prop="secondaryText" placeholder="text-inactive">
                    <input type="color" class="iu-token-picker" data-iu-picker-prop="secondaryText" title="Elegir color exacto">
                  </div>
                </div>
              </div>
              <div class="iu-color-pair">
                <div class="ts-field" data-iu-field-wrap="placeholderSize">
                  <label for="iu_placeholderSize"><span>Tam. Placeholder (<strong id="iu_placeholderSizeVal">14px</strong>)</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('placeholderSize')">↺</button></label>
                  <input type="range" id="iu_placeholderSize" data-iu-prop="placeholderSize" min="10" max="28" step="1" value="14">
                </div>
                <div class="ts-field" data-iu-field-wrap="secondaryFontSize">
                  <label for="iu_secondaryFontSize"><span>Tam. Secundario (<strong id="iu_secondaryFontSizeVal">12px</strong>)</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('secondaryFontSize')">↺</button></label>
                  <input type="range" id="iu_secondaryFontSize" data-iu-prop="secondaryFontSize" min="9" max="24" step="1" value="12">
                </div>
              </div>
            </div>

            <!-- Bloque 2: Modal / Popover Flotante -->
            <div class="iu-group-box">
              <div class="iu-group-head"><span>2. Modal / Popover Flotante</span></div>
              <div class="iu-color-pair">
                <div class="iu-token-field" data-iu-field-wrap="surfaceBg">
                  <label for="iu_surfaceBg"><span>Fondo Modal</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('surfaceBg')" title="Volver al valor Global">↺</button></label>
                  <div class="iu-token-control">
                    <input type="text" id="iu_surfaceBg" class="ts-input" data-iu-prop="surfaceBg" placeholder="back1">
                    <input type="color" class="iu-token-picker" data-iu-picker-prop="surfaceBg" title="Elegir color exacto">
                  </div>
                </div>
                <div class="iu-token-field" data-iu-field-wrap="surfaceText">
                  <label for="iu_surfaceText"><span>Texto Modal</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('surfaceText')" title="Volver al valor Global">↺</button></label>
                  <div class="iu-token-control">
                    <input type="text" id="iu_surfaceText" class="ts-input" data-iu-prop="surfaceText" placeholder="texto">
                    <input type="color" class="iu-token-picker" data-iu-picker-prop="surfaceText" title="Elegir color exacto">
                  </div>
                </div>
                <div class="iu-token-field" data-iu-field-wrap="surfaceBorder">
                  <label for="iu_surfaceBorder"><span>Borde Modal</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('surfaceBorder')" title="Volver al valor Global">↺</button></label>
                  <div class="iu-token-control">
                    <input type="text" id="iu_surfaceBorder" class="ts-input" data-iu-prop="surfaceBorder" placeholder="textc">
                    <input type="color" class="iu-token-picker" data-iu-picker-prop="surfaceBorder" title="Elegir color exacto">
                  </div>
                </div>
                <div class="iu-token-field" data-iu-field-wrap="focusRing">
                  <label for="iu_focusRing"><span>Color Foco / Ring</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('focusRing')" title="Volver al valor Global">↺</button></label>
                  <div class="iu-token-control">
                    <input type="text" id="iu_focusRing" class="ts-input" data-iu-prop="focusRing" placeholder="color2">
                    <input type="color" class="iu-token-picker" data-iu-picker-prop="focusRing" title="Elegir color exacto">
                  </div>
                </div>
              </div>
            </div>

            <!-- Bloque 3: Botón Guardar / Primario -->
            <div class="iu-group-box">
              <div class="iu-group-head">
                <span>3. Botón Guardar / Primario</span>
                <span id="iuPrimaryWcagBadge" class="iu-wcag-badge pass">7.2:1 AA</span>
              </div>
              <div class="iu-color-pair">
                <div class="iu-token-field" data-iu-field-wrap="btnPrimaryBg">
                  <label for="iu_btnPrimaryBg"><span>Fondo Botón</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('btnPrimaryBg')" title="Volver al valor Global">↺</button></label>
                  <div class="iu-token-control">
                    <input type="text" id="iu_btnPrimaryBg" class="ts-input" data-iu-prop="btnPrimaryBg" placeholder="back6">
                    <input type="color" class="iu-token-picker" data-iu-picker-prop="btnPrimaryBg" title="Elegir color exacto">
                  </div>
                </div>
                <div class="iu-token-field" data-iu-field-wrap="btnPrimaryText">
                  <label for="iu_btnPrimaryText"><span>Texto Botón</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('btnPrimaryText')" title="Volver al valor Global">↺</button></label>
                  <div class="iu-token-control">
                    <input type="text" id="iu_btnPrimaryText" class="ts-input" data-iu-prop="btnPrimaryText" placeholder="textw">
                    <input type="color" class="iu-token-picker" data-iu-picker-prop="btnPrimaryText" title="Elegir color exacto">
                  </div>
                </div>
              </div>
              <div class="iu-token-field" data-iu-field-wrap="btnPrimaryBorder">
                <label for="iu_btnPrimaryBorder"><span>Borde Botón Guardar</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('btnPrimaryBorder')" title="Volver al valor Global">↺</button></label>
                <div class="iu-token-control">
                  <input type="text" id="iu_btnPrimaryBorder" class="ts-input" data-iu-prop="btnPrimaryBorder" placeholder="transparent">
                  <input type="color" class="iu-token-picker" data-iu-picker-prop="btnPrimaryBorder" title="Elegir color exacto">
                  <button type="button" class="iu-quick-chip-btn" onclick="setInputDesignProp('btnPrimaryBorder', 'transparent')" title="Quitar borde">Sin borde</button>
                  <button type="button" class="iu-quick-chip-btn" onclick="autoFixInputButtonContrast('primary')" title="Ajustar contraste WCAG">✨ Auto</button>
                </div>
              </div>
            </div>

            <!-- Bloque 4: Botón Cancelar / Secundario -->
            <div class="iu-group-box">
              <div class="iu-group-head">
                <span>4. Botón Cancelar / Secundario</span>
                <span id="iuSecondaryWcagBadge" class="iu-wcag-badge pass">8.5:1 AA</span>
              </div>
              <div class="iu-color-pair">
                <div class="iu-token-field" data-iu-field-wrap="btnSecondaryBg">
                  <label for="iu_btnSecondaryBg"><span>Fondo Botón</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('btnSecondaryBg')" title="Volver al valor Global">↺</button></label>
                  <div class="iu-token-control">
                    <input type="text" id="iu_btnSecondaryBg" class="ts-input" data-iu-prop="btnSecondaryBg" placeholder="back2">
                    <input type="color" class="iu-token-picker" data-iu-picker-prop="btnSecondaryBg" title="Elegir color exacto">
                  </div>
                </div>
                <div class="iu-token-field" data-iu-field-wrap="btnSecondaryText">
                  <label for="iu_btnSecondaryText"><span>Texto Botón</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('btnSecondaryText')" title="Volver al valor Global">↺</button></label>
                  <div class="iu-token-control">
                    <input type="text" id="iu_btnSecondaryText" class="ts-input" data-iu-prop="btnSecondaryText" placeholder="texto">
                    <input type="color" class="iu-token-picker" data-iu-picker-prop="btnSecondaryText" title="Elegir color exacto">
                  </div>
                </div>
              </div>
              <div class="iu-token-field" data-iu-field-wrap="btnSecondaryBorder">
                <label for="iu_btnSecondaryBorder"><span>Borde Botón Cancelar</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('btnSecondaryBorder')" title="Volver al valor Global">↺</button></label>
                <div class="iu-token-control">
                  <input type="text" id="iu_btnSecondaryBorder" class="ts-input" data-iu-prop="btnSecondaryBorder" placeholder="transparent">
                  <input type="color" class="iu-token-picker" data-iu-picker-prop="btnSecondaryBorder" title="Elegir color exacto">
                  <button type="button" class="iu-quick-chip-btn" onclick="setInputDesignProp('btnSecondaryBorder', 'transparent')" title="Quitar borde">Sin borde</button>
                  <button type="button" class="iu-quick-chip-btn" onclick="autoFixInputButtonContrast('secondary')" title="Ajustar contraste WCAG">✨ Auto</button>
                </div>
              </div>
            </div>

            <!-- Bloque 5: Acento, Carril y Manejador -->
            <div class="iu-group-box">
              <div class="iu-group-head"><span>5. Acento, Carril y Manejador</span></div>
              <div class="iu-color-pair">
                <div class="iu-token-field" data-iu-field-wrap="accentColor">
                  <label for="iu_accentColor"><span>Fondo Acento</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('accentColor')" title="Volver al valor Global">↺</button></label>
                  <div class="iu-token-control">
                    <input type="text" id="iu_accentColor" class="ts-input" data-iu-prop="accentColor" placeholder="color2">
                    <input type="color" class="iu-token-picker" data-iu-picker-prop="accentColor" title="Elegir color exacto">
                  </div>
                </div>
                <div class="iu-token-field" data-iu-field-wrap="accentText">
                  <label for="iu_accentText"><span>Texto Acento</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('accentText')" title="Volver al valor Global">↺</button></label>
                  <div class="iu-token-control">
                    <input type="text" id="iu_accentText" class="ts-input" data-iu-prop="accentText" placeholder="textb">
                    <input type="color" class="iu-token-picker" data-iu-picker-prop="accentText" title="Elegir color exacto">
                  </div>
                </div>
                <div class="iu-token-field" data-iu-field-wrap="trackBg">
                  <label for="iu_trackBg"><span>Fondo Carril</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('trackBg')" title="Volver al valor Global">↺</button></label>
                  <div class="iu-token-control">
                    <input type="text" id="iu_trackBg" class="ts-input" data-iu-prop="trackBg" placeholder="back2">
                    <input type="color" class="iu-token-picker" data-iu-picker-prop="trackBg" title="Elegir color exacto">
                  </div>
                </div>
                <div class="iu-token-field" data-iu-field-wrap="thumbBg">
                  <label for="iu_thumbBg"><span>Círculo / Thumb</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('thumbBg')" title="Volver al valor Global">↺</button></label>
                  <div class="iu-token-control">
                    <input type="text" id="iu_thumbBg" class="ts-input" data-iu-prop="thumbBg" placeholder="textw">
                    <input type="color" class="iu-token-picker" data-iu-picker-prop="thumbBg" title="Elegir color exacto">
                  </div>
                </div>
              </div>
            </div>

            <!-- Bloque 6: Bordes, Outline y Geometría -->
            <div class="iu-group-box">
              <div class="iu-group-head"><span>6. Bordes, Outline y Dimensiones</span></div>
              <div class="ts-field" data-iu-field-wrap="borderWidth">
                <label for="iu_borderWidth" style="display:flex; justify-content:space-between; align-items:center;">
                  <span>Grosor de Borde (<strong id="iu_borderWidthVal">1px</strong>)</span>
                  <span style="display:inline-flex; gap:4px; align-items:center;">
                    <button type="button" class="iu-quick-chip-btn" onclick="setInputDesignProp('borderWidth', 0)">0px Sin bordes</button>
                    <button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('borderWidth')">↺</button>
                  </span>
                </label>
                <input type="range" id="iu_borderWidth" data-iu-prop="borderWidth" min="0" max="4" step="1" value="1">
              </div>

              <label class="cs-check-row" data-iu-field-wrap="focusOutline">
                <input type="checkbox" id="iu_focusOutline" data-iu-prop="focusOutline">
                <span>🎯 Mostrar Outline al enfocar (Focus Ring)</span>
                <button type="button" class="iu-local-reset-btn" onclick="event.preventDefault(); resetSingleInputLocalProp('focusOutline')">↺</button>
              </label>

              <div class="ts-field" data-iu-field-wrap="radius">
                <label for="iu_radius"><span>Radio de bordes (<strong id="iu_radiusVal">15px</strong>)</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('radius')">↺</button></label>
                <input type="range" id="iu_radius" data-iu-prop="radius" min="0" max="32" step="1" value="15">
              </div>

              <div class="ts-field" data-iu-field-wrap="touchSize">
                <label for="iu_touchSize"><span>Altura / Área Táctil (<strong id="iu_touchSizeVal">44px</strong>)</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('touchSize')">↺</button></label>
                <input type="range" id="iu_touchSize" data-iu-prop="touchSize" min="32" max="56" step="2" value="44">
              </div>

              <div class="ts-field" data-iu-field-wrap="fontSize">
                <label for="iu_fontSize"><span>Tamaño de Texto (<strong id="iu_fontSizeVal">14px</strong>)</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('fontSize')">↺</button></label>
                <input type="range" id="iu_fontSize" data-iu-prop="fontSize" min="10" max="28" step="1" value="14">
              </div>

              <div class="iu-color-pair" style="margin-bottom:4px;">
                <div class="ts-field" data-iu-field-wrap="shadow">
                  <label for="iu_shadow"><span>Sombra</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('shadow')">↺</button></label>
                  <select id="iu_shadow" class="ts-input" data-iu-prop="shadow">
                    <option value="soft">Soft (Suave)</option>
                    <option value="medium">Medium (Elevada)</option>
                    <option value="glass">Glass (Difuminada)</option>
                    <option value="none">Sin sombra</option>
                  </select>
                </div>
                <div class="ts-field" data-iu-field-wrap="blur">
                  <label for="iu_blur"><span>Blur (<strong id="iu_blurVal">10px</strong>)</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('blur')">↺</button></label>
                  <input type="range" id="iu_blur" data-iu-prop="blur" min="0" max="24" step="1" value="10">
                </div>
              </div>

              <label class="cs-check-row" style="margin-bottom:0;" data-iu-field-wrap="mobileSheet">
                <input type="checkbox" id="iu_mobileSheet" data-iu-prop="mobileSheet" checked>
                <span>📱 Bottom-Sheet táctil en móvil (&lt;= 576px)</span>
                <button type="button" class="iu-local-reset-btn" onclick="event.preventDefault(); resetSingleInputLocalProp('mobileSheet')">↺</button>
              </label>
            </div>

            <!-- Bloque 6b: Ancho, Gaps, Miniaturas y Scrollbar (FloatingScroll) -->
            <div class="iu-group-box">
              <div class="iu-group-head"><span>6b. Ancho, Gaps, Miniaturas y Scrollbar</span></div>
              <div class="ts-field" data-iu-field-wrap="width">
                <label for="iu_width" style="display:flex; justify-content:space-between; align-items:center;">
                  <span>Ancho estándar (<code>--ui-width</code>)</span>
                  <button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('width')">↺</button>
                </label>
                <div class="iu-token-control">
                  <input type="text" id="iu_width" class="ts-input" data-iu-prop="width" placeholder="100%">
                  <button type="button" class="iu-quick-chip-btn" onclick="setInputDesignProp('width', '100%'); syncInputStudioForm()">100%</button>
                  <button type="button" class="iu-quick-chip-btn" onclick="setInputDesignProp('width', '50%'); syncInputStudioForm()">50%</button>
                  <button type="button" class="iu-quick-chip-btn" onclick="setInputDesignProp('width', '320px'); syncInputStudioForm()">320px</button>
                  <button type="button" class="iu-quick-chip-btn" onclick="setInputDesignProp('width', 'auto'); syncInputStudioForm()">auto</button>
                </div>
              </div>

              <div class="iu-color-pair">
                <div class="ts-field" data-iu-field-wrap="gap">
                  <label for="iu_gap"><span>Gap Interno (<strong id="iu_gapVal">8px</strong>)</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('gap')">↺</button></label>
                  <input type="range" id="iu_gap" data-iu-prop="gap" min="0" max="32" step="1" value="8">
                </div>
                <div class="ts-field" data-iu-field-wrap="fieldGap">
                  <label for="iu_fieldGap"><span>Gap Campos (<strong id="iu_fieldGapVal">14px</strong>)</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('fieldGap')">↺</button></label>
                  <input type="range" id="iu_fieldGap" data-iu-prop="fieldGap" min="4" max="40" step="1" value="14">
                </div>
                <div class="ts-field" data-iu-field-wrap="boxPadding">
                  <label for="iu_boxPadding"><span>Padding Caja (<strong id="iu_boxPaddingVal">12px</strong>)</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('boxPadding')">↺</button></label>
                  <input type="range" id="iu_boxPadding" data-iu-prop="boxPadding" min="4" max="40" step="1" value="12">
                </div>
                <div class="ts-field" data-iu-field-wrap="thumbSize">
                  <label for="iu_thumbSize"><span>Miniatura (<strong id="iu_thumbSizeVal">80px</strong>)</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('thumbSize')">↺</button></label>
                  <input type="range" id="iu_thumbSize" data-iu-prop="thumbSize" min="48" max="160" step="4" value="80">
                </div>
              </div>

              <label class="cs-check-row" data-iu-field-wrap="hideScrollbar">
                <input type="checkbox" id="iu_hideScrollbar" data-iu-prop="hideScrollbar">
                <span>🫥 Ocultar barra de scroll visual (scroll táctil)</span>
                <button type="button" class="iu-local-reset-btn" onclick="event.preventDefault(); resetSingleInputLocalProp('hideScrollbar')">↺</button>
              </label>

              <div class="iu-color-pair">
                <div class="iu-token-field" data-iu-field-wrap="scrollbarColor">
                  <label for="iu_scrollbarColor"><span>Color Scroll</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('scrollbarColor')">↺</button></label>
                  <div class="iu-token-control">
                    <input type="text" id="iu_scrollbarColor" class="ts-input" data-iu-prop="scrollbarColor" placeholder="color2">
                    <input type="color" class="iu-token-picker" data-iu-picker-prop="scrollbarColor" title="Elegir color exacto">
                  </div>
                </div>
                <div class="ts-field" data-iu-field-wrap="scrollbarWidth">
                  <label for="iu_scrollbarWidth"><span>Ancho Scroll (<strong id="iu_scrollbarWidthVal">6px</strong>)</span><button type="button" class="iu-local-reset-btn" onclick="resetSingleInputLocalProp('scrollbarWidth')">↺</button></label>
                  <input type="range" id="iu_scrollbarWidth" data-iu-prop="scrollbarWidth" min="2" max="14" step="1" value="6">
                </div>
              </div>
            </div>

            <!-- Bloque 7: Clases CSS por Zona (solo visible en modo Local) -->
            <div id="iuLocalClassesBox" class="iu-group-box" style="display:none;">
              <div class="iu-group-head"><span>7. Clases CSS / JIT Exclusivas</span></div>
              <div class="ts-field">
                <label for="iuLocal_class">Clases en el elemento (<code>class</code>)</label>
                <div class="iu-jit-input-wrap">
                  <input type="text" id="iuLocal_class" class="ts-input" data-iu-local="class" placeholder="ej. w100 border-none outline-none">
                  <button type="button" class="iu-jit-trigger-btn" onclick="toggleJitAssistant('iuLocal_class', this)">⚡ JIT</button>
                </div>
              </div>
              <div class="ts-field">
                <label for="iuLocal_uiTrigger"><code>ui-trigger</code> (disparador/caja)</label>
                <div class="iu-jit-input-wrap">
                  <input type="text" id="iuLocal_uiTrigger" class="ts-input" data-iu-local="uiTrigger" placeholder="ej. br15 p15 shadow1 border-none">
                  <button type="button" class="iu-jit-trigger-btn" onclick="toggleJitAssistant('iuLocal_uiTrigger', this)">⚡ JIT</button>
                </div>
              </div>
              <div class="ts-field">
                <label for="iuLocal_uiSurface"><code>ui-surface</code> (modal/popover)</label>
                <div class="iu-jit-input-wrap">
                  <input type="text" id="iuLocal_uiSurface" class="ts-input" data-iu-local="uiSurface" placeholder="ej. br20 shadow2">
                  <button type="button" class="iu-jit-trigger-btn" onclick="toggleJitAssistant('iuLocal_uiSurface', this)">⚡ JIT</button>
                </div>
              </div>
              <div class="iu-color-pair">
                <div class="ts-field">
                  <label for="iuLocal_uiBtnPrimary"><code>ui-btn-primary</code></label>
                  <div class="iu-jit-input-wrap">
                    <input type="text" id="iuLocal_uiBtnPrimary" class="ts-input" data-iu-local="uiBtnPrimary" placeholder="ej. border-none">
                    <button type="button" class="iu-jit-trigger-btn" onclick="toggleJitAssistant('iuLocal_uiBtnPrimary', this)">⚡</button>
                  </div>
                </div>
                <div class="ts-field">
                  <label for="iuLocal_uiBtnSecondary"><code>ui-btn-secondary</code></label>
                  <div class="iu-jit-input-wrap">
                    <input type="text" id="iuLocal_uiBtnSecondary" class="ts-input" data-iu-local="uiBtnSecondary" placeholder="ej. border-none">
                    <button type="button" class="iu-jit-trigger-btn" onclick="toggleJitAssistant('iuLocal_uiBtnSecondary', this)">⚡</button>
                  </div>
                </div>
              </div>
              <div id="iuLegacyCompatGroup" style="margin-top:4px; border-top:1px dashed var(--ts-border); padding-top:8px;">
                <div style="font-size:0.7rem; color:var(--ts-muted); margin-bottom:4px;">Compatibilidad clásica (legacy):</div>
                <div class="iu-color-pair">
                  <div class="ts-field">
                    <label for="iuLocal_boxImage"><code>box-image</code></label>
                    <input type="text" id="iuLocal_boxImage" class="ts-input" data-iu-local="boxImage" placeholder="Opcional">
                  </div>
                  <div class="ts-field">
                    <label for="iuLocal_boxBtnImage"><code>box-btn-image</code></label>
                    <input type="text" id="iuLocal_boxBtnImage" class="ts-input" data-iu-local="boxBtnImage" placeholder="Opcional">
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Sub-panel 3: Atributos HTML y Comportamiento -->
          <div id="iuPanel_attrs" style="display:none;">
            <div class="iu-color-pair">
              <div class="ts-field">
                <label for="iuAttr_name">Atributo <code>name</code></label>
                <input type="text" id="iuAttr_name" class="ts-input" data-iu-local="name" placeholder="avatar">
              </div>
              <div class="ts-field">
                <label for="iuAttr_id">Atributo <code>id</code></label>
                <input type="text" id="iuAttr_id" class="ts-input" data-iu-local="id" placeholder="ej. avatar_usuario">
              </div>
            </div>
            <div class="iu-color-pair">
              <div class="ts-field">
                <label for="iuAttr_label">Texto de <code>&lt;label&gt;</code></label>
                <input type="text" id="iuAttr_label" class="ts-input" data-iu-local="label" placeholder="Ej. Foto de perfil">
              </div>
              <div class="ts-field">
                <label for="iuAttr_labelClass">Clases <code>labelClass</code></label>
                <div class="iu-jit-input-wrap">
                  <input type="text" id="iuAttr_labelClass" class="ts-input" data-iu-local="labelClass" placeholder="ej. bold700 x14">
                  <button type="button" class="iu-jit-trigger-btn" onclick="toggleJitAssistant('iuAttr_labelClass', this)">⚡</button>
                </div>
              </div>
            </div>
            <div class="iu-color-pair">
              <div class="ts-field">
                <label for="iuAttr_placeholder">Placeholder</label>
                <input type="text" id="iuAttr_placeholder" class="ts-input" data-iu-local="placeholder" placeholder="Texto guía...">
              </div>
              <div class="ts-field">
                <label for="iuAttr_value">Valor inicial (<code>value</code>)</label>
                <input type="text" id="iuAttr_value" class="ts-input" data-iu-local="value" placeholder="Opcional">
              </div>
            </div>
            <div class="iu-color-pair">
              <div class="ts-field">
                <label for="iuAttr_helperText">Texto de ayuda (<code>helperText</code>)</label>
                <input type="text" id="iuAttr_helperText" class="ts-input" data-iu-local="helperText" placeholder="Nota al pie del campo">
              </div>
              <div class="ts-field">
                <label for="iuAttr_helperClass">Clases <code>helperClass</code></label>
                <div class="iu-jit-input-wrap">
                  <input type="text" id="iuAttr_helperClass" class="ts-input" data-iu-local="helperClass" placeholder="ej. x12 color2">
                  <button type="button" class="iu-jit-trigger-btn" onclick="toggleJitAssistant('iuAttr_helperClass', this)">⚡</button>
                </div>
              </div>
            </div>
            <div class="iu-color-pair">
              <div class="ts-field">
                <label for="iuAttr_wrapperClass">Clases del contenedor (<code>wrapperClass</code>)</label>
                <div class="iu-jit-input-wrap">
                  <input type="text" id="iuAttr_wrapperClass" class="ts-input" data-iu-local="wrapperClass" placeholder="br15 p15 ui-field-card">
                  <button type="button" class="iu-jit-trigger-btn" onclick="toggleJitAssistant('iuAttr_wrapperClass', this)">⚡</button>
                </div>
              </div>
              <div class="ts-field">
                <label for="iuAttr_showWhen">Visibilidad condicional (<code>showWhen</code>)</label>
                <input type="text" id="iuAttr_showWhen" class="ts-input" data-iu-local="showWhen" placeholder="campo=valor1,valor2">
              </div>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:8px;">
              <label class="cs-check-row" style="margin:0;"><input type="checkbox" id="iuAttr_required" onchange="onInputLocalChange('required', this.checked)"><span>required</span></label>
              <label class="cs-check-row" style="margin:0;"><input type="checkbox" id="iuAttr_disabled" onchange="onInputLocalChange('disabled', this.checked)"><span>disabled</span></label>
              <label class="cs-check-row" style="margin:0;"><input type="checkbox" id="iuAttr_readonly" onchange="onInputLocalChange('readonly', this.checked)"><span>readonly</span></label>
              <label class="cs-check-row" style="margin:0;"><input type="checkbox" id="iuAttr_autoSubmit" onchange="onInputLocalChange('autoSubmit', this.checked)"><span>autoSubmit</span></label>
              <label class="cs-check-row" style="margin:0;"><input type="checkbox" id="iuAttr_noAutoSubmit" onchange="onInputLocalChange('noAutoSubmit', this.checked)"><span>noAutoSubmit</span></label>
              <label class="cs-check-row" style="margin:0;"><input type="checkbox" id="iuAttr_processAutoSubmit" onchange="onInputLocalChange('processAutoSubmit', this.checked)"><span>processAutoSubmit</span></label>
            </div>
            <div id="iuSpecificAttrsContainer">
              <!-- Controles específicos dinámicos según tipo seleccionado -->
            </div>
          </div>
        </div>
        </div>
      </div>

      <!-- TAB 5.8: GALERÍA DE ICONOS SVG (/Ico) -->
      <div id="tab-icons" class="ts-tab-content">
        <div class="ts-section">
          <div class="ts-section-title">
            <span>1. Explorador de Iconos (/Ico)</span>
            <button type="button" class="ts-btn ts-btn-secondary" style="padding:3px 8px; font-size:0.72rem;" onclick="refreshIconsCatalog(true)" title="Volver a escanear carpetas de /Ico en disco">
              🔄 Re-escanear
            </button>
          </div>

          <div class="ts-field" style="margin-bottom:10px;">
            <label for="isSearchInput">Buscar icono o carpeta</label>
            <div class="is-search-wrap">
              <input type="text" id="isSearchInput" class="is-search-input" placeholder="ej. asterisk, arrow, Separator..." oninput="onIconSearchChange(this.value, 'sidebar')">
              <button type="button" id="isSearchClearSidebar" class="is-search-clear" onclick="onIconSearchChange('', 'clear')" title="Limpiar búsqueda">✕</button>
            </div>
          </div>

          <div class="ts-field" style="margin-bottom:6px;">
            <label>Carpetas detectadas (<span id="isSidebarFolderCount">0</span>)</label>
            <div id="isSidebarFolderPills" class="is-folder-pills">
              <!-- Renderizado dinámico vía JS -->
            </div>
          </div>
        </div>

        <div class="ts-section">
          <div class="ts-section-title">
            <span>2. Inspector &amp; Constructor PHP</span>
            <button type="button" class="ts-btn ts-btn-secondary" style="padding:3px 8px; font-size:0.7rem;" onclick="resetIconBuilderOptions()" title="Limpiar clases y volver a svg(&quot;nombre&quot;);">
              ↺ Limpiar
            </button>
          </div>

          <div class="is-inspector-card" id="isInspectorCard">
            <div class="is-inspector-top">
              <div class="is-inspector-preview" id="isInspectorPreview"></div>
              <div class="is-inspector-meta">
                <div class="is-inspector-name" id="isInspectorName">Selecciona un icono</div>
                <div class="is-inspector-sub" id="isInspectorMeta">Haz clic en cualquier SVG de la grilla para copiar su código PHP.</div>
              </div>
            </div>
            <code id="isInspectorCodePreview" class="cs-syntax-badge" style="margin-top:0; margin-bottom:8px;">svg("asterisk");</code>
            <button type="button" class="ts-btn ts-btn-primary" style="width:100%; justify-content:center; padding:7px; font-size:0.8rem;" onclick="copySelectedIconCode()">
              📋 Copiar Código PHP
            </button>
          </div>

          <div class="ts-field">
            <label for="isCopyFormatSelect">Formato al hacer clic en un icono</label>
            <select id="isCopyFormatSelect" class="ts-select" onchange="setIconCopyFormat(this.value)">
              <option value="php">svg("nombre"); (Por defecto)</option>
              <option value="echo">&lt;?= svg("nombre") ?&gt; (En vista HTML)</option>
              <option value="phpEcho">&lt;?php echo svg("nombre"); ?&gt;</option>
              <option value="js">await svg("nombre"); (JS aaSvgStore)</option>
            </select>
          </div>

          <div class="cs-color-control">
            <div class="cs-color-header">
              <span>Clase CSS opcional (<code>$class</code>)</span>
              <span style="font-size:0.68rem; color:var(--ts-muted);">opcional</span>
            </div>
            <div class="cs-color-chips" id="isClassColorChips"></div>
            <input type="text" id="isCustomClassInput" class="ts-input-text" style="font-family:monospace; font-size:0.76rem; padding:6px 8px;" placeholder="ej. color2 x25 (vacío = solo nombre)" oninput="onIconBuilderOptionChange()">
          </div>

          <div class="ts-field" style="margin-bottom:0;">
            <label for="isCustomTransformInput">Transformación SVG opcional (<code>$transform</code>)</label>
            <input type="text" id="isCustomTransformInput" class="ts-input-text" style="font-family:monospace; font-size:0.76rem; padding:6px 8px;" placeholder="ej. rotate(90) o scale(-1, 1)" oninput="onIconBuilderOptionChange()">
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

      <?php require __DIR__ . '/partials/tab_js_modules.php'; ?>
      <?php require __DIR__ . '/partials/tab_config.php'; ?>

    </aside>

    <!-- Right Live Real Project View Stage -->
    <main class="ts-sandbox-stage">
      
      <!-- Barra Superior de Navegación del Canvas & Resoluciones Viewport -->
      <div class="ts-canvas-bar">
        <!-- Izquierda: Toggle de Sidebar, Conmutador de Escenario y Barra de Direcciones del Proyecto -->
        <div class="ts-canvas-nav">
          <button type="button" class="ts-sidebar-toggle-btn" id="btnToggleSidebar" onclick="toggleSidebar()" title="Ocultar / Mostrar panel lateral">
            ◀ Panel
          </button>

          <div class="ts-stage-switcher" title="Cambiar entre vista real del sitio, laboratorio de gráficos, diseñador de inputs y galería de iconos SVG">
            <button type="button" class="ts-stage-btn active" id="btnStageSite" onclick="setStageMode('site')">
              🌐 <span>Sitio</span>
            </button>
            <button type="button" class="ts-stage-btn" id="btnStageCharts" onclick="setStageMode('charts')">
              📊 <span>Gráficos</span>
            </button>
            <button type="button" class="ts-stage-btn" id="btnStageInputs" onclick="setStageMode('inputs')">
              🎛️ <span>Inputs</span>
            </button>
            <button type="button" class="ts-stage-btn" id="btnStageIcons" onclick="setStageMode('icons')">
              🖼️ <span>Iconos</span>
            </button>
            <button type="button" class="ts-stage-btn" id="btnStageJsModules" onclick="setStageMode('jsmodules')">
              📦 <span>Módulos JS</span>
            </button>
            <button type="button" class="ts-stage-btn" id="btnStageConfig" onclick="setStageMode('config')">
              ⚙️ <span>Config</span>
            </button>
          </div>

          <div class="ts-route-bar" id="projectRouteBar">
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

          <div id="chartBarStatus" style="display:none; align-items:center; gap:6px; font-size:0.78rem; color:var(--ts-muted); white-space:nowrap;">
            <span>🧪 Chart Studio</span>
            <span id="csActiveChartBadge" class="ts-badge">horizontalBars</span>
          </div>

          <div id="iconBarStatus" style="display:none; align-items:center; gap:6px; font-size:0.78rem; color:var(--ts-muted); white-space:nowrap;">
            <span>🖼️ Galería SVG (/Ico)</span>
            <span id="isTotalBadge" class="ts-badge">0 iconos</span>
          </div>
          <div id="inputBarStatus" style="display:none; align-items:center; gap:6px; font-size:0.78rem; color:var(--ts-muted); white-space:nowrap;">
            <span>🎛️ UI / Input Studio</span>
            <span class="ts-badge">19 componentes</span>
          </div>
          <div id="jsModulesBarStatus" style="display:none; align-items:center; gap:6px; font-size:0.78rem; color:var(--ts-muted); white-space:nowrap;">
            <span>📦 Módulos JS &amp; Librerías</span>
          </div>
          <div id="configBarStatus" style="display:none; align-items:center; gap:6px; font-size:0.78rem; color:var(--ts-muted); white-space:nowrap;">
            <span>⚙️ Configuración &amp; Vault</span>
          </div>
        </div>

        <!-- Derecha: Selector de Resolución y Dispositivos (dinámico desde mediaQueries) -->
        <div class="ts-viewport-bar" id="viewportBar">
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

          <!-- Laboratorio Interactivo de Gráficos (Chart Sandbox) -->
          <div id="chartSandboxView">
            <div class="cs-toolbar">
              <div class="cs-toolbar-group">
                <div class="ts-mode-segmented">
                  <button type="button" class="ts-mode-btn active" id="btnCsViewGallery" onclick="setChartSandboxViewMode('gallery')">
                    🔲 <span>Galería (12)</span>
                  </button>
                  <button type="button" class="ts-mode-btn" id="btnCsViewFocused" onclick="setChartSandboxViewMode('focused')">
                    🎯 <span>Vista Enfocada (1)</span>
                  </button>
                </div>

                <div id="csFocusedWidthWrap" style="display:none; align-items:center; gap:5px;">
                  <span style="font-size:0.74rem; color:var(--ts-muted);">Ancho tarjeta:</span>
                  <select id="csFocusedWidthSelect" class="cs-toolbar-select" onchange="setFocusedCardWidth(this.value)">
                    <option value="100%">100% (Ancho completo)</option>
                    <option value="640px">640px (Tarjeta amplia)</option>
                    <option value="480px">480px (Columna media)</option>
                    <option value="380px">380px (Tarjeta compacta)</option>
                    <option value="280px">280px (Widget estrecho)</option>
                  </select>
                </div>
              </div>

              <div class="cs-toolbar-group">
                <select id="csSurfaceSelect" class="cs-toolbar-select" onchange="setChartCardSurface(this.value)" title="Fondo simulado de las tarjetas">
                  <option value="var(--body)">Fondo: --body</option>
                  <option value="var(--back-color)">Fondo: back1</option>
                  <option value="var(--back-color2)">Fondo: back2</option>
                  <option value="var(--back-color3)">Fondo: back3</option>
                  <option value="#0c0c0e">Fondo: #0C0C0E</option>
                  <option value="#ffffff">Fondo: #FFFFFF</option>
                </select>

                <select id="csToolbarPresetSelect" class="cs-toolbar-select" onchange="setChartDatasetPreset(this.value)" title="Preset de datos de prueba">
                  <option value="standard">Datos: Estándar</option>
                  <option value="compact">Datos: Pocos (3)</option>
                  <option value="dense">Datos: Muchos (8-10)</option>
                  <option value="long-labels">Datos: Nombres largos</option>
                </select>

                <button type="button" class="ts-btn ts-btn-secondary" style="padding:5px 10px; font-size:0.76rem;" onclick="replayChartsAnimation()" title="Reproducir animación de entrada">
                  ▶️ <span>Animar</span>
                </button>
              </div>
            </div>

            <div class="cs-sandbox-stage" id="chartSandboxStage">
              <div id="csGalleryContainer" class="cs-gallery-grid">
                <!-- Tarjetas de los 12 gráficos generadas por JS -->
              </div>
              <div id="csFocusedContainer" class="cs-focused-wrap" style="display:none;">
                <!-- Vista enfocada de 1 gráfico generada por JS -->
              </div>
            </div>

            <!-- Panel Generador de Código PHP "Copy & Paste" -->
            <div class="cs-code-dock" id="csCodeDock">
              <div class="cs-code-header">
                <div class="cs-code-tabs">
                  <button type="button" class="cs-code-tab active" id="csCodeTab_helper" onclick="setChartCodeFormat('helper')">_chart()</button>
                  <button type="button" class="cs-code-tab" id="csCodeTab_module" onclick="setChartCodeFormat('module')">GraphicsModule</button>
                  <button type="button" class="cs-code-tab" id="csCodeTab_global" onclick="setChartCodeFormat('global')">configStyle()</button>
                </div>

                <div class="cs-code-options">
                  <label><input type="checkbox" id="csCodeOnlyModified" checked onchange="updateChartCodeOutput()"> Solo modificadas</label>
                  <label id="csCodeIncludeDataLabel"><input type="checkbox" id="csCodeIncludeData" checked onchange="updateChartCodeOutput()"> Incluir datos</label>
                  <label id="csCodeWrapLabel"><input type="checkbox" id="csCodeWrapContainer" onchange="updateChartCodeOutput()"> Envolver div</label>
                </div>

                <div style="display:flex; align-items:center; gap:6px;">
                  <button type="button" class="ts-icon-btn" id="btnToggleCodeDock" onclick="toggleChartCodeDock()" title="Colapsar / Expandir visor de código">▾</button>
                  <button type="button" class="ts-btn ts-btn-primary" style="padding:5px 11px; font-size:0.76rem;" onclick="copyChartPhpCode()">
                    📋 <span>Copiar Código</span>
                  </button>
                </div>
              </div>
              <div class="cs-code-body">
                <pre class="cs-code-pre"><code id="chartPhpCodeOutput"></code></pre>
              </div>
            </div>
          </div>

          <!-- Galería Visual Interactiva de Iconos SVG (/Ico) -->
          <div id="iconSandboxView">
            <div class="cs-toolbar">
              <div class="cs-toolbar-group" style="flex:1; min-width:220px;">
                <div class="is-search-wrap">
                  <input type="text" id="isStageSearchInput" class="is-search-input" placeholder="🔍 Filtrar SVG por nombre o carpeta..." oninput="onIconSearchChange(this.value, 'stage')">
                  <button type="button" id="isSearchClearStage" class="is-search-clear" onclick="onIconSearchChange('', 'clear')" title="Limpiar búsqueda">✕</button>
                </div>

                <select id="isFolderFilterSelect" class="cs-toolbar-select" onchange="setIconFolderFilter(this.value)" title="Filtrar por carpeta dentro de /Ico">
                  <option value="__all__">Todas las carpetas</option>
                </select>
              </div>

              <div class="cs-toolbar-group">
                <select id="isSurfaceSelect" class="cs-toolbar-select" onchange="setIconStageSurface(this.value)" title="Fondo simulado de la galería">
                  <option value="var(--body)">Fondo: --body</option>
                  <option value="var(--back-color)">Fondo: back1</option>
                  <option value="var(--back-color2)">Fondo: back2</option>
                  <option value="var(--back-color3)">Fondo: back3</option>
                  <option value="#0c0c0e">Fondo: #0C0C0E</option>
                  <option value="#ffffff">Fondo: #FFFFFF</option>
                </select>

                <select id="isPreviewColorSelect" class="cs-toolbar-select" onchange="setIconPreviewColorToken(this.value)" title="Color visual de previsualización de los SVG">
                  <option value="auto">Color: Auto</option>
                  <option value="texto">Color: texto</option>
                  <option value="textc">Color: textc</option>
                  <option value="textw">Color: textw</option>
                  <option value="color1">Color: color1</option>
                  <option value="color2">Color: color2</option>
                  <option value="color3">Color: color3</option>
                </select>

                <div class="is-size-segmented" title="Tamaño de previsualización en la grilla">
                  <button type="button" class="is-size-btn" data-size="20" onclick="setIconPreviewSize(20, this)">S</button>
                  <button type="button" class="is-size-btn active" data-size="28" onclick="setIconPreviewSize(28, this)">M</button>
                  <button type="button" class="is-size-btn" data-size="38" onclick="setIconPreviewSize(38, this)">L</button>
                </div>

                <button type="button" class="ts-btn ts-btn-secondary" style="padding:5px 10px; font-size:0.76rem;" onclick="refreshIconsCatalog(true)" title="Re-escanear carpetas y archivos SVG nuevos en /Ico">
                  🔄 <span>Actualizar</span>
                </button>
              </div>
            </div>

            <div class="is-sandbox-stage" id="iconSandboxStage">
              <div id="isFoldersContainer" class="is-folders-stack">
                <!-- Secciones por carpeta y grillas de SVG generadas dinámicamente por JS -->
              </div>
            </div>

            <!-- Dock Inferior de Código PHP "Copy & Paste" para el SVG seleccionado -->
            <div class="cs-code-dock" id="isCodeDock">
              <div class="cs-code-header">
                <div class="cs-code-tabs">
                  <button type="button" class="cs-code-tab active" id="isCodeTab_php" onclick="setIconCopyFormat('php')">svg("...");</button>
                  <button type="button" class="cs-code-tab" id="isCodeTab_echo" onclick="setIconCopyFormat('echo')">&lt;?= svg(...) ?&gt;</button>
                  <button type="button" class="cs-code-tab" id="isCodeTab_phpEcho" onclick="setIconCopyFormat('phpEcho')">&lt;?php echo ... ?&gt;</button>
                  <button type="button" class="cs-code-tab" id="isCodeTab_js" onclick="setIconCopyFormat('js')">JS svg()</button>
                </div>

                <div style="font-size:0.74rem; color:var(--ts-muted); font-family:monospace;" id="isDockHint">
                  Haz clic en cualquier SVG para copiar al portapapeles
                </div>

                <div style="display:flex; align-items:center; gap:6px;">
                  <button type="button" class="ts-icon-btn" id="btnToggleIconCodeDock" onclick="toggleIconCodeDock()" title="Colapsar / Expandir visor de código">▾</button>
                  <button type="button" class="ts-btn ts-btn-primary" style="padding:5px 11px; font-size:0.76rem;" onclick="copySelectedIconCode()">
                    📋 <span>Copiar PHP</span>
                  </button>
                </div>
              </div>
              <div class="cs-code-body" style="padding:10px 14px;">
                <pre class="cs-code-pre"><code id="iconPhpCodeOutput">svg("asterisk");</code></pre>
              </div>
            </div>
          </div>

          <!-- Laboratorio Visual Interactivo de Inputs (FormUiModule) -->
          <div id="inputSandboxView">
            <div class="cs-toolbar">
              <div class="cs-toolbar-group">
                <div class="cs-mode-switch">
                  <button type="button" class="ts-mode-btn active" id="btnIuViewGallery" onclick="setInputSandboxViewMode('gallery')">
                    <span>🗂️ Todos (19)</span>
                  </button>
                  <button type="button" class="ts-mode-btn" id="btnIuViewFocused" onclick="setInputSandboxViewMode('focused')">
                    <span>🔍 Enfoque</span>
                  </button>
                  <button type="button" class="ts-mode-btn" id="btnIuViewFormBuilder" onclick="setInputSandboxViewMode('formBuilder')">
                    <span>🧱 Armar Formulario</span>
                  </button>
                </div>

                <button type="button" class="ts-btn ts-btn-secondary iu-touch-btn" id="btnIuTouchSim" style="padding:5px 10px; font-size:0.75rem;" onclick="toggleInputTouchSim()" title="Simular pantallas táctiles y modo Bottom-Sheet móvil">
                  👆 <span>Simular Touch</span>
                </button>

                <button type="button" class="ts-btn ts-btn-secondary" style="padding:5px 10px; font-size:0.75rem;" onclick="openCropDemoFromStudio()" title="Abrir modal de recorte de imagen con imagen demo instantánea">
                  ✂️ <span>Probar Recorte</span>
                </button>
              </div>

              <div class="cs-toolbar-group">
                <select id="iuSurfaceSelect" class="cs-toolbar-select" onchange="setInputStageSurface(this.value)" title="Fondo simulado de prueba">
                  <option value="var(--body)">Fondo: --body</option>
                  <option value="var(--back-color)">Fondo: back1</option>
                  <option value="var(--back-color2)">Fondo: back2</option>
                  <option value="#0c0c0e">Fondo: #0C0C0E</option>
                  <option value="#ffffff">Fondo: #FFFFFF</option>
                </select>

                <div id="iuFocusedWidthWrap" style="display:none; align-items:center; gap:6px;">
                  <span style="font-size:0.74rem; color:var(--ts-muted);">Ancho:</span>
                  <select id="iuFocusedWidthSelect" class="cs-toolbar-select" onchange="setInputFocusedWidth(this.value)">
                    <option value="100%">100%</option>
                    <option value="540px">540px</option>
                    <option value="375px">375px (Móvil)</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="iu-sandbox-stage" id="inputSandboxStage">
              <div id="iuGalleryContainer" class="iu-gallery-grid">
                <!-- Tarjetas de los 19 inputs generadas desde initialInputsHtmlMap -->
              </div>
              <div id="iuFocusedContainer" class="iu-focused-wrap">
                <!-- Vista enfocada del input activo -->
              </div>
              <div id="iuFormBuilderContainer" class="fb-stage-wrap" style="display:none;">
                <!-- Diseñador visual completo de formularios -->
              </div>
            </div>

            <!-- Dock Inferior de Código "Copy & Paste" -->
            <div class="cs-code-dock" id="iuCodeDock">
              <div class="cs-code-header">
                <div class="cs-code-tabs">
                  <button type="button" class="cs-code-tab active" id="iuCodeTab_php" onclick="setInputCopyFormat('php')"><span id="iuCodeTab_phpLabel">Helper _input()</span></button>
                  <button type="button" class="cs-code-tab" id="iuCodeTab_html" onclick="setInputCopyFormat('html')">HTML Completo</button>
                  <button type="button" class="cs-code-tab" id="iuCodeTab_global" onclick="setInputCopyFormat('global')">Global FormUiModule</button>
                </div>

                <div class="iu-code-options" id="iuSingleCodeOptions">
                  <label><input type="checkbox" id="iuOptWrapContainer" checked onchange="onInputCodeOptionChange()"> <span>Envolver &lt;div&gt;</span></label>
                  <label><input type="checkbox" id="iuOptIncludeLabel" checked onchange="onInputCodeOptionChange()"> <span>Incluir &lt;label&gt;</span></label>
                  <label title="Incluye también los cambios globales dentro del código copiado para que sea 100% autocontenido en la vista"><input type="checkbox" id="iuOptIncludeGlobalInCode" checked onchange="onInputCodeOptionChange()"> <span>Autocontenido (Global + Local)</span></label>
                  <label><input type="checkbox" id="iuOptInlineVars" onchange="onInputCodeOptionChange()"> <span>Vars --ui-* inline</span></label>
                  <label><input type="checkbox" id="iuOptMultiline" checked onchange="onInputCodeOptionChange()"> <span>Multilínea</span></label>
                </div>
                <div class="iu-code-options" id="iuFbCodeOptions" style="display:none;">
                  <label title="Cambia el tab PHP de _form() a llamadas individuales _input()"><input type="checkbox" id="fbOptIndividualInputs" onchange="updateFormBuilderCodeOutput()"> <span>Usar _input() individuales</span></label>
                </div>

                <div style="display:flex; align-items:center; gap:6px;">
                  <button type="button" class="ts-icon-btn" id="btnToggleInputCodeDock" onclick="toggleInputCodeDock()" title="Colapsar / Expandir código">▾</button>
                  <button type="button" class="ts-btn ts-btn-primary" style="padding:5px 11px; font-size:0.76rem;" onclick="copyInputCode()">
                    📋 <span>Copiar Código</span>
                  </button>
                </div>
              </div>
              <div class="cs-code-body" style="padding:10px 14px;">
                <pre class="cs-code-pre"><code id="inputCodeOutput"></code></pre>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php require __DIR__ . '/partials/stage_js_modules.php'; ?>
      <?php require __DIR__ . '/partials/stage_config.php'; ?>
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

  <!-- Motor Charts (GraphicsModule) autocontenido para Chart Studio -->
  <script id="ts-charts-core-js"><?= $chartsJs ?? '' ?></script>
  <!-- Motor FormUi autocontenido para UI / Input Studio -->
  <script id="ts-inputs-core-js"><?= $inputsJs ?? '' ?></script>

  <!-- Pure Vanilla Client-Side JavaScript Engine -->
  <script>
    // Configuración base leída desde App/Public/Css/_configTheme.css del proyecto
    const defaultCssConfig = <?= $rawDefaultJson ?>;
    // Configuración activa (cargada desde theme.json o defaultCssConfig)
    const initialConfig = <?= $rawJson ?>;
    const initialFullConfig = <?= $rawFullJson ?? json_encode(['activeTheme' => 'default', 'themes' => ['default' => $config]]) ?>;
    const initialChartsHtmlMap = <?= json_encode($initialChartsHtmlMap ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const initialChartsSampleData = <?= json_encode($initialChartsSampleData ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const initialInputsHtmlMap = <?= json_encode($initialInputsHtmlMap ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const JIT_CLASSES_CATALOG = <?= json_encode($jitCatalog ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const initialJitPreviewCss = <?= json_encode($initialJitPreviewCss ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const initialIconsCatalog = <?= json_encode($initialIconsCatalog ?? ['totalIcons' => 0, 'totalFolders' => 0, 'primaryDir' => 'Resources/Ico', 'folders' => []], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

    let fullThemeConfig = JSON.parse(JSON.stringify(initialFullConfig));
    let activeThemeSlug = '<?= $activeThemeSlug ?? "default" ?>';
    let currentThemeSlug = '<?= $config["themeSlug"] ?? "default" ?>';
    let themeConfig = JSON.parse(JSON.stringify(initialConfig));
    let currentPreviewMode = 'light'; // 'light' o 'dark'
    let currentViewportWidth = '100%';
    let currentTabId = 'themes';
    let isRestoringWorkspace = true;
    let iframeThemeObserver = null;

    const DEFAULT_MEDIA_QUERIES = [
      { slug: 'phone', jit: 'sml', label: 'Móvil (Phone)', min: null, max: 576 },
      { slug: 'tablet', jit: 'mid', label: 'Tablet', min: 577, max: 992 },
      { slug: 'desk', jit: 'desk', label: 'Escritorio (Desktop)', min: 993, max: null }
    ];

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
      // 0. Asegurar estructura de mediaQueries, charts e inputs
      ensureMediaQueries();
      ensureChartsConfig();
      ensureInputsConfig();
      // 1. Renderizar lista de temas y selector
      renderThemesTab();
      // 2. Renderizar inputs de paleta clara
      renderColorSlots('light');
      // 3. Renderizar inputs de paleta oscura
      renderColorSlots('dark');
      // 4. Renderizar media queries y botones de viewport
      renderMediaQueriesList();
      renderViewportButtons();
      // 5. Inicializar valores de formulario
      syncFormValues();
      // 5.5. Inicializar Chart Studio (panel y caché inicial)
      initChartStudio();
      // 5.6. Inicializar UI / Input Studio (panel, presets y caché inicial)
      initInputStudio();
      // 5.8. Inicializar Icon Studio (galería visual de SVG en /Ico)
      initIconStudio();
      // 6. Configurar iframe de vista real del proyecto
      setupProjectIframe();
      // 7. Inyectar variables en vivo
      applySandboxStyles();
      // 8. Actualizar visor JSON
      updateJsonViewer();
      // 9. Vincular persistencia de desplazamiento en pestañas de barra lateral y restaurar espacio de trabajo
      document.querySelectorAll('.ts-tab-content').forEach((tabEl) => {
        if (tabEl.dataset.wsScrollBound) return;
        tabEl.dataset.wsScrollBound = '1';
        let sbScrollTimer = null;
        tabEl.addEventListener('scroll', () => {
          clearTimeout(sbScrollTimer);
          sbScrollTimer = setTimeout(saveWorkspaceState, 180);
        });
      });
      restoreWorkspaceState();
      initStudioFloatingScrolls();
    }

    // ==============================================================
    // FLOATING SCROLLBARS GLOBALES DEL STUDIO (Resources/Js/Components/floatingScroll.js)
    // ==============================================================
    function getStudioScrollContainers() {
      const list = [];
      document.querySelectorAll('.ts-tab-content').forEach((el) => list.push(el));
      ['jsModulesSandboxView', 'configSandboxView', 'chartSandboxStage', 'iconSandboxStage', 'inputSandboxStage', 'jsonViewer'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) list.push(el);
      });
      return list;
    }

    function initStudioFloatingScrolls() {
      if (typeof window.FloatingScroll === 'undefined' || typeof window.FloatingScroll.attach !== 'function') {
        return;
      }
      getStudioScrollContainers().forEach((el) => {
        window.FloatingScroll.attach(el, {
          width: 7,
          color: 'var(--ts-primary, #38bdf8)',
          hoverExpand: 3,
          minThumbHeight: 36,
          offsetRight: 3,
          offsetY: 6,
          opacity: 0.55,
          zIndex: 90
        });
      });
    }

    function syncStudioFloatingScrolls() {
      initStudioFloatingScrolls();
      getStudioScrollContainers().forEach((el) => {
        if (el.__floatingScrollInstance && typeof el.__floatingScrollInstance.refresh === 'function') {
          el.__floatingScrollInstance.refresh();
        }
      });
      if (typeof window.FmeUi?.syncAllFloatingScrolls === 'function') {
        window.FmeUi.syncAllFloatingScrolls();
      }
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
      const routeSel = document.getElementById('projectRouteSelect');
      if (routeInput) routeInput.value = routeUri;
      if (routeSel && Array.from(routeSel.options).some(o => o.value === routeUri)) {
        routeSel.value = routeUri;
      }
      if (frame) {
        frame.src = routeUri;
      }
      saveWorkspaceState();
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

      // Media Queries
      ensureMediaQueries();
      renderMediaQueriesList();
      renderViewportButtons();
    }

    // ==============================================================
    // GESTIÓN VISUAL DE MEDIA QUERIES Y PANTALLAS RESPONSIVAS
    // ==============================================================

    function buildMediaQueryExpr(min, max) {
      const hasMin = Number.isFinite(min) && min > 0;
      const hasMax = Number.isFinite(max) && max > 0;
      if (hasMin && hasMax) {
        const a = Math.min(min, max);
        const b = Math.max(min, max);
        return `(min-width: ${a}px) and (max-width: ${b}px)`;
      }
      if (hasMin) return `(min-width: ${min}px)`;
      if (hasMax) return `(max-width: ${max}px)`;
      return '(min-width: 0px)';
    }

    function sanitizeMediaSlug(raw) {
      let s = String(raw || '').toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/^no-/, '')
        .replace(/[^a-z0-9_-]+/g, '-')
        .replace(/^-+|-+$/g, '');
      return s;
    }

    function ensureMediaQueries() {
      if (!themeConfig.layout || typeof themeConfig.layout !== 'object') {
        themeConfig.layout = {};
      }
      const list = themeConfig.layout.mediaQueries;
      if (!Array.isArray(list) || list.length === 0) {
        themeConfig.layout.mediaQueries = DEFAULT_MEDIA_QUERIES.map(item => ({
          ...item,
          query: buildMediaQueryExpr(item.min, item.max)
        }));
      } else {
        const defaultJits = { phone: 'sml', tablet: 'mid', desk: 'desk' };
        themeConfig.layout.mediaQueries = list.map((item, idx) => {
          const slug = sanitizeMediaSlug(item.slug || `screen-${idx + 1}`) || `screen-${idx + 1}`;
          const min = (item.min !== null && item.min !== '' && item.min !== undefined) ? Math.max(0, parseInt(item.min, 10) || 0) : null;
          const max = (item.max !== null && item.max !== '' && item.max !== undefined) ? Math.max(0, parseInt(item.max, 10) || 0) : null;
          const jit = item.jit ? sanitizeMediaSlug(item.jit) : (defaultJits[slug] || slug);
          return {
            slug,
            jit,
            label: (item.label && String(item.label).trim()) ? String(item.label).trim() : slug,
            min,
            max,
            query: buildMediaQueryExpr(min, max)
          };
        });
      }
      return themeConfig.layout.mediaQueries;
    }

    function getViewportTestWidth(mq) {
      const hasMin = mq.min !== null && mq.min > 0;
      const hasMax = mq.max !== null && mq.max > 0;
      if (hasMin && hasMax) return Math.round((mq.min + mq.max) / 2);
      if (hasMax) return Math.min(mq.max, 380);
      if (hasMin) return Math.max(mq.min, 1200);
      return 1200;
    }

    function renderViewportButtons() {
      const bar = document.getElementById('viewportBar');
      if (!bar) return;
      const list = ensureMediaQueries();

      let html = `
        <button type="button" class="ts-view-btn ${currentViewportWidth === '100%' ? 'active' : ''}" onclick="setViewport('100%', this)" title="Escritorio Completo">
          🖥️ <span>100%</span>
        </button>
      `;

      list.forEach(mq => {
        const w = getViewportTestWidth(mq);
        const widthStr = `${w}px`;
        const icon = mq.slug === 'phone' ? '📲' : (mq.slug === 'tablet' ? '📱' : (mq.slug === 'desk' ? '💻' : '🖥️'));
        const activeClass = currentViewportWidth === widthStr ? 'active' : '';
        html += `
          <button type="button" class="ts-view-btn ${activeClass}" onclick="setViewport('${widthStr}', this)" title="${mq.label} (${mq.query})">
            ${icon} <span>${mq.slug} (${w}px)</span>
          </button>
        `;
      });

      bar.innerHTML = html;
    }

    function renderMediaQueriesList() {
      const container = document.getElementById('mediaQueriesList');
      if (!container) return;
      const list = ensureMediaQueries();
      const coreSlugs = ['phone', 'tablet', 'desk'];

      container.innerHTML = '';

      list.forEach((mq, index) => {
        const isCore = coreSlugs.includes(mq.slug);
        const card = document.createElement('div');
        card.className = 'ts-mq-card';
        card.innerHTML = `
          <div class="ts-mq-header">
            <div class="ts-mq-badges">
              <span class="ts-mq-class-badge" id="mqBadge_${index}">.no-${mq.slug}</span>
              <span class="ts-mq-class-badge" id="mqBadgeCss_${index}" style="opacity:0.75;">.no-${mq.slug}-css</span>
            </div>
            ${!isCore ? `
              <button type="button" class="ts-btn-del-slot" onclick="removeMediaQuerySlot(${index})" title="Eliminar esta media query">✕</button>
            ` : `<span style="font-size:0.68rem; color:var(--ts-muted);">Base</span>`}
          </div>
          <div class="ts-mq-grid">
            <div class="ts-mq-field">
              <label>Nombre / Etiqueta</label>
              <input type="text" class="ts-mq-input" value="${String(mq.label).replace(/"/g, '&quot;')}" oninput="onMediaQueryChange(${index}, 'label', this.value)">
            </div>
            <div class="ts-mq-field">
              <label>Slug (.no-slug)</label>
              <input type="text" class="ts-mq-input" style="font-family:monospace;" value="${mq.slug}" ${isCore ? 'readonly title="Clase base del framework"' : `onchange="onMediaQueryChange(${index}, 'slug', this.value)"`}>
            </div>
            <div class="ts-mq-field">
              <label>Mínimo (px)</label>
              <input type="number" min="0" class="ts-mq-input" placeholder="Sin mín." value="${mq.min !== null ? mq.min : ''}" oninput="onMediaQueryChange(${index}, 'min', this.value)">
            </div>
            <div class="ts-mq-field">
              <label>Máximo (px)</label>
              <input type="number" min="0" class="ts-mq-input" placeholder="Sin máx." value="${mq.max !== null ? mq.max : ''}" oninput="onMediaQueryChange(${index}, 'max', this.value)">
            </div>
          </div>
          <div class="ts-mq-preview" id="mqPreview_${index}">@media screen and ${mq.query}</div>
        `;
        container.appendChild(card);
      });
    }

    function onMediaQueryChange(index, field, value) {
      const list = ensureMediaQueries();
      const mq = list[index];
      if (!mq) return;

      if (field === 'label') {
        mq.label = String(value || '').trim() || mq.slug;
      } else if (field === 'slug') {
        let clean = sanitizeMediaSlug(value);
        if (!clean) clean = `screen-${index + 1}`;
        const duplicate = list.some((item, i) => i !== index && item.slug === clean);
        if (duplicate) {
          showToast(`⚠️ Ya existe una media query con el slug "${clean}"`, 'error');
          renderMediaQueriesList();
          return;
        }
        mq.slug = clean;
        if (!['phone', 'tablet', 'desk'].includes(clean)) {
          mq.jit = clean;
        }
        const badge = document.getElementById(`mqBadge_${index}`);
        const badgeCss = document.getElementById(`mqBadgeCss_${index}`);
        if (badge) badge.textContent = `.no-${mq.slug}`;
        if (badgeCss) badgeCss.textContent = `.no-${mq.slug}-css`;
      } else if (field === 'min' || field === 'max') {
        const trimmed = String(value || '').trim();
        mq[field] = trimmed === '' ? null : Math.max(0, parseInt(trimmed, 10) || 0);
      }

      mq.query = buildMediaQueryExpr(mq.min, mq.max);
      const previewEl = document.getElementById(`mqPreview_${index}`);
      if (previewEl) previewEl.textContent = `@media screen and ${mq.query}`;

      renderViewportButtons();
      applySandboxStyles();
      updateJsonViewer();
    }

    function addMediaQuerySlot() {
      const list = ensureMediaQueries();
      let counter = 1;
      let candidate = 'laptop';
      const suggestions = ['laptop', 'tv', 'wide', 'mini'];
      for (const s of suggestions) {
        if (!list.some(item => item.slug === s)) {
          candidate = s;
          break;
        }
      }
      while (list.some(item => item.slug === candidate)) {
        candidate = `pantalla-${counter++}`;
      }

      list.push({
        slug: candidate,
        jit: candidate,
        label: candidate.charAt(0).toUpperCase() + candidate.slice(1),
        min: 1440,
        max: null,
        query: '(min-width: 1440px)'
      });

      renderMediaQueriesList();
      renderViewportButtons();
      applySandboxStyles();
      updateJsonViewer();
      showToast(`📱 Media query ".no-${candidate}" agregada`);
    }

    function removeMediaQuerySlot(index) {
      const list = ensureMediaQueries();
      const target = list[index];
      if (!target) return;
      if (['phone', 'tablet', 'desk'].includes(target.slug)) {
        showToast('⚠️ Las pantallas base (phone, tablet, desk) no se pueden eliminar.', 'error');
        return;
      }

      list.splice(index, 1);
      renderMediaQueriesList();
      renderViewportButtons();
      applySandboxStyles();
      updateJsonViewer();
      showToast(`🗑️ Media query ".no-${target.slug}" eliminada`);
    }

    function resetDefaultMediaQueries() {
      themeConfig.layout.mediaQueries = DEFAULT_MEDIA_QUERIES.map(item => ({
        ...item,
        query: buildMediaQueryExpr(item.min, item.max)
      }));
      renderMediaQueriesList();
      renderViewportButtons();
      applySandboxStyles();
      updateJsonViewer();
      showToast('🔄 Media Queries restauradas a los valores por defecto');
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

      const activeMediaQueries = ensureMediaQueries();
      activeMediaQueries.forEach(mq => {
        css += `  --mq-${mq.slug}: ${mq.query} !important;\n`;
      });

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
      if (typeof buildInputCssVarsBlock === 'function') {
        css += buildInputCssVarsBlock(true);
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
      if (typeof buildInputCssVarsBlock === 'function') {
        css += buildInputCssVarsBlock(true);
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

      // Reglas de Media Queries y Visibilidad Responsiva en vivo
      const liveMediaQueries = ensureMediaQueries();
      css += `\n/* Media Queries activas en vivo */\n`;
      liveMediaQueries.forEach(mq => {
        css += `@media screen and ${mq.query} {\n`;
        css += `  .no-${mq.slug}:not([data-responsive-processed]), .no-${mq.slug}-css { display: none !important; }\n`;
        css += `}\n`;
      });

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

        // Sincronizar Media Queries con querie.js dentro del iframe si está cargado
        const win = frame.contentWindow;
        if (win) {
          win.fmeMediaQueries = JSON.parse(JSON.stringify(ensureMediaQueries()));
          if (win.responsiveVisibility && typeof win.responsiveVisibility.refreshMediaQueries === 'function') {
            win.responsiveVisibility.refreshMediaQueries(win.fmeMediaQueries);
          }
        }
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

      // Actualizar variables CSS en tiempo real dentro del laboratorio de gráficos (0 ms)
      injectLiveThemeIntoChartSandbox();
      if (typeof renderChartColorChips === 'function') {
        renderChartColorChips();
      }

      // Actualizar variables CSS --ui-* en tiempo real dentro del laboratorio de inputs (0 ms)
      if (typeof injectLiveThemeIntoInputSandbox === 'function') {
        injectLiveThemeIntoInputSandbox();
      }
      if (typeof renderInputColorChips === 'function') {
        renderInputColorChips();
      }
      if (typeof updateInputWcagBadges === 'function') {
        updateInputWcagBadges();
      }

      // Actualizar variables CSS en tiempo real dentro de la galería de iconos SVG (0 ms)
      if (typeof injectLiveThemeIntoIconSandbox === 'function') {
        injectLiveThemeIntoIconSandbox();
      }
      if (typeof renderIconColorChips === 'function') {
        renderIconColorChips();
      }

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
      saveWorkspaceState();
    }

    function toggleSidebar(forceState = null) {
      const sidebar = document.querySelector('.ts-sidebar');
      const btn = document.getElementById('btnToggleSidebar');
      if (!sidebar) return;
      const isCollapsed = (typeof forceState === 'boolean')
        ? sidebar.classList.toggle('collapsed', forceState)
        : sidebar.classList.toggle('collapsed');
      if (btn) {
        btn.innerHTML = isCollapsed ? '▶ Panel' : '◀ Panel';
        btn.title = isCollapsed ? 'Mostrar panel lateral de configuración' : 'Ocultar panel lateral';
      }
      saveWorkspaceState();
    }

    function setViewport(width, btnEl) {
      currentViewportWidth = width || '100%';
      const windowEl = document.getElementById('sandboxWindow');
      if (windowEl) windowEl.style.maxWidth = currentViewportWidth;
      document.querySelectorAll('.ts-view-btn').forEach(btn => btn.classList.remove('active'));
      if (btnEl) {
        btnEl.classList.add('active');
      } else {
        const matchedBtn = Array.from(document.querySelectorAll('.ts-view-btn')).find((b) => {
          const oncl = b.getAttribute('onclick') || '';
          return oncl.includes(`'${currentViewportWidth}'`) || oncl.includes(`"${currentViewportWidth}"`);
        });
        if (matchedBtn) {
          matchedBtn.classList.add('active');
        } else if (typeof event !== 'undefined' && event && event.target && event.target.classList) {
          event.target.classList.add('active');
        }
      }
      if (iframeScrollInstance) {
        setTimeout(() => iframeScrollInstance.refresh(), 260);
      }
      if (currentStageMode === 'charts') {
        refitAllChartsInSandbox();
        setTimeout(refitAllChartsInSandbox, 270);
      } else if (currentStageMode === 'inputs') {
        const pxMatch = String(currentViewportWidth || '').match(/^(\d+)px$/i);
        const autoMobile = !!(pxMatch && parseInt(pxMatch[1], 10) <= 576);
        const inputView = document.getElementById('inputSandboxView');
        const activeTouch = inputSandboxTouchSim || autoMobile;
        if (inputView) inputView.classList.toggle('is-touch-simulated', activeTouch);
        document.body.classList.toggle('is-touch-simulated', activeTouch);
        if (typeof window.FmeUi?.syncAllFloatingScrolls === 'function') {
          setTimeout(() => window.FmeUi.syncAllFloatingScrolls(), 260);
        }
      }
      if (typeof syncStudioFloatingScrolls === 'function') {
        setTimeout(syncStudioFloatingScrolls, 220);
      }
      saveWorkspaceState();
    }

    function switchTab(tabId, btnEl) {
      currentTabId = tabId || 'themes';
      document.querySelectorAll('.ts-tab-btn').forEach(btn => btn.classList.remove('active'));
      document.querySelectorAll('.ts-tab-content').forEach(c => c.classList.remove('active'));

      const resolvedBtn = btnEl || document.querySelector(`.ts-tab-btn[data-tab-id="${currentTabId}"]`) || (typeof event !== 'undefined' && event ? event.target : null);
      if (resolvedBtn && resolvedBtn.classList) {
        resolvedBtn.classList.add('active');
      }
      const target = document.getElementById('tab-' + currentTabId);
      if (target) target.classList.add('active');

      if (currentTabId === 'json') {
        updateJsonViewer();
        if (currentStageMode === 'jsmodules' || currentStageMode === 'config') {
          setStageMode('site');
        }
      } else if (currentTabId === 'charts') {
        setStageMode('charts');
      } else if (currentTabId === 'inputs') {
        setStageMode('inputs');
      } else if (currentTabId === 'icons') {
        setStageMode('icons');
      } else if (currentTabId === 'jsmodules') {
        setStageMode('jsmodules');
      } else if (currentTabId === 'config') {
        setStageMode('config');
      } else if (currentStageMode === 'jsmodules' || currentStageMode === 'config') {
        setStageMode('site');
      }
      if (typeof syncStudioFloatingScrolls === 'function') {
        requestAnimationFrame(syncStudioFloatingScrolls);
      }
      saveWorkspaceState();
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
          requestChartRender('all', true);
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
      ensureChartsConfig();
      ensureInputsConfig();

      renderColorSlots('light');
      renderColorSlots('dark');
      syncFormValues();
      syncChartStudioForm();
      syncInputStudioForm();
      renderThemesTab();
      applySandboxStyles();
      updateJsonViewer();
      updateChartCodeOutput();
      requestChartRender('all', true);
      requestInputRender('all', true);

      if (!isRestoringWorkspace) {
        showToast(`🎨 Tema cargado: ${themeConfig.name || slug}`);
      }
      saveWorkspaceState();
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
            if (fullThemeConfig.themes && fullThemeConfig.themes[currentThemeSlug]) {
              themeConfig = JSON.parse(JSON.stringify(fullThemeConfig.themes[currentThemeSlug]));
              ensureChartsConfig();
              ensureInputsConfig();
            }
          }
          if (data.activeThemeSlug) {
            activeThemeSlug = data.activeThemeSlug;
          }
          renderThemesTab();
          syncChartStudioForm();
          syncInputStudioForm();
          applySandboxStyles();
          updateJsonViewer();
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

    // ==============================================================
    // CHART STUDIO (CREADOR Y CONFIGURADOR DE GRÁFICOS)
    // ==============================================================

    const CHART_CATALOG = [
      { type: 'arcMeter', title: 'Arc Meter', icon: '🎯', dataKeys: ['value', 'label'], defaultBarWidth: 16, hasRx: false, hasAxes: false },
      { type: 'stackedTones', title: 'Stacked Tones', icon: '📊', dataKeys: ['quarters'], defaultBarWidth: 24, hasRx: true, hasAxes: false },
      { type: 'tileTreemap', title: 'Tile Treemap', icon: '🧱', dataKeys: ['tiles'], defaultBarWidth: 18, hasRx: false, hasAxes: false },
      { type: 'hybridSpline', title: 'Hybrid Spline', icon: '📈', dataKeys: ['values', 'labels'], defaultBarWidth: 14, hasRx: true, hasAxes: false },
      { type: 'pillPillars', title: 'Pill Pillars', icon: '🏛️', dataKeys: ['pairs'], defaultBarWidth: 12, hasRx: true, hasAxes: false },
      { type: 'simpleBars', title: 'Simple Bars', icon: '📶', dataKeys: ['bars'], defaultBarWidth: 18, hasRx: true, hasAxes: true },
      { type: 'horizontalBars', title: 'Horizontal Bars', icon: '📏', dataKeys: ['bars'], defaultBarWidth: 18, hasRx: true, hasAxes: true },
      { type: 'bulletTarget', title: 'Bullet Target', icon: '🎚️', dataKeys: ['targets'], defaultBarWidth: 14, hasRx: true, hasAxes: false },
      { type: 'roundedDonut', title: 'Rounded Donut', icon: '🍩', dataKeys: ['items'], defaultBarWidth: 20, hasRx: false, hasAxes: false },
      { type: 'pyramidStack', title: 'Pyramid Stack', icon: '🔺', dataKeys: ['items'], defaultBarWidth: 26, hasRx: true, hasAxes: false },
      { type: 'splineDynamics', title: 'Spline Dynamics', icon: '〰️', dataKeys: ['primary', 'secondary'], defaultBarWidth: 16, hasRx: false, hasAxes: false },
      { type: 'matrixHeatmap', title: 'Matrix Heatmap', icon: '🟩', dataKeys: ['rows', 'cols', 'matrix'], defaultBarWidth: 24, hasRx: true, hasAxes: true },
    ];

    const DEFAULT_CHARTS_CONFIG = {
      color: 'texto',
      colorLabel: 'textw',
      axisLabel: 'texto',
      axisLabelSize: null,
      transition: 400,
      tooltip: true,
      barWidth: null,
      yLabelPosition: 'default',
      width: null,
      height: null,
    };

    const CHART_LOCAL_DEFAULTS = {
      arcMeter: { value: 90, label: 'Optimal Load', showNumber: true, showLabel: true },
      tileTreemap: { gap: 8 },
      hybridSpline: { tension: 0.25 },
      pillPillars: { pillarGap: null },
      simpleBars: { showAxisY: true, showAxisX: true, valueSuffix: '' },
      horizontalBars: { showAxisY: true, showAxisX: true, valueSuffix: '' },
      bulletTarget: { targetColor: '#10b981' },
      roundedDonut: { startAngle: -135, centerValue: '', centerLabel: 'Mono Arc', showCenter: true, showLegend: true },
      pyramidStack: { gapY: 9, minWidth: 84, maxWidth: 254 },
      splineDynamics: { tension: 0.25, showSecondary: true, showPoints: true },
      matrixHeatmap: { cellSize: null, cellGap: null, interpolate: false, colLabelPosition: 'bottom', showAxisY: true, showAxisX: true },
    };

    let currentStageMode = 'site'; // 'site' | 'charts'
    let chartScope = 'global'; // 'global' | 'local'
    let activeChartType = 'horizontalBars';
    let chartSandboxViewMode = 'gallery'; // 'gallery' | 'focused'
    let chartDatasetPreset = 'standard';
    let chartCardSurface = 'var(--body)';
    let chartFocusedWidth = '100%';
    let chartCodeFormat = 'helper'; // 'helper' | 'module' | 'global'

    let chartsHtmlCache = Object.assign({}, initialChartsHtmlMap || {});
    let chartsSampleDataCache = Object.assign({}, initialChartsSampleData || {});
    let chartRenderTimer = null;
    let chartRenderAbort = null;
    let chartRenderSeq = 0;

    function ensureChartsConfig() {
      if (!themeConfig.charts || typeof themeConfig.charts !== 'object' || Array.isArray(themeConfig.charts)) {
        themeConfig.charts = JSON.parse(JSON.stringify(DEFAULT_CHARTS_CONFIG));
      } else {
        for (const [k, v] of Object.entries(DEFAULT_CHARTS_CONFIG)) {
          if (themeConfig.charts[k] === undefined) {
            themeConfig.charts[k] = v;
          }
        }
      }
      if (!themeConfig.charts.localOverrides || typeof themeConfig.charts.localOverrides !== 'object') {
        themeConfig.charts.localOverrides = {};
      }
      return themeConfig.charts;
    }

    function initChartStudio() {
      ensureChartsConfig();
      renderChartTypeGrid();
      renderChartColorChips();
      syncChartStudioForm();
      renderChartSandboxFromCache();
      updateChartCodeOutput();
    }

    function setStageMode(mode) {
      currentStageMode = (mode === 'charts' || mode === 'inputs' || mode === 'icons' || mode === 'jsmodules' || mode === 'config') ? mode : 'site';
      const isToolStage = (currentStageMode === 'jsmodules' || currentStageMode === 'config');
      const btnSite = document.getElementById('btnStageSite');
      const btnCharts = document.getElementById('btnStageCharts');
      const btnInputs = document.getElementById('btnStageInputs');
      const btnIcons = document.getElementById('btnStageIcons');
      const btnJsMod = document.getElementById('btnStageJsModules');
      const btnConfig = document.getElementById('btnStageConfig');
      const sandboxWindow = document.getElementById('sandboxWindow');
      const viewportBar = document.getElementById('viewportBar');
      const iframe = document.getElementById('projectPreviewFrame');
      const chartView = document.getElementById('chartSandboxView');
      const inputView = document.getElementById('inputSandboxView');
      const iconView = document.getElementById('iconSandboxView');
      const jsModView = document.getElementById('jsModulesSandboxView');
      const cfgView = document.getElementById('configSandboxView');
      const routeBar = document.getElementById('projectRouteBar');
      const chartStatus = document.getElementById('chartBarStatus');
      const inputStatus = document.getElementById('inputBarStatus');
      const iconStatus = document.getElementById('iconBarStatus');
      const jsModStatus = document.getElementById('jsModulesBarStatus');
      const cfgStatus = document.getElementById('configBarStatus');

      if (btnSite) btnSite.classList.toggle('active', currentStageMode === 'site');
      if (btnCharts) btnCharts.classList.toggle('active', currentStageMode === 'charts');
      if (btnInputs) btnInputs.classList.toggle('active', currentStageMode === 'inputs');
      if (btnIcons) btnIcons.classList.toggle('active', currentStageMode === 'icons');
      if (btnJsMod) btnJsMod.classList.toggle('active', currentStageMode === 'jsmodules');
      if (btnConfig) btnConfig.classList.toggle('active', currentStageMode === 'config');

      if (sandboxWindow) sandboxWindow.style.display = isToolStage ? 'none' : '';
      if (viewportBar) viewportBar.style.display = isToolStage ? 'none' : 'flex';
      if (iframe) iframe.style.display = currentStageMode === 'site' ? 'block' : 'none';
      if (chartView) chartView.style.display = currentStageMode === 'charts' ? 'flex' : 'none';
      if (inputView) inputView.style.display = currentStageMode === 'inputs' ? 'flex' : 'none';
      if (iconView) iconView.style.display = currentStageMode === 'icons' ? 'flex' : 'none';
      if (jsModView) jsModView.style.display = currentStageMode === 'jsmodules' ? 'flex' : 'none';
      if (cfgView) cfgView.style.display = currentStageMode === 'config' ? 'flex' : 'none';
      if (routeBar) routeBar.style.display = currentStageMode === 'site' ? 'flex' : 'none';
      if (chartStatus) chartStatus.style.display = currentStageMode === 'charts' ? 'flex' : 'none';
      if (inputStatus) inputStatus.style.display = currentStageMode === 'inputs' ? 'flex' : 'none';
      if (iconStatus) iconStatus.style.display = currentStageMode === 'icons' ? 'flex' : 'none';
      if (jsModStatus) jsModStatus.style.display = currentStageMode === 'jsmodules' ? 'flex' : 'none';
      if (cfgStatus) cfgStatus.style.display = currentStageMode === 'config' ? 'flex' : 'none';

      if (isToolStage && currentTabId !== currentStageMode) {
        currentTabId = currentStageMode;
        document.querySelectorAll('.ts-tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.ts-tab-content').forEach(c => c.classList.remove('active'));
        const sideBtn = document.querySelector(`.ts-tab-btn[data-tab-id="${currentStageMode}"]`);
        if (sideBtn) sideBtn.classList.add('active');
        const sideTab = document.getElementById('tab-' + currentStageMode);
        if (sideTab) sideTab.classList.add('active');
      }

      document.body.classList.toggle('is-touch-simulated', currentStageMode === 'inputs' && !!inputSandboxTouchSim);

      if (currentStageMode === 'charts') {
        injectLiveThemeIntoChartSandbox();
        renderChartSandboxFromCache();
        setTimeout(() => {
          const stage = document.getElementById('chartSandboxStage');
          initRenderedCharts(stage);
        }, 20);
      } else if (currentStageMode === 'inputs') {
        injectLiveThemeIntoInputSandbox();
        if (inputSandboxViewMode === 'formBuilder') {
          renderFormBuilderView();
          renderFormBuilderInspector();
          requestFormBuilderRender();
        } else {
          renderInputSandboxFromCache();
          setTimeout(() => {
            initInputSandboxDom();
          }, 20);
        }
      } else if (currentStageMode === 'icons') {
        injectLiveThemeIntoIconSandbox();
        refreshIconsCatalog(false);
      } else if (currentStageMode === 'jsmodules' && typeof loadJsModulesCatalog === 'function') {
        loadJsModulesCatalog(false);
      } else if (currentStageMode === 'config' && typeof loadConfigState === 'function') {
        loadConfigState(false);
      }
      if (typeof syncStudioFloatingScrolls === 'function') {
        requestAnimationFrame(syncStudioFloatingScrolls);
      }
      saveWorkspaceState();
    }

    function setChartSandboxViewMode(viewMode) {
      chartSandboxViewMode = (viewMode === 'focused') ? 'focused' : 'gallery';
      const btnGallery = document.getElementById('btnCsViewGallery');
      const btnFocused = document.getElementById('btnCsViewFocused');
      const galleryEl = document.getElementById('csGalleryContainer');
      const focusedEl = document.getElementById('csFocusedContainer');
      const widthWrap = document.getElementById('csFocusedWidthWrap');

      if (btnGallery) btnGallery.classList.toggle('active', chartSandboxViewMode === 'gallery');
      if (btnFocused) btnFocused.classList.toggle('active', chartSandboxViewMode === 'focused');
      if (galleryEl) galleryEl.style.display = chartSandboxViewMode === 'gallery' ? 'grid' : 'none';
      if (focusedEl) focusedEl.style.display = chartSandboxViewMode === 'focused' ? 'flex' : 'none';
      if (widthWrap) widthWrap.style.display = chartSandboxViewMode === 'focused' ? 'flex' : 'none';

      renderChartSandboxFromCache();
      setTimeout(() => {
        const container = chartSandboxViewMode === 'gallery' ? galleryEl : focusedEl;
        initRenderedCharts(container);
      }, 20);
      saveWorkspaceState();
    }

    function setFocusedCardWidth(widthVal) {
      chartFocusedWidth = widthVal || '100%';
      const card = document.querySelector('#csFocusedContainer .cs-focused-card');
      if (card) {
        card.style.maxWidth = chartFocusedWidth;
        refitAllChartsInSandbox(card);
        setTimeout(() => refitAllChartsInSandbox(card), 270);
      }
      saveWorkspaceState();
    }

    function setChartCardSurface(surfaceVal) {
      chartCardSurface = surfaceVal || 'var(--body)';
      injectLiveThemeIntoChartSandbox();
      saveWorkspaceState();
    }

    function setChartScope(scope) {
      chartScope = (scope === 'local') ? 'local' : 'global';
      const btnGlobal = document.getElementById('btnScopeGlobal');
      const btnLocal = document.getElementById('btnScopeLocal');
      const scopeInfo = document.getElementById('csScopeInfo');

      if (btnGlobal) btnGlobal.classList.toggle('active', chartScope === 'global');
      if (btnLocal) btnLocal.classList.toggle('active', chartScope === 'local');
      if (scopeInfo) {
        scopeInfo.textContent = chartScope === 'local' ? `Local: ${activeChartType}` : 'Global (configStyle)';
        scopeInfo.className = `cs-local-badge ${chartScope === 'local' ? 'is-local' : 'is-global'}`;
      }

      syncChartStudioForm();
      updateChartCodeOutput();
    }

    function selectActiveChart(chartType, autoFocusIfGallery = false) {
      if (!CHART_CATALOG.some(c => c.type === chartType)) return;
      activeChartType = chartType;

      const badge = document.getElementById('csActiveChartBadge');
      if (badge) badge.textContent = chartType;

      const scopeInfo = document.getElementById('csScopeInfo');
      if (scopeInfo && chartScope === 'local') {
        scopeInfo.textContent = `Local: ${activeChartType}`;
      }

      renderChartTypeGrid();
      syncChartStudioForm();
      updateChartCodeOutput();

      if (autoFocusIfGallery && chartSandboxViewMode === 'gallery') {
        setChartSandboxViewMode('focused');
      } else {
        document.querySelectorAll('#csGalleryContainer .cs-chart-card').forEach(card => {
          card.classList.toggle('active', card.dataset.chartType === activeChartType);
        });
        if (chartSandboxViewMode === 'focused') {
          renderChartSandboxFromCache();
          const focusedEl = document.getElementById('csFocusedContainer');
          initRenderedCharts(focusedEl);
        }
      }
      saveWorkspaceState();
    }

    function getLocalOverridesMap(createIfMissing = false) {
      const cfg = ensureChartsConfig();
      if (!cfg.localOverrides[activeChartType]) {
        if (!createIfMissing) return {};
        cfg.localOverrides[activeChartType] = {};
      }
      return cfg.localOverrides[activeChartType];
    }

    function getEffectiveChartValue(prop) {
      const cfg = ensureChartsConfig();
      const localMap = cfg.localOverrides[activeChartType] || {};
      if (localMap[prop] !== undefined) {
        return { value: localMap[prop], isLocal: true };
      }
      return { value: cfg[prop] !== undefined ? cfg[prop] : DEFAULT_CHARTS_CONFIG[prop], isLocal: false };
    }

    function clearActiveChartLocalOverrides() {
      const cfg = ensureChartsConfig();
      delete cfg.localOverrides[activeChartType];
      syncChartStudioForm();
      updateJsonViewer();
      updateChartCodeOutput();
      requestChartRender(activeChartType, true);
      showToast(`↺ Overrides locales de "${activeChartType}" limpiados`);
    }

    function renderChartTypeGrid() {
      const grid = document.getElementById('csChartTypeGrid');
      if (!grid) return;
      const cfg = ensureChartsConfig();

      grid.innerHTML = CHART_CATALOG.map(c => {
        const hasOverrides = cfg.localOverrides[c.type] && Object.keys(cfg.localOverrides[c.type]).length > 0;
        return `
          <button type="button" class="cs-type-btn ${c.type === activeChartType ? 'active' : ''}" onclick="selectActiveChart('${c.type}')" title="Seleccionar ${c.title}">
            <span>${c.icon}</span>
            <span class="cs-type-btn-text">
              <span class="cs-type-btn-name">${c.title}${hasOverrides ? ' •' : ''}</span>
              <span class="cs-type-btn-method">${c.type}</span>
            </span>
          </button>
        `;
      }).join('');
    }

    function getActiveThemePaletteForCharts() {
      const isDark = (currentPreviewMode === 'dark');
      const lightColors = themeConfig.light.colors || [];
      const colors = isDark
        ? (themeConfig.dark.colors && themeConfig.dark.colors.length ? themeConfig.dark.colors : lightColors.map(c => invertToDarkHex(c)))
        : lightColors;
      const textPrimary = isDark ? themeConfig.dark.text.primary : themeConfig.light.text.primary;
      const textSecondary = isDark ? themeConfig.dark.text.secondary : themeConfig.light.text.secondary;
      return { colors, textPrimary, textSecondary };
    }

    function resolveColorTokenPreviewHex(rawToken) {
      const token = String(rawToken || '').trim();
      if (/^#[0-9A-Fa-f]{6}$/.test(token)) return token.toUpperCase();
      if (/^#[0-9A-Fa-f]{3}$/.test(token)) {
        return ('#' + token[1] + token[1] + token[2] + token[2] + token[3] + token[3]).toUpperCase();
      }

      const { colors, textPrimary, textSecondary } = getActiveThemePaletteForCharts();
      const lower = token.replace(/^\./, '').toLowerCase();
      if (lower === 'texto') return textPrimary || '#00124D';
      if (lower === 'textc') return textSecondary || '#FFF9E5';
      if (lower === 'textw') return '#FFFFFF';
      if (lower === 'textb') return '#000000';
      if (lower === 'color') return colors[0] || '#38BDF8';

      const colorClassMatch = lower.match(/^color(\d+)$/);
      if (colorClassMatch) {
        const idx = parseInt(colorClassMatch[1], 10) - 1;
        return colors[idx] || colors[0] || '#38BDF8';
      }

      const varMatch = token.match(/^var\(--back-color(\d*)\)$/i);
      if (varMatch) {
        const idx = varMatch[1] ? (parseInt(varMatch[1], 10) - 1) : 0;
        return colors[idx] || colors[0] || '#38BDF8';
      }

      return '#38BDF8';
    }

    function renderChartColorChips() {
      const { colors, textPrimary, textSecondary } = getActiveThemePaletteForCharts();
      const baseTokens = [
        { token: 'texto', dot: textPrimary },
        { token: 'textw', dot: '#FFFFFF' },
        { token: 'textc', dot: textSecondary },
        { token: 'textb', dot: '#000000' },
      ];
      colors.forEach((hex, idx) => {
        baseTokens.push({ token: `color${idx + 1}`, dot: hex });
      });

      ['color', 'colorLabel', 'axisLabel'].forEach(prop => {
        const container = document.getElementById(`csColorChips_${prop}`);
        if (!container) return;
        const currentVal = String(getEffectiveChartValue(prop).value || '').replace(/^\./, '');
        container.innerHTML = baseTokens.map(item => `
          <button type="button" class="cs-color-chip ${currentVal === item.token ? 'active' : ''}" onclick="onChartColorInput('${prop}', '${item.token}')" title="Usar clase .${item.token}">
            <span class="cs-chip-dot" style="background:${item.dot};"></span>
            <span>${item.token}</span>
          </button>
        `).join('');
      });

      const tgtContainer = document.getElementById('csColorChips_targetColor');
      if (tgtContainer) {
        const localTgt = String(getLocalOverridesMap(false).targetColor || '#10b981').replace(/^\./, '');
        const tgtTokens = [
          { token: '#10b981', dot: '#10b981' },
          { token: themeConfig.status?.success || '#00B900', dot: themeConfig.status?.success || '#00B900' },
          { token: themeConfig.status?.caution || '#FF8C00', dot: themeConfig.status?.caution || '#FF8C00' },
          { token: themeConfig.status?.danger || '#FF0000', dot: themeConfig.status?.danger || '#FF0000' },
          ...colors.slice(0, 6).map((hex, i) => ({ token: `var(--back-color${i === 0 ? '' : i + 1})`, label: `back${i + 1}`, dot: hex }))
        ];
        tgtContainer.innerHTML = tgtTokens.map(item => `
          <button type="button" class="cs-color-chip ${localTgt === item.token ? 'active' : ''}" onclick="onChartLocalColorInput('targetColor', '${item.token}')">
            <span class="cs-chip-dot" style="background:${item.dot};"></span>
            <span>${item.label || item.token}</span>
          </button>
        `).join('');
      }
    }

    function setScopedChartProperty(prop, value) {
      const cfg = ensureChartsConfig();
      if (chartScope === 'local') {
        const localMap = getLocalOverridesMap(true);
        localMap[prop] = value;
      } else {
        cfg[prop] = value;
        if (cfg.localOverrides[activeChartType] && cfg.localOverrides[activeChartType][prop] !== undefined) {
          delete cfg.localOverrides[activeChartType][prop];
        }
      }
    }

    function onChartColorInput(prop, val) {
      const trimmed = String(val ?? '').trim() || DEFAULT_CHARTS_CONFIG[prop];
      setScopedChartProperty(prop, trimmed);

      const inputEl = document.getElementById(`csColorInput_${prop}`);
      const pickerEl = document.getElementById(`csColorPicker_${prop}`);
      if (inputEl && inputEl.value !== trimmed) inputEl.value = trimmed;
      if (pickerEl) pickerEl.value = resolveColorTokenPreviewHex(trimmed);

      updateScopeBadge(`csColorBadge_${prop}`, getEffectiveChartValue(prop).isLocal);
      renderChartColorChips();
      renderChartTypeGrid();
      updateJsonViewer();
      updateChartCodeOutput();
      requestChartRender(chartScope === 'local' ? activeChartType : 'all');
    }

    function onChartLocalColorInput(prop, val) {
      const trimmed = String(val ?? '').trim();
      onChartLocalParam(prop, trimmed === '' ? null : trimmed);
      renderChartColorChips();
    }

    function onChartScopedMeasure(prop, rawVal, keepZero = false) {
      let parsed = null;
      if (rawVal !== null && rawVal !== undefined && String(rawVal).trim() !== '') {
        const num = Number(rawVal);
        if (Number.isFinite(num) && (keepZero ? num >= 0 : num > 0)) {
          parsed = Math.round(num);
        }
      }
      setScopedChartProperty(prop, parsed);
      syncChartStudioForm();
      renderChartTypeGrid();
      updateJsonViewer();
      updateChartCodeOutput();
      requestChartRender(chartScope === 'local' ? activeChartType : 'all');
    }

    function onChartScopedBool(prop, checked) {
      setScopedChartProperty(prop, !!checked);
      syncChartStudioForm();
      renderChartTypeGrid();
      updateJsonViewer();
      updateChartCodeOutput();
      requestChartRender(chartScope === 'local' ? activeChartType : 'all');
    }

    function onChartLocalParam(prop, val) {
      const cfg = ensureChartsConfig();
      if (val === null || val === undefined || val === '') {
        if (cfg.localOverrides[activeChartType]) {
          delete cfg.localOverrides[activeChartType][prop];
          if (Object.keys(cfg.localOverrides[activeChartType]).length === 0) {
            delete cfg.localOverrides[activeChartType];
          }
        }
      } else {
        const localMap = getLocalOverridesMap(true);
        localMap[prop] = val;
      }

      syncChartStudioForm();
      renderChartTypeGrid();
      updateJsonViewer();
      updateChartCodeOutput();
      requestChartRender(activeChartType);
    }

    function setChartDatasetPreset(preset) {
      chartDatasetPreset = ['standard', 'compact', 'dense', 'long-labels'].includes(preset) ? preset : 'standard';
      const s1 = document.getElementById('csDatasetPresetSelect');
      const s2 = document.getElementById('csToolbarPresetSelect');
      if (s1) s1.value = chartDatasetPreset;
      if (s2) s2.value = chartDatasetPreset;
      updateChartCodeOutput();
      requestChartRender('all', true);
    }

    // ==============================================================
    // CONSTRUCTOR VISUAL yLabelPosition
    // ==============================================================

    function parseYLabelPositionJs(raw) {
      const def = { row: 'default', labelPct: 50, chartPct: 50, align: 'start', hasSplit: false };
      if (!raw || raw === 'default') return def;

      const rowAligns = ['default', 'between', 'around', 'start', 'left', 'center', 'end', 'right'];
      const textAligns = ['start', 'left', 'center', 'middle', 'end', 'right'];

      if (typeof raw === 'object' && !Array.isArray(raw)) {
        const out = { ...def };
        if (raw.row) out.row = String(raw.row).toLowerCase();
        if (Array.isArray(raw.split) && raw.split.length >= 2) {
          out.labelPct = parseFloat(raw.split[0]) || 50;
          out.chartPct = parseFloat(raw.split[1]) || 50;
          out.hasSplit = true;
        } else if (raw.labelPct !== undefined && raw.chartPct !== undefined) {
          out.labelPct = parseFloat(raw.labelPct) || 50;
          out.chartPct = parseFloat(raw.chartPct) || 50;
          out.hasSplit = true;
        }
        if (raw.align) out.align = String(raw.align).toLowerCase();
        return out;
      }

      const str = String(raw).trim();
      const res = { ...def };
      let rowSet = false;
      const pctMatch = str.match(/(?:\[\s*)?(\d+(?:\.\d+)?)\s*%?\s*,\s*(\d+(?:\.\d+)?)\s*%?(?:\s*,\s*([a-zA-Z]+))?(?:\s*\])?/);
      if (pctMatch) {
        res.labelPct = parseFloat(pctMatch[1]) || 50;
        res.chartPct = parseFloat(pctMatch[2]) || 50;
        res.hasSplit = true;
        if (pctMatch[3] && textAligns.includes(pctMatch[3].toLowerCase())) {
          res.align = pctMatch[3].toLowerCase();
        }
        const before = str.slice(0, pctMatch.index).split(/[\[\],\s]+/).filter(Boolean);
        const after = str.slice(pctMatch.index + pctMatch[0].length).split(/[\[\],\s]+/).filter(Boolean);
        before.forEach(tk => {
          const t = tk.toLowerCase();
          if (!rowSet && rowAligns.includes(t)) { res.row = t; rowSet = true; }
          else if (textAligns.includes(t)) res.align = t;
        });
        after.forEach(tk => {
          const t = tk.toLowerCase();
          if (textAligns.includes(t)) res.align = t;
          else if (!rowSet && rowAligns.includes(t)) { res.row = t; rowSet = true; }
        });
      } else {
        const tokens = str.split(/[\[\],\s]+/).filter(Boolean);
        tokens.forEach(tk => {
          const t = tk.toLowerCase();
          if (!rowSet && rowAligns.includes(t)) { res.row = t; rowSet = true; }
          else if (textAligns.includes(t)) res.align = t;
        });
      }

      if (res.row === 'left') res.row = 'start';
      if (res.row === 'right') res.row = 'end';
      if (res.align === 'left') res.align = 'start';
      if (res.align === 'right') res.align = 'end';
      if (res.align === 'middle') res.align = 'center';
      return res;
    }

    function serializeYLabelPosition(parsed) {
      const row = parsed.row || 'default';
      const align = parsed.align || 'start';
      if (!parsed.hasSplit) {
        if (row === 'default' && align === 'start') return 'default';
        if (align === 'start') return row;
        return `[${row}],[${align}]`;
      }
      const lp = Math.max(10, Math.min(85, Math.round(parsed.labelPct || 50)));
      const cp = Math.max(15, Math.min(90, Math.round(parsed.chartPct || 40)));
      return `[${row}],[${lp}, ${cp}, ${align}]`;
    }

    function updateYLabelPositionState(mutator) {
      const eff = getEffectiveChartValue('yLabelPosition');
      const parsed = parseYLabelPositionJs(eff.value);
      mutator(parsed);
      const serialized = serializeYLabelPosition(parsed);
      setScopedChartProperty('yLabelPosition', serialized);
      syncYLabelPositionUi(serialized, chartScope === 'local');
      renderChartTypeGrid();
      updateJsonViewer();
      updateChartCodeOutput();
      requestChartRender(chartScope === 'local' ? activeChartType : 'all');
    }

    function setYPosRow(row) {
      updateYLabelPositionState(p => { p.row = row; });
    }

    function setYPosHasSplit(checked) {
      updateYLabelPositionState(p => {
        p.hasSplit = !!checked;
        if (p.hasSplit && p.labelPct + p.chartPct > 100) {
          p.labelPct = 50;
          p.chartPct = 40;
        }
      });
    }

    function setYPosSplitPct(which, rawVal) {
      const val = Math.max(10, Math.min(90, parseInt(rawVal, 10) || 40));
      updateYLabelPositionState(p => {
        p.hasSplit = true;
        if (which === 'label') {
          p.labelPct = Math.min(85, val);
          if (p.labelPct + p.chartPct > 100) {
            p.chartPct = Math.max(15, 100 - p.labelPct);
          }
        } else {
          p.chartPct = Math.min(90, Math.max(15, val));
          if (p.labelPct + p.chartPct > 100) {
            p.labelPct = Math.max(10, 100 - p.chartPct);
          }
        }
      });
    }

    function setYPosAlign(align) {
      updateYLabelPositionState(p => { p.align = align; });
    }

    function syncYLabelPositionUi(rawYPos, isLocal) {
      const parsed = parseYLabelPositionJs(rawYPos);
      const serialized = serializeYLabelPosition(parsed);

      updateScopeBadge('csYPosBadge', isLocal);

      document.querySelectorAll('#csYPosRowGrid .cs-seg-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.row === parsed.row);
      });
      document.querySelectorAll('#csYPosAlignGrid .cs-seg-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.align === parsed.align);
      });

      const chkSplit = document.getElementById('csYPosHasSplit');
      const splitWrap = document.getElementById('csYPosSplitControls');
      if (chkSplit) chkSplit.checked = !!parsed.hasSplit;
      if (splitWrap) splitWrap.style.display = parsed.hasSplit ? 'block' : 'none';

      const lp = Math.round(parsed.labelPct);
      const cp = Math.round(parsed.chartPct);
      const gap = Math.max(0, 100 - lp - cp);

      const lpRange = document.getElementById('csYPosLabelPctRange');
      const lpNum = document.getElementById('csYPosLabelPctNum');
      const cpRange = document.getElementById('csYPosChartPctRange');
      const cpNum = document.getElementById('csYPosChartPctNum');
      if (lpRange) lpRange.value = lp;
      if (lpNum) lpNum.value = lp;
      if (cpRange) cpRange.value = cp;
      if (cpNum) cpNum.value = cp;

      const segL = document.getElementById('csSplitSegLabel');
      const segG = document.getElementById('csSplitSegGap');
      const segC = document.getElementById('csSplitSegChart');
      if (segL) { segL.style.width = `${lp}%`; segL.textContent = `Nombres ${lp}%`; }
      if (segG) { segG.style.width = `${gap}%`; segG.textContent = gap > 8 ? `${gap}%` : ''; }
      if (segC) { segC.style.width = `${cp}%`; segC.textContent = `Barras ${cp}%`; }

      const preview = document.getElementById('csYPosSyntaxPreview');
      if (preview) preview.textContent = serialized;
    }

    function updateScopeBadge(elId, isLocal) {
      const el = document.getElementById(elId);
      if (!el) return;
      el.textContent = isLocal ? 'Local' : 'Global';
      el.className = `cs-local-badge ${isLocal ? 'is-local' : 'is-global'}`;
    }

    function syncChartStudioForm() {
      const cfg = ensureChartsConfig();
      const localMap = cfg.localOverrides[activeChartType] || {};
      const hasAnyOverrides = Object.keys(localMap).length > 0;
      const meta = CHART_CATALOG.find(c => c.type === activeChartType) || CHART_CATALOG[0];

      const localActions = document.getElementById('csLocalActions');
      if (localActions) {
        localActions.style.display = (chartScope === 'local' || hasAnyOverrides) ? 'block' : 'none';
      }

      // Colores
      ['color', 'colorLabel', 'axisLabel'].forEach(prop => {
        const eff = getEffectiveChartValue(prop);
        const inp = document.getElementById(`csColorInput_${prop}`);
        const pick = document.getElementById(`csColorPicker_${prop}`);
        if (inp) inp.value = eff.value || '';
        if (pick) pick.value = resolveColorTokenPreviewHex(eff.value);
        updateScopeBadge(`csColorBadge_${prop}`, eff.isLocal);
      });

      const tgtGroup = document.getElementById('csTargetColorGroup');
      if (tgtGroup) {
        tgtGroup.style.display = activeChartType === 'bulletTarget' ? 'block' : 'none';
        const tgtVal = localMap.targetColor || '#10b981';
        const tgtInp = document.getElementById('csColorInput_targetColor');
        const tgtPick = document.getElementById('csColorPicker_targetColor');
        if (tgtInp) tgtInp.value = tgtVal;
        if (tgtPick) tgtPick.value = resolveColorTokenPreviewHex(tgtVal);
      }
      renderChartColorChips();

      // barWidth
      const bwEff = getEffectiveChartValue('barWidth');
      const bwRange = document.getElementById('csBarWidthRange');
      const bwNum = document.getElementById('csBarWidthNum');
      const bwAuto = document.getElementById('csBarWidthAuto');
      if (bwRange) bwRange.value = bwEff.value || meta.defaultBarWidth || 18;
      if (bwNum) bwNum.value = bwEff.value !== null && bwEff.value !== '' ? bwEff.value : '';
      if (bwAuto) bwAuto.classList.toggle('active', bwEff.value === null || bwEff.value === '');
      updateScopeBadge('csBarWidthBadge', bwEff.isLocal);

      // axisLabelSize
      const asEff = getEffectiveChartValue('axisLabelSize');
      const asRange = document.getElementById('csAxisSizeRange');
      const asNum = document.getElementById('csAxisSizeNum');
      const asAuto = document.getElementById('csAxisSizeAuto');
      if (asRange) asRange.value = asEff.value || 12;
      if (asNum) asNum.value = asEff.value !== null && asEff.value !== '' ? asEff.value : '';
      if (asAuto) asAuto.classList.toggle('active', asEff.value === null || asEff.value === '');
      updateScopeBadge('csAxisSizeBadge', asEff.isLocal);

      // rx (local)
      const rxWrap = document.getElementById('csRxWrapper');
      if (rxWrap) rxWrap.style.display = meta.hasRx ? 'block' : 'none';
      const rxRange = document.getElementById('csRxRange');
      const rxNum = document.getElementById('csRxNum');
      const rxAuto = document.getElementById('csRxAuto');
      const rxVal = localMap.rx !== undefined ? localMap.rx : null;
      const fallbackRx = Math.round((bwEff.value || meta.defaultBarWidth || 18) / 2);
      if (rxRange) rxRange.value = rxVal !== null ? rxVal : fallbackRx;
      if (rxNum) rxNum.value = rxVal !== null ? rxVal : '';
      if (rxAuto) rxAuto.classList.toggle('active', rxVal === null);

      // width / height
      const wEff = getEffectiveChartValue('width');
      const hEff = getEffectiveChartValue('height');
      const wNum = document.getElementById('csWidthNum');
      const hNum = document.getElementById('csHeightNum');
      const wAuto = document.getElementById('csWidthAuto');
      const hAuto = document.getElementById('csHeightAuto');
      if (wNum) wNum.value = wEff.value || '';
      if (hNum) hNum.value = hEff.value || '';
      if (wAuto) wAuto.classList.toggle('active', !wEff.value);
      if (hAuto) hAuto.classList.toggle('active', !hEff.value);
      updateScopeBadge('csDimBadge', wEff.isLocal || hEff.isLocal);

      // Controles contextuales por gráfico
      const toggleCtx = (id, show) => {
        const el = document.getElementById(id);
        if (el) el.style.display = show ? 'block' : 'none';
      };

      toggleCtx('csCtxArcMeter', activeChartType === 'arcMeter');
      if (activeChartType === 'arcMeter') {
        const v = localMap.value !== undefined ? localMap.value : 90;
        document.getElementById('csArcValueRange').value = v;
        document.getElementById('csArcValueNum').value = v;
        document.getElementById('csArcLabel').value = localMap.label !== undefined ? localMap.label : '';
        document.getElementById('csArcShowNumber').checked = localMap.showNumber !== false;
        document.getElementById('csArcShowLabel').checked = localMap.showLabel !== false;
      }

      toggleCtx('csCtxPillPillars', activeChartType === 'pillPillars');
      if (activeChartType === 'pillPillars') {
        const pg = localMap.pillarGap !== undefined ? localMap.pillarGap : null;
        document.getElementById('csPillarGapRange').value = pg !== null ? pg : 4;
        document.getElementById('csPillarGapNum').value = pg !== null ? pg : '';
        document.getElementById('csPillarGapAuto').classList.toggle('active', pg === null);
      }

      toggleCtx('csCtxPyramidStack', activeChartType === 'pyramidStack');
      if (activeChartType === 'pyramidStack') {
        const gy = localMap.gapY !== undefined ? localMap.gapY : 9;
        document.getElementById('csPyramidGapYRange').value = gy;
        document.getElementById('csPyramidGapYNum').value = localMap.gapY !== undefined ? localMap.gapY : '';
        document.getElementById('csPyramidMinW').value = localMap.minWidth !== undefined ? localMap.minWidth : '';
        document.getElementById('csPyramidMaxW').value = localMap.maxWidth !== undefined ? localMap.maxWidth : '';
      }

      toggleCtx('csCtxMatrixHeatmap', activeChartType === 'matrixHeatmap');
      if (activeChartType === 'matrixHeatmap') {
        const cs = localMap.cellSize !== undefined ? localMap.cellSize : null;
        const cg = localMap.cellGap !== undefined ? localMap.cellGap : null;
        document.getElementById('csMatrixCellSizeRange').value = cs !== null ? cs : 24;
        document.getElementById('csMatrixCellSizeNum').value = cs !== null ? cs : '';
        document.getElementById('csMatrixCellSizeAuto').classList.toggle('active', cs === null);
        document.getElementById('csMatrixCellGapRange').value = cg !== null ? cg : 6;
        document.getElementById('csMatrixCellGapNum').value = cg !== null ? cg : '';
        document.getElementById('csMatrixCellGapAuto').classList.toggle('active', cg === null);
        document.getElementById('csMatrixColPos').value = localMap.colLabelPosition || 'bottom';
        document.getElementById('csMatrixInterpolate').checked = !!localMap.interpolate;
      }

      toggleCtx('csCtxTension', activeChartType === 'hybridSpline' || activeChartType === 'splineDynamics');
      if (activeChartType === 'hybridSpline' || activeChartType === 'splineDynamics') {
        const ten = localMap.tension !== undefined ? localMap.tension : 0.25;
        document.getElementById('csTensionRange').value = ten;
        document.getElementById('csTensionNum').value = localMap.tension !== undefined ? localMap.tension : '';
      }

      toggleCtx('csCtxSplineDyn', activeChartType === 'splineDynamics');
      if (activeChartType === 'splineDynamics') {
        document.getElementById('csSplineShowSecondary').checked = localMap.showSecondary !== false;
        document.getElementById('csSplineShowPoints').checked = localMap.showPoints !== false;
      }

      toggleCtx('csCtxDonut', activeChartType === 'roundedDonut');
      if (activeChartType === 'roundedDonut') {
        const sa = localMap.startAngle !== undefined ? localMap.startAngle : -135;
        document.getElementById('csDonutStartAngleRange').value = sa;
        document.getElementById('csDonutStartAngleNum').value = localMap.startAngle !== undefined ? localMap.startAngle : '';
        document.getElementById('csDonutCenterValue').value = localMap.centerValue !== undefined ? localMap.centerValue : '';
        document.getElementById('csDonutCenterLabel').value = localMap.centerLabel !== undefined ? localMap.centerLabel : '';
        document.getElementById('csDonutShowCenter').checked = localMap.showCenter !== false;
        document.getElementById('csDonutShowLegend').checked = localMap.showLegend !== false;
      }

      toggleCtx('csCtxTreemap', activeChartType === 'tileTreemap');
      if (activeChartType === 'tileTreemap') {
        const g = localMap.gap !== undefined ? localMap.gap : 8;
        document.getElementById('csTreemapGapRange').value = g;
        document.getElementById('csTreemapGapNum').value = localMap.gap !== undefined ? localMap.gap : '';
      }

      toggleCtx('csCtxAxes', meta.hasAxes);
      if (meta.hasAxes) {
        document.getElementById('csShowAxisY').checked = localMap.showAxisY !== false;
        document.getElementById('csShowAxisX').checked = localMap.showAxisX !== false;
        const suffixWrap = document.getElementById('csValueSuffixWrap');
        if (suffixWrap) suffixWrap.style.display = (activeChartType === 'simpleBars' || activeChartType === 'horizontalBars') ? 'block' : 'none';
        document.getElementById('csValueSuffix').value = localMap.valueSuffix !== undefined ? localMap.valueSuffix : '';
      }

      // yLabelPosition
      const ypEff = getEffectiveChartValue('yLabelPosition');
      syncYLabelPositionUi(ypEff.value, ypEff.isLocal);

      // transition & tooltip
      const trEff = getEffectiveChartValue('transition');
      document.getElementById('csTransitionRange').value = trEff.value ?? 400;
      document.getElementById('csTransitionNum').value = trEff.value ?? 400;
      updateScopeBadge('csTransitionBadge', trEff.isLocal);

      const ttEff = getEffectiveChartValue('tooltip');
      document.getElementById('csTooltipCheck').checked = ttEff.value !== false;
      updateScopeBadge('csTooltipBadge', ttEff.isLocal);
    }

    function injectLiveThemeIntoChartSandbox() {
      const view = document.getElementById('chartSandboxView');
      if (!view) return;

      const isDark = (currentPreviewMode === 'dark');
      if (isDark) {
        view.setAttribute('data-theme', 'dark');
      } else {
        view.removeAttribute('data-theme');
      }

      const lightColors = themeConfig.light.colors || [];
      const darkColors = (themeConfig.dark.colors && themeConfig.dark.colors.length)
        ? themeConfig.dark.colors
        : lightColors.map(c => invertToDarkHex(c));

      const activeBody = isDark ? themeConfig.dark.body : themeConfig.light.body;
      const activeTextPrimary = isDark ? themeConfig.dark.text.primary : themeConfig.light.text.primary;
      const activeTextSecondary = isDark ? themeConfig.dark.text.secondary : themeConfig.light.text.secondary;
      const activeTextInactive = isDark ? themeConfig.dark.text.inactive : themeConfig.light.text.inactive;
      const activeColors = isDark ? darkColors : lightColors;

      let css = `#chartSandboxView {\n`;
      css += `  --font: ${themeConfig.layout.font};\n`;
      css += `  --font-size: ${themeConfig.layout.fontSize};\n`;
      css += `  --body: ${activeBody};\n`;
      css += `  --text-primary: ${activeTextPrimary};\n`;
      css += `  --text-secondary: ${activeTextSecondary};\n`;
      css += `  --text-inactive: ${activeTextInactive};\n`;
      css += `  --success: ${themeConfig.status.success};\n`;
      css += `  --caution: ${themeConfig.status.caution};\n`;
      css += `  --danger: ${themeConfig.status.danger};\n`;

      activeColors.forEach((hex, i) => {
        const vName = i === 0 ? '--back-color' : `--back-color${i + 1}`;
        css += `  ${vName}: ${hex};\n`;
        if (i === 0) css += `  --back-color1: ${hex};\n`;
      });
      css += `}\n`;

      css += `#chartSandboxView .texto, #chartSandboxView .texto a { color: var(--text-primary) !important; }\n`;
      css += `#chartSandboxView .textw, #chartSandboxView .textw a { color: #ffffff !important; }\n`;
      css += `#chartSandboxView .textc, #chartSandboxView .textc a { color: var(--text-secondary) !important; }\n`;
      css += `#chartSandboxView .textb, #chartSandboxView .textb a { color: #000000 !important; }\n`;
      css += `#chartSandboxView .color-success { color: var(--success) !important; }\n`;
      css += `#chartSandboxView .color-caution { color: var(--caution) !important; }\n`;
      css += `#chartSandboxView .color-danger { color: var(--danger) !important; }\n`;

      activeColors.forEach((_, i) => {
        const idx = i + 1;
        const vName = idx === 1 ? '--back-color' : `--back-color${idx}`;
        css += `#chartSandboxView .back${idx} { background-color: var(${vName}) !important; }\n`;
        css += `#chartSandboxView .color${idx}, #chartSandboxView .color${idx} a { color: var(${vName}) !important; }\n`;
        css += `#chartSandboxView .border${idx} { border: 1px solid var(${vName}); }\n`;
      });

      // Superficie de tarjetas y contraste legible en las cabeceras de tarjeta
      let headTextColor = 'var(--text-primary)';
      if (chartCardSurface === '#0c0c0e') {
        headTextColor = '#e2e8f0';
      } else if (chartCardSurface === '#ffffff') {
        headTextColor = '#0f172a';
      } else if (chartCardSurface.startsWith('var(--back-color')) {
        const resolvedSurface = resolveColorTokenPreviewHex(chartCardSurface);
        const lum = getRelativeLuminance(resolvedSurface);
        headTextColor = lum < 0.35 ? '#f8fafc' : '#0f172a';
      }

      css += `#chartSandboxView .cs-chart-card, #chartSandboxView .cs-focused-card {\n`;
      css += `  background-color: ${chartCardSurface};\n`;
      css += `}\n`;
      css += `#chartSandboxView .cs-chart-card-head, #chartSandboxView .cs-focused-meta {\n`;
      css += `  color: ${headTextColor};\n`;
      css += `}\n`;

      let styleEl = document.getElementById('frame-theme-studio-sandbox-vars');
      if (!styleEl) {
        styleEl = document.createElement('style');
        styleEl.id = 'frame-theme-studio-sandbox-vars';
        document.head.appendChild(styleEl);
      }
      styleEl.textContent = css;
    }

    function getCleanGlobalChartsConfig() {
      const cfg = ensureChartsConfig();
      const clean = {};
      for (const key of Object.keys(DEFAULT_CHARTS_CONFIG)) {
        if (cfg[key] !== undefined && cfg[key] !== null && cfg[key] !== '') {
          clean[key] = cfg[key];
        }
      }
      return clean;
    }

    function requestChartRender(scope = 'all', immediate = false) {
      clearTimeout(chartRenderTimer);
      const delay = immediate ? 0 : 120;

      chartRenderTimer = setTimeout(async () => {
        const mySeq = ++chartRenderSeq;
        if (chartRenderAbort) {
          try { chartRenderAbort.abort(); } catch (e) {}
        }
        chartRenderAbort = typeof AbortController !== 'undefined' ? new AbortController() : null;

        const cfg = ensureChartsConfig();
        const renderAll = (scope === 'all');
        const payload = {
          type: renderAll ? 'all' : activeChartType,
          activeChart: activeChartType,
          globalConfig: getCleanGlobalChartsConfig(),
          localParamsByType: cfg.localOverrides || {},
          localParams: (cfg.localOverrides && cfg.localOverrides[activeChartType]) || {},
          datasetPreset: chartDatasetPreset
        };

        try {
          const response = await fetch('/_frame/theme-studio/render-chart', {
            method: 'POST',
            headers: getStudioHeaders(),
            body: JSON.stringify(payload),
            signal: chartRenderAbort ? chartRenderAbort.signal : undefined
          });
          if (!response.ok) return;
          const res = await response.json();
          if (mySeq !== chartRenderSeq || !res || !res.success) return;

          if (res.type === 'all' && res.charts) {
            chartsHtmlCache = res.charts;
            if (res.sampleData) chartsSampleDataCache = res.sampleData;
            renderChartSandboxFromCache();
            if (currentStageMode === 'charts') {
              const stage = document.getElementById('chartSandboxStage');
              initRenderedCharts(stage);
            }
          } else if (res.html) {
            chartsHtmlCache[res.type] = res.html;
            if (res.sampleData) chartsSampleDataCache[res.type] = res.sampleData;
            updateSingleChartCardInDom(res.type, res.html);
          }
          updateChartCodeOutput();
        } catch (err) {
          if (err && err.name === 'AbortError') return;
        }
      }, delay);
    }

    function updateSingleChartCardInDom(chartType, html) {
      const cardBody = document.querySelector(`#csGalleryContainer .cs-chart-card[data-chart-type="${chartType}"] .cs-chart-card-body`);
      if (cardBody) {
        cardBody.innerHTML = html;
        if (currentStageMode === 'charts' && chartSandboxViewMode === 'gallery') {
          initRenderedCharts(cardBody);
        }
      }

      if (chartSandboxViewMode === 'focused' && activeChartType === chartType) {
        renderChartSandboxFromCache();
        if (currentStageMode === 'charts') {
          const focusedEl = document.getElementById('csFocusedContainer');
          initRenderedCharts(focusedEl);
        }
      }
    }

    function renderChartSandboxFromCache() {
      const galleryEl = document.getElementById('csGalleryContainer');
      const focusedEl = document.getElementById('csFocusedContainer');
      const cfg = ensureChartsConfig();

      if (galleryEl && chartSandboxViewMode === 'gallery') {
        galleryEl.innerHTML = CHART_CATALOG.map(meta => {
          const html = chartsHtmlCache[meta.type] || '';
          const hasLocal = cfg.localOverrides[meta.type] && Object.keys(cfg.localOverrides[meta.type]).length > 0;
          return `
            <div class="cs-chart-card ${meta.type === activeChartType ? 'active' : ''}" data-chart-type="${meta.type}" onclick="selectActiveChart('${meta.type}')">
              <div class="cs-chart-card-head">
                <span class="cs-chart-card-title">${meta.icon} ${meta.title}${hasLocal ? ' <span class="cs-local-badge is-local">Local</span>' : ''}</span>
                <span class="cs-chart-card-method">${meta.type}</span>
              </div>
              <div class="cs-chart-card-body">${html}</div>
            </div>
          `;
        }).join('');
      }

      if (focusedEl && chartSandboxViewMode === 'focused') {
        const meta = CHART_CATALOG.find(c => c.type === activeChartType) || CHART_CATALOG[0];
        const html = chartsHtmlCache[meta.type] || '';
        const effColor = getEffectiveChartValue('color').value;
        const effLabel = getEffectiveChartValue('colorLabel').value;
        const effAxis = getEffectiveChartValue('axisLabel').value;
        const effBw = getEffectiveChartValue('barWidth').value;
        const effAs = getEffectiveChartValue('axisLabelSize').value;
        const effYPos = getEffectiveChartValue('yLabelPosition').value;

        focusedEl.innerHTML = `
          <div class="cs-focused-card" style="max-width: ${chartFocusedWidth};">
            <div class="cs-chart-card-head" style="margin-bottom:14px;">
              <span class="cs-chart-card-title" style="font-size:0.95rem;">${meta.icon} ${meta.title}</span>
              <span class="cs-chart-card-method" style="font-size:0.76rem;">GraphicsModule::${meta.type}()</span>
            </div>
            <div class="cs-chart-card-body" style="min-height:220px;">${html}</div>
            <div class="cs-focused-meta">
              <span>color: <strong>${effColor}</strong></span>
              <span>colorLabel: <strong>${effLabel}</strong></span>
              <span>axisLabel: <strong>${effAxis}</strong></span>
              <span>barWidth: <strong>${effBw ?? 'Auto'}</strong></span>
              <span>axisLabelSize: <strong>${effAs ?? 'Auto'}</strong></span>
              <span>yLabelPosition: <strong>${effYPos}</strong></span>
            </div>
          </div>
        `;
      }
    }

    function refitAllChartsInSandbox(rootEl) {
      const root = rootEl || document.getElementById('chartSandboxStage');
      if (!root || !window.Charts) return;

      const fitters = [
        ['[data-chart="stacked-tones"]', window.Charts.fitStackedTonesToContainer],
        ['[data-chart="hybrid-spline"]', window.Charts.fitHybridSplineToContainer],
        ['[data-chart="pill-pillars"]', window.Charts.fitPillPillarsToContainer],
        ['[data-chart="simple-bars"]', window.Charts.fitSimpleBarsToContainer],
        ['[data-chart="horizontal-bars"]', window.Charts.fitHorizontalBarsToContainer],
        ['[data-chart="bullet-target"]', window.Charts.fitBulletTargetToContainer],
        ['[data-chart="pyramid-stack"]', window.Charts.fitPyramidStackToContainer],
        ['[data-chart="spline-dynamics"]', window.Charts.fitSplineDynamicsToContainer],
      ];

      fitters.forEach(([sel, fn]) => {
        if (typeof fn !== 'function') return;
        root.querySelectorAll(sel).forEach(el => {
          try { fn(el); } catch (e) {}
        });
      });
    }

    function initRenderedCharts(rootEl) {
      const root = rootEl || document.getElementById('chartSandboxStage');
      if (!root) return;

      root.querySelectorAll('[data-chart="matrix-heatmap"]').forEach(el => {
        delete el.dataset.matrixHeatmapInitialized;
      });

      if (typeof window.initCharts === 'function') {
        window.initCharts(root, { force: true });
      }
      refitAllChartsInSandbox(root);
    }

    function replayChartsAnimation() {
      if (currentStageMode !== 'charts') {
        setStageMode('charts');
      }
      const root = document.getElementById('chartSandboxStage');
      initRenderedCharts(root);
    }

    // ==============================================================
    // GENERADOR DE CÓDIGO PHP "COPY & PASTE"
    // ==============================================================

    function setChartCodeFormat(fmt) {
      chartCodeFormat = ['helper', 'module', 'global'].includes(fmt) ? fmt : 'helper';
      ['helper', 'module', 'global'].forEach(key => {
        const tab = document.getElementById(`csCodeTab_${key}`);
        if (tab) tab.classList.toggle('active', chartCodeFormat === key);
      });

      const includeDataLabel = document.getElementById('csCodeIncludeDataLabel');
      const wrapLabel = document.getElementById('csCodeWrapLabel');
      if (includeDataLabel) includeDataLabel.style.display = chartCodeFormat === 'global' ? 'none' : 'inline-flex';
      if (wrapLabel) wrapLabel.style.display = chartCodeFormat === 'global' ? 'none' : 'inline-flex';

      updateChartCodeOutput();
    }

    function toggleChartCodeDock() {
      const dock = document.getElementById('csCodeDock');
      const btn = document.getElementById('btnToggleCodeDock');
      if (!dock) return;
      const collapsed = dock.classList.toggle('collapsed');
      if (btn) btn.textContent = collapsed ? '▸' : '▾';
      saveWorkspaceState();
    }

    function toPhpLiteral(val, indentLevel = 0) {
      const pad = '  '.repeat(indentLevel);
      const innerPad = '  '.repeat(indentLevel + 1);

      if (val === null || val === undefined) return 'null';
      if (typeof val === 'boolean') return val ? 'true' : 'false';
      if (typeof val === 'number') return String(val);
      if (typeof val === 'string') {
        return `'${val.replace(/\\/g, '\\\\').replace(/'/g, "\\'")}'`;
      }

      if (Array.isArray(val)) {
        if (val.length === 0) return '[]';
        const isSimpleScalarList = val.every(item => typeof item !== 'object' || item === null);
        if (isSimpleScalarList && val.length <= 10) {
          return `[${val.map(item => toPhpLiteral(item, 0)).join(', ')}]`;
        }
        const rows = val.map(item => `${innerPad}${toPhpLiteral(item, indentLevel + 1)},`);
        return `[\n${rows.join('\n')}\n${pad}]`;
      }

      if (typeof val === 'object') {
        const entries = Object.entries(val);
        if (entries.length === 0) return '[]';
        const isFlat = entries.every(([, v]) => typeof v !== 'object' || v === null);
        if (isFlat && indentLevel >= 2 && entries.length <= 5) {
          const inlinePairs = entries.map(([k, v]) => `${toPhpLiteral(k)} => ${toPhpLiteral(v, 0)}`);
          return `[${inlinePairs.join(', ')}]`;
        }
        const rows = entries.map(([k, v]) => `${innerPad}${toPhpLiteral(k)} => ${toPhpLiteral(v, indentLevel + 1)},`);
        return `[\n${rows.join('\n')}\n${pad}]`;
      }

      return 'null';
    }

    function buildChartCodeArrayObject(onlyModified, includeData) {
      const cfg = ensureChartsConfig();
      const localMap = cfg.localOverrides[activeChartType] || {};
      const defaultLocal = CHART_LOCAL_DEFAULTS[activeChartType] || {};
      const merged = {};

      if (chartCodeFormat === 'global') {
        for (const [key, defVal] of Object.entries(DEFAULT_CHARTS_CONFIG)) {
          const val = cfg[key];
          if (val === null || val === undefined || val === '') continue;
          if (onlyModified && val === defVal) continue;
          merged[key] = val;
        }
        return merged;
      }

      // Combinar estilo efectivo (global + local overrides)
      const combinedStyle = {};
      for (const [key, defVal] of Object.entries(DEFAULT_CHARTS_CONFIG)) {
        const val = localMap[key] !== undefined ? localMap[key] : cfg[key];
        if (val === null || val === undefined || val === '') continue;
        if (onlyModified && val === defVal) continue;
        combinedStyle[key] = val;
      }

      // Añadir parámetros locales específicos del gráfico
      for (const [key, val] of Object.entries(localMap)) {
        if (DEFAULT_CHARTS_CONFIG[key] !== undefined) continue;
        if (val === null || val === undefined || val === '') continue;
        if (onlyModified && defaultLocal[key] !== undefined && val === defaultLocal[key]) continue;
        combinedStyle[key] = val;
      }

      Object.assign(merged, combinedStyle);

      if (includeData) {
        const sample = chartsSampleDataCache[activeChartType] || {};
        for (const [dKey, dVal] of Object.entries(sample)) {
          if (merged[dKey] === undefined) {
            merged[dKey] = dVal;
          }
        }
      }

      return merged;
    }

    function updateChartCodeOutput() {
      const outEl = document.getElementById('chartPhpCodeOutput');
      if (!outEl) return;

      const onlyModified = document.getElementById('csCodeOnlyModified')?.checked ?? true;
      const includeData = document.getElementById('csCodeIncludeData')?.checked ?? true;
      const wrapDiv = document.getElementById('csCodeWrapContainer')?.checked ?? false;

      const paramsObj = buildChartCodeArrayObject(onlyModified, includeData);
      const hasParams = Object.keys(paramsObj).length > 0;
      const arrayPhp = hasParams ? toPhpLiteral(paramsObj, 0) : '';

      const phpOpen = '<' + '?php';
      const phpEcho = '<' + '?=';
      const phpClose = '?' + '>';

      let code = '';
      if (chartCodeFormat === 'global') {
        const globalArgs = hasParams ? arrayPhp : '[]';
        code = `${phpOpen} \\Base\\Module\\GraphicsModule::configStyle(${globalArgs}); ${phpClose}`;
      } else if (chartCodeFormat === 'module') {
        const call = hasParams
          ? `\\Base\\Module\\GraphicsModule::${activeChartType}(${arrayPhp})`
          : `\\Base\\Module\\GraphicsModule::${activeChartType}()`;
        code = `${phpEcho} ${call} ${phpClose}`;
      } else {
        const call = hasParams
          ? `_chart('${activeChartType}', ${arrayPhp});`
          : `_chart('${activeChartType}');`;
        code = `${phpOpen} ${call} ${phpClose}`;
      }

      if (wrapDiv && chartCodeFormat !== 'global') {
        const indented = code.split('\n').map(line => `  ${line}`).join('\n');
        code = `<div class="p20 br15 back1">\n${indented}\n</div>`;
      }

      outEl.textContent = code;
    }

    function copyTextToClipboard(text, successMsg = '📋 Código PHP copiado al portapapeles') {
      if (!text) return;
      if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
        navigator.clipboard.writeText(text).then(() => {
          showToast(successMsg);
        }).catch(() => fallbackCopyText(text, successMsg));
      } else {
        fallbackCopyText(text, successMsg);
      }
    }

    function copyChartPhpCode() {
      const outEl = document.getElementById('chartPhpCodeOutput');
      const code = outEl ? outEl.textContent : '';
      copyTextToClipboard(code);
    }

    function fallbackCopyText(text, successMsg = '📋 Código PHP copiado al portapapeles') {
      const ta = document.createElement('textarea');
      ta.value = text;
      ta.style.position = 'fixed';
      ta.style.left = '-9999px';
      document.body.appendChild(ta);
      ta.select();
      try {
        document.execCommand('copy');
        showToast(successMsg);
      } catch (e) {
        showToast('❌ No se pudo copiar automáticamente', 'error');
      }
      document.body.removeChild(ta);
    }

    // ==============================================================
    // ICON STUDIO (GALERÍA VISUAL DE SVG EN /Ico POR CARPETAS)
    // ==============================================================

    let iconsCatalog = normalizeIconsCatalog(initialIconsCatalog);
    let iconsByName = new Map();
    let selectedIconName = '';
    let iconSearchQuery = '';
    let iconFolderFilter = '__all__';
    let iconStageSurface = 'var(--body)';
    let iconPreviewColorToken = 'auto';
    let iconPreviewSize = 28;
    let iconCopyFormat = 'php';
    let iconCustomClass = '';
    let iconCustomTransform = '';
    let iconCatalogSignature = '';
    let iconRefreshInFlight = false;
    let lastIconRefreshAt = 0;

    function escapeHtml(str) {
      return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    function normalizeIconsCatalog(raw) {
      const cat = (raw && typeof raw === 'object') ? raw : {};
      const folders = Array.isArray(cat.folders) ? cat.folders : [];
      return {
        totalIcons: Number(cat.totalIcons || 0),
        totalFolders: Number(cat.totalFolders || folders.length || 0),
        primaryDir: String(cat.primaryDir || 'Resources/Ico'),
        folders: folders.map(f => ({
          key: String(f.key ?? ''),
          label: String(f.label ?? (f.key || 'Raíz (/Ico)')),
          path: String(f.path ?? 'Ico/'),
          count: Number(f.count ?? (Array.isArray(f.icons) ? f.icons.length : 0)),
          icons: Array.isArray(f.icons) ? f.icons : []
        }))
      };
    }

    function computeIconCatalogSignature(cat) {
      return cat.folders
        .map(f => `${f.key}:${f.icons.map(i => `${i.name}@${i.sizeBytes}`).join(',')}`)
        .join('|');
    }

    function rebuildIconLookup() {
      iconsByName = new Map();
      let defaultCandidate = '';
      for (const folder of iconsCatalog.folders) {
        for (const icon of folder.icons) {
          iconsByName.set(icon.name, icon);
          if (!defaultCandidate) defaultCandidate = icon.name;
          if (icon.name === 'asterisk') defaultCandidate = 'asterisk';
        }
      }
      if (!selectedIconName || !iconsByName.has(selectedIconName)) {
        selectedIconName = defaultCandidate;
      }
      iconCatalogSignature = computeIconCatalogSignature(iconsCatalog);
    }

    function initIconStudio() {
      rebuildIconLookup();
      bindIconGalleryEvents();
      renderIconColorChips();
      renderIconFolderFilters();
      renderIconGallery();
      syncIconInspector();
      injectLiveThemeIntoIconSandbox();
    }

    function bindIconGalleryEvents() {
      const container = document.getElementById('isFoldersContainer');
      if (!container || container.dataset.bound === '1') return;
      container.dataset.bound = '1';

      container.addEventListener('click', (e) => {
        const card = e.target.closest('.is-icon-card[data-icon-name]');
        if (!card) return;
        const iconName = card.getAttribute('data-icon-name') || '';
        if (iconName) {
          onIconCardClick(iconName, card);
        }
      });
    }

    function getFolderFilterKeyValue(folderKey) {
      return folderKey === '' ? '__root__' : folderKey;
    }

    function renderIconFolderFilters() {
      const totalBadge = document.getElementById('isTotalBadge');
      const sidebarCount = document.getElementById('isSidebarFolderCount');
      const pillsContainer = document.getElementById('isSidebarFolderPills');
      const selectEl = document.getElementById('isFolderFilterSelect');

      const folders = iconsCatalog.folders;
      if (totalBadge) {
        totalBadge.textContent = `${iconsCatalog.totalIcons} iconos · ${folders.length} carpetas`;
      }
      if (sidebarCount) {
        sidebarCount.textContent = String(folders.length);
      }

      if (iconFolderFilter !== '__all__') {
        const stillExists = folders.some(f => getFolderFilterKeyValue(f.key) === iconFolderFilter);
        if (!stillExists) iconFolderFilter = '__all__';
      }

      if (pillsContainer) {
        const allPill = `
          <button type="button" class="is-folder-pill ${iconFolderFilter === '__all__' ? 'active' : ''}" data-folder-filter="__all__" onclick="setIconFolderFilter('__all__')">
            <span>Todas</span>
            <span class="is-folder-pill-count">${iconsCatalog.totalIcons}</span>
          </button>
        `;
        const folderPills = folders.map(f => {
          const val = getFolderFilterKeyValue(f.key);
          const active = iconFolderFilter === val ? 'active' : '';
          return `
            <button type="button" class="is-folder-pill ${active}" data-folder-filter="${escapeHtml(val)}" onclick="setIconFolderFilter(this.dataset.folderFilter)" title="${escapeHtml(f.path)}">
              <span>${f.key === '' ? '📂' : '📁'} ${escapeHtml(f.label)}</span>
              <span class="is-folder-pill-count">${f.count}</span>
            </button>
          `;
        }).join('');
        pillsContainer.innerHTML = allPill + folderPills;
      }

      if (selectEl) {
        const prev = iconFolderFilter;
        selectEl.innerHTML = `<option value="__all__">Todas las carpetas (${iconsCatalog.totalIcons})</option>` +
          folders.map(f => {
            const val = getFolderFilterKeyValue(f.key);
            return `<option value="${escapeHtml(val)}">${escapeHtml(f.label)} (${f.count})</option>`;
          }).join('');
        selectEl.value = prev;
      }
    }

    function setIconFolderFilter(filterVal) {
      iconFolderFilter = filterVal || '__all__';
      renderIconFolderFilters();
      renderIconGallery();
    }

    function onIconSearchChange(val, source) {
      iconSearchQuery = String(val ?? '');
      const sideIn = document.getElementById('isSearchInput');
      const stageIn = document.getElementById('isStageSearchInput');
      if (source !== 'sidebar' && sideIn) sideIn.value = iconSearchQuery;
      if (source !== 'stage' && stageIn) stageIn.value = iconSearchQuery;

      const hasQuery = iconSearchQuery.trim() !== '';
      const clrSide = document.getElementById('isSearchClearSidebar');
      const clrStage = document.getElementById('isSearchClearStage');
      if (clrSide) clrSide.classList.toggle('visible', hasQuery);
      if (clrStage) clrStage.classList.toggle('visible', hasQuery);

      renderIconGallery();
    }

    function setIconStageSurface(surfaceVal) {
      iconStageSurface = surfaceVal || 'var(--body)';
      injectLiveThemeIntoIconSandbox();
    }

    function setIconPreviewColorToken(token) {
      iconPreviewColorToken = token || 'auto';
      injectLiveThemeIntoIconSandbox();
    }

    function setIconPreviewSize(px, btnEl) {
      iconPreviewSize = Math.max(16, Math.min(64, parseInt(px, 10) || 28));
      document.querySelectorAll('.is-size-btn').forEach(b => {
        b.classList.toggle('active', Number(b.dataset.size) === iconPreviewSize);
      });
      if (btnEl) btnEl.classList.add('active');
      injectLiveThemeIntoIconSandbox();
    }

    function renderIconColorChips() {
      const { colors, textPrimary, textSecondary } = getActiveThemePaletteForCharts();
      const tokens = [
        { token: 'texto', dot: textPrimary },
        { token: 'textc', dot: textSecondary },
        { token: 'textw', dot: '#FFFFFF' },
        ...colors.slice(0, 8).map((hex, idx) => ({ token: `color${idx + 1}`, dot: hex }))
      ];

      const chipsEl = document.getElementById('isClassColorChips');
      if (chipsEl) {
        const currentTokens = iconCustomClass.trim().split(/\s+/).filter(Boolean);
        chipsEl.innerHTML = tokens.map(item => `
          <button type="button" class="cs-color-chip ${currentTokens.includes(item.token) ? 'active' : ''}" onclick="toggleIconClassChip('${item.token}')" title="Agregar o quitar clase .${item.token}">
            <span class="cs-chip-dot" style="background:${item.dot};"></span>
            <span>${item.token}</span>
          </button>
        `).join('');
      }

      const previewSelect = document.getElementById('isPreviewColorSelect');
      if (previewSelect) {
        const prev = iconPreviewColorToken;
        const dynamicOpts = [
          `<option value="auto">Color: Auto</option>`,
          `<option value="texto">Color: texto</option>`,
          `<option value="textc">Color: textc</option>`,
          `<option value="textw">Color: textw</option>`,
          `<option value="textb">Color: textb</option>`,
          ...colors.map((_, i) => `<option value="color${i + 1}">Color: color${i + 1}</option>`),
          `<option value="color-success">Color: success</option>`,
          `<option value="color-caution">Color: caution</option>`,
          `<option value="color-danger">Color: danger</option>`
        ];
        previewSelect.innerHTML = dynamicOpts.join('');
        previewSelect.value = previewSelect.querySelector(`option[value="${prev}"]`) ? prev : 'auto';
      }
    }

    function toggleIconClassChip(token) {
      const input = document.getElementById('isCustomClassInput');
      const current = String(input ? input.value : iconCustomClass).trim().split(/\s+/).filter(Boolean);
      const idx = current.indexOf(token);
      if (idx >= 0) {
        current.splice(idx, 1);
      } else {
        // Reemplazar otra clase de color previo para mantener limpio el snippet
        const filtered = current.filter(t => !/^color\d+$|^texto$|^textc$|^textw$|^textb$/.test(t));
        filtered.unshift(token);
        current.splice(0, current.length, ...filtered);
      }
      if (input) input.value = current.join(' ');
      onIconBuilderOptionChange();
    }

    function onIconBuilderOptionChange() {
      const clsIn = document.getElementById('isCustomClassInput');
      const trIn = document.getElementById('isCustomTransformInput');
      iconCustomClass = clsIn ? clsIn.value.trim() : '';
      iconCustomTransform = trIn ? trIn.value.trim() : '';
      renderIconColorChips();
      syncIconInspector();
    }

    function resetIconBuilderOptions() {
      const clsIn = document.getElementById('isCustomClassInput');
      const trIn = document.getElementById('isCustomTransformInput');
      if (clsIn) clsIn.value = '';
      if (trIn) trIn.value = '';
      iconCustomClass = '';
      iconCustomTransform = '';
      setIconCopyFormat('php');
      renderIconColorChips();
      syncIconInspector();
    }

    function setIconCopyFormat(fmt) {
      iconCopyFormat = ['php', 'echo', 'phpEcho', 'js'].includes(fmt) ? fmt : 'php';
      const sel = document.getElementById('isCopyFormatSelect');
      if (sel && sel.value !== iconCopyFormat) sel.value = iconCopyFormat;
      ['php', 'echo', 'phpEcho', 'js'].forEach(key => {
        const tab = document.getElementById(`isCodeTab_${key}`);
        if (tab) tab.classList.toggle('active', iconCopyFormat === key);
      });
      syncIconInspector();
    }

    function toggleIconCodeDock() {
      const dock = document.getElementById('isCodeDock');
      const btn = document.getElementById('btnToggleIconCodeDock');
      if (!dock) return;
      const collapsed = dock.classList.toggle('collapsed');
      if (btn) btn.textContent = collapsed ? '▸' : '▾';
    }

    function escapePhpDoubleQuoted(val) {
      return String(val ?? '')
        .replace(/\\/g, '\\\\')
        .replace(/"/g, '\\"')
        .replace(/\$/g, '\\$');
    }

    function buildIconCodeSnippet(iconName) {
      const cleanName = String(iconName || 'asterisk');
      const args = [`"${escapePhpDoubleQuoted(cleanName)}"`];
      if (iconCustomClass !== '') {
        args.push(`"${escapePhpDoubleQuoted(iconCustomClass)}"`);
      } else if (iconCustomTransform !== '') {
        args.push('""');
      }
      if (iconCustomTransform !== '') {
        args.push(`"${escapePhpDoubleQuoted(iconCustomTransform)}"`);
      }

      const callExpr = `svg(${args.join(', ')})`;
      if (iconCopyFormat === 'echo') {
        return `<` + `?= ${callExpr} ?` + `>`;
      }
      if (iconCopyFormat === 'phpEcho') {
        return `<` + `?php echo ${callExpr}; ?` + `>`;
      }
      if (iconCopyFormat === 'js') {
        return `await ${callExpr};`;
      }
      return `${callExpr};`;
    }

    function renderIconGallery() {
      const container = document.getElementById('isFoldersContainer');
      if (!container) return;

      const query = iconSearchQuery.trim().toLowerCase();
      const visibleFolders = [];

      for (const folder of iconsCatalog.folders) {
        const filterKey = getFolderFilterKeyValue(folder.key);
        if (iconFolderFilter !== '__all__' && iconFolderFilter !== filterKey) {
          continue;
        }

        const matchedIcons = query === ''
          ? folder.icons
          : folder.icons.filter(icon =>
              icon.name.toLowerCase().includes(query) ||
              icon.filename.toLowerCase().includes(query) ||
              folder.label.toLowerCase().includes(query)
            );

        // Si hay búsqueda activa, ocultar carpetas sin coincidencias
        if (query !== '' && matchedIcons.length === 0) {
          continue;
        }

        visibleFolders.push({
          folder,
          matchedIcons,
        });
      }

      if (visibleFolders.length === 0) {
        container.innerHTML = `
          <div class="is-empty-search">
            <div style="font-size:1.8rem; margin-bottom:8px;">🔍</div>
            <div style="font-weight:700; margin-bottom:6px;">No se encontraron iconos SVG para "${escapeHtml(iconSearchQuery)}"</div>
            <div style="font-size:0.78rem; opacity:0.75; margin-bottom:12px;">Prueba con otro nombre o limpia los filtros activos.</div>
            <button type="button" class="ts-btn ts-btn-secondary" onclick="onIconSearchChange('', 'clear'); setIconFolderFilter('__all__');">
              ↺ Mostrar todos los iconos
            </button>
          </div>
        `;
        return;
      }

      container.innerHTML = visibleFolders.map(({ folder, matchedIcons }) => {
        const countText = query !== ''
          ? `${matchedIcons.length} / ${folder.count}`
          : `${folder.count} ${folder.count === 1 ? 'icono' : 'iconos'}`;

        const bodyHtml = matchedIcons.length === 0
          ? `
            <div class="is-empty-folder">
              <span>Carpeta vacía. Agrega archivos <code>.svg</code> en <code>${escapeHtml(folder.path)}</code> para que aparezcan aquí automáticamente.</span>
              <button type="button" class="ts-btn ts-btn-secondary" style="padding:4px 10px; font-size:0.72rem;" onclick="refreshIconsCatalog(true)">
                🔄 Escanear ahora
              </button>
            </div>
          `
          : `
            <div class="is-icon-grid">
              ${matchedIcons.map(icon => {
                const isSelected = icon.name === selectedIconName;
                const glyphHtml = icon.valid && icon.svgHtml
                  ? icon.svgHtml
                  : '<span style="font-size:1rem; opacity:0.6;" title="SVG inválido">⚠️</span>';
                const tooltip = `${icon.name} — Clic para copiar ${buildIconCodeSnippet(icon.name)}`;
                return `
                  <button type="button" class="is-icon-card ${isSelected ? 'selected' : ''}" data-icon-name="${escapeHtml(icon.name)}" title="${escapeHtml(tooltip)}">
                    <span class="is-icon-copy-pill">Copiar</span>
                    <div class="is-icon-glyph">${glyphHtml}</div>
                    <div class="is-icon-name">${escapeHtml(icon.filename)}</div>
                  </button>
                `;
              }).join('')}
            </div>
          `;

        return `
          <section class="is-folder-section" data-folder-key="${escapeHtml(folder.key)}">
            <div class="is-folder-header">
              <div class="is-folder-title-wrap">
                <span class="is-folder-title">${folder.key === '' ? '📂' : '📁'} ${escapeHtml(folder.label)}</span>
                <span class="is-folder-path">${escapeHtml(folder.path)}</span>
              </div>
              <span class="is-folder-count">${escapeHtml(countText)}</span>
            </div>
            ${bodyHtml}
          </section>
        `;
      }).join('');
    }

    function syncIconInspector() {
      const icon = iconsByName.get(selectedIconName);
      const previewEl = document.getElementById('isInspectorPreview');
      const nameEl = document.getElementById('isInspectorName');
      const metaEl = document.getElementById('isInspectorMeta');
      const sideCodeEl = document.getElementById('isInspectorCodePreview');
      const dockCodeEl = document.getElementById('iconPhpCodeOutput');

      if (!icon) {
        if (previewEl) previewEl.innerHTML = '🖼️';
        if (nameEl) nameEl.textContent = 'Sin iconos';
        if (metaEl) metaEl.textContent = 'Agrega archivos .svg en la carpeta /Ico para comenzar.';
        if (sideCodeEl) sideCodeEl.textContent = 'svg("nombre");';
        if (dockCodeEl) dockCodeEl.textContent = 'svg("nombre");';
        return;
      }

      const snippet = buildIconCodeSnippet(icon.name);
      if (previewEl) {
        previewEl.innerHTML = icon.valid && icon.svgHtml ? icon.svgHtml : '⚠️';
      }
      if (nameEl) nameEl.textContent = icon.name;
      if (metaEl) {
        const folderLabel = icon.folder === '' ? 'Raíz (/Ico)' : icon.folder;
        metaEl.textContent = `Carpeta: ${folderLabel} · ${icon.sizeBytes} B · viewBox: ${icon.viewBox}`;
      }
      if (sideCodeEl) sideCodeEl.textContent = snippet;
      if (dockCodeEl) dockCodeEl.textContent = snippet;
    }

    function onIconCardClick(iconName, cardEl) {
      if (!iconsByName.has(iconName)) return;
      selectedIconName = iconName;

      document.querySelectorAll('#isFoldersContainer .is-icon-card').forEach(el => {
        el.classList.toggle('selected', el.getAttribute('data-icon-name') === iconName);
      });

      if (cardEl) {
        cardEl.classList.remove('copied');
        void cardEl.offsetWidth;
        cardEl.classList.add('copied');
        const pill = cardEl.querySelector('.is-icon-copy-pill');
        if (pill) pill.textContent = '✓ Copiado';
        clearTimeout(cardEl.__copyTimer);
        cardEl.__copyTimer = setTimeout(() => {
          cardEl.classList.remove('copied');
          if (pill) pill.textContent = 'Copiar';
        }, 1400);
      }

      syncIconInspector();

      const snippet = buildIconCodeSnippet(iconName);
      copyTextToClipboard(snippet, `📋 Copiado: ${snippet}`);
    }

    function copySelectedIconCode() {
      const iconName = selectedIconName || 'asterisk';
      const snippet = buildIconCodeSnippet(iconName);
      copyTextToClipboard(snippet, `📋 Copiado: ${snippet}`);
    }

    async function refreshIconsCatalog(notify = false) {
      if (iconRefreshInFlight) return;
      iconRefreshInFlight = true;
      lastIconRefreshAt = Date.now();

      try {
        const res = await fetch(`/_frame/theme-studio/icons?t=${Date.now()}`, {
          headers: { 'Accept': 'application/json' },
          cache: 'no-store'
        });
        if (!res.ok) {
          if (notify) showToast('❌ No se pudo re-escanear /Ico', 'error');
          return;
        }
        const data = await res.json();
        if (!data || !data.success || !data.catalog) {
          if (notify) showToast('❌ Respuesta inválida al escanear /Ico', 'error');
          return;
        }

        const nextCatalog = normalizeIconsCatalog(data.catalog);
        const nextSig = computeIconCatalogSignature(nextCatalog);
        const changed = nextSig !== iconCatalogSignature;

        if (changed) {
          iconsCatalog = nextCatalog;
          rebuildIconLookup();
          renderIconFolderFilters();
          renderIconGallery();
          syncIconInspector();
          injectLiveThemeIntoIconSandbox();
          showToast(`🔄 Catálogo /Ico actualizado: ${iconsCatalog.totalIcons} iconos en ${iconsCatalog.totalFolders} carpetas`);
        } else if (notify) {
          showToast(`✅ Catálogo /Ico al día (${iconsCatalog.totalIcons} iconos)`);
        }
      } catch (e) {
        if (notify) showToast('❌ Error de conexión al re-escanear /Ico', 'error');
      } finally {
        iconRefreshInFlight = false;
      }
    }

    function resolveIconPreviewColorValue(token, fallbackHeadColor) {
      if (!token || token === 'auto') return fallbackHeadColor;
      if (token === 'texto') return 'var(--text-primary)';
      if (token === 'textc') return 'var(--text-secondary)';
      if (token === 'textw') return '#ffffff';
      if (token === 'textb') return '#000000';
      if (token === 'color-success') return 'var(--success)';
      if (token === 'color-caution') return 'var(--caution)';
      if (token === 'color-danger') return 'var(--danger)';
      const m = token.match(/^color(\d+)$/);
      if (m) {
        const idx = parseInt(m[1], 10);
        return idx === 1 ? 'var(--back-color)' : `var(--back-color${idx})`;
      }
      return fallbackHeadColor;
    }

    function injectLiveThemeIntoIconSandbox() {
      const view = document.getElementById('iconSandboxView');
      if (!view) return;

      const isDark = (currentPreviewMode === 'dark');
      if (isDark) {
        view.setAttribute('data-theme', 'dark');
      } else {
        view.removeAttribute('data-theme');
      }

      const lightColors = themeConfig.light.colors || [];
      const darkColors = (themeConfig.dark.colors && themeConfig.dark.colors.length)
        ? themeConfig.dark.colors
        : lightColors.map(c => invertToDarkHex(c));

      const activeBody = isDark ? themeConfig.dark.body : themeConfig.light.body;
      const activeTextPrimary = isDark ? themeConfig.dark.text.primary : themeConfig.light.text.primary;
      const activeTextSecondary = isDark ? themeConfig.dark.text.secondary : themeConfig.light.text.secondary;
      const activeTextInactive = isDark ? themeConfig.dark.text.inactive : themeConfig.light.text.inactive;
      const activeColors = isDark ? darkColors : lightColors;

      let surfaceHex = activeBody;
      let headTextColor = 'var(--text-primary)';
      if (iconStageSurface === '#0c0c0e') {
        surfaceHex = '#0c0c0e';
        headTextColor = '#e2e8f0';
      } else if (iconStageSurface === '#ffffff') {
        surfaceHex = '#ffffff';
        headTextColor = '#0f172a';
      } else if (iconStageSurface.startsWith('var(--back-color')) {
        surfaceHex = resolveColorTokenPreviewHex(iconStageSurface);
        headTextColor = getRelativeLuminance(surfaceHex) < 0.35 ? '#f8fafc' : '#0f172a';
      }

      const isSurfaceDark = getRelativeLuminance(surfaceHex) < 0.35;
      view.setAttribute('data-surface-dark', isSurfaceDark ? '1' : '0');

      const glyphColor = resolveIconPreviewColorValue(iconPreviewColorToken, headTextColor);

      let css = `#iconSandboxView {\n`;
      css += `  --font: ${themeConfig.layout.font};\n`;
      css += `  --font-size: ${themeConfig.layout.fontSize};\n`;
      css += `  --body: ${activeBody};\n`;
      css += `  --text-primary: ${activeTextPrimary};\n`;
      css += `  --text-secondary: ${activeTextSecondary};\n`;
      css += `  --text-inactive: ${activeTextInactive};\n`;
      css += `  --success: ${themeConfig.status.success};\n`;
      css += `  --caution: ${themeConfig.status.caution};\n`;
      css += `  --danger: ${themeConfig.status.danger};\n`;
      css += `  --is-preview-size: ${iconPreviewSize}px;\n`;

      activeColors.forEach((hex, i) => {
        const vName = i === 0 ? '--back-color' : `--back-color${i + 1}`;
        css += `  ${vName}: ${hex};\n`;
        if (i === 0) css += `  --back-color1: ${hex};\n`;
      });
      css += `}\n`;

      css += `#iconSandboxStage {\n`;
      css += `  background-color: ${iconStageSurface};\n`;
      css += `  color: ${headTextColor};\n`;
      css += `}\n`;
      css += `#iconSandboxView .is-icon-card {\n`;
      css += `  color: ${headTextColor};\n`;
      css += `}\n`;
      css += `#iconSandboxView .is-icon-glyph {\n`;
      css += `  color: ${glyphColor};\n`;
      css += `}\n`;

      const inspectorPrev = document.getElementById('isInspectorPreview');
      if (inspectorPrev) {
        inspectorPrev.style.backgroundColor = iconStageSurface;
        inspectorPrev.style.color = glyphColor;
      }

      let styleEl = document.getElementById('frame-theme-studio-icon-vars');
      if (!styleEl) {
        styleEl = document.createElement('style');
        styleEl.id = 'frame-theme-studio-icon-vars';
        document.head.appendChild(styleEl);
      }
      styleEl.textContent = css;
    }

    // Re-escanear /Ico automáticamente al volver a enfocar la ventana mientras se ve la galería de iconos
    window.addEventListener('focus', () => {
      if (currentStageMode === 'icons' && (Date.now() - lastIconRefreshAt) > 2000) {
        refreshIconsCatalog(false);
      }
    });

    // ==============================================================
    // UI / INPUT STUDIO (DISEÑADOR DE COMPONENTES FORMUIMODULE)
    // ==============================================================

    const DEFAULT_INPUTS_CONFIG = {
      preset: 'soft',
      triggerBg: 'back1',
      triggerText: 'texto',
      triggerBorder: 'textc',
      surfaceBg: 'back1',
      surfaceText: 'texto',
      surfaceMuted: 'text-inactive',
      surfaceBorder: 'textc',
      placeholderColor: 'text-inactive',
      placeholderSize: 14,
      secondaryText: 'text-inactive',
      secondaryFontSize: 12,
      btnPrimaryBg: 'back6',
      btnPrimaryText: 'textw',
      btnPrimaryBorder: 'transparent',
      btnSecondaryBg: 'back2',
      btnSecondaryText: 'texto',
      btnSecondaryBorder: 'transparent',
      accentColor: 'color2',
      accentText: 'textb',
      trackBg: 'back2',
      thumbBg: 'textw',
      borderWidth: 1,
      focusRing: 'color2',
      focusOutline: false,
      radius: 15,
      touchSize: 44,
      fontSize: 14,
      width: '100%',
      gap: 8,
      fieldGap: 14,
      boxPadding: 12,
      thumbSize: 80,
      hideScrollbar: false,
      scrollbarColor: 'color2',
      scrollbarWidth: 6,
      shadow: 'soft',
      blur: 10,
      mobileSheet: true,
      transition: 250
    };

    const INPUT_PRESETS = {
      soft: { preset: 'soft', radius: 15, fontSize: 14, placeholderSize: 14, secondaryFontSize: 12, shadow: 'soft', blur: 8, touchSize: 44, borderWidth: 1, focusOutline: false, triggerBorder: 'textc', surfaceBorder: 'textc', btnPrimaryBorder: 'transparent', btnSecondaryBorder: 'transparent', placeholderColor: 'text-inactive', secondaryText: 'text-inactive', scrollbarColor: 'color2', width: '100%', gap: 8, fieldGap: 14, boxPadding: 12, thumbSize: 80, scrollbarWidth: 6, hideScrollbar: false },
      minimal: { preset: 'minimal', radius: 8, fontSize: 14, placeholderSize: 14, secondaryFontSize: 12, shadow: 'none', blur: 0, touchSize: 44, borderWidth: 1, focusOutline: false, triggerBorder: 'textc', surfaceBorder: 'textc', btnPrimaryBorder: 'transparent', btnSecondaryBorder: 'border2', placeholderColor: 'text-inactive', secondaryText: 'text-inactive', scrollbarColor: 'color8', width: '100%', gap: 6, fieldGap: 12, boxPadding: 10, thumbSize: 72, scrollbarWidth: 5, hideScrollbar: false },
      glass: { preset: 'glass', radius: 18, fontSize: 14, placeholderSize: 14, secondaryFontSize: 12, shadow: 'glass', blur: 16, touchSize: 46, borderWidth: 1, focusOutline: false, triggerBorder: 'textc', surfaceBorder: 'textc', btnPrimaryBorder: 'transparent', btnSecondaryBorder: 'transparent', placeholderColor: 'text-inactive', secondaryText: 'text-inactive', scrollbarColor: 'color5', width: '100%', gap: 8, fieldGap: 14, boxPadding: 14, thumbSize: 84, scrollbarWidth: 6, hideScrollbar: false },
      pill: { preset: 'pill', radius: 28, fontSize: 15, placeholderSize: 15, secondaryFontSize: 12, shadow: 'soft', blur: 10, touchSize: 46, borderWidth: 0, focusOutline: false, triggerBorder: 'transparent', surfaceBorder: 'textc', btnPrimaryBorder: 'transparent', btnSecondaryBorder: 'transparent', placeholderColor: 'text-inactive', secondaryText: 'text-inactive', scrollbarColor: 'color6', width: '100%', gap: 10, fieldGap: 16, boxPadding: 14, thumbSize: 84, scrollbarWidth: 6, hideScrollbar: false },
      elevated: { preset: 'elevated', radius: 14, fontSize: 15, placeholderSize: 15, secondaryFontSize: 12, shadow: 'medium', blur: 12, touchSize: 44, borderWidth: 1, focusOutline: true, triggerBorder: 'transparent', surfaceBorder: 'textc', btnPrimaryBorder: 'transparent', btnSecondaryBorder: 'border2', placeholderColor: '#94a3b8', secondaryText: '#94a3b8', scrollbarColor: '#38bdf8', width: '100%', gap: 8, fieldGap: 16, boxPadding: 14, thumbSize: 84, scrollbarWidth: 6, hideScrollbar: false }
    };

    const INPUT_SHADOW_MAP = {
      none: 'none',
      soft: '0 8px 24px rgba(0, 0, 0, 0.14)',
      medium: '0 14px 36px rgba(0, 0, 0, 0.24)',
      glass: '0 8px 32px rgba(0, 0, 0, 0.18), inset 0 1px 0 rgba(255, 255, 255, 0.16)'
    };

    const INPUT_PROP_TO_CSS_VAR = {
      triggerBg: '--ui-trigger-bg',
      triggerText: '--ui-trigger-text',
      triggerBorder: '--ui-trigger-border',
      surfaceBg: '--ui-surface-bg',
      surfaceText: '--ui-surface-text',
      surfaceMuted: '--ui-surface-muted',
      surfaceBorder: '--ui-surface-border',
      placeholderColor: '--ui-placeholder-color',
      secondaryText: '--ui-secondary-text',
      btnPrimaryBg: '--ui-btn-primary-bg',
      btnPrimaryText: '--ui-btn-primary-text',
      btnPrimaryBorder: '--ui-btn-primary-border',
      btnSecondaryBg: '--ui-btn-secondary-bg',
      btnSecondaryText: '--ui-btn-secondary-text',
      btnSecondaryBorder: '--ui-btn-secondary-border',
      accentColor: '--ui-accent',
      accentText: '--ui-accent-text',
      trackBg: '--ui-track-bg',
      thumbBg: '--ui-thumb-bg',
      scrollbarColor: '--ui-scrollbar-color',
      focusRing: '--ui-focus-ring'
    };

    const INPUT_TOKEN_LABELS = {
      triggerBg: 'Fondo Campo',
      triggerText: 'Texto Campo',
      surfaceMuted: 'Texto Guía / Muted',
      triggerBorder: 'Borde Campo',
      placeholderColor: 'Color Placeholder',
      secondaryText: 'Texto Secundario',
      surfaceBg: 'Fondo Modal',
      surfaceText: 'Texto Modal',
      surfaceBorder: 'Borde Modal',
      focusRing: 'Color Foco / Ring',
      btnPrimaryBg: 'Fondo Botón Guardar',
      btnPrimaryText: 'Texto Botón Guardar',
      btnPrimaryBorder: 'Borde Botón Guardar',
      btnSecondaryBg: 'Fondo Botón Cancelar',
      btnSecondaryText: 'Texto Botón Cancelar',
      btnSecondaryBorder: 'Borde Botón Cancelar',
      accentColor: 'Fondo Acento',
      accentText: 'Texto Acento',
      trackBg: 'Fondo Carril',
      thumbBg: 'Círculo / Thumb',
      scrollbarColor: 'Color Barra Scroll'
    };

    const INPUT_CATALOG = [
      { type: 'cropImage', title: 'Recorte Imagen', icon: '✂️', legacyCompat: true, demoBtn: '✂️ Probar Modal Recorte', defaultLabel: 'Avatar / Imagen recortada', hasHiddenPreview: true, hiddenPreviewLabel: 'Modal de Recorte (Recortar Imagen)' },
      { type: 'loadImgModern', title: 'Multi-Imagen', icon: '🖼️', legacyCompat: false, defaultLabel: 'Galería de imágenes', hasHiddenPreview: true, hiddenPreviewLabel: 'Miniaturas y botón de borrado' },
      { type: 'colorPicker', title: 'Color Picker', icon: '🎨', legacyCompat: false, defaultLabel: 'Color del tema', hasHiddenPreview: true, hiddenPreviewLabel: 'Popover Selector de Color' },
      { type: 'checkboxSwitch', title: 'Switch Táctil', icon: '🔘', legacyCompat: false, defaultLabel: 'Activar estado' },
      { type: 'dropdown', title: 'Dropdown', icon: '▾', legacyCompat: false, defaultLabel: 'País / Categoría', hasHiddenPreview: true, hiddenPreviewLabel: 'Lista desplegable de opciones' },
      { type: 'datePicker', title: 'Select Fecha', icon: '📅', legacyCompat: false, defaultLabel: 'Fecha del evento', hasHiddenPreview: true, hiddenPreviewLabel: 'Popover de Calendario' },
      { type: 'timePicker', title: 'Select Hora', icon: '🕒', legacyCompat: false, defaultLabel: 'Hora programada', hasHiddenPreview: true, hiddenPreviewLabel: 'Popover Selector de Hora' },
      { type: 'rangeSlider', title: 'Rango / Slider', icon: '🎚️', legacyCompat: false, defaultLabel: 'Presupuesto / Rango' },
      { type: 'passwordInput', title: 'Contraseña', icon: '🔒', legacyCompat: false, defaultLabel: 'Contraseña segura' },
      { type: 'otpCodeInput', title: 'Código OTP', icon: '🔢', legacyCompat: false, defaultLabel: 'Código de verificación' },
      { type: 'tagsInput', title: 'Etiquetas', icon: '🏷️', legacyCompat: false, defaultLabel: 'Palabras clave' },
      { type: 'stepperInput', title: 'Stepper ±', icon: '±', legacyCompat: false, defaultLabel: 'Cantidad' },
      { type: 'ratingInput', title: 'Rating / Estrellas', icon: '★', legacyCompat: false, defaultLabel: 'Calificación' },
      { type: 'fileDropzone', title: 'File Dropzone', icon: '📎', legacyCompat: false, defaultLabel: 'Adjuntar documentos', hasHiddenPreview: true, hiddenPreviewLabel: 'Lista de archivos adjuntos' },
      { type: 'repeatCamp', title: 'Repetibles +', icon: '➕', legacyCompat: false, defaultLabel: 'Opciones dinámicas' },
      { type: 'textLimiter', title: 'Límite Texto', icon: '📝', legacyCompat: false, defaultLabel: 'Descripción corta' },
      { type: 'textInput', title: 'Input Texto', icon: '🔤', legacyCompat: false, defaultLabel: 'Nombre completo' },
      { type: 'textareaInput', title: 'Textarea UI', icon: '📄', legacyCompat: false, defaultLabel: 'Mensaje / Comentario' },
      { type: 'submitButton', title: 'Botón Submit', icon: '🚀', legacyCompat: false, defaultLabel: '' }
    ];

    let activeInputType = 'cropImage';
    let activeInputDesignScope = 'global'; // 'global' | 'local'
    let activeInputSubTab = 'global'; // 'global' | 'local' | 'attrs'
    let focusedInputTokenProp = 'triggerBg';
    let inputSandboxViewMode = 'gallery'; // 'gallery' | 'focused' | 'formBuilder'
    let inputSandboxTouchSim = false;
    let inputStageSurface = 'var(--body)';
    let inputFocusedWidth = '100%';
    let inputCodeFormat = 'php'; // 'php' | 'html' | 'global'
    let iuColorDockCollapsed = false;

    let inputsHtmlCache = Object.assign({}, initialInputsHtmlMap || {});
    let inputCodeCache = { htmlSnippet: '', phpSnippet: '', globalSnippet: '' };
    let inputRenderTimer = null;
    let inputRenderAbort = null;
    let inputRenderSeq = 0;

    function escIuHtml(str) {
      return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    function applyJitPreviewCss(cssText) {
      const styleEl = document.getElementById('tsJitLivePreviewCss');
      if (styleEl && typeof cssText === 'string') {
        styleEl.textContent = cssText;
      }
    }

    function sanitizeWidthCss(rawVal) {
      const val = String(rawVal || '').trim();
      if (!val) return '100%';
      const lower = val.toLowerCase();
      if (['auto', 'fit-content', 'min-content', 'max-content'].includes(lower)) return lower;
      if (/^\d{1,4}(?:\.\d{1,2})?$/.test(val)) return `${val}px`;
      if (/^\d{1,4}(?:\.\d{1,2})?(?:%|px|rem|em|vw|vh|dvw|dvh|ch)$/i.test(val)) return lower;
      return '100%';
    }

    function ensureInputsConfig() {
      if (!themeConfig.inputs || typeof themeConfig.inputs !== 'object' || Array.isArray(themeConfig.inputs)) {
        themeConfig.inputs = JSON.parse(JSON.stringify(DEFAULT_INPUTS_CONFIG));
      } else {
        for (const [k, v] of Object.entries(DEFAULT_INPUTS_CONFIG)) {
          if (themeConfig.inputs[k] === undefined) {
            themeConfig.inputs[k] = v;
          }
        }
      }
      if (!themeConfig.inputs.localOverrides || typeof themeConfig.inputs.localOverrides !== 'object') {
        themeConfig.inputs.localOverrides = {};
      }
      return themeConfig.inputs;
    }

    function resolveUiTokenToCss(rawToken) {
      const token = String(rawToken || '').replace(/^\./, '').trim();
      const lower = token.toLowerCase();
      if (!token || lower === 'transparent' || lower === 'none' || lower === 'border-none' || lower === 'back-none' || lower === 'outline-none' || lower === '0') {
        return 'transparent';
      }

      const numMatch = token.match(/^(?:back|color|border)(\d+)(-hover)?$/i);
      if (numMatch) {
        const idx = parseInt(numMatch[1], 10);
        const vName = idx <= 1 ? '--back-color' : `--back-color${idx}`;
        return numMatch[2] ? `var(${vName}-hover)` : `var(${vName})`;
      }

      const map = {
        body: 'var(--body)',
        'body-back': 'var(--body)',
        texto: 'var(--text-primary)',
        textc: 'var(--text-secondary)',
        textw: '#ffffff',
        textb: '#000000',
        'text-inactive': 'var(--text-inactive)',
        inactive: 'var(--text-inactive)',
        success: 'var(--success)',
        'back-success': 'var(--success)',
        'color-success': 'var(--success)',
        caution: 'var(--caution)',
        'back-caution': 'var(--caution)',
        'color-caution': 'var(--caution)',
        danger: 'var(--danger)',
        'back-danger': 'var(--danger)',
        'color-danger': 'var(--danger)'
      };
      if (map[lower]) return map[lower];
      if (token.startsWith('--')) return `var(${token})`;
      return token;
    }

    function resolveUiTokenToHex(rawToken) {
      const token = String(rawToken || '').replace(/^\./, '').trim();
      const lower = token.toLowerCase();
      const isDark = (currentPreviewMode === 'dark');
      const colors = isDark
        ? ((themeConfig.dark.colors && themeConfig.dark.colors.length)
            ? themeConfig.dark.colors
            : (themeConfig.light.colors || []).map(c => invertToDarkHex(c)))
        : (themeConfig.light.colors || []);
      const bodyHex = isDark ? (themeConfig.dark.body || '#0f172a') : (themeConfig.light.body || '#ffffff');
      const textPrimary = isDark ? (themeConfig.dark.text?.primary || '#f8fafc') : (themeConfig.light.text?.primary || '#0f172a');
      const textSecondary = isDark ? (themeConfig.dark.text?.secondary || '#cbd5e1') : (themeConfig.light.text?.secondary || '#475569');
      const textInactive = isDark ? (themeConfig.dark.text?.inactive || '#94a3b8') : (themeConfig.light.text?.inactive || '#64748b');

      if (!token || lower === 'transparent' || lower === 'none' || lower === 'border-none' || lower === 'back-none' || lower === 'outline-none') {
        return bodyHex;
      }
      if (/^#[0-9a-f]{3,6}$/i.test(token)) return token;

      const numMatch = token.match(/^(?:back|color|border)(\d+)/i);
      if (numMatch) {
        const idx = Math.max(0, parseInt(numMatch[1], 10) - 1);
        return colors[idx] || colors[0] || '#3b82f6';
      }

      const map = {
        body: bodyHex,
        'body-back': bodyHex,
        texto: textPrimary,
        textc: textSecondary,
        textw: '#FFFFFF',
        textb: '#000000',
        'text-inactive': textInactive,
        inactive: textInactive,
        success: themeConfig.status?.success || '#22c55e',
        'back-success': themeConfig.status?.success || '#22c55e',
        'color-success': themeConfig.status?.success || '#22c55e',
        caution: themeConfig.status?.caution || '#f59e0b',
        'back-caution': themeConfig.status?.caution || '#f59e0b',
        'color-caution': themeConfig.status?.caution || '#f59e0b',
        danger: themeConfig.status?.danger || '#ef4444',
        'back-danger': themeConfig.status?.danger || '#ef4444',
        'color-danger': themeConfig.status?.danger || '#ef4444'
      };
      return map[lower] || '#3b82f6';
    }

    function buildInputCssVarsBlock(important = false, sourceCfg = null) {
      const cfg = sourceCfg || ensureInputsConfig();
      const imp = important ? ' !important' : '';
      const shadowVal = INPUT_SHADOW_MAP[String(cfg.shadow || 'soft').toLowerCase()] || INPUT_SHADOW_MAP.soft;
      const borderW = Math.max(0, Math.min(6, parseInt(cfg.borderWidth ?? 1, 10) || 0));
      const focusRingRaw = String(cfg.focusRing || 'color2');
      const focusRingCss = resolveUiTokenToCss(focusRingRaw);
      const focusOutlineCss = (!cfg.focusOutline || focusRingCss === 'transparent') ? 'none' : `2px solid ${focusRingCss}`;

      const lines = [
        `  --ui-trigger-bg: ${resolveUiTokenToCss(cfg.triggerBg)}${imp};`,
        `  --ui-trigger-text: ${resolveUiTokenToCss(cfg.triggerText)}${imp};`,
        `  --ui-trigger-border: ${resolveUiTokenToCss(cfg.triggerBorder)}${imp};`,
        `  --ui-surface-bg: ${resolveUiTokenToCss(cfg.surfaceBg)}${imp};`,
        `  --ui-surface-text: ${resolveUiTokenToCss(cfg.surfaceText)}${imp};`,
        `  --ui-surface-muted: ${resolveUiTokenToCss(cfg.surfaceMuted || 'text-inactive')}${imp};`,
        `  --ui-surface-border: ${resolveUiTokenToCss(cfg.surfaceBorder)}${imp};`,
        `  --ui-placeholder-color: ${resolveUiTokenToCss(cfg.placeholderColor || cfg.surfaceMuted || 'text-inactive')}${imp};`,
        `  --ui-placeholder-size: ${parseInt(cfg.placeholderSize ?? cfg.fontSize ?? 14, 10) || 14}px${imp};`,
        `  --ui-secondary-text: ${resolveUiTokenToCss(cfg.secondaryText || cfg.surfaceMuted || 'text-inactive')}${imp};`,
        `  --ui-secondary-font-size: ${parseInt(cfg.secondaryFontSize ?? 12, 10) || 12}px${imp};`,
        `  --ui-surface-shadow: ${shadowVal}${imp};`,
        `  --ui-btn-primary-bg: ${resolveUiTokenToCss(cfg.btnPrimaryBg)}${imp};`,
        `  --ui-btn-primary-text: ${resolveUiTokenToCss(cfg.btnPrimaryText)}${imp};`,
        `  --ui-btn-primary-border: ${resolveUiTokenToCss(cfg.btnPrimaryBorder ?? 'transparent')}${imp};`,
        `  --ui-btn-secondary-bg: ${resolveUiTokenToCss(cfg.btnSecondaryBg)}${imp};`,
        `  --ui-btn-secondary-text: ${resolveUiTokenToCss(cfg.btnSecondaryText)}${imp};`,
        `  --ui-btn-secondary-border: ${resolveUiTokenToCss(cfg.btnSecondaryBorder ?? 'transparent')}${imp};`,
        `  --ui-accent: ${resolveUiTokenToCss(cfg.accentColor)}${imp};`,
        `  --ui-accent-text: ${resolveUiTokenToCss(cfg.accentText || 'textb')}${imp};`,
        `  --ui-track-bg: ${resolveUiTokenToCss(cfg.trackBg)}${imp};`,
        `  --ui-thumb-bg: ${resolveUiTokenToCss(cfg.thumbBg ?? 'textw')}${imp};`,
        `  --ui-border-width: ${borderW}px${imp};`,
        `  --ui-focus-ring: ${focusRingCss}${imp};`,
        `  --ui-focus-outline: ${focusOutlineCss}${imp};`,
        `  --ui-radius: ${parseInt(cfg.radius ?? 15, 10) || 0}px${imp};`,
        `  --ui-touch-size: ${parseInt(cfg.touchSize ?? 44, 10) || 44}px${imp};`,
        `  --ui-font-size: ${parseInt(cfg.fontSize ?? 14, 10) || 14}px${imp};`,
        `  --ui-width: ${sanitizeWidthCss(cfg.width || '100%')}${imp};`,
        `  --ui-gap: ${parseInt(cfg.gap ?? 8, 10) || 8}px${imp};`,
        `  --ui-field-gap: ${parseInt(cfg.fieldGap ?? 14, 10) || 14}px${imp};`,
        `  --ui-box-padding: ${parseInt(cfg.boxPadding ?? 12, 10) || 12}px${imp};`,
        `  --ui-thumb-size: ${parseInt(cfg.thumbSize ?? 80, 10) || 80}px${imp};`,
        `  --ui-scrollbar-display: ${cfg.hideScrollbar ? 'none' : 'block'}${imp};`,
        `  --ui-scrollbar-color: ${resolveUiTokenToCss(cfg.scrollbarColor || cfg.accentColor || 'color2')}${imp};`,
        `  --ui-scrollbar-width: ${parseInt(cfg.scrollbarWidth ?? 6, 10) || 6}px${imp};`,
        `  --ui-overlay-blur: ${parseInt(cfg.blur ?? 10, 10) || 0}px${imp};`,
        `  --ui-transition: ${parseInt(cfg.transition ?? 250, 10) || 250}ms${imp};`
      ];
      return lines.join('\n') + '\n';
    }

    function applyLocalCssVarsLiveToDom(type) {
      const cfg = ensureInputsConfig();
      const local = (cfg.localOverrides && cfg.localOverrides[type]) ? cfg.localOverrides[type] : {};
      const merged = Object.assign({}, cfg, local);
      const hasAnyLocal = Object.keys(local).length > 0;

      const targets = [];
      const galleryCard = document.querySelector(`#iuGalleryContainer .iu-input-card[data-input-type="${type}"] .iu-card-body`);
      if (galleryCard) targets.push(galleryCard);
      if (inputSandboxViewMode === 'focused' && activeInputType === type) {
        const focusedBody = document.querySelector('#iuFocusedContainer .iu-focused-card .iu-card-body');
        const inlineHost = document.getElementById('iuInlinePreviewHost');
        if (focusedBody) targets.push(focusedBody);
        if (inlineHost) targets.push(inlineHost);
      }

      const pxNumericVars = {
        borderWidth: '--ui-border-width',
        radius: '--ui-radius',
        touchSize: '--ui-touch-size',
        fontSize: '--ui-font-size',
        placeholderSize: '--ui-placeholder-size',
        secondaryFontSize: '--ui-secondary-font-size',
        gap: '--ui-gap',
        fieldGap: '--ui-field-gap',
        boxPadding: '--ui-box-padding',
        thumbSize: '--ui-thumb-size',
        scrollbarWidth: '--ui-scrollbar-width',
        blur: '--ui-overlay-blur',
        chipRadius: '--ui-chip-radius',
        starSize: '--ui-star-size',
        dropzoneMinHeight: '--ui-dropzone-min-height',
        dropzoneIconSize: '--ui-dropzone-icon-size',
        sliderTrackHeight: '--ui-slider-track-height',
        sliderThumbSize: '--ui-slider-thumb-size',
        otpCellSize: '--ui-otp-cell-size',
        maxHeight: '--ui-tp-max-height'
      };

      const colorMicroVars = {
        removeBg: '--ui-remove-bg',
        removeColor: '--ui-remove-color',
        removeBorder: '--ui-remove-border'
      };

      targets.forEach((container) => {
        const comps = container.querySelectorAll('.ui-input-component, .ui-field-card, .ui-field-group, .ui-field-label, .ui-field-helper, .ui-text-input-wrap, .ui-text-input, .ui-textarea, .ui-submit-btn, .repeat-group, .repeat-container, .repeat-rows-wrap, .js-crop-img-mask, .js-load-img-mask, .ui-dp-trigger-wrap, .ui-dp-input-display, .dropdown-select, .dropdown-selected, .ui-password-wrap, .ui-otp-group, .ui-tags-box, .ui-stepper, .ui-rating, .ui-rating-icon-box, .ui-rating-value, .ui-range-wrap, .ui-dropzone-wrap, .checkbox-switch-container, .crop-modal-card, .custom-color-picker-popover, .ui-dp-popover, .ui-tp-col, .dropdown-options, .tl-counter');
        const nodes = [container, ...Array.from(comps)];
        nodes.forEach((node) => {
          if (!node || !node.style) return;
          Object.entries(INPUT_PROP_TO_CSS_VAR).forEach(([prop, cssVar]) => {
            if (hasAnyLocal && local[prop] !== undefined && local[prop] !== '') {
              node.style.setProperty(cssVar, resolveUiTokenToCss(local[prop]));
            } else {
              node.style.removeProperty(cssVar);
            }
          });
          Object.entries(colorMicroVars).forEach(([prop, cssVar]) => {
            if (hasAnyLocal && local[prop] !== undefined && local[prop] !== '') {
              node.style.setProperty(cssVar, resolveUiTokenToCss(local[prop]));
            } else {
              node.style.removeProperty(cssVar);
            }
          });
          Object.entries(pxNumericVars).forEach(([prop, cssVar]) => {
            if (hasAnyLocal && local[prop] !== undefined && local[prop] !== '') {
              const numVal = parseInt(local[prop], 10);
              if (Number.isFinite(numVal)) node.style.setProperty(cssVar, `${numVal}px`);
            } else {
              node.style.removeProperty(cssVar);
            }
          });
          if (hasAnyLocal && local.width !== undefined && local.width !== '') {
            node.style.setProperty('--ui-width', sanitizeWidthCss(local.width));
          } else {
            node.style.removeProperty('--ui-width');
          }
          if (hasAnyLocal && local.hideScrollbar !== undefined && local.hideScrollbar !== '') {
            node.style.setProperty('--ui-scrollbar-display', local.hideScrollbar ? 'none' : 'block');
            if (node.dataset) node.dataset.hideScrollbar = local.hideScrollbar ? 'true' : 'false';
          } else {
            node.style.removeProperty('--ui-scrollbar-display');
            if (node.dataset && node.dataset.hideScrollbar !== undefined) {
              delete node.dataset.hideScrollbar;
            }
          }
          if (hasAnyLocal && (local.focusOutline !== undefined || local.focusRing !== undefined)) {
            const fo = local.focusOutline !== undefined ? !!local.focusOutline : !!cfg.focusOutline;
            const fr = resolveUiTokenToCss(local.focusRing ?? cfg.focusRing ?? 'color2');
            node.style.setProperty('--ui-focus-outline', (!fo || fr === 'transparent') ? 'none' : `2px solid ${fr}`);
          } else {
            node.style.removeProperty('--ui-focus-outline');
          }
          if (hasAnyLocal && local.shadow !== undefined && local.shadow !== '') {
            node.style.setProperty('--ui-surface-shadow', INPUT_SHADOW_MAP[String(local.shadow).toLowerCase()] || INPUT_SHADOW_MAP.soft);
          } else {
            node.style.removeProperty('--ui-surface-shadow');
          }
          if (hasAnyLocal && local.dropzoneBorderStyle) {
            node.style.setProperty('--ui-dropzone-border-style', String(local.dropzoneBorderStyle));
          } else {
            node.style.removeProperty('--ui-dropzone-border-style');
          }
        });
      });

      // Sincronizar portales abiertos si corresponden al input activo
      document.querySelectorAll('.custom-color-picker-popover, .ui-dp-popover, .crop-modal-overlay, .dropdown-options').forEach((portal) => {
        if (!portal.style) return;
        const cssVarMap = {
          '--ui-trigger-bg': resolveUiTokenToCss(merged.triggerBg),
          '--ui-trigger-text': resolveUiTokenToCss(merged.triggerText),
          '--ui-trigger-border': resolveUiTokenToCss(merged.triggerBorder),
          '--ui-surface-bg': resolveUiTokenToCss(merged.surfaceBg),
          '--ui-surface-text': resolveUiTokenToCss(merged.surfaceText),
          '--ui-surface-muted': resolveUiTokenToCss(merged.surfaceMuted),
          '--ui-surface-border': resolveUiTokenToCss(merged.surfaceBorder),
          '--ui-placeholder-color': resolveUiTokenToCss(merged.placeholderColor || merged.surfaceMuted || 'text-inactive'),
          '--ui-placeholder-size': `${parseInt(merged.placeholderSize ?? 14, 10) || 14}px`,
          '--ui-secondary-text': resolveUiTokenToCss(merged.secondaryText || merged.surfaceMuted || 'text-inactive'),
          '--ui-secondary-font-size': `${parseInt(merged.secondaryFontSize ?? 12, 10) || 12}px`,
          '--ui-surface-shadow': INPUT_SHADOW_MAP[String(merged.shadow || 'soft').toLowerCase()] || INPUT_SHADOW_MAP.soft,
          '--ui-btn-primary-bg': resolveUiTokenToCss(merged.btnPrimaryBg),
          '--ui-btn-primary-text': resolveUiTokenToCss(merged.btnPrimaryText),
          '--ui-btn-primary-border': resolveUiTokenToCss(merged.btnPrimaryBorder ?? 'transparent'),
          '--ui-btn-secondary-bg': resolveUiTokenToCss(merged.btnSecondaryBg),
          '--ui-btn-secondary-text': resolveUiTokenToCss(merged.btnSecondaryText),
          '--ui-btn-secondary-border': resolveUiTokenToCss(merged.btnSecondaryBorder ?? 'transparent'),
          '--ui-accent': resolveUiTokenToCss(merged.accentColor),
          '--ui-accent-text': resolveUiTokenToCss(merged.accentText),
          '--ui-track-bg': resolveUiTokenToCss(merged.trackBg),
          '--ui-thumb-bg': resolveUiTokenToCss(merged.thumbBg ?? 'textw'),
          '--ui-border-width': `${parseInt(merged.borderWidth ?? 1, 10) || 0}px`,
          '--ui-radius': `${parseInt(merged.radius ?? 15, 10) || 0}px`,
          '--ui-touch-size': `${parseInt(merged.touchSize ?? 44, 10) || 44}px`,
          '--ui-font-size': `${parseInt(merged.fontSize ?? 14, 10) || 14}px`,
          '--ui-gap': `${parseInt(merged.gap ?? 8, 10) || 8}px`,
          '--ui-box-padding': `${parseInt(merged.boxPadding ?? 12, 10) || 12}px`,
          '--ui-scrollbar-display': merged.hideScrollbar ? 'none' : 'block',
          '--ui-scrollbar-color': resolveUiTokenToCss(merged.scrollbarColor || merged.accentColor || 'color2'),
          '--ui-scrollbar-width': `${parseInt(merged.scrollbarWidth ?? 6, 10) || 6}px`,
          '--ui-overlay-blur': `${parseInt(merged.blur ?? 10, 10) || 0}px`
        };
        Object.entries(cssVarMap).forEach(([vn, vv]) => portal.style.setProperty(vn, vv));
      });

      if (typeof window.FmeUi?.syncAllFloatingScrolls === 'function') {
        requestAnimationFrame(() => window.FmeUi.syncAllFloatingScrolls());
      }
    }

    function injectLiveThemeIntoInputSandbox() {
      const view = document.getElementById('inputSandboxView');
      if (!view) return;

      const isDark = (currentPreviewMode === 'dark');
      if (isDark) {
        view.setAttribute('data-theme', 'dark');
      } else {
        view.removeAttribute('data-theme');
      }

      const lightColors = themeConfig.light.colors || [];
      const darkColors = (themeConfig.dark.colors && themeConfig.dark.colors.length)
        ? themeConfig.dark.colors
        : lightColors.map(c => invertToDarkHex(c));

      const activeBody = isDark ? themeConfig.dark.body : themeConfig.light.body;
      const activeTextPrimary = isDark ? themeConfig.dark.text.primary : themeConfig.light.text.primary;
      const activeTextSecondary = isDark ? themeConfig.dark.text.secondary : themeConfig.light.text.secondary;
      const activeTextInactive = isDark ? themeConfig.dark.text.inactive : themeConfig.light.text.inactive;
      const activeColors = isDark ? darkColors : lightColors;

      let varsCss = '';
      varsCss += `  --font: ${themeConfig.layout.font};\n`;
      varsCss += `  --font-size: ${themeConfig.layout.fontSize};\n`;
      varsCss += `  --body: ${activeBody};\n`;
      varsCss += `  --text-primary: ${activeTextPrimary};\n`;
      varsCss += `  --text-secondary: ${activeTextSecondary};\n`;
      varsCss += `  --text-inactive: ${activeTextInactive};\n`;
      varsCss += `  --success: ${themeConfig.status.success};\n`;
      varsCss += `  --caution: ${themeConfig.status.caution};\n`;
      varsCss += `  --danger: ${themeConfig.status.danger};\n`;
      activeColors.forEach((hex, i) => {
        const vName = i === 0 ? '--back-color' : `--back-color${i + 1}`;
        varsCss += `  ${vName}: ${hex};\n`;
        if (i === 0) varsCss += `  --back-color1: ${hex};\n`;
      });
      varsCss += buildInputCssVarsBlock(false);

      let css = `#inputSandboxView,\n.ui-popover,\n.custom-color-picker-popover,\n.ui-dp-popover,\n.crop-modal-overlay,\n.dropdown-portal {\n${varsCss}}\n`;

      css += `#inputSandboxView .iu-sandbox-stage {\n  background-color: ${inputStageSurface};\n}\n`;
      css += `#inputSandboxView .texto { color: var(--text-primary); }\n`;
      css += `#inputSandboxView .textw { color: #ffffff; }\n`;
      css += `#inputSandboxView .textc { color: var(--text-secondary); }\n`;
      css += `#inputSandboxView .textb { color: #000000; }\n`;

      activeColors.forEach((_, i) => {
        const idx = i + 1;
        const vName = idx === 1 ? '--back-color' : `--back-color${idx}`;
        css += `#inputSandboxView .back${idx} { background-color: var(${vName}); }\n`;
        css += `#inputSandboxView .color${idx} { color: var(${vName}); }\n`;
        css += `#inputSandboxView .border${idx} { border: 1px solid var(${vName}); }\n`;
      });

      let styleEl = document.getElementById('frame-theme-studio-inputs-vars');
      if (!styleEl) {
        styleEl = document.createElement('style');
        styleEl.id = 'frame-theme-studio-inputs-vars';
        document.head.appendChild(styleEl);
      }
      styleEl.textContent = css;

      if (typeof window.FmeUi?.syncAllFloatingScrolls === 'function') {
        requestAnimationFrame(() => window.FmeUi.syncAllFloatingScrolls());
      }
    }

    function getEffectiveInputDesignConfig() {
      const cfg = ensureInputsConfig();
      if (activeInputDesignScope === 'local') {
        return Object.assign({}, cfg, getActiveInputLocalConfig());
      }
      return cfg;
    }

    function updateInputWcagBadges() {
      const cfg = getEffectiveInputDesignConfig();
      const applyBadge = (badgeId, bgToken, txtToken) => {
        const el = document.getElementById(badgeId);
        if (!el) return;
        const ratio = getContrastRatio(resolveUiTokenToHex(bgToken), resolveUiTokenToHex(txtToken));
        const rating = getWcagRating(ratio);
        el.textContent = `${ratio.toFixed(1)}:1 ${rating.level}`;
        el.classList.toggle('pass', !!rating.pass);
        el.classList.toggle('fail', !rating.pass);
      };
      applyBadge('iuPrimaryWcagBadge', cfg.btnPrimaryBg, cfg.btnPrimaryText);
      applyBadge('iuSecondaryWcagBadge', cfg.btnSecondaryBg, cfg.btnSecondaryText);
    }

    function autoFixInputButtonContrast(kind) {
      const cfg = getEffectiveInputDesignConfig();
      const bgKey = kind === 'secondary' ? 'btnSecondaryBg' : 'btnPrimaryBg';
      const textKey = kind === 'secondary' ? 'btnSecondaryText' : 'btnPrimaryText';
      const bgHex = resolveUiTokenToHex(cfg[bgKey]);
      const ratioWhite = getContrastRatio(bgHex, '#FFFFFF');
      const ratioDark = getContrastRatio(bgHex, '#0F172A');
      const bestText = ratioWhite >= ratioDark ? 'textw' : '#0F172A';
      setInputDesignProp(textKey, bestText);
      syncInputStudioForm();
      showToast('✨ Contraste de botón ajustado automáticamente');
    }

    function renderInputTypeGrid() {
      const grid = document.getElementById('iuTypeGrid');
      if (!grid) return;
      const cfg = ensureInputsConfig();
      grid.innerHTML = '';

      INPUT_CATALOG.forEach((item) => {
        const hasLocal = !!(cfg.localOverrides && cfg.localOverrides[item.type] && Object.keys(cfg.localOverrides[item.type]).length > 0);
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'iu-type-pill' + (item.type === activeInputType ? ' active' : '');
        btn.title = `${item.title} (${item.type})`;
        btn.innerHTML = `<span>${item.icon}</span><span>${escIuHtml(item.title)}</span>${hasLocal ? '<span class="iu-type-pill-dot" title="Tiene overrides locales">●</span>' : ''}`;
        btn.onclick = () => selectInputType(item.type, true);
        grid.appendChild(btn);
      });

      const badge = document.getElementById('iuActiveTypeBadge');
      const localLbl = document.getElementById('iuLocalTypeLabel');
      const subBadge = document.getElementById('iuSubTabLocalBadge');
      if (badge) badge.textContent = activeInputType;
      if (localLbl) localLbl.textContent = activeInputType;
      if (subBadge) subBadge.textContent = activeInputType;
    }

    function toggleIuColorChipsAccordion(forceCollapsed = null) {
      const dock = document.getElementById('iuFloatingColorDock');
      const btn = document.getElementById('btnToggleIuColorDock');
      if (!dock) return;
      iuColorDockCollapsed = (typeof forceCollapsed === 'boolean')
        ? forceCollapsed
        : !iuColorDockCollapsed;
      dock.classList.toggle('is-collapsed', iuColorDockCollapsed);
      if (btn) {
        btn.textContent = iuColorDockCollapsed ? '▸ Colores' : '▾ Ocultar';
        btn.setAttribute('aria-expanded', iuColorDockCollapsed ? 'false' : 'true');
      }
      saveWorkspaceState();
    }

    function syncActiveInputColorChip() {
      const container = document.getElementById('iuColorChips');
      if (!container) return;
      const effective = getEffectiveInputDesignConfig();
      const curVal = String(effective[focusedInputTokenProp] || '').trim().toLowerCase();
      container.querySelectorAll('.cs-color-chip').forEach((chip) => {
        const tok = String(chip.dataset.colorToken || '').toLowerCase();
        chip.classList.toggle('active', tok !== '' && tok === curVal);
      });
    }

    function setFocusedInputTokenProp(prop) {
      if (!prop || !INPUT_PROP_TO_CSS_VAR[prop]) return;
      focusedInputTokenProp = prop;
      const lbl = document.getElementById('iuFocusedTokenLabel');
      if (lbl) lbl.textContent = INPUT_TOKEN_LABELS[prop] || prop;
      document.querySelectorAll('[data-iu-prop]').forEach((el) => {
        el.classList.toggle('iu-token-focused', el.getAttribute('data-iu-prop') === prop);
      });
      syncActiveInputColorChip();
    }

    function applySystemColorToFocusedToken(token) {
      const prop = focusedInputTokenProp || 'triggerBg';
      const targetEl = document.querySelector(`[data-iu-prop="${prop}"]`);
      setInputDesignProp(prop, token);
      if (targetEl) targetEl.value = token;
      syncInputStudioForm();
      syncActiveInputColorChip();
    }

    function renderInputColorChips() {
      const container = document.getElementById('iuColorChips');
      if (!container) return;
      const isDark = (currentPreviewMode === 'dark');
      const colors = isDark
        ? ((themeConfig.dark.colors && themeConfig.dark.colors.length)
            ? themeConfig.dark.colors
            : (themeConfig.light.colors || []).map(c => invertToDarkHex(c)))
        : (themeConfig.light.colors || []);

      const chips = [];
      colors.forEach((hex, idx) => chips.push({ token: `back${idx + 1}`, hex }));
      chips.push({ token: 'texto', hex: resolveUiTokenToHex('texto') });
      chips.push({ token: 'textc', hex: resolveUiTokenToHex('textc') });
      chips.push({ token: 'textw', hex: '#FFFFFF' });
      chips.push({ token: 'textb', hex: '#000000' });
      chips.push({ token: 'body', hex: resolveUiTokenToHex('body') });
      chips.push({ token: 'text-inactive', hex: resolveUiTokenToHex('text-inactive') });
      chips.push({ token: 'success', hex: themeConfig.status?.success || '#22c55e' });
      chips.push({ token: 'caution', hex: themeConfig.status?.caution || '#f59e0b' });
      chips.push({ token: 'danger', hex: themeConfig.status?.danger || '#ef4444' });

      container.innerHTML = '';
      chips.forEach((ch) => {
        const el = document.createElement('button');
        el.type = 'button';
        el.className = 'cs-color-chip';
        el.dataset.colorToken = ch.token;
        el.title = `Aplicar token ${ch.token} (${ch.hex}) al campo seleccionado`;
        el.innerHTML = `<span class="cs-chip-dot" style="background:${ch.hex}"></span><span>${ch.token}</span>`;
        el.onclick = () => applySystemColorToFocusedToken(ch.token);
        container.appendChild(el);
      });
      syncActiveInputColorChip();
    }

    // Asistente Visual de Clases CSS JIT
    function toggleJitAssistant(inputId, btnEl) {
      const inputEl = document.getElementById(inputId);
      if (!inputEl) return;
      const hostWrap = inputEl.closest('.iu-jit-input-wrap') || inputEl.parentElement;
      if (!hostWrap || !hostWrap.parentElement) return;

      const existing = hostWrap.parentElement.querySelector(`.iu-jit-popover[data-jit-for="${inputId}"]`);
      document.querySelectorAll('.iu-jit-popover').forEach((pop) => pop.remove());
      if (existing) return;

      const pop = document.createElement('div');
      pop.className = 'iu-jit-popover';
      pop.dataset.jitFor = inputId;

      const renderChips = () => {
        const currentTokens = String(inputEl.value || '').trim().split(/\s+/).filter(Boolean);
        pop.innerHTML = '';
        Object.values(JIT_CLASSES_CATALOG || {}).forEach((cat) => {
          const catBox = document.createElement('div');
          const title = document.createElement('div');
          title.className = 'iu-jit-cat-title';
          title.textContent = cat.label || '';
          catBox.appendChild(title);

          const list = document.createElement('div');
          list.className = 'iu-jit-chip-list';
          (cat.classes || []).forEach((cls) => {
            const chip = document.createElement('button');
            chip.type = 'button';
            const isActive = currentTokens.includes(cls);
            chip.className = 'iu-jit-chip' + (isActive ? ' active' : '');
            chip.textContent = cls;
            chip.onclick = (ev) => {
              ev.preventDefault();
              const latest = String(inputEl.value || '').trim().split(/\s+/).filter(Boolean);
              const idx = latest.indexOf(cls);
              if (idx >= 0) {
                latest.splice(idx, 1);
              } else {
                latest.push(cls);
              }
              inputEl.value = latest.join(' ');
              inputEl.dispatchEvent(new Event('input', { bubbles: true }));
              renderChips();
            };
            list.appendChild(chip);
          });
          catBox.appendChild(list);
          pop.appendChild(catBox);
        });
      };

      renderChips();
      hostWrap.insertAdjacentElement('afterend', pop);
    }

    function switchInputSubTab(tab) {
      activeInputSubTab = (tab === 'local' || tab === 'attrs') ? tab : 'global';
      if (activeInputSubTab === 'global' || activeInputSubTab === 'local') {
        activeInputDesignScope = activeInputSubTab;
      }

      ['global', 'local', 'attrs'].forEach((name) => {
        const btn = document.getElementById('iuSubTab_' + name);
        if (btn) btn.classList.toggle('active', activeInputSubTab === name);
      });

      const designPanel = document.getElementById('iuPanel_design');
      const attrsPanel = document.getElementById('iuPanel_attrs');
      const scopeBanner = document.getElementById('iuScopeBanner');
      const localClassesBox = document.getElementById('iuLocalClassesBox');

      if (designPanel) designPanel.style.display = (activeInputSubTab === 'attrs') ? 'none' : 'block';
      if (attrsPanel) attrsPanel.style.display = (activeInputSubTab === 'attrs') ? 'block' : 'none';
      if (scopeBanner) scopeBanner.style.display = (activeInputSubTab === 'local') ? 'flex' : 'none';
      if (localClassesBox) localClassesBox.style.display = (activeInputSubTab === 'local') ? 'flex' : 'none';

      syncInputStudioForm();
      saveWorkspaceState();
    }

    function getActiveInputLocalConfig() {
      const cfg = ensureInputsConfig();
      if (!cfg.localOverrides[activeInputType] || typeof cfg.localOverrides[activeInputType] !== 'object') {
        return {};
      }
      return cfg.localOverrides[activeInputType];
    }

    function setInputDesignProp(prop, value, options = {}) {
      if (activeInputDesignScope === 'local') {
        onInputLocalChange(prop, value, Object.assign({ liveOnly: false }, options));
      } else {
        onInputGlobalChange(prop, value, options);
      }
    }

    function resetSingleInputLocalProp(prop) {
      onInputLocalChange(prop, null);
      syncInputStudioForm();
    }

    function renderSpecificInputAttrsPanelFor(box, compType, targetObj, changeCallFn) {
      if (!box) return;
      const local = targetObj || {};
      const callExpr = (k, valExpr) => `${changeCallFn}('${k}', ${valExpr})`;

      const textField = (key, label, placeholder = '') => {
        const val = local[key] !== undefined ? String(local[key]) : '';
        return `<div class="ts-field"><label>${escIuHtml(label)}</label><input type="text" class="ts-input" value="${escIuHtml(val)}" placeholder="${escIuHtml(placeholder)}" oninput="${callExpr(key, 'this.value')}"></div>`;
      };
      const textareaField = (key, label, placeholder = '') => {
        const val = local[key] !== undefined ? String(local[key]) : '';
        return `<div class="ts-field"><label>${escIuHtml(label)}</label><textarea class="ts-input" rows="3" placeholder="${escIuHtml(placeholder)}" oninput="${callExpr(key, 'this.value')}">${escIuHtml(val)}</textarea></div>`;
      };
      const numField = (key, label, placeholder = '') => {
        const val = local[key] !== undefined ? String(local[key]) : '';
        return `<div class="ts-field"><label>${escIuHtml(label)}</label><input type="number" class="ts-input" value="${escIuHtml(val)}" placeholder="${escIuHtml(placeholder)}" oninput="${callExpr(key, "this.value === '' ? null : Number(this.value)")}"></div>`;
      };
      const selectField = (key, label, opts, defVal) => {
        const cur = local[key] !== undefined ? String(local[key]) : defVal;
        return `<div class="ts-field"><label>${escIuHtml(label)}</label><select class="ts-input" onchange="${callExpr(key, 'this.value')}">${opts.map(o => `<option value="${escIuHtml(o.v)}" ${cur === o.v ? 'selected' : ''}>${escIuHtml(o.l)}</option>`).join('')}</select></div>`;
      };
      const checkField = (key, label, defChecked = false) => {
        const checked = local[key] !== undefined ? !!local[key] : defChecked;
        return `<label class="cs-check-row"><input type="checkbox" ${checked ? 'checked' : ''} onchange="${callExpr(key, 'this.checked')}"><span>${escIuHtml(label)}</span></label>`;
      };

      let html = '';
      switch (compType) {
        case 'cropImage':
          html = textField('croppingSize', 'Tamaño recorte (WxH)', '500x500')
            + textField('accept', 'Tipos aceptados (accept)', 'image/*')
            + selectField('cropShape', 'Forma de recorte', [{ v: 'square', l: 'Cuadrado / Rectangular' }, { v: 'circle', l: 'Circular (Avatar)' }], 'square')
            + textField('modalTitle', 'Título del modal', 'Recortar Imagen')
            + `<div class="iu-color-pair">${textField('saveLabel', 'Botón Guardar', 'Recortar y Guardar')}${textField('cancelLabel', 'Botón Cancelar', 'Cancelar')}</div>`
            + checkField('allowRotate', 'Mostrar botones rotar 90°', false)
            + checkField('noPreview', 'Ocultar miniatura previa (no-preview)', false);
          break;
        case 'loadImgModern':
          html = textField('accept', 'Tipos aceptados (accept)', 'image/*')
            + `<div class="iu-color-pair">${numField('maxFiles', 'Máx. archivos', '8')}${numField('maxSizeMb', 'Peso máx. (MB)', '10')}</div>`
            + checkField('multiple', 'Permitir múltiples archivos', true)
            + checkField('dropzone', 'Modo Dropzone visual', false);
          break;
        case 'colorPicker':
          html = textField('swatches', 'Muestras hex separadas por coma', '#EF4444,#F59E0B,#3B82F6')
            + textField('defaultColor', 'Color predeterminado (defaultColor)', '#3B82F6')
            + checkField('showAlpha', 'Mostrar slider de opacidad (Alpha)', false);
          break;
        case 'checkboxSwitch':
          html = selectField('size', 'Tamaño', [{ v: 'sm', l: 'Pequeño (sm)' }, { v: 'md', l: 'Mediano (md)' }, { v: 'lg', l: 'Grande (lg)' }], 'md')
            + `<div class="iu-color-pair">${textField('onValue', 'Valor Activo (onValue)', 'true')}${textField('offValue', 'Valor Inactivo (offValue)', 'false')}</div>`
            + `<div class="iu-color-pair">${textField('labelOn', 'Texto Activo', 'Sí')}${textField('labelOff', 'Texto Inactivo', 'No')}</div>`
            + checkField('checked', 'Activo inicialmente', true);
          break;
        case 'dropdown':
          html = textareaField('options', 'Opciones (valor:Etiqueta separadas por coma o línea)', 'es:España, mx:México, ar:Argentina')
            + `<div class="iu-color-pair">${textField('searchPlaceholder', 'Placeholder buscador', 'Buscar...')}${textField('emptyText', 'Texto sin resultados', 'Sin coincidencias')}</div>`
            + numField('maxHeight', 'Altura máx. lista (px)', '240')
            + checkField('searchable', 'Buscador interno', true)
            + checkField('clearable', 'Botón limpiar selección', false)
            + checkField('animated', 'Animación de entrada', false);
          break;
        case 'datePicker':
          html = selectField('mode', 'Modo calendario', [{ v: 'single', l: 'Fecha única' }, { v: 'range', l: 'Rango de fechas' }], 'single')
            + `<div class="iu-color-pair">${selectField('format', 'Formato salida', [{ v: 'YYYY-MM-DD', l: 'ISO (YYYY-MM-DD)' }, { v: 'DD/MM/YYYY', l: 'DD/MM/YYYY' }], 'YYYY-MM-DD')}${selectField('weekStart', 'Inicio de semana', [{ v: 'monday', l: 'Lunes' }, { v: 'sunday', l: 'Domingo' }], 'monday')}</div>`
            + `<div class="iu-color-pair">${textField('min', 'Fecha mínima', '2026-01-01')}${textField('max', 'Fecha máxima', '2027-12-31')}</div>`
            + checkField('showPresets', 'Botones rápidos (Hoy / Últimos 7d)', false)
            + checkField('showTime', 'Incluir selector de hora', false);
          break;
        case 'timePicker':
          html = `<div class="iu-color-pair">${selectField('timeFormat', 'Formato reloj', [{ v: '24h', l: '24 Horas' }, { v: '12h', l: '12 Horas (AM/PM)' }], '24h')}${numField('step', 'Intervalo min.', '15')}</div>`
            + `<div class="iu-color-pair">${numField('maxHeight', 'Altura columnas (px)', '200')}${textField('min', 'Hora mín. (HH:MM)', '08:00')}</div>`
            + textField('max', 'Hora máx. (HH:MM)', '20:00');
          break;
        case 'rangeSlider':
          html = selectField('mode', 'Tipo de rango', [{ v: 'single', l: 'Un manejador' }, { v: 'range', l: 'Doble rango (mín-máx)' }], 'single')
            + `<div class="iu-color-pair">${numField('min', 'Mínimo', '0')}${numField('max', 'Máximo', '100')}</div>`
            + `<div class="iu-color-pair">${numField('step', 'Paso', '1')}${textField('suffix', 'Sufijo (ej. %, $)', '')}</div>`
            + `<div class="iu-color-pair">${numField('sliderTrackHeight', 'Alto pista (px)', '10')}${numField('sliderThumbSize', 'Tam. thumb (px)', '24')}</div>`
            + checkField('showTooltip', 'Burbuja flotante de valor', true);
          break;
        case 'passwordInput':
          html = numField('minlength', 'Longitud mínima (minlength)', '8')
            + checkField('showStrength', 'Medidor de fortaleza de contraseña', false);
          break;
        case 'otpCodeInput':
          html = `<div class="iu-color-pair">${selectField('length', 'Cantidad dígitos', [{ v: '4', l: '4 dígitos' }, { v: '6', l: '6 dígitos' }, { v: '8', l: '8 dígitos' }], '6')}${numField('otpCellSize', 'Tamaño celda (px)', '44')}</div>`
            + selectField('inputMode', 'Teclado móvil', [{ v: 'numeric', l: 'Numérico' }, { v: 'text', l: 'Alfanumérico' }], 'numeric')
            + checkField('mask', 'Enmascarar dígitos (type=password)', false);
          break;
        case 'tagsInput':
          html = `<div class="iu-color-pair">${numField('maxTags', 'Máx. etiquetas', '10')}${textField('separator', 'Delimitador', ',')}</div>`
            + numField('chipRadius', 'Radio chip (px, 999 pill)', '999');
          break;
        case 'stepperInput':
          html = `<div class="iu-color-pair">${numField('min', 'Mínimo', '0')}${numField('max', 'Máximo', '99')}</div>`
            + numField('step', 'Incremento / Paso', '1');
          break;
        case 'ratingInput':
          html = `<div class="iu-color-pair">${numField('max', 'Estrellas máx.', '5')}${selectField('icon', 'Icono', [{ v: 'star', l: 'Estrella ★' }, { v: 'heart', l: 'Corazón ♥' }], 'star')}</div>`
            + numField('starSize', 'Tamaño icono (px)', '24')
            + checkField('allowHalf', 'Permitir medias (0.5)', false)
            + checkField('showValue', 'Mostrar valor numérico', false)
            + checkField('clearable', 'Permitir limpiar haciendo clic de nuevo', false);
          break;
        case 'fileDropzone':
          html = textField('accept', 'MIME / Extensiones', '.pdf,.doc,.docx,.zip,image/*')
            + textField('subtext', 'Subtítulo informativo (subtext)', 'PDF, JPG o PNG máx 10MB')
            + `<div class="iu-color-pair">${numField('maxFiles', 'Máx. archivos', '5')}${numField('maxSizeMb', 'Máx. MB', '10')}</div>`
            + `<div class="iu-color-pair">${numField('dropzoneMinHeight', 'Altura mín. (px)', '130')}${selectField('dropzoneBorderStyle', 'Estilo borde', [{ v: 'dashed', l: 'Discontinuo (dashed)' }, { v: 'solid', l: 'Sólido (solid)' }, { v: 'dotted', l: 'Punteado (dotted)' }], 'dashed')}</div>`
            + numField('dropzoneIconSize', 'Tamaño icono (px)', '32')
            + checkField('multiple', 'Permitir múltiples archivos', true);
          break;
        case 'repeatCamp':
          html = `<div class="iu-color-pair">${numField('max', 'Máx. filas', '8')}${textField('addLabel', 'Texto botón agregar', '+ Agrega otra opción')}</div>`
            + selectField('addVariant', 'Variante botón Agregar', [{ v: 'secondary', l: 'Secundario (Tema)' }, { v: 'primary', l: 'Primario (Destacado)' }], 'secondary')
            + `<div class="iu-color-pair">${textField('removeBg', 'Fondo botón quitar', 'transparent')}${textField('removeColor', 'Texto botón quitar', 'text-inactive')}</div>`
            + textField('removeBorder', 'Borde botón quitar', 'textc');
          break;
        case 'textLimiter':
          html = `<div class="iu-color-pair">${numField('limit', 'Límite máximo', '160')}${selectField('limitType', 'Unidad', [{ v: 'chars', l: 'Caracteres' }, { v: 'words', l: 'Palabras' }], 'chars')}</div>`
            + numField('rows', 'Filas del textarea', '3');
          break;
        case 'textInput':
          html = `<div class="iu-color-pair">${selectField('inputType', 'Tipo HTML (inputType)', [
            { v: 'text', l: 'Texto (text)' },
            { v: 'email', l: 'Email (email)' },
            { v: 'tel', l: 'Teléfono (tel)' },
            { v: 'number', l: 'Número (number)' },
            { v: 'search', l: 'Búsqueda (search)' },
            { v: 'url', l: 'URL (url)' }
          ], 'text')}${textField('autocomplete', 'Autocomplete', 'name')}</div>`
            + `<div class="iu-color-pair">${textField('icon', 'Icono SVG (/Ico)', '')}${selectField('iconPosition', 'Posición icono', [{ v: 'left', l: 'Izquierda' }, { v: 'right', l: 'Derecha' }], 'left')}</div>`
            + `<div class="iu-color-pair">${numField('minlength', 'Mín. chars', '')}${numField('maxlength', 'Máx. chars', '')}</div>`
            + textField('pattern', 'Patrón RegExp (pattern)', '');
          break;
        case 'textareaInput':
          html = `<div class="iu-color-pair">${numField('rows', 'Filas visibles (rows)', '4')}${selectField('resize', 'Redimensión (resize)', [{ v: 'vertical', l: 'Vertical' }, { v: 'none', l: 'Fija (none)' }, { v: 'both', l: 'Libre (both)' }], 'vertical')}</div>`
            + `<div class="iu-color-pair">${numField('minlength', 'Mín. chars', '')}${numField('maxlength', 'Máx. chars', '')}</div>`;
          break;
        case 'submitButton':
          html = `<div class="iu-color-pair">${textField('text', 'Texto del botón', 'Enviar')}${selectField('variant', 'Variante', [{ v: 'primary', l: 'Primario (primary)' }, { v: 'secondary', l: 'Secundario (secondary)' }], 'primary')}</div>`
            + `<div class="iu-color-pair">${selectField('buttonType', 'Tipo HTML', [{ v: 'submit', l: 'submit' }, { v: 'button', l: 'button' }, { v: 'reset', l: 'reset' }], 'submit')}${textField('icon', 'Icono SVG (/Ico)', '')}</div>`
            + textField('loadingText', 'Texto al enviar (loadingText)', 'Procesando...');
          break;
      }
      box.innerHTML = html;
    }

    function renderSpecificInputAttrsPanel() {
      const box = document.getElementById('iuSpecificAttrsContainer');
      renderSpecificInputAttrsPanelFor(box, activeInputType, getActiveInputLocalConfig(), 'onInputLocalChange');
    }

    function syncInputStudioForm() {
      const cfg = ensureInputsConfig();
      const local = getActiveInputLocalConfig();
      const effective = getEffectiveInputDesignConfig();
      const activeMeta = INPUT_CATALOG.find(i => i.type === activeInputType) || INPUT_CATALOG[0];

      document.querySelectorAll('.iu-preset-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.preset === (cfg.preset || 'soft'));
      });

      document.querySelectorAll('[data-iu-prop]').forEach((el) => {
        const k = el.getAttribute('data-iu-prop');
        const val = effective[k];
        if (el.type === 'checkbox') {
          el.checked = !!val;
        } else if (val !== undefined && val !== null) {
          el.value = val;
        }
        el.classList.toggle('iu-token-focused', k === focusedInputTokenProp);

        const wrap = el.closest('[data-iu-field-wrap]');
        if (wrap) {
          const hasOverride = activeInputDesignScope === 'local' && local[k] !== undefined && local[k] !== null && local[k] !== '';
          wrap.classList.toggle('has-local-override', hasOverride);
        }
      });

      document.querySelectorAll('[data-iu-picker-prop]').forEach((picker) => {
        const prop = picker.getAttribute('data-iu-picker-prop');
        const val = effective[prop] ?? '';
        const hex = resolveUiTokenToHex(val);
        if (/^#[0-9a-f]{6}$/i.test(hex)) picker.value = hex;
      });

      const badgeUnits = [
        ['iu_borderWidthVal', effective.borderWidth ?? 1, 'px'],
        ['iu_radiusVal', effective.radius ?? 15, 'px'],
        ['iu_touchSizeVal', effective.touchSize ?? 44, 'px'],
        ['iu_fontSizeVal', effective.fontSize ?? 14, 'px'],
        ['iu_placeholderSizeVal', effective.placeholderSize ?? 14, 'px'],
        ['iu_secondaryFontSizeVal', effective.secondaryFontSize ?? 12, 'px'],
        ['iu_gapVal', effective.gap ?? 8, 'px'],
        ['iu_fieldGapVal', effective.fieldGap ?? 14, 'px'],
        ['iu_boxPaddingVal', effective.boxPadding ?? 12, 'px'],
        ['iu_thumbSizeVal', effective.thumbSize ?? 80, 'px'],
        ['iu_scrollbarWidthVal', effective.scrollbarWidth ?? 6, 'px'],
        ['iu_blurVal', effective.blur ?? 10, 'px']
      ];
      badgeUnits.forEach(([id, v, unit]) => {
        const lbl = document.getElementById(id);
        if (lbl) lbl.textContent = v + unit;
      });

      document.querySelectorAll('[data-iu-local]').forEach((el) => {
        const k = el.getAttribute('data-iu-local');
        el.value = local[k] !== undefined && local[k] !== null ? local[k] : '';
      });

      const attrChecks = {
        iuAttr_required: !!local.required,
        iuAttr_disabled: !!local.disabled,
        iuAttr_readonly: !!local.readonly,
        iuAttr_autoSubmit: !!local.autoSubmit,
        iuAttr_noAutoSubmit: !!local.noAutoSubmit,
        iuAttr_processAutoSubmit: !!local.processAutoSubmit
      };
      Object.entries(attrChecks).forEach(([id, state]) => {
        const el = document.getElementById(id);
        if (el) el.checked = state;
      });

      const legacyBox = document.getElementById('iuLegacyCompatGroup');
      if (legacyBox) {
        legacyBox.style.display = activeMeta.legacyCompat ? 'block' : 'none';
      }

      const tokenLbl = document.getElementById('iuFocusedTokenLabel');
      if (tokenLbl) tokenLbl.textContent = INPUT_TOKEN_LABELS[focusedInputTokenProp] || focusedInputTokenProp;

      renderSpecificInputAttrsPanel();
      updateInputWcagBadges();
      syncActiveInputColorChip();
    }

    function updateInputRangeBadge(key, value) {
      const defaultsMap = {
        borderWidth: ['iu_borderWidthVal', 1],
        radius: ['iu_radiusVal', 15],
        touchSize: ['iu_touchSizeVal', 44],
        fontSize: ['iu_fontSizeVal', 14],
        placeholderSize: ['iu_placeholderSizeVal', 14],
        secondaryFontSize: ['iu_secondaryFontSizeVal', 12],
        gap: ['iu_gapVal', 8],
        fieldGap: ['iu_fieldGapVal', 14],
        boxPadding: ['iu_boxPaddingVal', 12],
        thumbSize: ['iu_thumbSizeVal', 80],
        scrollbarWidth: ['iu_scrollbarWidthVal', 6],
        blur: ['iu_blurVal', 10]
      };
      if (defaultsMap[key]) {
        const [elId, defV] = defaultsMap[key];
        const l = document.getElementById(elId);
        if (l) l.textContent = (value ?? defV) + 'px';
      }
    }

    function onInputGlobalChange(key, value, options = {}) {
      const cfg = ensureInputsConfig();
      cfg[key] = value;
      updateInputRangeBadge(key, value);
      injectLiveThemeIntoInputSandbox();
      injectLiveThemeIntoIframe();
      updateInputWcagBadges();
      updateJsonViewer();

      if (inputSandboxViewMode === 'formBuilder') {
        clearTimeout(formBuilderRenderTimer);
        formBuilderRenderTimer = setTimeout(() => {
          requestFormBuilderRender();
        }, 160);
        return;
      }

      const updateStage = !options.liveOnly;
      clearTimeout(inputRenderTimer);
      inputRenderTimer = setTimeout(() => {
        requestInputRender(activeInputType, updateStage);
      }, 160);
    }

    function onInputLocalChange(key, value, options = {}) {
      const cfg = ensureInputsConfig();
      if (!cfg.localOverrides[activeInputType] || typeof cfg.localOverrides[activeInputType] !== 'object') {
        cfg.localOverrides[activeInputType] = {};
      }
      if (value === null || value === '' || value === undefined) {
        delete cfg.localOverrides[activeInputType][key];
      } else {
        cfg.localOverrides[activeInputType][key] = value;
      }
      if (Object.keys(cfg.localOverrides[activeInputType]).length === 0) {
        delete cfg.localOverrides[activeInputType];
      }

      updateInputRangeBadge(key, value ?? cfg[key]);
      applyLocalCssVarsLiveToDom(activeInputType);
      updateInputWcagBadges();

      const wrap = document.querySelector(`[data-iu-field-wrap="${key}"]`);
      if (wrap) {
        const hasOverride = activeInputDesignScope === 'local' && cfg.localOverrides[activeInputType] && cfg.localOverrides[activeInputType][key] !== undefined;
        wrap.classList.toggle('has-local-override', !!hasOverride);
      }

      renderInputTypeGrid();
      updateJsonViewer();

      const visualKeys = [
        'borderWidth', 'focusOutline', 'radius', 'touchSize', 'fontSize',
        'placeholderSize', 'secondaryFontSize', 'width', 'gap', 'fieldGap',
        'boxPadding', 'thumbSize', 'hideScrollbar', 'scrollbarWidth',
        'shadow', 'blur', 'chipRadius', 'starSize', 'removeBg', 'removeColor',
        'removeBorder', 'dropzoneMinHeight', 'dropzoneBorderStyle', 'dropzoneIconSize',
        'sliderTrackHeight', 'sliderThumbSize', 'otpCellSize'
      ];
      const isVisualCssProp = !!(INPUT_PROP_TO_CSS_VAR[key] || visualKeys.includes(key));
      const shouldUpdateStage = options.liveOnly ? false : !isVisualCssProp;

      clearTimeout(inputRenderTimer);
      inputRenderTimer = setTimeout(() => {
        requestInputRender(activeInputType, shouldUpdateStage);
      }, 150);
    }

    function applyInputPreset(presetName) {
      const preset = INPUT_PRESETS[presetName];
      if (!preset) return;
      const cfg = ensureInputsConfig();
      Object.assign(cfg, preset);
      syncInputStudioForm();
      injectLiveThemeIntoInputSandbox();
      injectLiveThemeIntoIframe();
      updateJsonViewer();
      if (inputSandboxViewMode === 'formBuilder') {
        requestFormBuilderRender();
      } else {
        scheduleInputRender('all');
      }
    }

    function resetAllInputStyles() {
      themeConfig.inputs = JSON.parse(JSON.stringify(DEFAULT_INPUTS_CONFIG));
      themeConfig.inputs.localOverrides = {};
      syncInputStudioForm();
      renderInputTypeGrid();
      injectLiveThemeIntoInputSandbox();
      injectLiveThemeIntoIframe();
      updateJsonViewer();
      if (inputSandboxViewMode === 'formBuilder') {
        requestFormBuilderRender();
      } else {
        requestInputRender('all', true);
      }
      showToast('↺ Estilos de Inputs restaurados a valores base');
    }

    function clearActiveInputLocalOverrides() {
      const cfg = ensureInputsConfig();
      if (cfg.localOverrides && cfg.localOverrides[activeInputType]) {
        delete cfg.localOverrides[activeInputType];
      }
      applyLocalCssVarsLiveToDom(activeInputType);
      syncInputStudioForm();
      renderInputTypeGrid();
      updateJsonViewer();
      requestInputRender(activeInputType, true);
    }

    function selectInputType(type, autoFocusIfNeeded = false) {
      if (!INPUT_CATALOG.some(i => i.type === type)) return;
      const changed = (activeInputType !== type);
      activeInputType = type;
      renderInputTypeGrid();
      if (changed) {
        syncInputStudioForm();
      }

      document.querySelectorAll('#iuGalleryContainer .iu-input-card').forEach((card) => {
        card.classList.toggle('active', card.dataset.inputType === type);
      });

      if (autoFocusIfNeeded && inputSandboxViewMode === 'focused') {
        renderInputSandboxFromCache();
        initInputSandboxDom();
      }
      if (changed || autoFocusIfNeeded) {
        requestInputRender(type, false);
        saveWorkspaceState();
      }
    }

    function getInputCodeOptionsPayload() {
      const activeMeta = INPUT_CATALOG.find(i => i.type === activeInputType) || INPUT_CATALOG[0];
      const local = getActiveInputLocalConfig();
      const wrapContainer = !!document.getElementById('iuOptWrapContainer')?.checked;
      const includeLabel = !!document.getElementById('iuOptIncludeLabel')?.checked;
      const includeGlobalInCode = !!document.getElementById('iuOptIncludeGlobalInCode')?.checked;
      const inlineVars = !!document.getElementById('iuOptInlineVars')?.checked;
      const multiline = !!document.getElementById('iuOptMultiline')?.checked;
      const cfg = ensureInputsConfig();

      const opts = {
        wrapContainer,
        inlineVars,
        includeGlobalInCode,
        multiline,
        label: includeLabel ? (local.label || activeMeta.defaultLabel || '') : ''
      };
      if (inlineVars) {
        Object.keys(DEFAULT_INPUTS_CONFIG).forEach((sk) => {
          if (sk === 'preset') return;
          if (local[sk] === undefined && cfg[sk] !== undefined) {
            opts[sk] = cfg[sk];
          }
        });
      }
      return opts;
    }

    function cleanStaleInputPortals() {
      document.querySelectorAll('.custom-color-picker-popover, .ui-dp-popover, .dropdown-portal, .ui-floating-backdrop').forEach((el) => {
        if (el.classList.contains('is-inline-preview')) return;
        if (el.classList.contains('hidden') || el.parentNode === document.body) {
          el.remove();
        }
      });
    }

    function initInputSandboxDom() {
      if (typeof window.initFormUiComponents === 'function') {
        window.initFormUiComponents();
      }
      if (inputSandboxViewMode === 'focused') {
        renderFocusedHiddenPreview();
      }
      if (typeof window.FmeUi?.syncAllFloatingScrolls === 'function') {
        requestAnimationFrame(() => window.FmeUi.syncAllFloatingScrolls());
      }
    }

    function renderFocusedHiddenPreview() {
      const host = document.getElementById('iuInlinePreviewHost');
      const card = document.querySelector('#iuFocusedContainer .iu-focused-card');
      if (!host || !card) return;
      const target = card.querySelector('.ui-input-component');
      if (!target) return;

      const detail = { host };
      if (activeInputType === 'cropImage') {
        detail.imgUrl = createStudioDemoImageDataUrl();
      }
      target.dispatchEvent(new CustomEvent('fme:renderInlinePreview', { detail }));
      applyLocalCssVarsLiveToDom(activeInputType);
    }

    function renderInputSandboxFromCache() {
      if (inputSandboxViewMode === 'formBuilder') {
        renderFormBuilderView();
        return;
      }
      const galleryEl = document.getElementById('iuGalleryContainer');
      const focusedEl = document.getElementById('iuFocusedContainer');
      if (!galleryEl || !focusedEl) return;

      cleanStaleInputPortals();

      if (inputSandboxViewMode === 'gallery') {
        galleryEl.innerHTML = '';
        INPUT_CATALOG.forEach((item) => {
          const card = document.createElement('div');
          card.className = 'iu-input-card' + (item.type === activeInputType ? ' active' : '');
          card.dataset.inputType = item.type;
          card.addEventListener('pointerdown', () => {
            if (activeInputType !== item.type) {
              selectInputType(item.type, false);
            }
          }, true);

          const head = document.createElement('div');
          head.className = 'iu-card-head';
          head.onclick = () => selectInputType(item.type, false);

          const title = document.createElement('div');
          title.className = 'iu-card-title';
          title.innerHTML = `<span>${item.icon}</span><span>${escIuHtml(item.title)}</span>`;

          const actions = document.createElement('div');
          actions.className = 'iu-card-actions';

          if (item.demoBtn) {
            const demoBtn = document.createElement('button');
            demoBtn.type = 'button';
            demoBtn.className = 'iu-card-pill-btn';
            demoBtn.textContent = item.demoBtn;
            demoBtn.onclick = (e) => {
              e.stopPropagation();
              selectInputType(item.type, false);
              openCropDemoFromStudio();
            };
            actions.appendChild(demoBtn);
          }

          const focusBtn = document.createElement('button');
          focusBtn.type = 'button';
          focusBtn.className = 'iu-card-pill-btn';
          focusBtn.textContent = '🔍 Enfocar';
          focusBtn.onclick = (e) => {
            e.stopPropagation();
            selectInputType(item.type, false);
            setInputSandboxViewMode('focused');
          };
          actions.appendChild(focusBtn);

          head.appendChild(title);
          head.appendChild(actions);

          const body = document.createElement('div');
          body.className = 'iu-card-body';
          body.innerHTML = inputsHtmlCache[item.type] || '<em>Cargando...</em>';

          card.appendChild(head);
          card.appendChild(body);
          galleryEl.appendChild(card);
        });
      } else {
        const item = INPUT_CATALOG.find(i => i.type === activeInputType) || INPUT_CATALOG[0];
        focusedEl.innerHTML = '';
        const card = document.createElement('div');
        card.className = 'iu-focused-card';
        card.style.maxWidth = inputFocusedWidth;

        const head = document.createElement('div');
        head.className = 'iu-card-head';
        head.innerHTML = `<div class="iu-card-title"><span>${item.icon}</span><span>${escIuHtml(item.title)} (<code>${escIuHtml(item.type)}</code>)</span></div>`;
        if (item.demoBtn) {
          const demoBtn = document.createElement('button');
          demoBtn.type = 'button';
          demoBtn.className = 'iu-card-pill-btn';
          demoBtn.textContent = item.demoBtn;
          demoBtn.onclick = () => openCropDemoFromStudio();
          head.appendChild(demoBtn);
        }

        const body = document.createElement('div');
        body.className = 'iu-card-body';
        body.innerHTML = inputsHtmlCache[item.type] || '<em>Cargando...</em>';

        card.appendChild(head);
        card.appendChild(body);
        focusedEl.appendChild(card);

        if (item.hasHiddenPreview) {
          const previewPanel = document.createElement('div');
          previewPanel.className = 'iu-inline-preview-panel';
          previewPanel.style.maxWidth = inputFocusedWidth;
          previewPanel.innerHTML = `<div class="iu-inline-preview-head"><span>👁️ ${escIuHtml(item.hiddenPreviewLabel || 'Vista previa oculta')}</span><span class="iu-inline-preview-badge">En vivo sin abrir</span></div><div class="iu-inline-preview-host" id="iuInlinePreviewHost"></div>`;
          focusedEl.appendChild(previewPanel);
        }
      }

      setTimeout(initInputSandboxDom, 0);
    }

    function setInputSandboxViewMode(viewMode) {
      inputSandboxViewMode = ['focused', 'formBuilder'].includes(viewMode) ? viewMode : 'gallery';
      const btnGallery = document.getElementById('btnIuViewGallery');
      const btnFocused = document.getElementById('btnIuViewFocused');
      const btnBuilder = document.getElementById('btnIuViewFormBuilder');
      const galleryEl = document.getElementById('iuGalleryContainer');
      const focusedEl = document.getElementById('iuFocusedContainer');
      const builderEl = document.getElementById('iuFormBuilderContainer');
      const widthWrap = document.getElementById('iuFocusedWidthWrap');

      const singleSidebar = document.getElementById('iuSingleInputSidebarWrap');
      const builderSidebar = document.getElementById('iuFormBuilderSidebarPanel');
      const phpTabLabel = document.getElementById('iuCodeTab_phpLabel');
      const singleOpts = document.getElementById('iuSingleCodeOptions');
      const fbOpts = document.getElementById('iuFbCodeOptions');

      if (btnGallery) btnGallery.classList.toggle('active', inputSandboxViewMode === 'gallery');
      if (btnFocused) btnFocused.classList.toggle('active', inputSandboxViewMode === 'focused');
      if (btnBuilder) btnBuilder.classList.toggle('active', inputSandboxViewMode === 'formBuilder');
      if (galleryEl) galleryEl.style.display = inputSandboxViewMode === 'gallery' ? 'grid' : 'none';
      if (focusedEl) focusedEl.style.display = inputSandboxViewMode === 'focused' ? 'flex' : 'none';
      if (builderEl) builderEl.style.display = inputSandboxViewMode === 'formBuilder' ? 'flex' : 'none';
      if (widthWrap) widthWrap.style.display = inputSandboxViewMode === 'focused' ? 'flex' : 'none';

      if (singleSidebar) singleSidebar.style.display = inputSandboxViewMode === 'formBuilder' ? 'none' : 'block';
      if (builderSidebar) builderSidebar.style.display = inputSandboxViewMode === 'formBuilder' ? 'block' : 'none';
      if (phpTabLabel) phpTabLabel.textContent = inputSandboxViewMode === 'formBuilder' ? 'Helper _form()' : 'Helper _input()';
      if (singleOpts) singleOpts.style.display = inputSandboxViewMode === 'formBuilder' ? 'none' : 'flex';
      if (fbOpts) fbOpts.style.display = inputSandboxViewMode === 'formBuilder' ? 'flex' : 'none';

      if (inputSandboxViewMode === 'formBuilder') {
        renderFormBuilderView();
        renderFormBuilderInspector();
        requestFormBuilderRender();
      } else {
        renderInputSandboxFromCache();
        updateInputCodeOutput();
      }
      saveWorkspaceState();
    }

    function toggleInputTouchSim() {
      inputSandboxTouchSim = !inputSandboxTouchSim;
      const btn = document.getElementById('btnIuTouchSim');
      const view = document.getElementById('inputSandboxView');
      if (btn) btn.classList.toggle('active', inputSandboxTouchSim);
      if (view) view.classList.toggle('is-touch-simulated', inputSandboxTouchSim);
      document.body.classList.toggle('is-touch-simulated', currentStageMode === 'inputs' && inputSandboxTouchSim);

      if (inputSandboxTouchSim && inputSandboxViewMode === 'focused') {
        setInputFocusedWidth('375px');
        const sel = document.getElementById('iuFocusedWidthSelect');
        if (sel) sel.value = '375px';
      }
      if (!isRestoringWorkspace) {
        showToast(inputSandboxTouchSim ? '👆 Simulación Touch / Bottom-Sheet activada' : '🖥️ Simulación Touch desactivada');
      }
      saveWorkspaceState();
    }

    function setInputStageSurface(surfaceVal) {
      inputStageSurface = surfaceVal || 'var(--body)';
      injectLiveThemeIntoInputSandbox();
      saveWorkspaceState();
    }

    function setInputFocusedWidth(widthVal) {
      inputFocusedWidth = widthVal || '100%';
      document.querySelectorAll('#iuFocusedContainer .iu-focused-card, #iuFocusedContainer .iu-inline-preview-panel').forEach((el) => {
        el.style.maxWidth = inputFocusedWidth;
      });
      const autoMobile = (inputFocusedWidth === '375px') || inputSandboxTouchSim;
      const view = document.getElementById('inputSandboxView');
      if (view) view.classList.toggle('is-touch-simulated', autoMobile);
      document.body.classList.toggle('is-touch-simulated', currentStageMode === 'inputs' && autoMobile);
      saveWorkspaceState();
    }

    function createStudioDemoImageDataUrl() {
      const canvas = document.createElement('canvas');
      canvas.width = 640;
      canvas.height = 640;
      const ctx = canvas.getContext('2d');
      const grad = ctx.createLinearGradient(0, 0, 640, 640);
      grad.addColorStop(0, '#0f172a');
      grad.addColorStop(0.5, '#1e3a8a');
      grad.addColorStop(1, '#0284c7');
      ctx.fillStyle = grad;
      ctx.fillRect(0, 0, 640, 640);
      ctx.fillStyle = 'rgba(255,255,255,0.16)';
      ctx.beginPath();
      ctx.arc(320, 260, 120, 0, Math.PI * 2);
      ctx.fill();
      ctx.beginPath();
      ctx.arc(320, 560, 200, Math.PI, 0);
      ctx.fill();
      ctx.fillStyle = '#ffffff';
      ctx.font = 'bold 34px sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText('FRAME DEMO CROP', 320, 340);
      return canvas.toDataURL('image/png');
    }

    function openCropDemoFromStudio() {
      if (currentStageMode !== 'inputs') {
        setStageMode('inputs');
      }
      const cropInput = document.querySelector('#inputSandboxView .selectAndCropImage');
      if (!cropInput) {
        showToast('Selecciona primero Recorte Imagen para probar el modal', 'error');
        return;
      }
      cropInput.dispatchEvent(new CustomEvent('fme:openCropDemo', { bubbles: true, detail: { imgUrl: createStudioDemoImageDataUrl() } }));
    }

    function scheduleInputRender(type) {
      clearTimeout(inputRenderTimer);
      inputRenderTimer = setTimeout(() => {
        requestInputRender(type, true);
      }, 140);
    }

    async function requestInputRender(type = 'all', updateStage = true) {
      const seq = ++inputRenderSeq;
      if (inputRenderAbort) {
        try { inputRenderAbort.abort(); } catch (_) {}
      }
      inputRenderAbort = new AbortController();

      const cfg = ensureInputsConfig();
      const cleanGlobal = {};
      Object.keys(DEFAULT_INPUTS_CONFIG).forEach((k) => {
        if (cfg[k] !== undefined && cfg[k] !== null && cfg[k] !== '') {
          cleanGlobal[k] = cfg[k];
        }
      });

      const payload = {
        type: type || activeInputType,
        activeInput: activeInputType,
        globalConfig: cleanGlobal,
        localParamsByType: cfg.localOverrides || {},
        codeOptions: getInputCodeOptionsPayload()
      };

      try {
        const res = await fetch('/_frame/theme-studio/render-input', {
          method: 'POST',
          headers: getStudioHeaders(),
          body: JSON.stringify(payload),
          signal: inputRenderAbort.signal
        });
        const data = await res.json();
        if (seq !== inputRenderSeq || !data || !data.success) return;

        if (data.type === 'all' && data.inputs) {
          inputsHtmlCache = Object.assign({}, data.inputs);
        } else if (data.type && data.html !== undefined) {
          inputsHtmlCache[data.type] = data.html;
        }

        if (typeof data.jitPreviewCss === 'string') {
          applyJitPreviewCss(data.jitPreviewCss);
        }

        inputCodeCache = {
          htmlSnippet: data.htmlSnippet || '',
          phpSnippet: data.phpSnippet || '',
          globalSnippet: data.globalSnippet || ''
        };
        if (inputSandboxViewMode !== 'formBuilder') {
          updateInputCodeOutput();
        }

        if (updateStage && currentStageMode === 'inputs' && inputSandboxViewMode !== 'formBuilder') {
          renderInputSandboxFromCache();
        }
      } catch (err) {
        if (err && err.name === 'AbortError') return;
      }
    }

    function updateInputCodeOutput() {
      if (inputSandboxViewMode === 'formBuilder') {
        updateFormBuilderCodeOutput();
        return;
      }
      const codeEl = document.getElementById('inputCodeOutput');
      if (!codeEl) return;
      if (inputCodeFormat === 'php') {
        codeEl.textContent = inputCodeCache.phpSnippet || `<?= _input('${activeInputType}') ?>`;
      } else if (inputCodeFormat === 'global') {
        codeEl.textContent = inputCodeCache.globalSnippet || '';
      } else {
        codeEl.textContent = inputCodeCache.htmlSnippet || inputsHtmlCache[activeInputType] || '';
      }
    }

    function setInputCopyFormat(fmt) {
      inputCodeFormat = (fmt === 'html' || fmt === 'global') ? fmt : 'php';
      ['php', 'html', 'global'].forEach((k) => {
        const tab = document.getElementById('iuCodeTab_' + k);
        if (tab) tab.classList.toggle('active', k === inputCodeFormat);
      });
      updateInputCodeOutput();
      saveWorkspaceState();
    }

    function onInputCodeOptionChange() {
      requestInputRender(activeInputType, false);
    }

    function toggleInputCodeDock() {
      const dock = document.getElementById('iuCodeDock');
      const btn = document.getElementById('btnToggleInputCodeDock');
      if (!dock) return;
      dock.classList.toggle('collapsed');
      if (btn) btn.textContent = dock.classList.contains('collapsed') ? '▸' : '▾';
      saveWorkspaceState();
    }

    function copyInputCode() {
      const codeEl = document.getElementById('inputCodeOutput');
      const txt = codeEl ? codeEl.textContent : '';
      if (!txt) return;
      navigator.clipboard.writeText(txt).then(() => {
        const phpLbl = inputSandboxViewMode === 'formBuilder' ? 'Helper _form()' : 'Helper _input()';
        const label = inputCodeFormat === 'html' ? 'HTML' : (inputCodeFormat === 'php' ? phpLbl : 'PHP Global');
        showToast(`📋 Código ${label} copiado al portapapeles`);
      });
    }

    // ==============================================================
    // DISEÑADOR VISUAL DE FORMULARIOS (FORM BUILDER - FASE 7)
    // ==============================================================

    const FORM_TEMPLATES = {
      contact: {
        label: '📩 Contacto',
        formConfig: {
          action: '/contacto',
          method: 'POST',
          id: 'formContacto',
          autoSubmit: false,
          fetchPreview: false,
          csrf: true,
          showSubmitButton: true,
          submitLabel: 'Enviar mensaje',
          multiStep: false,
          stepProgress: true,
          prevLabel: 'Anterior',
          nextLabel: 'Siguiente',
          formClass: 'br15 p20 back1',
          gridClass: 'grid col-desk-2 gap15'
        },
        stepsMeta: [{ title: 'Datos de contacto', description: 'Cuéntanos tu consulta', stepClass: '', validateUrl: '' }],
        fields: [
          { uid: 'fb_c1', type: 'textInput', inputType: 'text', name: 'nombre', label: 'Nombre completo', placeholder: 'Tu nombre completo', required: true, wrapperClass: '', step: 0 },
          { uid: 'fb_c2', type: 'textInput', inputType: 'email', name: 'correo', label: 'Correo electrónico', placeholder: 'nombre@empresa.com', required: true, wrapperClass: '', step: 0 },
          { uid: 'fb_c3', type: 'dropdown', name: 'motivo', label: 'Motivo de consulta', options: 'soporte:Soporte Técnico, ventas:Cotización, feedback:Sugerencia', wrapperClass: 'span-desk-2', step: 0 },
          { uid: 'fb_c4', type: 'textLimiter', name: 'mensaje', label: 'Mensaje', placeholder: 'Escribe los detalles...', limit: 240, rows: 3, helperText: 'Responderemos en menos de 24 horas hábiles.', wrapperClass: 'span-desk-2', step: 0 }
        ]
      },
      register: {
        label: '👤 Registro + Avatar',
        formConfig: {
          action: '/auth/registro',
          method: 'POST',
          id: 'formRegistro',
          autoSubmit: false,
          fetchPreview: false,
          csrf: true,
          showSubmitButton: true,
          submitLabel: 'Crear cuenta',
          multiStep: false,
          stepProgress: true,
          prevLabel: 'Anterior',
          nextLabel: 'Siguiente',
          formClass: 'br15 p20 back1',
          gridClass: 'grid col-desk-2 gap15'
        },
        stepsMeta: [{ title: 'Registro de cuenta', description: '', stepClass: '', validateUrl: '' }],
        fields: [
          { uid: 'fb_r1', type: 'cropImage', name: 'avatar', label: 'Foto de perfil', cropShape: 'circle', croppingSize: '400x400', wrapperClass: 'span-desk-2', step: 0 },
          { uid: 'fb_r2', type: 'textInput', inputType: 'text', name: 'usuario', label: 'Usuario', placeholder: '@tuusuario', required: true, step: 0 },
          { uid: 'fb_r3', type: 'passwordInput', name: 'password', label: 'Contraseña', showStrength: true, minlength: 8, required: true, step: 0 },
          { uid: 'fb_r4', type: 'checkboxSwitch', name: 'terminos', label: 'Acepto términos y condiciones', checked: true, wrapperClass: 'span-desk-2', step: 0 }
        ]
      },
      checkout: {
        label: '🛒 Checkout Multi-Paso',
        formConfig: {
          action: '/checkout/confirmar',
          method: 'POST',
          id: 'formCheckout',
          autoSubmit: false,
          fetchPreview: false,
          csrf: true,
          showSubmitButton: true,
          submitLabel: 'Confirmar Pedido',
          multiStep: true,
          stepProgress: true,
          prevLabel: 'Anterior',
          nextLabel: 'Siguiente paso',
          formClass: 'br15 p20 back1',
          gridClass: 'grid col-desk-2 gap15'
        },
        stepsMeta: [
          { title: 'Envío', description: 'Dirección y modalidad de entrega', stepClass: '', validateUrl: '/api/validar-envio' },
          { title: 'Pago & Confirmación', description: 'Método de pago y código de verificación', stepClass: '', validateUrl: '' }
        ],
        fields: [
          { uid: 'fb_k1', type: 'textInput', inputType: 'text', name: 'destinatario', label: 'Nombre quien recibe', placeholder: 'Ej. Sofía Navarro', required: true, step: 0 },
          { uid: 'fb_k2', type: 'dropdown', name: 'tipo_envio', label: 'Método de entrega', value: 'domicilio', options: 'domicilio:Envío a domicilio, tienda:Retiro en sucursal', required: true, step: 0 },
          { uid: 'fb_k3', type: 'textInput', inputType: 'text', name: 'direccion', label: 'Dirección de entrega', placeholder: 'Calle, número y ciudad', showWhen: 'tipo_envio=domicilio', wrapperClass: 'span-desk-2', step: 0 },
          { uid: 'fb_k4', type: 'datePicker', name: 'fecha_entrega', label: 'Fecha preferida', showPresets: true, step: 1 },
          { uid: 'fb_k5', type: 'otpCodeInput', name: 'codigo_seguridad', label: 'Código PIN', length: 4, step: 1 }
        ]
      },
      filters: {
        label: '⚡ Filtros Auto-Submit',
        formConfig: {
          action: '/productos/filtrar',
          method: 'POST',
          id: 'formFiltros',
          autoSubmit: true,
          fetchPreview: true,
          csrf: true,
          showSubmitButton: false,
          submitLabel: 'Aplicar',
          multiStep: false,
          stepProgress: true,
          prevLabel: 'Anterior',
          nextLabel: 'Siguiente',
          formClass: 'br15 p15 back1',
          gridClass: 'grid col-desk-2 gap10'
        },
        stepsMeta: [{ title: 'Filtros', description: '', stepClass: '', validateUrl: '' }],
        fields: [
          { uid: 'fb_f1', type: 'textInput', inputType: 'search', name: 'q', label: 'Buscar en catálogo', placeholder: 'Buscar producto...', step: 0 },
          { uid: 'fb_f2', type: 'dropdown', name: 'categoria', label: 'Categoría', options: 'todas:Todas, tech:Tecnología, hogar:Hogar', value: 'todas', step: 0 },
          { uid: 'fb_f3', type: 'rangeSlider', name: 'precio', label: 'Rango de precio', mode: 'range', min: 10, max: 500, value: '50,300', suffix: '$', wrapperClass: 'span-desk-2', step: 0 },
          { uid: 'fb_f4', type: 'textareaInput', name: 'nota_local', label: 'Nota local (noAutoSubmit)', placeholder: 'No dispara envío automático al editar', noAutoSubmit: true, rows: 2, wrapperClass: 'span-desk-2', step: 0 }
        ]
      },
      survey: {
        label: '⭐ Encuesta',
        formConfig: {
          action: '/encuesta/responder',
          method: 'POST',
          id: 'formEncuesta',
          autoSubmit: false,
          fetchPreview: false,
          csrf: true,
          showSubmitButton: true,
          submitLabel: 'Enviar respuestas',
          multiStep: false,
          stepProgress: true,
          prevLabel: 'Anterior',
          nextLabel: 'Siguiente',
          formClass: 'br15 p20 back1',
          gridClass: 'flex-column gap15'
        },
        stepsMeta: [{ title: 'Encuesta de experiencia', description: '', stepClass: '', validateUrl: '' }],
        fields: [
          { uid: 'fb_s1', type: 'ratingInput', name: 'calificacion', label: '¿Cómo calificas el servicio?', max: 5, showValue: true, step: 0 },
          { uid: 'fb_s2', type: 'tagsInput', name: 'aspectos', label: 'Aspectos destacados', value: 'Rapidez, Calidad', step: 0 },
          { uid: 'fb_s3', type: 'repeatCamp', name: 'sugerencias[]', label: 'Sugerencias adicionales', max: 5, addLabel: '+ Agrega otra sugerencia', step: 0 },
          { uid: 'fb_s4', type: 'textareaInput', name: 'comentarios', label: 'Comentario abierto', placeholder: 'Tu opinión nos ayuda a mejorar...', rows: 3, step: 0 }
        ]
      }
    };

    function createDefaultFormBuilderState() {
      const tpl = JSON.parse(JSON.stringify(FORM_TEMPLATES.contact));
      return {
        formConfig: tpl.formConfig,
        stepsMeta: tpl.stepsMeta,
        fields: tpl.fields,
        selectedFieldUid: tpl.fields[0]?.uid || null,
        inspectorTab: 'form',
        activeStepFilter: 0
      };
    }

    let formBuilderState = createDefaultFormBuilderState();
    let formBuilderCodeCache = { phpFormSnippet: '', phpFieldsSnippet: '', htmlSnippet: '', globalSnippet: '' };
    let formBuilderLastHtml = '';
    let formBuilderRenderTimer = null;
    let formBuilderRenderAbort = null;
    let formBuilderRenderSeq = 0;

    function fbNextUid() {
      return 'fb_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 6);
    }

    function sanitizeFormBuilderState(raw) {
      if (!raw || typeof raw !== 'object' || !raw.formConfig || !Array.isArray(raw.fields)) {
        return createDefaultFormBuilderState();
      }
      const clean = {
        formConfig: Object.assign({}, FORM_TEMPLATES.contact.formConfig, raw.formConfig),
        stepsMeta: Array.isArray(raw.stepsMeta) && raw.stepsMeta.length > 0
          ? raw.stepsMeta.map((s, i) => ({
              title: String(s?.title ?? `Paso ${i + 1}`),
              description: String(s?.description ?? ''),
              stepClass: String(s?.stepClass ?? ''),
              validateUrl: String(s?.validateUrl ?? '')
            }))
          : [{ title: 'Paso 1', description: '', stepClass: '', validateUrl: '' }],
        fields: raw.fields.filter(f => f && typeof f === 'object' && INPUT_CATALOG.some(c => c.type === f.type)).map(f => Object.assign({}, f, { uid: f.uid || fbNextUid(), step: Number.isFinite(Number(f.step)) ? Math.max(0, parseInt(f.step, 10)) : 0 })),
        selectedFieldUid: raw.selectedFieldUid || null,
        inspectorTab: (raw.inspectorTab === 'field') ? 'field' : 'form',
        activeStepFilter: Number.isFinite(Number(raw.activeStepFilter)) ? parseInt(raw.activeStepFilter, 10) : 0
      };
      if (!clean.fields.some(f => f.uid === clean.selectedFieldUid)) {
        clean.selectedFieldUid = clean.fields[0]?.uid || null;
      }
      return clean;
    }

    function loadFormBuilderTemplate(tplKey) {
      const tpl = FORM_TEMPLATES[tplKey];
      if (!tpl) return;
      const cloned = JSON.parse(JSON.stringify(tpl));
      formBuilderState = {
        formConfig: cloned.formConfig,
        stepsMeta: cloned.stepsMeta,
        fields: cloned.fields,
        selectedFieldUid: cloned.fields[0]?.uid || null,
        inspectorTab: 'form',
        activeStepFilter: 0
      };
      renderFormBuilderView();
      renderFormBuilderInspector();
      requestFormBuilderRender();
      saveWorkspaceState();
      showToast(`🧱 Plantilla cargada: ${tpl.label}`);
    }

    function clearFormBuilderFields() {
      formBuilderState.fields = [];
      formBuilderState.selectedFieldUid = null;
      renderFormBuilderView();
      renderFormBuilderInspector();
      requestFormBuilderRender();
      saveWorkspaceState();
    }

    function switchFormBuilderInspectorTab(tab) {
      formBuilderState.inspectorTab = (tab === 'field') ? 'field' : 'form';
      renderFormBuilderInspector();
      saveWorkspaceState();
    }

    function fbAddField(type) {
      const meta = INPUT_CATALOG.find(i => i.type === type);
      if (!meta) return;
      const existingSame = formBuilderState.fields.filter(f => f.type === type).length;
      const suffix = existingSame > 0 ? `_${existingSame + 1}` : '';
      const uid = fbNextUid();
      const targetStep = formBuilderState.formConfig.multiStep
        ? Math.max(0, Math.min(formBuilderState.stepsMeta.length - 1, formBuilderState.activeStepFilter >= 0 ? formBuilderState.activeStepFilter : 0))
        : 0;

      const baseNameMap = {
        cropImage: 'avatar', loadImgModern: 'galeria[]', colorPicker: 'color',
        checkboxSwitch: 'activo', dropdown: 'opcion', datePicker: 'fecha',
        timePicker: 'hora', rangeSlider: 'rango', passwordInput: 'password',
        otpCodeInput: 'codigo', tagsInput: 'etiquetas', stepperInput: 'cantidad',
        ratingInput: 'valoracion', fileDropzone: 'archivos[]', repeatCamp: 'items[]',
        textLimiter: 'resumen', textInput: 'campo_texto', textareaInput: 'comentario',
        submitButton: 'enviar_btn'
      };
      const newField = {
        uid,
        type,
        name: (baseNameMap[type] || 'campo').replace(/\[\]$/, '') + suffix + (String(baseNameMap[type] || '').endsWith('[]') ? '[]' : ''),
        label: meta.defaultLabel ? `${meta.defaultLabel}${existingSame > 0 ? ' ' + (existingSame + 1) : ''}` : '',
        step: targetStep
      };
      if (type === 'submitButton') {
        newField.text = 'Enviar';
        newField.variant = 'primary';
      }
      formBuilderState.fields.push(newField);
      formBuilderState.selectedFieldUid = uid;
      formBuilderState.inspectorTab = 'field';

      renderFormBuilderView();
      renderFormBuilderInspector();
      requestFormBuilderRender();
      saveWorkspaceState();
    }

    function fbSelectField(uid) {
      formBuilderState.selectedFieldUid = uid;
      formBuilderState.inspectorTab = 'field';
      renderFormBuilderView();
      renderFormBuilderInspector();
      saveWorkspaceState();
    }

    function fbMoveField(uid, dir) {
      const idx = formBuilderState.fields.findIndex(f => f.uid === uid);
      if (idx === -1) return;
      const targetIdx = idx + dir;
      if (targetIdx < 0 || targetIdx >= formBuilderState.fields.length) return;
      const [item] = formBuilderState.fields.splice(idx, 1);
      formBuilderState.fields.splice(targetIdx, 0, item);
      renderFormBuilderView();
      requestFormBuilderRender();
      saveWorkspaceState();
    }

    function fbDuplicateField(uid) {
      const idx = formBuilderState.fields.findIndex(f => f.uid === uid);
      if (idx === -1) return;
      const copy = JSON.parse(JSON.stringify(formBuilderState.fields[idx]));
      copy.uid = fbNextUid();
      if (copy.name) {
        copy.name = String(copy.name).replace(/(\[\])?$/, '_copia$1');
      }
      if (copy.label) {
        copy.label = `${copy.label} (copia)`;
      }
      formBuilderState.fields.splice(idx + 1, 0, copy);
      formBuilderState.selectedFieldUid = copy.uid;
      formBuilderState.inspectorTab = 'field';
      renderFormBuilderView();
      renderFormBuilderInspector();
      requestFormBuilderRender();
      saveWorkspaceState();
    }

    function fbDeleteField(uid) {
      const idx = formBuilderState.fields.findIndex(f => f.uid === uid);
      if (idx === -1) return;
      formBuilderState.fields.splice(idx, 1);
      if (formBuilderState.selectedFieldUid === uid) {
        formBuilderState.selectedFieldUid = formBuilderState.fields[Math.min(idx, formBuilderState.fields.length - 1)]?.uid || null;
      }
      renderFormBuilderView();
      renderFormBuilderInspector();
      requestFormBuilderRender();
      saveWorkspaceState();
    }

    function fbAddStep() {
      formBuilderState.formConfig.multiStep = true;
      const num = formBuilderState.stepsMeta.length + 1;
      formBuilderState.stepsMeta.push({
        title: `Paso ${num}`,
        description: '',
        stepClass: '',
        validateUrl: ''
      });
      formBuilderState.activeStepFilter = num - 1;
      renderFormBuilderView();
      renderFormBuilderInspector();
      requestFormBuilderRender();
      saveWorkspaceState();
    }

    function fbDeleteStep(stepIdx) {
      if (formBuilderState.stepsMeta.length <= 1) return;
      formBuilderState.stepsMeta.splice(stepIdx, 1);
      formBuilderState.fields.forEach((f) => {
        if (f.step >= formBuilderState.stepsMeta.length) {
          f.step = formBuilderState.stepsMeta.length - 1;
        }
      });
      if (formBuilderState.activeStepFilter >= formBuilderState.stepsMeta.length) {
        formBuilderState.activeStepFilter = formBuilderState.stepsMeta.length - 1;
      }
      renderFormBuilderView();
      renderFormBuilderInspector();
      requestFormBuilderRender();
      saveWorkspaceState();
    }

    function fbSelectStepFilter(stepIdx) {
      formBuilderState.activeStepFilter = stepIdx;
      renderFormBuilderView();
      saveWorkspaceState();
    }

    function fbUpdateFormProp(key, val) {
      formBuilderState.formConfig[key] = val;
      if (key === 'multiStep' && val && formBuilderState.stepsMeta.length < 2) {
        formBuilderState.stepsMeta.push({ title: 'Paso 2', description: '', stepClass: '', validateUrl: '' });
      }
      if (key === 'multiStep' || key === 'autoSubmit') {
        renderFormBuilderView();
        renderFormBuilderInspector();
      }
      clearTimeout(formBuilderRenderTimer);
      formBuilderRenderTimer = setTimeout(() => {
        requestFormBuilderRender();
      }, 150);
      saveWorkspaceState();
    }

    function fbUpdateStepProp(stepIdx, key, val) {
      if (!formBuilderState.stepsMeta[stepIdx]) return;
      formBuilderState.stepsMeta[stepIdx][key] = val;
      if (key === 'title') {
        renderFormBuilderView();
      }
      clearTimeout(formBuilderRenderTimer);
      formBuilderRenderTimer = setTimeout(() => {
        requestFormBuilderRender();
      }, 150);
      saveWorkspaceState();
    }

    function fbUpdateFieldProp(key, val) {
      const fld = formBuilderState.fields.find(f => f.uid === formBuilderState.selectedFieldUid);
      if (!fld) return;
      if (val === null || val === undefined || val === '') {
        delete fld[key];
      } else {
        fld[key] = val;
      }
      if (key === 'type') {
        renderFormBuilderInspector();
      }
      if (['type', 'name', 'label', 'step'].includes(key)) {
        renderFormBuilderView();
      }
      clearTimeout(formBuilderRenderTimer);
      formBuilderRenderTimer = setTimeout(() => {
        requestFormBuilderRender();
      }, 150);
      saveWorkspaceState();
    }

    function renderFormBuilderInspector() {
      const tabForm = document.getElementById('fbSubTab_form');
      const tabField = document.getElementById('fbSubTab_field');
      const host = document.getElementById('fbSidebarInspectorHost');
      if (!host) return;

      if (tabForm) tabForm.classList.toggle('active', formBuilderState.inspectorTab === 'form');
      if (tabField) tabField.classList.toggle('active', formBuilderState.inspectorTab === 'field');

      if (formBuilderState.inspectorTab === 'form') {
        const fc = formBuilderState.formConfig;
        const stepsHtml = fc.multiStep ? `
          <div class="iu-group-box">
            <div class="iu-group-head">
              <span>Pasos del Asistente (${formBuilderState.stepsMeta.length})</span>
              <button type="button" class="iu-quick-chip-btn" onclick="fbAddStep()">+ Paso</button>
            </div>
            ${formBuilderState.stepsMeta.map((st, idx) => `
              <div style="border:1px solid var(--ts-border); border-radius:8px; padding:8px; display:flex; flex-direction:column; gap:6px; background:rgba(15,23,42,0.5);">
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.72rem; font-weight:700;">
                  <span>Paso ${idx + 1}</span>
                  ${formBuilderState.stepsMeta.length > 1 ? `<button type="button" class="fb-mini-act danger" onclick="fbDeleteStep(${idx})">Eliminar</button>` : ''}
                </div>
                <input type="text" class="ts-input" value="${escIuHtml(st.title)}" placeholder="Título del paso" oninput="fbUpdateStepProp(${idx}, 'title', this.value)">
                <input type="text" class="ts-input" value="${escIuHtml(st.description)}" placeholder="Descripción opcional" oninput="fbUpdateStepProp(${idx}, 'description', this.value)">
                <div class="iu-jit-input-wrap">
                  <input type="text" id="fb_stepClass_${idx}" class="ts-input" value="${escIuHtml(st.stepClass)}" placeholder="Clases JIT paso (ej. col-desk-2)" oninput="fbUpdateStepProp(${idx}, 'stepClass', this.value)">
                  <button type="button" class="iu-jit-trigger-btn" onclick="toggleJitAssistant('fb_stepClass_${idx}', this)">⚡ JIT</button>
                </div>
                <input type="text" class="ts-input" value="${escIuHtml(st.validateUrl)}" placeholder="URL AJAX validación (opcional)" oninput="fbUpdateStepProp(${idx}, 'validateUrl', this.value)">
              </div>
            `).join('')}
          </div>
        ` : '';

        host.innerHTML = `
          <div class="iu-group-box">
            <div class="iu-group-head"><span>Atributos &lt;form&gt;</span></div>
            <div class="iu-color-pair">
              <div class="ts-field">
                <label>Action URL</label>
                <input type="text" class="ts-input" value="${escIuHtml(fc.action || '')}" placeholder="/procesar" oninput="fbUpdateFormProp('action', this.value)">
              </div>
              <div class="ts-field">
                <label>Método</label>
                <select class="ts-input" onchange="fbUpdateFormProp('method', this.value)">
                  <option value="POST" ${fc.method !== 'GET' ? 'selected' : ''}>POST</option>
                  <option value="GET" ${fc.method === 'GET' ? 'selected' : ''}>GET</option>
                </select>
              </div>
            </div>
            <div class="iu-color-pair">
              <div class="ts-field">
                <label>ID Formulario</label>
                <input type="text" class="ts-input" value="${escIuHtml(fc.id || '')}" placeholder="miFormulario" oninput="fbUpdateFormProp('id', this.value)">
              </div>
              <div class="ts-field">
                <label>Botón Submit</label>
                <input type="text" class="ts-input" value="${escIuHtml(fc.submitLabel || 'Enviar formulario')}" oninput="fbUpdateFormProp('submitLabel', this.value)">
              </div>
            </div>
            <div class="ts-field">
              <label>Clases JIT Formulario (<code>formClass</code>)</label>
              <div class="iu-jit-input-wrap">
                <input type="text" id="fb_formClass" class="ts-input" value="${escIuHtml(fc.formClass || '')}" placeholder="ej. br15 p20 back1 shadow1" oninput="fbUpdateFormProp('formClass', this.value)">
                <button type="button" class="iu-jit-trigger-btn" onclick="toggleJitAssistant('fb_formClass', this)">⚡ JIT</button>
              </div>
            </div>
            <div class="ts-field">
              <label>Grilla de Campos (<code>gridClass</code>)</label>
              <div class="iu-jit-input-wrap">
                <input type="text" id="fb_gridClass" class="ts-input" value="${escIuHtml(fc.gridClass || '')}" placeholder="ej. grid col-desk-2 gap15" oninput="fbUpdateFormProp('gridClass', this.value)">
                <button type="button" class="iu-jit-trigger-btn" onclick="toggleJitAssistant('fb_gridClass', this)">⚡ JIT</button>
              </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-top:4px;">
              <label class="cs-check-row" style="margin:0;"><input type="checkbox" ${fc.autoSubmit ? 'checked' : ''} onchange="fbUpdateFormProp('autoSubmit', this.checked)"><span>⚡ Auto-Submit</span></label>
              <label class="cs-check-row" style="margin:0;"><input type="checkbox" ${fc.fetchPreview ? 'checked' : ''} onchange="fbUpdateFormProp('fetchPreview', this.checked)"><span>🔄 Fetch Preview</span></label>
              <label class="cs-check-row" style="margin:0;"><input type="checkbox" ${fc.csrf !== false ? 'checked' : ''} onchange="fbUpdateFormProp('csrf', this.checked)"><span>🔒 CSRF Automático</span></label>
              <label class="cs-check-row" style="margin:0;"><input type="checkbox" ${fc.showSubmitButton !== false ? 'checked' : ''} onchange="fbUpdateFormProp('showSubmitButton', this.checked)"><span>🚀 Mostrar Submit</span></label>
              <label class="cs-check-row" style="margin:0;"><input type="checkbox" ${fc.multiStep ? 'checked' : ''} onchange="fbUpdateFormProp('multiStep', this.checked)"><span>🪜 Multi-Paso</span></label>
              <label class="cs-check-row" style="margin:0;"><input type="checkbox" ${fc.stepProgress !== false ? 'checked' : ''} onchange="fbUpdateFormProp('stepProgress', this.checked)"><span>📊 Barra Progreso</span></label>
            </div>
            ${fc.multiStep ? `
              <div class="iu-color-pair" style="margin-top:6px;">
                <div class="ts-field"><label>Texto Anterior</label><input type="text" class="ts-input" value="${escIuHtml(fc.prevLabel || 'Anterior')}" oninput="fbUpdateFormProp('prevLabel', this.value)"></div>
                <div class="ts-field"><label>Texto Siguiente</label><input type="text" class="ts-input" value="${escIuHtml(fc.nextLabel || 'Siguiente')}" oninput="fbUpdateFormProp('nextLabel', this.value)"></div>
              </div>
            ` : ''}
          </div>
          ${stepsHtml}
        `;
      } else {
        const fld = formBuilderState.fields.find(f => f.uid === formBuilderState.selectedFieldUid);
        if (!fld) {
          host.innerHTML = `<div class="fb-empty-state">Selecciona un campo en la lista o añade uno desde la barra superior para editar sus propiedades.</div>`;
          return;
        }
        const fc = formBuilderState.formConfig;
        const stepSelectHtml = fc.multiStep ? `
          <div class="ts-field">
            <label>Asignar al Paso</label>
            <select class="ts-input" onchange="fbUpdateFieldProp('step', parseInt(this.value, 10))">
              ${formBuilderState.stepsMeta.map((st, idx) => `<option value="${idx}" ${(fld.step || 0) === idx ? 'selected' : ''}>Paso ${idx + 1}: ${escIuHtml(st.title || '')}</option>`).join('')}
            </select>
          </div>
        ` : '';

        host.innerHTML = `
          <div class="iu-group-box">
            <div class="iu-group-head"><span>Propiedades del Campo</span></div>
            <div class="iu-color-pair">
              <div class="ts-field">
                <label>Tipo de componente</label>
                <select class="ts-input" onchange="fbUpdateFieldProp('type', this.value)">
                  ${INPUT_CATALOG.map(c => `<option value="${escIuHtml(c.type)}" ${fld.type === c.type ? 'selected' : ''}>${c.icon} ${escIuHtml(c.title)}</option>`).join('')}
                </select>
              </div>
              ${stepSelectHtml || `<div class="ts-field"><label>Atributo <code>id</code></label><input type="text" class="ts-input" value="${escIuHtml(fld.id || '')}" placeholder="opcional" oninput="fbUpdateFieldProp('id', this.value)"></div>`}
            </div>
            <div class="iu-color-pair">
              <div class="ts-field">
                <label>Atributo <code>name</code></label>
                <input type="text" class="ts-input" value="${escIuHtml(fld.name || '')}" oninput="fbUpdateFieldProp('name', this.value)">
              </div>
              <div class="ts-field">
                <label>Texto Label</label>
                <input type="text" class="ts-input" value="${escIuHtml(fld.label || '')}" oninput="fbUpdateFieldProp('label', this.value)">
              </div>
            </div>
            <div class="iu-color-pair">
              <div class="ts-field">
                <label>Placeholder</label>
                <input type="text" class="ts-input" value="${escIuHtml(fld.placeholder || '')}" oninput="fbUpdateFieldProp('placeholder', this.value)">
              </div>
              <div class="ts-field">
                <label>Valor inicial</label>
                <input type="text" class="ts-input" value="${escIuHtml(fld.value || '')}" oninput="fbUpdateFieldProp('value', this.value)">
              </div>
            </div>
            <div class="ts-field">
              <label>Texto de ayuda (<code>helperText</code>)</label>
              <input type="text" class="ts-input" value="${escIuHtml(fld.helperText || '')}" oninput="fbUpdateFieldProp('helperText', this.value)">
            </div>
            <div class="ts-field">
              <label>Visibilidad condicional (<code>showWhen</code>)</label>
              <input type="text" class="ts-input" value="${escIuHtml(fld.showWhen || '')}" placeholder="ej. tipo_envio=domicilio" oninput="fbUpdateFieldProp('showWhen', this.value)">
            </div>
            <div class="ts-field">
              <label>Clases JIT Contenedor (<code>wrapperClass</code>)</label>
              <div class="iu-jit-input-wrap">
                <input type="text" id="fb_fldWrapperClass" class="ts-input" value="${escIuHtml(fld.wrapperClass || '')}" placeholder="ej. span-desk-2 br15 p10" oninput="fbUpdateFieldProp('wrapperClass', this.value)">
                <button type="button" class="iu-jit-trigger-btn" onclick="toggleJitAssistant('fb_fldWrapperClass', this)">⚡ JIT</button>
              </div>
            </div>
            <div class="ts-field">
              <label>Clases JIT Control (<code>class</code>)</label>
              <div class="iu-jit-input-wrap">
                <input type="text" id="fb_fldClass" class="ts-input" value="${escIuHtml(fld.class || '')}" placeholder="ej. focus-ring2" oninput="fbUpdateFieldProp('class', this.value)">
                <button type="button" class="iu-jit-trigger-btn" onclick="toggleJitAssistant('fb_fldClass', this)">⚡ JIT</button>
              </div>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
              <label class="cs-check-row" style="margin:0;"><input type="checkbox" ${fld.required ? 'checked' : ''} onchange="fbUpdateFieldProp('required', this.checked)"><span>required</span></label>
              <label class="cs-check-row" style="margin:0;"><input type="checkbox" ${fld.disabled ? 'checked' : ''} onchange="fbUpdateFieldProp('disabled', this.checked)"><span>disabled</span></label>
              <label class="cs-check-row" style="margin:0;"><input type="checkbox" ${fld.readonly ? 'checked' : ''} onchange="fbUpdateFieldProp('readonly', this.checked)"><span>readonly</span></label>
              <label class="cs-check-row" style="margin:0;"><input type="checkbox" ${fld.noAutoSubmit ? 'checked' : ''} onchange="fbUpdateFieldProp('noAutoSubmit', this.checked)"><span>noAutoSubmit</span></label>
              <label class="cs-check-row" style="margin:0;"><input type="checkbox" ${fld.processAutoSubmit ? 'checked' : ''} onchange="fbUpdateFieldProp('processAutoSubmit', this.checked)"><span>processAutoSubmit</span></label>
            </div>
          </div>
          <div class="iu-group-box">
            <div class="iu-group-head"><span>Atributos Específicos (${escIuHtml(fld.type)})</span></div>
            <div id="fbSpecificFieldAttrsContainer"></div>
          </div>
        `;
        renderSpecificInputAttrsPanelFor(
          document.getElementById('fbSpecificFieldAttrsContainer'),
          fld.type,
          fld,
          'fbUpdateFieldProp'
        );
      }
    }

    function mountFormBuilderPreviewHtml(html) {
      const host = document.getElementById('fbLiveFormHost');
      if (!host) return;
      host.innerHTML = html || '<div class="fb-empty-state">El formulario está vacío. Añade campos desde la barra superior.</div>';
      const formEl = host.querySelector('form[data-studio-preview="true"]');
      if (formEl) {
        formEl.addEventListener('submit', (ev) => {
          if (!formEl.classList.contains('auto-submit') && !formEl.hasAttribute('data-fetch-preview')) {
            ev.preventDefault();
            const statusEl = document.getElementById('fbSimulationStatus');
            if (statusEl) statusEl.textContent = '✅ Submit simulado';
            showToast('🚀 Submit clásico simulado correctamente (sin recarga)');
          }
        });
      }
      if (typeof window.initFormUiComponents === 'function') {
        window.initFormUiComponents();
      }
      if (typeof window.FmeUi?.syncAllFloatingScrolls === 'function') {
        requestAnimationFrame(() => window.FmeUi.syncAllFloatingScrolls());
      }
    }

    function renderFormBuilderView() {
      const container = document.getElementById('iuFormBuilderContainer');
      if (!container) return;

      const fc = formBuilderState.formConfig;
      const isMulti = !!fc.multiStep;

      if (!document.getElementById('fbLiveFormHost')) {
        const templatesHtml = `
          <div class="fb-templates-bar">
            <span class="fb-bar-label">Plantillas rápidas:</span>
            ${Object.entries(FORM_TEMPLATES).map(([k, tpl]) => `<button type="button" class="fb-template-btn" onclick="loadFormBuilderTemplate('${k}')">${escIuHtml(tpl.label)}</button>`).join('')}
            <button type="button" class="fb-template-btn" style="margin-left:auto;" onclick="clearFormBuilderFields()">🗑️ Vaciar</button>
          </div>
        `;

        const paletteHtml = `
          <div class="fb-palette-bar">
            <span class="fb-bar-label">Agregar campo (19):</span>
            ${INPUT_CATALOG.map(c => `<button type="button" class="fb-palette-btn" onclick="fbAddField('${c.type}')" title="Añadir ${escIuHtml(c.title)}">${c.icon} ${escIuHtml(c.title)}</button>`).join('')}
            <button type="button" class="fb-palette-btn is-step" onclick="fbAddStep()" title="Agregar paso al asistente Multi-Paso">➕ Nuevo Paso</button>
          </div>
        `;

        container.innerHTML = `
          ${templatesHtml}
          ${paletteHtml}
          <div class="fb-stage-grid">
            <div class="fb-structure-card">
              <div class="fb-stage-card-head">
                <span>🧩 Estructura</span>
                <span id="fbStructureCount" style="font-size:0.68rem; color:var(--ts-muted);">0 campos</span>
              </div>
              <div id="fbStepTabsHost"></div>
              <div class="fb-fields-list" id="fbFieldsListWrap"></div>
            </div>
            <div class="fb-stage-card">
              <div class="fb-stage-card-head">
                <span id="fbPreviewHeaderTitle">👁️ Vista Previa Interactiva</span>
                <span id="fbSimulationStatus" class="ts-badge">Listo</span>
              </div>
              <div class="fb-preview-host" id="fbLiveFormHost">
                <div class="fb-empty-state">Renderizando formulario en vivo...</div>
              </div>
            </div>
          </div>
        `;

        if (formBuilderLastHtml) {
          mountFormBuilderPreviewHtml(formBuilderLastHtml);
        }

        const listElInit = document.getElementById('fbFieldsListWrap');
        if (listElInit && typeof window.FmeUi?.attachFloatingScroll === 'function') {
          window.FmeUi.attachFloatingScroll(listElInit, container);
        }
      }

      const countEl = document.getElementById('fbStructureCount');
      if (countEl) {
        countEl.textContent = `${formBuilderState.fields.length} campos`;
      }

      const stepTabsHost = document.getElementById('fbStepTabsHost');
      if (stepTabsHost) {
        stepTabsHost.innerHTML = isMulti ? `
          <div class="fb-step-tabs">
            <button type="button" class="fb-step-tab ${formBuilderState.activeStepFilter === -1 ? 'active' : ''}" onclick="fbSelectStepFilter(-1)">Todos (${formBuilderState.fields.length})</button>
            ${formBuilderState.stepsMeta.map((st, idx) => {
              const count = formBuilderState.fields.filter(f => (f.step || 0) === idx).length;
              return `<button type="button" class="fb-step-tab ${formBuilderState.activeStepFilter === idx ? 'active' : ''}" onclick="fbSelectStepFilter(${idx})">Paso ${idx + 1} (${count})</button>`;
            }).join('')}
          </div>
        ` : '';
      }

      const visibleFields = isMulti && formBuilderState.activeStepFilter >= 0
        ? formBuilderState.fields.filter(f => (f.step || 0) === formBuilderState.activeStepFilter)
        : formBuilderState.fields;

      const listEl = document.getElementById('fbFieldsListWrap');
      if (listEl) {
        const prevScroll = listEl.scrollTop || 0;
        listEl.innerHTML = visibleFields.length === 0
          ? `<div class="fb-empty-state">Sin campos en esta vista.<br>Usa la barra superior para agregar campos.</div>`
          : visibleFields.map((fld) => {
              const meta = INPUT_CATALOG.find(c => c.type === fld.type) || { icon: '🔧', title: fld.type };
              const isSel = fld.uid === formBuilderState.selectedFieldUid;
              return `
                <div class="fb-field-item ${isSel ? 'is-selected' : ''}" onclick="fbSelectField('${fld.uid}')">
                  <div class="fb-field-toolbar">
                    <span>${meta.icon} ${escIuHtml(fld.label || meta.title)}</span>
                    <div class="fb-field-actions-row">
                      <button type="button" class="fb-mini-act" title="Subir" onclick="event.stopPropagation(); fbMoveField('${fld.uid}', -1)">↑</button>
                      <button type="button" class="fb-mini-act" title="Bajar" onclick="event.stopPropagation(); fbMoveField('${fld.uid}', 1)">↓</button>
                      <button type="button" class="fb-mini-act" title="Duplicar" onclick="event.stopPropagation(); fbDuplicateField('${fld.uid}')">⧉</button>
                      <button type="button" class="fb-mini-act danger" title="Eliminar" onclick="event.stopPropagation(); fbDeleteField('${fld.uid}')">✕</button>
                    </div>
                  </div>
                  <div style="font-size:0.68rem; color:var(--ts-muted); display:flex; justify-content:space-between; gap:6px;">
                    <span><code>${escIuHtml(fld.type)}</code> · name=<code>${escIuHtml(fld.name || '')}</code></span>
                    ${isMulti ? `<span>Paso ${(fld.step || 0) + 1}</span>` : ''}
                  </div>
                </div>
              `;
            }).join('');
        listEl.scrollTop = prevScroll;
        if (typeof window.FmeUi?.syncAllFloatingScrolls === 'function') {
          window.FmeUi.syncAllFloatingScrolls(listEl.parentElement || container);
        }
      }

      const headerTitleEl = document.getElementById('fbPreviewHeaderTitle');
      if (headerTitleEl) {
        const badges = [
          fc.multiStep ? `Multi-Paso (${formBuilderState.stepsMeta.length})` : 'Simple',
          fc.autoSubmit ? '⚡ Auto-Submit' : '🖱️ Submit Manual',
          `${formBuilderState.fields.length} campos`
        ].join(' · ');
        headerTitleEl.textContent = `👁️ Vista Previa Interactiva (${badges})`;
      }
    }

    function updateFormBuilderCodeOutput() {
      const codeEl = document.getElementById('inputCodeOutput');
      if (!codeEl) return;
      if (inputCodeFormat === 'php') {
        const useIndiv = !!document.getElementById('fbOptIndividualInputs')?.checked;
        codeEl.textContent = useIndiv
          ? (formBuilderCodeCache.phpFieldsSnippet || '')
          : (formBuilderCodeCache.phpFormSnippet || '');
      } else if (inputCodeFormat === 'global') {
        codeEl.textContent = formBuilderCodeCache.globalSnippet || '';
      } else {
        codeEl.textContent = formBuilderCodeCache.htmlSnippet || '';
      }
    }

    async function requestFormBuilderRender() {
      const seq = ++formBuilderRenderSeq;
      if (formBuilderRenderAbort) {
        try { formBuilderRenderAbort.abort(); } catch (_) {}
      }
      formBuilderRenderAbort = new AbortController();

      const cfg = ensureInputsConfig();
      const cleanGlobal = {};
      Object.keys(DEFAULT_INPUTS_CONFIG).forEach((k) => {
        if (cfg[k] !== undefined && cfg[k] !== null && cfg[k] !== '') {
          cleanGlobal[k] = cfg[k];
        }
      });

      const formConfigPayload = Object.assign({}, formBuilderState.formConfig, {
        stepsMeta: formBuilderState.stepsMeta
      });
      const fieldsPayload = formBuilderState.fields.map((f) => {
        const clone = Object.assign({}, f);
        delete clone.uid;
        return clone;
      });

      try {
        const res = await fetch('/_frame/theme-studio/render-form', {
          method: 'POST',
          headers: getStudioHeaders(),
          body: JSON.stringify({
            globalConfig: cleanGlobal,
            formConfig: formConfigPayload,
            stepsOrFields: fieldsPayload
          }),
          signal: formBuilderRenderAbort.signal
        });
        const data = await res.json();
        if (seq !== formBuilderRenderSeq) return;
        if (!data || !data.success) {
          const hostErr = document.getElementById('fbLiveFormHost');
          if (hostErr) {
            hostErr.innerHTML = `<div class="fb-empty-state">⚠️ ${escIuHtml(data?.message || 'No se pudo renderizar el formulario.')}</div>`;
          }
          return;
        }

        if (typeof data.jitPreviewCss === 'string') {
          applyJitPreviewCss(data.jitPreviewCss);
        }

        formBuilderLastHtml = data.html || '';
        formBuilderCodeCache = {
          phpFormSnippet: data.phpFormSnippet || '',
          phpFieldsSnippet: data.phpFieldsSnippet || '',
          htmlSnippet: data.htmlSnippet || '',
          globalSnippet: data.globalSnippet || ''
        };
        if (inputSandboxViewMode === 'formBuilder') {
          updateFormBuilderCodeOutput();
        }

        mountFormBuilderPreviewHtml(formBuilderLastHtml);
      } catch (err) {
        if (err && err.name === 'AbortError') return;
        const hostErr = document.getElementById('fbLiveFormHost');
        if (hostErr) {
          hostErr.innerHTML = `<div class="fb-empty-state">⚠️ Error de conexión al renderizar la vista previa del formulario.</div>`;
        }
      }
    }

    // ==============================================================
    // FASE 9: PERSISTENCIA DE ESTADO DEL ESPACIO DE TRABAJO
    // ==============================================================

    const WORKSPACE_STATE_KEY = 'frame_ts_workspace_state';

    function saveWorkspaceState() {
      if (isRestoringWorkspace) return;
      try {
        const sidebarEl = document.querySelector('.ts-sidebar');
        const activeTabEl = document.querySelector('.ts-tab-content.active');
        const csDock = document.getElementById('csCodeDock');
        const iuDock = document.getElementById('iuCodeDock');
        const routeInput = document.getElementById('projectRouteInput');

        const payload = {
          tab: currentTabId || 'themes',
          stage: currentStageMode || 'site',
          themeSlug: currentThemeSlug || activeThemeSlug || 'default',
          previewMode: currentPreviewMode || 'light',
          viewportWidth: currentViewportWidth || '100%',
          sidebarCollapsed: sidebarEl ? sidebarEl.classList.contains('collapsed') : false,
          sidebarScrollTop: activeTabEl ? Math.round(activeTabEl.scrollTop || 0) : 0,
          projectRoute: routeInput ? routeInput.value : '/',
          charts: {
            viewMode: typeof chartSandboxViewMode !== 'undefined' ? chartSandboxViewMode : 'gallery',
            activeChart: typeof activeChartType !== 'undefined' ? activeChartType : 'horizontalBars',
            surface: typeof chartCardSurface !== 'undefined' ? chartCardSurface : 'var(--body)',
            focusedWidth: typeof chartFocusedWidth !== 'undefined' ? chartFocusedWidth : '100%',
            codeFormat: typeof chartCodeFormat !== 'undefined' ? chartCodeFormat : 'helper',
            dockCollapsed: csDock ? csDock.classList.contains('collapsed') : false
          },
          inputs: {
            viewMode: inputSandboxViewMode || 'gallery',
            activeInput: activeInputType || 'cropImage',
            subTab: activeInputSubTab || 'global',
            touchSim: !!inputSandboxTouchSim,
            surface: inputStageSurface || 'var(--body)',
            focusedWidth: inputFocusedWidth || '100%',
            codeFormat: inputCodeFormat || 'php',
            dockCollapsed: iuDock ? iuDock.classList.contains('collapsed') : false,
            colorDockCollapsed: !!iuColorDockCollapsed,
            individualInputsCode: !!document.getElementById('fbOptIndividualInputs')?.checked,
            formBuilderDraft: formBuilderState
          }
        };
        window.localStorage.setItem(WORKSPACE_STATE_KEY, JSON.stringify(payload));

        const url = new URL(window.location.href);
        url.searchParams.set('tab', payload.tab);
        url.searchParams.set('stage', payload.stage);
        window.history.replaceState(null, '', url.toString());
      } catch (_) {}
    }

    function restoreWorkspaceState() {
      isRestoringWorkspace = true;
      try {
        let saved = null;
        try {
          const raw = window.localStorage.getItem(WORKSPACE_STATE_KEY);
          if (raw) saved = JSON.parse(raw);
        } catch (_) {}

        const urlParams = new URLSearchParams(window.location.search);
        const urlTab = urlParams.get('tab');
        const urlStage = urlParams.get('stage');

        if (saved && typeof saved === 'object') {
          if (saved.themeSlug && saved.themeSlug !== currentThemeSlug && fullThemeConfig?.themes?.[saved.themeSlug]) {
            const themeSel = document.getElementById('themeSelector');
            if (themeSel) themeSel.value = saved.themeSlug;
            switchThemeTo(saved.themeSlug);
          }

          if (saved.previewMode === 'dark' || saved.previewMode === 'light') {
            setPreviewMode(saved.previewMode);
          }

          if (saved.viewportWidth) {
            setViewport(saved.viewportWidth);
          }

          if (typeof saved.sidebarCollapsed === 'boolean') {
            toggleSidebar(saved.sidebarCollapsed);
          }

          if (saved.projectRoute && saved.projectRoute !== '/') {
            navigateProjectRoute(saved.projectRoute);
          }

          if (saved.charts && typeof saved.charts === 'object') {
            if (saved.charts.activeChart && typeof selectActiveChart === 'function') {
              selectActiveChart(saved.charts.activeChart, false);
            }
            if (saved.charts.surface && typeof setChartCardSurface === 'function') {
              const csSurfSel = document.getElementById('csCardSurfaceSelect');
              if (csSurfSel) csSurfSel.value = saved.charts.surface;
              setChartCardSurface(saved.charts.surface);
            }
            if (saved.charts.focusedWidth && typeof setFocusedCardWidth === 'function') {
              setFocusedCardWidth(saved.charts.focusedWidth);
            }
            if (saved.charts.codeFormat && typeof setChartCopyFormat === 'function') {
              setChartCopyFormat(saved.charts.codeFormat);
            }
            if (saved.charts.viewMode && typeof setChartSandboxViewMode === 'function') {
              setChartSandboxViewMode(saved.charts.viewMode);
            }
            const csDock = document.getElementById('csCodeDock');
            const csDockBtn = document.getElementById('btnToggleCodeDock');
            if (csDock && typeof saved.charts.dockCollapsed === 'boolean') {
              csDock.classList.toggle('collapsed', saved.charts.dockCollapsed);
              if (csDockBtn) csDockBtn.textContent = saved.charts.dockCollapsed ? '▸' : '▾';
            }
          }

          if (saved.inputs && typeof saved.inputs === 'object') {
            if (saved.inputs.formBuilderDraft) {
              formBuilderState = sanitizeFormBuilderState(saved.inputs.formBuilderDraft);
            }
            if (saved.inputs.activeInput && INPUT_CATALOG.some(i => i.type === saved.inputs.activeInput)) {
              activeInputType = saved.inputs.activeInput;
              renderInputTypeGrid();
            }
            if (saved.inputs.subTab) {
              switchInputSubTab(saved.inputs.subTab);
            }
            if (typeof saved.inputs.touchSim === 'boolean' && saved.inputs.touchSim !== inputSandboxTouchSim) {
              toggleInputTouchSim();
            }
            if (saved.inputs.surface) {
              const surfSel = document.getElementById('iuSurfaceSelect');
              if (surfSel) surfSel.value = saved.inputs.surface;
              setInputStageSurface(saved.inputs.surface);
            }
            if (saved.inputs.focusedWidth) {
              const fwSel = document.getElementById('iuFocusedWidthSelect');
              if (fwSel) fwSel.value = saved.inputs.focusedWidth;
              setInputFocusedWidth(saved.inputs.focusedWidth);
            }
            if (saved.inputs.codeFormat) {
              setInputCopyFormat(saved.inputs.codeFormat);
            }
            const indivChk = document.getElementById('fbOptIndividualInputs');
            if (indivChk && typeof saved.inputs.individualInputsCode === 'boolean') {
              indivChk.checked = saved.inputs.individualInputsCode;
            }
            const iuDock = document.getElementById('iuCodeDock');
            const iuDockBtn = document.getElementById('btnToggleInputCodeDock');
            if (iuDock && typeof saved.inputs.dockCollapsed === 'boolean') {
              iuDock.classList.toggle('collapsed', saved.inputs.dockCollapsed);
              if (iuDockBtn) iuDockBtn.textContent = saved.inputs.dockCollapsed ? '▸' : '▾';
            }
            if (typeof saved.inputs.colorDockCollapsed === 'boolean') {
              toggleIuColorChipsAccordion(saved.inputs.colorDockCollapsed);
            }
            if (saved.inputs.viewMode) {
              setInputSandboxViewMode(saved.inputs.viewMode);
            }
          }
        }

        const targetTab = (urlTab && document.getElementById('tab-' + urlTab))
          ? urlTab
          : ((saved?.tab && document.getElementById('tab-' + saved.tab)) ? saved.tab : 'themes');
        const validStages = ['site', 'charts', 'inputs', 'icons', 'jsmodules', 'config'];
        const targetStage = (urlStage && validStages.includes(urlStage))
          ? urlStage
          : ((saved?.stage && validStages.includes(saved.stage)) ? saved.stage : null);

        switchTab(targetTab);
        if (targetStage && targetStage !== currentStageMode) {
          setStageMode(targetStage);
        }

        const activeTabEl = document.getElementById('tab-' + targetTab);
        const scrollTopTarget = saved?.sidebarScrollTop || 0;
        if (activeTabEl && scrollTopTarget > 0) {
          requestAnimationFrame(() => {
            activeTabEl.scrollTop = scrollTopTarget;
          });
        }
      } finally {
        isRestoringWorkspace = false;
        saveWorkspaceState();
      }
    }

    function initInputStudio() {
      ensureInputsConfig();

      document.querySelectorAll('[data-iu-prop]').forEach((el) => {
        if (el.dataset.iuBound) return;
        el.dataset.iuBound = 'true';
        const prop = el.getAttribute('data-iu-prop');

        if (INPUT_PROP_TO_CSS_VAR[prop]) {
          el.addEventListener('focus', () => setFocusedInputTokenProp(prop));
          el.addEventListener('click', () => setFocusedInputTokenProp(prop));
        }

        const handleVal = () => {
          let val;
          if (el.type === 'checkbox') {
            val = el.checked;
          } else if (el.type === 'range' || el.type === 'number') {
            val = el.value === '' ? null : parseInt(el.value, 10);
          } else {
            val = el.value;
          }
          setInputDesignProp(prop, val, { liveOnly: el.type === 'range' });
          if (INPUT_PROP_TO_CSS_VAR[prop]) {
            const picker = document.querySelector(`[data-iu-picker-prop="${prop}"]`);
            if (picker) {
              const hex = resolveUiTokenToHex(val);
              if (/^#[0-9a-f]{6}$/i.test(hex)) picker.value = hex;
            }
          }
        };

        el.addEventListener('input', handleVal);
        if (el.type === 'range' || el.type === 'checkbox' || el.tagName === 'SELECT') {
          el.addEventListener('change', () => {
            let val;
            if (el.type === 'checkbox') val = el.checked;
            else if (el.type === 'range' || el.type === 'number') val = el.value === '' ? null : parseInt(el.value, 10);
            else val = el.value;
            setInputDesignProp(prop, val, { liveOnly: false });
          });
        }
      });

      document.querySelectorAll('[data-iu-picker-prop]').forEach((picker) => {
        if (picker.dataset.iuBound) return;
        picker.dataset.iuBound = 'true';
        const prop = picker.getAttribute('data-iu-picker-prop');

        picker.addEventListener('click', () => setFocusedInputTokenProp(prop));
        picker.addEventListener('focus', () => setFocusedInputTokenProp(prop));

        picker.addEventListener('input', () => {
          setFocusedInputTokenProp(prop);
          const hex = picker.value.toUpperCase();
          const target = document.querySelector(`[data-iu-prop="${prop}"]`);
          if (target) target.value = hex;
          setInputDesignProp(prop, hex, { liveOnly: true });
        });
        picker.addEventListener('change', () => {
          const hex = picker.value.toUpperCase();
          setInputDesignProp(prop, hex, { liveOnly: false });
        });
      });

      document.querySelectorAll('[data-iu-local]').forEach((el) => {
        if (el.dataset.iuBound) return;
        el.dataset.iuBound = 'true';
        el.addEventListener('input', () => {
          onInputLocalChange(el.getAttribute('data-iu-local'), el.value);
        });
      });

      if (!document.documentElement.dataset.fbSimListenersBound) {
        document.documentElement.dataset.fbSimListenersBound = '1';
        document.addEventListener('studio:autoSubmitSimulated', () => {
          const statusEl = document.getElementById('fbSimulationStatus');
          if (statusEl) statusEl.textContent = '⚡ Auto-Submit simulado';
          showToast('⚡ Auto-Submit simulado en vista previa (sin envío real)');
        });
        document.addEventListener('studio:stepValidated', (ev) => {
          const statusEl = document.getElementById('fbSimulationStatus');
          const url = ev?.detail?.validateUrl || '';
          if (statusEl) statusEl.textContent = '✓ Paso validado';
          showToast(`✅ Validación AJAX simulada (${url})`);
        });
      }

      renderInputTypeGrid();
      renderInputColorChips();
      switchInputSubTab(activeInputSubTab);
      setInputCopyFormat(inputCodeFormat);
      renderInputSandboxFromCache();
      requestInputRender(activeInputType, false);
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
