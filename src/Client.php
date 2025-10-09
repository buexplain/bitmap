<?php

declare(strict_types=1);

namespace BitMap;

use Generator;
use InvalidArgumentException;
use Roaring\Bitmap;
use RuntimeException;

/**
 * Class Client
 * 基于 https://github.com/buexplain/roaring 实现的bitmap类
 * @package BitMap
 */
class Client
{
    protected Bitmap $bitmap;

    public function __construct()
    {
        $this->bitmap = new Bitmap();
    }

    /**
     * GetCardinality returns the number of integers contained in the bitmap
     * @return int
     */
    public function getCardinality(): int
    {
        return $this->bitmap->getCardinality();
    }

    /**
     * AndCardinality returns the cardinality of the intersection between two bitmaps, bitmaps are not modified
     * @param Client $client
     * @return int
     */
    public function andCardinality(Client $client): int
    {
        return $this->bitmap->andCardinality($client->bitmap);
    }

    /**
     * OrCardinality  returns the cardinality of the union between two bitmaps, bitmaps are not modified
     * @param Client $client
     * @return int
     */
    public function orCardinality(Client $client): int
    {
        return $this->bitmap->orCardinality($client->bitmap);
    }

    /**
     * Add the uint32 x to the bitmap
     * @param int $x
     * @return $this
     */
    public function add(int $x): self
    {
        if ($x < 0) {
            throw new InvalidArgumentException('param has to be uint32');
        }
        $this->bitmap->add($x);
        return $this;
    }

    /**
     * CheckedAdd adds the integer x to the bitmap and return true  if it was added (false if the integer was already present)
     * @param int $x
     * @return bool
     */
    public function checkedAdd(int $x): bool
    {
        if ($x < 0) {
            throw new InvalidArgumentException('param has to be uint32');
        }
        return $this->bitmap->addChecked($x);
    }

    /**
     * AddMany add all the values in dat
     * @param int[] $x
     * @return $this
     */
    public function addMany(array $x): self
    {
        $this->bitmap->addMany($x);
        return $this;
    }

    /**
     * AddRange adds the integers in [rangeStart, rangeEnd) to the bitmap.
     * The function uses 64-bit parameters even though a Bitmap stores 32-bit values because it is allowed and meaningful to use [0,uint64(0x100000000)) as a range
     * while uint64(0x100000000) cannot be represented as a 32-bit value.
     * @param int $rangeStart
     * @param int $rangeEnd
     * @return $this
     */
    public function addRange(int $rangeStart, int $rangeEnd): self
    {
        if ($rangeStart < 0 || $rangeEnd < 0) {
            throw new InvalidArgumentException('param has to be uint32');
        }
        $this->bitmap->addRange($rangeStart, $rangeEnd);
        return $this;
    }

    /**
     * Rank returns the number of integers that are smaller or equal to x (Rank(infinity) would be GetCardinality()).
     * If you pass the smallest value, you get the value 1. If you pass a value that is smaller than the smallest
     * value, you get 0. Note that this function differs in convention from the Select function since it
     * return 1 and not 0 on the smallest value.
     * @param int $x
     * @return int
     */
    public function rank(int $x): int
    {
        if ($x < 0) {
            throw new InvalidArgumentException('param has to be uint32');
        }
        return $this->bitmap->rank($x);
    }

    /**
     * Contains returns true if the integer is contained in the bitmap
     * @param int $x
     * @return bool
     */
    public function contains(int $x): bool
    {
        if ($x < 0) {
            throw new InvalidArgumentException('param has to be uint32');
        }
        return $this->bitmap->contains($x);
    }

    /**
     * Remove the integer x from the bitmap
     * @param int $x
     * @return $this
     */
    public function remove(int $x): self
    {
        if ($x < 0) {
            throw new InvalidArgumentException('param has to be uint32');
        }
        $this->bitmap->remove($x);
        return $this;
    }

