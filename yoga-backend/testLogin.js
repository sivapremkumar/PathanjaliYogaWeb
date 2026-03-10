fetch('http://localhost:7082/api/Auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username: 'superadmin', password: 'password123' })
})
    .then(async (res) => {
        console.log("Status:", res.status);
        console.log("Response:", await res.text());
    })
    .catch(e => console.error("Error:", e));
