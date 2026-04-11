/**
 * Playwright Global Teardown
 *
 * This file runs once after all tests complete.
 * Used to clean up test environment.
 *
 * @package FlavorPlatform
 */

const fs = require('fs');
const path = require('path');

/**
 * Global teardown function
 *
 * @param {Object} config - Playwright config
 */
async function globalTeardown(config) {
    console.log('\n=== Global Teardown ===');

    // Clean up auth state files
    const authDir = path.join(__dirname, '.auth');
    if (fs.existsSync(authDir)) {
        console.log('Cleaning up auth state...');
        fs.rmSync(authDir, { recursive: true, force: true });
    }

    // Log test summary if available
    const resultsPath = path.join(
        process.cwd(),
        'playwright-report',
        'results.json'
    );
    if (fs.existsSync(resultsPath)) {
        try {
            const results = JSON.parse(fs.readFileSync(resultsPath, 'utf-8'));
            console.log('\nTest Summary:');
            console.log(`  Total: ${results.stats?.expected || 0}`);
            console.log(`  Passed: ${results.stats?.expected || 0}`);
            console.log(`  Failed: ${results.stats?.unexpected || 0}`);
            console.log(`  Skipped: ${results.stats?.skipped || 0}`);
        } catch (error) {
            // Ignore JSON parse errors
        }
    }

    console.log('=== Global Teardown Complete ===\n');
}

module.exports = globalTeardown;
