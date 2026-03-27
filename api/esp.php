<?php

$file = "state.json";

// default state
if (!file_exists($file)) {
    file_put_contents($file, json_encode([
        "lamp" => "OFF",
        "led" => "OFF",
        "last_heartbeat" => time(),
        "blink" => "0",
        "ledblink" => "0"
    ]));
}

$data = json_decode(file_get_contents($file), true);

// ESP requests state
if (isset($_GET["get"])) {
    if ($_GET["get"] == "lamp") {
        echo $data["lamp"];
        exit;
    }
    if ($_GET["get"] == "led") {
        echo $data["led"];
        exit;
    }
    if ($_GET["get"] == "blink") {
        $blinkValue = $data["blink"] ?? "0";
        $data["blink"] = "0";
        file_put_contents($file, json_encode($data));
        echo $blinkValue;
        exit;
    }
    if ($_GET["get"] == "ledblink") {
        $ledBlinkVal = $data["ledblink"] ?? "0";
        $data["ledblink"] = "0";
        file_put_contents($file, json_encode($data));
        echo $ledBlinkVal;
        exit;
    }
}

// heartbeat from ESP
if (isset($_GET["heartbeat"])) {
    $data["last_heartbeat"] = time();
    file_put_contents($file, json_encode($data));
    echo "OK";
    exit;
}

// app control
if (isset($_GET["lamp"])) {
    $data["lamp"] = $_GET["lamp"];
    file_put_contents($file, json_encode($data));
    echo "UPDATED";
    exit;
}

if (isset($_GET["led"])) {
    $data["led"] = $_GET["led"];
    file_put_contents($file, json_encode($data));
    echo "UPDATED";
    exit;
}

if (isset($_GET["blink"])) {
    if ($_GET["blink"] == "1") {
        $data["blink"] = "1";
        file_put_contents($file, json_encode($data));
        echo "UPDATED";
        exit;
    }
}

if (isset($_GET["ledblink"])) {
    if ($_GET["ledblink"] == "1") {
        $data["ledblink"] = "1";
        file_put_contents($file, json_encode($data));
        echo "UPDATED";
        exit;
    }
}


?>