const pool = require('../../core/db');
const HttpError = require('../../core/http-error');

const PROJECT_IMPACT = {
  infrastructure_upgrade: 'infrastructure_score = infrastructure_score + 4, development_level = development_level + 1, transport_score = transport_score + 1',
  airport_upgrade: 'airport_level = airport_level + 1, transport_score = transport_score + 3',
  healthcare_upgrade: 'healthcare_score = healthcare_score + 4, safety_score = safety_score + 1',
  education_upgrade: 'education_score = education_score + 4, economy_score = economy_score + 1',
  industry_upgrade: 'industry_level = industry_level + 1, economy_score = economy_score + 4, employment_rate = employment_rate + 1',
  housing_upgrade: 'housing_level = housing_level + 1, development_level = development_level + 1',
  security_upgrade: 'safety_score = safety_score + 4',
  transport_upgrade: 'transport_score = transport_score + 4'
};

async function getActiveGovernor(cityId) {
  const [rows] = await pool.query(
    `SELECT g.id, g.city_id, g.user_id, g.term_start, g.term_end, g.reputation, g.experience,
            g.completed_projects, g.corruption_risk, g.is_active,
            u.username
     FROM governors g
     JOIN users u ON u.id = g.user_id
     WHERE g.city_id = ? AND g.is_active = 1
     ORDER BY g.id DESC
     LIMIT 1`,
    [Number(cityId)]
  );

  return rows[0] || null;
}

async function ensureGovernorPermission(userId, cityId) {
  const governor = await getActiveGovernor(cityId);
  if (!governor || Number(governor.user_id) !== Number(userId)) {
    throw new HttpError(403, 'Only active governor can perform this action');
  }

  return governor;
}

async function listCityProjects(cityId) {
  const [rows] = await pool.query(
    `SELECT cp.id, cp.city_id, cp.project_type, cp.title, cp.description,
            cp.status, cp.budget_allocated, cp.budget_spent, cp.progress_percent,
            cp.started_at, cp.expected_end_at, cp.completed_at,
            u.username AS started_by
     FROM city_projects cp
     JOIN users u ON u.id = cp.started_by_user_id
     WHERE cp.city_id = ?
     ORDER BY cp.id DESC`,
    [Number(cityId)]
  );

  return rows;
}

async function startProject(userId, cityId, payload) {
  await ensureGovernorPermission(userId, cityId);

  const projectType = String(payload.projectType || '').trim();
  const title = String(payload.title || '').trim();
  const description = String(payload.description || '').trim();
  const budget = Number(payload.budgetAllocated || 0);

  if (!PROJECT_IMPACT[projectType]) {
    throw new HttpError(400, 'Unsupported project type');
  }

  if (!title || budget <= 0) {
    throw new HttpError(400, 'title and budgetAllocated are required');
  }

  const [cityRows] = await pool.query('SELECT id, local_treasury FROM cities WHERE id = ? LIMIT 1', [Number(cityId)]);
  if (!cityRows.length) {
    throw new HttpError(404, 'City not found');
  }

  if (Number(cityRows[0].local_treasury) < budget) {
    throw new HttpError(400, 'Insufficient city treasury');
  }

  const expectedEndAt = new Date(Date.now() + 72 * 3600 * 1000);

  const [insertResult] = await pool.query(
    `INSERT INTO city_projects
     (city_id, started_by_user_id, project_type, title, description, status,
      budget_allocated, budget_spent, progress_percent, started_at, expected_end_at)
     VALUES (?, ?, ?, ?, ?, 'in_progress', ?, 0, 0, NOW(), ?)`,
    [Number(cityId), Number(userId), projectType, title, description || null, budget, expectedEndAt]
  );

  await pool.query('UPDATE cities SET local_treasury = local_treasury - ? WHERE id = ?', [budget, Number(cityId)]);

  return {
    id: insertResult.insertId,
    cityId: Number(cityId),
    projectType,
    title,
    status: 'in_progress',
    progressPercent: 0
  };
}

async function applyProjectImpact(cityId, projectType) {
  await pool.query(`UPDATE cities SET ${PROJECT_IMPACT[projectType]} WHERE id = ?`, [Number(cityId)]);
}

async function addProjectEffort(userId, cityId, projectId, effortPoints) {
  const governor = await ensureGovernorPermission(userId, cityId);
  const safeEffort = Math.min(Math.max(Number(effortPoints) || 0, 1), 25);

  const [projectRows] = await pool.query(
    `SELECT id, city_id, project_type, status, progress_percent, budget_allocated, budget_spent
     FROM city_projects
     WHERE id = ? AND city_id = ?
     LIMIT 1`,
    [Number(projectId), Number(cityId)]
  );

  if (!projectRows.length) {
    throw new HttpError(404, 'Project not found');
  }

  const project = projectRows[0];
  if (project.status !== 'in_progress') {
    throw new HttpError(400, 'Project is not in progress');
  }

  const progressDelta = Number((safeEffort * 1.8).toFixed(2));
  const nextProgress = Math.min(Number(project.progress_percent) + progressDelta, 100);
  const spendDelta = safeEffort * 100;

  let status = 'in_progress';
  let completedAtSql = 'NULL';

  if (nextProgress >= 100) {
    status = 'completed';
    completedAtSql = 'NOW()';
    await applyProjectImpact(cityId, project.project_type);

    await pool.query(
      `UPDATE governors
       SET completed_projects = completed_projects + 1,
           experience = experience + ?,
           reputation = LEAST(reputation + 0.5, 100)
       WHERE id = ?`,
      [safeEffort * 10, governor.id]
    );
  }

  await pool.query(
    `UPDATE city_projects
     SET progress_percent = ?,
         budget_spent = budget_spent + ?,
         status = ?,
         completed_at = ${completedAtSql}
     WHERE id = ?`,
    [nextProgress, spendDelta, status, Number(projectId)]
  );

  return {
    id: Number(projectId),
    cityId: Number(cityId),
    progressPercent: nextProgress,
    status
  };
}

module.exports = {
  getActiveGovernor,
  listCityProjects,
  startProject,
  addProjectEffort
};
