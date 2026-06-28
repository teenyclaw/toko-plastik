<?php

if (! function_exists('format_rupiah')) {
    function format_rupiah(float|int|string $value): string
    {
        $num = is_string($value) ? (float) $value : $value;

        return 'Rp '.number_format($num, 0, ',', '.');
    }
}

if (! function_exists('format_qty')) {
    function format_qty(float|int|string $value): string
    {
        $num = is_string($value) ? (float) $value : $value;

        return rtrim(rtrim(number_format($num, 3, ',', '.'), '0'), ',');
    }
}
