<?php

if (!function_exists('formatearPrecioParaguayo')) {
    /**
     * Formatear precio en guaraníes paraguayos
     * 
     * @param float|int $precio
     * @return string
     */
    function formatearPrecioParaguayo($precio) {
        return '₲' . number_format($precio, 0, ',', '.');
    }
}

if (!function_exists('formatearNumero')) {
    /**
     * Formatear número con separadores de miles
     * 
     * @param float|int $numero
     * @return string
     */
    function formatearNumero($numero) {
        return number_format($numero, 0, ',', '.');
    }
}