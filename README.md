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

## Exception Design

EvaEngine exceptions contains status code for http response. If an exception throw to top, EvaEngine will use `Error\ErrorHandler` to catch exception and set status code into response.

Exceptions dependents are as below:

- `StandardException extends PhalconException implements ExceptionInterface`
  - `LogicException`
    - `BadFunctionCallException`
    - `BadMethodCallException`
    - `DomainException`
    - `InvalidArgumentException`
    - `LengthException`
    - `OperationNotPermitedException`
    - `ResourceConflictException`
    - `ResourceExpiredException`
    - `ResourceNotFoundException`
    - `UnauthorizedException`
    - `VerifyFailedException`
  - `RuntimeException`
    - `IOException`
