<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sentry DSN
    |--------------------------------------------------------------------------
    |
    | Your Sentry Data Source Name. This can be found in your Sentry project settings.
    |
    */
    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    /*
    |--------------------------------------------------------------------------
    | Breadcrumbs
    |--------------------------------------------------------------------------
    |
    | Configure breadcrumb recording for better error context.
    |
    */
    'breadcrumbs' => [
        'logs' => true,
        'sql_queries' => true,
        'sql_bindings' => true,
        'queue_info' => true,
        'command_info' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Enable performance monitoring and configure sample rate.
    | Set to 1.0 to trace 100% of transactions.
    |
    */
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.2),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Set the environment for Sentry error tracking.
    |
    */
    'environment' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Release
    |--------------------------------------------------------------------------
    |
    | Set a release version for better error tracking across deploys.
    |
    */
    'release' => env('SENTRY_RELEASE'),

    /*
    |--------------------------------------------------------------------------
    | Send Default PII
    |--------------------------------------------------------------------------
    |
    | Whether to send personally identifiable information (PII) to Sentry.
    | Disable this in production for privacy compliance.
    |
    */
    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),

    /*
    |--------------------------------------------------------------------------
    | Before Send Callback
    |--------------------------------------------------------------------------
    |
    | Filter events before they are sent to Sentry.
    |
    */
    'before_send' => function (\Sentry\Event $event, ?\Sentry\EventHint $hint): ?\Sentry\Event {
        // Don't send specific exceptions
        $ignoredExceptions = [
            \Illuminate\Auth\AuthenticationException::class,
            \Illuminate\Validation\ValidationException::class,
        ];

        if ($hint && $hint->exception) {
            foreach ($ignoredExceptions as $exception) {
                if ($hint->exception instanceof $exception) {
                    return null;
                }
            }
        }

        // Filter sensitive data
        if ($event->getRequest()) {
            $data = $event->getRequest()->getData();
            if (is_array($data)) {
                foreach (['password', 'token', 'secret', 'api_key'] as $key) {
                    if (isset($data[$key])) {
                        $data[$key] = '***FILTERED***';
                    }
                }
                $event->setRequest(array_merge($event->getRequest(), ['data' => $data]));
            }
        }

        return $event;
    },

    /*
    |--------------------------------------------------------------------------
    | Context
    |--------------------------------------------------------------------------
    |
    | Additional context to send with every error.
    |
    */
    'context' => [
        'tags' => [
            'php_version' => PHP_VERSION,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integrations
    |--------------------------------------------------------------------------
    |
    | Configure Sentry integrations.
    |
    */
    'integrations' => [
        // Automatically capture unhandled promise rejections
        'default_integrations' => true,
    ],
];
