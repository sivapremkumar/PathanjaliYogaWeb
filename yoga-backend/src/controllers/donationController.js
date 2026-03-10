const Donation = require('../models/Donation');
const Razorpay = require('razorpay');
const crypto = require('crypto');

// Initialize Razorpay
const razorpay = new Razorpay({
    key_id: process.env.RAZORPAY_KEY_ID,
    key_secret: process.env.RAZORPAY_KEY_SECRET,
});

// @desc    Get Razorpay Client Key
// @route   GET /api/Donation/key
// @access  Public
const getRazorpayKey = (req, res) => {
    res.json({ key: process.env.RAZORPAY_KEY_ID });
};

// @desc    Create a donation order
// @route   POST /api/Donation/order
// @access  Public
const createDonationOrder = async (req, res) => {
    try {
        const { amount } = req.body;

        const options = {
            amount: amount * 100, // amount in smallest currency unit (paise)
            currency: 'INR',
            receipt: 'ORDER_' + Date.now(),
        };

        const order = await razorpay.orders.create(options);

        res.status(201).json({ message: 'Order created', orderId: order.id, amount: order.amount });
    } catch (error) {
        console.error(error);
        res.status(500).json({ message: 'Failed to create order' });
    }
};

// @desc    Verify payment and save donation
// @route   POST /api/Donation/verify
// @access  Public
const verifyPayment = async (req, res) => {
    try {
        const { donorName, email, phone, amount, panNumber, address, orderId, paymentId, signature } = req.body;

        const body = orderId + "|" + paymentId;
        const expectedSignature = crypto.createHmac('sha256', process.env.RAZORPAY_KEY_SECRET)
            .update(body.toString())
            .digest('hex');

        if (expectedSignature === signature) {
            // Payment verified, save to database
            const donation = await Donation.create({
                donorName, email, phone, amount, panNumber, address, paymentStatus: 'Completed', transactionId: paymentId
            });
            res.status(201).json({ message: 'Payment verified successfully', donation });
        } else {
            res.status(400).json({ message: 'Invalid payment signature' });
        }
    } catch (error) {
        console.error(error);
        res.status(500).json({ message: 'Failed to verify payment' });
    }
};

// @desc    Get all donations
// @route   GET /api/Donation
// @access  Private/Admin
const getDonations = async (req, res) => {
    try {
        const donations = await Donation.findAll({ order: [['createdAt', 'DESC']] });
        res.json(donations);
    } catch (error) {
        res.status(500).json({ message: 'Failed to get donations' });
    }
};

module.exports = { getRazorpayKey, createDonationOrder, verifyPayment, getDonations };
