<?php

namespace App\Helpers;

use App\Models\Setting;

class CodeGenerator
{
    public static function generate($type, $data = [])
    {
        $prefix = Setting::get($type . '_prefix', strtoupper($type));
        $format = Setting::get($type . '_format', '{PREFIX}-{RANDOM}');

        $replacements = [
            '{PREFIX}' => $prefix,
            '{RANDOM}' => strtoupper(substr(uniqid(), -6)),
            '{DATE}' => now()->format('ymd'),
            '{YEAR}' => now()->format('Y'),
            '{MONTH}' => now()->format('m'),
            '{DAY}' => now()->format('d'),
            '{PO}' => $data['po'] ?? 'PO' . rand(1000, 9999),
            '{ID}' => $data['id'] ?? rand(1000, 9999),
        ];

        $code = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $format
        );

        // Remove any remaining braces
        $code = preg_replace('/\{[^}]+\}/', '', $code);

        return $code;
    }

    public static function generateResi($data = [])
    {
        return self::generate('resi', $data);
    }

    public static function generateInvoice($data = [])
    {
        return self::generate('invoice', $data);
    }

    public static function generateFuel($data = [])
    {
        return self::generate('fuel', $data);
    }

    public static function generateMaintenance($data = [])
    {
        return self::generate('maintenance', $data);
    }
}