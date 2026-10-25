<?php
namespace EdgeInfinity\Dice;
use Flarum\Post\CommentPost;
use s9e\TextFormatter\Renderer;
final class RenderDice
{
    public function __invoke(Renderer $renderer, mixed $context, string $xml): string
    {
        $ledger=$context instanceof CommentPost ? DiceSyntax::ledger($context->getAttribute('edge_dice')) : null;
        // An edit while disabled can remove the formatter tags but not the retained ledger.
        // Reconstruct tags in memory only; accept the ledger only if the complete sequence still matches.
        if ($ledger && !str_contains($xml, '<EDGEDICE')) {
            $formatter=CommentPost::getFormatter();
            $xml=$formatter->parse($formatter->unparse($xml, $context) ?? '', $context);
        }
        $doc=DiceSyntax::document($xml);
        if (!$doc) return $xml;
        $ranges=DiceSyntax::ranges($xml);
        $valid=$ledger && DiceSyntax::matches($ranges,$ledger);
        $index=0;
        foreach (DiceSyntax::nodes($doc) as $node) {
            if (!DiceSyntax::range($node)) continue;
            // Result attributes in stored/imported XML are NEVER trusted.
            $node->removeAttribute('result');
            $node->removeAttribute('edited');
            $node->setAttribute('state', $context instanceof CommentPost && $context->exists ? 'unrolled' : 'pending');
            if ($valid) { $node->setAttribute('result',(string)$ledger['rolls'][$index][2]);$node->setAttribute('state','rolled');$node->setAttribute('edited',!empty($ledger['edited'])?'1':'0'); }
            $index++;
        }
        return $doc->saveXML($doc->documentElement);
    }
}
