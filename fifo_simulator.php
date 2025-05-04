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

// función para cargar datos “reales” de la página
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
    body { font-family: Arial, sans-serif; margin: 20px; }
    .items span { display: inline-block; width: 30px; text-align: center; margin: 2px; }
    .pagination a { margin: 0 5px; text-decoration: none; }
    .cache { margin-top: 20px; }
    .cache div { margin: 5px 0; }
    .hit { color: green; } .miss { color: red; }
  </style>
</head>
<body>
  <h2>Paginación con Cache FIFO (<?php echo $cacheMaxSize ?> páginas)</h2>

  <p>
    Página actual: <strong><?php echo $page ?></strong>
    — 
    <?php if($hit): ?>
      <span class="hit">Cache HIT</span>
    <?php else: ?>
      <span class="miss">Cache MISS</span>
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
      for ($p = 1; $p <= $totalPages; $p++):
        if ($p === $page) {
          echo "<strong>$p</strong>";
        } else {
          echo "<a href=\"?page=$p\">$p</a>";
        }
      endfor;
    ?>
  </div>

  <div class="cache">
    <h3>Estado del Cache (FIFO: primera página en entrar → primera en salir)</h3>
    <div>
      <strong>Cola FIFO:</strong>
      <?php echo implode(' → ', $_SESSION['fifo_queue']) ?>
    </div>
    <?php foreach ($_SESSION['fifo_cache'] as $p => $items): ?>
      <div>
        <strong>Página <?php echo $p ?>:</strong>
        [ <?php echo implode(', ', $items) ?> ]
      </div>
    <?php endforeach; ?>
  </div>
</body>
</html>
