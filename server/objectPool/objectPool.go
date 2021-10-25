package objectPool

import (
	"buexplain/bitmap/connectionIDPool"
	"buexplain/bitmap/identity"
	"github.com/RoaringBitmap/roaring"
	"runtime"
	"sync"
)

type shards []*sync.Map

func (r shards) get(connectionID identity.ConnectionID) *sync.Map {
	i := int(connectionID) % len(r)
	if i >= 0 && i < len(r) {
		return r[i]
	}
	return nil
}

type Pool struct {
	gc     chan identity.ConnectionID
	shards shards
}

func New() *Pool {
	length := runtime.NumCPU()
	tmp := &Pool{}
	tmp.shards = make(shards, 0, length)
	for i := 0; i < length; i++ {
		tmp.shards = append(tmp.shards, new(sync.Map))
	}
	tmp.gc = make(chan identity.ConnectionID, 65536)
	go func() {
		for connectionID := range tmp.gc {
			//获取连接所在的map
			item := tmp.shards.get(connectionID)
			if item != nil {
				//删除连接对应的所有bitmap对象
				item.Delete(connectionID)
				//将连接id返回给连接id池
				connectionIDPool.Put(connectionID)
			}
		}
	}()
	return tmp
}

// GetBitmapPool 读写获取bitmap对象的池子，如果不存在则会创建一个
func (r *Pool) GetBitmapPool(connectionID identity.ConnectionID) *sync.Map {
	shard := r.shards.get(connectionID)
	p := new(sync.Map)
	actual, loaded := shard.LoadOrStore(connectionID, p)
	if loaded {
		return actual.(*sync.Map)
	}
	return p
}

// RGetBitmapPool 只读获取bitmap对象的池子
func (r *Pool) RGetBitmapPool(connectionID identity.ConnectionID) *sync.Map {
	item := r.shards.get(connectionID)
	if v, ok := item.Load(connectionID); ok {
		return v.(*sync.Map)
	}
	return nil
}

// GetBitmap 获取一个bitmap对象
func (r *Pool) GetBitmap(id identity.ID) *roaring.Bitmap {
	item := r.shards.get(id.ConnectionID)
	if bitmapPool, ok := item.Load(id.ConnectionID); ok {
		bp := bitmapPool.(*sync.Map)
		if v, ok := bp.Load(id.ObjectID); ok {
			return v.(*roaring.Bitmap)
		}
	}
	return nil
}

// GetBitmaps 读取多个bitmap对象，并按入参的顺序写入返回结果中
func (r *Pool) GetBitmaps(ids []identity.ID) []*roaring.Bitmap {
	result := make([]*roaring.Bitmap, 0, len(ids))
	for _, id := range ids {
		item := r.shards.get(id.ConnectionID)
		if v, ok := item.Load(id.ConnectionID); ok {
			bitmapPool := v.(*sync.Map)
			if b, ok := bitmapPool.Load(id.ObjectID); ok {
				result = append(result, b.(*roaring.Bitmap))
			} else {
				result = append(result, nil)
			}
		} else {
			result = append(result, nil)
		}
	}
	return result
}

func (r *Pool) GC(connectionID identity.ConnectionID) {
	r.gc <- connectionID
}
