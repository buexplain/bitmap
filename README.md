# 请使用 https://github.com/buexplain/roaring 代替本包。

# php的bitmap操作类

基于 https://github.com/buexplain/roaring 实现的bitmap类

## 安装

`composer require buexplain/bitmap`

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

//迭代，每次从$b1中读取2个元素
$b1->clear()->addMany([1, 2, 3, 4, 5, 200000000, 6, 20, 200000001]);
$generator = $b1->generator();
foreach ($generator as $tmp) {
print_r($tmp); // [1,2], [3,4], [5,6], [20,200000000], [200000001]
}
```

## License

[Apache-2.0](http://www.apache.org/licenses/LICENSE-2.0.html)
