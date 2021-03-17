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
	for i:=0; i<10; i++ {
		payload := service.OpIntPayload{}
		payload.ID = id
		payload.Value = uint32(i)
		var out bool
		if err := s.Add(payload, &out); err != nil {
			t.Error("添加失败", err)
		}
	}
	var out []uint32
	for {
		_ = s.Iterate(service.OpIteratePayload{ID: id, Value: 2}, &out)
		t.Log(out)
		if len(out) == 0 {
			break
		}
	}
}
