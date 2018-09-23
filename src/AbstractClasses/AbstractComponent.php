<?php
declare(strict_types = 1);
namespace Kappit\AbstractClasses;

abstract class AbstractComponent
{
	abstract protected function controller () : object;

	abstract protected function templateUrl () : string;

	public function __destruct ()
	{
		$controller = $this->controller();
		$reflectionObject = new \ReflectionObject($controller);
		$reflectionMethod = $reflectionObject->getMethod('injections');
		$reflectionNamedType = $reflectionMethod->getReturnType();
		if (!($reflectionNamedType && $reflectionNamedType->getName() === 'void')) {
			throw new \Exception();
		}
		$reflectionParameters = $reflectionMethod->getParameters();
		$reflectionParametersCount = \count($reflectionParameters);
		$parameters = [];
		for ($i = 0; $i < $reflectionParametersCount; $i++) {
			$reflectionParameter = $reflectionParameters[$i];
			$reflectionNamedType = $reflectionParameter->getType();
			$injectionClass = $reflectionNamedType->getName();
			$reflectionClass = new \ReflectionClass($injectionClass);
			$instantiable = $reflectionClass->isInstantiable();
			$name = $reflectionClass->getName();
			if (!($instantiable && $injectionClass === $name)) {
				throw new \Exception();
			}
			$parameters[] = new $injectionClass();
		}
		$controller->injections(...$parameters);
		$templateUrl = $this->templateUrl();
		$stringPosition = \mb_strpos($templateUrl, '\\');
		$templateUrlSubstring = \mb_substr($templateUrl, $stringPosition + 1);
		$templateClass = 'Templates\\' . $templateUrlSubstring . 'Template';
		$reflectionClass = new \ReflectionClass($templateClass);
		$instantiable = $reflectionClass->isInstantiable();
		$name = $reflectionClass->getName();
		$parentClass = $reflectionClass->getParentClass();
		$instanceOfTemplate = $parentClass->getName() === AbstractTemplate::class;
		if (!($instantiable && $instanceOfTemplate && $templateClass === $name)) {
			throw new \Exception();
		}
		$reflectionProperties = $reflectionObject->getProperties();
		$reflectionPropertiesCount = \count($reflectionProperties);
		$properties = new \stdClass();
		for ($i = 0; $i < $reflectionPropertiesCount; $i++) {
			$reflectionProperty = $reflectionProperties[$i];
			$name = $reflectionProperty->getName();
			$value = $reflectionProperty->getValue($controller);
			$properties->$name = $value;
		}
		new $templateClass($properties);
	}
}