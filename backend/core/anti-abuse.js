const HttpError = require('./http-error');
const balancing = require('../config/balancing');

const cooldownMemory = new Map();
const burstMemory = new Map();

function buildKey(userId, actionKey) {
  return `${Number(userId)}:${String(actionKey)}`;
}

function assertActionAllowed(userId, actionKey) {
  const key = buildKey(userId, actionKey);
  const now = Date.now();

  const cooldownSec = balancing.antiAbuse.cooldowns[actionKey] || 0;
  const cooldownUntil = cooldownMemory.get(key) || 0;
  if (cooldownUntil > now) {
    const retryAfter = Math.ceil((cooldownUntil - now) / 1000);
    throw new HttpError(429, 'Action cooldown active', { actionKey, retryAfter });
  }

  const burstLimit = balancing.antiAbuse.maxActionBurst[actionKey] || 0;
  if (burstLimit > 0) {
    const bucket = burstMemory.get(key) || [];
    const fresh = bucket.filter((ts) => now - ts < 10000);
    if (fresh.length >= burstLimit) {
      throw new HttpError(429, 'Action burst limit exceeded', { actionKey, burstLimit });
    }
    fresh.push(now);
    burstMemory.set(key, fresh);
  }

  if (cooldownSec > 0) {
    cooldownMemory.set(key, now + cooldownSec * 1000);
  }
}

function __resetForTests() {
  cooldownMemory.clear();
  burstMemory.clear();
}

module.exports = {
  assertActionAllowed,
  __resetForTests
};
