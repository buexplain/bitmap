package service

import (
	"buexplain/bitmap/objectIDPool"
	"buexplain/bitmap/objectPool"
	"errors"
	"github.com/RoaringBitmap/roaring"
)

var ErrNotFound = errors.New("not found bitmap object by id")
var ErrEmpty = errors.New("bitmap is empty")

var pool *objectPool.Pool

func init() {
	pool = objectPool.New()
}

func GC(connectionID uint32) {
	pool.GC(objectPool.ConnectionID(connectionID))
}

type OpIntPayload struct {
	ID    objectPool.ID `json:"id"`
	Value uint32        `json:"value"`
}

type OpManyIntPayload struct {
	ID    objectPool.ID `json:"id"`
	Value []uint32      `json:"value"`
}

type OpStringPayload struct {
	ID    objectPool.ID `json:"id"`
	Value string        `json:"value"`
}

type OpBytesPayload struct {
	ID    objectPool.ID `json:"id"`
	Value []byte        `json:"value"`
}

type OpManyBytesPayload struct {
	ID    objectPool.ID `json:"id"`
	Value [][]byte      `json:"value"`
}

type OpManyGroupBytesPayload struct {
	ID    objectPool.ID `json:"id"`
	Value map[string][][]byte `json:"value"`
}

type OpBoolPayload struct {
	ID    objectPool.ID `json:"id"`
	Value bool          `json:"value"`
}

type OpIDPayload struct {
	CurrentID objectPool.ID `json:"currentID"`
	TargetID  objectPool.ID `json:"targetID"`
}

type OpManyIDPayload struct {
	CurrentID objectPool.ID   `json:"currentID"`
	TargetID  []objectPool.ID `json:"targetID"`
}

type OpIteratePayload struct {
	ID    objectPool.ID `json:"id"`
	Value uint32          `json:"value"`
}

type Service struct{}

func (r *Service) Ping(msg string, s *string) error {
	if msg == "ping" {
		*s = "pong"
	}
	return nil
}

func (r *Service) new(connectionID objectPool.ConnectionID) (objectID objectPool.ObjectID, bitmap *roaring.Bitmap) {
	bitmapPool := pool.GetBitmapPool(connectionID)
	objectID = objectPool.ObjectID(objectIDPool.Get(uint32(connectionID)))
	bitmap = roaring.New()
	bitmapPool.Store(objectID, bitmap)
	return
}

func (r *Service) New(connectionID objectPool.ConnectionID, out *uint32) error {
	objectID, _ := r.new(connectionID)
	*out = uint32(objectID)
	return nil
}

func (r *Service) Destruct(id objectPool.ID, out *bool) error {
	if bitmapPool := pool.RGetBitmapPool(id.ConnectionID); bitmapPool != nil {
		bitmapPool.Delete(id.ObjectID)
	}
	*out = true
	return nil
}

func (r *Service) GetCardinality(id objectPool.ID, out *uint64) error {
	if b := pool.GetBitmap(id); b != nil {
		*out = b.GetCardinality()
		return nil
	}
	return ErrNotFound
}

func (r *Service) AndCardinality(payload OpIDPayload, out *uint64) error {
	bs := pool.GetBitmaps([]objectPool.ID{payload.CurrentID, payload.TargetID})
	if bs[0] != nil && bs[1] != nil {
		*out = bs[0].AndCardinality(bs[1])
		return nil
	}
	return ErrNotFound
}

func (r *Service) OrCardinality(payload OpIDPayload, out *uint64) error {
	bs := pool.GetBitmaps([]objectPool.ID{payload.CurrentID, payload.TargetID})
	if bs[0] != nil && bs[1] != nil {
		*out = bs[0].OrCardinality(bs[1])
		return nil
	}
	return ErrNotFound
}

func (r *Service) Add(payload OpIntPayload, out *bool) error {
	if b := pool.GetBitmap(payload.ID); b != nil {
		b.Add(payload.Value)
		*out = true
		return nil
	}
	return ErrNotFound
}

func (r *Service) CheckedAdd(payload OpIntPayload, out *bool) error {
	if b := pool.GetBitmap(payload.ID); b != nil {
		*out = b.CheckedAdd(payload.Value)
		return nil
	}
	return ErrNotFound
}

