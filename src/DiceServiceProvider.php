<?php
namespace EdgeInfinity\Dice;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Post\CommentPost;
final class DiceServiceProvider extends AbstractServiceProvider
{
    public function boot(): void
    {
        CommentPost::creating(fn(CommentPost $post) => $this->container->make(RollPolicy::class)->creating($post));
        CommentPost::saving(fn(CommentPost $post) => $this->container->make(RollPolicy::class)->saving($post));
    }
}
