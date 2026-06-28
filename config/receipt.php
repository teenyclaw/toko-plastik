<?php

return [
    /** Lebar kertas thermal: 58 atau 80 (mm) */
    'paper_width' => (int) env('RECEIPT_PAPER_WIDTH', 58),

    /** Auto-print saat halaman struk dibuka setelah pembayaran */
    'auto_print' => env('RECEIPT_AUTO_PRINT', true),
];
