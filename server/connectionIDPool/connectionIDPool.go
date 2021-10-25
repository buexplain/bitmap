package connectionIDPool

import (
	"buexplain/bitmap/identity"
)

var idCache chan identity.ConnectionID

func init() {
	var i, l identity.ConnectionID
	l = 65536
	idCache = make(chan identity.ConnectionID, l)
	for i = 1; i < l; i++ {
		idCache <- i
	}
}

func Get() identity.ConnectionID {
	return <-idCache
}

func Put(id identity.ConnectionID) {
	idCache <- id
}
