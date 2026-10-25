<?php
namespace EdgeInfinity\Dice;
use Flarum\Settings\Event\Saving;
use Flarum\Foundation\ValidationException;
use InvalidArgumentException;
final class ValidateSettings
{
    public function handle(Saving $event): void
    {
        $key='edgeinfinity-dice.config';
        if(!array_key_exists($key,$event->settings)) return;
        try { $event->settings[$key]=json_encode(DiceConfig::validate($event->settings[$key]),JSON_THROW_ON_ERROR); }
        catch(InvalidArgumentException $e) { throw new ValidationException([$key=>$e->getMessage()]); }
    }
}
