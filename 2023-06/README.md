This was a projects where i was to make a car dealership site in javascript.

Searching for an "inspiration" i came across [gratka.pl](https://gratka.pl) and i've created a script (`./gen`) that scraped all the offers from standard car category.

It goes through the search pages getting basic information and saving it to `oferty.json`. At the same time every offer is scraped and converted into html file with just the content in `offery/{id}.html`. This has worked particularly nicely as styling already created html with css is much easier than creating everything anew.

This resulted in my site offering 9911 offers. But because javascript cannot into file access, contents of `oferty.json` have to be manually pasted in `index.html` (to oferty variable).

`oferty.json` weighted 3.79M and `oferty` dir 103M.

Some example files were left in `oferty` but all of them can be found in `oferty.tar.xz`, they've been packed to save space.

Since those files are old most of the image links don't work.

`gen` script requires [reliq](https://github.com/TUVIMEN/reliq) at `a69a580cb20f20ae64ec4f054b8e7b98b45f79f1` commit, when it was named `hgrep`.

Made around 2023-06.
