const express = require('express');
const cors = require('cors');
const dotenv = require('dotenv');
const { connectDB } = require('./config/db');

dotenv.config();

const app = express();

// Middleware
app.use(cors());
app.use(express.json());

// Connect to database
connectDB();

// Routes imports
const authRoutes = require('./routes/authRoutes');
const trusteeRoutes = require('./routes/trusteeRoutes');
const donationRoutes = require('./routes/donationRoutes');
const newsEventRoutes = require('./routes/newsEventRoutes');
const inquiryRoutes = require('./routes/inquiryRoutes');
const adminDashboardRoutes = require('./routes/adminDashboardRoutes');

// Use Routes
app.use('/api/Auth', authRoutes);
app.use('/api/Trustee', trusteeRoutes);
app.use('/api/Donation', donationRoutes);
app.use('/api/NewsEvent', newsEventRoutes);
app.use('/api/Inquiry', inquiryRoutes);
app.use('/api/AdminDashboard', adminDashboardRoutes);

// Basic route for testing
app.get('/', (req, res) => {
    res.send('Yoga Trust API is running...');
});

const PORT = process.env.PORT || 7082;
app.listen(PORT, () => console.log(`Server running on port ${PORT}`));