    /**
     * CheckedRemove removes the integer x from the bitmap and return true if the integer was effectively remove (and false if the integer was not present)
     * @param int $x
     * @return bool
     */
    public function checkedRemove(int $x): bool
    {
        if ($x < 0) {
            throw new InvalidArgumentException('param has to be uint32');
        }
        return $this->bitmap->removeChecked($x);
    }

    /**
     * RemoveMany remove all of the values in dat
     * @param int[] $x
     * @return $this
     */
    public function removeMany(array $x): self
    {
        $this->bitmap->removeMany($x);
        return $this;
    }

    /**
     *  RemoveRange removes the integers in [rangeStart, rangeEnd) from the bitmap.
     *  The function uses 64-bit parameters even though a Bitmap stores 32-bit values because it is allowed and meaningful to use [0,uint64(0x100000000)) as a range
     *  while uint64(0x100000000) cannot be represented as a 32-bit value.
     * @param int $rangeStart
     * @param int $rangeEnd
     * @return $this
     */
    public function removeRange(int $rangeStart, int $rangeEnd): self
    {
        if ($rangeStart < 0 || $rangeEnd < 0) {
            throw new InvalidArgumentException('param has to be uint32');
        }
        $this->bitmap->removeRange($rangeStart, $rangeEnd);
        return $this;
    }

    /**
     * Flip negates the bits in the given range (i.e., [rangeStart,rangeEnd)), any integer present in this range and in the bitmap is removed,
     * and any integer present in the range and not in the bitmap is added.
     * The function uses 64-bit parameters even though a Bitmap stores 32-bit values because it is allowed and meaningful to use [0,uint64(0x100000000)) as a range
     * while uint64(0x100000000) cannot be represented as a 32-bit value.
     * @param int $rangeStart
     * @param int $rangeEnd
     * @return $this
     */
    public function flip(int $rangeStart, int $rangeEnd): self
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     *  Clear resets the Bitmap to be logically empty, but may retain
     *  some memory allocations that may speed up future operations
     * @return $this
     */
    public function clear(): self
    {
        $this->bitmap->clear();
        return $this;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->bitmap->isEmpty();
    }

    /**
     * Select returns the xth integer in the bitmap. If you pass 0, you get
     * the smallest element. Note that this function differs in convention from
     * the Rank function which returns 1 on the smallest value.
     * If overflowed, you get -1.
     * @param int $position
     * @return int
     */
    public function select(int $position): int
    {
        if ($position < 0) {
            throw new InvalidArgumentException('param has to be uint32');
        }
        $ret = $this->bitmap->select($position);
        if (is_null($ret)) {
            return -1;
        }
        return $ret;
    }

    /**
     * Minimum get the smallest value stored in this roaring bitmap, assumes that it is not empty
     * @return int
     */
    public function minimum(): int
    {
        return $this->bitmap->minimum();
    }

    /**
     * Maximum get the largest value stored in this roaring bitmap, assumes that it is not empty
     * @return int
     */
    public function maximum(): int
    {
        return $this->bitmap->maximum();
    }

    /**
     * String creates a string representation of the Bitmap
     * @return string
     */
    public function string(): string
    {
        return '{' . implode(',', $this->bitmap->toArray()) . '}';
    }

    /**
     * ToArray creates a new slice containing all the integers stored in the Bitmap in sorted order
     * @return int[]
     */
    public function toArray(): array
    {
        return $this->bitmap->toArray();
    }

    /**
     * ToBase64 serializes a bitmap as Base64
     * @return string
     */
    public function toBase64(): string
    {
        return $this->bitmap->toBase64();
    }

    /**
     * ToBytes returns an array of bytes corresponding to what is written
     * when calling WriteTo
     * @return string
     */
    public function toBytes(): string
    {
        return $this->bitmap->toBytes();
    }

    /**
     * FromBase64 deserializes a bitmap from Base64
     * @param string $b64
     * @return int
     */
    public function fromBase64(string $b64): int
    {
        $b64 = base64_decode($b64);
        $this->bitmap->orInPlace($b64);
        return strlen($b64);
    }

