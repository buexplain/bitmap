package connectionIDPool

var idCache chan uint32

func init() {
	var i, l uint32
	l = 65536
	idCache = make(chan uint32, l)
	for i = 1; i < l; i++ {
		idCache <- i
	}
}

func Get() uint32 {
	return <-idCache
}

func Put(id uint32) {
	idCache <- id
}
