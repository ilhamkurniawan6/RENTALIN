const { spawnSync } = require('child_process');
const path = require('path');

module.exports = async () => {
  const script = path.join(__dirname, 'cleanup-tests.js');
  try {
    console.log('Running Playwright teardown: cleanup-tests.js');
    const res = spawnSync(process.execPath, [script], { stdio: 'inherit' });
    if (res.error) {
      console.error('Error running cleanup script:', res.error);
      return;
    }
    if (res.status !== 0) {
      console.error('cleanup-tests.js exited with code', res.status);
    } else {
      console.log('cleanup-tests.js completed successfully');
    }
  } catch (e) {
    console.error('Exception during teardown:', e);
  }
};
