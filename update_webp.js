const fs = require('fs');

let php = fs.readFileSync('index.php', 'utf8');
// Only replace assets/ paths
php = php.replace(/assets\/([a-zA-Z0-9_-]+)\.(png|jpg|jpeg)/g, 'assets/$1.webp');
fs.writeFileSync('index.php', php);

let js = fs.readFileSync('support.js', 'utf8');
js = js.replace(/assets\/([a-zA-Z0-9_-]+)\.(png|jpg|jpeg)/g, 'assets/$1.webp');
fs.writeFileSync('support.js', js);
