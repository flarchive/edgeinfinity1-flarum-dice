<?php
namespace EdgeInfinity\Dice;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Parser\Tag;
final class ConfigureFormatter
{
    public function __invoke(Configurator $config): void
    {
        $tag=$config->tags->add('EDGEDICE');
        $tag->attributes->add('min')->filterChain->append('#int');
        $tag->attributes->add('max')->filterChain->append('#int');
        $tag->filterChain->append([self::class,'valid'])->setJS('function(tag) { var a=Number(tag.getAttribute("min")), b=Number(tag.getAttribute("max")); return a<=b && Math.abs(a)<=1000000000 && Math.abs(b)<=1000000000; }');
        $tag->template = '<xsl:choose><xsl:when test="ancestor::QUOTE or ancestor::CODE or ancestor::C or ancestor::URL or ancestor::IMG or ancestor::EMAIL"><xsl:apply-templates/></xsl:when><xsl:otherwise>'
            .'<span class="EdgeDice" data-min="{@min}" data-max="{@max}">'
            .'<xsl:if test="@result"><xsl:attribute name="data-result"><xsl:value-of select="@result"/></xsl:attribute></xsl:if>'
            .'<xsl:if test="@edited"><xsl:attribute name="data-edited"><xsl:value-of select="@edited"/></xsl:attribute></xsl:if>'
            .'<xsl:if test="@state"><xsl:attribute name="data-state"><xsl:value-of select="@state"/></xsl:attribute></xsl:if>'
            // Excerpts consume this HTML as rich markup or plain text. Keep the
            // source as the only text; forum JS enhances full posts/previews only.
            .'<xsl:apply-templates/>'
            .'</span></xsl:otherwise></xsl:choose>';
        $config->Preg->match(DiceSyntax::PATTERN,'EDGEDICE');
    }
    public static function valid(Tag $tag): bool
    { $a=(int)$tag->getAttribute('min');$b=(int)$tag->getAttribute('max');return $a <= $b && abs($a)<=DiceSyntax::LIMIT && abs($b)<=DiceSyntax::LIMIT; }
}
