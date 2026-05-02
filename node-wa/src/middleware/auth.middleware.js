module.exports = function authMiddleware(req, res, next) {
  const expected = process.env.INTERNAL_SECRET;
  const header = req.header('authorization') || '';
  const token = header.replace(/^Bearer\s+/i, '');

  if (!expected || token !== expected) {
    return res.status(401).json({
      success: false,
      message: 'Unauthorized'
    });
  }

  next();
};
