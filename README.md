MinifyJS - A JavaScript minifier written in PHP
===============================================

Because there was no JS minifier written in PHP available that could do optimisation of variables, and other (sometimes extreme and complex) transformations,
I decided to write my own. Using the parser and tokenizer ported from Narcissus by Tino from tweakers.net, I created a simple system based on an AST which could
easily handle all transformations.

How do I use it?
----------------

In the root folder there is a PHP file called min.php. It has the execute bit set and contains the shebang neccessary. `./min.php jquery-1.7.2.js` does the trick.

There are various flags you can use, which will impact MinifyJS in some way:
* `--no-mangle` or `-nm`: Do not optimise variables
* `--no-crush-bool` or `-ncb`: Do not transform `true` to `!0` or `false` to `!1`
* `--unsafe` or `-us`: Perform possibly unsafe transformations. Almost always safe
* `--beautify` or `-b`: Instead of minifying, beautify the code. Comments are still lost, except for license blocks
* `--no-copyright` or `-nc`: Also remove license blocks (`/*! ... */`)
* `--strip-debug` or `-sd`: Strip calls using the console variable, and remove debugger statements
* `--no-inlining` or `-ni`: Do not inline constant variables (`var a = 5;alert(a);` will not transform to `alert(5)`)

* `--timer` or `-t`: Instead of showing the minified version, show various timing statistics


What kind of optimisations are done?
------------------------------------
A *lot*. A few things:

* Variables are minified when not global.
* Constant folding: `alert(5 + 5 / 2)` will become `alert(7.5)`. Only when the result is smaller than the original.
* Inlining
* Removal of useless statements.
* `if (foo) bar` => `foo&&bar` and other similar stuff.
* Constant if statements are replaced by either the then or else (depending on the condition)
* Same with while, for statements and alike.
* Unused variables and named functions are removed

Why?
----
The initial payload sent by a website is often on a clean cache. This basically means that people will have to download
all content you send for your page. By minifying as much as you can, the page will be at the client faster. MinifyJS attempts
to create not only smaller, but also faster JavaScript.