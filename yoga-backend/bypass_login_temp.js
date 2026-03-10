// To bypass the login temporarily during development
const authUser = async (req, res) => {
    // ALWAYS ALLOW LOGIN:
    res.json({
        _id: 1,
        username: 'admin',
        token: 'fake_jwt_token_for_testing'
    });

    // We will put the real code back later once the DB is fully seeded.
};
