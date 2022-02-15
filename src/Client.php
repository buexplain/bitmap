<?php

declare(strict_types=1);

namespace BitMap;

use InvalidArgumentException;
use ErrorException;
use Spiral\Goridge\Exceptions\ServiceException;

/**
 * Class Client
 * @see https://github.com/spiral/goridge
 * @see https://github.com/RoaringBitmap/roaring
 * @package BitMap
 */
class Client
{
    /**
     * @var RPCInterface
     */
    protected $rpc;

    /**
     * 实例id
     * @var array|int[]
     */
    protected $id = [
        'connectionID' => 0,
        'objectID' => 0,
    ];

    public function getID(): array
    {
        return $this->id;
    }

    public function __construct(RPCInterface $rpc, array $id = [])
    {
        $this->rpc = $rpc;
        if (empty($id)) {
            $id = $rpc->getID();
        }
        $this->id = $id;
    }

    /**
     * If not destroyed, memory will leak
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function __destruct()
    {
        $this->rpc->call('Service.Destruct', $this->id);
        $this->rpc = null;
    }

    /**
     * @return bool
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function ping(): bool
    {
        return $this->rpc->call('Service.Ping', 'ping') == 'pong';
    }

    /**
     * GetCardinality returns the number of integers contained in the bitmap
     * @return int
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function getCardinality(): int
    {
        return $this->rpc->call('Service.GetCardinality', $this->id);
    }

    /**
     * AndCardinality returns the cardinality of the intersection between two bitmaps, bitmaps are not modified
     * @param Client $client
     * @return int
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function andCardinality(Client $client): int
    {
        return $this->rpc->call('Service.AndCardinality', ['currentID' => $this->id, 'targetID' => $client->getID()]);
    }

    /**
     * OrCardinality  returns the cardinality of the union between two bitmaps, bitmaps are not modified
     * @param Client $client
     * @return int
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function orCardinality(Client $client): int
    {
        return $this->rpc->call('Service.OrCardinality', ['currentID' => $this->id, 'targetID' => $client->getID()]);
    }

    /**
     * Add the uint32 x to the bitmap
     * @param int $x
     * @return $this
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function add(int $x): self
    {
        if ($x < 0) {
            throw new InvalidArgumentException('param has to be uint32');
        }
        $this->rpc->call('Service.Add', ['id' => $this->id, 'value' => $x]);
        return $this;
    }

    /**
     * CheckedAdd adds the integer x to the bitmap and return true  if it was added (false if the integer was already present)
     * @param int $x
     * @return bool
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function checkedAdd(int $x): bool
    {
        if ($x < 0) {
            throw new InvalidArgumentException('param has to be uint32');
        }
        return $this->rpc->call('Service.CheckedAdd', ['id' => $this->id, 'value' => $x]);
    }

    /**
     * AddMany add all of the values in dat
     * @param int[] $x
     * @return $this
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function addMany(array $x): self
    {
        if (count($x) == 0) {
            return $this;
        }
        $this->rpc->call('Service.AddMany', ['id' => $this->id, 'value' => $x]);
        return $this;
    }

    /**
     * AddRange adds the integers in [rangeStart, rangeEnd) to the bitmap.
     * The function uses 64-bit parameters even though a Bitmap stores 32-bit values because it is allowed and meaningful to use [0,uint64(0x100000000)) as a range
     * while uint64(0x100000000) cannot be represented as a 32-bit value.
     * @param int $rangeStart
     * @param int $rangeEnd
     * @return $this
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function addRange(int $rangeStart, int $rangeEnd): self
    {
        if ($rangeStart < 0 || $rangeEnd < 0) {
            throw new InvalidArgumentException('param has to be uint32');
        }
        $this->rpc->call('Service.AddRange', ['id' => $this->id, 'value' => [$rangeStart, $rangeEnd]]);
        return $this;
    }

    /**
     * Rank returns the number of integers that are smaller or equal to x (Rank(infinity) would be GetCardinality()).
     * If you pass the smallest value, you get the value 1. If you pass a value that is smaller than the smallest
     * value, you get 0. Note that this function differs in convention from the Select function since it
     * return 1 and not 0 on the smallest value.
     * @param int $x
     * @return int
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function rank(int $x): int
    {
        if ($x < 0) {
            throw new InvalidArgumentException('param has to be uint32');
        }
        return $this->rpc->call('Service.Rank', ['id' => $this->id, 'value' => $x]);
    }

    /**
     * Contains returns true if the integer is contained in the bitmap
     * @param int $x
     * @return bool
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function contains(int $x): bool
    {
        if ($x < 0) {
            throw new InvalidArgumentException('param has to be uint32');
        }
        return $this->rpc->call('Service.Contains', ['id' => $this->id, 'value' => $x]);
    }

    /**
     * Remove the integer x from the bitmap
     * @param int $x
     * @return $this
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function remove(int $x): self
    {
        if ($x < 0) {
            throw new InvalidArgumentException('param has to be uint32');
        }
        $this->rpc->call('Service.Remove', ['id' => $this->id, 'value' => $x]);
        return $this;
    }

    /**
     * CheckedRemove removes the integer x from the bitmap and return true if the integer was effectively remove (and false if the integer was not present)
     * @param int $x
     * @return bool
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function checkedRemove(int $x): bool
    {
        if ($x < 0) {
            throw new InvalidArgumentException('param has to be uint32');
        }
        return $this->rpc->call('Service.CheckedRemove', ['id' => $this->id, 'value' => $x]);
    }

    /**
     * RemoveMany remove all of the values in dat
     * @param int[] $x
     * @return $this
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function removeMany(array $x): self
    {
        $this->rpc->call('Service.RemoveMany', ['id' => $this->id, 'value' => $x]);
        return $this;
    }

    /**
     *  RemoveRange removes the integers in [rangeStart, rangeEnd) from the bitmap.
     *  The function uses 64-bit parameters even though a Bitmap stores 32-bit values because it is allowed and meaningful to use [0,uint64(0x100000000)) as a range
     *  while uint64(0x100000000) cannot be represented as a 32-bit value.
     * @param int $rangeStart
     * @param int $rangeEnd
     * @return $this
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function removeRange(int $rangeStart, int $rangeEnd): self
    {
        if ($rangeStart < 0 || $rangeEnd < 0) {
            throw new InvalidArgumentException('param has to be uint32');
        }
        $this->rpc->call('Service.RemoveRange', ['id' => $this->id, 'value' => [$rangeStart, $rangeEnd]]);
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
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function flip(int $rangeStart, int $rangeEnd): self
    {
        if ($rangeStart < 0 || $rangeEnd < 0) {
            throw new InvalidArgumentException('param has to be uint32');
        }
        $this->rpc->call('Service.Flip', ['id' => $this->id, 'value' => [$rangeStart, $rangeEnd]]);
        return $this;
    }

    /**
     *  Clear resets the Bitmap to be logically empty, but may retain
     *  some memory allocations that may speed up future operations
     * @return $this
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function clear(): self
    {
        $this->rpc->call('Service.Clear', $this->id);
        return $this;
    }

    /**
     * @return bool
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function isEmpty(): bool
    {
        return $this->rpc->call('Service.IsEmpty', $this->id);
    }

    /**
     * Select returns the xth integer in the bitmap. If you pass 0, you get
     * the smallest element. Note that this function differs in convention from
     * the Rank function which returns 1 on the smallest value.
     * If overflow, you get -1.
     * @param int $position
     * @return int
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function select(int $position): int
    {
        if ($position < 0) {
            throw new InvalidArgumentException('param has to be uint32');
        }
        return $this->rpc->call('Service.Select', ['id' => $this->id, 'value' => $position]);
    }

    /**
     * Minimum get the smallest value stored in this roaring bitmap, assumes that it is not empty
     * @return int
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function minimum(): int
    {
        return $this->rpc->call('Service.Minimum', $this->id);
    }

    /**
     * Maximum get the largest value stored in this roaring bitmap, assumes that it is not empty
     * @return int
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function maximum(): int
    {
        return $this->rpc->call('Service.Maximum', $this->id);
    }

    /**
     * String creates a string representation of the Bitmap
     * @return string
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function string(): string
    {
        return $this->rpc->call('Service.String', $this->id);
    }

    /**
     * ToArray creates a new slice containing all of the integers stored in the Bitmap in sorted order
     * @return int[]
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function toArray(): array
    {
        return $this->rpc->call('Service.ToArray', $this->id);
    }

    /**
     * ToBase64 serializes a bitmap as Base64
     * @return string
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function toBase64(): string
    {
        return $this->rpc->call('Service.ToBase64', $this->id);
    }

    /**
     * ToBytes returns an array of bytes corresponding to what is written
     * when calling WriteTo
     * @return string
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function toBytes(): string
    {
        return $this->rpc->call('Service.ToBytes', $this->id);
    }

    /**
     * FromBase64 deserializes a bitmap from Base64
     * @param string $b64
     * @return int
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function fromBase64(string $b64): int
    {
        return $this->rpc->call('Service.FromBase64', ['id' => $this->id, 'value' => $b64]);
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
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function fromBuffer(string $bytes): int
    {
        return $this->rpc->call('Service.FromBuffer', ['id' => $this->id, 'value' => base64_encode($bytes)]);
    }

    /**
     * GetSizeInBytes estimates the memory usage of the Bitmap. Note that this
     * might differ slightly from the amount of bytes required for persistent storage
     * @return int
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function getSizeInBytes(): int
    {
        return $this->rpc->call('Service.GetSizeInBytes', $this->id);
    }

    /**
     * GetSerializedSizeInBytes computes the serialized size in bytes
     * of the Bitmap. It should correspond to the
     * number of bytes written when invoking WriteTo. You can expect
     * that this function is much cheaper computationally than WriteTo.
     * @return int
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function getSerializedSizeInBytes(): int
    {
        return $this->rpc->call('Service.GetSerializedSizeInBytes', $this->id);
    }

    /**
     * Stats returns details on container type usage in a Statistics struct.
     * @return array
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function stats(): array
    {
        return $this->rpc->call('Service.Stats', $this->id);
    }

    /**
     * SetCopyOnWrite sets this bitmap to use copy-on-write so that copies are fast and memory conscious
     * if the parameter is true, otherwise we leave the default where hard copies are made
     * (copy-on-write requires extra care in a threaded context).
     * Calling SetCopyOnWrite(true) on a bitmap created with FromBuffer is unsafe.
     * @param bool $x
     * @return $this
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function setCopyOnWrite(bool $x): self
    {
        $this->rpc->call('Service.SetCopyOnWrite', ['id' => $this->id, 'value' => $x]);
        return $this;
    }

    /**
     * GetCopyOnWrite gets this bitmap's copy-on-write property
     * @return bool
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function getCopyOnWrite(): bool
    {
        return $this->rpc->call('Service.GetCopyOnWrite', $this->id);
    }

    /**
     * Clone creates a copy of the Bitmap
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function __clone()
    {
        $this->id = $this->rpc->call('Service.Clone', $this->id);
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
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function cloneCopyOnWriteContainers(): self
    {
        $this->rpc->call('Service.CloneCopyOnWriteContainers', $this->id);
        return $this;
    }

    /**
     * HasRunCompression returns true if the bitmap benefits from run compression
     * @return bool
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function hasRunCompression(): bool
    {
        return $this->rpc->call('Service.HasRunCompression', $this->id);
    }

    /**
     * RunOptimize attempts to further compress the runs of consecutive values found in the bitmap
     * @return $this
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function runOptimize(): self
    {
        $this->rpc->call('Service.RunOptimize', $this->id);
        return $this;
    }

    /**
     * And computes the intersection between two bitmaps and stores the result in the current bitmap
     * @param Client $client
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function and(Client $client)
    {
        $this->rpc->call('Service.And', ['currentID' => $this->id, 'targetID' => $client->getID()]);
    }

    /**
     * @param string $bytes
     * @throws ReconnectException|ServiceException|ErrorException
     * @see and
     */
    public function andBuffer(string $bytes)
    {
        $this->rpc->call('Service.AndBuffer', ['id' => $this->id, 'value' => base64_encode($bytes)]);
    }

