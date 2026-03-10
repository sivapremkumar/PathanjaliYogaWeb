const Inquiry = require('../models/Inquiry');

// @desc    Submit an inquiry
// @route   POST /api/Inquiry
// @access  Public
const submitInquiry = async (req, res) => {
    try {
        const { name, email, phone, subject, message } = req.body;
        const inquiry = await Inquiry.create({ name, email, phone, subject, message });
        res.status(201).json({ message: 'Inquiry submitted successfully', inquiry });
    } catch (error) {
        res.status(500).json({ message: 'Failed to submit inquiry' });
    }
};

// @desc    Get all inquiries
// @route   GET /api/Inquiry
// @access  Private/Admin
const getInquiries = async (req, res) => {
    try {
        const inquiries = await Inquiry.findAll({ order: [['createdAt', 'DESC']] });
        res.json(inquiries);
    } catch (error) {
        res.status(500).json({ message: 'Failed to get inquiries' });
    }
};

module.exports = { submitInquiry, getInquiries };
