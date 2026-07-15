// Try a list of likely base URLs and report the first that returns HTTP 200 for /register/index.php
const candidates = [
  'http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages',
  'http://localhost/RENTALIN_TENSTING-Copy/src/pages',
  'http://localhost/RENTALIN_TENSTING/src/pages',
  'http://localhost/RENTALIN_TENSTING%20Copy/src/pages',
  'http://localhost',
];

const fetchWithTimeout = async (url, ms = 3000) => {
  const controller = new AbortController();
  const id = setTimeout(() => controller.abort(), ms);
  try {
    const res = await fetch(url, { signal: controller.signal });
    clearTimeout(id);
    return res;
  } catch (e) {
    clearTimeout(id);
    return null;
  }
};

(async () => {
  console.log('Checking candidate base URLs for /register/index.php ...');
  for (const base of candidates) {
    const url = `${base.replace(/\/$/, '')}/register/index.php`;
    process.stdout.write(`- trying ${url} ... `);
    const res = await fetchWithTimeout(url, 3000);
    if (res && res.status === 200) {
      console.log('OK');
      console.log(`Working base URL: ${base}`);
      process.exit(0);
    }
    console.log(res ? `HTTP ${res.status}` : 'no response');
  }
  console.log('No candidate base URL returned 200. Please check your Laragon/Apache configuration and ensure the project is accessible in the browser.');
  process.exit(1);
})();
