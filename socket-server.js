const express = require('express');
const http = require('http');
const { Server } = require('socket.io');

const app = express();
app.use(express.json());

const server = http.createServer(app);
const io = new Server(server, {
    cors: { origin: '*' }
});

io.on('connection', () => console.log('Client connected'));

app.post('/notify', (req, res) => {
    const payload = req.body;
    if (!payload || !payload.event) {
        return res.status(400).json({ error: 'Invalid payload' });
    }
    io.emit(payload.event, payload);
    res.json({ success: true });
});

const PORT = process.env.SOCKET_PORT || 4000;
server.listen(PORT, () => console.log(`Socket server running on ${PORT}`));
