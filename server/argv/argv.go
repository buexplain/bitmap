package argv

import (
	"flag"
	"os"
	"runtime"
	"strings"
)

var Network string

var Address string

// GCTick 定时多少秒扫描一次待回收的连接id
var GCTick int

// ReconnectWait 重连等待秒数，该秒之后没有重连的连接id，会被回收掉
var ReconnectWait int64

func init() {
	if strings.EqualFold(runtime.GOOS, "linux") || strings.EqualFold(runtime.GOOS, "freebsd") || strings.EqualFold(runtime.GOOS, "darwin") {
		flag.StringVar(&Network, "network", "unix", "unix or tcp")
		var tmp string
		if fileExists("/run/") {
			tmp = "/run/bitmap-rpc.sock"
		} else if fileExists("/var/run/") {
			tmp = "/run/bitmap-rpc.sock"
		}
		flag.StringVar(&Address, "address", tmp, tmp+" or 127.0.0.1:6060")
	} else {
		flag.StringVar(&Network, "network", "tcp", "tcp or unix")
		flag.StringVar(&Address, "address", "127.0.0.1:6060", "127.0.0.1:6060 or /run/bitmap-rpc.sock")
	}
	flag.IntVar(&GCTick, "gcTick", 3, "connection gc tick second")
	flag.Int64Var(&ReconnectWait, "reconnectWait", 60, "reconnect wait second")
	flag.Parse()
}

func fileExists(path string) bool {
	_, err := os.Stat(path)
	if os.IsNotExist(err) {
		return false
	}
	return true
}
