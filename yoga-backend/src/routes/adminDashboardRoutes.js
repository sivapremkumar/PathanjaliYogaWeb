const express = require('express');
const router = express.Router();
const { getAdminStats } = require('../controllers/adminDashboardController');
const { protect } = require('../middleware/authMiddleware');

router.get('/stats', protect, getAdminStats);

module.exports = router;
