const express = require('express');
const http = require('http');
const { Server } = require('socket.io');

const app = express();
app.use(express.json());

const server = http.createServer(app);
const io = new Server(server, {
    cors: { origin: '*' }
});

const roomName = (restaurantId) => `restaurant:${restaurantId}`;

io.on('connection', (socket) => {
    const handshakeId = socket.handshake.auth?.restaurantId;
    if (handshakeId) {
        socket.join(roomName(handshakeId));
    }

    socket.on('registerRestaurant', ({ restaurantId }) => {
        if (!restaurantId) return;
        socket.join(roomName(restaurantId));
    });

    socket.on('disconnect', () => {
        // noop but reserved for future logging
    });
});

app.get('/health', (_req, res) => res.json({ status: 'ok' }));

app.post('/notify', (req, res) => {
    const payload = req.body;
    if (!payload || !payload.event) {
        return res.status(400).json({ error: 'Invalid payload' });
    }

    if (payload.restaurant_id) {
        io.to(roomName(payload.restaurant_id)).emit(payload.event, payload);
    } else {
        io.emit(payload.event, payload);
    }

    res.json({ success: true });
});

const PORT = process.env.SOCKET_PORT || 4000;
server.listen(PORT, () => console.log(`Socket server running on ${PORT}`));