func (r *Service) AddMany(payload OpManyIntPayload, out *bool) error {
	if b := pool.GetBitmap(payload.ID); b != nil {
		b.AddMany(payload.Value)
		*out = true
		return nil
	}
	return ErrNotFound
}

func (r *Service) AddRange(payload OpManyIntPayload, out *bool) error {
	if b := pool.GetBitmap(payload.ID); b != nil && len(payload.Value) == 2 {
		b.AddRange(uint64(payload.Value[0]), uint64(payload.Value[1]))
		*out = true
		return nil
	}
	return ErrNotFound
}

func (r *Service) Rank(payload OpIntPayload, out *uint64) error {
	if b := pool.GetBitmap(payload.ID); b != nil {
		*out = b.Rank(payload.Value)
		return nil
	}
	return ErrNotFound
}

func (r *Service) Contains(payload OpIntPayload, out *bool) error {
	if b := pool.GetBitmap(payload.ID); b != nil {
		*out = b.Contains(payload.Value)
		return nil
	}
	return ErrNotFound
}

func (r *Service) Remove(payload OpIntPayload, out *bool) error {
	if b := pool.GetBitmap(payload.ID); b != nil {
		b.Remove(payload.Value)
		*out = true
		return nil
	}
	return ErrNotFound
}

func (r *Service) CheckedRemove(payload OpIntPayload, out *bool) error {
	if b := pool.GetBitmap(payload.ID); b != nil {
		*out = b.CheckedRemove(payload.Value)
		return nil
	}
	return ErrNotFound
}

func (r *Service) RemoveMany(payload OpManyIntPayload, out *bool) error {
	if b := pool.GetBitmap(payload.ID); b != nil {
		for _, v := range payload.Value {
			b.Remove(v)
		}
		*out = true
		return nil
	}
	return ErrNotFound
}

func (r *Service) RemoveRange(payload OpManyIntPayload, out *bool) error {
	if b := pool.GetBitmap(payload.ID); b != nil && len(payload.Value) == 2 {
		b.RemoveRange(uint64(payload.Value[0]), uint64(payload.Value[1]))
		*out = true
		return nil
	}
	return ErrNotFound
}

func (r *Service) Flip(payload OpManyIntPayload, out *bool) error {
	if b := pool.GetBitmap(payload.ID); b != nil && len(payload.Value) == 2 {
		b.Flip(uint64(payload.Value[0]), uint64(payload.Value[1]))
		*out = true
		return nil
	}
	return ErrNotFound
}

func (r *Service) Clear(id objectPool.ID, out *bool) error {
	if b := pool.GetBitmap(id); b != nil {
		b.Clear()
		*out = true
		return nil
	}
	return ErrNotFound
}

func (r *Service) IsEmpty(id objectPool.ID, out *bool) error {
	if b := pool.GetBitmap(id); b != nil {
		*out = b.IsEmpty()
		return nil
	}
	return ErrNotFound
}

func (r *Service) Select(payload OpIntPayload, out *int64) error {
	if b := pool.GetBitmap(payload.ID); b != nil {
		if result, err := b.Select(payload.Value); err == nil {
			*out = int64(result)
		} else {
			*out = -1
		}
		return nil
	}
	*out = -1
	return ErrNotFound
}

func (r *Service) Minimum(id objectPool.ID, out *uint32) error {
	if b := pool.GetBitmap(id); b != nil {
		if b.IsEmpty() {
			return ErrEmpty
		}
		*out = b.Minimum()
		return nil
	}
	*out = 0
	return ErrNotFound
}

func (r *Service) Maximum(id objectPool.ID, out *uint32) error {
	if b := pool.GetBitmap(id); b != nil {
		if b.IsEmpty() {
			return ErrEmpty
		}
		*out = b.Maximum()
		return nil
	}
	*out = 0
	return ErrNotFound
}

func (r *Service) String(id objectPool.ID, out *string) error {
	if b := pool.GetBitmap(id); b != nil {
		*out = b.String()
		return nil
	}
	return ErrNotFound
}

