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

const restaurantRoom = (restaurantId) => `restaurant:${restaurantId}`;
const tableRoom = (token) => `table:${token}`;

io.on('connection', (socket) => {
    const handshake = socket.handshake.auth || {};
    if (handshake.restaurantId) {
        socket.join(restaurantRoom(handshake.restaurantId));
    }
    if (handshake.tableToken) {
        socket.join(tableRoom(handshake.tableToken));
    }

    socket.on('registerRestaurant', ({ restaurantId }) => {
        if (restaurantId) {
            socket.join(restaurantRoom(restaurantId));
        }
    });

    socket.on('registerTable', ({ tableToken }) => {
        if (tableToken) {
            socket.join(tableRoom(tableToken));
        }
    });
});

app.get('/health', (_req, res) => res.json({ status: 'ok' }));

app.post('/notify', (req, res) => {
    const payload = req.body;
    if (!payload || !payload.event) {
        return res.status(400).json({ error: 'Invalid payload' });
    }

    if (payload.restaurant_id) {
        io.to(restaurantRoom(payload.restaurant_id)).emit(payload.event, payload);
    } else {
        io.emit(payload.event, payload);
    }

    if (payload.table_token) {
        io.to(tableRoom(payload.table_token)).emit(payload.event, payload);
    }

    res.json({ success: true });
});

const PORT = 4000;
server.listen(PORT, () => console.log(`Socket server running on ${PORT}`));
