<?php

declare(strict_types=1);

use BitMap\ClientFactory;
use Swoole\Coroutine;

ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');

error_reporting(E_ALL);
date_default_timezone_set('Asia/Shanghai');

!defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));

//判定是否测试指定目标
global $argv;
$testFilter = '';
foreach ($argv as $value) {
    if(stripos($value,'filter') !== false) {
        $testFilter = $value;
        break;
    }
}

function getMillisecond()
{
    [$s1, $s2] = explode(' ', microtime());
    return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
}

require BASE_PATH . '/vendor/autoload.php';

//多进程，多协程读写测试
if (
    ($testFilter == '' || $testFilter == '--filter=swoole') &&
    defined('SWOOLE_HOOK_ALL') &&
    class_exists('\Swoole\Process') &&
    class_exists('\Swoole\Coroutine')) {
    echo 'start test multi process and multi coroutine concurrent' . PHP_EOL;
    \Swoole\Runtime::enableCoroutine(true, SWOOLE_HOOK_ALL);
    $maxProcess = swoole_cpu_num();
    for ($processNum = 1; $processNum <= $maxProcess; $processNum++) {
        $process = new \Swoole\Process(function () use ($processNum) {
            $wg = new \Swoole\Coroutine\WaitGroup();
            $client = \BitMap\ClientFactory::getInstance()->get();
            $client->addRange(1, rand(1, 10) * 10000);
            for ($i = 1; $i <= 10; $i++) {
                $client->add(400000 + $i);
            }
            for ($i = 1; $i <= 10; $i++) {
                $client->add(3000000 + $i);
            }
            for ($i = 1; $i <= 10; $i++) {
                $client->add(20000000 + $i);
            }
            for ($i = 1; $i <= 10; $i++) {
                $client->add(100000000 + $i);
            }
            $cardinality = $client->getCardinality();
            $bytes = $client->toBytes();
            echo '进程' . $processNum . '开始 coroutineId --> ' . Coroutine::getCid() . PHP_EOL;
            $maxCoroutine = swoole_cpu_num() * 20;
            for ($coroutineNum = 0; $coroutineNum < $maxCoroutine; $coroutineNum++) {
                $wg->add(1);
                \Swoole\Coroutine::create(function () use ($wg, $bytes, $processNum, $coroutineNum, $cardinality) {
                    $maxRequest = swoole_cpu_num() * 200;
                    $success = 0;
                    $time = getMillisecond();
                    for ($i = 0; $i < $maxRequest; $i++) {
                        try {
                            $client = \BitMap\ClientFactory::make();
                            $client->fromBuffer($bytes);
                            if ($client->getCardinality() == $cardinality) {
                                $success++;
                            }
                            $client->clear();
                            $client->__destruct();
                            unset($client);
                        } catch (Throwable $throwable) {
                            echo sprintf('%s in %s on line %d%s', $throwable->getMessage(), $throwable->getFile(), $throwable->getLine(), PHP_EOL);
                            break;
                        }
                    }
                    if ($success != $maxRequest) {
                        echo sprintf('test concurrent failed%s', PHP_EOL);
                    } else {
                        $t = getMillisecond() - $time;
                        $success *= 3;
                        echo sprintf('processNum %d coroutineNum %d coroutineId %d count %d ms %d req/s %d%s', $processNum, $coroutineNum, Coroutine::getCid(), $success, $t, $success / ($t / 1000), PHP_EOL);
                    }
                    $wg->done();
                });
            }
            $wg->wait();
            echo '进程' . $processNum . '结束' . PHP_EOL;
            \BitMap\ClientFactory::getInstance()->__destruct();
        }, false, 0, true);
        $process->start();
    }

    for ($n = $maxProcess; $n--;) {
        $status = \Swoole\Process::wait(true);
    }
    echo '进程结束' . PHP_EOL;
    //关闭所有hook，因为如果没有 sockets 扩展，则会采用stream socket，此时接下来的测试因为不再协程环境中，则会失败。
    \Swoole\Runtime::enableCoroutine(false);
}

if($testFilter == '' || $testFilter == '--filter=speed') {
    echo 'start test one process and one connect speed' . PHP_EOL;
    $client = \BitMap\ClientFactory::make();
    $client->addRange(0, 200000);
    $client->add(200000000);
    $bytes = $client->toBytes();
    $success = 0;
    $time = getMillisecond();
    for ($i = 0; $i < 20000; $i++) {
        $client = \BitMap\ClientFactory::make();
        $client->fromBuffer($bytes);
        if ($client->getCardinality() == 200001) {
            $success += 3;
        }
    }
    $t = getMillisecond() - $time;
    echo sprintf('count %d ms %d req/s %d%s', $success, $t, $success / ($t / 1000), PHP_EOL);
}

echo 'start unit test' . PHP_EOL;
