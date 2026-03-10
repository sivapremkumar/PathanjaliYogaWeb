const Trustee = require('../models/Trustee');

// @desc    Get all trustees
// @route   GET /api/Trustee
// @access  Public
const getTrustees = async (req, res) => {
    try {
        const trustees = await Trustee.findAll();
        res.json(trustees);
    } catch (error) {
        res.status(500).json({ message: 'Server Error' });
    }
};

// @desc    Create a trustee
// @route   POST /api/Trustee
// @access  Private
const createTrustee = async (req, res) => {
    try {
        const { name, position, imageName, isActive } = req.body;
        const trustee = await Trustee.create({ name, position, imageName, isActive });
        res.status(201).json(trustee);
    } catch (error) {
        res.status(500).json({ message: 'Failed to create trustee' });
    }
};

// @desc    Delete a trustee
// @route   DELETE /api/Trustee/:id
// @access  Private
const deleteTrustee = async (req, res) => {
    try {
        const trustee = await Trustee.findByPk(req.params.id);
        if (trustee) {
            await trustee.destroy();
            res.json({ message: 'Trustee removed' });
        } else {
            res.status(404).json({ message: 'Trustee not found' });
        }
    } catch (error) {
        res.status(500).json({ message: 'Server error' });
    }
};

module.exports = { getTrustees, createTrustee, deleteTrustee };
