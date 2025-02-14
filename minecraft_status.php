<?php
// minecraft_status.php

class MinecraftServerStatus {
    private $host;
    private $port;
    private $timeout;

    public function __construct($host = 'play.back2basics.gg', $port = 25565, $timeout = 5) {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
    }

    public function getStatus() {
        try {
            $socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

            if (!$socket) {
                return [
                    'online' => false,
                    'players' => ['online' => 0, 'max' => 0],
                    'error' => "Cannot connect to server"
                ];
            }

            stream_set_timeout($socket, $this->timeout);

            // Send handshake + status request
            $data = "\x00"; // packet ID
            $data .= "\x00"; // protocol version
            $data .= pack('c', strlen($this->host)) . $this->host; // server host length and host
            $data .= pack('n', $this->port); // server port
            $data .= "\x01"; // next state (1 for status)

            $data = pack('c', strlen($data)) . $data; // prepend length of packet ID and data

            fwrite($socket, $data);
            fwrite($socket, "\x01\x00"); // status request

            // Read response
            $length = $this->readVarInt($socket);
            $data = fread($socket, $length);
            fclose($socket);

            // Decode JSON response
            $response = json_decode(substr($data, 1), true);

            return [
                'online' => true,
                'players' => [
                    'online' => $response['players']['online'],
                    'max' => $response['players']['max']
                ]
            ];
        } catch (Exception $e) {
            return [
                'online' => false,
                'players' => ['online' => 0, 'max' => 0],
                'error' => $e->getMessage()
            ];
        }
    }

    private function readVarInt($socket) {
        $result = 0;
        $numRead = 0;

        do {
            $byte = ord(fread($socket, 1));
            $value = $byte & 0x7F;
            $result |= $value << (7 * $numRead);
            $numRead++;

            if ($numRead > 5) {
                throw new Exception("VarInt too big");
            }
        } while (($byte & 0x80) != 0);

        return $result;
    }
}

// For AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $server = new MinecraftServerStatus();
    echo json_encode($server->getStatus());
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minecraft Server Status</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.js"></script>
</head>
<body class="bg-gray-100">
    <?php
    $server = new MinecraftServerStatus();
    $status = $server->getStatus();
    ?>

    <div class="max-w-sm mx-auto mt-8">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Server Status
                    </h3>
                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>

                <div id="status-container" class="mt-4">
                    <?php if ($status['online']): ?>
                        <div class="text-3xl font-bold text-gray-900">
                            <?php echo $status['players']['online']; ?> / <?php echo $status['players']['max']; ?>
                        </div>
                        <p class="text-sm text-gray-500">Players Online</p>
                        
                        <div class="mt-4 flex items-center">
                            <div class="h-3 w-3 rounded-full bg-green-500"></div>
                            <span class="ml-2 text-sm text-gray-500">Server Online</span>
                        </div>
                    <?php else: ?>
                        <div class="text-red-500">
                            Server Offline
                            <?php if (isset($status['error'])): ?>
                                <p class="text-sm"><?php echo htmlspecialchars($status['error']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    function updateStatus() {
        fetch('minecraft_status.php?ajax=1')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('status-container');
                if (data.online) {
                    container.innerHTML = `
                        <div class="text-3xl font-bold text-gray-900">
                            ${data.players.online} / ${data.players.max}
                        </div>
                        <p class="text-sm text-gray-500">Players Online</p>
                        
                        <div class="mt-4 flex items-center">
                            <div class="h-3 w-3 rounded-full bg-green-500"></div>
                            <span class="ml-2 text-sm text-gray-500">Server Online</span>
                        </div>
                    `;
                } else {
                    container.innerHTML = `
                        <div class="text-red-500">
                            Server Offline
                            ${data.error ? `<p class="text-sm">${data.error}</p>` : ''}
                        </div>
                    `;
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // Update status every 60 seconds
    setInterval(updateStatus, 60000);
    </script>
</body>
</html>
