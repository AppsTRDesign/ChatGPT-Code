const fs = require('fs');
const https = require('https');
const express = require('express');
const { Server } = require('socket.io');

const app = express();
app.use(express.json());

const certificatePath = process.env.SSL_CERT_PATH || '/usr/local/psa/var/certificates/scfgYrZUm';

const readCredential = (envKey, fallback) => {
    const filePath = process.env[envKey] || fallback;
    if (!fs.existsSync(filePath)) {
        console.error(`SSL credential not found for ${envKey || 'default'} at ${filePath}`);
        process.exit(1);
    }
    return fs.readFileSync(filePath);
};

const tlsOptions = {
    key: readCredential('SSL_KEY_PATH', certificatePath),
    cert: readCredential('SSL_CERT_PATH', certificatePath),
    ca: readCredential('SSL_CA_PATH', certificatePath),
    requestCert: false,
    rejectUnauthorized: false,
};

const server = https.createServer(tlsOptions, app);
const io = new Server(server, {
    cors: {
        origin: process.env.CORS_ORIGIN ? process.env.CORS_ORIGIN.split(',') : ['https://qrmenu.noasoft.org'],
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

const PORT = process.env.SOCKET_PORT || 4000;
server.listen(PORT, () => console.log(`Socket server running on ${PORT}`));
