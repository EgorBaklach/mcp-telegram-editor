<?php namespace Magistrale\Contracts;

interface OperableInterface
{
    public function setCallback(CallbackInterface $callback): void;
}
