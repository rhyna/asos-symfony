# Porter 2 Stemmer for PHP

[![Circle CI](https://circleci.com/gh/markfullmer/porter2.svg?style=shield)](https://circleci.com/gh/markfullmer/porter2)
[![GitHub license](https://img.shields.io/badge/license-MIT-blue.svg)](https://raw.githubusercontent.com/markfullmer/porter2/master/LICENSE)

A PHP library for stemming words using the English Porter 2 algorithm.

![Screenshot of Conversion](https://raw.githubusercontent.com/markfullmer/porter2/master/demo/stemmer-demo.png)

## Background
A stemmer takes a given word and follows a set of rules to reduce this word
to search-index-usable stem (as opposed to the actual word root). For example,
*aggravate*, *aggravated*, and *aggravates* all reduce to "aggrav," thus
creating a commonality between those words.

Martin Porter's English (Porter 2) Algorithm improves on the original Porter
stemmer as described [here](http://snowball.tartarus.org/algorithms/english/stemmer.html).

## Basic Usage
The included `/demo/index.php` file contains a conversion form demonstration.

Make your code aware of the `Porter2` class via your favorite method (e.g.,
`use` or `require`)

Then pass a string of text into the class:
```php
$text = Porter2::stem('consistently');
echo $text; // consist

$text = Porter2::stem('consisting');
echo $text; // consist

$text = Porter2::stem('consistency');
echo $text; // consist
```

## Stemmer Resources
* [Step definition for the Porter 2 stemmer](http://snowball.tartarus.org/algorithms/english/stemmer.html)

## Tests
A verification list of 29,000 words and their expected stems can be run (after
```composer install``` via ```phpunit```).
