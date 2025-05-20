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

const twigJsCompile = require('twig/lib/compile')

const config = {
    tempPath: '../cache/twigjs_tmp2',
    sourcePath: '../public/osec_themes/vortex/twig/',
    replaceFilesPath: '../public/js/',
    templates: [
        // E.g public/osec_themes/vortex/twig/agenda.twig
        'agenda',
        'oneday',
        'month',
    ],
    /*
        The files where aggregated by Time.ly using unknown build script.
        So we just replace the Twig template parts, to be able to use frontend rendering.
     */
    //
    //
    // open-source-event-calendar/public/js/scripts/calendar.js
    additionalReplaces: [
        '../public/js/pages/calendar.js',
        '../public/js/scripts/calendar.js'
    ]
}

const twigOpts = {
    ...twigJsCompile.defaults,
    ...{
        output: config.tempPath,
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

    // TODO
    //  FOLLOWING IS SOMEHOW BUGGY, LEADING TO UNUSABLE JS SCRIPTS
    //  Manually replacing works tho.

    // config.templates.forEach(async(template) => {
    //     // Load compiled template
    //     const tempFile = config.tempPath + '/' + template + '.twig.js';
    //     const replacementRaw = await fs.readFile(tempFile, 'utf8');
    //
    //     // Load file to replace
    //     const replaceInPath = config.replaceFilesPath + template + '.js';
    //
    //     // All files to process replacements
    //     const files = structuredClone(config.additionalReplaces);
    //     files.push(replaceInPath);
    //
    //     /*
    //         @var chopComment
    //           must exist before and after the Twig-js template in the files for this to work!
    //     */
    //     const chopComment = '/*REPLACE:' + template + '.twig*/';
    //     const chopCommentEscaped = escapeRegExp(chopComment)
    //
    //     // Prepare replacement
    //     //   Remove wrapper "twig(...);\n" and add chop-comments e.g: "/*REPLACE:'agenda.twig*/"
    //     let replacement = replacementRaw.replace(/^(twig\()/,"");
    //     replacement = replacement.replace(/(\);\n)$/,"");
    //     replacement = chopComment + replacement + chopComment;
    //
    //     // Prepare regex
    //     const regex = new RegExp(String.raw`${chopCommentEscaped}.+?${chopCommentEscaped}`, "gsm");
    //
    //     // Replace in files.
    //     await files.forEach(async (file) => {
    //         const fileConetent = await fs.readFile(file, 'utf8');
    //
    //         // Replace in JS files containing twig
    //         const replacedConetent = fileConetent.replace(regex, replacement);
    //
    //         console.log(replacedConetent, 'replacedConetent')
    //
    //         const isWriteable = await isWritable(file);
    //
    //         console.log({
    //             template,
    //             tempFile,
    //             replaceInPath,
    //             chopComment,
    //             regex,
    //             file,
    //             isWriteable,
    //             // replacedConetent
    //         });
    //         // Update file.
    //         await fs.writeFile(file, replacedConetent, 'utf8');
    //     });
    //
    // })
}

const escapeRegExp = (text) => {
    return text.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
}

const isWritable = async (path) => {
    return new Promise(async (resolve) => {
        await fs.access(path, fs.constants.W_OK)
            .then(() => resolve(true))
            .catch(() => resolve(false))
    })
};

updateTwigJsTemplates();
