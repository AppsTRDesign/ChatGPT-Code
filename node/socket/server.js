const https = require('https');
const fs = require('fs');
const socket = require('socket.io');

const options = {
    key: fs.readFileSync('/usr/local/psa/var/certificates/scfgYrZUm'),
    cert: fs.readFileSync('/usr/local/psa/var/certificates/scfgYrZUm'),
    ca: fs.readFileSync('/usr/local/psa/var/certificates/scfgYrZUm'),
    requestCert: false,
    rejectUnauthorized: false,
};

const server = https.createServer(options);
const io = socket(server, {
    cors: {
        origin: 'https://qrmenu.noasoft.org',
        methods: ['GET', 'POST'],
    },
});

io.on('connection', (client) => {
    client.on('waiter:call', (payload) => {
        io.emit('waiter:call', payload);
        io.emit('waiter:update', { status: 'Yolda', ...payload });
    });

    client.on('waiter:update', (payload) => {
        io.emit('waiter:update', payload);
    });

    client.on('order:new', (payload) => {
        io.emit('order:update', { status: 'Beklemede', ...payload });
    });

    client.on('order:update', (payload) => {
        io.emit('order:update', payload);
    });
});

server.listen(4000, () => {
    console.log('Socket server running on port 4000');
});
