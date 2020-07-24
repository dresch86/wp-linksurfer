import path from 'path';
import fs from 'fs-extra';
import archiver from 'archiver';

const pkg = JSON.parse(fs.readFileSync(path.resolve(__dirname, "../package.json"), "utf8"));
const pthBuild = path.resolve(__dirname, '../', pkg.dirs.build);

if (!fs.existsSync(pthBuild)) {
    console.log('Build directory not found!');
    process.exit(1);
}

var zipArchiveOutput = fs.createWriteStream(pthBuild + path.sep + (pkg.name + '.zip'));
var zipArchiveHandler = archiver('zip', {
  zlib: { level: 9 } // Compression level...
});

zipArchiveOutput.on('close', () => {
    console.log('ZIP archiver wrote ' + zipArchiveHandler.pointer() + ' bytes...');
    console.log('Finished processing plugin files!');
});

zipArchiveHandler.on('warning', (err) => {
    if (err.code === 'ENOENT') {
        console.log(err);
    } else {
        throw err;
    }
});
  
zipArchiveHandler.on('error', (err) => {console.log(err)});
zipArchiveHandler.pipe(zipArchiveOutput);

zipArchiveHandler.directory(pthBuild + path.sep + pkg.name + path.sep, false);
zipArchiveHandler.finalize();