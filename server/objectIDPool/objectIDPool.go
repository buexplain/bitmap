package objectIDPool

import (
	"buexplain/bitmap/identity"
	"sync/atomic"
)

var idCounter []identity.ObjectID
var l identity.ConnectionID = 64

func init() {
	idCounter = make([]identity.ObjectID, l, l)
	var i identity.ConnectionID
	for i = 0; i < l; i++ {
		idCounter[i] = 0
	}
}

func Get(connectionID identity.ConnectionID) identity.ObjectID {
	return identity.ObjectID(atomic.AddUint32((*uint32)(&idCounter[connectionID%l]), 1))
}
