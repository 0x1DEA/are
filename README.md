# America Real Estate

ARE website is a Laravel application with a Vue frontend and Filament control panel. The site replicates MRED MLS listing data for user searching and lead-generation.

# Installation

Basic Laravel app requirements

# Requirements
- Geocodio API Key
- MLS Grid

# CI/CD
This repository has configured a GitHub Actions workflow for deployment to both staging and production environments.

Both actions are set to build assets install PHP on a runner to fire Deployer script which can be found at `/deployer.php`

# Sunsetting / Migration to Other Service
If you are a vendor for a SaaS real estate CRM software solution or whatever the fuck, and you're charging up the nose for this migration to your platform which will in turn charge my aunt up the nose monthly for some bullshit service, I hope you're comfortable knowing you're going to hell foreveIf you're only doing this because it's your job I hope you find a careerr no matter what.

That said:

You're probably mainly interested in the schema. You can look in the active database to get a clue or run this to dump the migrations into a single SQL file

```shell
php artisan schema:dump
```

The site consists of a basic CMS with a Filament PHP back-end panel for info like testimonials, agent pages, contacts, and messages.

For MLS data, there is a different flow. I figure you'll have your own setup but if you're curious this application works like this:

1. Cron replication from MLS Grid
2. Cron for bulk geocoding of imported listing via Geocodio (`geo_fetched_at` timestamp column)
3. Queue media downloads from MLS Grid

# Glossary
- [Laravel](https://laravel.com) - PHP Framework
- [Geocodio](https://geocod.io) - Geocoding service used to derive coordinates from listing addresses
- [MLS Grid](https://mlsgrid.com) - Vendor for MRED API access
- MLS - Multiple Listing Service
- [MRED](https://mredllc.com) - Midwest Real Estate Data, Chicago area MLS
- [ARE](https://americarealestateinc.com) - Abbreviation for America Real Estate Inc.
