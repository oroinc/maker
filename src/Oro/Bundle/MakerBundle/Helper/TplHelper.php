<?php

namespace Oro\Bundle\MakerBundle\Helper;

/**
 * Helper to dump array structure in templates.
 */
class TplHelper
{
    public static function dumpArray(array $data): string
    {
        $parts = [];
        foreach ($data as $key => $value) {
            if (!str_contains($key, '::')) {
                $key = "'" . $key . "'";
            }
            $part = $key . ' => ';
            if (is_array($value)) {
                $part .= self::dumpArray($value);
            } elseif (is_bool($value)) {
                $part .= $value ? 'true' : 'false';
            } elseif (is_numeric($value) || str_contains($value, '::')) {
                $part .= $value;
            } else {
                $part .= "'" . $value . "'";
            }

            $parts[] = $part;
        }

        return '[' . implode(', ', $parts) . ']';
    }
}
