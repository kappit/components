<?php
declare(strict_types = 1);
namespace Kappit\AbstractClasses;

abstract class AbstractTemplate
{
	abstract public function __construct (object $viewNodel);
}