package main

import (
	"buexplain/bitmap/argv"
	"buexplain/bitmap/connectionGC"
	"buexplain/bitmap/connectionIDPool"
	"buexplain/bitmap/identity"
	"buexplain/bitmap/service"
	"github.com/spiral/goridge/v2"
	"log"
	"net"
	"net/rpc"
	"os"
	"os/signal"
	"syscall"
)

func main() {
	if err := rpc.Register(new(service.Service)); err != nil {
		panic(err)
	}

	var ln net.Listener
	var err error
	ln, err = net.Listen(argv.Network, argv.Address)

	if err != nil {
		panic(err)
	}

	log.Printf(
		"bitmap rpc service running: %s://%s gcTick: %d reconnectWait: %d pid: %d\n",
		argv.Network, argv.Address, argv.GCTick, argv.ReconnectWait, os.Getpid(),
	)

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
			//客户端连接id
			var connectionID identity.ConnectionID = 0
			codec := goridge.NewCodec(conn)
			//尝试读取客户端发来的连接id
			if err := codec.ReadRequestBody(&connectionID); err != nil {
				log.Println(err)
				_ = codec.Close()
				return
			}
			if connectionID <= 0 {
				//客户端第一次连接，从连接id池中分配一个连接id
				goto alloc
			}
			//客户端使用旧连接id重连，从延迟回收队列中移除
			if connectionGC.Del(connectionID) {
				//移除成功，连接还未被回收，可以继续使用，重新下发连接id回去
				err := codec.WriteResponse(emptyResponse, connectionID)
				if err == nil {
					rpc.ServeCodec(codec)
					//连接退出，将连接id加入到延迟回收队列中
					connectionGC.Add(connectionID)
					return
				}
				log.Println(err)
				connectionGC.Add(connectionID)
				_ = codec.Close()
				return
			}
		alloc:
			connectionID = connectionIDPool.Get()
			err := codec.WriteResponse(emptyResponse, connectionID)
			if err == nil {
				rpc.ServeCodec(codec)
				//连接退出，将连接id加入到延迟回收队列中
				connectionGC.Add(connectionID)
				return
			}
			log.Println(err)
			connectionIDPool.Put(connectionID)
			_ = codec.Close()
		}()
	}
}
