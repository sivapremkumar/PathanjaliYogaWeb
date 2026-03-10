const express = require('express');
const router = express.Router();
const { getRazorpayKey, createDonationOrder, verifyPayment, getDonations } = require('../controllers/donationController');
const { protect } = require('../middleware/authMiddleware');

router.get('/key', getRazorpayKey);
router.post('/order', createDonationOrder);
router.post('/verify', verifyPayment);
router.get('/', protect, getDonations);

module.exports = router;
