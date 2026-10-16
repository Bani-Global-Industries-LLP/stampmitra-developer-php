<?php

declare(strict_types=1);

namespace StampMitra\Tests\Support;

/**
 * Compares the bundled OpenAPI snapshot (spec/openapi.json) with the
 * METHOD + path pairs the SDK resources actually call.
 */
final class SpecCoverage
{
    /**
     * @return list<string> e.g. "GET /v1/orders/{}"
     */
    public static function specOperations(string $root): array
    {
        $spec = json_decode((string) file_get_contents($root . '/spec/openapi.json'), true, 512, JSON_THROW_ON_ERROR);
        $ops = [];
        foreach ($spec['paths'] as $path => $item) {
            foreach (array_keys($item) as $method) {
                if (in_array($method, ['get', 'post', 'put', 'patch', 'delete'], true)) {
                    $ops[] = strtoupper($method) . ' ' . (string) preg_replace('/\{[^}]+\}/', '{}', (string) $path);
                }
            }
        }
        sort($ops);

        return $ops;
    }

    /**
     * @return list<string>
     */
    public static function sdkOperations(string $root): array
    {
        $ops = [];
        foreach (glob($root . '/src/Resource/*.php') ?: [] as $file) {
            $code = (string) file_get_contents($file);
            preg_match_all('/\'(GET|POST|PUT|PATCH|DELETE)\',\s*((?:\'[^\']*\'|\s*\.\s*|\$this->segment\([^)]*\))+)/', $code, $m, PREG_SET_ORDER);
            foreach ($m as $hit) {
                $path = '';
                preg_match_all('/\'([^\']*)\'|\$this->segment\([^)]*\)/', $hit[2], $parts, PREG_SET_ORDER);
                foreach ($parts as $part) {
                    $path .= isset($part[1]) ? $part[1] : '{}';
                }
                $ops[] = $hit[1] . ' ' . $path;
            }
        }
        $ops = array_values(array_unique($ops));
        sort($ops);

        return $ops;
    }

    /**
     * @return list<string> spec operations with no SDK method
     */
    public static function missing(string $root): array
    {
        return array_values(array_diff(self::specOperations($root), self::sdkOperations($root)));
    }
}
