<?php
if (!defined('ROOT_PATH') || !class_exists(\Base\Studio\StudioKernel::class) || !\Base\Studio\StudioKernel::isDevEnvironment()) {
  http_response_code(404);
  exit;
}
?>
<!-- STAGE: GESTOR VISUAL DE CONFIGURACIÓN Y VAULT -->
<div id="configSandboxView" style="display:none; flex-direction:column; flex:1; width:100%; align-self:stretch; min-height:0; overflow-y:auto; padding:22px; gap:18px; background:var(--ts-bg); border-radius:10px; border:1px solid var(--ts-border);">
  <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; background:var(--ts-surface); border:1px solid var(--ts-border); border-radius:12px; padding:16px 20px;">
    <div>
      <h2 style="font-size:1.1rem; font-weight:700; color:#fff; margin-bottom:4px;">⚙️ Configuración de Entorno y Vault Cifrado</h2>
      <p id="cfgStageSubtitle" style="font-size:0.82rem; color:var(--ts-muted);">Editando perfil Local (.env). Las constantes críticas del núcleo y URLs dinámicas están protegidas automáticamente.</p>
    </div>
    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
      <button type="button" class="ts-btn ts-btn-secondary" onclick="testConfigDatabase()">🔌 Probar BD</button>
      <button type="button" class="ts-btn ts-btn-success" onclick="saveConfigProfile(false)">💾 Guardar Perfil</button>
      <button type="button" class="ts-btn ts-btn-primary" onclick="compileProductionVault()">🔐 Compilar Vault</button>
    </div>
  </div>

  <div id="cfgSectionsContainer" style="display:flex; flex-direction:column; gap:16px;"></div>
</div>

