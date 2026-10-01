<?php

namespace App\Support\Settings;

/**
 * The security rules a Super admin can set for the whole platform: what each one is, its default and its limits, and how the
 * settings page groups them. Defaults match how the platform behaved before these became settings, so nothing changes until
 * someone changes it.
 */
final class SecuritySettings
{
    /** @return array<string, array{type: string, default: mixed, label: string, help?: string, min?: int, max?: int, unit?: string}> */
    public static function definitions(): array
    {
        return [
            'security.otp_members_required' => ['type' => 'bool', 'default' => false, 'label' => 'Require a sign-in code for members', 'help' => 'Every member confirms each sign-in with a code, whether or not they turned two-step verification on themselves. Members with an authenticator app use it; everyone else is emailed a code.'],
            'security.otp_staff_required' => ['type' => 'bool', 'default' => false, 'label' => 'Require a sign-in code for staff', 'help' => 'The same for the staff portal. Staff without an authenticator app are emailed a code at every sign-in, so check that outgoing email works before turning this on.'],
            'security.authenticator_allowed' => ['type' => 'bool', 'default' => true, 'label' => 'Allow authenticator apps', 'help' => 'Lets people set up Google Authenticator, Authy or similar. Turn it off to use emailed codes only; anyone already using an app falls back to email.'],
            'security.otp_max_attempts' => ['type' => 'int', 'default' => 5, 'min' => 3, 'max' => 10, 'label' => 'Wrong codes allowed', 'help' => 'After this many wrong codes the person is locked out of codes for the time under Sign-in protection.', 'unit' => 'attempts'],

            'security.trusted_devices_enabled' => ['type' => 'bool', 'default' => false, 'label' => 'Let people trust a device', 'help' => 'Offers "Trust this device" after a correct code, so that browser skips the code for a while. A password change, a reset by staff or turning this off ends the trust.'],
            'security.trusted_device_days' => ['type' => 'int', 'default' => 30, 'min' => 1, 'max' => 90, 'label' => 'A trusted device is trusted for', 'unit' => 'days'],

            'security.password_min_length' => ['type' => 'int', 'default' => 8, 'min' => 8, 'max' => 64, 'label' => 'Minimum length', 'help' => 'Staff passwords always need at least 12 characters, whatever this says.', 'unit' => 'characters'],
            'security.password_mixed_case' => ['type' => 'bool', 'default' => false, 'label' => 'Require upper and lower case letters'],
            'security.password_number' => ['type' => 'bool', 'default' => false, 'label' => 'Require a number'],
            'security.password_symbol' => ['type' => 'bool', 'default' => false, 'label' => 'Require a symbol', 'help' => 'These apply when someone registers, resets or changes a password. Existing passwords keep working until they are changed.'],

            'security.signin_max_attempts' => ['type' => 'int', 'default' => 5, 'min' => 3, 'max' => 20, 'label' => 'Failed sign-ins allowed', 'help' => 'Counted per email address and device.', 'unit' => 'attempts'],
            'security.signin_lockout_minutes' => ['type' => 'int', 'default' => 1, 'min' => 1, 'max' => 120, 'label' => 'Then locked out for', 'help' => 'Also how long someone who used up their wrong sign-in codes has to wait.', 'unit' => 'minutes'],

            'security.member_idle_minutes' => ['type' => 'int', 'default' => 120, 'min' => 5, 'max' => 10080, 'label' => 'Members are signed out after', 'help' => 'Minutes without any activity.', 'unit' => 'minutes idle'],
            'security.member_max_hours' => ['type' => 'int', 'default' => 0, 'min' => 0, 'max' => 720, 'label' => 'Longest a member stays signed in', 'help' => 'Hours since signing in, however active they are. 0 means no limit. A limit also switches off "Keep me signed in".', 'unit' => 'hours'],
            'security.staff_idle_minutes' => ['type' => 'int', 'default' => 120, 'min' => 5, 'max' => 10080, 'label' => 'Staff are signed out after', 'help' => 'Minutes without any activity.', 'unit' => 'minutes idle'],
            'security.staff_max_hours' => ['type' => 'int', 'default' => 0, 'min' => 0, 'max' => 720, 'label' => 'Longest a staff member stays signed in', 'help' => 'Hours since signing in. 0 means no limit. A limit also switches off "Keep me signed in".', 'unit' => 'hours'],
            'security.member_single_session' => ['type' => 'bool', 'default' => false, 'label' => 'One active session per member', 'help' => 'Signing in on another device signs the member out everywhere else.'],
            'security.staff_single_session' => ['type' => 'bool', 'default' => false, 'label' => 'One active session per staff member', 'help' => 'Signing in on another device signs the staff member out everywhere else.'],
        ];
    }

    /** @return list<array{title: string, description: string, keys: list<string>}> */
    public static function sections(): array
    {
        return [
            ['title' => 'Sign-in codes', 'description' => 'A second step after the password: an authenticator app, or a code sent by email.', 'keys' => ['security.otp_members_required', 'security.otp_staff_required', 'security.authenticator_allowed', 'security.otp_max_attempts']],
            ['title' => 'Trusted devices', 'description' => 'Spare people the code on a browser they have already confirmed.', 'keys' => ['security.trusted_devices_enabled', 'security.trusted_device_days']],
            ['title' => 'Passwords', 'description' => 'What a new password has to look like.', 'keys' => ['security.password_min_length', 'security.password_mixed_case', 'security.password_number', 'security.password_symbol']],
            ['title' => 'Sign-in protection', 'description' => 'Slows down anyone guessing passwords.', 'keys' => ['security.signin_max_attempts', 'security.signin_lockout_minutes']],
            ['title' => 'Sessions', 'description' => 'How long people stay signed in, and where.', 'keys' => ['security.member_idle_minutes', 'security.member_max_hours', 'security.member_single_session', 'security.staff_idle_minutes', 'security.staff_max_hours', 'security.staff_single_session']],
        ];
    }
}
