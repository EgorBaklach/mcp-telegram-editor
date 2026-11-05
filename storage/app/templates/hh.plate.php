<?php

/**
 * @var int     $match    Процент совпадения вакансии с профилем разработчика (0–100)
 * @var string  $title    Название вакансии на HH.ru
 * @var ?string $company  Название компании-работодателя (опционально)
 * @var ?string $address  Город или список регионов публикации вакансии (опционально)
 * @var ?string $schedule Формат работы и график (удалёнка, гибрид, офис) (опционально)
 * @var ?string $salary   Указанная зарплатная вилка вакансии (опционально)
 * @var string  $rate     Оценка рентабельности оффера относительно порога 150 000 ₽ / 1 500 ₽/ч
 * @var string  $summary  Краткий аналитический разбор требований и задач вакансии
 * @var ?string $proposal Готовый текст сопроводительного письма (Cover Letter) (опционально)
 * @var string  $link     Прямая ссылка на страницу вакансии на HH.ru
 */
?>
<b>🏢 Новая вакансия на HH.ru — совпадение <?php echo $match ?>%</b>

<b>Вакансия:</b> <?php echo $title.PHP_EOL ?>
<b>Компания:</b> <?php echo ($company ?? '-').PHP_EOL ?>
<b>Локация:</b> <?php echo ($address ?? '-').PHP_EOL ?>
<b>Формат работы:</b> <?php echo ($schedule ?? '-').PHP_EOL ?>
<b>Зарплата:</b> <?php echo ($salary ?? '-').PHP_EOL ?>
<b>Оценка оффера (от 150 000 ₽ / 1 500 ₽/ч):</b> <?php echo $rate.PHP_EOL ?>

<?php echo $summary.PHP_EOL ?>
<?php if($proposal ?? null): ?>

<pre><?php echo $proposal ?></pre>
<?php endif ?>

<a href="<?php echo $link ?>">Открыть вакансию на HH.ru</a>