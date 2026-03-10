const express = require('express');
const router = express.Router();
const { getTrustees, createTrustee, deleteTrustee } = require('../controllers/trusteeController');
const { protect } = require('../middleware/authMiddleware');

router.route('/')
    .get(getTrustees)
    .post(protect, createTrustee);

router.route('/:id')
    .delete(protect, deleteTrustee);

module.exports = router;
