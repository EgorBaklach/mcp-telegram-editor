<?php

/**
 * @var int     $match    Процент совпадения заказа с профилем разработчика (0–100)
 * @var string  $title    Заголовок заказа на FL.ru
 * @var ?string $budget   Бюджет заказа или «По договорённости» (опционально)
 * @var string  $rate     Оценка рентабельность заказа из расчёта 1 500 ₽/ч
 * @var string  $summary  Краткий аналитический разбор сути задачи и стека
 * @var ?string $proposal Готовый текст персонализированного отклика для заказчика (опционально)
 * @var string  $link     Прямая ссылка на страницу заказа на FL.ru
 */
?>
<b>💼 Новый заказ на FL.ru — совпадение <?php echo $match ?>%</b>

<b>Заказ:</b> <?php echo $title.PHP_EOL ?>
<b>Бюджет:</b> <?php echo ($budget ?? '-').PHP_EOL ?>
<b>Рентабельность (1 500 ₽/ч):</b> <?php echo $rate.PHP_EOL ?>

<?php echo $summary.PHP_EOL ?>
<?php if($proposal ?? null): ?>

<pre><?php echo $proposal ?></pre>
<?php endif ?>

<a href="<?php echo $link ?>">Открыть заказ на FL.ru</a>