func (r *Service) ToArray(id objectPool.ID, out *[]uint32) error {
	if b := pool.GetBitmap(id); b != nil {
		*out = b.ToArray()
		return nil
	}
	return ErrNotFound
}

func (r *Service) ToBase64(id objectPool.ID, out *string) error {
	if b := pool.GetBitmap(id); b != nil {
		result, err := b.ToBase64()
		if err == nil {
			*out = result
			return nil
		}
		return err
	}
	return ErrNotFound
}

func (r *Service) ToBytes(id objectPool.ID, out *[]byte) error {
	if b := pool.GetBitmap(id); b != nil {
		result, err := b.ToBytes()
		if err == nil {
			*out = result
			return nil
		}
		return err
	}
	return ErrNotFound
}

func (r *Service) FromBase64(payload OpStringPayload, out *int64) error {
	if b := pool.GetBitmap(payload.ID); b != nil {
		n, err := b.FromBase64(payload.Value)
		if err == nil {
			*out = n
			return nil
		}
		return err
	}
	return ErrNotFound
}

func (r *Service) FromBuffer(payload OpBytesPayload, out *int64) error {
	if b := pool.GetBitmap(payload.ID); b != nil {
		n, err := b.FromBuffer(payload.Value)
		if err == nil {
			*out = n
			return nil
		}
		return err
	}
	return ErrNotFound
}

func (r *Service) GetSizeInBytes(id objectPool.ID, out *uint64) error {
	if b := pool.GetBitmap(id); b != nil {
		*out = b.GetSizeInBytes()
		return nil
	}
	return ErrNotFound
}

func (r *Service) GetSerializedSizeInBytes(id objectPool.ID, out *uint64) error {
	if b := pool.GetBitmap(id); b != nil {
		*out = b.GetSerializedSizeInBytes()
		return nil
	}
	return ErrNotFound
}

func (r *Service) Stats(id objectPool.ID, out *roaring.Statistics) error {
	if b := pool.GetBitmap(id); b != nil {
		*out = b.Stats()
		return nil
	}
	return ErrNotFound
}

func (r *Service) SetCopyOnWrite(payload OpBoolPayload, out *bool) error {
	if b := pool.GetBitmap(payload.ID); b != nil {
		b.SetCopyOnWrite(payload.Value)
		*out = true
		return nil
	}
	return ErrNotFound
}

func (r *Service) GetCopyOnWrite(id objectPool.ID, out *bool) error {
	if b := pool.GetBitmap(id); b != nil {
		*out = b.GetCopyOnWrite()
		return nil
	}
	return ErrNotFound
}

func (r *Service) Clone(id objectPool.ID, out *objectPool.ID) error {
	bitmapPool := pool.RGetBitmapPool(id.ConnectionID)
	if bitmapPool == nil {
		return ErrEmpty
	}
	v, ok := bitmapPool.Load(id.ObjectID)
	if !ok {
		return ErrEmpty
	}
	b := v.(*roaring.Bitmap)
	if b == nil {
		return ErrEmpty
	}
	objectID := objectPool.ObjectID(objectIDPool.Get(uint32(id.ConnectionID)))
	bitmapPool.Store(objectID, b.Clone())
	*out = objectPool.ID{ConnectionID: id.ConnectionID, ObjectID: objectID}
	return nil
}

func (r *Service) CloneCopyOnWriteContainers(id objectPool.ID, out *bool) error {
	if b := pool.GetBitmap(id); b != nil {
		b.CloneCopyOnWriteContainers()
		*out = true
		return nil
	}
	return ErrNotFound
}

func (r *Service) HasRunCompression(id objectPool.ID, out *bool) error {
	if b := pool.GetBitmap(id); b != nil {
		*out = b.HasRunCompression()
		return nil
	}
	return ErrNotFound
}

func (r *Service) RunOptimize(id objectPool.ID, out *bool) error {
	if b := pool.GetBitmap(id); b != nil {
		b.RunOptimize()
		*out = true
		return nil
	}
	return ErrNotFound
}

