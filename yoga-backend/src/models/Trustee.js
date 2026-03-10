const { DataTypes } = require('sequelize');
const { sequelize } = require('../config/db');

const Trustee = sequelize.define('Trustee', {
    name: {
        type: DataTypes.STRING,
        allowNull: false
    },
    position: {
        type: DataTypes.STRING,
        allowNull: false
    },
    imageName: {
        type: DataTypes.STRING
    },
    isActive: {
        type: DataTypes.BOOLEAN,
        defaultValue: true
    }
});

module.exports = Trustee;
