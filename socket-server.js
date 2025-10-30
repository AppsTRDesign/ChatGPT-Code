const fs = require('fs');
const https = require('https');
const express = require('express');
const { Server } = require('socket.io');

const app = express();
app.use(express.json());

const certificatePath = '/usr/local/psa/var/certificates/scfgYrZUm';

if (!fs.existsSync(certificatePath)) {
    console.error(`SSL credential not found at ${certificatePath}`);
    process.exit(1);
}

const tlsOptions = {
    key: fs.readFileSync(certificatePath),
    cert: fs.readFileSync(certificatePath),
    ca: fs.readFileSync(certificatePath),
    requestCert: false,
    rejectUnauthorized: false,
};

const server = https.createServer(tlsOptions, app);
const io = new Server(server, {
    cors: {
        origin: ['https://qrmenu.noasoft.org'],
        methods: ['GET', 'POST', 'PUT', 'DELETE'],
        credentials: true,
    }
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

const PORT = 4000;
server.listen(PORT, () => console.log(`Socket server running on ${PORT}`));
