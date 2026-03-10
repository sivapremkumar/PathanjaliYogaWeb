const { DataTypes } = require('sequelize');
const { sequelize } = require('../config/db');

const NewsEvent = sequelize.define('NewsEvent', {
    title: {
        type: DataTypes.STRING,
        allowNull: false
    },
    description: {
        type: DataTypes.TEXT,
        allowNull: false
    },
    imagePath: {
        type: DataTypes.STRING
    },
    date: {
        type: DataTypes.DATE,
        allowNull: false
    },
    type: {
        type: DataTypes.ENUM('News', 'Event'),
        defaultValue: 'News'
    }
});

module.exports = NewsEvent;
