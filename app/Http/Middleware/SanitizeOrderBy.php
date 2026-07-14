<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SanitizeOrderBy
{
    // Hanya karakter alfanumerik, underscore, dan titik (format table.column) yang diizinkan.
    // Karakter lain seperti backtick, tanda kurung, spasi, atau koma tidak dapat lolos validasi ini,
    // sehingga SQL injection via ORDER BY column tidak dapat dilakukan.
    private const COLUMN_PATTERN = '/^[a-zA-Z0-9_.]+$/';
    private const VALID_DIRECTIONS = ['asc', 'desc'];

    public function handle(Request $request, Closure $next)
    {
        $column  = $request->input('column');
        $orderby = $request->input('orderby');

        $columnValid  = $column !== null && preg_match(self::COLUMN_PATTERN, (string) $column);
        $orderbyValid = $orderby !== null && in_array(strtolower((string) $orderby), self::VALID_DIRECTIONS, true);

        // Keduanya harus valid sekaligus; jika salah satu tidak valid, null-kan dua-duanya
        // agar controller tidak memanggil orderBy(null, 'asc') yang menghasilkan SQL error.
        if ($columnValid && $orderbyValid) {
            $request->merge(['orderby' => strtolower((string) $orderby)]);
        } else {
            $request->merge(['column' => null, 'orderby' => null]);
        }

        return $next($request);
    }
}
