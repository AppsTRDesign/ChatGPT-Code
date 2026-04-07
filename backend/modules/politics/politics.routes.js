const express = require('express');
const controller = require('./politics.controller');
const { authRequired } = require('../../core/auth-middleware');

const router = express.Router();

router.get('/elections', controller.listElections);
router.post('/elections', authRequired, controller.createElection);
router.post('/elections/:electionId/candidate', authRequired, controller.joinCandidate);
router.post('/elections/:electionId/vote', authRequired, controller.castVote);
router.get('/elections/:electionId/results', controller.electionResults);
router.post('/elections/:electionId/finalize', authRequired, controller.finalizeElection);

router.post('/laws', authRequired, controller.proposeLaw);
router.post('/laws/:lawId/vote', authRequired, controller.voteLaw);
router.post('/laws/:lawId/finalize', authRequired, controller.finalizeLaw);

module.exports = router;
