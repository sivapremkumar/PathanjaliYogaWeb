const AdminUser = require('../models/AdminUser');
const jwt = require('jsonwebtoken');

const generateToken = (id) => {
    return jwt.sign({ id }, process.env.JWT_SECRET, { expiresIn: '30d' });
};

// @desc    Auth user & get token
// @route   POST /api/Auth/login
// @access  Public
const authUser = async (req, res) => {
    try {
        const { username, password } = req.body;
        console.log(`Login attempt for username: ${username}`);

        let user = await AdminUser.findOne({ where: { username } });

        // AUTO-SETUP: If this is the very first time logging in and no admin exists, create it
        if (!user) {
            console.log(`User ${username} not found. Creating a new admin user...`);
            user = await AdminUser.create({ username, password });
            console.log(`User ${username} created successfully.`);
        }

        const isMatch = await user.matchPassword(password);
        console.log(`Password match for ${username}: ${isMatch}`);

        if (user && isMatch) {
            console.log(`Login successful for ${username}. Returning token.`);
            res.json({
                _id: user.id,
                username: user.username,
                token: generateToken(user.id)
            });
        } else {
            console.log(`Login failed for ${username}: Invalid password.`);
            res.status(401).json({ message: 'Invalid username or password' });
        }
    } catch (error) {
        console.error("Error during authUser:", error);
        res.status(500).json({ message: 'Server error during login', error: error.message });
    }
};

// @desc    Register a new admin (Setup purpose only, maybe disabled later)
// @route   POST /api/Auth/register
const registerUser = async (req, res) => {
    const { username, password } = req.body;

    const userExists = await AdminUser.findOne({ where: { username } });

    if (userExists) {
        res.status(400).json({ message: 'User already exists' });
        return;
    }

    const user = await AdminUser.create({ username, password });

    if (user) {
        res.status(201).json({
            _id: user.id,
            username: user.username,
            token: generateToken(user.id)
        });
    } else {
        res.status(400).json({ message: 'Invalid user data' });
    }
};

module.exports = { authUser, registerUser };
