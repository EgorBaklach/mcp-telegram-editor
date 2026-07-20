<?php namespace Magistrale\Dispatchers\Telegram;

interface ResultInterface
{
    /**
     * Возвращает результат работы диспетчера.
     *
     * @return mixed
     */
    public function getResults(): mixed;
}
