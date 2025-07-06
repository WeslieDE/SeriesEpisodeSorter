# SeriesEpisodeSorter

This is a simple PHP web app for tracking watched episodes from TV shows. Users can create series and episodes, mark them as watched and add ratings or comments. The first user to register becomes the only account; further registrations are disabled.

## Features
- Login with username/password
- Add series and episodes
- Mark episodes as watched with one rating and comment per user
- Optional public read-only access (not implemented yet)
- Uses SQLite via PDO for storage
- Mobile friendly layout using Bootstrap
- Basic PWA manifest and service worker

## Setup
1. Install PHP 8 with SQLite support.
2. Place the contents of the `public/` directory in your web root.
3. Access `index.php` in your browser. On first visit the database will be created automatically.
4. Register the first user account. Subsequent registrations are disabled.

This project is a minimal prototype and lacks many advanced features like WebSockets and admin panels.
It now uses the [Twig](https://twig.symfony.com/) template engine so the HTML
templates live in the `templates/` directory. If the `vendor/` folder is not
present you can install the dependencies with `composer install`.

## Docker

The application can also run inside a container. Build the image and start a container with Docker:

```bash
docker build -t series-episode-sorter .
docker run -p 8080:80 series-episode-sorter
```

Open `http://localhost:8080` in your browser. The SQLite database will be created in `/var/www/html/data` inside the container.
