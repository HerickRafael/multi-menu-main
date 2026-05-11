/**
 * Minify JS files using Terser
 * 
 * Minifies individual JS files in-place (creates .min.js versions)
 * for public-facing JS files.
 */
const { minify } = require('terser');
const fs = require('fs');
const path = require('path');

const files = [
  // Public JS
  'public/js/lazy-load.js',
  'public/js/image-preload.js',
  'public/js/mobile-optimizations.js',
  'public/js/promo-countdown.js',
  // Admin/shared JS
  'public/assets/js/ui.js',
  'public/assets/js/admin.js',
  'public/assets/js/admin-common.js',
  'public/assets/js/toast-system.js',
  'public/assets/js/skeleton-system.js',
  'public/assets/js/lazy-loading.js',
  // Notification system (extraídos de layout.php)
  'public/assets/js/kds-chime.js',
  'public/assets/js/order-notifications.js',
  // PWA + CSRF (extraídos de layout.php)
  'public/assets/js/admin-pwa.js',
  'public/assets/js/admin-csrf.js',
];

async function run() {
  let totalOriginal = 0;
  let totalMinified = 0;

  for (const file of files) {
    const fullPath = path.resolve(__dirname, '../../', file);
    if (!fs.existsSync(fullPath)) {
      console.log(`⚠ Skip: ${file} (not found)`);
      continue;
    }

    const code = fs.readFileSync(fullPath, 'utf-8');
    const origSize = Buffer.byteLength(code);
    totalOriginal += origSize;

    try {
      const result = await minify(code, {
        compress: { drop_console: false, passes: 2 },
        mangle: true,
        format: { comments: false },
      });

      const minPath = fullPath.replace(/\.js$/, '.min.js');
      fs.writeFileSync(minPath, result.code);
      const minSize = Buffer.byteLength(result.code);
      totalMinified += minSize;

      const savings = ((1 - minSize / origSize) * 100).toFixed(1);
      console.log(`✅ ${file} → ${(origSize / 1024).toFixed(1)}KB → ${(minSize / 1024).toFixed(1)}KB (-${savings}%)`);
    } catch (err) {
      console.error(`❌ ${file}: ${err.message}`);
    }
  }

  console.log(`\nTotal: ${(totalOriginal / 1024).toFixed(1)}KB → ${(totalMinified / 1024).toFixed(1)}KB`);
}

run();
