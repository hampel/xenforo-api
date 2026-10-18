<?php

declare(strict_types=1);

/**
 * Generate the entity classes from the specifications in resources/.
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
 * resources/dbtech-ecommerce.openapi.json is the other kind: DragonByte eCommerce ships no
 * specification, so that one is transcribed by hand from the add-on's own @api annotations
 * and pinned to the add-on version in its `info`. It describes eCommerce's entities only and
 * refers to core ones - User, Attachment - as `openapi.json#/components/schemas/...`, which
 * resolve to the core namespace below.
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

/**
 * Each specification, where its classes go, their namespace and what their header calls the
 * source - keyed by the spec's file name, which is how a `$ref` in one names another.
 *
 * @var array<string, array{string, string, string}> $runs
 */
$runs = [
    'openapi.json' => [
        'src/Generated/Schema',
        'Hampel\\XenForo\\Api\\Generated\\Schema',
        'the XenForo OpenAPI specification',
    ],
    'dbtech-ecommerce.openapi.json' => [
        'src/Generated/Schema/DBTech/eCommerce',
        'Hampel\\XenForo\\Api\\Generated\\Schema\\DBTech\\eCommerce',
        'the DragonByte eCommerce API specification',
    ],
];

$namespaces = array_map(static fn (array $run): string => $run[1], $runs);

foreach ($runs as $file => [$target, $namespace, $source]) {
    $specFile = $root . '/resources/' . $file;
    $targetDir = $root . '/' . $target;

    $spec = json_decode((string) file_get_contents($specFile), true, 512, JSON_THROW_ON_ERROR);

    $components = is_array($spec) ? ($spec['components'] ?? null) : null;
    $schemas = is_array($components) ? ($components['schemas'] ?? null) : null;

    if (!is_array($spec) || !is_array($schemas)) {
        fwrite(STDERR, "No components.schemas in {$specFile}\n");
        exit(1);
    }

    /** @var array<string, array<mixed>> $schemas */

    if (!is_dir($targetDir) && !mkdir($targetDir, 0o755, true)) {
        fwrite(STDERR, "Could not create {$targetDir}\n");
        exit(1);
    }

    // Anything left over from a previous run for a schema the spec no longer has. Removing it
    // matters more than it looks: a stale entity keeps compiling and keeps passing tests.
    foreach (glob($targetDir . '/*.php') ?: [] as $existing) {
        unlink($existing);
    }

    $info = $spec['info'] ?? null;
    $version = is_array($info) && is_string($info['version'] ?? null) ? $info['version'] : '?';
    $count = 0;

    foreach ($schemas as $name => $schema) {
        $class = className($name);
        file_put_contents(
            $targetDir . '/' . $class . '.php',
            renderClass($class, $name, $schema, $namespace, $source, $version, $namespaces)
        );
        $count++;
    }

    echo "Generated {$count} entities in {$target} from {$file} (version {$version}).\n";
}

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
 * @param  array<string, string>  $namespaces  every run's namespace, by spec file name
 */
function renderClass(
    string $class,
    string $specName,
    array $schema,
    string $namespace,
    string $source,
    string $version,
    array $namespaces,
): string {
    $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];

    $declarations = [];
    $assignments = [];

    foreach ($properties as $field => $definition) {
        if (!is_string($field) || !is_array($definition)) {
            continue;
        }

        [$type, $docType, $expression, $default] = resolve($definition, $field, $namespaces);

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

        namespace {$namespace};

        use Hampel\\XenForo\\Api\\Support\\Cast;

        /**
         * {$summary}
         *
         * Generated from {$source} (version {$version}) by
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
 * @param  array<string, string>  $namespaces  every run's namespace, by spec file name
 * @return array{string, string|null, string, string}  php type, docblock type, the
 *                                                     expression that builds it, default
 */
function resolve(array $definition, string $field, array $namespaces): array
{
    $access = "\$data['{$field}'] ?? null";

    $ref = $definition['$ref'] ?? null;
    if (is_string($ref)) {
        $class = className(basename($ref));

        // `openapi.json#/components/schemas/User` is another spec's entity, so the class is
        // named in full. A ref with no file part is this spec's own, in this namespace.
        $file = strstr($ref, '#', true);
        if ($file !== false && $file !== '') {
            if (!isset($namespaces[$file])) {
                throw new RuntimeException("{$field} refers to {$ref}, and {$file} is not a specification this generates.");
            }

            $class = '\\' . $namespaces[$file] . '\\' . $class;
        }

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
                return resolve($member, $field, $namespaces);
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
