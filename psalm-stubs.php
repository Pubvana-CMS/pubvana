<?php

/**
 * Psalm taint-analysis stubs for Pubvana.
 *
 * PHPStan owns general type/code checking at level 8. Psalm is used here for
 * taint/security analysis (XSS, SQLi, shell, SSRF, header injection).
 *
 * The app does not read the PHP superglobals that Psalm's default taint
 * tracker knows about ($_GET/$_POST/$_COOKIE). User input arrives through
 * Flight's request abstraction:
 *
 *   $app->request()->data->getData()   (parsed POST/JSON body)
 *   $app->request()->query->xxx         (query-string params)
 *   $app->request()->data->xxx          (magic property access)
 *   $app->request()->getBody()          (raw body)
 *
 * The annotations below re-declare the flight collections and request as
 * taint sources so security analysis traces user input through the app. These
 * stubs merge with the real vendor classes; at runtime they are never loaded.
 *
 * NOTE: use @psalm-taint-source with the `input` group alias (as the
 * psalm-plugin-laravel package does), not a pipe-separated list of individual
 * kinds. An invalid kind name (for example one not present in this Psalm
 * version) silently disables the whole annotation.
 */

namespace Flight\Util {

    use ArrayAccess;
    use Countable;
    use Iterator;
    use JsonSerializable;

    /**
     * @psalm-taint-source input
     */
    class Collection implements ArrayAccess, Iterator, Countable, JsonSerializable
    {
        /**
         * @psalm-taint-source input
         * @return mixed
         */
        public function __get(string $key)
        {
        }

        /**
         * @psalm-taint-source input
         * @return array<string, mixed>
         */
        public function getData(): array
        {
        }
    }
}

namespace Flight\Net {

    use Flight\Util\Collection;

    class Request
    {
        public Collection $query;
        public Collection $data;
        public Collection $cookies;

        /**
         * @psalm-taint-source input
         */
        public function getBody(): string
        {
        }
    }
}
