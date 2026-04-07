const politicsService = require('./politics.service');

async function createElection(req, res, next) {
  try {
    const data = await politicsService.createElection(req.auth.sub, req.body);
    res.status(201).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function listElections(req, res, next) {
  try {
    const data = await politicsService.listElections(req.query.status);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function joinCandidate(req, res, next) {
  try {
    const data = await politicsService.joinCandidate(req.auth.sub, req.params.electionId, req.body.partyName, req.body.manifesto);
    res.status(201).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function castVote(req, res, next) {
  try {
    const data = await politicsService.castElectionVote(req.auth.sub, req.params.electionId, req.body.candidateUserId);
    res.status(201).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function electionResults(req, res, next) {
  try {
    const data = await politicsService.electionResults(req.params.electionId);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function finalizeElection(req, res, next) {
  try {
    const data = await politicsService.finalizeElection(req.params.electionId);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function proposeLaw(req, res, next) {
  try {
    const data = await politicsService.proposeLaw(req.auth.sub, req.body);
    res.status(201).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function voteLaw(req, res, next) {
  try {
    const data = await politicsService.voteLaw(req.auth.sub, req.params.lawId, req.body.voteValue);
    res.status(201).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function finalizeLaw(req, res, next) {
  try {
    const data = await politicsService.finalizeLaw(req.params.lawId);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

module.exports = {
  createElection,
  listElections,
  joinCandidate,
  castVote,
  electionResults,
  finalizeElection,
  proposeLaw,
  voteLaw,
  finalizeLaw
};
