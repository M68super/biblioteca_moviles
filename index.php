<?php

require_once __DIR__ . '/config/conexion.php';

try {
    $sql = "
        SELECT
            p.id,
            p.name AS movil,
            p.launch_year,
            b.name AS marca,
            s.name AS familia
        FROM phones p
        INNER JOIN series s
            ON s.id = p.series_id
        INNER JOIN brands b
            ON b.id = s.brand_id
        ORDER BY
            b.name ASC,
            s.name ASC,
            p.name ASC
    ";

    $stmt = $conexion->query($sql);
    $moviles = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Error al consultar el catálogo: ' . $e->getMessage());

    http_response_code(500);
    exit('No se ha podido cargar el catálogo. Inténtalo más tarde.');
}

function escapar($valor): string
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Catálogo de smartphones</title>

    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

    <header class="cabecera">
        <div class="contenedor">
            <p class="sobrelinea">CATÁLOGO TECNOLÓGICO</p>
            <h1>Biblioteca de smartphones</h1>
            <p class="subtitulo">
                Explora modelos, especificaciones y valoraciones.
            </p>
        </div>
    </header>

    <main class="contenedor">

        <section class="introduccion">
            <h2>Catálogo de móviles</h2>
            <p>
                Una base de datos para consultar y comparar teléfonos
                de distintas marcas y generaciones.
            </p>
        </section>

        <section class="catalogo" aria-labelledby="titulo-catalogo">

            <div class="encabezado-catalogo">
                <h2 id="titulo-catalogo">Modelos registrados</h2>
                <span class="contador">
                    <?= count($moviles) ?>
                    <?= count($moviles) === 1 ? 'móvil' : 'móviles' ?>
                </span>
            </div>

            <?php if (empty($moviles)): ?>

                <div class="estado-vacio">
                    <h3>El catálogo está vacío</h3>
                    <p>
                        La conexión funciona, pero todavía no has
                        añadido ningún teléfono.
                    </p>
                    <p>
                        El siguiente paso será registrar las primeras
                        marcas, familias y modelos de prueba.
                    </p>
                </div>

            <?php else: ?>

                <div class="grid-moviles">

                    <?php foreach ($moviles as $movil): ?>

                        <article class="tarjeta">

                            <p class="marca">
                                <?= escapar($movil['marca']) ?>
                            </p>

                            <h3>
                                <?= escapar($movil['movil']) ?>
                            </h3>

                            <p class="familia">
                                Familia:
                                <?= escapar($movil['familia']) ?>
                            </p>

                            <p class="anio">
                                Lanzamiento:
                                <?= $movil['launch_year'] !== null
                                    ? escapar($movil['launch_year'])
                                    : 'No indicado' ?>
                            </p>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </main>

    <footer class="pie">
        <div class="contenedor">
            <p>Catálogo de smartphones · Proyecto personal</p>
        </div>
    </footer>

</body>
</html>
