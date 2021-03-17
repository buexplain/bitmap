package connCloseMonitor

import (
	"buexplain/bitmap/connectionIDPool"
	"buexplain/bitmap/service"
	"github.com/spiral/goridge/v2"
)

type ConnCloseMonitor struct {
	*goridge.Codec
	ConnectionID uint32
}

func New() *ConnCloseMonitor {
	return &ConnCloseMonitor{ConnectionID: connectionIDPool.Get()}
}

func (r *ConnCloseMonitor) Close() error {
	service.GC(r.ConnectionID)
	return r.Codec.Close()
}
