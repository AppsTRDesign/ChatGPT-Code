const express = require('express');
const controller = require('./governors.controller');
const { authRequired } = require('../../core/auth-middleware');

const router = express.Router();

router.get('/cities/:cityId', controller.getCityGovernor);
router.get('/cities/:cityId/projects', controller.getCityProjects);
router.post('/cities/:cityId/projects', authRequired, controller.createProject);
router.post('/cities/:cityId/projects/:projectId/effort', authRequired, controller.addEffort);

module.exports = router;
