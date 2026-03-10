const Donation = require('../models/Donation');
const Trustee = require('../models/Trustee');
const NewsEvent = require('../models/NewsEvent');
const Inquiry = require('../models/Inquiry');

// @desc    Get Admin Dashboard Stats
// @route   GET /api/AdminDashboard/stats
// @access  Private/Admin
const getAdminStats = async (req, res) => {
    try {
        const donations = await Donation.findAll({ where: { paymentStatus: 'Completed' } });
        const totalDonations = donations.reduce((acc, curr) => acc + Number(curr.amount), 0);

        const donationCount = await Donation.count();
        const trusteeCount = await Trustee.count({ where: { isActive: true } });
        const galleryCount = await NewsEvent.count();
        const newInquiries = await Inquiry.count({ where: { isResolved: false } });

        res.json({
            totalDonations,
            donationCount,
            trusteeCount,
            galleryCount,
            newInquiries
        });
    } catch (error) {
        res.status(500).json({ message: 'Failed to fetch admin stats' });
    }
};

module.exports = { getAdminStats };
