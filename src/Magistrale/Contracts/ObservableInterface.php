<?php namespace Magistrale\Contracts;

interface ObservableInterface
{
    public function setObserver(ObserverInterface $observer): void;
}
