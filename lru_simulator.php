<?php
session_start();

// --- CONFIGURACIÓN ---
$totalItems   = 100;    // número total de elementos
$perPage      = 10;     // elementos por página
$cacheMaxSize = 3;      // cuántas páginas guardar en cache

// datos de ejemplo: 1..100
$data = range(1, $totalItems);

// inicializa cache LRU en sesión
if (!isset($_SESSION['lru_cache'])) {
    $_SESSION['lru_cache'] = [];   // pageNum => [items]
    $_SESSION['lru_history'] = []; // historial de acceso (el último es el más reciente)
}

// página solicitada (?page=)
$page = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$page = min($page, ceil($totalItems/$perPage));

// botón para reiniciar la cache
if (isset($_GET['reset'])) {
    $_SESSION['lru_cache'] = [];
    $_SESSION['lru_history'] = [];
    header("Location: lru_simulator.php" . ($page > 1 ? "?page=$page" : ""));
    exit;
}

// función para cargar datos "reales" de la página
function loadPage($page, $perPage, $data) {
    $start = ($page - 1) * $perPage;
    return array_slice($data, $start, $perPage);
}

// comprueba el cache
$hit = false;
if (isset($_SESSION['lru_cache'][$page])) {
    // HIT: actualizamos la posición en LRU (mostrar al final = más reciente)
    $hit = true;
    $pageData = $_SESSION['lru_cache'][$page];
    
    // Actualizar posición en historia de acceso
    $pos = array_search($page, $_SESSION['lru_history']);
    if ($pos !== false) {
        array_splice($_SESSION['lru_history'], $pos, 1);
    }
    $_SESSION['lru_history'][] = $page;
} else {
    // MISS: cargamos y metemos en cache
    $hit = false;
    $pageData = loadPage($page, $perPage, $data);

    // si la cache está llena, expulsamos LRU (menos reciente)
    if (count($_SESSION['lru_cache']) >= $cacheMaxSize) {
        // El primero en el historial es el menos reciente
        $lruPage = array_shift($_SESSION['lru_history']);
        unset($_SESSION['lru_cache'][$lruPage]);
        
        // Guardar información sobre qué página fue reemplazada
        $_SESSION['replaced_page'] = $lruPage;
    }
    
    // Insertamos la nueva página (la más reciente)
    $_SESSION['lru_cache'][$page] = $pageData;
    $_SESSION['lru_history'][] = $page;
}

