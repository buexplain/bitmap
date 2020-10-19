package objectPool

import (
	"buexplain/bitmap/connectionIDPool"
	"github.com/RoaringBitmap/roaring"
	"runtime"
	"sync"
)

type ConnectionID uint32

type ObjectID uint32

type ID struct {
	ConnectionID `json:"connectionID"`
	ObjectID     `json:"objectID"`
}

type items []*sync.Map

func (r items) getItem(connectionID ConnectionID) *sync.Map {
	return r[int(connectionID)%len(r)]
}

type Pool struct {
	gc    chan ConnectionID
	items items
}

func New() *Pool {
	length := runtime.NumCPU()
	tmp := &Pool{}
	tmp.items = make(items, 0, length)
	for i := 0; i < length; i++ {
		tmp.items = append(tmp.items, new(sync.Map))
	}
	tmp.gc = make(chan ConnectionID, 65536)
	go func() {
		for connectionID := range tmp.gc {
			item := tmp.items.getItem(connectionID)
			item.Delete(connectionID)
			connectionIDPool.Put(uint32(connectionID))
		}
	}()
	return tmp
}

//读写获取bitmap对象的池子，如果不存在则会创建一个
func (r *Pool) GetBitmapPool(connectionID ConnectionID) *sync.Map {
	item := r.items.getItem(connectionID)
	p := new(sync.Map)
	actual, loaded := item.LoadOrStore(connectionID, p)
	if loaded {
		return actual.(*sync.Map)
	}
	return p
}

//只读获取bitmap对象的池子
func (r *Pool) RGetBitmapPool(connectionID ConnectionID) *sync.Map {
	item := r.items.getItem(connectionID)
	if v, ok := item.Load(connectionID); ok {
		return v.(*sync.Map)
	}
	return nil
}

//获取一个bitmap对象
func (r *Pool) GetBitmap(id ID) *roaring.Bitmap {
	item := r.items.getItem(id.ConnectionID)
	if bitmapPool, ok := item.Load(id.ConnectionID); ok {
		bp := bitmapPool.(*sync.Map)
		if v, ok := bp.Load(id.ObjectID); ok {
			return v.(*roaring.Bitmap)
		}
	}
	return nil
}

//读取多个bitmap对象，并按入参的顺序写入返回结果中
func (r *Pool) GetBitmaps(ids []ID) []*roaring.Bitmap {
	result := make([]*roaring.Bitmap, 0, len(ids))
	for _, id := range ids {
		item := r.items.getItem(id.ConnectionID)
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

func (r *Pool) GC(connectionID ConnectionID) {
	r.gc <- connectionID
}
