<?php
namespace tool_batchcertificate\local;

defined('MOODLE_INTERNAL') || die();

final class provider_manager {
    /** @var provider[] */
    private array $providers = [];

    public function __construct() {
        $this->providers = [
            new customcert_provider(),
            new simplecertificate_provider(),
            new generic_provider(),
        ];
    }

    public function get_provider(\cm_info $cm): ?provider {
        foreach ($this->providers as $provider) {
            if ($provider->supports($cm)) {
                return $provider;
            }
        }
        return null;
    }
}
