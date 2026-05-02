const router = require('express').Router();
const controller = require('../controllers/messageController');

router.post('/send', controller.send);

module.exports = router;
