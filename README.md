Performant asynchronous pure PHP Micro Service Transport using plain TCP layer based on ReactPHP.

Library not enough much functional and not recommended for production use. For production use need to provide some much functional like: logging, statistics, debug, unit tests, timeouts, better error handling, client authentication and tls encryption options. Possible to pass "factor" bigint option to server to pin worker thread for request.

This library provides clients and server:

Server - listen tcp port, receive connections, fork workers and pass requests to workers. If worker closed connection, then restarts worker. If any error acquired during request server 'll return empty response.

Client (async promises and sync) - sends request to listening server and returns result. Failover class provides ability to resend request N times with specified interval, if request failed.

Installation:
```
composer require tatikoma/react-mst:dev-master
```

For example usage start server.php from examples directory and then client-sync.php or/and client-async.php
```
~ # cd examples
~/examples # $ php server.php &

~/examples # $ php client-sync.php 
Result: 5

~/examples # $ php client-async.php 
Result: 5
```