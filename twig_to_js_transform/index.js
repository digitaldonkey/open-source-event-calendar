const fs = require('node:fs').promises;

/*
    Update Twig-Javascript templates
      from the source Twig files used in PHP.

    Only a very few templates are used in frontend rendering:
      public/osec_themes/vortex/twig/[agenda|oneday|month].twig

    DO NOT UPDATE:
      Is mandatory to use twig:"^0.7.2 for to keep old stuff from ai1ec working.

    May be turned off:
      Only applies if "use_frontend_rendering" is checked in Osec Settings.
 */

const twigJsCompile = require('twig/lib/compile');
const escapeStringRegexp = require('escape-string-regexp').default;

const config = {
    tempPath: '../cache/twigjs_tmp2',
    sourcePath: '../public/osec_themes/vortex/twig/',
    replaceFilesPath: '../public/js',
    templates: [
        // E.g public/osec_themes/vortex/twig/agenda.twig
        'oneday',
        'agenda',
        'month',
    ],
    /*
        The files where aggregated by Time.ly using unknown build script.
        So we just replace the Twig template parts, to be able to use frontend rendering.
     */
    additionalReplaces: [
        '../public/js/pages/calendar.js',
    ]
}

const twigOpts = {
    ...twigJsCompile.defaults,
    ...{
        output: config.replaceFilesPath,
        compress: true,
    }
}

async function compileTwigJsTemplates () {
    // Compile templates to twig-js.
    const templates = config.templates.map((template) => config.sourcePath + template + '.twig');
    console.log({sourceTemplates: templates})
    twigJsCompile.compile(twigOpts, templates);
    // Take some time to finish.
    return new Promise(resolve => setTimeout(resolve, 1000));
}

async function updateTwigJsTemplates () {
    await compileTwigJsTemplates();
    console.log('COMPILE DONE')

    for (let template of config.templates) {
        await processTemplate(template);
    }
}

const processTemplate = async (template) => {

    // Load compiled template
    const tempFile = `${config.replaceFilesPath}/${template}.twig.js`;
    const newTemplateRaw = await fs.readFile(
        tempFile,
        'utf8'
    );

    // Load file to replace
    const replaceInPath = `${config.replaceFilesPath}/${template}.js`;

    // All files to process replacements
    const destFiles = structuredClone(config.additionalReplaces);
    destFiles.push(replaceInPath);

    /*
        @var chopComment
          must exist before and after the Twig-js template in the files for this to work!
    */
    const chopComment = '/*REPLACE:' + template + '.twig*/';
    // const chopCommentEscaped = `\/\*REPLACE:${template}\.twig\*\/`;

    // Prepare replacement
    //   Remove wrapper "twig(...);\n" and add chop-comments e.g: "/*REPLACE:'agenda.twig*/"
    let replacement = newTemplateRaw.replace(/^(twig\()/,"");
    replacement = replacement.replace(/(\);\n)$/,"");
    replacement = chopComment + replacement + chopComment;

    // process.stdout.write('replacement' + '\n')
    // process.stdout.write(JSON.stringify(replacement) + '\n')


    const regex = new RegExp(String.raw`${escapeStringRegexp(chopComment)}.+?${escapeStringRegexp(chopComment)}`, "gsm");

    console.log({destFiles})

    for (let destFile of destFiles) {
        await processFile(template, destFile, tempFile, regex, chopComment, replacement);

    }
    // Cleanup temp file.
    return fs.rm(tempFile);
}

const processFile = async (template, destFile, tempFile, regex, chopComment,  replacement) => {
    const fileConetent = await fs.readFile(destFile, 'utf8');

    // Replace in JS files containing twig
    const replacedConetent = await fileConetent.replace(regex, replacement);
    const isWriteable = await isWritable(destFile);

    console.log({
        template,
        sourcePath: config.sourcePath + template + '.twig',
        tempFile,
        destFile,
        chopComment,
        regex,
        isWriteable,
    });
    // process.stdout.write('replacedConetent' + '\n')
    // process.stdout.write(JSON.stringify(replacedConetent) + '\n')

    // Update file.
    return await fs.writeFile(destFile, replacedConetent, 'utf8');
}

const isWritable = async (path) => {
    return new Promise(async (resolve) => {
        await fs.access(path, fs.constants.W_OK)
            .then(() => resolve(true))
            .catch(() => resolve(false))
    })
};

updateTwigJsTemplates();
