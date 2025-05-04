<?php
session_start();

// --- CONFIGURACIÓN ---
$totalItems   = 100;     // número total de elementos
$perPage      = 10;      // elementos por página
$cacheMaxSize = 3;       // cuántas páginas guardar en cache

// Generamos datos de ejemplo
$data = range(1, $totalItems);

// Inicializamos estructura de cache en sesión
if (!isset($_SESSION['lru_cache'])) {
    $_SESSION['lru_cache'] = [];       // pageNum => [items]
    $_SESSION['lru_order'] = [];       // lista LRU: al final más reciente
}

// Página solicitada (por GET ?page=)
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$page = min($page, ceil($totalItems/$perPage));

// Función para cargar los datos “reales” de una página
function loadPageData($page, $perPage, $data) {
    $start = ($page - 1) * $perPage;
    return array_slice($data, $start, $perPage);
}

// Comprueba cache
$hit = false;
if (isset($_SESSION['lru_cache'][$page])) {
    // HIT: actualiza recencia
    $hit = true;
    $idx = array_search($page, $_SESSION['lru_order']);
    array_splice($_SESSION['lru_order'], $idx, 1);
    $_SESSION['lru_order'][] = $page;
    $pageData = $_SESSION['lru_cache'][$page];
} else {
    // MISS: cargar y cachear
    $pageData = loadPageData($page, $perPage, $data);

    // si el cache está lleno, expulsar LRU
    if (count($_SESSION['lru_cache']) >= $cacheMaxSize) {
        $lru = array_shift($_SESSION['lru_order']);
        unset($_SESSION['lru_cache'][$lru]);
    }
    // insertar nuevo
    $_SESSION['lru_cache'][$page]   = $pageData;
    $_SESSION['lru_order'][] = $page;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Paginación con Cache LRU</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .items { margin: 10px 0; }
    .items span { display: inline-block; width: 30px; text-align: center; }
    .pagination a { margin: 0 5px; text-decoration: none; }
    .cache { margin-top: 20px; }
    .cache div { margin: 5px 0; }
  </style>
</head>
<body>
  <h2>Paginación con Cache LRU (<?php echo $cacheMaxSize ?> páginas)</h2>

  <p>
    Página actual: <strong><?php echo $page ?></strong>
    — <em><?php echo $hit ? 'Cache HIT' : 'Cache MISS' ?></em>
  </p>

  <div class="items">
    <?php foreach ($pageData as $item): ?>
      <span><?php echo $item ?></span>
    <?php endforeach ?>
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
    <h3>Estado Cache (LRU order → más reciente al final)</h3>
    <div><strong>Orden de páginas:</strong>
      <?php echo implode(' &rarr; ', $_SESSION['lru_order']) ?>
    </div>
    <?php foreach ($_SESSION['lru_cache'] as $p => $items): ?>
      <div>
        <strong>Page <?php echo $p ?>:</strong>
        [ <?php echo implode(', ', $items) ?> ]
      </div>
    <?php endforeach ?>
  </div>
</body>
</html>
