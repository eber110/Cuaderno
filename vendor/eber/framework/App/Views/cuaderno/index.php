<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?></title>

  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg-dark: #090d16;
      --card-bg: rgba(30, 41, 59, 0.7);
      --card-border: rgba(255, 255, 255, 0.08);
      --text-primary: #f8fafc;
      --text-muted: #94a3b8;
      --accent-blue: #38bdf8;
      --accent-purple: #a855f7;
      --accent-pink: #ec4899;
      --accent-amber: #f59e0b;
      --accent-green: #10b981;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bg-dark);
      background-image: 
        radial-gradient(at 0% 0%, rgba(56, 189, 248, 0.12) 0px, transparent 50%),
        radial-gradient(at 100% 100%, rgba(168, 85, 247, 0.12) 0px, transparent 50%);
      color: var(--text-primary);
      min-height: 100vh;
      padding: 2rem 1rem;
    }

    .container {
      max-width: 1100px;
      margin: 0 auto;
    }

    /* Header & Stats */
    .header {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      gap: 1.5rem;
      margin-bottom: 2rem;
      padding-bottom: 1.5rem;
      border-bottom: 1px solid var(--card-border);
    }

    .header-title h1 {
      font-size: 2rem;
      font-weight: 700;
      background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .header-title p {
      color: var(--text-muted);
      font-size: 0.9rem;
      margin-top: 0.25rem;
    }

    .stats-bar {
      display: flex;
      gap: 1rem;
    }

    .stat-card {
      background: var(--card-bg);
      backdrop-filter: blur(12px);
      border: 1px solid var(--card-border);
      padding: 0.75rem 1.25rem;
      border-radius: 12px;
      text-align: center;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }

    .stat-card .val {
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--accent-blue);
    }

    .stat-card .lbl {
      font-size: 0.75rem;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    /* Actions Bar (Search & Categories) */
    .toolbar {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .categories {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .cat-btn {
      text-decoration: none;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 500;
      color: var(--text-muted);
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid var(--card-border);
      transition: all 0.2s ease;
    }

    .cat-btn:hover, .cat-btn.active {
      color: #fff;
      background: linear-gradient(135deg, rgba(56, 189, 248, 0.2), rgba(168, 85, 247, 0.2));
      border-color: var(--accent-blue);
      box-shadow: 0 0 12px rgba(56, 189, 248, 0.2);
    }

    .search-box {
      position: relative;
      min-width: 260px;
    }

    .search-input {
      width: 100%;
      padding: 0.6rem 1rem 0.6rem 2.5rem;
      border-radius: 20px;
      background: rgba(15, 23, 42, 0.8);
      border: 1px solid var(--card-border);
      color: #fff;
      font-size: 0.9rem;
      outline: none;
      transition: border 0.2s;
    }

    .search-input:focus {
      border-color: var(--accent-blue);
    }

    .search-icon {
      position: absolute;
      left: 0.9rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
    }

    /* Main Form Layout */
    .main-grid {
      display: grid;
      grid-template-columns: 340px 1fr;
      gap: 2rem;
    }

    @media (max-width: 850px) {
      .main-grid {
        grid-template-columns: 1fr;
      }
    }

    .form-card {
      background: var(--card-bg);
      backdrop-filter: blur(12px);
      border: 1px solid var(--card-border);
      border-radius: 16px;
      padding: 1.5rem;
      height: fit-content;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
      position: sticky;
      top: 1.5rem;
    }

    .form-card h2 {
      font-size: 1.1rem;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .form-group {
      margin-bottom: 1rem;
    }

    .form-group label {
      display: block;
      font-size: 0.8rem;
      color: var(--text-muted);
      margin-bottom: 0.4rem;
      font-weight: 500;
    }

    .form-input, .form-textarea, .form-select {
      width: 100%;
      padding: 0.65rem 0.9rem;
      border-radius: 8px;
      background: rgba(15, 23, 42, 0.9);
      border: 1px solid var(--card-border);
      color: #fff;
      font-family: inherit;
      font-size: 0.9rem;
      outline: none;
      transition: all 0.2s;
    }

    .form-input:focus, .form-textarea:focus, .form-select:focus {
      border-color: var(--accent-blue);
      box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.15);
    }

    .color-picker {
      display: flex;
      gap: 0.5rem;
      margin-top: 0.4rem;
    }

    .color-opt {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      cursor: pointer;
      border: 2px solid transparent;
      transition: transform 0.2s;
    }

    .color-opt:hover {
      transform: scale(1.2);
    }

    .color-opt.selected {
      border-color: #fff;
      transform: scale(1.15);
    }

    .btn-submit {
      width: 100%;
      padding: 0.75rem;
      border: none;
      border-radius: 10px;
      background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
      color: #fff;
      font-weight: 600;
      font-size: 0.95rem;
      cursor: pointer;
      transition: opacity 0.2s, transform 0.1s;
      box-shadow: 0 4px 15px rgba(56, 189, 248, 0.25);
    }

    .btn-submit:hover {
      opacity: 0.9;
      transform: translateY(-1px);
    }

    /* Notes Cards Grid */
    .notes-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 1.25rem;
    }

    .note-card {
      background: var(--card-bg);
      backdrop-filter: blur(12px);
      border: 1px solid var(--card-border);
      border-radius: 16px;
      padding: 1.25rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .note-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
      border-color: rgba(255, 255, 255, 0.15);
    }

    .note-card.pinned {
      border-top: 3px solid var(--accent-amber);
    }

    .note-accent-strip {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
    }

    .note-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 0.75rem;
      gap: 0.5rem;
    }

    .note-title {
      font-size: 1.1rem;
      font-weight: 600;
      color: #fff;
      line-height: 1.3;
    }

    .note-badge {
      font-size: 0.7rem;
      font-weight: 600;
      padding: 0.2rem 0.6rem;
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.08);
      color: var(--accent-blue);
      border: 1px solid rgba(56, 189, 248, 0.2);
      white-space: nowrap;
    }

    .note-body {
      color: #cbd5e1;
      font-size: 0.9rem;
      line-height: 1.5;
      margin-bottom: 1.25rem;
      white-space: pre-wrap;
      word-break: break-word;
    }

    .note-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-top: 0.75rem;
      border-top: 1px solid rgba(255, 255, 255, 0.05);
      font-size: 0.75rem;
      color: var(--text-muted);
    }

    .note-actions {
      display: flex;
      gap: 0.5rem;
    }

    .action-icon {
      text-decoration: none;
      color: var(--text-muted);
      width: 28px;
      height: 28px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(255, 255, 255, 0.04);
      transition: all 0.2s;
    }

    .action-icon:hover {
      background: rgba(255, 255, 255, 0.12);
      color: #fff;
    }

    .action-icon.active-star {
      color: var(--accent-amber);
      background: rgba(245, 158, 11, 0.15);
    }

    .action-icon.active-pin {
      color: var(--accent-blue);
      background: rgba(56, 189, 248, 0.15);
    }

    .action-icon.delete:hover {
      color: var(--accent-pink);
      background: rgba(236, 72, 153, 0.15);
    }

    .empty-state {
      text-align: center;
      padding: 3rem 1rem;
      background: var(--card-bg);
      border: 1px dashed var(--card-border);
      border-radius: 16px;
      color: var(--text-muted);
      grid-column: 1 / -1;
    }
  </style>
