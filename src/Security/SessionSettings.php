<?php
namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class SessionSettings
{
    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root) {}

    public function read(): array
    {
        $defaults = ['idle_minutes' => 30, 'maximum_hours' => 8];
        $path = $this->root.'/var/security/session-settings.json';
        if (!is_file($path)) return $defaults;
        $settings = json_decode((string) file_get_contents($path), true);
        return is_array($settings) && $this->valid($settings) ? array_intersect_key($settings, $defaults) : $defaults;
    }

    public function valid(array $settings): bool
    {
        return isset($settings['idle_minutes'], $settings['maximum_hours'])
            && is_int($settings['idle_minutes']) && $settings['idle_minutes'] >= 5 && $settings['idle_minutes'] <= 120
            && is_int($settings['maximum_hours']) && $settings['maximum_hours'] >= 1 && $settings['maximum_hours'] <= 24
            && $settings['idle_minutes'] <= $settings['maximum_hours'] * 60;
    }

    public function save(array $settings): void
    {
        if (!$this->valid($settings)) throw new \InvalidArgumentException('Invalid session limits.');
        $directory = $this->root.'/var/security';
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) throw new \RuntimeException('Cannot create security settings directory.');
        $temporary = tempnam($directory, 'session-');
        if ($temporary === false) throw new \RuntimeException('Cannot create settings file.');
        try {
            if (file_put_contents($temporary, json_encode(array_intersect_key($settings, ['idle_minutes'=>true, 'maximum_hours'=>true]), JSON_THROW_ON_ERROR), LOCK_EX) === false
                || !rename($temporary, $directory.'/session-settings.json')) throw new \RuntimeException('Cannot save session settings.');
        } finally {
            if (is_file($temporary)) unlink($temporary);
        }
    }
}
