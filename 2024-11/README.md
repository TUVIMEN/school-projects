This is another car dealership site, this time it had to be made in javascript and php and should allow users for adding and interacting with content. For higher grade you could also add system of moderation.

I've searched for an "inspiration" and stumbled upon [otomoto.pl](https://otomoto.pl).

Analyzing it i've made a very simple script (`./otomoto`) that staticly extracts json from all offers. I called it static because instead of implementing complete system of traversing i've just copied api links for iterating through search pages for 4 main categories.

Script is run with 1 argument only that is my arbitrary index for categories, 1 to 4 and it downloads using 16 processes in parallel. It was run for about a week in `screen` session.

Ideally i could have gotten around 2500000 offers but i've terminated it at 1001952, `ciezarowe` 17167 1.4G, `motocykle` 25629 2.1G, `osobowe` 56177 5,6G and `czesci` 954847 65G.

I've analyzed the json and i've created 20 sql tables in `./finalize` script. Then i've created a python script (`./convert.py`) that filled the database. Because same record could be created from many json files every insert had to be checked for it's uniqueness. This step took more than getting the data.

Ultimately the database had 40000000 records.

I've wanted to learn new things with this project so i've played with laravel and concluded that it's not a good choice for just an api.

As for javascript i've used vue, typescript, axios and tailwindcss all of which were completely new to me.

You can see my frontend in the `pro` directory.

The biggest problem of the whole project was the css. Every time i've made a change i could only be sure of how it worked when i closed the site and loaded it in private mode in browser. Which was very annoying when i had to compile project and login every change.

The site is functional, users can create, update, delete everything with moderation. The only thing that wasn't added was messages, even though they were implemented on the backend i didn't have time.

The size of database was 5617.4M and queries are not easy as there is many tables with many to many relations. Just searching has up to 6 joins and any deletion of uploaded images required for searching if image isn't used by other records. Queries even with indexing were not fast.

The ridiculous thing was that i've run this site on a laptop from 2007 with dying hdd. CPU was not even loaded due to it.

When presenting the site i had to literally restart mariadb daemon and i wasn't able to present some of the features.

Made around 2024-11