    /**
     * AndAny provides a result equivalent to x1.And(FastOr(bitmaps)).
     * It's optimized to minimize allocations. It also might be faster than separate calls.
     * @param Client ...$clients
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function andAny(Client ...$clients)
    {
        $ids = [];
        foreach ($clients as $client) {
            $ids[] = $client->getID();
        }
        if (count($ids) > 0) {
            $this->rpc->call('Service.AndAny', ['currentID' => $this->id, 'targetID' => $ids]);
        }
    }

    /**
     * @param string ...$bytes
     * @throws ReconnectException|ServiceException|ErrorException
     * @see andAny
     */
    public function andAnyBuffer(string ...$bytes)
    {
        if (empty($bytes)) {
            return;
        }
        foreach ($bytes as &$v) {
            $v = base64_encode($v);
        }
        $this->rpc->call('Service.AndAnyBuffer', ['id' => $this->id, 'value' => $bytes]);
    }

    /**
     * AndNot computes the difference between two bitmaps and stores the result in the current bitmap
     * @param Client $client
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function andNot(Client $client)
    {
        $this->rpc->call('Service.AndNot', ['currentID' => $this->id, 'targetID' => $client->getID()]);
    }

    /**
     * @param string $bytes
     * @throws ReconnectException|ServiceException|ErrorException
     * @see andNot
     */
    public function andNotBuffer(string $bytes)
    {
        $this->rpc->call('Service.AndNotBuffer', ['id' => $this->id, 'value' => base64_encode($bytes)]);
    }

