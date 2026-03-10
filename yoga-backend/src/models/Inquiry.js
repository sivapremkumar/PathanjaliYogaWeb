const { DataTypes } = require('sequelize');
const { sequelize } = require('../config/db');

const Inquiry = sequelize.define('Inquiry', {
    name: {
        type: DataTypes.STRING,
        allowNull: false
    },
    email: {
        type: DataTypes.STRING,
        allowNull: false
    },
    phone: {
        type: DataTypes.STRING
    },
    subject: {
        type: DataTypes.STRING,
        allowNull: false
    },
    message: {
        type: DataTypes.TEXT,
        allowNull: false
    },
    isResolved: {
        type: DataTypes.BOOLEAN,
        defaultValue: false
    }
});

module.exports = Inquiry;
