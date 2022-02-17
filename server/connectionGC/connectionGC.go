package connectionGC

import (
	"buexplain/bitmap/argv"
	"buexplain/bitmap/identity"
	"buexplain/bitmap/service"
	"sync"
	"time"
)

var gc map[identity.ConnectionID]int64
var lock sync.RWMutex

func init() {
	gc = make(map[identity.ConnectionID]int64)
	lock = sync.RWMutex{}
}

func init() {
	if argv.GCTick > 0 {
		go func() {
			//间隔一定秒数进行连接id的回收
			tick := time.Tick(time.Second * time.Duration(argv.GCTick))
			for now := range tick {
				lock.Lock()
				curr := now.Unix()
				for connectionID, t := range gc {
					//一定秒数内没有重连则会被回收掉
					if curr-t >= argv.ReconnectWait {
						service.GC(connectionID)
						delete(gc, connectionID)
					}
				}
				lock.Unlock()
			}
		}()
	}
}

func Add(connectionID identity.ConnectionID) {
	//如果没有定时检查延迟gc的配置，则直接回收
	if argv.GCTick <= 0 {
		//id被当场回收
		service.GC(connectionID)
		return
	}
	lock.Lock()
	defer lock.Unlock()
	gc[connectionID] = time.Now().Unix()
}

func Del(connectionID identity.ConnectionID) bool {
	if argv.GCTick <= 0 {
		//无需延迟回收连接id，则认为删除失败，因为id被当场回收了
		return false
	}
	lock.Lock()
	defer lock.Unlock()
	if _, ok := gc[connectionID]; ok {
		delete(gc, connectionID)
		return true
	}
	return false
}
