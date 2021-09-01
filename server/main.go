package main

import (
	"buexplain/bitmap/connCloseMonitor"
	"buexplain/bitmap/service"
	"flag"
	"github.com/spiral/goridge/v2"
	"log"
	"net"
	"net/rpc"
	"os"
	"os/signal"
	"runtime"
	"strings"
	"syscall"
)

func main() {
	if err := rpc.Register(new(service.Service)); err != nil {
		panic(err)
	}

	var network, address string
	if strings.EqualFold(runtime.GOOS, "linux") || strings.EqualFold(runtime.GOOS, "freebsd")  || strings.EqualFold(runtime.GOOS, "darwin") {
		flag.StringVar(&network, "network", "unix", "unix or tcp")
		var tmp string
		if fileExists("/run/") {
			tmp = "/run/bitmap-rpc.sock"
		}else if fileExists("/var/run/") {
			tmp = "/run/bitmap-rpc.sock"
		}
		flag.StringVar(&address, "address", tmp, tmp+" or 127.0.0.1:6060")
	} else {
		flag.StringVar(&network, "network", "tcp", "tcp or unix")
		flag.StringVar(&address, "address", "127.0.0.1:6060", "127.0.0.1:6060 or /run/bitmap-rpc.sock")
	}

	flag.Parse()

	var ln net.Listener
	var err error
	ln, err = net.Listen(network, address)

	if err != nil {
		panic(err)
	}

	log.Printf("bitmap rpc service running: %s://%s pid: %d\n", network, address, os.Getpid())

	signalCh := make(chan os.Signal, 1)
	signal.Notify(signalCh,
		syscall.SIGHUP,  //hangup
		syscall.SIGTERM, //terminated
		syscall.SIGINT,  //interrupt
		syscall.SIGQUIT, //quit
	)

	go func(ln net.Listener, signalCh chan os.Signal) {
		for s := range signalCh {
			if s == syscall.SIGHUP {
				//忽略session断开信号
				continue
			}
			if err := ln.Close(); err == nil {
				log.Printf("Caught signal %s(%d): bitmap rpc service shutting down succeed\n", s, s)
				os.Exit(0)
			} else {
				log.Printf("Caught signal %s(%d): bitmap rpc service shutting down failed %s\n", s, s, err)
				os.Exit(1)
			}
		}
	}(ln, signalCh)

	emptyResponse := &rpc.Response{}
	for {
		conn, err := ln.Accept()
		if err != nil {
			continue
		}
		go func() {
			codec := connCloseMonitor.New()
			codec.Codec = goridge.NewCodec(conn)
			if err := codec.WriteResponse(emptyResponse, codec.ConnectionID); err == nil {
				rpc.ServeCodec(codec)
			}
		}()
	}
}

func fileExists(path string) bool {
	_, err := os.Stat(path)
	if os.IsNotExist(err) {
		return false
	}
	return true
}
