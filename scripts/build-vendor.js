const fs = require('fs');
const path = require('path');

const destDir = path.join(__dirname, '../public/assets/vendor');

if (!fs.existsSync(destDir)) {
    fs.mkdirSync(destDir, { recursive: true });
}

const filesToCopy = [
    { src: 'node_modules/alpinejs/dist/cdn.min.js', dest: 'alpine.min.js' },
    { src: 'node_modules/lucide/dist/umd/lucide.min.js', dest: 'lucide.min.js' }
];

filesToCopy.forEach(f => {
    const srcPath = path.join(__dirname, '..', f.src);
    const destPath = path.join(destDir, f.dest);
    try {
        fs.copyFileSync(srcPath, destPath);
        console.log(`Successfully copied ${f.src} to public/assets/vendor/${f.dest}`);
    } catch (err) {
        console.error(`Error copying ${f.src}:`, err);
        process.exit(1);
    }
});
