# php的bitmap操作类

基于 [github.com/RoaringBitmap/roaring](https://pkg.go.dev/github.com/RoaringBitmap/roaring) 与 [github.com/spiral/goridge](https://pkg.go.dev/github.com/spiral/goridge) 给`php`提供完善的`bitmap`操作能力。
整个软件包分为`go`服务端与`php`客户端，`php`与`go`之间通过`tcp`或者是`unix domain socket`进行通信。

## 安装

1. 引入php包 `composer require buexplain/bitmap`
2. 根据系统环境执行go服务 `vendor\bin\bitmap-win-amd64.exe.bat` 或者是 `./vendor/bin/bitmap-linux-amd64.bin` 

```text
[root@localhost bitmap]# bin/bitmap-linux-amd64.bin -h
Usage of bin/bitmap-linux-amd64.bin:
  -address string
    	/tmp/bitmap-rpc.sock or 127.0.0.1:37101 (default "/tmp/bitmap-rpc.sock")
  -network string
    	unix or tcp (default "unix")
```

## 示例

```php
<?php

require "vendor/autoload.php";

$b1 = \BitMap\ClientFactory::make();
//添加一个
$b1->add(1);
$b1->add(2);
var_dump($b1->toArray()); //[1,2]
$b2 = \BitMap\ClientFactory::make();
//添加多个
$b2->addMany([2,3]);
var_dump($b2->toArray()); //[2,3]
//求并集，并将结果保存到$b1
$b1->or($b2);
var_dump($b1->toArray()); //[1,2,3]
//求交集，并将结果保存到$b1
$b1->and($b2);
var_dump($b1->toArray()); //[2,3]
//清空位图
$b1->clear();
$b2->clear();
//求差集，并将结果保存到$b1
$b1->addMany([1,2,3]);
$b2->addMany([3,4,5]);
$b1->xOr($b2);
var_dump($b1->toArray()); //[1,2,4,5]
//迭代，每次从$b1中弹出2个元素
while ($tmp = $b1->iterate(2)) {
    var_dump($tmp); // [1,2], [4,5]
}
```

## 手动编译go服务

```bash
cd server
go build -ldflags "-s -w" -o ../bin/bitmap-win-amd64.exe main.go
go build -ldflags "-s -w" -o ../bin/bitmap-linux-amd64.bin main.go
```

## License
[Apache-2.0](http://www.apache.org/licenses/LICENSE-2.0.html)
