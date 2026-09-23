<?php

// Minimal declarations of the Composer classes Installer uses, for phpstan
// only (listed under scanFiles, never loaded at runtime). Installing
// composer/composer as a dev dependency would pull dozens of packages into
// every `composer create-project taw/create`.

namespace Composer {
    class Config
    {
        /** @return mixed */
        public function get(string $key, int $flags = 0) {}
    }

    class Composer
    {
        public function getConfig(): Config {}
    }
}

namespace Composer\IO {
    interface IOInterface
    {
        public function isInteractive(): bool;

        /** @param string|string[] $messages */
        public function write($messages, bool $newline = true, int $verbosity = 32): void;

        /**
         * @param string[]    $choices
         * @param bool|string $default
         * @return int|string|list<string>|bool
         */
        public function select(string $question, array $choices, $default, $attempts = false, string $errorMessage = 'Value "%s" is invalid', bool $multiselect = false);
    }
}

namespace Composer\Script {
    class Event
    {
        public function getIO(): \Composer\IO\IOInterface {}

        public function getComposer(): \Composer\Composer {}

        public function isDevMode(): bool {}
    }
}
