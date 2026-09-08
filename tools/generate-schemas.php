<?php

declare(strict_types=1);

/**
 * Generate src/Generated/Schema/*.php from resources/openapi.json.
 *
 *     composer generate
 *
 * WHY GENERATE THESE AND HAND-WRITE THE RESOURCES
 *
 * The spec describes 33 entities carrying some 640 fields between them. Typing those by
 * hand is work with no judgement in it, and every one is a chance to mistype a field name
 * in a way no test would catch. The endpoints are the opposite: what makes a client
 * pleasant to use is deciding that fetching a thread's posts is threads()->posts($id) and
 * not a generic call with a path in it, and no generator has an opinion about that.
 *
 * WHERE THE SPEC COMES FROM
 *
 * https://github.com/xenforo-ltd/docs, static/api/openapi.json. It is XenForo's
 * documentation repository rather than a versioned artefact of the product, so the copy in
 * resources/ is pinned deliberately: regenerating means updating that file first, and
 * recording which commit it came from in the changelog.
 *
 * WHAT THE GENERATED CLASSES DELIBERATELY DO NOT DO
 *
 * They do not validate. Every field is nullable and every absent field reads as null,
 * because a XenForo API result varies by verbosity, by the acting user's permissions and
 * by which add-ons the forum has installed - see the note on Cast. Each entity also keeps
 * the response array it was built from in ->raw, so a field an add-on added to an entity
 * is never lost just because it is not in the spec.
 */

$root = dirname(__DIR__);
$specFile = $root . '/resources/openapi.json';
$targetDir = $root . '/src/Generated/Schema';

$spec = json_decode((string) file_get_contents($specFile), true, 512, JSON_THROW_ON_ERROR);

if (!is_array($spec) || !isset($spec['components']['schemas']) || !is_array($spec['components']['schemas'])) {
    fwrite(STDERR, "No components.schemas in {$specFile}\n");
    exit(1);
}

/** @var array<string, array<mixed>> $schemas */
$schemas = $spec['components']['schemas'];

if (!is_dir($targetDir) && !mkdir($targetDir, 0o755, true)) {
    fwrite(STDERR, "Could not create {$targetDir}\n");
    exit(1);
}

// Anything left over from a previous run for a schema the spec no longer has. Removing it
// matters more than it looks: a stale entity keeps compiling and keeps passing tests.
foreach (glob($targetDir . '/*.php') ?: [] as $existing) {
    unlink($existing);
}

$version = is_string($spec['info']['version'] ?? null) ? $spec['info']['version'] : '?';
$count = 0;

foreach ($schemas as $name => $schema) {
    $class = className($name);
    file_put_contents($targetDir . '/' . $class . '.php', renderClass($class, $name, $schema, $version));
    $count++;
}

echo "Generated {$count} entities in src/Generated/Schema from OpenAPI {$version}.\n";

/**
 * XFMG_MediaItem and XFRM_ResourceItem are the add-on entities. Their spec names are
 * already valid PHP, and renaming them to something prettier would break the one property
 * a reader has - being able to grep the spec for the name in the code.
 */
function className(string $name): string
{
    return str_replace(['-', ' '], '', $name);
}

/**
 * @param  array<mixed>  $schema
 */
