<?php

use GuzzleHttp\Exception\GuzzleException;

/**
 * @var Throwable $e       Перехваченное исключение (сетевой сбой Guzzle или фатальная ошибка пайплайна)
 * @var string    $message Экранированный текст сообщения об ошибке ($e->getMessage())
 */
?>
<b><?php echo $e instanceof GuzzleException ? '⚠️' : '❌' ?> <?php echo $e::class ?> [<?php echo basename($e->getFile()) ?>:<?php echo $e->getLine() ?>]</b>
<pre><?php echo $message ?></pre><?php if($e instanceof GuzzleException): ?>

<i>ℹ️ Сетевой сбой перехвачен, пайп продолжил работу.</i><?php endif ?>
