<?php
use EdgeInfinity\Dice\{ConfigureFormatter,DiceConfig,DiceServiceProvider,RenderDice,ValidateSettings};
use Flarum\Extend;
return [
    (new Extend\Frontend('forum'))->js(__DIR__.'/js/dist/forum.js')->css(__DIR__.'/less/forum.less'),
    (new Extend\Frontend('admin'))->js(__DIR__.'/js/dist/admin.js')->css(__DIR__.'/less/admin.less'),
    new Extend\Locales(__DIR__.'/locale'),
    (new Extend\Formatter())->configure(ConfigureFormatter::class)->render(RenderDice::class),
    (new Extend\ServiceProvider())->register(DiceServiceProvider::class),
    (new Extend\Settings())->default('edgeinfinity-dice.config',json_encode(DiceConfig::defaults()))
        ->serializeToForum('edgeDiceConfig','edgeinfinity-dice.config',[DiceConfig::class,'decode']),
    (new Extend\Event())->listen(Flarum\Settings\Event\Saving::class,ValidateSettings::class),
];
