@echo off

rem 批处理教程 http://bbs.bathome.net/thread-18-1-1.html
rem 批处理常用命令及用法大全 http://bbs.bathome.net/thread-39-1-1.html

rem 声明采用UTF-8编码
chcp 65001

rem 清空屏幕
cls

echo build start...

rem 检查编译输出目录
if not exist bin (
    mkdir bin
) else (
    del /f /s /q bin\mirror-win-amd64.exe
    del /f /s /q bin\mirror-linux-amd64.bin
    del /f /s /q bin\mirror-darwin-amd64.bin
    del /f /s /q bin\config.example.toml
)

rem 复制配置文件
copy config.example.toml .\bin\config.example.toml /A/Y

rem 关闭cgo
SET CGO_ENABLED=0

rem 编译发布程序
echo build mirror-win-amd64.exe
set GOARCH=amd64&&set GOOS=windows&&go build -a -v -trimpath -ldflags "-s -w" -o ./bin/mirror-win-amd64.exe main.go
if %errorlevel% NEQ  0 (
    echo build failed
    exit /b %errorlevel%
)
set GOARCH=amd64&&set GOOS=linux&&go build -a -v -trimpath -ldflags "-s -w" -o ./bin/mirror-linux-amd64.bin main.go
echo build mirror-linux-amd64.bin
if %errorlevel% NEQ  0 (
    echo build failed
    exit /b %errorlevel%
)
echo build mirror-darwin-amd64.bin
set GOARCH=amd64&&set GOOS=darwin&&go build -a -v -trimpath -ldflags "-s -w" -o ./bin/mirror-darwin-amd64.bin main.go
if %errorlevel% NEQ  0 (
    echo build failed
    exit /b %errorlevel%
)
echo build successfully
pause