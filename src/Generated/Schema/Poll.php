<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The Poll entity, as the XenForo API returns it.
 *
 * Generated from the XenForo OpenAPI specification (version 1) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class Poll
{
    public readonly ?bool $can_vote;

    public readonly ?bool $has_voted;

    /**
     * List of possible responses with text, vote count (if visible) and whether the API user has voted for each
     *
     * @var array<mixed>
     */
    public readonly array $responses;

    public readonly ?int $poll_id;

    public readonly ?string $question;

    public readonly ?int $voter_count;

    public readonly ?bool $public_votes;

    public readonly ?int $max_votes;

    public readonly ?int $close_date;

    public readonly ?bool $change_vote;

    public readonly ?bool $view_results_unvoted;

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

        $this->can_vote = Cast::bool($data['can_vote'] ?? null);
        $this->has_voted = Cast::bool($data['has_voted'] ?? null);
        $this->responses = Cast::array($data['responses'] ?? null);
        $this->poll_id = Cast::int($data['poll_id'] ?? null);
        $this->question = Cast::string($data['question'] ?? null);
        $this->voter_count = Cast::int($data['voter_count'] ?? null);
        $this->public_votes = Cast::bool($data['public_votes'] ?? null);
        $this->max_votes = Cast::int($data['max_votes'] ?? null);
        $this->close_date = Cast::int($data['close_date'] ?? null);
        $this->change_vote = Cast::bool($data['change_vote'] ?? null);
        $this->view_results_unvoted = Cast::bool($data['view_results_unvoted'] ?? null);
    }

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
