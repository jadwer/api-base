<?php

return [
    /*
     * Borrar del disco public las imagenes (products/) y fichas (datasheets/)
     * que quedan huerfanas al quitarlas o reemplazarlas. En dev debe ir en
     * false: dev comparte storage/app/public con prod (carpeta shared del servidor) y
     * borraria archivos que prod sigue usando.
     */
    'delete_orphan_files' => (bool) env('PRODUCT_DELETE_ORPHAN_FILES', true),
];
