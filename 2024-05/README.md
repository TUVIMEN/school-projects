This project was supposed to be made in javascript, but could use php for higher grade. I don't know what exacly we were supposed to do as i've made completely different (we could show any other interesting projects).
The basic idea was to make site with pages that user could create.

Having developed [xenforo-scraper](https://github.com/TUVIMEN/xenforo-scraper) and i've had a lot forums downloaded, so i've come up making a web interface for it.

As the "donor" i've chosen [veganforum](https://www.veganforum.org/) as it was relatively small i.e. 300M.

I've created the `prepare_data` script that downloads the forum data and packs it into `veganforum-members.json` and `veganforum-threads.json`. This part of the script should really be run separately.

Next it creates data.sql. First it writes basic sql structure, then using sophisticated tools like `sed` and `jq` it converts json into inserts and to make it faster every 4000 lines is encapsulated in `BEGIN;` and `END;` sql commands to make inserts faster.

Sql structure initially doesn't have `auto_increment` on ids because it would overwrite the inserted ids which are referenced in other fields. Because of that after inserts records with the same ids are being deleted and structure is altered to have `auto_increment`. Also indexes are created on foreign keys.

`data.sql` had 555459 lines and weighted 306M.

To make this assignment more challenging i've completely separated php from javascript by making an api (`api.php`) that has 12 routes and making single page site.

`index.html` has 5 divs with classes starting with `container_` that represent each view. Views are changed by going through them and adding `display:none` to all inactive ones. All other html is created by javascript functions. Site has 6 routes `login`, `register`, `forums`, `search`, `thread`, `error` and is a promise hell (see `script.js`).

Overall this site is a functional forum, that has users that can create (but not delete or update) threads, posts, reactions and do searching. It also doesn't have any permission system.

One of the requirements for this assignment was for this site to be hosted on internet, and i've failed in this as my free hosting used only apache (nginx is superior) that did not allow `RewriteRule` in `.htaccess` and my api relied on being called `/api/.*`.

Made around 2024-05.
