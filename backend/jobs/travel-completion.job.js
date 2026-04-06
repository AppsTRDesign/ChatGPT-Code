const pool = require('../core/db');

async function runTravelCompletionJob() {
  const [rows] = await pool.query(
    `SELECT id, user_id, arrival_city_id
     FROM travels
     WHERE travel_status = 'in_progress' AND end_time <= NOW()
     LIMIT 500`
  );

  if (!rows.length) return { processed: 0 };

  const connection = await pool.getConnection();
  try {
    await connection.beginTransaction();

    for (const row of rows) {
      await connection.query(
        `UPDATE travels
         SET travel_status = 'completed', completed_at = NOW()
         WHERE id = ? AND travel_status = 'in_progress'`,
        [Number(row.id)]
      );

      await connection.query(
        `UPDATE users u
         JOIN cities c ON c.id = ?
         SET u.current_city_id = c.id,
             u.current_country_id = c.country_id
         WHERE u.id = ?`,
        [Number(row.arrival_city_id), Number(row.user_id)]
      );
    }

    await connection.commit();
    return { processed: rows.length };
  } catch (error) {
    await connection.rollback();
    throw error;
  } finally {
    connection.release();
  }
}

module.exports = {
  runTravelCompletionJob
};
