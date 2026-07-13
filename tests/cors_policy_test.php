<?php

require_once __DIR__ . '/../config/cors.php';

function assertSameValue($expected, $actual, $message) {
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual: ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

$siteDomains = ['https://box.example'];

assertSameValue(
    'https://wiki.david888.com',
    resolveCorsAllowOrigin('https://wiki.david888.com', $siteDomains),
    'wiki.david888.com should be allowed by the david888.com wildcard policy'
);

assertSameValue(
    'https://assets.david888.com',
    resolveCorsAllowOrigin('https://assets.david888.com', $siteDomains),
    'any david888.com subdomain should be allowed by the wildcard policy'
);

assertSameValue(
    'https://david888.com',
    resolveCorsAllowOrigin('https://david888.com', $siteDomains),
    'the david888.com apex should be allowed with its subdomains'
);

assertSameValue(
    'https://box.example',
    resolveCorsAllowOrigin('https://box.example', $siteDomains),
    'configured site domains should remain allowed'
);

assertSameValue(
    'null',
    resolveCorsAllowOrigin('https://evil.example', $siteDomains),
    'unknown origins should be rejected'
);

assertSameValue(
    '*',
    resolveCorsAllowOrigin('https://any.example', ['*']),
    'site domain wildcard should continue to allow every origin'
);
