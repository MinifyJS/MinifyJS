<?php
class Property extends Node {
	protected $key;
	protected $value;

	public function __construct($key, Expression $value) {
		if (!$key instanceof Identifier && !$key instanceof ConstantExpression) {
			throw new InvalidArgumentException('Key must be constant expression');
		}

		$this->key = $key;
		$this->value = $value;

		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->value->visit($ast);

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->value->collectStatistics($ast);
	}

	public function toString() {
		return $this->key->toString() . ':' . $this->value->toString();
	}
}