<?php
session_start();

// --- CONFIGURACIÓN ---
$totalItems   = 100;    // número total de elementos
$perPage      = 10;     // elementos por página
$cacheMaxSize = 3;      // cuántas páginas guardar en cache

// datos de ejemplo: 1..100
$data = range(1, $totalItems);

// inicializa cache FIFO en sesión
if (!isset($_SESSION['fifo_cache'])) {
    $_SESSION['fifo_cache'] = [];   // pageNum => [items]
    $_SESSION['fifo_queue'] = [];   // cola FIFO: la primera es la más vieja
}

// página solicitada (?page=)
$page = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$page = min($page, ceil($totalItems/$perPage));

// botón para reiniciar la cache
if (isset($_GET['reset'])) {
    $_SESSION['fifo_cache'] = [];
    $_SESSION['fifo_queue'] = [];
    header("Location: fifo_simulator.php" . ($page > 1 ? "?page=$page" : ""));
    exit;
}

// función para cargar datos "reales" de la página
function loadPage($page, $perPage, $data) {
    $start = ($page - 1) * $perPage;
    return array_slice($data, $start, $perPage);
}

// comprueba el cache
$hit = false;
if (isset($_SESSION['fifo_cache'][$page])) {
    // HIT: no se expulsa nada, solo señalamos el acierto
    $hit = true;
    $pageData = $_SESSION['fifo_cache'][$page];
} else {
    // MISS: cargamos y metemos en cache
    $hit = false;
    $pageData = loadPage($page, $perPage, $data);

    // si la cache está llena, expulsamos FIFO
    if (count($_SESSION['fifo_queue']) >= $cacheMaxSize) {
        $oldest = array_shift($_SESSION['fifo_queue']);
        unset($_SESSION['fifo_cache'][$oldest]);
    }
    // insertamos la nueva página al final de la cola
    $_SESSION['fifo_queue'][] = $page;
    $_SESSION['fifo_cache'][$page] = $pageData;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Paginación con Cache FIFO</title>
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
      border-left: 4px solid #e67e22;
      border-radius: 4px;
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
      background: #e67e22;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      transition: background 0.3s;
    }
    .btn:hover {
      background: #d35400;
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
      background: #e67e22;
      color: white;
    }
    .alg-switcher a:hover {
      background: #e0e0e0;
    }
    .alg-switcher a.active:hover {
      background: #d35400;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Simulador de Paginación con Cache FIFO</h2>
    
    <div class="alg-switcher">
      <a href="fifo_simulator.php<?php echo $page > 1 ? "?page=$page" : "" ?>" class="active">FIFO</a>
      <a href="lru_simulator.php<?php echo $page > 1 ? "?page=$page" : "" ?>">LRU</a>
      <a href="lfu_simulator.php<?php echo $page > 1 ? "?page=$page" : "" ?>">LFU</a>
      <a href="mfu_simulator.php<?php echo $page > 1 ? "?page=$page" : "" ?>">MFU</a>
    </div>

    <p>
      <strong>FIFO (First In First Out):</strong> Reemplaza la página que ha estado más tiempo en el cache,
      sin importar cuántas veces ha sido accedida.
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
      <h3>Estado del Cache (FIFO: primera página en entrar → primera en salir)</h3>
      
      <?php if (empty($_SESSION['fifo_cache'])): ?>
        <p><em>El cache está vacío</em></p>
      <?php else: ?>
        <div style="margin-bottom: 15px">
          <strong>Cola FIFO:</strong>
          <?php echo implode(' → ', $_SESSION['fifo_queue']) ?>
        </div>
        
        <?php foreach ($_SESSION['fifo_cache'] as $p => $items): ?>
          <div class="cache-item">
            <strong>Página <?php echo $p ?></strong>
            <div>[ <?php echo implode(', ', $items) ?> ]</div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
