#include <WiFi.h>
#include <HTTPClient.h>

const char* ssid = "Nicolaou";
const char* password = "nicnafam";

const char* server = "http://192.168.0.22:3000/api"; // Node API server

const int lampPin = 5;
const int ledPin = 2; // integrated ESP LED

unsigned long lastCheck = 0;
const unsigned long interval = 3000;

void setup() {
  Serial.begin(115200);
  pinMode(lampPin, OUTPUT);
  pinMode(ledPin, OUTPUT);

  WiFi.begin(ssid, password);
  Serial.print("Connecting WiFi");

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("\nConnected!");
}

void loop() {
  if (millis() - lastCheck > interval) {
    lastCheck = millis();

    if (WiFi.status() == WL_CONNECTED) {
      HTTPClient http;


      http.begin(String(server) + "/get/lamp");
      int httpCode = http.GET();
      bool gotLampData = false;

      if (httpCode == 200) {
        String payload = http.getString();
        payload.trim();
        Serial.println("Server lamp: " + payload);

        if (payload == "ON") {
          digitalWrite(lampPin, HIGH);
          gotLampData = true;
        } else if (payload == "OFF") {
          digitalWrite(lampPin, LOW);
          gotLampData = true;
        }
      }
      http.end();

      http.begin(String(server) + "/get/led");
      int ledCode = http.GET();
      bool gotLedData = false;

      if (ledCode == 200) {
        String ledPayload = http.getString();
        ledPayload.trim();
        Serial.println("Server led: " + ledPayload);

        if (ledPayload == "ON") {
          digitalWrite(ledPin, HIGH);
          gotLedData = true;
        } else if (ledPayload == "OFF") {
          digitalWrite(ledPin, LOW);
          gotLedData = true;
        }
      }
      http.end();

      if (!gotLampData && !gotLedData) {
        Serial.println("No valid lamp/led data, blinking alert led.");
        for (int i = 0; i < 3; i++) {
          digitalWrite(lampPin, HIGH);
          delay(150);
          digitalWrite(lampPin, LOW);
          delay(150);
        }
      }

      // check for blink command from app
      http.begin(String(server) + "/get/blink");
      int blinkCode = http.GET();
      if (blinkCode == 200) {
        String blinkPayload = http.getString();
        blinkPayload.trim();
        if (blinkPayload == "1") {
          Serial.println("Received blink command from app");
          for (int i = 0; i < 5; i++) {
            digitalWrite(lampPin, HIGH);
            delay(150);
            digitalWrite(lampPin, LOW);
            delay(150);
          }
        }
      }
      http.end();

      // check for ledblink command from app
      http.begin(String(server) + "/get/ledblink");
      int ledBlinkCode = http.GET();
      if (ledBlinkCode == 200) {
        String ledBlinkPayload = http.getString();
        ledBlinkPayload.trim();
        if (ledBlinkPayload == "1") {
          Serial.println("Received ledblink command from app");
          for (int i = 0; i < 5; i++) {
            digitalWrite(ledPin, HIGH);
            delay(150);
            digitalWrite(ledPin, LOW);
            delay(150);
          }
        }
      }
      http.end();

      http.begin(String(server) + "?heartbeat=1");
      http.GET();
      http.end();

    } else {
      Serial.println("WiFi lost! Blinking alert LED.");
      // Blink the lamp pin as a disconnect indicator
      for (int i = 0; i < 3; i++) {
        digitalWrite(lampPin, HIGH);
        delay(200);
        digitalWrite(lampPin, LOW);
        delay(200);
      }
    }
  }
}