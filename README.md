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
