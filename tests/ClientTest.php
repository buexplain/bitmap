<?php

declare(strict_types=1);

namespace BitMapTest;

use BitMap\Client;
use BitMap\ClientFactory;
use BitMap\RelayFactory;
use Exception;
use PHPUnit\Framework\TestCase;
use Spiral\Goridge\RelayInterface;
use Throwable;

/**
 * Class ClientTest
 * @see https://github.com/RoaringBitmap/roaring
 * @package BitMapTest
 */
class ClientTest extends TestCase
{
    public function testRelayFactory()
    {
        $relay = RelayFactory::make();
        $this->assertTrue($relay->connection instanceof RelayInterface);
        $this->assertTrue($relay->id > 0);
    }

    /**
     * @depends testRelayFactory
     */
    public function testClientFactory()
    {
        $client = ClientFactory::make();
        $this->assertTrue($client instanceof Client);
        $this->assertTrue(!empty($client->getId()));
    }

    /**
     * @depends testRelayFactory
     */
    public function testGetCardinality()
    {
        try {
            $client = ClientFactory::make();
            $this->assertTrue($client->getCardinality() == 0);
        }catch (Throwable $throwable) {
            $this->fail(sprintf('%s in %s on line %d', $throwable->getMessage(), __FILE__, __LINE__));
        }
    }

