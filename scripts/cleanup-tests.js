(async ()=>{
  const BASE = process.env.BASE_URL || 'http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages';
  const tokenFile = './.dev_token';
  const fetch = globalThis.fetch || require('node-fetch');

  if (!require('fs').existsSync(tokenFile)) {
    console.error('.dev_token not found in project root. Create file with a secret token to enable cleanup.');
    process.exit(1);
  }

  const token = require('fs').readFileSync(tokenFile, 'utf8').trim();
  if (!token) {
    console.error('.dev_token is empty');
    process.exit(1);
  }

  const url = `${BASE}/api/cleanup-tests.php`;
  try {
    const form = new URLSearchParams();
    form.append('token', token);
    const res = await fetch(url, { method: 'POST', body: form });
    const text = await res.text();
    console.log('STATUS', res.status);
    console.log(text);
    process.exit(res.ok ? 0 : 1);
  } catch (e) {
    console.error('Request failed', e.message);
    process.exit(1);
  }
})();
