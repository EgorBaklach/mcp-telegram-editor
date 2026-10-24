<?php namespace Magistrale\Traits;

use Magistrale\Contracts\CallbackInterface;

trait OperableTrait
{
    public CallbackInterface $callback;

    public function setCallback(CallbackInterface $callback): void
    {
        $this->callback = $callback;
    }
}
