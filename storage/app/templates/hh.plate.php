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
<b>🏢 Новая вакансия на HH.ru — совпадение <?php echo $match ?>%</b><br><br>
<b>Вакансия:</b> <?php echo $title ?><br>
<b>Компания:</b> <?php echo $company ?? '-' ?><br>
<b>Локация:</b> <?php echo $address ?? '-' ?><br>
<b>Формат работы:</b> <?php echo $schedule ?? '-' ?><br>
<b>Зарплата:</b> <?php echo $salary ?? '-' ?><br>
<b>Оценка оффера (от 150 000 ₽ / 1 500 ₽/ч):</b> <?php echo $rate ?><br><br>
<?php echo $summary ?><br>
<?php if($proposal ?? null): ?>
<pre><?php echo $proposal ?></pre>
<?php else: ?>
<br>
<?php endif ?>
<a href="<?php echo $link ?>">Открыть вакансию на HH.ru</a>