# php的bitmap操作类

基于 [github.com/RoaringBitmap/roaring](https://pkg.go.dev/github.com/RoaringBitmap/roaring) 与 [github.com/spiral/goridge](https://pkg.go.dev/github.com/spiral/goridge) 给`php`提供完善的`bitmap`操作能力。
整个软件包分为`go`服务端与`php`客户端，`php`与`go`之间通过`socket_create(AF_INET, SOCK_STREAM, SOL_TCP)`或者是`socket_create(AF_UNIX, SOCK_STREAM, 0)`或者是`stream_socket_client`进行通信。

## 安装

1. 引入php包 `composer require buexplain/bitmap`
2. 根据系统环境执行go服务 `vendor\bin\bitmap-win-amd64.exe.bat` 或者是 `./vendor/bin/bitmap-linux-amd64.bin` 
程序启动help：
```text
[root@localhost bin]# ./bitmap-linux-amd64.bin -h
Usage of ./bitmap-linux-amd64.bin:
  -address string
    	/run/bitmap-rpc.sock or 127.0.0.1:6060 (default "/run/bitmap-rpc.sock")
  -network string
    	unix or tcp (default "unix")
  -gcTick int
    	connection gc tick second (default 3)
  -reconnectWait int
    	reconnect wait second (default 60)
```
参数`gcTick`、`reconnectWait`的作用，请阅读 [源码](https://github.com/buexplain/bitmap/blob/main/server/connectionGC/connectionGC.go) 体会。

## 示例

```php
<?php

require "vendor/autoload.php";

use BitMap\ClientFactory;
$b1 = ClientFactory::make();
$b2 = ClientFactory::make();

//求并集，并将结果保存到$b1
$b1->addMany([1, 2]);
$b2->addMany([2, 3]);
$b1->or($b2);
print_r($b1->toArray()); //[1,2,3]

//求交集，并将结果保存到$b1
$b1->clear()->addMany([1, 2, 3]);
$b2->clear()->addMany([2, 3, 4]);
$b1->and($b2);
print_r($b1->toArray()); //[2,3]

//求差集，并将结果保存到$b1
$b1->clear()->addMany([1, 2, 3]);
$b2->clear()->addMany([1, 3, 4]);
$b1->andNot($b2);
print_r($b1->toArray()); //[2]

//求对称差集，并将结果保存到$b1
$b1->clear()->addMany([1, 2, 3]);
$b2->clear()->addMany([3, 4, 5]);
$b1->xOr($b2);
print_r($b1->toArray()); //[1,2,4,5]

//迭代，每次从$b1中弹出2个元素，所有迭代完成后，$b1中的元素个数是0
$b1->clear()->addMany([1, 2, 3, 4, 5, 200000000, 6, 20, 200000001]);
while ($tmp = $b1->iterate(2)) {
    print_r($tmp); // [1,2], [3,4], [5,6], [20,200000000], [200000001]
}
var_dump($b1->getCardinality() == 0); //true
```

## License
[Apache-2.0](http://www.apache.org/licenses/LICENSE-2.0.html)
