<?php
require_once __DIR__ . '/vendor/autoload.php';


use Workerman\Worker;
use Channel\Client;
use Channel\Server;

// 初始化一个Channel服务端
$channel_server = new Server('0.0.0.0', 2206);

// Create a Websocket server
$ws_worker = new Worker("websocket://0.0.0.0:2346");

// 4 processes
$ws_worker->count = 4;

$ws_worker->onWorkerStart = function ($worker) {
    // Channel客户端连接到Channel服务端
    Client::connect('0.0.0.0', 2206);

    // 订阅broadcast事件，并注册事件回调
    Client::on('broadcast', function ($event_data) use ($worker) {
        // 向当前worker进程的所有客户端广播消息
        foreach ($worker->connections as $connection) {
            $connection->send($event_data);
        }
    });
};

// Emitted when data received
$ws_worker->onMessage = function ($connection, $data) use ($ws_worker) {
    // 向所有worker进程发布broadcast事件
    Channel\Client::publish('broadcast', $data);
};

// Emitted when connection closed
$ws_worker->onClose = function ($connection) {
    echo "Connection closed\n";
};

// Run worker
Worker::runAll();
