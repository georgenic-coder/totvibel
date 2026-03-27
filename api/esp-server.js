const express = require('express');
const fs = require('fs');
const path = require('path');

const app = express();
const stateFile = path.join(__dirname, 'state.json');

function loadState() {
  if (!fs.existsSync(stateFile)) {
    const initial = { lamp: 'OFF', led: 'OFF', last_heartbeat: Math.floor(Date.now()/1000), blink: '0', ledblink: '0' };
    fs.writeFileSync(stateFile, JSON.stringify(initial, null, 2));
  }
  return JSON.parse(fs.readFileSync(stateFile, 'utf8'));
}

function saveState(state) {
  fs.writeFileSync(stateFile, JSON.stringify(state, null, 2));
}

app.use(express.static(path.join(__dirname)));

app.get('/api/state', (req, res) => {
  const state = loadState();
  res.json(state);
});

app.get('/api/lamp/:value', (req, res) => {
  const v = req.params.value.toUpperCase();
  if (!['ON', 'OFF'].includes(v)) return res.status(400).send('Invalid lamp value');
  const state = loadState();
  state.lamp = v;
  saveState(state);
  res.send('UPDATED');
});

app.get('/api/led/:value', (req, res) => {
  const v = req.params.value.toUpperCase();
  if (!['ON', 'OFF'].includes(v)) return res.status(400).send('Invalid led value');
  const state = loadState();
  state.led = v;
  saveState(state);
  res.send('UPDATED');
});

app.get('/api/heartbeat', (req, res) => {
  const state = loadState();
  state.last_heartbeat = Math.floor(Date.now()/1000);
  saveState(state);
  res.send('OK');
});

app.get('/api/blink', (req, res) => {
  const state = loadState();
  state.blink = '1';
  saveState(state);
  res.send('UPDATED');
});

app.get('/api/ledblink', (req, res) => {
  const state = loadState();
  state.ledblink = '1';
  saveState(state);
  res.send('UPDATED');
});

app.get('/api/get/:what', (req, res) => {
  const state = loadState();
  const t = req.params.what;

  if (t === 'lamp') return res.send(state.lamp);
  if (t === 'led') return res.send(state.led);
  if (t === 'blink') {
    const v = state.blink || '0';
    state.blink = '0';
    saveState(state);
    return res.send(v);
  }
  if (t === 'ledblink') {
    const v = state.ledblink || '0';
    state.ledblink = '0';
    saveState(state);
    return res.send(v);
  }
  res.status(400).send('unknown');
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`ESP API server running on http://localhost:${PORT}`));
