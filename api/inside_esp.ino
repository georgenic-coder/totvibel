#include <WiFi.h>
#include <WebServer.h>

const char* ssid = "Nicolaou";
const char* password = "nicnafam";

WebServer server(80);

const int lampPin = 5;
const int ledPin = 2; // integrated LED

String lampState = "OFF";
String ledState = "OFF";
unsigned long lastHeartbeat = 0;
String blinkState = "0";
String ledBlinkState = "0";

void handleSet() {
  String lampVal = server.arg("lamp");
  String ledVal = server.arg("led");

  if (lampVal.length()) {
    lampVal.toUpperCase();
    if (lampVal == "ON" || lampVal == "OFF") {
      lampState = lampVal;
      digitalWrite(lampPin, lampVal == "ON" ? HIGH : LOW);
    }
  }

  if (ledVal.length()) {
    ledVal.toUpperCase();
    if (ledVal == "ON" || ledVal == "OFF") {
      ledState = ledVal;
      digitalWrite(ledPin, ledVal == "ON" ? HIGH : LOW);
    }
  }

  server.send(200, "text/plain", "UPDATED");
}

void handleState() {
  lastHeartbeat = lastHeartbeat == 0 ? millis()/1000 : lastHeartbeat;
  String payload = "{\"lamp\":\"" + lampState + "\",\"led\":\"" + ledState + "\",\"last_heartbeat\":" + String(lastHeartbeat) + ",\"blink\":\"" + blinkState + "\",\"ledblink\":\"" + ledBlinkState + "\"}";
  server.send(200, "application/json", payload);
}

void handleHeartbeat() {
  lastHeartbeat = millis()/1000;
  server.send(200, "text/plain", "OK");
}

void handleBlink() {
  blinkState = "1";
  server.send(200, "text/plain", "UPDATED");
}

void handleLedBlink() {
  ledBlinkState = "1";
  server.send(200, "text/plain", "UPDATED");
}

void handleGet() {
  String what = server.arg("what");
  if (what == "lamp") {
    server.send(200, "text/plain", lampState);
    return;
  }
  if (what == "led") {
    server.send(200, "text/plain", ledState);
    return;
  }
  if (what == "blink") {
    String r = blinkState;
    blinkState = "0";
    server.send(200, "text/plain", r);
    return;
  }
  if (what == "ledblink") {
    String r = ledBlinkState;
    ledBlinkState = "0";
    server.send(200, "text/plain", r);
    return;
  }
  server.send(400, "text/plain", "UNKNOWN");
}

void setup() {
  Serial.begin(115200);
  pinMode(lampPin, OUTPUT);
  pinMode(ledPin, OUTPUT);

  digitalWrite(lampPin, LOW);
  digitalWrite(ledPin, LOW);

  WiFi.begin(ssid, password);
  Serial.print("Connecting WiFi");

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("\nConnected!");
  Serial.println(WiFi.localIP());

  server.on("/state", HTTP_GET, handleState);
  server.on("/set", HTTP_GET, handleSet);
  server.on("/heartbeat", HTTP_GET, handleHeartbeat);
  server.on("/blink", HTTP_GET, handleBlink);
  server.on("/ledblink", HTTP_GET, handleLedBlink);
  server.on("/get", HTTP_GET, handleGet);

  server.begin();
}

void loop() {
  server.handleClient();

  if (blinkState == "1") {
    for (int i = 0; i < 5; i++) {
      digitalWrite(lampPin, HIGH);
      delay(150);
      digitalWrite(lampPin, LOW);
      delay(150);
    }
    blinkState = "0";
  }

  if (ledBlinkState == "1") {
    for (int i = 0; i < 5; i++) {
      digitalWrite(ledPin, HIGH);
      delay(150);
      digitalWrite(ledPin, LOW);
      delay(150);
    }
    ledBlinkState = "0";
  }
}