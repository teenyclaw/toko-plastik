<?php

return [
    /** Wajib buka shift sebelum proses pembayaran */
    'require_open_shift' => env('POS_REQUIRE_OPEN_SHIFT', true),

    /** Interval polling antrian kasir & KDS (detik). Cocok untuk shared hosting. */
    'realtime_poll_seconds' => (int) env('POS_REALTIME_POLL_SECONDS', 15),
];
