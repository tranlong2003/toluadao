// server/index.js
const express = require('express');
const helmet = require('helmet');
const cors = require('cors');
require('dotenv').config();

const pool = require('./db');
const { normalize, riskScore } = require('./utils');

const app = express();
app.use(express.json({ limit: '1mb' }));
app.use(helmet());
app.use(cors());

app.get('/api/health', (_,res)=>res.json({ok:true}));

// SEARCH: /api/search?type=phone|bank|link&value=...
app.get('/api/search', async (req, res) => {
  try {
    const { type, value } = req.query;
    if (!['phone','bank','link'].includes(type || '')) return res.status(400).json({error:'type invalid'});
    const value_norm = normalize(type, value);
    const conn = await pool.getConnection();

    // Tìm entity
    const [entities] = await conn.execute(
      'SELECT id FROM entity WHERE type=? AND value_norm=? LIMIT 1',
      [type, value_norm]
    );

    if (entities.length === 0) {
      conn.release();
      return res.json({ level: 'An toàn', stats: { evidences: 0, community: 0, last: null }, evidences: [] });
    }

    const entityId = entities[0].id;

    const [evidences] = await conn.execute(
      `SELECT title, excerpt, source_name AS source, source_url AS url, published_at
       FROM evidence WHERE entity_id=? ORDER BY IFNULL(published_at, created_at) DESC LIMIT 50`,
      [entityId]
    );

    const [rows] = await conn.execute(
      `SELECT COUNT(*) AS c FROM community_report WHERE entity_id=? AND status='approved'`,
      [entityId]
    );
    conn.release();

    const { level } = riskScore(evidences, rows[0].c);
    res.json({
      level,
      stats: { evidences: evidences.length, community: rows[0].c, last: evidences[0]?.published_at || null },
      evidences,
    });
  } catch (e) {
    console.error(e);
    res.status(500).json({ error: 'server_error' });
  }
});

// Gửi báo cáo cộng đồng
app.post('/api/report', async (req, res) => {
  try {
    const { type, value, title, details, loss_vnd } = req.body || {};
    if (!['phone','bank','link'].includes(type || '')) return res.status(400).json({error:'type invalid'});
    if (!title || !value) return res.status(400).json({error:'missing fields'});
    const value_norm = normalize(type, value);

    const conn = await pool.getConnection();
    try {
      // Upsert entity
      const [exist] = await conn.execute(
        'SELECT id FROM entity WHERE type=? AND value_norm=? LIMIT 1',
        [type, value_norm]
      );
      let entityId = exist[0]?.id;
      if (!entityId) {
        const [ins] = await conn.execute(
          'INSERT INTO entity (type, value_norm) VALUES (?,?)',
          [type, value_norm]
        );
        entityId = ins.insertId;
      }
      await conn.execute(
        'INSERT INTO community_report (entity_id, title, details, loss_vnd, status) VALUES (?,?,?,?,?)',
        [entityId, title, details || null, loss_vnd || null, 'pending']
      );
      res.status(201).json({ ok: true });
    } finally {
      conn.release();
    }
  } catch (e) {
    console.error(e);
    res.status(500).json({ error: 'server_error' });
  }
});

app.listen(process.env.PORT, () => {
  console.log(`API listening http://localhost:${process.env.PORT}`);
});