    /**
     * @param string ...$bytes
     * @throws ReconnectException|ServiceException|ErrorException
     * @see andNot
     */
    public function andNotAnyBuffer(string ...$bytes)
    {
        if (empty($bytes)) {
            return;
        }
        foreach ($bytes as &$v) {
            $v = base64_encode($v);
        }
        $this->rpc->call('Service.AndNotAnyBuffer', ['id' => $this->id, 'value' => $bytes]);
    }

    /**
     * Or computes the union between two bitmaps and stores the result in the current bitmap
     * @param Client $client
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function or(Client $client)
    {
        $this->rpc->call('Service.Or', ['currentID' => $this->id, 'targetID' => $client->getID()]);
    }

    /**
     * @param string $bytes
     * @throws ReconnectException|ServiceException|ErrorException
     * @see or
     */
    public function orBuffer(string $bytes)
    {
        $this->rpc->call('Service.OrBuffer', ['id' => $this->id, 'value' => base64_encode($bytes)]);
    }

    /**
     * @param string ...$bytes
     * @throws ReconnectException|ServiceException|ErrorException
     * @see or
     */
    public function orAnyBuffer(string ...$bytes)
    {
        if (empty($bytes)) {
            return;
        }
        foreach ($bytes as &$v) {
            $v = base64_encode($v);
        }
        $this->rpc->call('Service.OrAnyBuffer', ['id' => $this->id, 'value' => $bytes]);
    }

