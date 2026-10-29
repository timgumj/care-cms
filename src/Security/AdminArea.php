<?php
namespace App\Security;
final class AdminArea
{
    // Edit the segment and matching patterns together before deployment.
    public const PATH = '/manage';
    public const PATTERN = '^/manage(?:/|$)';
    public const LOGIN_PATTERN = '^/manage/login$';
    public const SETUP_PATTERN = '^/manage/setup$';
    public const VERIFY_PATTERN = '^/manage/verify(?:/check)?$';
    public static function matches(string $path): bool { return $path === self::PATH || str_starts_with($path, self::PATH.'/'); }
}
