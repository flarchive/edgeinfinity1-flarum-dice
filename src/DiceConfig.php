<?php
namespace EdgeInfinity\Dice;
use InvalidArgumentException;
final class DiceConfig
{
    public static function defaults(): array { return json_decode(file_get_contents(__DIR__.'/../resources/defaults.json'),true,512,JSON_THROW_ON_ERROR); }
    public static function decode(mixed $value): array
    { try { return self::validate($value ?: self::defaults()); } catch (InvalidArgumentException $e) { return self::defaults(); } }
    public static function validate(mixed $value): array
    {
        if (is_string($value)) { if(strlen($value)>20000) throw new InvalidArgumentException('Configuration is too large.'); $value=json_decode($value,true); }
        if (!is_array($value)) throw new InvalidArgumentException('Configuration must be a JSON object.');
        // Older saved settings have no mode; preserve their selected rule and
        // shared digit threshold without persisting an automatic site rewrite.
        if (!array_key_exists('criticalMode', $value)) {
            $value['criticalMode'] = array_key_exists('criticalPercent', $value) && !array_key_exists('criticalCount', $value) ? 'percent' : 'count';
        }
        if (array_key_exists('digitsMin', $value)) {
            if (!is_int($value['digitsMin']) || $value['digitsMin'] < 2 || $value['digitsMin'] > 10) throw new InvalidArgumentException('Invalid legacy digit threshold.');
            foreach (['repeatDigitsMin', 'sequenceDigitsMin'] as $key) if (!array_key_exists($key, $value)) $value[$key] = $value['digitsMin'];
            unset($value['digitsMin']);
        }
        $defaults=self::defaults();
        if(array_diff(array_keys($value),array_keys($defaults))) throw new InvalidArgumentException('Unknown configuration key.');
        $out=array_replace($defaults,$value);
        foreach (['maxDice'=>[1,100],'criticalMinFaces'=>[2,2000000001],'criticalCount'=>[1,1000000000],'repeatDigitsMin'=>[2,10],'sequenceDigitsMin'=>[2,10]] as $k=>[$min,$max])
            if(!is_int($out[$k]) || $out[$k]<$min || $out[$k]>$max) throw new InvalidArgumentException($k.' is outside the allowed integer range.');
        if (!in_array($out['criticalMode'], ['count', 'percent'], true)) throw new InvalidArgumentException('Invalid critical mode.');
        if ((!is_int($out['criticalPercent']) && !is_float($out['criticalPercent'])) || !is_finite((float)$out['criticalPercent']) || $out['criticalPercent'] < 0.01 || $out['criticalPercent'] > 50) throw new InvalidArgumentException('Critical percentage must be between 0.01 and 50.');
        foreach(['criticalEnabled','successHigh','signEnabled','repeatEnabled','sequenceEnabled','specialEnabled','allowEdit','markEdited'] as $k)
            if(!is_bool($out[$k])) throw new InvalidArgumentException($k.' must be a boolean.');
        if(!is_array($out['specialNumbers']) || !array_is_list($out['specialNumbers']) || count($out['specialNumbers'])>100) throw new InvalidArgumentException('Use at most 100 special numbers.');
        foreach($out['specialNumbers'] as $n) if(!is_int($n) || abs($n)>DiceSyntax::LIMIT) throw new InvalidArgumentException('Special numbers must be supported integers.');
        $a=$out['priority']; $b=$defaults['priority'];
        if(!is_array($a)||!array_is_list($a)) throw new InvalidArgumentException('Priority must be an array.');
        sort($a);sort($b);if($a!==$b) throw new InvalidArgumentException('Priority must contain every effect exactly once.');
        if(!is_array($out['colors']) || array_diff(array_keys($out['colors']),array_keys($defaults['colors']))) throw new InvalidArgumentException('Unknown color category.');
        foreach ($out['colors'] as $palette) if (!is_array($palette)) throw new InvalidArgumentException('Color categories must be objects.');
        foreach($defaults['colors'] as $effect=>$themes) foreach($themes as $theme=>$properties) {
            $colors=$out['colors'][$effect][$theme]??[];
            if(!is_array($colors)||array_diff(array_keys($colors),array_keys($properties))) throw new InvalidArgumentException('Unknown color property.');
            foreach($properties as $property=>$fallback) {
                $c=$colors[$property]??$fallback;
                if(!is_string($c)||($c!==''&&!preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/iD',$c))) throw new InvalidArgumentException('Colors must be hexadecimal or empty.');
                $out['colors'][$effect][$theme][$property]=$c;
            }
            if(array_diff(array_keys($out['colors'][$effect]),['light','dark'])) throw new InvalidArgumentException('Unknown color theme.');
        }
        foreach (['genericIcon', 'editedIcon'] as $key) {
            if (!self::fontIcon($out[$key])) throw new InvalidArgumentException('Use a Font Awesome style and one icon class.');
        }
        if (!is_array($out['effectLabels']) || array_diff(array_keys($out['effectLabels']), $defaults['priority'])) throw new InvalidArgumentException('Unknown effect label.');
        $out['effectLabels'] = array_replace($defaults['effectLabels'], $out['effectLabels']);
        foreach ($out['effectLabels'] as $label) {
            if (!is_string($label) || mb_strlen($label) > 40 || preg_match('/[\x00-\x1f\x7f<>]/u', $label)) throw new InvalidArgumentException('Labels must be plain text up to 40 characters.');
        }
        if (!is_array($out['specialIcons']) || array_diff(array_keys($out['specialIcons']), array_keys($defaults['specialIcons']))) throw new InvalidArgumentException('Unknown special die.');
        foreach ($defaults['specialIcons'] as $faces => $fallback) {
            $icon = $out['specialIcons'][$faces] ?? [];
            if (!is_array($icon) || array_diff(array_keys($icon), array_keys($fallback))) throw new InvalidArgumentException('Unknown icon property.');
            $icon = array_replace($fallback, $icon);
            if (!is_bool($icon['enabled']) || !in_array($icon['type'], ['builtin', 'font', 'image'], true) || !is_string($icon['value']) || strlen($icon['value']) > 2048) throw new InvalidArgumentException('Invalid icon configuration.');
            // Validate even inactive icons so a later toggle cannot expose an unsafe URL.
            if ($icon['type'] === 'font' && !self::fontIcon($icon['value'])) throw new InvalidArgumentException('Invalid Font Awesome icon.');
            if ($icon['type'] === 'image' && !self::imageUrl($icon['value'])) throw new InvalidArgumentException('Images require a root-relative or HTTP(S) URL, without credentials.');
            $out['specialIcons'][$faces] = $icon;
        }
        return $out;
    }
    private static function fontIcon(mixed $value): bool
    {
        return is_string($value) && preg_match('/^(?:fas|far|fab|fal|fat|fad) fa-[a-z0-9]+(?:-[a-z0-9]+)*$/D', $value) === 1 && strlen($value) <= 100;
    }
    private static function imageUrl(string $value): bool
    {
        if ($value === '' || preg_match('/[\x00-\x20\x7f<>"\\\\]/', $value) || str_starts_with($value, '//')) return false;
        if (str_starts_with($value, '/')) return true;
        $parts = parse_url($value);
        return $parts && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) && !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass']);
    }
}
