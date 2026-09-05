<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The Node entity, as the XenForo API returns it.
 *
 * Generated from the XenForo OpenAPI specification (version 1) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class Node
{
    /**
     * A list of breadcrumbs for this node, including the node_id, title, and node_type_id
     *
     * @var array<mixed>
     */
    public readonly array $breadcrumbs;

    /**
     * Data related to the specific node type this represents. Contents will vary significantly.
     *
     * @var array<string, mixed>
     */
    public readonly array $type_data;

    public readonly ?string $view_url;

    public readonly ?int $node_id;

    public readonly ?string $title;

    public readonly ?string $node_name;

    public readonly ?string $description;

    public readonly ?string $node_type_id;

    public readonly ?int $parent_node_id;

    public readonly ?int $display_order;

    public readonly ?bool $display_in_list;

    /**
     * The response data this entity was built from, exactly as it arrived.
     *
     * @var array<mixed>
     */
    public readonly array $raw;

    /**
     * @param  array<mixed>  $data
     */
    public function __construct(array $data)
    {
        $this->raw = $data;

        $this->breadcrumbs = Cast::array($data['breadcrumbs'] ?? null);
        $this->type_data = Cast::array($data['type_data'] ?? null);
        $this->view_url = Cast::string($data['view_url'] ?? null);
        $this->node_id = Cast::int($data['node_id'] ?? null);
        $this->title = Cast::string($data['title'] ?? null);
        $this->node_name = Cast::string($data['node_name'] ?? null);
        $this->description = Cast::string($data['description'] ?? null);
        $this->node_type_id = Cast::string($data['node_type_id'] ?? null);
        $this->parent_node_id = Cast::int($data['parent_node_id'] ?? null);
        $this->display_order = Cast::int($data['display_order'] ?? null);
        $this->display_in_list = Cast::bool($data['display_in_list'] ?? null);
    }

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