    /**
     * @depends testRelayFactory
     * @depends testGetCardinality
     * @throws Exception
     */
    public function testAdd()
    {
        $client = ClientFactory::make();
        $client->add(10);
        $this->assertTrue($client->getCardinality() == 1);
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testAndCardinality()
    {
        $clientA = ClientFactory::make();
        $clientB = ClientFactory::make();
        $clientA->add(1);
        $clientA->add(2);
        $clientA->add(3);
        $clientA->add(4);
        $clientB->add(1);
        $clientB->add(2);
        $clientB->add(3);
        $clientB->add(5);
        $this->assertTrue($clientA->andCardinality($clientB) == 3);
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testOrCardinality()
    {
        $clientA = ClientFactory::make();
        $clientB = ClientFactory::make();
        $clientA->add(1);
        $clientA->add(2);
        $clientA->add(3);
        $clientA->add(4);
        $clientB->add(1);
        $clientB->add(2);
        $clientB->add(3);
        $clientB->add(5);
        $this->assertTrue($clientA->orCardinality($clientB) == 5);
    }

    /**
     * @throws Exception
     */
    public function testCheckedAdd()
    {
        $client = ClientFactory::make();
        $this->assertTrue($client->checkedAdd(10));
        $this->assertFalse($client->checkedAdd(10));
        $this->assertTrue($client->getCardinality() == 1);
    }

    /**
     * @depends testGetCardinality
     * @throws Exception
     */
    public function testAddMany()
    {
        $client = ClientFactory::make();
        $client->addMany([0,1,0,1,2]);
        $this->assertTrue($client->getCardinality() == 3);
    }

    /**
     * @depends testGetCardinality
     * @throws Exception
     */
    public function testAddRange()
    {
        $client = ClientFactory::make();
        $client->addRange(0, 100);
        $this->assertTrue($client->getCardinality() == 100);
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testRank()
    {
        $client = ClientFactory::make();
        $this->assertTrue($client->rank(0) == 0);
        $this->assertTrue($client->rank(1) == 0);
        $client->add(8);
        $client->add(9);
        $client->add(10);
        $this->assertTrue($client->rank(10) == 3);
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testContains()
    {
        $client = ClientFactory::make();
        $this->assertFalse($client->contains(0));
        $this->assertFalse($client->contains(100000000));
        $client->add(0);
        $this->assertTrue($client->contains(0));
        $client->add(100000000);
        $this->assertTrue($client->contains(100000000));
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testRemove()
    {
        $client = ClientFactory::make();
        $client->remove(0);
        $client->remove(100000000);
        $client->add(0);
        $client->remove(0);
        $client->add(100000000);
        $client->remove(100000000);
        $this->assertTrue($client->getCardinality() == 0);
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testCheckedRemove()
    {
        $client = ClientFactory::make();
        $this->assertFalse($client->checkedRemove(0));
        $this->assertFalse($client->checkedRemove(100000000));
        $client->add(0);
        $client->add(100000000);
        $this->assertTrue($client->checkedRemove(0));
        $this->assertTrue($client->checkedRemove(100000000));
        $this->assertTrue($client->getCardinality() == 0);
    }

    /**
     * @depends testAddMany
     * @throws Exception
     */
    public function testRemoveMany()
    {
        $client = ClientFactory::make();
        $client->addMany([0,1,0,1,2]);
        $this->assertTrue($client->getCardinality() == 3);
        $client->removeMany([0,1,0,1,2]);
        $this->assertTrue($client->getCardinality() == 0);
    }

    /**
     * @depends testAddRange
     * @throws Exception
     */
    public function testRemoveRange()
    {
        $client = ClientFactory::make();
        $client->addRange(0, 100);
        $this->assertTrue($client->getCardinality() == 100);
        $client->removeRange(0, 50);
        $this->assertTrue($client->getCardinality() == 50);
    }

    /**
     * @depends testAddRange
     * @throws Exception
     */
    public function testFlip()
    {
        $client = ClientFactory::make();
        $client->addRange(2, 6);
        $this->assertTrue($client->getCardinality() == 4);
        $client->flip(2, 6);
        $this->assertTrue($client->getCardinality() == 0);
        $client->flip(2, 6);
        $this->assertTrue($client->getCardinality() == 4);
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testClear()
    {
        $client = ClientFactory::make();
        $client->clear();
        $client->add(1);
        $this->assertTrue($client->getCardinality() == 1);
        $client->clear();
        $this->assertTrue($client->getCardinality() == 0);
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testIsEmpty()
    {
        $client = ClientFactory::make();
        $this->assertTrue($client->isEmpty());
        $client->add(1);
        $this->assertFalse($client->isEmpty());
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testSelect()
    {
        $client = ClientFactory::make();
        $client->add(1);
        $client->add(2);
        $client->add(3);
        $this->assertTrue($client->select(0) == 1);
        $this->assertTrue($client->select(2) == 3);
        $this->assertTrue($client->select(4) == -1);
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testMinimum()
    {
        $client = ClientFactory::make();
        $client->add(1);
        $client->add(5);
        $this->assertTrue($client->minimum() == 1);
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testMaximum()
    {
        $client = ClientFactory::make();
        $client->add(5);
        $this->assertTrue($client->maximum() == 5);
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testString()
    {
        $client = ClientFactory::make();
        $this->assertTrue($client->string() == '{}');
        $client->add(1);
        $this->assertTrue($client->string() == '{1}');
        $client->add(2);
        $this->assertTrue($client->string() == '{1,2}');
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testToArray()
    {
        $client = ClientFactory::make();
        $this->assertTrue(empty($client->toArray()));
        $client->add(1);
        $this->assertTrue($client->toArray() == [1]);
        $client->add(2);
        $this->assertTrue($client->toArray() == [1,2]);
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testToBase64()
    {
        $client = ClientFactory::make();
        $this->assertTrue($client->toBase64() == 'OjAAAAAAAAA=');
        $client->add(1);
        $this->assertTrue($client->toBase64() == 'OjAAAAEAAAAAAAAAEAAAAAEA');
        $client->add(2);
        $this->assertTrue($client->toBase64() == 'OjAAAAEAAAAAAAEAEAAAAAEAAgA=');
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testToBytes()
    {
        $client = ClientFactory::make();
        $this->assertTrue($client->toBytes() == base64_decode('OjAAAAAAAAA='));
        $client->add(1);
        $this->assertTrue($client->toBytes() == base64_decode('OjAAAAEAAAAAAAAAEAAAAAEA'));
        $client->add(2);
        $this->assertTrue($client->toBytes() == base64_decode('OjAAAAEAAAAAAAEAEAAAAAEAAgA='));
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testFromBase64()
    {
        $client = ClientFactory::make();
        for($i=10000;$i<10010;$i++) {
            if($i > 10000) {
                $client->add($i);
            }
            $b64 = $client->toBase64();
            $target = ClientFactory::make();
            $target->fromBase64($b64);
            $this->assertTrue($target->toBase64() == $b64);
        }
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testFromBuffer()
    {
        $client = ClientFactory::make();
        for($i=10000;$i<10010;$i++) {
            if($i > 10000) {
                $client->add($i);
            }
            $bytes = $client->toBytes();
            $target = ClientFactory::make();
            $target->fromBuffer($bytes);
            $this->assertTrue($target->toBytes() == $bytes);
        }
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testGetSizeInBytes()
    {
        $client = ClientFactory::make();
        $this->assertTrue($client->getSizeInBytes() == 8);
        $client->add(1);
        $this->assertTrue($client->getSizeInBytes() == 12);
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testGetSerializedSizeInBytes()
    {
        $client = ClientFactory::make();
        $this->assertTrue($client->getSerializedSizeInBytes() == 8);
        $client->add(1);
        $this->assertTrue($client->getSerializedSizeInBytes() == 18);
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testStats()
    {
        $client = ClientFactory::make();
        $this->assertTrue(array_values($client->stats()) == [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]);
        $client->add(1);
        $this->assertTrue(array_values($client->stats()) == [1, 1, 1, 2, 1, 0, 0, 0, 0, 0, 0]);
    }

    /**
     * @throws Exception
     */
    public function testSetCopyOnWrite()
    {
        $client = ClientFactory::make();
        $client->setCopyOnWrite(true);
        $this->assertTrue(true);
    }

    /**
     * @depends testSetCopyOnWrite
     * @throws Exception
     */
    public function testGetCopyOnWrite()
    {
        $client = ClientFactory::make();
        $this->assertFalse($client->getCopyOnWrite());
        $client->setCopyOnWrite(true);
        $this->assertTrue($client->getCopyOnWrite());
    }

    /**
     * @throws Exception
     */
    public function testClone()
    {
        $client = ClientFactory::make();
        $result = clone $client;
        $this->assertTrue($result instanceof Client);
        $this->assertTrue($result->getId() != $client->getId());
        $this->assertTrue($client->toArray() == $result->toArray());
        $client->add(1);
        $client->add(2);
        $this->assertFalse($client->toArray() == $result->toArray());
        $result->add(1);
        $result->add(2);
        $this->assertTrue($client->toArray() == $result->toArray());
    }

    /**
     * @throws Exception
     */
    public function testCloneCopyOnWriteContainers()
    {
        $client = ClientFactory::make();
        $client->cloneCopyOnWriteContainers();
        $this->assertTrue(true);
    }

    /**
     * @throws Exception
     */
    public function testHasRunCompression()
    {
        $client = ClientFactory::make();
        $this->assertTrue($client->hasRunCompression() == false);
    }

    /**
     * @throws Exception
     */
    public function testRunOptimize()
    {
        $client = ClientFactory::make();
        $client->runOptimize();
        $this->assertTrue(true);
    }

    /**
     * @depends testAdd
     * @depends testToArray
     * @throws Exception
     */
    public function testAnd()
    {
        $clientA = ClientFactory::make();
        $clientB = ClientFactory::make();
        $clientA->and($clientB);
        $this->assertTrue($clientA->toArray() == []);
        $clientA->add(1);
        $clientA->add(2);
        $clientA->add(3);
        $clientA->add(4);
        $clientB->add(1);
        $clientB->add(2);
        $clientB->add(3);
        $clientB->add(5);
        $clientA->and($clientB);
        $this->assertTrue($clientA->toArray() == [1,2,3]);

        $clientB->clear();
        $clientB->add(5);
        $clientA->and($clientB);
        $this->assertTrue($clientA->toArray() == []);
    }

    /**
     * @depends testAdd
     * @depends testToArray
     * @throws Exception
     */
    public function testAndBuffer()
    {
        $clientA = ClientFactory::make();
        $clientB = ClientFactory::make();
        $clientA->andBuffer($clientB->toBytes());
        $this->assertTrue($clientA->toArray() == []);
        $clientA->add(1);
        $clientA->add(2);
        $clientA->add(3);
        $clientA->add(4);
        $clientB->add(1);
        $clientB->add(2);
        $clientB->add(3);
        $clientB->add(5);
        $clientA->andBuffer($clientB->toBytes());
        $this->assertTrue($clientA->toArray() == [1,2,3]);

        $clientB->clear();
        $clientB->add(5);
        $clientA->andBuffer($clientB->toBytes());
        $this->assertTrue($clientA->toArray() == []);
    }

    /**
     * @depends testAdd
     * @depends testToArray
     * @throws Exception
     */
    public function testAndAny()
    {
        $clientA = ClientFactory::make();
        $clientA->add(1);
        $clientA->add(2);
        $clientA->add(3);
        $clientA->add(6);
        $clientB = ClientFactory::make();
        $clientB->add(2);
        $clientB->add(4);
        $clientB->add(6);
        $clientB->add(7);
        $client3 = ClientFactory::make();
        $client3->add(3);
        $client3->add(5);
        $client3->add(6);
        $client3->add(7);
        $clientA->andAny($clientB, $client3);
        $this->assertTrue($clientA->toArray() == [2,3,6]);
    }

    /**
     * @depends testAdd
     * @depends testToArray
     * @throws Exception
     */
    public function testAndAnyBuffer()
    {
        $clientA = ClientFactory::make();
        $clientA->add(1);
        $clientA->add(2);
        $clientA->add(3);
        $clientA->add(6);
        $clientB = ClientFactory::make();
        $clientB->add(2);
        $clientB->add(4);
        $clientB->add(6);
        $clientB->add(7);
        $client3 = ClientFactory::make();
        $client3->add(3);
        $client3->add(5);
        $client3->add(6);
        $client3->add(7);
        $clientA->andAnyBuffer($clientB->toBytes(), $client3->toBytes());
        $this->assertTrue($clientA->toArray() == [2,3,6]);
    }

    /**
     * @depends testAdd
     * @depends testToArray
     * @throws Exception
     */
    public function testAndNot()
    {
        $clientA = ClientFactory::make();
        $clientB = ClientFactory::make();
        $clientA->andNot($clientB);
        $this->assertTrue($clientA->toArray() == []);
        $clientA->add(1);
        $clientA->add(2);
        $clientA->add(3);
        $clientB->add(1);
        $clientB->add(2);
        $clientB->add(4);
        $clientA->andNot($clientB);
        $this->assertTrue($clientA->toArray() == [3]);
    }

    /**
     * @depends testAdd
     * @depends testToArray
     * @throws Exception
     */
    public function testAndNotBuffer()
    {
        $clientA = ClientFactory::make();
        $clientB = ClientFactory::make();
        $clientA->andNotBuffer($clientB->toBytes());
        $this->assertTrue($clientA->toArray() == []);
        $clientA->add(1);
        $clientA->add(2);
        $clientA->add(3);
        $clientB->add(1);
        $clientB->add(2);
        $clientB->add(4);
        $clientA->andNotBuffer($clientB->toBytes());
        $this->assertTrue($clientA->toArray() == [3]);
    }

    /**
     * @depends testAdd
     * @depends testToArray
     * @throws Exception
     */
    public function testAndNotAnyBuffer()
    {
        $clientA = ClientFactory::make();
        $clientB = ClientFactory::make();
        $clientC = ClientFactory::make();
        $clientA->add(1);
        $clientA->add(2);
        $clientA->add(3);
        $clientA->add(4);
        $clientA->add(5);

        $clientB->add(1);
        $clientB->add(2);
        $clientB->add(8);

        $clientC->add(4);
        $clientB->add(8);
        $clientB->add(9);

        $clientA->andNotAnyBuffer($clientB->toBytes(), $clientC->toBytes());
        $this->assertTrue($clientA->toArray() == [3, 5]);
    }

    /**
     * @depends testAdd
     * @depends testToArray
     * @throws Exception
     */
    public function testOr()
    {
        $clientA = ClientFactory::make();
        $clientB = ClientFactory::make();
        $clientA->or($clientB);
        $this->assertTrue($clientA->toArray() == []);
        $clientA->add(1);
        $clientA->add(2);
        $clientB->add(1);
        $clientB->add(3);
        $clientA->or($clientB);
        $this->assertTrue($clientA->toArray() == [1,2,3]);
    }

    /**
     * @depends testAdd
     * @depends testToArray
     * @throws Exception
     */
    public function testOrBuffer()
    {
        $clientA = ClientFactory::make();
        $clientB = ClientFactory::make();
        $clientA->orBuffer($clientB->toBytes());
        $this->assertTrue($clientA->toArray() == []);
        $clientA->add(1);
        $clientA->add(2);
        $clientB->add(1);
        $clientB->add(3);
        $clientA->orBuffer($clientB->toBytes());
        $this->assertTrue($clientA->toArray() == [1,2,3]);
    }

    /**
     * @depends testAdd
     * @depends testToArray
     * @throws Exception
     */
    public function testOrAnyBuffer()
    {
        $clientA = ClientFactory::make();
        $clientB = ClientFactory::make();
        //空bitmap
        $clientA->orAnyBuffer($clientB->toBytes());
        $this->assertTrue($clientA->toArray() == []);

        //添加了两个元素
        $clientA->add(1);
        $clientA->add(2);
        $this->assertTrue($clientA->toArray() == [1,2]);

        //or了一个bitmap
        $clientB->add(1);
        $clientB->add(3);
        $clientA->orAnyBuffer($clientB->toBytes());
        $this->assertTrue($clientA->toArray() == [1,2,3]);

        //又or了两个bitmap
        $clientC = ClientFactory::make();
        $clientC->add(4);
        $clientD = ClientFactory::make();
        $clientD->add(5);
        $clientA->orAnyBuffer($clientC->toBytes(), $clientD->toBytes());
        $this->assertTrue($clientA->toArray() == [1,2,3,4,5]);
    }

    /**
     * @depends testAdd
     * @depends testToArray
     * @throws Exception
     */
    public function testOrAnyGroupBuffer()
    {
        $clientA = ClientFactory::make();
        $groupBuffer = [
            'a'=>[
                ClientFactory::make()->addMany([1,2,3])->toBytes(),
                ClientFactory::make()->addMany([2,3])->toBytes(),
                ClientFactory::make()->addMany([4,5])->toBytes(),
            ],
            'b'=>[
                ClientFactory::make()->addMany([3])->toBytes(),
                ClientFactory::make()->addMany([2,3])->toBytes(),
                ClientFactory::make()->addMany([4,5,6])->toBytes(),
            ],
        ];
        $result = $clientA->orAnyGroupBuffer($groupBuffer);
        foreach ($result as $group=>$client) {
            if($group == 'a') {
                $this->assertTrue($client->toArray() == [1,2,3,4,5]);
            }elseif($group == 'b') {
                $this->assertTrue($client->toArray() == [2,3,4,5,6]);
            }
        }
        //所有组交集汇总
        $this->assertTrue($clientA->toArray() == [1,2,3,4,5,6]);
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testXor()
    {
        $clientA = ClientFactory::make();
        $clientB = ClientFactory::make();
        $clientA->add(1);
        $clientA->add(2);
        $clientB->add(1);
        $clientB->add(3);
        $clientA->xOr($clientB);
        $this->assertTrue($clientA->toArray() == [2,3]);
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testXorBuffer()
    {
        $clientA = ClientFactory::make();
        $clientB = ClientFactory::make();
        $clientA->add(1);
        $clientA->add(2);
        $clientB->add(1);
        $clientB->add(3);
        $clientA->xOrBuffer($clientB->toBytes());
        $this->assertTrue($clientA->toArray() == [2,3]);
    }

    /**
     * @depends testAdd
     * @depends testToArray
     * @throws Exception
     */
    public function testIntersects()
    {
        $clientA = ClientFactory::make();
        $clientB = ClientFactory::make();
        $clientA->intersects($clientB);
        $this->assertTrue($clientA->toArray() == []);
        $clientA->add(1);
        $this->assertFalse($clientA->intersects($clientB));
        $clientB->add(1);
        $this->assertTrue($clientA->intersects($clientB));
        $clientA->add(2);
        $clientB->add(3);
        $this->assertTrue($clientA->intersects($clientB));
        $this->assertTrue($clientA->toArray() == [1, 2]);
    }

    /**
     * @depends testAdd
     * @depends testToArray
     * @throws Exception
     */
    public function testIntersectsBuffer()
    {
        $clientA = ClientFactory::make();
        $clientB = ClientFactory::make();
        $clientA->intersectsBuffer($clientB->toBytes());
        $this->assertTrue($clientA->toArray() == []);
        $clientA->add(1);
        $this->assertFalse($clientA->intersectsBuffer($clientB->toBytes()));
        $clientB->add(1);
        $this->assertTrue($clientA->intersectsBuffer($clientB->toBytes()));
        $clientA->add(2);
        $clientB->add(3);
        $this->assertTrue($clientA->intersectsBuffer($clientB->toBytes()));
        $this->assertTrue($clientA->toArray() == [1, 2]);
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testEquals()
    {
        $clientA = ClientFactory::make();
        $clientB = ClientFactory::make();
        $this->assertTrue($clientA->equals($clientB));
        $clientA->add(1);
        $clientB->add(1);
        $this->assertTrue($clientA->equals($clientB));
        $clientA->add(5);
        $clientB->add(4);
        $this->assertFalse($clientA->equals($clientB));
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testEqualsBuffer()
    {
        $clientA = ClientFactory::make();
        $clientB = ClientFactory::make();
        $this->assertTrue($clientA->equalsBuffer($clientB->toBytes()));
        $clientA->add(1);
        $clientB->add(1);
        $this->assertTrue($clientA->equalsBuffer($clientB->toBytes()));
        $clientA->add(5);
        $clientB->add(4);
        $this->assertFalse($clientA->equalsBuffer($clientB->toBytes()));
    }

    /**
     * @depends testAdd
     * @throws Exception
     */
    public function testIterate()
    {
        $client = ClientFactory::make();
        for($i=0; $i<100; $i++) {
            $client->add($i);
        }
        $clone = clone $client;
        $result = [];
        while (true) {
            $tmp = $clone->iterate(2);
            if(count($tmp) == 0) {
                break;
            }
            $result = array_merge($result, $tmp);
        }
        $this->assertTrue($clone->getCardinality() == 0);
        $this->assertTrue($result == $client->toArray());
    }
}