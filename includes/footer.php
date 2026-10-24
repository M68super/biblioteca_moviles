<?php
/**
 * ============================================================================
 * PIE COMÚN A TODAS LAS PÁGINAS
 * Si la página define $pagina_scripts (texto HTML con <script>), se imprime aquí.
 * ============================================================================
 */
?>
    <footer class="pie">
        <div class="contenedor">
            <p>Catálogo de smartphones · Proyecto personal estilo Kimovil</p>
        </div>
    </footer>

    <?= $pagina_scripts ?? '' ?>
</body>
</html>
