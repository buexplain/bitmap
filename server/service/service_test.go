package service_test

import (
	"buexplain/bitmap/objectPool"
	"buexplain/bitmap/service"
	"testing"
)

func TestIterate(t *testing.T)  {
	id := objectPool.ID{}
	id.ConnectionID = 1
	s := new(service.Service)
	var objectID uint32
	if err := s.New(id.ConnectionID, &objectID);err != nil {
		t.Error("创建bitmap失败")
	}
	id.ObjectID = objectPool.ObjectID(objectID)
	ids := []uint32{0,2,5,3,1,4,6,9,8,7,10,18000000,17,12,180,120,110}
	for _, v:= range ids{
		payload := service.OpIntPayload{}
		payload.ID = id
		payload.Value = v
		var out bool
		if err := s.Add(payload, &out); err != nil {
			t.Error("添加失败", err)
		}
	}
	var out []uint32
	for {
		_ = s.Iterate(service.OpIteratePayload{ID: id, Value: 4}, &out)
		var counter uint64
		_ = s.GetCardinality(id, &counter)
		t.Log(counter, out)
		if len(out) == 0 {
			if counter != 0 {
				t.Error("迭代器未移除已经迭代的数据")
			}
			break
		}
	}
}
