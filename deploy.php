<?php

namespace Deployer;

import('recipe/laravel.php');
import('contrib/rsync.php');
import('contrib/crontab.php');

set('application', getenv('CI_PROJECT_NAME'));
set('ssh_multiplexing', true);

set('rsync_src', function () {
    return __DIR__;
});

host('production')
    ->setHostname(getenv('CI_SSH_HOST'))
    ->setRemoteUser('deployer')
    ->setPort(getenv('CI_SSH_PORT'))
    ->setDeployPath('/var/www/are')
    ->set('branch', 'main')
    ->setLabels(['env' => 'production']);

host('staging')
    ->setHostname(getenv('CI_SSH_HOST'))
    ->setRemoteUser('deployer')
    ->setPort(getenv('CI_SSH_PORT'))
    ->setDeployPath('/var/www/are-staging')
    ->set('branch', 'develop')
    ->setLabels(['env' => 'staging']);

add('rsync', [
    'exclude' => [
        '.git',
        '/.env',
        '/storage/',
        '/vendor/',
        '/node_modules/',
        '.github',
        'deploy.php',
    ],
]);

// Tasks
task('deploy:secrets', function () {
    file_put_contents(__DIR__.'/.env', getenv('DOT_ENV'));
    upload('.env', get('deploy_path').'/shared');
});

task('fix:folders', function () {
    run('mkdir -p {{deploy_path}}/shared/storage/framework '.
        '{{deploy_path}}/shared/storage/framework/cache '.
        '{{deploy_path}}/shared/storage/framework/sessions '.
        '{{deploy_path}}/shared/storage/framework/views '.
        '{{deploy_path}}/shared/storage/clockwork');
});

set('writable_dirs', ['{{deploy_path}}/shared/storage/framework']);

desc('Update disposable email list');
task('artisan:disposable:update', artisan('disposable:update'));

desc('Update Cloudflare IP list');
task('artisan:cloudflare:reload', artisan('cloudflare:reload'));

after('deploy:failed', 'deploy:unlock');

desc('Deploy the application');
task('deploy', [
    'deploy:info',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'fix:folders',
    'rsync',
    'deploy:secrets',
    'deploy:shared',
    'deploy:writable',
    'deploy:vendors',

    // Begin Laravel Stuff
    'artisan:storage:link',
    'artisan:view:cache',
    'artisan:config:cache',
    'artisan:route:cache',
    'artisan:optimize',
    'artisan:migrate',
    'artisan:disposable:update',
    'artisan:cloudflare:reload',
    // End Laravel Stuff

    'deploy:symlink',
    'deploy:unlock',
    'deploy:cleanup',
    'deploy:success',
    'cronfigure',
]);

desc('Set cron jobs');
task('cronfigure', function () {
    add('crontab:jobs', [
        '* * * * * cd {{deploy_path}} && {{bin/php}} artisan schedule:run >> /dev/null 2>&1',
    ]);
    set('crontab:identifier', 'are-'.get('labels')['env']);
});

after('cronfigure', 'crontab:sync');