func (r *Service) And(payload OpIDPayload, out *bool) error {
	bs := pool.GetBitmaps([]objectPool.ID{payload.CurrentID, payload.TargetID})
	if bs[0] != nil && bs[1] != nil {
		bs[0].And(bs[1])
		*out = true
		return nil
	}
	return ErrNotFound
}

func (r *Service) AndBuffer(payload OpBytesPayload, out *bool) error {
	if b := pool.GetBitmap(payload.ID); b != nil {
		tmp := roaring.New()
		if _, err := tmp.FromBuffer(payload.Value); err == nil {
			b.And(tmp)
			*out = true
			return nil
		} else {
			return err
		}
	}
	return ErrNotFound
}

func (r *Service) AndAny(payload OpManyIDPayload, out *bool) error {
	ids := append([]objectPool.ID{payload.CurrentID}, payload.TargetID...)
	bs := pool.GetBitmaps(ids)
	if len(ids) < 2 {
		return ErrNotFound
	}
	bs[0].AndAny(bs[1:]...)
	*out = true
	return nil
}

func (r *Service) AndAnyBuffer(payload OpManyBytesPayload, out *bool) error {
	b := pool.GetBitmap(payload.ID)
	if b == nil {
		return ErrNotFound
	}
	bitmaps := make([]*roaring.Bitmap, 0, len(payload.Value))
	for _, v := range payload.Value {
		tmp := roaring.New()
		if _, err := tmp.FromBuffer(v); err == nil {
			bitmaps = append(bitmaps, tmp)
		} else {
			return err
		}
	}
	if len(bitmaps) == 0 {
		return ErrNotFound
	}
	b.AndAny(bitmaps...)
	*out = true
	return nil
}

func (r *Service) AndNot(payload OpIDPayload, out *bool) error {
	bs := pool.GetBitmaps([]objectPool.ID{payload.CurrentID, payload.TargetID})
	if bs[0] != nil && bs[1] != nil {
		bs[0].AndNot(bs[1])
		*out = true
		return nil
	}
	return ErrNotFound
}

func (r *Service) AndNotBuffer(payload OpBytesPayload, out *bool) error {
	if b := pool.GetBitmap(payload.ID); b != nil {
		tmp := roaring.New()
		if _, err := tmp.FromBuffer(payload.Value); err == nil {
			b.AndNot(tmp)
			*out = true
			return nil
		} else {
			return err
		}
	}
	return ErrNotFound
}

func (r *Service) AndNotAnyBuffer(payload OpManyBytesPayload, out *bool) error {
	if b := pool.GetBitmap(payload.ID); b != nil {
		tmp := roaring.New()
		for _, v := range payload.Value {
			if _, err := tmp.FromBuffer(v); err == nil {
				b.AndNot(tmp)
				tmp.Clear()
			} else {
				return err
			}
		}
		*out = true
		return nil
	}
	return ErrNotFound
}

func (r *Service) Or(payload OpIDPayload, out *bool) error {
	bs := pool.GetBitmaps([]objectPool.ID{payload.CurrentID, payload.TargetID})
	if bs[0] != nil && bs[1] != nil {
		bs[0].Or(bs[1])
		*out = true
		return nil
	}
	return ErrNotFound
}

func (r *Service) OrBuffer(payload OpBytesPayload, out *bool) error {
	if b := pool.GetBitmap(payload.ID); b != nil {
		tmp := roaring.New()
		if _, err := tmp.FromBuffer(payload.Value); err == nil {
			b.Or(tmp)
			*out = true
			return nil
		} else {
			return err
		}
	}
	return ErrNotFound
}

func (r *Service) OrAnyBuffer(payload OpManyBytesPayload, out *bool) error {
	if b := pool.GetBitmap(payload.ID); b != nil {
		tmp := roaring.New()
		for _, v := range payload.Value {
			if _, err := tmp.FromBuffer(v); err == nil {
				b.Or(tmp)
				tmp.Clear()
			} else {
				return err
			}
		}
		*out = true
		return nil
	}
	return ErrNotFound
}

