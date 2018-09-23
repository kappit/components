<?php
declare(strict_types = 1);
namespace Kappit\AbstractClasses;

abstract class AbstractStronglyTypedView
{
	private $model;

	public function __construct (AbstractModel $model)
	{
		$this->model = $model;
	}

	abstract protected function template (AbstractModel $model) : void;

	public function __destruct ()
	{
		$this->template($this->model);
	}
}