<script>
  let __cfgState = null;
  let __cfgActiveProfile = 'local';
  let __cfgActiveSection = 'all';

  async function loadConfigState(forceRefresh = false) {
    if (__cfgState && !forceRefresh) {
      renderConfigStage();
      return;
    }
    try {
      const res = await fetch('/_frame/studio/config-state');
      const data = await res.json();
      if (!data.success) {
        showToast('❌ ' + (data.message || 'No se pudo cargar la configuración'));
        return;
      }
      __cfgState = data;
      renderConfigStage();
    } catch (e) {
      showToast('❌ Error de red cargando configuración');
    }
  }

  function setConfigProfile(profile) {
    __cfgActiveProfile = (profile === 'production') ? 'production' : 'local';
    const btnLocal = document.getElementById('btnCfgProfileLocal');
    const btnProd = document.getElementById('btnCfgProfileProd');
    const badge = document.getElementById('cfgProfileBadge');
    const btnClone = document.getElementById('btnCfgCloneLocal');
    const subtitle = document.getElementById('cfgStageSubtitle');

    if (btnLocal) {
      btnLocal.className = 'ts-btn ' + (__cfgActiveProfile === 'local' ? 'ts-btn-primary' : 'ts-btn-secondary');
    }
    if (btnProd) {
      btnProd.className = 'ts-btn ' + (__cfgActiveProfile === 'production' ? 'ts-btn-primary' : 'ts-btn-secondary');
    }
    if (badge) {
      badge.textContent = __cfgActiveProfile === 'production' ? 'Producción (Vault)' : 'Local (.env)';
    }
    if (btnClone) {
      btnClone.style.display = __cfgActiveProfile === 'production' ? 'flex' : 'none';
    }
    if (subtitle) {
      subtitle.textContent = __cfgActiveProfile === 'production'
        ? 'Editando perfil de Producción (guardado localmente sin subir texto plano). Al compilar se cifra el Vault en App/Config/.'
        : 'Editando perfil Local (.env). Las constantes críticas del núcleo y URLs dinámicas están protegidas automáticamente.';
    }
    renderConfigStage();
  }

  function setConfigSectionFilter(secId) {
    __cfgActiveSection = secId || 'all';
    renderConfigStage();
  }

  function cloneNonSecretsFromLocal() {
    if (!__cfgState) return;
    const localVals = __cfgState.profiles.local || {};
    const prodVals = __cfgState.profiles.production || {};
    (__cfgState.fields || []).forEach(f => {
      if (f.storage === 'compiled' && f.key !== 'APP_ENV') {
        prodVals[f.key] = localVals[f.key];
      }
    });
    prodVals.APP_ENV = 'production';
    __cfgState.profiles.production = prodVals;
    renderConfigStage();
    showToast('📋 Valores no-secretos copiados al perfil de Producción.');
  }

  function onCfgFieldChange(key, value) {
    if (!__cfgState) return;
    if (!__cfgState.profiles[__cfgActiveProfile]) {
      __cfgState.profiles[__cfgActiveProfile] = {};
    }
    __cfgState.profiles[__cfgActiveProfile][key] = value;
  }

  function toggleSecretInputVisibility(key) {
    const inp = document.getElementById('cfgInput_' + key);
    if (!inp) return;
    inp.type = (inp.type === 'password') ? 'text' : 'password';
  }

  async function generateCfgKey(key, type) {
    try {
      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      const res = await fetch('/_frame/studio/config-generate-key', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : ''
        },
        body: JSON.stringify({ type })
      });
      const data = await res.json();
      if (data.success && data.key) {
        onCfgFieldChange(key, data.key);
        const inp = document.getElementById('cfgInput_' + key);
        if (inp) {
          inp.value = data.key;
          inp.type = 'text';
        }
        showToast(`🔑 Nueva clave generada para ${key}`);
      }
    } catch (e) {
      showToast('❌ Error generando clave');
    }
  }

  function renderConfigStatusBox() {
    const box = document.getElementById('cfgVaultStatusBox');
    if (!box || !__cfgState) return;
    const st = __cfgState.status || {};
    box.innerHTML = `
      <div><strong>Algoritmo AEAD:</strong> <span style="color:var(--ts-primary);">${escapeHtmlStudio(st.cipher || 'N/A')}</span></div>
      <div><strong>compiled.config.php:</strong> ${st.compiledExists ? '✅ ' + formatBytesKb(st.compiledBytes) : '⚪ Sin compilar'}</div>
      <div><strong>secrets.vault.php:</strong> ${st.vaultExists ? '🔒 ' + formatBytesKb(st.vaultBytes) : '⚪ Sin compilar'}</div>
      <div><strong>vault.key.php:</strong> ${st.keyExists ? '🔑 Presente en App/Safety/' : '⚪ Se generará al compilar'}</div>
    `;
  }

  function renderConfigStage() {
    if (!__cfgState) return;
    renderConfigStatusBox();

    const nav = document.getElementById('cfgSectionsNav');
    if (nav) {
      const allBtn = `<button type="button" class="ts-btn ${__cfgActiveSection === 'all' ? 'ts-btn-primary' : 'ts-btn-secondary'}" style="justify-content:flex-start; padding:6px 10px; font-size:0.78rem;" onclick="setConfigSectionFilter('all')">📑 Todas las secciones</button>`;
      const secBtns = (__cfgState.sections || []).map(sec => `
        <button type="button" class="ts-btn ${__cfgActiveSection === sec.id ? 'ts-btn-primary' : 'ts-btn-secondary'}" style="justify-content:flex-start; padding:6px 10px; font-size:0.78rem;" onclick="setConfigSectionFilter('${sec.id}')">${sec.icon} ${escapeHtmlStudio(sec.label)}</button>
      `).join('');
      nav.innerHTML = allBtn + secBtns;
    }

    const container = document.getElementById('cfgSectionsContainer');
    if (!container) return;

    const values = __cfgState.profiles[__cfgActiveProfile] || {};
    const fields = __cfgState.fields || [];
    const sections = (__cfgState.sections || []).filter(s => __cfgActiveSection === 'all' || s.id === __cfgActiveSection);

    container.innerHTML = sections.map(sec => {
      const secFields = fields.filter(f => f.section === sec.id);
      const inputsHtml = secFields.map(f => {
        const val = values[f.key] !== undefined ? values[f.key] : f.default;
        const badgeColor = f.storage === 'vault'
          ? 'background:rgba(245,158,11,0.16); color:#fbbf24;'
          : (f.storage === 'local_only'
            ? 'background:rgba(148,163,184,0.16); color:var(--ts-muted);'
            : 'background:rgba(34,197,94,0.16); color:#4ade80;');
        const badgeLabel = f.storage === 'vault' ? '🔒 Vault AEAD' : (f.storage === 'local_only' ? '💻 Solo Local' : '⚡ OPcache');
        const isLockedProdEnv = (__cfgActiveProfile === 'production' && f.key === 'APP_ENV');

        let controlHtml = '';
        if (f.type === 'bool') {
          controlHtml = `
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; padding:6px 0;">
              <input type="checkbox" id="cfgInput_${f.key}" ${val ? 'checked' : ''} onchange="onCfgFieldChange('${f.key}', this.checked)">
              <span style="font-size:0.82rem; color:#fff;">${val ? 'Activado (true)' : 'Desactivado (false)'}</span>
            </label>
          `;
        } else if (f.type === 'enum') {
          const opts = (f.options || []).map(opt => `<option value="${escapeHtmlStudio(opt)}" ${String(val) === String(opt) ? 'selected' : ''}>${escapeHtmlStudio(opt)}</option>`).join('');
          controlHtml = `<select id="cfgInput_${f.key}" class="ts-input-text" ${isLockedProdEnv ? 'disabled' : ''} onchange="onCfgFieldChange('${f.key}', this.value)">${opts}</select>`;
        } else if (f.type === 'int') {
          controlHtml = `<input type="number" id="cfgInput_${f.key}" class="ts-input-text" value="${escapeHtmlStudio(val)}" ${f.min !== undefined ? 'min="' + f.min + '"' : ''} ${f.max !== undefined ? 'max="' + f.max + '"' : ''} oninput="onCfgFieldChange('${f.key}', this.value)">`;
        } else if (f.type === 'list') {
          const listStr = Array.isArray(val) ? val.join(', ') : String(val || '');
          controlHtml = `<input type="text" id="cfgInput_${f.key}" class="ts-input-text" style="font-family:monospace;" value="${escapeHtmlStudio(listStr)}" oninput="onCfgFieldChange('${f.key}', this.value)">`;
        } else if (f.type === 'secret') {
          const genBtn = f.generatable
            ? `<button type="button" class="ts-btn ts-btn-secondary" style="padding:4px 8px; font-size:0.74rem;" onclick="generateCfgKey('${f.key}', '${f.generatable}')">🔑 Generar</button>`
            : '';
          controlHtml = `
            <div style="display:flex; gap:6px;">
              <input type="password" id="cfgInput_${f.key}" class="ts-input-text" style="font-family:monospace;" value="${escapeHtmlStudio(val)}" oninput="onCfgFieldChange('${f.key}', this.value)">
              <button type="button" class="ts-btn ts-btn-secondary" style="padding:4px 8px; font-size:0.74rem;" onclick="toggleSecretInputVisibility('${f.key}')">👁️</button>
              ${genBtn}
            </div>
          `;
        } else {
          controlHtml = `<input type="text" id="cfgInput_${f.key}" class="ts-input-text" value="${escapeHtmlStudio(val)}" oninput="onCfgFieldChange('${f.key}', this.value)">`;
        }

        return `
          <div style="background:var(--ts-bg); border:1px solid var(--ts-border); border-radius:8px; padding:11px; display:flex; flex-direction:column; gap:6px;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:6px;">
              <label for="cfgInput_${f.key}" style="font-size:0.82rem; font-weight:600; color:#fff;">${escapeHtmlStudio(f.label)} <code style="color:var(--ts-muted); font-size:0.7rem;">${f.key}</code></label>
              <span style="font-size:0.66rem; padding:2px 6px; border-radius:999px; ${badgeColor}">${badgeLabel}</span>
            </div>
            ${controlHtml}
            <div style="font-size:0.72rem; color:var(--ts-muted);">${escapeHtmlStudio(f.help)}</div>
          </div>
        `;
      }).join('');

      return `
        <section style="background:var(--ts-surface); border:1px solid var(--ts-border); border-radius:12px; padding:16px 20px;">
          <div style="margin-bottom:12px;">
            <h3 style="font-size:0.96rem; font-weight:700; color:#fff;">${sec.icon} ${escapeHtmlStudio(sec.label)}</h3>
            <p style="font-size:0.76rem; color:var(--ts-muted);">${escapeHtmlStudio(sec.desc)}</p>
          </div>
          <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:12px;">
            ${inputsHtml}
          </div>
        </section>
      `;
    }).join('');
    if (typeof syncStudioFloatingScrolls === 'function') {
      requestAnimationFrame(syncStudioFloatingScrolls);
    }
  }

  async function saveConfigProfile(compileAfter = false) {
    if (!__cfgState) return;
    try {
      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      const res = await fetch('/_frame/studio/config-save', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : ''
        },
        body: JSON.stringify({
          profile: __cfgActiveProfile,
          values: __cfgState.profiles[__cfgActiveProfile] || {},
          compile: !!compileAfter
        })
      });
      const data = await res.json();
      if (data.success) {
        if (data.status) __cfgState.status = data.status;
        renderConfigStatusBox();
        showToast('✅ ' + data.message);
      } else {
        const firstErr = data.errors ? Object.entries(data.errors).map(([k, v]) => `${k}: ${v}`).join(' | ') : '';
        showToast('❌ ' + (firstErr || data.message || 'Error al guardar'));
      }
    } catch (e) {
      showToast('❌ Error de red al guardar perfil');
    }
  }

  async function compileProductionVault() {
    if (!__cfgState) return;
    try {
      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      const res = await fetch('/_frame/studio/config-compile', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : ''
        },
        body: JSON.stringify({
          values: __cfgState.profiles.production || {}
        })
      });
      const data = await res.json();
      if (data.success) {
        if (data.status) __cfgState.status = data.status;
        renderConfigStatusBox();
        showToast(`🔐 Compilado (${data.cipher}): ${data.envCount} vars + ${data.secretsCount} secretos cifrados.`);
      } else {
        showToast('❌ ' + (data.message || 'No se pudo compilar el Vault'));
      }
    } catch (e) {
      showToast('❌ Error de red al compilar producción');
    }
  }

  async function testConfigDatabase() {
    if (!__cfgState) return;
    const vals = __cfgState.profiles[__cfgActiveProfile] || {};
    try {
      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      const res = await fetch('/_frame/studio/config-test-db', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : ''
        },
        body: JSON.stringify({
          DB_CONNECTION: vals.DB_CONNECTION,
          DB_HOST: vals.DB_HOST,
          DB_PORT: vals.DB_PORT,
          DB_DATABASE: vals.DB_DATABASE,
          DB_USERNAME: vals.DB_USERNAME,
          DB_PASSWORD: vals.DB_PASSWORD,
          DB_CHARSET: vals.DB_CHARSET
        })
      });
      const data = await res.json();
      showToast((data.success ? '✅ ' : '❌ ') + data.message);
    } catch (e) {
      showToast('❌ Error probando conexión BD');
    }
  }
</script>