func (r *Service) OrAnyGroupBuffer(payload OpManyGroupBytesPayload, out *map[string]objectPool.ID) error {
	if b := pool.GetBitmap(payload.ID); b != nil {
		result := make(map[string]objectPool.ID)
		tmp := roaring.New()
		for k, g := range payload.Value {
			objectID, bitmap := r.new(payload.ID.ConnectionID)
			id := objectPool.ID{ConnectionID:payload.ID.ConnectionID, ObjectID:objectID}
			result[k] = id
			tmp.Clear()
			for _, v := range g {
				if _, err := tmp.FromBuffer(v); err == nil {
					bitmap.Or(tmp)
					tmp.Clear()
				} else {
					return err
				}
			}
			b.Or(bitmap)
		}
		*out = result
		return nil
	}
	return ErrNotFound
}

func (r *Service) OrCardinalityAnyGroupBuffer(payload OpManyGroupBytesPayload, out *map[string]uint64) error {
	if b := pool.GetBitmap(payload.ID); b != nil {
		result := make(map[string]uint64)
		tmp := roaring.New()
		bitmap := roaring.New()
		if _, ok := payload.Value["total"]; ok {
			return errors.New("invalid argument: total")
		}
		for k, g := range payload.Value {
			tmp.Clear()
			bitmap.Clear()
			for _, v := range g {
				if _, err := tmp.FromBuffer(v); err == nil {
					bitmap.Or(tmp)
					tmp.Clear()
				} else {
					return err
				}
			}
			b.Or(bitmap)
			result[k] = bitmap.GetCardinality()
		}
		//这里写死了这个key，客户端不要占用，否则报错
		result["total"] = b.GetCardinality()
		*out = result
		return nil
	}
	return ErrNotFound
}

func (r *Service) Xor(payload OpIDPayload, out *bool) error {
	bs := pool.GetBitmaps([]objectPool.ID{payload.CurrentID, payload.TargetID})
	if bs[0] != nil && bs[1] != nil {
		bs[0].Xor(bs[1])
		*out = true
		return nil
	}
	return ErrNotFound
}

func (r *Service) XorBuffer(payload OpBytesPayload, out *bool) error {
	if b := pool.GetBitmap(payload.ID); b != nil {
		tmp := roaring.New()
		if _, err := tmp.FromBuffer(payload.Value); err == nil {
			b.Xor(tmp)
			*out = true
			return nil
		} else {
			return err
		}
	}
	return ErrNotFound
}

func (r *Service) Intersects(payload OpIDPayload, out *bool) error {
	bs := pool.GetBitmaps([]objectPool.ID{payload.CurrentID, payload.TargetID})
	if bs[0] != nil && bs[1] != nil {
		*out = bs[0].Intersects(bs[1])
		return nil
	}
	return ErrNotFound
}

func (r *Service) IntersectsBuffer(payload OpBytesPayload, out *bool) error {
	if b := pool.GetBitmap(payload.ID); b != nil {
		tmp := roaring.New()
		if _, err := tmp.FromBuffer(payload.Value); err == nil {
			*out = b.Intersects(tmp)
			return nil
		} else {
			return err
		}
	}
	return ErrNotFound
}

func (r *Service) Equals(payload OpIDPayload, out *bool) error {
	bs := pool.GetBitmaps([]objectPool.ID{payload.CurrentID, payload.TargetID})
	if bs[0] != nil && bs[1] != nil {
		*out = bs[0].Equals(bs[1])
		return nil
	}
	return ErrNotFound
}

func (r *Service) EqualsBuffer(payload OpBytesPayload, out *bool) error {
	if b := pool.GetBitmap(payload.ID); b != nil {
		tmp := roaring.New()
		if _, err := tmp.FromBuffer(payload.Value); err == nil {
			*out = b.Equals(tmp)
			return nil
		} else {
			return err
		}
	}
	return ErrNotFound
}

func (r *Service) Iterate(payload OpIteratePayload, out *[]uint32) error {
	b := pool.GetBitmap(payload.ID)
	if b == nil {
		out = new([]uint32)
		return nil
	}
	iter := b.ManyIterator()
	buf := make([]uint32, payload.Value)
	buf = buf[0:iter.NextMany(buf)]
	if l := len(buf); l > 0 {
		b.RemoveRange(uint64(buf[0]), uint64(buf[l-1])+1)
	}
	*out = buf
	return nil
}