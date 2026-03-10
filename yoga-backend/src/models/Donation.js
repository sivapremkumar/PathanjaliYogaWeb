const { DataTypes } = require('sequelize');
const { sequelize } = require('../config/db');

const Donation = sequelize.define('Donation', {
    donorName: {
        type: DataTypes.STRING,
        allowNull: false
    },
    email: {
        type: DataTypes.STRING,
        allowNull: false
    },
    phone: {
        type: DataTypes.STRING,
        allowNull: false
    },
    amount: {
        type: DataTypes.DECIMAL(10, 2),
        allowNull: false
    },
    panNumber: {
        type: DataTypes.STRING
    },
    address: {
        type: DataTypes.TEXT
    },
    paymentStatus: {
        type: DataTypes.STRING,
        defaultValue: 'Pending'
    },
    transactionId: {
        type: DataTypes.STRING
    },
    receiptPath: {
        type: DataTypes.STRING
    }
});

module.exports = Donation;
