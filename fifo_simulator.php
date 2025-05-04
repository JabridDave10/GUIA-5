<!DOCTYPE html>
<html>
<head>
    <title>Simulación FIFO - Paginación</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        input, button { padding: 6px; margin: 5px 0; }
        table { border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid black; padding: 8px; text-align: center; }
    </style>
</head>
<body>

<h2>Simulador de Paginación FIFO</h2>

<form method="post">
    <label>Secuencia de páginas (separadas por comas):</label><br>
    <input type="text" name="pages" required placeholder="Ej: 1,2,3,4,1,2,5"><br><br>

    <label>Número de marcos de página:</label><br>
    <input type="number" name="frames" required min="1"><br><br>

    <button type="submit">Simular</button>
</form>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pages = explode(",", str_replace(" ", "", $_POST['pages']));
    $frames_count = intval($_POST['frames']);
    $frames = [];
    $queue = [];
    $page_faults = 0;

    echo "<h3>Resultado de la simulación:</h3>";
    echo "<table>";
    echo "<tr><th>Página</th><th>Estado de los marcos</th><th>¿Falló?</th></tr>";

    foreach ($pages as $page) {
        $page = trim($page);
        if (!in_array($page, $frames)) {
            $page_faults++;
            if (count($frames) < $frames_count) {
                $frames[] = $page;
                $queue[] = $page;
            } else {
                $removed = array_shift($queue);
                $index = array_search($removed, $frames);
                $frames[$index] = $page;
                $queue[] = $page;
            }
            $fault = "Sí";
        } else {
            $fault = "No";
        }

        echo "<tr><td>$page</td><td>" . implode(", ", $frames) . "</td><td>$fault</td></tr>";
    }

    echo "</table>";
    echo "<p><strong>Total de fallos de página:</strong> $page_faults</p>";
}
?>

</body>
</html>
