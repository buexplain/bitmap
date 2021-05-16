# php的bitmap操作类

基于 [github.com/RoaringBitmap/roaring](https://pkg.go.dev/github.com/RoaringBitmap/roaring) 与 [github.com/spiral/goridge](https://pkg.go.dev/github.com/spiral/goridge) 给`php`提供完善的`bitmap`操作能力。
整个软件包分为`go`服务端与`php`客户端，`php`与`go`之间通过`socket_create(AF_INET, SOCK_STREAM, SOL_TCP)`或者是`socket_create(AF_UNIX, SOCK_STREAM, 0)`或者是`stream_socket_client`进行通信。

## 安装

1. 引入php包 `composer require buexplain/bitmap`
2. 根据系统环境执行go服务 `vendor\bin\bitmap-win-amd64.exe.bat` 或者是 `./vendor/bin/bitmap-linux-amd64.bin` 

```text
[root@localhost bitmap]# bin/bitmap-linux-amd64.bin -h
Usage of bin/bitmap-linux-amd64.bin:
  -address string
    	/run/bitmap-rpc.sock or 127.0.0.1:37101 (default "/run/bitmap-rpc.sock")
  -network string
    	unix or tcp (default "unix")
```

## 示例

```php
<?php

require "vendor/autoload.php";

$b1 = \BitMap\ClientFactory::make();
$b2 = \BitMap\ClientFactory::make();

//求并集，并将结果保存到$b1
$b1->addMany([1, 2]);
$b2->addMany([2, 3]);
$b1->or($b2);
print_r($b1->toArray()); //[1,2,3]

//求交集，并将结果保存到$b1
$b1->clear();
$b2->clear();
$b1->addMany([1, 2, 3]);
$b2->addMany([2, 3, 4]);
$b1->and($b2);
print_r($b1->toArray()); //[2,3]

//求差集，并将结果保存到$b1
$b1->clear();
$b2->clear();
$b1->addMany([1, 2, 3]);
$b2->addMany([1, 3, 4]);
$b1->andNot($b2);
print_r($b1->toArray()); //[2]

//求对称差集，并将结果保存到$b1
$b1->clear();
$b2->clear();
$b1->addMany([1, 2, 3]);
$b2->addMany([3, 4, 5]);
$b1->xOr($b2);
print_r($b1->toArray()); //[1,2,4,5]

//迭代，每次从$b1中弹出2个元素
$b1->clear();
$b1->addMany([1, 2, 3, 4, 5]);
while ($tmp = $b1->iterate(2)) {
    print_r($tmp); // [1,2], [3,4], [5]
}
```

## 手动编译go服务

```bash
cd server
set GOARCH=amd64&&set GOOS=windows&&go build -ldflags "-s -w" -o ../bin/bitmap-win-amd64.exe main.go
set GOARCH=amd64&&set GOOS=linux&&go build -ldflags "-s -w" -o ../bin/bitmap-linux-amd64.bin main.go
set GOARCH=amd64&&set GOOS=darwin&&go build -ldflags "-s -w" -o ../bin/bitmap-darwin-amd64.bin main.go
```

## License
[Apache-2.0](http://www.apache.org/licenses/LICENSE-2.0.html)
