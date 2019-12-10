<?php

// Error handling
function print_warning(string $message): void
{
    fwrite(STDERR, "\e[33m" . $message . "\e[0m\n");
}

function print_error(string $message): void
{
    fwrite(STDERR, "\n\e[31m" . $message . "\e[0m\n");
}

set_exception_handler(function (Throwable $e) {
    print_error($e instanceof RuntimeException ? $e->getMessage() : (string)$e);
    exit(1);
});
//----

function camelcase(string $string, string $space = ''): string
{
    return preg_replace_callback('/-(.)/', fn($match) => $space . strtoupper($match[1]), ucfirst($string));
}

$vendor = getenv('PACKAGIST_VENDOR');
if ($vendor === false) {
    throw new RuntimeException("'PACKAGIST_VENDOR' environment variable not set");
}

$name = basename(getcwd());
$library = $vendor . '/' . $name;
$title = camelcase($name, ' ');
$namespace = camelcase($vendor) . '\\' . camelcase($name) . '\\';
$repo = getenv('GITHUB_REPO') ?: $library;

$author = (function () {
    $name = trim(shell_exec('git config --global --get user.name'));
    $email = trim(shell_exec('git config --global --get user.email'));
    $homepage = trim(shell_exec('git config --global --get user.homepage'));

    $author = null;
    if ($name !== '') $author['name'] = $name;
    if ($email !== '') $author['email'] = $email;
    if ($homepage !== '') $author['homepage'] = $homepage;

    return $author;
})();

$authorDesc =
  (isset($author['name']) ? $author['name'] . (isset($author['email']) ? " <{$author['email']}>" : ''): null) ??
  $author['email'] ??
  $author['homepage'] ??
  $vendor;

// Create README.md and LICENSE
$trVars = [
    '{{library}}' => $library,
    '{{vendor}}' => $vendor,
    '{{name}}' => $name,
    '{{title}}' => $title,
    '{{author}}' => $authorDesc,
    '{{year}}' => date('Y'),
];

$readme = strtr(file_get_contents('README.md.dist'), $trVars);
file_put_contents('README.md', $readme);
unlink('README.dist');

$license = strtr(file_get_contents('LICENSE'), $trVars);
file_put_contents('LICENSE', $license);

// Create composer.json
$composer = json_decode(file_get_contents('composer.json.dist'), true);

$composer['name'] = $library;
$composer['description'] = $title;
$composer['support']['issues'] = "https://github.com/$repo/issues";
$composer['support']['source'] = "https://github.com/$repo";
$composer['autoload']['psr-4'][$namespace] = "src/";
$composer['autoload-dev']['psr-4'][$namespace . "Tests\\"] = "tests/";
if ($author !== null) $composer['authors'][] = $author;

file_put_contents('composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
unlink('composer.json.dist');

// Install test suite
system('composer install');

copy('vendor/jasny/php-code-quality/phpstan.neon.dist', './phpstan.neon');
copy('vendor/jasny/php-code-quality/phpunit.xml.dist', './phpunit.xml.dist');
copy('vendor/jasny/php-code-quality/phpcs.xml.dist', './phpcs.xml');

copy('vendor/jasny/php-code-quality/travis.yml.dist', './.travis.yml');
copy('vendor/jasny/php-code-quality/scrutinizer.yml.dist', './.scrutinizer.yml');

// Create GitHub repository
(function() use ($title, $repo) {
    if (is_dir('.git')) return;

    system(join(" && ", [
        'git init',
        'git add .',
        'git commit -m "Initial commit"',
        'git create -d ' . escapeshellarg($title) . ' ' . escapeshellarg($repo),
        'git push -u origin master',
    ]), $ret);

    if ($ret > 0) throw new RuntimeException("Failed to create github repo (is hub installed?)");
})();

# Travis
(function() {
    system("travis enable --no-interactive", $ret);

    if ($ret > 0) print_warning("Failed to enable project on Travis");
})();

# Scrutinizer
(function() use ($library, $vendor) {
    $accessToken = getenv('SCRUTINIZER_ACCESS_TOKEN');
    
    if (!$accessToken) {
        print_warning("Skipping scrutinizer: access token not configured");
        return;
    }

    $organization = getenv('SCRUTINIZER_ORGANIZATION');
    $globalConfig = getenv('SCRUTINIZER_GLOBAL_CONFIG');

    $data['name'] = $library;
    if ($organization) $data['organization'] = $organization;
    if ($globalConfig) $data['global_config'] = $globalConfig;

    $ch = curl_init("https://scrutinizer-ci.com/api/repositories/g?access_token=$accessToken");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $ret = curl_exec($ch);
    if (!$ret) print_warning("Failed to enable project on Scrutinizer");
})();

