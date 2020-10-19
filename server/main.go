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

	if strings.EqualFold(runtime.GOOS, "linux") {
		flag.StringVar(&network, "network", "unix", "unix or tcp")
		flag.StringVar(&address, "address", "/tmp/bitmap-rpc.sock", "/tmp/bitmap-rpc.sock or 127.0.0.1:37101")
	} else {
		flag.StringVar(&network, "network", "tcp", "tcp or unix")
		flag.StringVar(&address, "address", "127.0.0.1:37101", "127.0.0.1:37101 or /tmp/bitmap-rpc.sock")
	}

	flag.Parse()

	var ln net.Listener
	var err error
	ln, err = net.Listen(network, address)

	if err != nil {
		panic(err)
	}

	log.Printf("bitmap rpc service running: %s://%s\n", network, address)

	signalCh := make(chan os.Signal, 1)
	signal.Notify(signalCh, syscall.SIGINT, syscall.SIGHUP)

	go func(ln net.Listener, signalCh chan os.Signal) {
		s := <-signalCh
		if err := ln.Close(); err == nil {
			log.Printf("Caught signal %s(%d): bitmap rpc service shutting down succeed\n", s, s)
			os.Exit(0)
		} else {
			log.Printf("Caught signal %s(%d): bitmap rpc service shutting down failed %s\n", s, s, err)
			os.Exit(2)
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
