# SeriesEpisodeSorter

This is a simple PHP web app for tracking watched episodes from TV shows. Users can create series and episodes, mark them as watched and add ratings or comments. The first user to register becomes the only account; further registrations are disabled.

## Features
- Login with username/password
- Add series and episodes
- Bulk add multiple episodes for a season
- Mark episodes as watched with one rating and comment per user
- Edit series details after creation
- Mark watched episodes as unwatched again
- List all series on the home page with links to a detail view
- Public read-only access via `view.php`
- Uses SQLite via PDO for storage
- Mobile friendly layout using Bootstrap
- Navigation menu with login and logout
- Basic PWA manifest and service worker

### Bulk adding episodes

On the series detail page you can create an entire season at once. Enter the season number and how many episodes it contains and the application will create blank entries numbered sequentially.

## Setup
1. Install PHP 8 with SQLite support.
2. Place the contents of the `public/` directory in your web root.
3. Access `index.php` in your browser. On first visit the database will be created automatically.
4. Register the first user account. Subsequent registrations are disabled.
5. View the read-only list at `view.php` to share your progress.

### Configuration

Basic settings live in `config.php`. Here you can adjust the title shown in the
navigation bar and HTML `<title>` tags, switch the database driver, set the
language and provide API keys for external services.

When logged in you can open **Config** from the navigation bar to edit these
settings through the browser. Saving regenerates `config.php` and will fail if
the file is not writable.

This project is a minimal prototype and lacks many advanced features like WebSockets and admin panels.

## Docker

The application can also run inside a container. Build the image and start a container with Docker:

```bash
docker build -t series-episode-sorter .
docker run -p 8080:80 series-episode-sorter
```

Open `http://localhost:8080` in your browser. The SQLite database will be created in `/var/www/html/data` inside the container.
