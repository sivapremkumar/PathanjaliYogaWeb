const express = require('express');
const router = express.Router();
const { submitInquiry, getInquiries } = require('../controllers/inquiryController');
const { protect } = require('../middleware/authMiddleware');

router.route('/')
    .post(submitInquiry)
    .get(protect, getInquiries);

module.exports = router;
