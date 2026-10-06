const fs = require('node:fs').promises;

/*
    Update Twig-Javascript templates
      from the source Twig files used in PHP.

    Only a very few templates are used in frontend rendering:
      public/osec_themes/vortex/twig/[agenda|oneday|month].twig

    Also writes the twig.js runtime (node_modules/twig/twig.min.js) into the bundle, so templates
    and runtime always come from the same twig version. Templates autoescape like PHP Twig.

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
        The calendar bundle is the only served copy of these templates.
     */
    destFiles: [
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
    await updateRuntime();
}

/*
    The twig.js runtime the templates were compiled with, as the module "external_libs/twig".
    twig.min.js is UMD; given a local module object it takes its CommonJS branch.
 */
const updateRuntime = async () => {
    const version = require('twig/package.json').version;
    // BSD-2-Clause: the notice travels with the code. Its pointer to twig.min.js.LICENSE.txt and the
    // source map reference go, neither file ships.
    const license = (await fs.readFile(require.resolve('twig/LICENSE'), 'utf8')).trim().replace(/\*\//g, '* /');
    const runtime = (await fs.readFile(require.resolve('twig/twig.min.js'), 'utf8'))
        .replace(/^\/\*! For license information please see [^*]*\*\/\n?/, '')
        .replace(/\n?\/\/# sourceMappingURL=\S+\s*$/, '')
        .trim();
    const begin = '/*BEGIN:twig.js runtime*/';
    const end = '/*END:twig.js runtime*/';
    const wrapped = begin + 'timely.define("external_libs/twig", [], function () {\n'
        + `/*!\n * twig.js ${version}, https://github.com/twigjs/twig.js\n *\n`
        + license.split('\n').map((line) => (' * ' + line).trimEnd()).join('\n') + '\n */\n'
        + `    // twig.js ${version} (twig.min.js, UMD): its CommonJS branch fills this module object.\n`
        + '    var module = {exports: {}}, exports = module.exports, define;\n'
        + runtime + '\n'
        + '    return module.exports;\n'
        + '})' + end;
    const regex = new RegExp(String.raw`${escapeStringRegexp(begin)}.+?${escapeStringRegexp(end)}`, 'gs');

    for (const destFile of config.destFiles) {
        const content = await fs.readFile(destFile, 'utf8');
        if ((content.match(regex) || []).length !== 1) {
            throw new Error(`Runtime markers not found exactly once in ${destFile}`);
        }
        // A function replacement: the minified runtime contains "$" sequences.
        await fs.writeFile(destFile, content.replace(regex, () => wrapped), 'utf8');
        console.log({runtime: version, destFile});
    }
}

const processTemplate = async (template) => {

    // Load compiled template
    const tempFile = `${config.replaceFilesPath}/${template}.twig.js`;
    const newTemplateRaw = await fs.readFile(
        tempFile,
        'utf8'
    );

    const destFiles = config.destFiles;

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
    // Escape like PHP Twig: the templates mark HTML with |raw and attributes with |e('html_attr').
    if (!replacement.endsWith(', precompiled: true}')) {
        throw new Error(`Unexpected compiled template format: ${template}`);
    }
    replacement = replacement.replace(/, precompiled: true}$/, ', precompiled: true, autoescape: true}');
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
    if ((fileConetent.match(regex) || []).length !== 1) {
        throw new Error(`${chopComment} markers not found exactly once in ${destFile}`);
    }
    // A function replacement: "$" sequences in the template must stay as they are.
    const replacedConetent = fileConetent.replace(regex, () => replacement);
    if (!replacedConetent.includes(`autoescape: true}${chopComment}`)) {
        throw new Error(`${template} is not created with autoescape in ${destFile}`);
    }
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
