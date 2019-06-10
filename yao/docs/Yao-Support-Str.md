Yao\Support\Str
===============

Str

(Copy From \Illuminate\Support\Str )

see https://github.com/laravel/framework/blob/5.3/src/Illuminate/Support/Str.php


* Class name: Str
* Namespace: Yao\Support





Properties
----------


### $snakeCache

    protected array $snakeCache = array()

The cache of snake-cased words.



* Visibility: **protected**
* This property is **static**.


### $camelCache

    protected array $camelCache = array()

The cache of camel-cased words.



* Visibility: **protected**
* This property is **static**.


### $studlyCache

    protected array $studlyCache = array()

The cache of studly-cased words.



* Visibility: **protected**
* This property is **static**.


### $macros

    protected array $macros = array()

The registered string macros.



* Visibility: **protected**
* This property is **static**.


Methods
-------


### ascii

    string Yao\Support\Str::ascii(string $value)

Transliterate a UTF-8 value to ASCII.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $value **string**



### camel

    string Yao\Support\Str::camel(string $value)

Convert a value to camel case.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $value **string**



### contains

    boolean Yao\Support\Str::contains(string $haystack, string|array $needles)

Determine if a given string contains a given substring.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $haystack **string**
* $needles **string|array**



### endsWith

    boolean Yao\Support\Str::endsWith(string $haystack, string|array $needles)

Determine if a given string ends with a given substring.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $haystack **string**
* $needles **string|array**



### finish

    string Yao\Support\Str::finish(string $value, string $cap)

Cap a string with a single instance of a given value.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $value **string**
* $cap **string**



### is

    boolean Yao\Support\Str::is(string $pattern, string $value)

Determine if a given string matches a given pattern.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $pattern **string**
* $value **string**



### length

    integer Yao\Support\Str::length(string $value)

Return the length of the given string.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $value **string**



### limit

    string Yao\Support\Str::limit(string $value, integer $limit, string $end)

Limit the number of characters in a string.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $value **string**
* $limit **integer**
* $end **string**



### lower

    string Yao\Support\Str::lower(string $value)

Convert the given string to lower-case.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $value **string**



### words

    string Yao\Support\Str::words(string $value, integer $words, string $end)

Limit the number of words in a string.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $value **string**
* $words **integer**
* $end **string**



### parseCallback

    array Yao\Support\Str::parseCallback(string $callback, string $default)

Parse a Class@method style callback into class and method.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $callback **string**
* $default **string**



### plural

    string Yao\Support\Str::plural(string $value, integer $count)

Get the plural form of an English word.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $value **string**
* $count **integer**



### random

    string Yao\Support\Str::random(integer $length)

Generate a more truly "random" alpha-numeric string.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $length **integer**



### quickRandom

    string Yao\Support\Str::quickRandom(integer $length)

Generate a "random" alpha-numeric string.

Should not be considered sufficient for cryptography, etc.

* Visibility: **public**
* This method is **static**.


#### Arguments
* $length **integer**



### replaceArray

    string Yao\Support\Str::replaceArray(string $search, array $replace, string $subject)

Replace a given value in the string sequentially with an array.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $search **string**
* $replace **array**
* $subject **string**



### replaceFirst

    string Yao\Support\Str::replaceFirst(string $search, string $replace, string $subject)

Replace the first occurrence of a given value in the string.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $search **string**
* $replace **string**
* $subject **string**



### replaceLast

    string Yao\Support\Str::replaceLast(string $search, string $replace, string $subject)

Replace the last occurrence of a given value in the string.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $search **string**
* $replace **string**
* $subject **string**



### upper

    string Yao\Support\Str::upper(string $value)

Convert the given string to upper-case.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $value **string**



### title

    string Yao\Support\Str::title(string $value)

Convert the given string to title case.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $value **string**



### singular

    string Yao\Support\Str::singular(string $value)

Get the singular form of an English word.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $value **string**



### slug

    string Yao\Support\Str::slug(string $title, string $separator)

Generate a URL friendly "slug" from a given string.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $title **string**
* $separator **string**



### snake

    string Yao\Support\Str::snake(string $value, string $delimiter)

Convert a string to snake case.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $value **string**
* $delimiter **string**



### startsWith

    boolean Yao\Support\Str::startsWith(string $haystack, string|array $needles)

Determine if a given string starts with a given substring.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $haystack **string**
* $needles **string|array**



### studly

    string Yao\Support\Str::studly(string $value)

Convert a value to studly caps case.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $value **string**



### substr

    string Yao\Support\Str::substr(string $string, integer $start, integer|null $length)

Returns the portion of string specified by the start and length parameters.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $string **string**
* $start **integer**
* $length **integer|null**



### ucfirst

    string Yao\Support\Str::ucfirst(string $string)

Make a string's first character uppercase.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $string **string**



### charsArray

    array Yao\Support\Str::charsArray()

Returns the replacements for the ascii method.

Note: Adapted from Stringy\Stringy.

* Visibility: **protected**
* This method is **static**.




### macro

    void Yao\Support\Str::macro(string $name, callable $macro)

Register a custom macro.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $name **string**
* $macro **callable**



### hasMacro

    boolean Yao\Support\Str::hasMacro(string $name)

Checks if macro is registered.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $name **string**



### __callStatic

    mixed Yao\Support\Str::__callStatic(string $method, array $parameters)

Dynamically handle calls to the class.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $method **string**
* $parameters **array**



### __call

    mixed Yao\Support\Str::__call(string $method, array $parameters)

Dynamically handle calls to the class.



* Visibility: **public**


#### Arguments
* $method **string**
* $parameters **array**


