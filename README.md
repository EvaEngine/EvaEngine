EvaEngine
=========

[![Latest Stable Version](https://poser.pugx.org/evaengine/evaengine/v/stable.svg)](https://packagist.org/packages/evaengine/evaengine)
[![License](https://poser.pugx.org/evaengine/evaengine/license.svg)](https://packagist.org/packages/evaengine/evaengine)
[![Build Status](https://travis-ci.org/EvaEngine/EvaEngine.svg?branch=master)](https://travis-ci.org/EvaEngine/EvaEngine)
[![Coverage Status](https://coveralls.io/repos/EvaEngine/EvaEngine/badge.png?branch=master)](https://coveralls.io/r/EvaEngine/EvaEngine?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/EvaEngine/EvaEngine/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/EvaEngine/EvaEngine/?branch=master)

A development engine based on [Phalcon Framework](http://phalconphp.com/). Fixed & changed some phalcon behaviors. Major features including:
 
- Fully module support
- Some build-in components (set into DI) for common web development
- CLI mode
- Better exceptions & error handle 

Thanks the icon from [Hrvoje Bielen](http://cargocollective.com/bielen)

----

## Development Tools

### Switch environment

Show current env

```
./engine env
```

Show single env variable

```
./engine env app
```

Set env variables
```
./engine env --App=Wscn --Module=EvaBlog
```

### Exception Tool

List all exception code:

```
./engine exc
```

Search exception code:

```
./engine exc 116430001083568710
```

### Create a Module

Create a standard module:

```
./engine make:module EvaTest
```

Will generate `Eva\EvaTest` module under `project/modules` folder.

Create a module with specific namespace and path:

```
./engine make:module EvaTest --namespace="Test\Foo" --target="logs"
```

Will generate `EvaTest` module with `Test\Foo` namespace under `logs/` folder.
 
### Create an Application

As same as `make:module`, default generate path is `project/apps`

```
./engine make:app MobileSite
```


### Create Controller

```
./engine make:controller Index

./engine make:controller Index --for-admin
```

### Create Entity

Generate entity under env Module

```
./engine make:entity Posts
```

```
./engine make:entity Posts --module=EvaBlog --db-table=blog_posts --namespace='Test\Eva\'
```

### Create Form

### Add Module to An App

### Merge all modules/apps composer.json

### Scaffold to create an CURD of admin

Template source load from App, then Engine

```
./engine scaffold 
```

### Database Migration

Generate migration files for env module

```
./engine make:migration
```

Generate migration files for specific module

```
./engine make:migration --module=EvaBlog
```

Generate migration file for specific db table

```
./engine make:migration blog_posts --target=./tests --db-prefix=eva_
```

Traversal all modules and generate all migrations

```
./engine make:migration --all
```
----

## Start A New Project:

```
mkdir yinxing
cd yinxing

# init project
composer require evaengine/evaengine:1.0.x-dev
./vendor/bin/engine make:project

# create module
./vendor/bin/engine make:module EvaMovie

# set environment as created module, as well as db name/username/password/etc
./vendor/bin/engine env --module=EvaMovie --db_dbname=yinxing --db_username=root --db_password=password

# create entity from DB
./vendor/bin/engine make:entity Movies --db-table=movie_movies

# create app for REST API
./vendor/bin/engine make:app YinxingApiVer1 
```

## Exceptions Design

EvaEngine exceptions contain status code for http response. If an exception throw to top, EvaEngine will use `Error\ErrorHandler` to catch exception and set status code into response.

Interfaces:

- `ExceptionInterface`
- `HttpRequestExceptionInterface` Exception will keep http request / response when calling remote resource failed
- `DatabaseExceptionInterface` Exception will keep database error messages

Exceptions dependents are as below:

- `StandardException extends PhalconException implements ExceptionInterface` 500
  - `LogicException` 400 
    - `BadFunctionCallException` 400
    - `BadMethodCallException` 405
    - `DomainException` 400
    - `InvalidArgumentException` 400
      - `FormValidateException` 400
      - `HttpRequestInvalidArgumentException` implements `HttpRequestExceptionInterface` 400
    - `LengthException` 400
    - `OperationNotPermittedException` 403
    - `ResourceConflictException` 409
    - `ResourceExpiredException`  403
    - `ResourceNotFoundException`  404
    - `UnauthorizedException` 401
    - `VerifyFailedException` 403
    - `UnsupportedOperationException` 405
    - `HttpRequestLogicException` implements `HttpRequestExceptionInterface` 403
  - `RuntimeException` 500
    - `IOException`
      - `HttpRequestIOException` implements `HttpRequestExceptionInterface`
      - `DatabaseWriteException` implements `DatabaseExceptionInterface` 
