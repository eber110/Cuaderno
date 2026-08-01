<?php

/**
 * Configuración de librerías JS y CSS para carga automática en el <head>.
 * 
 * Define las carpetas dentro de /App/Rsc/Library/ que deben cargarse.
 * Si el nombre de la carpeta de una librería no está en este array o está comentado, no se cargará.
 * El orden en este array determina el orden de inyección en el HTML.
 */

return [
    'Gsap',
    'ApexCharts',
];