function renderClass(string $class, string $specName, array $schema, string $version): string
{
    $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];

    $declarations = [];
    $assignments = [];

    foreach ($properties as $field => $definition) {
        if (!is_string($field) || !is_array($definition)) {
            continue;
        }

        [$type, $docType, $expression, $default] = resolve($definition, $field);

        $description = is_string($definition['description'] ?? null) ? trim($definition['description']) : '';

        $doc = [];
        if ($description !== '') {
            $doc[] = $description;
        }
        if ($docType !== null) {
            $doc[] = '@var ' . $docType;
        }

        if ($doc !== []) {
            $declarations[] = count($doc) === 1
                ? "    /** {$doc[0]} */"
                : "    /**\n     * " . implode("\n     *\n     * ", $doc) . "\n     */";
        }

        $declarations[] = sprintf('    public readonly %s $%s;', $type, $field);
        $declarations[] = '';

        $assignments[] = sprintf('        $this->%s = %s;', $field, $expression);

        unset($default);
    }

    // Trailing blank line from the loop.
    if ($declarations !== [] && end($declarations) === '') {
        array_pop($declarations);
    }

    $description = is_string($schema['description'] ?? null) ? trim($schema['description']) : '';
    $summary = $description !== '' ? $description : "The {$specName} entity, as the XenForo API returns it.";

    $body = $declarations === []
        ? ''
        : implode("\n", $declarations) . "\n\n";

    $assigned = $assignments === []
        ? ''
        : "\n" . implode("\n", $assignments) . "\n    ";

    return <<<PHP
        <?php

        declare(strict_types=1);

        namespace Hampel\\XenForo\\Api\\Generated\\Schema;

        use Hampel\\XenForo\\Api\\Support\\Cast;

        /**
         * {$summary}
         *
         * Generated from the XenForo OpenAPI specification (version {$version}) by
         * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
         *
         * Every field is nullable because a XenForo API result is not a fixed record: it
         * varies by verbosity, by what the acting user may see, and by which add-ons the
         * forum has installed. Fields this spec does not describe - one an add-on added by
         * extending the entity's toApiResult() - are still available in \$raw.
         */
        final class {$class} implements \\JsonSerializable
        {
        {$body}    /**
             * The response data this entity was built from, exactly as it arrived.
             *
             * @var array<mixed>
             */
            public readonly array \$raw;

            /**
             * @param  array<mixed>  \$data
             */
            public function __construct(array \$data)
            {
                \$this->raw = \$data;
        {$assigned}}

            /**
             * @param  array<mixed>  \$data
             */
            public static function fromArray(array \$data): self
            {
                return new self(\$data);
            }

            /**
             * What json_encode() emits: the payload as it arrived, and nothing else.
             *
             * Not the typed fields. Every field here is nullable, so serialising them
             * would render a field the credential was not allowed to see as null - and
             * XenForo omits those rather than blanking them, a distinction this package
             * keeps everywhere else. It would also drop any field an add-on added, which
             * \$raw exists to keep. \$raw round-trips through fromArray(); the typed set
             * does not.
             *
             * @return array<mixed>
             */
            public function jsonSerialize(): array
            {
                return \$this->raw;
            }
        }

        PHP;
}

/**
 * @param  array<mixed>  $definition
 * @return array{string, string|null, string, string}  php type, docblock type, the
 *                                                     expression that builds it, default
 */
function resolve(array $definition, string $field): array
{
    $access = "\$data['{$field}'] ?? null";

    $ref = $definition['$ref'] ?? null;
    if (is_string($ref)) {
        $class = className(basename($ref));

        return [
            '?' . $class,
            null,
            sprintf('is_array($data[\'%s\'] ?? null) ? %s::fromArray($data[\'%s\']) : null', $field, $class, $field),
            'null',
        ];
    }

    // The spec's only union is a nullable string, written the OpenAPI 3.1 way. Reducing it
    // to the non-null member is exactly right here, since every field is nullable anyway.
    $union = $definition['oneOf'] ?? $definition['anyOf'] ?? null;
    if (is_array($union)) {
        foreach ($union as $member) {
            if (is_array($member) && ($member['type'] ?? null) !== 'null') {
                return resolve($member, $field);
            }
        }
    }

    $type = is_string($definition['type'] ?? null) ? $definition['type'] : 'string';

    return match ($type) {
        'integer' => ['?int', null, "Cast::int({$access})", 'null'],
        'number' => ['?float', null, "Cast::float({$access})", 'null'],
        'boolean' => ['?bool', null, "Cast::bool({$access})", 'null'],
        'object' => ['array', 'array<string, mixed>', "Cast::array({$access})", '[]'],
        'array' => ['array', 'array<mixed>', "Cast::array({$access})", '[]'],
        default => ['?string', null, "Cast::string({$access})", 'null'],
    };
}