// Obtener el evento de reemplazo
$replacedPage = isset($_SESSION['replaced_page']) ? $_SESSION['replaced_page'] : null;
$_SESSION['replaced_page'] = null; // Limpiar para el próximo uso

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Paginación con Cache LRU</title>
  <style>
    body { 
      font-family: 'Segoe UI', Arial, sans-serif; 
      margin: 20px; 
      background-color: #f5f5f5;
      color: #333;
      line-height: 1.6;
    }
    .container {
      max-width: 900px;
      margin: 0 auto;
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    h2, h3 { 
      color: #2c3e50; 
      border-bottom: 2px solid #eee;
      padding-bottom: 10px;
    }
    .items { 
      background: #f9f9f9;
      padding: 15px;
      border-radius: 4px;
      margin: 15px 0;
    }
    .items span { 
      display: inline-block; 
      width: 40px; 
      height: 40px;
      line-height: 40px;
      text-align: center; 
      margin: 3px; 
      background: white;
      border-radius: 50%;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      font-weight: bold;
      transition: transform 0.2s;
    }
    .items span:hover {
      transform: scale(1.1);
      background: #f0f0f0;
    }
    .pagination { 
      margin: 20px 0;
      text-align: center; 
    }
    .pagination a, .pagination strong { 
      display: inline-block;
      padding: 5px 10px;
      margin: 0 3px;
      border: 1px solid #ddd;
      border-radius: 3px;
      text-decoration: none;
      color: #2980b9;
      background: white;
      transition: all 0.3s;
    }
    .pagination a:hover { 
      background: #e9f7fe;
      border-color: #2980b9;
    }
    .pagination strong {
      background: #2980b9;
      color: white;
      border-color: #2980b9;
    }
    .cache { 
      margin-top: 20px; 
      background: #f9f9f9;
      padding: 15px;
      border-radius: 4px;
    }
    .cache-item {
      margin: 10px 0;
      padding: 10px;
      background: white;
      border-left: 4px solid #1abc9c;
      border-radius: 4px;
    }
    .timeline-position {
      display: inline-block;
      padding: 2px 8px;
      background: #1abc9c;
      color: white;
      border-radius: 10px;
      font-size: 12px;
      margin-left: 10px;
      vertical-align: middle;
    }
    .hit { 
      color: #27ae60; 
      font-weight: bold;
      background: #e6f9ee;
      padding: 3px 8px;
      border-radius: 4px;
    } 
    .miss { 
      color: #e74c3c; 
      font-weight: bold;
      background: #fae9e7;
      padding: 3px 8px;
      border-radius: 4px;
    }
    .actions {
      margin: 20px 0;
    }
    .btn {
      padding: 8px 15px;
      background: #1abc9c;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      transition: background 0.3s;
    }
    .btn:hover {
      background: #16a085;
    }
    .alg-switcher {
      text-align: center;
      margin: 20px 0;
    }
    .alg-switcher a {
      margin: 0 10px;
      padding: 8px 15px;
      text-decoration: none;
      color: #333;
      background: #f0f0f0;
      border-radius: 4px;
      transition: all 0.3s;
    }
    .alg-switcher a.active {
      background: #1abc9c;
      color: white;
    }
    .alg-switcher a:hover {
      background: #e0e0e0;
    }
    .alg-switcher a.active:hover {
      background: #16a085;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Simulador de Paginación con Cache LRU</h2>
    
    <div class="alg-switcher">
      <a href="fifo_simulator.php<?php echo $page > 1 ? "?page=$page" : "" ?>">FIFO</a>
      <a href="lru_simulator.php<?php echo $page > 1 ? "?page=$page" : "" ?>" class="active">LRU</a>
      <a href="lfu_simulator.php<?php echo $page > 1 ? "?page=$page" : "" ?>">LFU</a>
      <a href="mfu_simulator.php<?php echo $page > 1 ? "?page=$page" : "" ?>">MFU</a>
    </div>

    <p>
      <strong>LRU (Least Recently Used):</strong> Reemplaza la página que no ha sido utilizada durante 
      más tiempo. Mantiene todas las páginas ordenadas por tiempo de último acceso.
    </p>

    <p>
      Página actual: <strong><?php echo $page ?></strong>
      — 
      <?php if($hit): ?>
        <span class="hit">Cache HIT ✓</span>
      <?php else: ?>
        <span class="miss">Cache MISS ✗</span>
      <?php endif; ?>
    </p>

    <div class="items">
      <?php foreach ($pageData as $item): ?>
        <span><?php echo $item ?></span>
      <?php endforeach; ?>
    </div>

    <div class="pagination">
      <?php
        $totalPages = ceil($totalItems / $perPage);
        
        // Mostrar botones de navegación
        echo '<a href="?page=1">&laquo; Primera</a> ';
        
        if ($page > 1) {
          echo '<a href="?page='.($page-1).'">&lsaquo; Anterior</a> ';
        }
        
        // Mostrar páginas cercanas a la actual
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        
        for ($p = $start; $p <= $end; $p++) {
          if ($p === $page) {
            echo "<strong>$p</strong>";
          } else {
            echo "<a href=\"?page=$p\">$p</a>";
          }
        }
        
        if ($page < $totalPages) {
          echo ' <a href="?page='.($page+1).'">Siguiente &rsaquo;</a>';
        }
        
        echo ' <a href="?page='.$totalPages.'">Última &raquo;</a>';
      ?>
    </div>

    <div class="actions">
      <a href="?reset=1<?php echo $page > 1 ? "&page=$page" : "" ?>" class="btn">Reiniciar Cache</a>
    </div>

    <div class="cache">
      <h3>Estado del Cache (LRU: expulsa la página menos recientemente usada)</h3>
      
      <?php if (empty($_SESSION['lru_cache'])): ?>
        <p><em>El cache está vacío</em></p>
      <?php else: ?>
        <?php 
          // Mostrar las páginas en orden de expulsión (la primera sale primero)
          $lruOrder = $_SESSION['lru_history'];
        ?>
        <div style="margin-bottom: 15px">
          <strong>Orden LRU (de más antigua a más reciente):</strong>
          <?php echo implode(' → ', $lruOrder) ?>
        </div>
        
        <?php foreach ($_SESSION['lru_cache'] as $p => $items): ?>
          <div class="cache-item">
            <strong>Página <?php echo $p ?></strong>
            <span class="timeline-position">
              <?php 
                $position = array_search($p, $_SESSION['lru_history']) + 1;
                echo $position == count($_SESSION['lru_history']) ? "Más reciente" : "Posición $position"; 
              ?>
            </span>
            <div>[ <?php echo implode(', ', $items) ?> ]</div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html> 