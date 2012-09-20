<?php
class Property extends Node {
	protected $key;
	protected $value;

	protected $keyString;

	public function __construct($key, Expression $value) {
		if (!$key instanceof Identifier && !$key instanceof ConstantExpression) {
			throw new InvalidArgumentException('Key must be constant expression');
		}

		$this->key = $key;
		$this->value = $value;

		$this->keyString = $key->toString();

		parent::__construct();
	}

	public function visit(AST $ast, Node $parent = null) {
		if ($this->key instanceof String) {
			if (Identifier::isValid($test = $this->key->asString())) {
				$this->key = new Identifier(null, $test);
				$this->keyString = $this->key->toString();
			} elseif (is_numeric($test) && ($testNum = new Number($test)) && $testNum->asString() === $test) {
				$this->key = $testNum;
				$this->keyString = $this->key->toString();
			}
		}

		$this->value = $this->value->visit($ast, $this);

		return $this;
	}

	public function gone() {
		$this->value->gone();
	}

	public function collectStatistics(AST $ast) {
		$this->value->collectStatistics($ast);
	}

	public function countLetters(&$letters) {
		foreach(array_keys($letters) as $letter) {
			$letters[$letter] += substr_count($this->keyString, $letter);
		}

		$this->value->countLetters($letters);
	}

	public function toString() {
		$v = $this->value->toString();
		if ($this->value instanceof CommaExpression) {
			$v = '(' . $v . ')';
		}
		return $this->keyString . ':' . (AST::$options['beautify'] ? ' ' : '') . $v;
	}
}