    /**
     * Or computes the any group bitmaps and stores the result in the current bitmap
     * @param array $groupBytes
     * @return self[]
     * @throws ReconnectException|ServiceException|ErrorException
     * @example ['group name1'=>[$bytes1, $bytes2, $bytes3], 'group name2'=>[$bytes1, $bytes2, $bytes3]]
     * @see or
     */
    public function orAnyGroupBuffer(array $groupBytes): array
    {
        if (count($groupBytes) == 0) {
            return [];
        }
        foreach ($groupBytes as $group => $bytes) {
            foreach ($bytes as &$v) {
                $v = base64_encode($v);
            }
            $groupBytes[(string)$group] = $bytes;
        }
        $data = $this->rpc->call('Service.OrAnyGroupBuffer', ['id' => $this->id, 'value' => $groupBytes]);
        $result = [];
        foreach ($data as $group => $id) {
            $result[$group] = new Client($this->rpc, $id);
        }
        return $result;
    }

    /**
     * Or computes the any group bitmaps and stores the result in the current bitmap
     * @param array $groupBytes
     * @return int[]
     * @throws ReconnectException|ServiceException|ErrorException
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
        foreach ($groupBytes as $group => $bytes) {
            foreach ($bytes as &$v) {
                $v = base64_encode($v);
            }
            $groupBytes[(string)$group] = $bytes;
        }
        return $this->rpc->call('Service.OrCardinalityAnyGroupBuffer', ['id' => $this->id, 'value' => $groupBytes]);
    }

    /**
     * Xor computes the symmetric difference between two bitmaps and stores the result in the current bitmap
     * @param Client $client
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function xOr(Client $client)
    {
        $this->rpc->call('Service.Xor', ['currentID' => $this->id, 'targetID' => $client->getID()]);
    }

    /**
     * @param string $bytes
     * @throws ReconnectException|ServiceException|ErrorException
     * @see xOr
     */
    public function xOrBuffer(string $bytes)
    {
        $this->rpc->call('Service.XorBuffer', ['id' => $this->id, 'value' => base64_encode($bytes)]);
    }

    /**
     * Intersects checks whether two bitmap intersects, bitmaps are not modified
     * @param Client $client
     * @return bool
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function intersects(Client $client): bool
    {
        return $this->rpc->call('Service.Intersects', ['currentID' => $this->id, 'targetID' => $client->getID()]);
    }

    /**
     * @param string $bytes
     * @return bool
     * @throws ReconnectException|ServiceException|ErrorException
     * @see intersects
     */
    public function intersectsBuffer(string $bytes): bool
    {
        return $this->rpc->call('Service.IntersectsBuffer', ['id' => $this->id, 'value' => base64_encode($bytes)]);
    }

    /**
     * Equals returns true if the two bitmaps contain the same integers
     * @param Client $client
     * @return bool
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function equals(Client $client): bool
    {
        return $this->rpc->call('Service.Equals', ['currentID' => $this->id, 'targetID' => $client->getID()]);
    }

    /**
     * @param string $bytes
     * @return bool
     * @throws ReconnectException|ServiceException|ErrorException
     * @see equals
     */
    public function equalsBuffer(string $bytes): bool
    {
        return $this->rpc->call('Service.EqualsBuffer', ['id' => $this->id, 'value' => base64_encode($bytes)]);
    }

    /**
     * creates a new ManyIntIterable to iterate over the integers contained in the bitmap, in sorted order; the iterator becomes invalid if the bitmap is modified (e.g., with Add or Remove).
     * The iteration with side effects, Because each iteration removes the element.
     * @param int $size
     * @return int[]
     * @throws ReconnectException|ServiceException|ErrorException
     */
    public function iterate(int $size = 100): array
    {
        if ($size <= 0) {
            $size = 100;
        }
        return $this->rpc->call('Service.Iterate', ['id' => $this->id, 'value' => $size]);
    }
}
