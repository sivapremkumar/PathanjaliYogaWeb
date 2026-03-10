fetch('http://localhost:7082/api/Auth/register', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username: 'admin', password: 'password123' })
}).then(r => r.json()).then(data => console.log('Admin User Created:', data)).catch(e => console.error(e));
