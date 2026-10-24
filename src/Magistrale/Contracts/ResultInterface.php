<?php namespace Magistrale\Contracts;

interface ResultInterface
{
    /**
     * Возвращает результат работы диспетчера.
     *
     * @return mixed
     */
    public function getResults(): mixed;
}
