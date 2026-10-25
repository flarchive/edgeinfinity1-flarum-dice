<?php
namespace EdgeInfinity\Dice;

use Flarum\Foundation\ValidationException;
use Flarum\Locale\TranslatorInterface;
use Flarum\Post\CommentPost;
use Flarum\Settings\SettingsRepositoryInterface;

final class RollPolicy
{
    public function __construct(private SettingsRepositoryInterface $settings, private TranslatorInterface $translator) {}

    public function creating(CommentPost $post): void
    {
        $ranges = DiceSyntax::ranges($post->parsed_content ?? '');
        $this->limit($ranges);
        $rolls = array_map(static fn(array $r): array => [$r[0], $r[1], random_int($r[0], $r[1])], $ranges);
        $post->setAttribute('edge_dice', $rolls ? $this->encode($rolls, false) : null);
    }

    public function saving(CommentPost $post): void
    {
        if (!$post->exists) return;
        $stored = $post->getRawOriginal('edge_dice');
        $post->setAttribute('edge_dice', $stored); // Never accept a client/model-assigned ledger.
        if (!$post->isDirty('content')) return;
        $ledger = DiceSyntax::ledger($stored);
        if ($stored && !$ledger) $this->fail('invalid_ledger');
        $ranges = DiceSyntax::ranges($post->parsed_content ?? '');
        if ($ledger && DiceSyntax::matches($ranges, $ledger)) return; // Prose-only edits never reroll.
        $config = DiceConfig::decode($this->settings->get('edgeinfinity-dice.config'));
        if (!$config['allowEdit']) {
            if ($ledger && $ledger['rolls']) $this->fail('locked');
            return;
        }
        if (!$ranges && !$ledger) return;
        $this->limit($ranges);
        // A range has no stable textual ID. Match identical ranges FIFO so moving
        // or deleting one never assigns a result to a different range.
        $available = [];
        foreach ($ledger['rolls'] ?? [] as $roll) $available[$roll[0].':'.$roll[1]][] = $roll;
        $rolls = [];
        foreach ($ranges as [$min, $max]) {
            $key = $min.':'.$max;
            $rolls[] = !empty($available[$key]) ? array_shift($available[$key]) : [$min, $max, random_int($min, $max)];
        }
        // Dropped rolls are absent from the new ledger. Keep only this small flag
        // when no dice remain: deletion and later addition must not erase the warning.
        // All surviving dice are flagged after sequence changes (selection risk).
        $post->setAttribute('edge_dice', $this->encode($rolls, true));
    }

    private function limit(array $ranges): void
    {
        $max = DiceConfig::decode($this->settings->get('edgeinfinity-dice.config'))['maxDice'];
        if (count($ranges) > $max) $this->fail('too_many', ['{count}' => $max]);
    }

    private function encode(array $rolls, bool $edited): string
    {
        // Keep the v1 roll tuple layout so beta.1 can still display retained
        // results if code is rolled back. It simply ignores this optional flag.
        return json_encode(['v' => 1, 'rolls' => $rolls, 'edited' => $edited], JSON_THROW_ON_ERROR);
    }

    private function fail(string $key, array $parameters = []): never
    {
        throw new ValidationException(['content' => $this->translator->trans('edgeinfinity-dice.forum.error_'.$key, $parameters)]);
    }
}
