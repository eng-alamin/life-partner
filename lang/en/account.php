<?php

return [
    'throttled_minutes' => 'Too many attempts. Please try again in :minutes minute(s).',

    'fields' => [
        'name' => 'Full name',
        'email' => 'Email address',
        'password' => 'Password',
        'password_hint' => 'At least 8 characters. Avoid common passwords.',
        'password_confirmation' => 'Confirm password',
        'code' => 'Verification code',
        'code_hint' => 'Enter the 6-digit code from your email. It is valid for :minutes minutes.',
    ],

    'register' => [
        'title' => 'Create account',
        'heading' => 'Create your account',
        'intro' => 'Register first, then build your biodata.',
        'submit' => 'Create account',
        'have_account' => 'Already registered?',
    ],

    'login' => [
        'title' => 'Log in',
        'heading' => 'Welcome back',
        'intro' => 'Log in to continue.',
        'remember' => 'Keep me signed in',
        'forgot' => 'Forgot password?',
        'submit' => 'Log in',
        'no_account' => 'New here?',
        'failed' => 'The email or password is incorrect.',
        'suspended' => 'This account has been suspended. Please contact support.',
    ],

    'verify' => [
        'title' => 'Verify email',
        'heading' => 'Verify your email',
        'intro' => 'We sent a 6-digit code to :email.',
        'submit' => 'Verify',
        'resend' => 'Send a new code',
        'resent' => 'A new code has been sent to your email.',
        'wrong_email' => 'Wrong email? Log out',
        'success' => 'Your email has been verified.',
    ],

    'reset' => [
        'title' => 'Reset password',
        'heading' => 'Reset your password',
        'intro_request' => 'Enter your email and we will send you a 6-digit code.',
        'send_code' => 'Send code',
        'sent_notice' => 'If this email is registered, a code has been sent. It may take a minute to arrive.',
        'new_password' => 'New password',
        'submit' => 'Change password',
        'resend' => 'Send a new code',
        'back_to_login' => 'Back to log in',
        'success' => 'Your password has been changed. Please log in.',
    ],

    'otp' => [
        'invalid' => 'The code is incorrect.',
        'expired' => 'The code has expired or was not found. Please request a new one.',
        'too_many_attempts' => 'Too many wrong attempts. Please request a new code.',
        'throttled' => 'Please wait :seconds seconds before requesting another code.',
    ],

    'mail' => [
        'subject_verify' => 'Your :app verification code',
        'subject_reset' => 'Your :app password reset code',
        'subject_link' => 'Your :app sign-in confirmation code',
        'greeting' => 'Assalamu alaikum, :name',
        'intro_verify' => 'Use this code to verify your email on :app.',
        'intro_reset' => 'Use this code to reset your :app password.',
        'intro_link' => 'Use this code to confirm your email and finish signing in to :app.',
        'expires' => 'This code expires in :minutes minutes. Never share it with anyone.',
        'ignore' => 'If you did not request this, you can ignore this email.',
    ],

    'dashboard' => [
        'title' => 'Dashboard',
        'heading' => 'Welcome, :name',
        'intro' => 'Your account is ready. Biodata creation comes next.',
    ],
    'social' => [
        'or' => 'or',
        'continue_with' => 'Continue with :provider',
        'cancelled' => 'Sign-in was cancelled or failed. Please try again.',
        'unavailable' => 'This account cannot sign in right now. Please contact support.',
        'default_name' => 'Member',
        'complete_title' => 'Confirm your email',
        'complete_heading' => 'Confirm your email address',
        'complete_intro' => ':provider did not confirm your email address. Enter it and we will send a 6-digit code to prove it is yours.',
        'send_code' => 'Send code',
        'code_sent' => 'If this address can receive email, a code has been sent.',
        'submit' => 'Confirm and continue',
        'cancel' => 'Cancel',
    ],
];
