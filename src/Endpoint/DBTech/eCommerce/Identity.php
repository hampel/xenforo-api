<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint\DBTech\eCommerce;

use Hampel\XenForo\Api\Exception\InvalidArgumentException;

/**
 * Who an eCommerce request is on behalf of: the customer's board, and the platform it runs.
 *
 * EVERY REQUEST NEEDS ONE. eCommerce logs each API request before it dispatches it, and the
 * log requires all four of these - `X-DragonByte-BoardUrl`, `-HttpHost`, `-Software` and
 * `-SoftwareVersion`. A request without them is refused with a 400
 * `please_enter_value_for_required_field_x` naming `board_url`, `http_host`, `software` and
 * `software_version`, on every endpoint, before the endpoint itself runs. That is deliberate:
 * the API exists for licensed software to check in, and it is expected to say where from.
 *
 * The software is the licensed PLATFORM - `XenForo` and `2.3.12` for an add-on licensed to a
 * XenForo board - not whatever is making the HTTP call. The board URL does a second job too:
 * a board that matches licenses to the board they are used on filters by it, so it decides
 * which licenses, purchased products and downloads the user appears to have.
 */
final readonly class Identity
{
    public function __construct(
        public string $boardUrl,
        public string $httpHost,
        public string $software,
        public string $softwareVersion,
    ) {
        $fields = [
            'boardUrl' => $boardUrl,
            'httpHost' => $httpHost,
            'software' => $software,
            'softwareVersion' => $softwareVersion,
        ];

        foreach ($fields as $field => $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException(sprintf(
                    'An eCommerce identity needs a %s: the forum refuses every request where it is empty.',
                    $field
                ));
            }
        }
    }

    /**
     * The usual case, where the host is the board URL's own.
     */
    public static function forBoard(string $boardUrl, string $software, string $softwareVersion): self
    {
        $host = parse_url($boardUrl, PHP_URL_HOST);

        if (!is_string($host) || $host === '') {
            throw new InvalidArgumentException(sprintf(
                '%s has no host to identify the board by; pass the host to the constructor instead.',
                $boardUrl === '' ? 'An empty board URL' : $boardUrl
            ));
        }

        return new self($boardUrl, $host, $software, $softwareVersion);
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return [
            'X-DragonByte-BoardUrl' => $this->boardUrl,
            'X-DragonByte-HttpHost' => $this->httpHost,
            'X-DragonByte-Software' => $this->software,
            'X-DragonByte-SoftwareVersion' => $this->softwareVersion,
        ];
    }
}
