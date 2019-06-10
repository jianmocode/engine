Yao\Support\Arr
===============

Str

(Copy From \Illuminate\Support\Arr )

see https://github.com/laravel/framework/blob/5.3/src/Illuminate/Support/Arr.php


* Class name: Arr
* Namespace: Yao\Support





Properties
----------


### $macros

    protected array $macros = array()

The registered string macros.



* Visibility: **protected**
* This property is **static**.


Methods
-------


### accessible

    boolean Yao\Support\Arr::accessible(mixed $value)

Determine whether the given value is array accessible.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $value **mixed**



### add

    array Yao\Support\Arr::add(array $array, string $key, mixed $value)

Add an element to an array using "dot" notation if it doesn't exist.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $array **array**
* $key **string**
* $value **mixed**



### collapse

    array Yao\Support\Arr::collapse(array $array)

Collapse an array of arrays into a single array.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $array **array**



### divide

    array Yao\Support\Arr::divide(array $array)

Divide an array into two arrays. One with keys and the other with values.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $array **array**



### dot

    array Yao\Support\Arr::dot(array $array, string $prepend)

Flatten a multi-dimensional associative array with dots.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $array **array**
* $prepend **string**



### except

    array Yao\Support\Arr::except(array $array, array|string $keys)

Get all of the given array except for a specified array of items.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $array **array**
* $keys **array|string**



### exists

    boolean Yao\Support\Arr::exists(\ArrayAccess|array $array, string|integer $key)

Determine if the given key exists in the provided array.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $array **ArrayAccess|array**
* $key **string|integer**



### first

    mixed Yao\Support\Arr::first(array $array, callable|null $callback, mixed $default)

Return the first element in an array passing a given truth test.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $array **array**
* $callback **callable|null**
* $default **mixed**



### last

    mixed Yao\Support\Arr::last(array $array, callable|null $callback, mixed $default)

Return the last element in an array passing a given truth test.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $array **array**
* $callback **callable|null**
* $default **mixed**



### flatten

    array Yao\Support\Arr::flatten(array $array, integer $depth)

Flatten a multi-dimensional array into a single level.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $array **array**
* $depth **integer**



### forget

    void Yao\Support\Arr::forget(array $array, array|string $keys)

Remove one or many array items from a given array using "dot" notation.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $array **array**
* $keys **array|string**



### get

    mixed Yao\Support\Arr::get(\ArrayAccess|array $array, string $key, mixed $default)

Get an item from an array using "dot" notation.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $array **ArrayAccess|array**
* $key **string**
* $default **mixed**



### has

    boolean Yao\Support\Arr::has(\ArrayAccess|array $array, string|array $keys)

Check if an item or items exist in an array using "dot" notation.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $array **ArrayAccess|array**
* $keys **string|array**



### isAssoc

    boolean Yao\Support\Arr::isAssoc(array $array)

Determines if an array is associative.

An array is "associative" if it doesn't have sequential numerical keys beginning with zero.

* Visibility: **public**
* This method is **static**.


#### Arguments
* $array **array**



### only

    array Yao\Support\Arr::only(array $array, array|string $keys)

Get a subset of the items from the given array.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $array **array**
* $keys **array|string**



### pluck

    array Yao\Support\Arr::pluck(array $array, string|array $value, string|array|null $key)

Pluck an array of values from an array.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $array **array**
* $value **string|array**
* $key **string|array|null**



### explodePluckParameters

    array Yao\Support\Arr::explodePluckParameters(string|array $value, string|array|null $key)

Explode the "value" and "key" arguments passed to "pluck".



* Visibility: **protected**
* This method is **static**.


#### Arguments
* $value **string|array**
* $key **string|array|null**



### prepend

    array Yao\Support\Arr::prepend(array $array, mixed $value, mixed $key)

Push an item onto the beginning of an array.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $array **array**
* $value **mixed**
* $key **mixed**



### pull

    mixed Yao\Support\Arr::pull(array $array, string $key, mixed $default)

Get a value from the array, and remove it.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $array **array**
* $key **string**
* $default **mixed**



### set

    array Yao\Support\Arr::set(array $array, string $key, mixed $value)

Set an array item to a given value using "dot" notation.

If no key is given to the method, the entire array will be replaced.

* Visibility: **public**
* This method is **static**.


#### Arguments
* $array **array**
* $key **string**
* $value **mixed**



### shuffle

    array Yao\Support\Arr::shuffle(array $array)

Shuffle the given array and return the result.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $array **array**



### sort

    array Yao\Support\Arr::sort(array $array, callable|string $callback)

Sort the array using the given callback or "dot" notation.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $array **array**
* $callback **callable|string**



### sortRecursive

    array Yao\Support\Arr::sortRecursive(array $array)

Recursively sort an array by keys and values.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $array **array**



### where

    array Yao\Support\Arr::where(array $array, callable $callback)

Filter the array using the given callback.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $array **array**
* $callback **callable**



### macro

    void Yao\Support\Arr::macro(string $name, callable $macro)

Register a custom macro.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $name **string**
* $macro **callable**



### hasMacro

    boolean Yao\Support\Arr::hasMacro(string $name)

Checks if macro is registered.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $name **string**



### __callStatic

    mixed Yao\Support\Arr::__callStatic(string $method, array $parameters)

Dynamically handle calls to the class.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $method **string**
* $parameters **array**



### __call

    mixed Yao\Support\Arr::__call(string $method, array $parameters)

Dynamically handle calls to the class.



* Visibility: **public**


#### Arguments
* $method **string**
* $parameters **array**