    /**
     * FromBuffer creates a bitmap from its serialized version stored in buffer
     *
     * The format specification is available here:
     * https://github.com/RoaringBitmap/RoaringFormatSpec
     *
     * The provided byte array (buf) is expected to be a constant.
     * The function makes the best effort attempt not to copy data.
     * You should take care not to modify buff as it will
     * likely result in unexpected program behavior.
     *
     * Resulting bitmaps are effectively immutable in the following sense:
     * a copy-on-write marker is used so that when you modify the resulting
     * bitmap, copies of selected data (containers) are made.
     * You should *not* change the copy-on-write status of the resulting
     * bitmaps (SetCopyOnWrite).
     *
     * If buf becomes unavailable, then a bitmap created with
     * FromBuffer would be effectively broken. Furthermore, any
     * bitmap derived from this bitmap (e.g., via Or, And) might
     * also be broken. Thus, before making buf unavailable, you should
     * call CloneCopyOnWriteContainers on all such bitmaps.
     *
     * @param string $bytes
     * @return int
     */
    public function fromBuffer(string $bytes): int
    {
        $this->bitmap->orInPlace($bytes);
        return strlen($bytes);
    }

    /**
     * GetSizeInBytes estimates the memory usage of the Bitmap. Note that this
     * might differ slightly from the amount of bytes required for persistent storage
     * @return int
     */
    public function getSizeInBytes(): int
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * GetSerializedSizeInBytes computes the serialized size in bytes
     * of the Bitmap. It should correspond to the
     * number of bytes written when invoking WriteTo. You can expect
     * that this function is much cheaper computationally than WriteTo.
     * @return int
     */
    public function getSerializedSizeInBytes(): int
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Stats returns details on container type usage in a Statistics struct.
     * @return array
     */
    public function stats(): array
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * SetCopyOnWrite sets this bitmap to use copy-on-write so that copies are fast and memory conscious
     * if the parameter is true, otherwise we leave the default where hard copies are made
     * (copy-on-write requires extra care in a threaded context).
     * Calling SetCopyOnWrite(true) on a bitmap created with FromBuffer is unsafe.
     * @param bool $x
     * @return $this
     */
    public function setCopyOnWrite(bool $x): self
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * GetCopyOnWrite gets this bitmap's copy-on-write property
     * @return bool
     */
    public function getCopyOnWrite(): bool
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Clone creates a copy of the Bitmap
     */
    public function __clone()
    {
        $this->bitmap = clone $this->bitmap;
    }

    /**
     * CloneCopyOnWriteContainers clones all containers which have
     * needCopyOnWrite set to true.
     * This can be used to make sure it is safe to munmap a []byte
     * that the roaring array may still have a reference to, after
     * calling FromBuffer.
     * More generally this function is useful if you call FromBuffer
     * to construct a bitmap with a backing array buf
     * and then later discard the buf array. Note that you should call
     * CloneCopyOnWriteContainers on all bitmaps that were derived
     * from the 'FromBuffer' bitmap since they map have dependencies
     * on the buf array as well.
     * @return $this
     */
    public function cloneCopyOnWriteContainers(): self
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * HasRunCompression returns true if the bitmap benefits from run compression
     * @return bool
     */
    public function hasRunCompression(): bool
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * RunOptimize attempts to further compress the runs of consecutive values found in the bitmap
     * @return $this
     */
    public function runOptimize(): self
    {
        $this->bitmap->runOptimize();
        return $this;
    }

    /**
     * And computes the intersection between two bitmaps and stores the result in the current bitmap
     * @param Client $client
     */
    public function and(Client $client)
    {
        $this->bitmap->andInPlace($client->bitmap);
    }

    /**
     * @param string $bytes
     * @see and
     */
    public function andBuffer(string $bytes)
    {
        $this->bitmap->andInPlace($bytes);
    }

    /**
     * @param string $b64
     * @see and
     */
    public function andBase64(string $b64)
    {
        $this->bitmap->andInPlace($b64);
    }

