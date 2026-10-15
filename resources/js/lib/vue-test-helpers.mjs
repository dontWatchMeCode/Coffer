import ts from 'typescript';

export function evaluateScript(
    source,
    imports,
    props = {},
    returnExpression = 'exports',
    model = null,
) {
    const { outputText } = ts.transpileModule(source, {
        compilerOptions: { module: ts.ModuleKind.CommonJS },
    });

    return new Function(
        'require',
        'exports',
        'defineProps',
        'defineOptions',
        'defineModel',
        `${outputText}\nreturn ${returnExpression};`,
    )(
        (name) => {
            if (!(name in imports)) {
                throw new Error(`Unexpected import: ${name}`);
            }

            return imports[name];
        },
        {},
        () => props,
        () => {},
        () => model,
    );
}
