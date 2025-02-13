# File: .env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=your-app-key
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

BROADCAST_DRIVER=redis
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# File: routes/web.php
<?php

use App\Events\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('send-message', function(Request $request) {
    $message = $request->input('message');
    $username = $request->input('username');
    event(new Message($username, $message));

    return ['success' => true];
})->name('send_message');

Route::get('/', function () {
    return view('welcome');
});

# File: app/Events/Message.php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Message implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $username;
    public $message;

    public function __construct($username, $message)
    {
        $this->username = $username;
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new Channel('chat');
    }

    public function broadcastAs()
    {
        return 'message';
    }
}

# File: resources/js/bootstrap.js
import 'bootstrap';
import axios from 'axios';
import Echo from 'laravel-echo';
import Redis from 'ioredis';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.Echo = new Echo({
    broadcaster: 'redis',
    key: process.env.MIX_REDIS_KEY,
    cluster: process.env.MIX_REDIS_CLUSTER,
    host: window.location.hostname + ':6001',
    forceTLS: false
});

# File: resources/views/welcome.blade.php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Redis Chat</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col">
                <section style="background-color: #eee;">
                    <div class="container py-5">
                        <div class="row d-flex justify-content-center">
                            <div class="col-md-8 col-lg-6 col-xl-4">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center p-3"
                                        style="border-top: 4px solid #ffa900;">
                                        <h5 class="mb-0">Chat messages</h5>
                                        <div class="d-flex flex-row align-items-center">
                                            <span class="badge bg-warning me-3">20</span>
                                            <i class="fas fa-minus me-3 text-muted fa-xs"></i>
                                            <i class="fas fa-comments me-3 text-muted fa-xs"></i>
                                            <i class="fas fa-times text-muted fa-xs"></i>
                                        </div>
                                    </div>

                                    <div class="card-body" data-mdb-perfect-scrollbar="true"
                                        style="position: relative; height: 400px; overflow-y: auto;">
                                        <div class="d-flex justify-content-between" id="show_username">
                                            <p class="small mb-1 text-muted" id="show_time"></p>
                                        </div>
                                        <div class="d-flex flex-row justify-content-start">
                                            <div id="show_img"></div>
                                            <div id="show_messages"></div>
                                        </div>
                                    </div>

                                    <div class="card-footer text-muted d-flex justify-content-start align-items-center p-3">
                                        <div class="input-group mb-0">
                                            <input type="text" class="form-control" placeholder="Type Username"
                                                name="username" id="username" />
                                            <input type="text" class="form-control" placeholder="Type Message"
                                                name="sms" id="sms" />
                                            <button class="btn btn-warning" type="button" id="send-message"
                                                style="padding-top: .55rem;">
                                                Send
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(document).ready(function() {
            window.Echo.channel('chat')
                .listen('.message', (data) => {
                    var now = new Date();
                    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    var monthName = months[now.getMonth()];
                    var date = now.getDate();
                    var hours = now.getHours() % 12 || 12;
                    var minutes = (now.getMinutes() < 10 ? '0' : '') + now.getMinutes();
                    var ampm = now.getHours() >= 12 ? 'pm' : 'am';
                    var formattedDateTime = date + ' ' + monthName + ' ' + hours + ':' + minutes + ' ' + ampm;

                    $('#show_messages').append(
                        '<strong>' + data.username + '</strong> :' +
                        '<p class="p-2 ms-3 mb-3 rounded-3 direct-chat-message" style="background-color: #f5f6f7;">' +
                        data.message + '</p>' +
                        '<br> <small>' + formattedDateTime + '</small>' + '<br>'
                    );
                });

            $(document).on('click', "#send-message", function(e) {
                e.preventDefault();

                let username = $("#username").val();
                let message = $("#sms").val();

                if (username == '' || message == '') {
                    alert("Please enter username and message");
                    return false;
                }

                $.ajax({
                    method: "post",
                    url: '{{ route('send_message') }}',
                    data: {
                        username: username,
                        message: message
                    },
                    success: function(res) {
                        if (res.success == true) {
                            $("#sms").val('');
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>

# File: config/broadcasting.php
// Add or update the redis configuration in the connections array:
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => [
            'default' => 'default',
            'connection' => 'default',
        ],
    ],
    // ... other connections
],

# Installation Steps:
1. composer require predis/predis
2. composer require beyondcode/laravel-websockets
3. php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="config"
4. npm install
5. npm install --save-dev laravel-echo ioredis

# Run the application:
1. Start Redis server
2. php artisan websockets:serve
3. php artisan serve
4. npm run dev

# Make sure BroadcastServiceProvider is uncommented in config/app.php:
'providers' => [
    // ...
    App\Providers\BroadcastServiceProvider::class,
],
