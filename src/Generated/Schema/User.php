<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema;

use Hampel\XenForo\Api\Support\Cast;

/**
 * Information about the user. Different information will be included based on permissions and verbosity.
 *
 * Generated from the XenForo OpenAPI specification (version 1) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class User implements \JsonSerializable
{
    public readonly ?string $about;

    public readonly ?bool $activity_visible;

    /** The user's current age. Only included if available. */
    public readonly ?int $age;

    /** @var array<mixed> */
    public readonly array $alert_optout;

    public readonly ?string $allow_post_profile;

    public readonly ?string $allow_receive_news_feed;

    public readonly ?string $allow_send_personal_conversation;

    public readonly ?string $allow_view_identities;

    public readonly ?string $allow_view_profile;

    /**
     * Maps from size types to URL.
     *
     * @var array<string, mixed>
     */
    public readonly array $avatar_urls;

    /**
     * Maps from size types to URL.
     *
     * @var array<string, mixed>
     */
    public readonly array $profile_banner_urls;

    public readonly ?bool $can_ban;

    public readonly ?bool $can_converse;

    public readonly ?bool $can_edit;

    public readonly ?bool $can_follow;

    public readonly ?bool $can_ignore;

    public readonly ?bool $can_post_profile;

    public readonly ?bool $can_view_profile;

    public readonly ?bool $can_view_profile_posts;

    public readonly ?bool $can_warn;

    public readonly ?bool $content_show_signature;

    public readonly ?string $creation_watch_state;

    /**
     * Map of custom field keys and values.
     *
     * @var array<string, mixed>
     */
    public readonly array $custom_fields;

    /** Will have a value if a custom title has been specifically set; prefer user_title instead. */
    public readonly ?string $custom_title;

    /**
     * Date of birth with year, month and day keys.
     *
     * @var array<string, mixed>
     */
    public readonly array $dob;

    public readonly ?string $email;

    public readonly ?bool $email_on_conversation;

    public readonly ?string $gravatar;

    public readonly ?bool $interaction_watch_state;

    public readonly ?bool $is_admin;

    public readonly ?bool $is_banned;

    public readonly ?bool $is_discouraged;

    /** True if the visitor is following this user. Only included if visitor is not a guest. */
    public readonly ?bool $is_followed;

    /** True if the visitor is ignoring this user. Only included if visitor is not a guest. */
    public readonly ?bool $is_ignored;

    public readonly ?bool $is_moderator;

    public readonly ?bool $is_super_admin;

    /** Unix timestamp of user's last activity, if available. */
    public readonly ?int $last_activity;

    public readonly ?string $location;

    public readonly ?bool $push_on_conversation;

    /** @var array<mixed> */
    public readonly array $push_optout;

    public readonly ?bool $receive_admin_email;

    /** @var array<mixed> */
    public readonly array $secondary_group_ids;

    public readonly ?bool $show_dob_date;

    public readonly ?bool $show_dob_year;

    public readonly ?string $signature;

    public readonly ?string $timezone;

    public readonly ?bool $use_tfa;

    public readonly ?int $user_group_id;

    public readonly ?string $user_state;

    public readonly ?string $user_title;

    public readonly ?bool $visible;

    /** Current warning points. */
    public readonly ?int $warning_points;

    public readonly ?string $website;

    public readonly ?string $view_url;

    public readonly ?int $user_id;

    public readonly ?string $username;

    public readonly ?int $message_count;

    public readonly ?int $question_solution_count;

    public readonly ?int $register_date;

    public readonly ?int $trophy_points;

    public readonly ?bool $is_staff;

    public readonly ?int $reaction_score;

    public readonly ?int $vote_score;

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

        $this->about = Cast::string($data['about'] ?? null);
        $this->activity_visible = Cast::bool($data['activity_visible'] ?? null);
        $this->age = Cast::int($data['age'] ?? null);
        $this->alert_optout = Cast::array($data['alert_optout'] ?? null);
        $this->allow_post_profile = Cast::string($data['allow_post_profile'] ?? null);
        $this->allow_receive_news_feed = Cast::string($data['allow_receive_news_feed'] ?? null);
        $this->allow_send_personal_conversation = Cast::string($data['allow_send_personal_conversation'] ?? null);
        $this->allow_view_identities = Cast::string($data['allow_view_identities'] ?? null);
        $this->allow_view_profile = Cast::string($data['allow_view_profile'] ?? null);
        $this->avatar_urls = Cast::array($data['avatar_urls'] ?? null);
        $this->profile_banner_urls = Cast::array($data['profile_banner_urls'] ?? null);
        $this->can_ban = Cast::bool($data['can_ban'] ?? null);
        $this->can_converse = Cast::bool($data['can_converse'] ?? null);
        $this->can_edit = Cast::bool($data['can_edit'] ?? null);
        $this->can_follow = Cast::bool($data['can_follow'] ?? null);
        $this->can_ignore = Cast::bool($data['can_ignore'] ?? null);
        $this->can_post_profile = Cast::bool($data['can_post_profile'] ?? null);
        $this->can_view_profile = Cast::bool($data['can_view_profile'] ?? null);
        $this->can_view_profile_posts = Cast::bool($data['can_view_profile_posts'] ?? null);
        $this->can_warn = Cast::bool($data['can_warn'] ?? null);
        $this->content_show_signature = Cast::bool($data['content_show_signature'] ?? null);
        $this->creation_watch_state = Cast::string($data['creation_watch_state'] ?? null);
        $this->custom_fields = Cast::array($data['custom_fields'] ?? null);
        $this->custom_title = Cast::string($data['custom_title'] ?? null);
        $this->dob = Cast::array($data['dob'] ?? null);
        $this->email = Cast::string($data['email'] ?? null);
        $this->email_on_conversation = Cast::bool($data['email_on_conversation'] ?? null);
        $this->gravatar = Cast::string($data['gravatar'] ?? null);
        $this->interaction_watch_state = Cast::bool($data['interaction_watch_state'] ?? null);
        $this->is_admin = Cast::bool($data['is_admin'] ?? null);
        $this->is_banned = Cast::bool($data['is_banned'] ?? null);
        $this->is_discouraged = Cast::bool($data['is_discouraged'] ?? null);
        $this->is_followed = Cast::bool($data['is_followed'] ?? null);
        $this->is_ignored = Cast::bool($data['is_ignored'] ?? null);
        $this->is_moderator = Cast::bool($data['is_moderator'] ?? null);
        $this->is_super_admin = Cast::bool($data['is_super_admin'] ?? null);
        $this->last_activity = Cast::int($data['last_activity'] ?? null);
        $this->location = Cast::string($data['location'] ?? null);
        $this->push_on_conversation = Cast::bool($data['push_on_conversation'] ?? null);
        $this->push_optout = Cast::array($data['push_optout'] ?? null);
        $this->receive_admin_email = Cast::bool($data['receive_admin_email'] ?? null);
        $this->secondary_group_ids = Cast::array($data['secondary_group_ids'] ?? null);
        $this->show_dob_date = Cast::bool($data['show_dob_date'] ?? null);
        $this->show_dob_year = Cast::bool($data['show_dob_year'] ?? null);
        $this->signature = Cast::string($data['signature'] ?? null);
        $this->timezone = Cast::string($data['timezone'] ?? null);
        $this->use_tfa = Cast::bool($data['use_tfa'] ?? null);
        $this->user_group_id = Cast::int($data['user_group_id'] ?? null);
        $this->user_state = Cast::string($data['user_state'] ?? null);
        $this->user_title = Cast::string($data['user_title'] ?? null);
        $this->visible = Cast::bool($data['visible'] ?? null);
        $this->warning_points = Cast::int($data['warning_points'] ?? null);
        $this->website = Cast::string($data['website'] ?? null);
        $this->view_url = Cast::string($data['view_url'] ?? null);
        $this->user_id = Cast::int($data['user_id'] ?? null);
        $this->username = Cast::string($data['username'] ?? null);
        $this->message_count = Cast::int($data['message_count'] ?? null);
        $this->question_solution_count = Cast::int($data['question_solution_count'] ?? null);
        $this->register_date = Cast::int($data['register_date'] ?? null);
        $this->trophy_points = Cast::int($data['trophy_points'] ?? null);
        $this->is_staff = Cast::bool($data['is_staff'] ?? null);
        $this->reaction_score = Cast::int($data['reaction_score'] ?? null);
        $this->vote_score = Cast::int($data['vote_score'] ?? null);
    }

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * What json_encode() emits: the payload as it arrived, and nothing else.
     *
     * Not the typed fields. Every field here is nullable, so serialising them
     * would render a field the credential was not allowed to see as null - and
     * XenForo omits those rather than blanking them, a distinction this package
     * keeps everywhere else. It would also drop any field an add-on added, which
     * $raw exists to keep. $raw round-trips through fromArray(); the typed set
     * does not.
     *
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->raw;
    }
}
