const express = require('express');
const router = express.Router();
const { getNewsEvents, createNewsEvent } = require('../controllers/newsEventController');
const { protect } = require('../middleware/authMiddleware');

router.route('/')
    .get(getNewsEvents)
    .post(protect, createNewsEvent);

module.exports = router;
