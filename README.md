# PharBuilder

My custom phar (PHp ARchive) builder

## Feature

* Create a phar from a directory
* load all php file in phar root
* loader script
* main script when phar directly executed
* automatic executable permition when main script passed

## Instalation

Download `phar-builder.phar`

## Usage

### From CLI

```
./phar-builder.phar src/ dist.phar
```

Optional argument
```
-l, --loaderFile <file> Set the file used for load the phar when include
-m, --mainFile <file>   Set the file used when the phar is directly executed
```

### From PHP file

```php
require_once 'phar-builder.phar';

// create PharBuilder object with source dir and output file
$builder = new PharBuilder\PharBuilder('src', 'dist.phar');

// set the default stub (phar loader script)
$builder->createDefaultStub();

// create the phar
$builder->createPhar();
```

#### Type of stub

```php
// load all php file in the root directory when import
$builder->createDefaultStub();

// use 'loader.php' as phar loader file
$builder->createStubLoader('loader.php');

// use 'main.php' as phar main file and load all php file when import
$builder->createStubMain('main.php');

// use 'main.php' as phar main file and 'loader.php' as loader file
$builder->createStubMainAndLoader('main.php', 'loader.php');
```

## Exemple

```sh
$ cat src/main.php
<?php echo "Hello from main.php!\n";
$ cat src/loader.php
<?php echo "Hello from loader.php!\n";
$ ./phar-builder.phar src/ test.phar -m main.php -l loader.php
Source: 76B (76 bytes)
Stub: 209B (209 bytes)
Source + Stub: 285B (285 bytes)
Phar: 414B (414 bytes)
$ ./test.phar
Hello from main.php!
$ cat test.php
<?php require 'test.phar';
$ php test.php
Hello from loader.php!
```

## Build

```
./build
```

Or using directly phar-builder.phar
```
./phar-builder.phar src phar-builder.phar -m cli.php -l phar-builder.php
```
:warning: work only if phar file tree is unchanged (php cache it)