    /**
     * AndAny provides a result equivalent to x1.And(FastOr(bitmaps)).
     * It's optimized to minimize allocations. It also might be faster than separate calls.
     * @param Client ...$clients
     */
    public function andAny(Client ...$clients)
    {
        $bitmaps = [];
        foreach ($clients as $client) {
            $bitmaps[] = $client->bitmap;
        }
        $this->bitmap->andAnyInPlace(...$bitmaps);
    }

    /**
     * @param string ...$bytes
     * @see andAny
     */
    public function andAnyBuffer(string ...$bytes)
    {
        $total = new Bitmap();
        foreach ($bytes as $byte) {
            $b = $this->bitmap->and($byte);
            $total->orInPlace($b);
        }
        $this->bitmap = $total;
    }

    /**
     * @param string ...$b64
     */
    public function andAnyBase64(string ...$b64)
    {
        $total = new Bitmap();
        foreach ($b64 as $byte) {
            $b = $this->bitmap->and($byte);
            $total->orInPlace($b);
        }
        $this->bitmap = $total;
    }

    /**
     * AndNot computes the difference between two bitmaps and stores the result in the current bitmap
     * @param Client $client
     */
    public function andNot(Client $client)
    {
        $this->bitmap->andNotInPlace($client->bitmap);
    }

    /**
     * @param string $bytes
     * @see andNot
     */
    public function andNotBuffer(string $bytes)
    {
        $this->bitmap->andNotInPlace($bytes);
    }

    /**
     * @param string $b64
     * @see andNot
     */
    public function andNotBase64(string $b64)
    {
        $this->bitmap->andNotInPlace($b64);
    }

    /**
     * @param string ...$bytes
     * @see andNot
     */
    public function andNotAnyBuffer(string ...$bytes)
    {
        $this->bitmap->andNotAnyInPlace(...$bytes);
    }

    /**
     * @param string ...$b64
     * @see andNot
     */
    public function andNotAnyBase64(string ...$b64)
    {
        foreach ($b64 as $v) {
            $this->bitmap->andNotInPlace($v);
        }
    }

    /**
     * Or computes the union between two bitmaps and stores the result in the current bitmap
     * @param Client $client
     */
    public function or(Client $client)
    {
        $this->bitmap->orInPlace($client->bitmap);
    }

    /**
     * @param string $bytes
     * @see or
     */
    public function orBuffer(string $bytes)
    {
        $this->bitmap->orInPlace($bytes);
    }

    /**
     * @param string $b64
     * @see or
     */
    public function orBase64(string $b64)
    {
        $this->bitmap->orInPlace($b64);
    }

    /**
     * @param string ...$bytes
     * @see or
     */
    public function orAnyBuffer(string ...$bytes)
    {
        $this->bitmap->orAnyInPlace(...$bytes);
    }

    /**
     * @param string ...$b64
     * @see or
     */
    public function orAnyBase64(string ...$b64)
    {
        foreach ($b64 as $v) {
            $this->bitmap->orInPlace($v);
        }
    }

    /**
     * Or computes any group bitmaps and stores the result in the current bitmap
     * @param array $groupBytes
     * @return self[]
     * @example ['group name1'=>[$bytes1, $bytes2, $bytes3], 'group name2'=>[$bytes1, $bytes2, $bytes3]]
     * @see or
     */
    public function orAnyGroupBuffer(array $groupBytes): array
    {
        if (count($groupBytes) == 0) {
            return [];
        }
        $result = [];
        foreach ($groupBytes as $group => $bytes) {
            $result[$group] = new Bitmap();
            foreach ($bytes as $v) {
                $result[$group]->orInPlace($v);
            }
            $this->bitmap->orInPlace($result[$group]);
        }
        return $result;
    }

    /**
     * Or computes any group bitmaps and stores the result in the current bitmap
     * @param array $groupB64
     * @return self[]
     * @example ['group name1'=>[$b64, $b64, $b64], 'group name2'=>[$b64, $b64, $b64]]
     * @see or
     */
    public function orAnyGroupBase64(array $groupB64): array
    {
        return $this->orAnyGroupBuffer($groupB64);
    }

