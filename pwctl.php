<?php
// 艾尔赛电子（LC）LCUS-2型双路USB继电器模块Web控制程序

// 串口设备路径
$devPath = '/dev/ttyUSB0';

// 短按开关秒数
$shortPressSeconds = 0.1;

// 长按开关秒数
$longPressSeconds = 5;

// 按钮名称和控制命令
$ctrlData = [
    [
        'name'  => '电脑1电源按钮',
        'open'  => "A00101A2",
        'close' => "A00100A1",
    ],
    [
        'name'  => '电脑1重启按钮',
        'open'  => "A00201A3",
        'close' => "A00200A2",
    ],
];

ob_start();

if (isset($_GET['getData'])) {
    echoJSON([
        'devPath' => $devPath,
        'shortPressSeconds' => $shortPressSeconds,
        'longPressSeconds' => $longPressSeconds,
        'ctrlData' => $ctrlData,
    ]);
    return;
}

if (isset($_POST['button'])) {
    $button = $_POST['button'];
    if (!isset($ctrlData[$button])) {
        echoJSON([
            'message' => "找不到序号为 $button 的按钮"
        ]);
        return;
    }

    $fp = fopen($devPath, 'w');
    if (!$fp) {
        echoJSON([
            'message' => "打开继电器设备 $devPath 失败，设备不存在或PHP无权访问"
        ]);
        return;
    }

    $pressSeconds = $shortPressSeconds;
    if (isset($_POST['time'])) {
        $pressSeconds = min(max(0, (double)$_POST['time']), 30);
    }

    $data = $ctrlData[$button];
    $openData = hex2bin($data['open']);
    $closeData = hex2bin($data['close']);

    $cmdLen = strlen($openData);
    $writeLen = fwrite($fp, $openData);
    if ($writeLen !== $cmdLen) {
        echoJSON([
            'message' => "继电器接通命令（{$cmdLen} 字节）发送失败，仅写入 {$writeLen} 字节"
        ]);
        return;
    }

    usleep($pressSeconds * 1000000);


    $cmdLen = strlen($closeData);
    $writeLen = fwrite($fp, $closeData);
    if ($writeLen !== $cmdLen) {
        echoJSON([
            'message' => "继电器断开命令（{$cmdLen} 字节）发送失败，仅写入 {$writeLen} 字节"
        ]);
        return;
    }

    fclose($fp);

    echoJSON([
        'message' => "已按下 $data[name] $pressSeconds 秒"
    ]);
    return;
}

function echoJSON($data) {
    header('Content-Type: application/json');
    ob_end_clean();
    echo json_encode($data, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>继电器控制</title>
    <script src="https://hu60.cn/tpl/jhin/js/jquery-3.1.1.min.js"></script>
</head>
<body>
<?php
    foreach ($ctrlData as $button => $data) {
        echo <<<HTML
            <p>
                <input type="button" value="短按$data[name]" onclick="pressButton($button, $shortPressSeconds)">
                <input type="button" value="长按$data[name]" onclick="pressButton($button, $longPressSeconds)">
            </p>
        HTML;
    }
?>
<div id="messages">
    <!-- 内容待JS填充 -->
</div>
<script>
    function dateFormat(format, date){
        date = date || new Date();
        var map = {
            "YY": date.getYear(),
            "M": date.getMonth() + 1, //月份
            "d": date.getDate(), //日
            "h": date.getHours(), //小时
            "m": date.getMinutes(), //分
            "s": date.getSeconds(), //秒  
            "q": Math.floor((date.getMonth() + 3) / 3), //季度
            "S": date.getMilliseconds() //毫秒
        };
        format = format.replace(/([YMdhmsqS])+/g, function(all, t){
            var v = map[t];
            if (v !== undefined) {
                if (all.length > 1) {
                    v = "0" + v;
                    v = v.substr(v.length - 2);
                }
                return v;
            }
            else if (t === "Y") {
                return (date.getFullYear() + "").substr(4 - all.length);
            }
            return all;
        });
        return format;
    }
    function outputLine(line) {
        $('#messages').append('<div>' + dateFormat('[hh:mm:ss] ') + line + '</div>');
    }
    function pressButton(button, pressSeconds) {
        $.post(document.location, "button=" + button + "&time=" + pressSeconds, function(data, stat, xhr) {
            console.log(data, stat, xhr);
            if (data && data.message) {
                outputLine(data.message);
            } else {
                outputLine('出错：' + JSON.stringify(data));
            }
        });
        outputLine('请求已发送');
    }
</script>
</body>
</html>
