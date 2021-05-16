<?php

declare(strict_types=1);

ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');

error_reporting(E_ALL);
date_default_timezone_set('Asia/Shanghai');

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));

function getMillisecond() {
    [$s1, $s2] = explode(' ', microtime());
    return (float) sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
}

require BASE_PATH . '/vendor/autoload.php';

if(defined('SWOOLE_HOOK_ALL') && class_exists('\Swoole\Process') && class_exists('\Swoole\Coroutine')) {
    echo 'start test multi process and multi coroutine concurrent'.PHP_EOL;
    \Swoole\Runtime::enableCoroutine(true, SWOOLE_HOOK_ALL);
    $maxProcess = swoole_cpu_num();
    for ($processNum = 1; $processNum <= $maxProcess; $processNum++) {
        $process = new \Swoole\Process(function () use($processNum) {
            $wg = new \Swoole\Coroutine\WaitGroup();
            $client = \BitMap\ClientFactory::make();
            $client->addRange(0, 200000);
            $client->add(200000000);
            $bytes = $client->toBytes();
            $maxCoroutine = swoole_cpu_num()*20;
            for($coroutineNum=0; $coroutineNum<$maxCoroutine; $coroutineNum++) {
                $wg->add(1);
                \Swoole\Coroutine::create(function () use($wg, $bytes, $processNum, $coroutineNum) {
                    $maxRequest = swoole_cpu_num()*200;
                    $success = 0;
                    $time = getMillisecond();
                    for($i=0; $i<$maxRequest; $i++) {
                        try {
                            $client = \BitMap\ClientFactory::make();
                            $client->fromBuffer($bytes);
                            if($client->getCardinality() == 200001) {
                                $success++;
                            }
                        }catch (Throwable $throwable) {
                            echo sprintf('%s in %s on line %d%s', $throwable->getMessage(), $throwable->getFile(), $throwable->getLine(), PHP_EOL);
                            break;
                        }
                    }
                    if($success != $maxRequest) {
                        echo sprintf('test concurrent failed%s',PHP_EOL);
                    }else{
                        $t = getMillisecond() - $time;
                        $success *= 3;
                        echo sprintf('processNum %d coroutineNum %d count %d ms %d req/s %d%s', $processNum, $coroutineNum, $success, $t, $success/($t/1000), PHP_EOL);
                    }
                    $wg->done();
                });
            }
            $wg->wait();
        },false, 0, true);
        $process->start();
    }

    for ($n = $maxProcess; $n--;) {
        $status = \Swoole\Process::wait(true);
    }

    //关闭所有hook，因为如果没有 sockets 扩展，则会采用stream socket，此时接下来的测试因为不再协程环境中，则会失败。
    \Swoole\Runtime::enableCoroutine(false);
}
echo 'start test one process and one connect speed'.PHP_EOL;
$client = \BitMap\ClientFactory::make();
$client->addRange(0, 200000);
$client->add(200000000);
$bytes = $client->toBytes();
$success = 0;
$time = getMillisecond();
for($i=0; $i<20000; $i++) {
    $client = \BitMap\ClientFactory::make();
    $client->fromBuffer($bytes);
    if($client->getCardinality() == 200001) {
        $success+=3;
    }
}
$t = getMillisecond() - $time;
echo sprintf('count %d ms %d req/s %d%s', $success, $t, $success/($t/1000), PHP_EOL);
echo 'start unit test'.PHP_EOL;
