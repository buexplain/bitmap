package objectIDPool

import (
	"sync/atomic"
)

var idCounter []uint32
var l uint32 = 50

func init() {
	idCounter = make([]uint32, l, l)
	var i uint32
	for i = 0; i < l; i++ {
		idCounter[int(i)] = 0
	}
}

func Get(connectionID uint32) uint32 {
	return atomic.AddUint32(&idCounter[connectionID%l], 1)
}
