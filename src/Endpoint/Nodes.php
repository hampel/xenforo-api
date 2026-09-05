<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint;

use Hampel\XenForo\Api\Generated\Schema\Node;

/**
 * Nodes - the `Nodes` tag in the XenForo API documentation.
 *
 * The node tree is the forum's structure: categories, forums, pages and link forums, all
 * one type of record distinguished by node_type_id. Writing to it needs a super-user key
 * and admin permissions - this is the admin panel's own data.
 */
final class Nodes extends Endpoint
{
    /**
     * The whole node tree.
     *
     * Comes back as a flat list plus a `tree_map` describing the parentage, rather than
     * nested. Both are returned here because reconstructing one from the other is a choice
     * the caller should make rather than one this class should make for them.
     *
     * @return array{nodes: list<Node>, tree_map: array<mixed>}
     */
    public function list(): array
    {
        $response = $this->apiGet('nodes/');

        $nodes = [];
        foreach ($response->array('nodes') as $node) {
            if (is_array($node)) {
                $nodes[] = Node::fromArray($node);
            }
        }

        return ['nodes' => $nodes, 'tree_map' => $response->array('tree_map')];
    }

    /**
     * The node tree flattened into display order, with each node's depth - which is what
     * you want to render a `<select>` of forums.
     *
     * @return array<mixed>
     */
    public function flattened(): array
    {
        return $this->apiGet('nodes/flattened')->array('nodes_flat');
    }

    public function get(int $nodeId): Node
    {
        return Node::fromArray($this->apiGet('nodes/' . $nodeId . '/')->array('node'));
    }

    public function find(int $nodeId): ?Node
    {
        return $this->apiFind('nodes/' . $nodeId . '/', [], 'node', Node::fromArray(...));
    }

    /**
     * Create a node.
     *
     * The node's own fields go in a `node` array and the type-specific ones in `type_data`,
     * which is XenForo's shape rather than a convenience invented here:
     *
     *     $nodes->create('Forum', [
     *         'title' => 'Announcements',
     *         'parent_node_id' => 1,
     *     ]);
     *
     * @param  string  $nodeTypeId  'Forum', 'Category', 'LinkForum', 'Page'
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $typeData
     */
    public function create(string $nodeTypeId, array $node, array $typeData = []): Node
    {
        return Node::fromArray($this->apiPost('nodes/', [
            'node_type_id' => $nodeTypeId,
            'node' => $node,
            'type_data' => $typeData,
        ])->array('node'));
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $typeData
     */
    public function update(int $nodeId, array $node, array $typeData = []): Node
    {
        $payload = ['node' => $node];

        if ($typeData !== []) {
            $payload['type_data'] = $typeData;
        }

        return Node::fromArray($this->apiPost('nodes/' . $nodeId . '/', $payload)->array('node'));
    }

    /**
     * Delete a node.
     *
     * @param  bool  $deleteChildren  delete the node's children too. False moves them up to
     *                                this node's parent rather than deleting them, which is
     *                                the safer default and XenForo's own.
     */
    public function delete(int $nodeId, bool $deleteChildren = false): bool
    {
        return $this->apiDelete('nodes/' . $nodeId . '/', ['delete_children' => $deleteChildren ? '1' : '0'])
            ->isSuccess();
    }
}
