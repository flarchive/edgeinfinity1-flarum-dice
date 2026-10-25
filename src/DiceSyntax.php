<?php
namespace EdgeInfinity\Dice;
use DOMDocument;
use DOMElement;
final class DiceSyntax
{
    public const PATTERN = '/\[[ \t]*(?<min>[+-]?[0-9]{1,10})[ \t]*,[ \t]*(?<max>[+-]?[0-9]{1,10})[ \t]*\](?![ \t]*\()/';
    public const LIMIT = 1000000000;
    public static function document(string $xml): ?DOMDocument
    {
        if (!str_contains($xml, '<EDGEDICE') || str_contains($xml, '<!DOCTYPE')) return null;
        $doc = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try { return $doc->loadXML($xml, LIBXML_NONET) ? $doc : null; }
        finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
    }
    public static function nodes(DOMDocument $doc): array
    {
        $nodes = [];
        foreach ($doc->getElementsByTagName('EDGEDICE') as $node) {
            // Quoted material must never acquire a new apparently original roll.
            $blocked = false;
            for ($parent = $node->parentNode; $parent instanceof DOMElement; $parent = $parent->parentNode) {
                if (in_array($parent->tagName, ['QUOTE','CODE','C','URL','IMG','EMAIL'], true)) { $blocked = true; break; }
            }
            if (!$blocked) $nodes[] = $node;
        }
        return $nodes;
    }
    public static function range(DOMElement $node): ?array
    {
        $min = $node->getAttribute('min'); $max = $node->getAttribute('max');
        if (!preg_match('/^[+-]?[0-9]{1,10}$/D', $min) || !preg_match('/^[+-]?[0-9]{1,10}$/D', $max)) return null;
        $a = (int) $min; $b = (int) $max;
        return ($a <= $b && abs($a) <= self::LIMIT && abs($b) <= self::LIMIT) ? [$a, $b] : null;
    }
    public static function ranges(string $xml): array
    {
        $doc = self::document($xml);
        if (!$doc) return [];
        $ranges=[];
        foreach (self::nodes($doc) as $node) if (($range=self::range($node)) !== null) $ranges[]=$range;
        return $ranges;
    }
    public static function ledger(?string $json): ?array
    {
        if (!$json) return null;
        $data = json_decode($json, true);
        if (!is_array($data) || ($data['v'] ?? null) !== 1 || !is_array($data['rolls'] ?? null)
            || !array_is_list($data['rolls']) || count($data['rolls']) > 100) return null;
        if (array_key_exists('edited', $data) && !is_bool($data['edited'])) return null;
        foreach ($data['rolls'] as $roll) {
            if (!is_array($roll) || !array_is_list($roll) || count($roll)!==3 || !is_int($roll[0]??null) || !is_int($roll[1]??null) || !is_int($roll[2]??null)
                || $roll[0]>$roll[1] || abs($roll[0])>self::LIMIT || abs($roll[1])>self::LIMIT || $roll[2]<$roll[0] || $roll[2]>$roll[1]) return null;
        }
        return $data;
    }
    public static function matches(array $ranges, array $ledger): bool
    { return $ranges === array_map(static fn(array $roll): array => [$roll[0],$roll[1]], $ledger['rolls']); }
}
