const router = require('express').Router();
const controller = require('../controllers/sessionController');

router.post('/connect', controller.connect);
router.get('/qr', controller.qr);
router.get('/status', controller.status);
router.get('/groups', controller.groups);
router.get('/contacts', controller.contacts);
router.post('/disconnect', controller.disconnect);

module.exports = router;
