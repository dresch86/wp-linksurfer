import path from 'path';
import fs from 'fs-extra';
import postcss from 'postcss';
import cssnano from 'cssnano';
import readdirp from 'readdirp';
import * as Terser from 'terser';
import autoprefixer from 'autoprefixer';

const pkg = JSON.parse(fs.readFileSync(path.resolve(__dirname, "../package.json"), "utf8"));
const pthBuild = path.resolve(__dirname, '../', pkg.dirs.build, pkg.name);
const pthSrc = path.resolve(__dirname, '../', pkg.dirs.src);

readdirp(pthSrc, {type: 'files'})
    .on('data', (entry) => {
        let extType = path.extname(entry.basename);

        if (extType == '.css') {
            fs.readFile(entry.fullPath, (err, cssIn) => {
                if (err !== null)
                {
                    console.log(err);
                }

                var pthCSSOut = pthBuild + path.sep + entry.path;

                postcss([autoprefixer(), cssnano])
                .process(cssIn, {from: entry.fullPath, to: pthCSSOut})
                .then(result => {
                    fs.outputFile(pthCSSOut, result.css)
                    .catch((err) => {console.log(err)});

                    if (result.map) {
                        fs.outputFile((pthCSSOut + '.map'), result.map)
                        .catch((err) => {console.log(err)});
                    }
                });
            });
        }
        else if (extType == '.js') {
            var pthJSOut = pthBuild + path.sep + entry.path;
            fs.outputFile(pthJSOut, Terser.minify(fs.readFileSync(entry.fullPath, "utf8")).code);
        }
        else {
            var sDestPath = pthBuild + path.sep + entry.path;
            fs.copy(entry.fullPath, sDestPath)
            .catch((err) => {console.log(err)});
        }
    })
    .on('warn', (warn) => {
        console.log("Warn: ", warn);
    })
    .on('error', (err) => {
        console.log("Error: ", err);
    })
    .on('end', () => {
        console.log('Plugin code built!');
    }
);