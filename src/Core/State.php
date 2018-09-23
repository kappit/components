<?php
declare(strict_types = 1);
namespace Kappit\Core;

use ICanBoogie\Inflector;
use Kappit\AbstractClasses\AbstractRoute;

final class State
{
	private static $instance;
	private $appName = '';
	private $parameters = [];

	private function __construct (string $appName)
	{
		try {
			$this->appName = $appName;
			$uri = \filter_input(INPUT_GET, 'uri', FILTER_SANITIZE_URL);
			$possibleRoute = $this->appName . '\\' . $uri;
			$posaibleRouteTrimSlashes = \rtrim($possibleRoute, '/');
			$possibleRouteReplaceSlashes = \str_replace('/', '\\', $posaibleRouteTrimSlashes);
			$this->findRoute($possibleRouteReplaceSlashes);
		} catch (\Exception $e) {
			\http_response_code(500);
			die($e->getMessage());
		}
	}

	private function findRoute (string $possibleRoute) : void
	{
		try {
			$inflector = Inflector::get('en');
			$possibleRouteCamelize = $inflector->camelize($possibleRoute);
			$possibleRouteClass = $possibleRouteCamelize . 'Route';
			$reflectionClass = new \ReflectionClass($possibleRouteClass);
			$instantiable = $reflectionClass->isInstantiable();
			$name = $reflectionClass->getName();
			$parentClass = $reflectionClass->getParentClass();
			$instanceOfRoute = $parentClass->getName() === AbstractRoute::class;
			if (!($instantiable && $instanceOfRoute && $possibleRouteClass === $name)) {
				throw new \Exception();
			}
			$parametersCount = \count($this->parameters);
			$reflectionMethod = $reflectionClass->getMethod('component');
			$numberOfParameters = $reflectionMethod->getNumberOfParameters();
			$numberOfRequiredParameters = $reflectionMethod->getNumberOfRequiredParameters();
			$validateInt = \filter_var($parametersCount,
			FILTER_VALIDATE_INT,
			[
			'options' => [
			'min_range' => $numberOfRequiredParameters,
			'max_range' => $numberOfParameters
			]
			]);
			if ($validateInt === false) {
				throw new \Exception();
			}
			$reflectionParameters = $reflectionMethod->getParameters();
			$reflectionParametersCount = \count($reflectionParameters);
			$parameters = [];
			for ($i = 0; $i < $reflectionParametersCount; $i++) {
				$reflectionParameter = $reflectionParameters[$i];
				$reflectionNamedType = $reflectionParameter->getType();
				$defaultValueAvailable = !$reflectionParameter->isDefaultValueAvailable();
				if ($defaultValueAvailable) {
					$parameters[] = $this->prepareParameter($reflectionNamedType, $this->parameters[$i]);
				} else
					if (\array_key_exists($i, $this->parameters)) {
						$parameters[] = $this->prepareParameter($reflectionNamedType, $this->parameters[$i]);
					}
			}
			(new $possibleRouteClass())->component(...$parameters);
		} catch (\Exception $e) {
			switch ($e->getLine()) {
				case 34:
					$stringPosition = \mb_strrpos($possibleRoute, '\\');
					$parameter = \mb_substr($possibleRoute, $stringPosition + 1);
					\array_unshift($this->parameters, $parameter);
					$possibleRoute = \mb_substr($possibleRoute, 0, $stringPosition);
					if ($possibleRoute !== $this->appName) {
						$this->findRoute($possibleRoute);
						break;
					}
					\http_response_code(404);
					die('Not Found');
					break;
				case 43:
				case 55:
					\http_response_code(404);
					die('Not Found');
					break;
				default:
					throw new \Exception($e->getMessage());
			}
		}
	}

	private function prepareParameter (ReflectionNamedType $reflectionNamedType, string $value)
	{
		switch ($reflectionNamedType->getName()) {
			case 'bool':
				$valueSanitized = \filter_var($value, FILTER_SANITIZE_STRING);
				$valueValidated = \filter_var($valueSanitized, FILTER_VALIDATE_BOOLEAN);

				return $valueValidated;
			case 'float':
				$valueSanitized = \filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT);
				$valueValidated = \filter_var($valueSanitized, FILTER_VALIDATE_FLOAT);

				return $valueValidated;
			case 'int':
				$valueSanitized = \filter_var($value, FILTER_SANITIZE_NUMBER_INT);
				$valueValidated = \filter_var($valueSanitized, FILTER_VALIDATE_INT);

				return $valueValidated;
			case 'string':
				$valueSanitized = \filter_var($value, FILTER_SANITIZE_STRING);

				return $valueSanitized;
		}
	}

	public static function getInstance (string $appName) : self
	{
		if (!self::$instance) {
			self::$instance = new static($appName);
		}

		return self::$instance;
	}
}