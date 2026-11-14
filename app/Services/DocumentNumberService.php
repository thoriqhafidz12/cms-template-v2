<?php

namespace App\Services;

use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    /**
     * Buat nomor dokumen unik dengan row lock per tipe dan tanggal.
     * Format: {PREFIX}-{YYYYMMDD}-{000001}
     */
    public static function generate(string $prefix, ?string $dateYmd = null): string
    {
        $dateStr = $dateYmd ?: now()->format('Ymd');

        return DB::transaction(function () use ($prefix, $dateStr) {
            $seq = DocumentSequence::where('doc_type', $prefix)
                ->where('sequence_date', $dateStr)
                ->lockForUpdate()
                ->first();

            if (!$seq) {
                $seq = DocumentSequence::create([
                    'doc_type' => $prefix,
                    'sequence_date' => $dateStr,
                    'last_number' => 1,
                ]);
                $next = 1;
            } else {
                $next = $seq->last_number + 1;
                $seq->update(['last_number' => $next]);
            }

            return sprintf('%s-%s-%06d', strtoupper($prefix), $dateStr, $next);
        });
    }
}
