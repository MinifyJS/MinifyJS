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
		if ($this->key instanceof String) {
			$test = $this->key->asString();

			if (Identifier::isValid($test)) {
				$this->key = new Identifier(null, $test);
			}
		}

		$this->value = $this->value->visit($ast);

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->value->collectStatistics($ast);
	}

	public function toString() {
		return $this->key->toString() . ':' . $this->value->toString();
	}
}