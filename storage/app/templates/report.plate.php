<?php

/**
 * @var array<string, array{total: int, published: int}> $sources Статистика по каждому источнику за 24 часа
 */
?>
<b>📊 Сводный отчёт source-monitor за 24 часа (<?php echo date('d.m.Y H:i') ?> UTC)</b><br>
<?php foreach($sources as $source => $stats): ?>
<br><b>🔹 <?php echo strtoupper($source) ?>:</b><br>
• Найдено за сутки: <b><?php echo $stats['total'] ?></b><br>
• Опубликовано: <b><?php echo $stats['published'] ?></b><br>
<?php endforeach ?>