</head>
<body>

  <div class="container">
    
    <!-- Encabezado y Estadísticas -->
    <header class="header">
      <div class="header-title">
        <h1>📓 Mi Cuaderno SQLite</h1>
        <p>Framework Eber con motor de almacenamiento ultra rápido SQLite</p>
      </div>

      <div class="stats-bar">
        <div class="stat-card">
          <div class="val"><?= $stats['total'] ?></div>
          <div class="lbl">Notas</div>
        </div>
        <div class="stat-card">
          <div class="val" style="color: var(--accent-amber);"><?= $stats['favoritas'] ?></div>
          <div class="lbl">Favoritas</div>
        </div>
        <div class="stat-card">
          <div class="val" style="color: var(--accent-purple);"><?= count($stats['categorias']) ?></div>
          <div class="lbl">Categorías</div>
        </div>
      </div>
    </header>

    <!-- Barra de Herramientas (Filtros & Búsqueda) -->
    <div class="toolbar">
      <div class="categories">
        <a href="/cuaderno?cat=todas" class="cat-btn <?= $categoriaActual === 'todas' ? 'active' : '' ?>">Todas</a>
        <a href="/cuaderno?cat=favoritas" class="cat-btn <?= $categoriaActual === 'favoritas' ? 'active' : '' ?>">⭐ Favoritas</a>
        <a href="/cuaderno?cat=Trabajo" class="cat-btn <?= $categoriaActual === 'Trabajo' ? 'active' : '' ?>">💼 Trabajo</a>
        <a href="/cuaderno?cat=Personal" class="cat-btn <?= $categoriaActual === 'Personal' ? 'active' : '' ?>">🏡 Personal</a>
        <a href="/cuaderno?cat=Ideas" class="cat-btn <?= $categoriaActual === 'Ideas' ? 'active' : '' ?>">💡 Ideas</a>
        <a href="/cuaderno?cat=Proyectos" class="cat-btn <?= $categoriaActual === 'Proyectos' ? 'active' : '' ?>">🚀 Proyectos</a>
      </div>

      <form action="/cuaderno" method="GET" class="search-box">
        <span class="search-icon">🔍</span>
        <input type="text" name="q" value="<?= htmlspecialchars($busquedaActual) ?>" placeholder="Buscar nota..." class="search-input">
        <?php if (!empty($categoriaActual) && $categoriaActual !== 'todas'): ?>
          <input type="hidden" name="cat" value="<?= htmlspecialchars($categoriaActual) ?>">
        <?php endif; ?>
      </form>
    </div>

    <!-- Layout Principal -->
    <div class="main-grid">

      <!-- Formulario para Crear Nota -->
      <aside class="form-card">
        <h2>✏️ Nueva Nota</h2>
        <form action="/cuaderno/guardar" method="POST">
          <div class="form-group">
            <label>Título</label>
            <input type="text" name="titulo" required placeholder="Ej: Idea de proyecto..." class="form-input">
          </div>

          <div class="form-group">
            <label>Categoría</label>
            <select name="categoria" class="form-select">
              <option value="General">General</option>
              <option value="Trabajo">Trabajo</option>
              <option value="Personal">Personal</option>
              <option value="Ideas">Ideas</option>
              <option value="Proyectos">Proyectos</option>
              <option value="Importante">Importante</option>
            </select>
          </div>

          <div class="form-group">
            <label>Color de Etiqueta</label>
            <div class="color-picker">
              <input type="hidden" name="color" id="selectedColor" value="#38bdf8">
              <div class="color-opt selected" style="background:#38bdf8;" onclick="selectColor(this, '#38bdf8')"></div>
              <div class="color-opt" style="background:#a855f7;" onclick="selectColor(this, '#a855f7')"></div>
              <div class="color-opt" style="background:#ec4899;" onclick="selectColor(this, '#ec4899')"></div>
              <div class="color-opt" style="background:#f59e0b;" onclick="selectColor(this, '#f59e0b')"></div>
              <div class="color-opt" style="background:#10b981;" onclick="selectColor(this, '#10b981')"></div>
            </div>
          </div>

          <div class="form-group">
            <label>Contenido</label>
            <textarea name="contenido" rows="5" required placeholder="Escribe el detalle de tu nota..." class="form-textarea"></textarea>
          </div>

          <button type="submit" class="btn-submit">➕ Guardar Nota</button>
        </form>
      </aside>

      <!-- Rejilla de Notas -->
      <main class="notes-grid">
        <?php if (empty($notas)): ?>
          <div class="empty-state">
            <p style="font-size: 2.5rem; margin-bottom: 0.5rem;">📝</p>
            <h3>No se encontraron notas</h3>
            <p style="font-size: 0.85rem; margin-top: 0.25rem;">Crea una nueva nota desde el panel lateral.</p>
          </div>
        <?php else: ?>
          <?php foreach ($notas as $nota): ?>
            <article class="note-card <?= !empty($nota['is_pinned']) ? 'pinned' : '' ?>">
              <div class="note-accent-strip" style="background: <?= htmlspecialchars($nota['color'] ?? '#38bdf8') ?>;"></div>

              <div>
                <div class="note-header">
                  <h3 class="note-title"><?= htmlspecialchars($nota['titulo']) ?></h3>
                  <span class="note-badge"><?= htmlspecialchars($nota['categoria'] ?? 'General') ?></span>
                </div>
                <div class="note-body"><?= htmlspecialchars($nota['contenido']) ?></div>
              </div>

              <div class="note-footer">
                <span>🕒 <?= date('d M, H:i', strtotime($nota['created_at'])) ?></span>

                <div class="note-actions">
                  <!-- Botón Fijar -->
                  <a href="/cuaderno/fijar/<?= $nota['id'] ?>" class="action-icon <?= !empty($nota['is_pinned']) ? 'active-pin' : '' ?>" title="Fijar nota">
                    📌
                  </a>
                  <!-- Botón Favorita -->
                  <a href="/cuaderno/favorita/<?= $nota['id'] ?>" class="action-icon <?= !empty($nota['is_favorite']) ? 'active-star' : '' ?>" title="Marcar favorita">
                    ⭐
                  </a>
                  <!-- Botón Eliminar -->
                  <a href="/cuaderno/eliminar/<?= $nota['id'] ?>" class="action-icon delete" title="Eliminar" onclick="return confirm('¿Estás seguro de eliminar esta nota?')">
                    🗑️
                  </a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </main>

    </div>
  </div>

  <script>
    function selectColor(el, hex) {
      document.querySelectorAll('.color-opt').forEach(opt => opt.classList.remove('selected'));
      el.classList.add('selected');
      document.getElementById('selectedColor').value = hex;
    }
  </script>

</body>
</html>
