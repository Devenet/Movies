# Movies

Movies is a light web application to manage movies and track ther state.  
It’s written in PHP, does not require a database and can be easily self hosted.


Version 2.0 is now available → [download the last version](https://github.com/Devenet/Movies/releases) 🚀


Main features:  
- Simple & fast, intuitive & mobile first
- No database (data is saved in a file)
- Easy installation
- Search, box office, watchlist, and random movie features
- Several optional metadata (original title, duration, release date, country of production, genres, external information link, movie poster)
- RSS feed for last movies
- Export and import feature with JSON file
- Settings (title, author, robots, pagination)
- Custom themes supported


![Movies](https://raw.github.com/Devenet/Movies/master/Movies.jpg)


Developed by Nicolas Devenet. Under MIT license.  
Code hosted on https://github.com/Devenet/Movies.

Thanks to [contributors](https://github.com/Devenet/Movies/graphs/contributors). Inspired by [Shaarli](https://github.com/sebsauvage/Shaarli).

---

## Migration from v1 to v2

Before migrated to v2:  

- If you want to keep your logs, you have to rename the file `/data/area-51.txt` into `/data/logs.txt`.  
  Otherwise, nothing to do.


After migrated to v2:

- You can delete the obsolete files `/boxoffice.rss` and `/watchlist.rss`.  
  You can also add a redirection on your webserver for the previous deleted files to the `movies.rss` file.
- You can delete the obsolete folder `/cache`.