    /**
     * Or computes any group bitmaps and stores the result in the current bitmap
     * @param array $groupBytes
     * @return int[]
     * @example ['group name1'=>[$bytes1, $bytes2, $bytes3], 'group name2'=>[$bytes1, $bytes2, $bytes3]]
     * @see or
     * @example ['total'=>'bitmap getCardinality', 'group name1'=>'bitmap getCardinality', 'group name2'=>'bitmap getCardinality']
     */
    public function orCardinalityAnyGroupBuffer(array $groupBytes): array
    {
        if (isset($groupBytes['total'])) {
            throw new InvalidArgumentException('disable setting key: total');
        }
        if (count($groupBytes) == 0) {
            return ['total' => 0];
        }
        $result = [];
        foreach ($groupBytes as $group => $bytes) {
            $result[$group] = new Bitmap();
            foreach ($bytes as $v) {
                $result[$group]->orInPlace($v);
            }
            $this->bitmap->orInPlace($result[$group]);
            $result[$group] = $result[$group]->getCardinality();
        }
        $result['total'] = $this->bitmap->getCardinality();
        return $result;
    }

    /**
     * Or computes any group bitmaps and stores the result in the current bitmap
     * @param array $groupB64
     * @return int[]
     * @example ['group name1'=>[$b64, $b64, $b64], 'group name2'=>[$b64, $b64, $b64]]
     * @see or
     * @example ['total'=>'bitmap getCardinality', 'group name1'=>'bitmap getCardinality', 'group name2'=>'bitmap getCardinality']
     */
    public function orCardinalityAnyGroupBase64(array $groupB64): array
    {
        return $this->orCardinalityAnyGroupBuffer($groupB64);
    }

    /**
     * Xor computes the symmetric difference between two bitmaps and stores the result in the current bitmap
     * @param Client $client
     */
    public function xOr(Client $client)
    {
        $this->bitmap->xorInPlace($client->bitmap);
    }

    /**
     * @param string $bytes
     * @see xOr
     */
    public function xOrBuffer(string $bytes)
    {
        $this->bitmap->xorInPlace($bytes);
    }

    /**
     * @param string $b64
     * @see xOr
     */
    public function xOrBase64(string $b64)
    {
        $this->bitmap->xorInPlace($b64);
    }

    /**
     * Intersects checks whether two bitmap intersects, bitmaps are not modified
     * @param Client $client
     * @return bool
     */
    public function intersects(Client $client): bool
    {
        return $this->bitmap->intersect($client->bitmap);
    }

    /**
     * @param string $bytes
     * @return bool
     * @see intersects
     */
    public function intersectsBuffer(string $bytes): bool
    {
        return $this->bitmap->intersect($bytes);
    }

    /**
     * @param string $b64
     * @return bool
     * @see intersects
     */
    public function intersectsBase64(string $b64): bool
    {
        return $this->bitmap->intersect($b64);
    }

    /**
     * Equals returns true if the two bitmaps contain the same integers
     * @param Client $client
     * @return bool
     */
    public function equals(Client $client): bool
    {
        return $this->bitmap->equals($client->bitmap);
    }

    /**
     * @param string $bytes
     * @return bool
     * @see equals
     */
    public function equalsBuffer(string $bytes): bool
    {
        return $this->bitmap->equals($bytes);
    }

    /**
     * @param string $b64
     * @return bool
     * @see equals
     */
    public function equalsBase64(string $b64): bool
    {
        return $this->bitmap->equals($b64);
    }

    /**
     * creates a new ManyIntIterable to iterate over the integers contained in the bitmap, in sorted order; the iterator becomes invalid if the bitmap is modified (e.g., with Add or Remove).
     * @param int $size
     * @return Generator
     */
    public function iterate(int $size = 100): Generator
    {
        if ($size <= 0) {
            $size = 100;
        }
        return $this->bitmap->iterate($size);
    }
}
