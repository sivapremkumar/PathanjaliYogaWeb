const NewsEvent = require('../models/NewsEvent');

// @desc    Get all news/events
// @route   GET /api/NewsEvent
// @access  Public
const getNewsEvents = async (req, res) => {
    try {
        const items = await NewsEvent.findAll({ order: [['date', 'DESC']] });
        res.json(items);
    } catch (error) {
        res.status(500).json({ message: 'Server Error' });
    }
};

// @desc    Create a news/event
// @route   POST /api/NewsEvent
// @access  Private
const createNewsEvent = async (req, res) => {
    try {
        const { title, description, imagePath, imageUrl, date, type } = req.body;
        const finalImagePath = imagePath || imageUrl || '';
        const finalDate = date || new Date();
        const finalType = type || 'News';

        const newsEvent = await NewsEvent.create({
            title,
            description,
            imagePath: finalImagePath,
            date: finalDate,
            type: finalType
        });
        res.status(201).json(newsEvent);
    } catch (error) {
        console.error("News create error:", error);
        res.status(500).json({ message: 'Failed to create news/event' });
    }
};

module.exports = { getNewsEvents, createNewsEvent };
